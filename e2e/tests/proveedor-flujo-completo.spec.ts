import { expect, test, type APIRequestContext } from '@playwright/test';

/**
 * Prueba E2E completa del flujo proveedor:
 *   login -> convocatoria -> detalle -> inscripción -> propuesta -> documentos -> contrato.
 *
 * Se ejecuta contra Railway production por defecto. Para evitar ruido en DB y
 * fallos idempotentes (ya inscrito, ya existe propuesta), cada paso tolera
 * respuestas 422/409 cuando el estado remoto ya refleja la operación.
 *
 * Esta suite combina:
 *  - Recorrido UI navegando por centro, convocatorias, detalle, participaciones,
 *    propuestas, documentos y contratos, verificando el badge de estatus en
 *    cada pantalla.
 *  - Recorrido API que exhibe la secuencia backend completa y es independiente
 *    del navegador.
 */

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

type ApiEnvelope<T = unknown> = {
  ok?: boolean;
  message?: string;
  data?: T;
  errors?: string[];
};

async function loginToken(
  request: APIRequestContext,
  email: string,
  password: string
): Promise<string> {
  const response = await request.post('/api/v1/auth/login', {
    data: { email, password },
  });
  expect(response.ok(), `login falló para ${email}`).toBeTruthy();
  const payload = (await response.json()) as ApiEnvelope<{ token?: string }>;
  const token = payload?.data?.token;
  expect(token, 'el backend no regresó token JWT').toBeTruthy();
  return token as string;
}

async function getMe(request: APIRequestContext, token: string) {
  const res = await request.get('/api/v1/me', {
    headers: { Authorization: `Bearer ${token}` },
  });
  expect(res.status(), 'GET /me debe responder 200').toBe(200);
  return (await res.json()) as ApiEnvelope<Record<string, unknown>>;
}

test.describe('Proveedor: flujo completo login -> convocatoria -> contrato', () => {
  test.describe.configure({ mode: 'serial' });

  test('API: recorrido completo login, listado, detalle, inscripción, propuesta, documentos y contratos', async ({ request }) => {
    test.setTimeout(120_000);

    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // 1. /me: validar rol, perfil proveedor y estatus
    const mePayload = await getMe(request, token);
    const me = mePayload?.data as Record<string, unknown> | undefined;
    expect(me?.rol, '/me debe regresar rol PROVEEDOR').toBe('PROVEEDOR');
    const proveedor = (me?.proveedor as Record<string, unknown> | undefined) ?? undefined;
    const estatus = (proveedor?.estatus as string | undefined) ?? undefined;
    expect(['PENDIENTE', 'VALIDADO', 'RECHAZADO', 'SUSPENDIDO']).toContain(estatus);
    const canOperate = estatus === 'VALIDADO';

    // 2. Listado de convocatorias disponibles para el proveedor
    const licRes = await request.get('/api/v1/licitaciones?limit=20&sort=fecha_creacion&order=DESC', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(licRes.status()).toBe(200);
    const licPayload = (await licRes.json()) as ApiEnvelope<Array<Record<string, unknown>>>;
    const licitaciones = licPayload?.data ?? [];
    expect(Array.isArray(licitaciones)).toBeTruthy();

    // Elegimos la primera licitación disponible para revisar el detalle.
    const detallePrimera = licitaciones[0];
    if (detallePrimera) {
      const detalleRes = await request.get(`/api/v1/licitaciones/${String(detallePrimera.id_licitacion)}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      expect(detalleRes.status()).toBe(200);
      const detallePayload = (await detalleRes.json()) as ApiEnvelope<Record<string, unknown>>;
      expect(detallePayload?.data?.id_licitacion).toBeDefined();
    }

    // 3. Inscripción: elegimos una licitación en estado elegible. Toleramos ya estar inscrito.
    const estadosInscripcion = new Set(['PUBLICADA', 'EN_ACLARACIONES', 'RECEPCION_PROPUESTAS']);
    const candidatoInscripcion = licitaciones.find((lic) =>
      estadosInscripcion.has(String(lic.estado_proceso))
    );

    let inscripcionStatus: 'creada' | 'existente' | 'no-validado' | 'sin-candidato' = 'sin-candidato';
    if (candidatoInscripcion) {
      const inscribirRes = await request.post(
        `/api/v1/licitaciones/${String(candidatoInscripcion.id_licitacion)}/participaciones`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      const status = inscribirRes.status();
      if (status === 201 || status === 200) {
        inscripcionStatus = 'creada';
      } else if (status === 409) {
        // Conflicto: ya inscrito en esta licitación (idempotente)
        const payload = (await inscribirRes.json()) as ApiEnvelope;
        const msg = (payload?.errors || []).join(' ').toLowerCase();
        expect(msg).toContain('ya está inscrito');
        inscripcionStatus = 'existente';
      } else if (status === 422) {
        const payload = (await inscribirRes.json()) as ApiEnvelope;
        const msg = (payload?.errors || []).join(' ').toLowerCase();
        if (msg.includes('validado')) {
          inscripcionStatus = 'no-validado';
          expect(canOperate, 'backend rechaza por estatus != VALIDADO, consistente con /me').toBeFalsy();
        } else if (msg.includes('no permite inscripciones')) {
          // licitación cambió de estado entre listado y POST
          inscripcionStatus = 'sin-candidato';
        } else {
          throw new Error(`Inscripción rechazada inesperadamente: ${msg}`);
        }
      } else {
        throw new Error(`Respuesta inesperada al inscribirse: ${status}`);
      }
    }

    // 4. Consultar "mis participaciones"
    const partRes = await request.get('/api/v1/participaciones/mias?page=1&limit=50', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(partRes.status()).toBe(200);
    const partPayload = (await partRes.json()) as ApiEnvelope<{ items?: Array<Record<string, unknown>>; total?: number }>;
    const participaciones = partPayload?.data?.items ?? [];
    expect(Array.isArray(participaciones)).toBeTruthy();

    // 5. Propuesta: enviar sólo si hay una participación elegible y proveedor validado.
    const participacionParaPropuesta = participaciones.find((item) =>
      String(item.estado_proceso) === 'RECEPCION_PROPUESTAS' && !item.id_propuesta
    );
    let propuestaId: number | null = null;
    if (participacionParaPropuesta && canOperate) {
      const propuestaRes = await request.post(
        `/api/v1/participaciones/${String(participacionParaPropuesta.id_participacion)}/propuesta`,
        {
          headers: { Authorization: `Bearer ${token}` },
          data: {
            monto_propuesta: 150_000,
            descripcion_tecnica: 'Propuesta E2E generada por Playwright — flujo completo proveedor.',
          },
        }
      );
      const status = propuestaRes.status();
      if (status === 201 || status === 200) {
        const payload = (await propuestaRes.json()) as ApiEnvelope<{ id_propuesta?: number }>;
        propuestaId = payload?.data?.id_propuesta ?? null;
        expect(propuestaId).toBeTruthy();
      } else if (status === 422) {
        const payload = (await propuestaRes.json()) as ApiEnvelope;
        const msg = (payload?.errors || []).join(' ').toLowerCase();
        expect(
          msg.includes('ya existe una propuesta') ||
            msg.includes('no está recibiendo propuestas') ||
            msg.includes('validado'),
          `mensaje inesperado al enviar propuesta: ${msg}`
        ).toBeTruthy();
      } else {
        throw new Error(`Respuesta inesperada al enviar propuesta: ${status}`);
      }
    }

    // 6. Documentos: subir un PNG de prueba como documento legal del proveedor y listar.
    const png1x1 = Buffer.from(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/luz1NwAAAABJRU5ErkJggg==',
      'base64'
    );
    const filename = `flujo-completo-e2e-${Date.now()}.png`;
    const uploadRes = await request.post('/api/v1/documentos/upload', {
      headers: { Authorization: `Bearer ${token}` },
      multipart: {
        tipo_documento: 'DOC_LEGAL_PROVEEDOR',
        archivo: { name: filename, mimeType: 'image/png', buffer: png1x1 },
      },
    });
    expect([201, 200]).toContain(uploadRes.status());
    const uploadPayload = (await uploadRes.json()) as ApiEnvelope<{ id_documento?: number }>;
    const idDocumento = uploadPayload?.data?.id_documento;
    expect(idDocumento).toBeTruthy();

    const docsRes = await request.get('/api/v1/documentos/mios?context=proveedor', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(docsRes.status()).toBe(200);
    const docsPayload = (await docsRes.json()) as ApiEnvelope<{ items?: Array<{ id_documento: number }> }>;
    const docs = docsPayload?.data?.items ?? [];
    expect(docs.some((doc) => doc.id_documento === idDocumento)).toBeTruthy();

    const downloadRes = await request.get(`/api/v1/documentos/${idDocumento}/download`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(downloadRes.status()).toBe(200);
    expect((await downloadRes.body()).length).toBeGreaterThan(0);

    // 7. Contratos: listar los contratos propios (histórico). Puede estar vacío sin error.
    const contratosRes = await request.get('/api/v1/contratos/mios?page=1&limit=20', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(contratosRes.status()).toBe(200);
    const contratosPayload = (await contratosRes.json()) as ApiEnvelope<{ items?: Array<Record<string, unknown>>; total?: number }>;
    expect(Array.isArray(contratosPayload?.data?.items)).toBeTruthy();
    expect(typeof contratosPayload?.data?.total).toBe('number');

    // Meta: dejar constancia del resultado relevante para diagnóstico.
    test.info().annotations.push(
      { type: 'estatus-proveedor', description: estatus ?? 'desconocido' },
      { type: 'inscripcion', description: inscripcionStatus },
      { type: 'propuesta', description: propuestaId ? `creada id=${propuestaId}` : 'no-aplicable' },
      { type: 'documento-legal', description: `id=${idDocumento}` }
    );
  });

  test('UI: recorrido visual exhibiendo el badge de estatus en cada pantalla del proveedor', async ({ page }) => {
    test.setTimeout(120_000);

    // Login
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(PROVIDER_EMAIL);
    await page.locator('#password').fill(PROVIDER_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();
    await page.waitForURL('**/frontend/proveedor/centro.html');

    // Centro: badge y banner visibles y cargados (no "Cargando estatus...")
    await expect(page.getByRole('heading', { name: /bienvenido/i })).toBeVisible();
    const centroBadge = page.getByTestId('estatus-proveedor');
    await expect(centroBadge).toBeVisible();
    await expect(centroBadge).not.toContainText('Cargando', { timeout: 15_000 });
    const centroBanner = page.getByTestId('estatus-banner');
    await expect(centroBanner).toBeVisible();
    await expect(centroBanner).toHaveAttribute('data-estatus', /(PENDIENTE|VALIDADO|RECHAZADO|SUSPENDIDO)/);
    const estatus = (await centroBadge.getAttribute('data-estatus')) || '';

    // Convocatorias: badge presente y lista cargada
    await page.getByRole('link', { name: /Convocatorias/i }).first().click();
    await page.waitForURL('**/frontend/proveedor/convocatorias.html');
    await expect(page.getByRole('heading', { name: /Convocatorias disponibles/i })).toBeVisible();
    const convocatoriasBadge = page.getByTestId('estatus-proveedor');
    await expect(convocatoriasBadge).toBeVisible();
    await expect(convocatoriasBadge).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 20_000 });

    // Detalle de licitación: click en primer "Ver detalle" si existe
    const detailLink = page.getByRole('link', { name: /Ver detalle/i }).first();
    if (await detailLink.count()) {
      await detailLink.click();
      await page.waitForURL(/\/frontend\/proveedor\/licitacion\.html\?id=\d+/);
      await expect(page.getByText(/Cronograma/i)).toBeVisible();
      await expect(page.getByText(/Reglas de participación/i)).toBeVisible();
      const detalleBadge = page.getByTestId('estatus-proveedor');
      await expect(detalleBadge).toBeVisible();
      await expect(detalleBadge).not.toContainText('Cargando', { timeout: 15_000 });

      // Si estatus != VALIDADO y hay botón inscribirme, debe estar deshabilitado o con "Requiere validación".
      if (estatus && estatus !== 'VALIDADO') {
        const inscribirBtn = page.locator('#inscribir-btn');
        if (await inscribirBtn.isVisible().catch(() => false)) {
          await expect(inscribirBtn).toBeDisabled();
        }
      }
      // Volver a convocatorias
      await page.goto('/frontend/proveedor/convocatorias.html');
      await expect(page.getByRole('heading', { name: /Convocatorias disponibles/i })).toBeVisible();
    }

    // Participaciones
    await page.goto('/frontend/proveedor/participaciones.html');
    await expect(page.getByRole('heading', { name: /Mis participaciones/i })).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 20_000 });

    // Propuestas
    await page.goto('/frontend/proveedor/propuestas.html');
    await expect(page.getByRole('heading', { name: /Mis propuestas/i })).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 20_000 });
    if (estatus && estatus !== 'VALIDADO') {
      await expect(page.locator('#create-submit')).toBeDisabled();
    }

    // Documentos
    await page.goto('/frontend/proveedor/documentos.html');
    await expect(page.getByRole('heading', { name: /^Documentos$/i })).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 20_000 });

    // Contratos
    await page.goto('/frontend/proveedor/contratos.html');
    await expect(page.getByRole('heading', { name: /Mis contratos/i })).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 20_000 });

    // Perfil (badge migrado a shared helper)
    await page.goto('/frontend/proveedor/perfil.html');
    await expect(page.getByRole('heading', { name: /Mi perfil/i })).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).toBeVisible();
    await expect(page.getByTestId('estatus-proveedor')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.getByTestId('estatus-banner')).toBeVisible();
  });
});
