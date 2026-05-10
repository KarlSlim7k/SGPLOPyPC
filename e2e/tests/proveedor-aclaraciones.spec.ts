import { expect, test, type APIRequestContext } from '@playwright/test';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

async function loginToken(request: APIRequestContext, email: string, password: string) {
  // Retry once after 65s if rate-limited (5 logins/60s in production)
  for (let attempt = 0; attempt < 2; attempt++) {
    const res = await request.post('/api/v1/auth/login', { data: { email, password } });
    if (res.status() === 429 && attempt === 0) {
      await new Promise((r) => setTimeout(r, 65_000));
      continue;
    }
    expect(res.ok(), `login falló para ${email}: ${res.status()}`).toBeTruthy();
    const payload = await res.json();
    return payload.data.token as string;
  }
  throw new Error('loginToken: no se pudo obtener token tras reintentos');
}

test.describe('Aclaraciones proveedor', () => {
  let providerToken = '';
  let adminToken = '';
  let meData: Record<string, unknown> = {};

  test.beforeAll(async ({ request }) => {
    providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    // Pequeña pausa para no saturar el rate limiter (5 logins/60s por IP)
    await new Promise((r) => setTimeout(r, 2_000));
    adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    const meRes = await request.get('/api/v1/me', { headers: { Authorization: `Bearer ${providerToken}` } });
    meData = (await meRes.json())?.data ?? {};
  });

  test('API: proveedor no puede enviar aclaración en licitación fuera de EN_ACLARACIONES', async ({ request }) => {
    const licRes = await request.get('/api/v1/licitaciones?limit=50', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(licRes.status()).toBe(200);
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;
    const noAclaracion = lics.find((l) => l.estado_proceso !== 'EN_ACLARACIONES');

    if (noAclaracion) {
      const res = await request.post(`/api/v1/licitaciones/${noAclaracion.id_licitacion}/aclaraciones`, {
        headers: { Authorization: `Bearer ${providerToken}` },
        data: { pregunta: 'Pregunta de prueba E2E' },
      });
      expect(res.status()).toBe(422);
      const payload = await res.json();
      expect(payload?.message?.toLowerCase()).toContain('aclaraciones');
    }
  });

  test('API: proveedor puede enviar aclaración y admin puede responder en licitación EN_ACLARACIONES', async ({ request }) => {
    const licRes = await request.get('/api/v1/licitaciones?limit=50&estado=EN_ACLARACIONES', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(licRes.status()).toBe(200);
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;

    if (!lics.length) {
      test.skip(true, 'No hay licitaciones EN_ACLARACIONES — test omitido');
      return;
    }

    const lic = lics[0];
    const idLicitacion = lic.id_licitacion;

    const postRes = await request.post(`/api/v1/licitaciones/${idLicitacion}/aclaraciones`, {
      headers: { Authorization: `Bearer ${providerToken}` },
      data: { pregunta: `Pregunta E2E ${Date.now()}` },
    });
    expect([201, 200]).toContain(postRes.status());
    const idAclaracion = (await postRes.json())?.data?.id_aclaracion;
    expect(idAclaracion).toBeTruthy();

    const listRes = await request.get(`/api/v1/licitaciones/${idLicitacion}/aclaraciones`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(listRes.status()).toBe(200);
    const items = (await listRes.json())?.data || [];
    expect(items.some((i: { id_aclaracion: number }) => i.id_aclaracion === idAclaracion)).toBeTruthy();

    const patchRes = await request.patch(`/api/v1/aclaraciones/${idAclaracion}/respuesta`, {
      headers: { Authorization: `Bearer ${adminToken}` },
      data: { respuesta: 'Respuesta oficial E2E' },
    });
    expect(patchRes.status()).toBe(200);

    const listRes2 = await request.get(`/api/v1/licitaciones/${idLicitacion}/aclaraciones`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const items2 = (await listRes2.json())?.data || [];
    const aclaracion = items2.find((i: { id_aclaracion: number }) => i.id_aclaracion === idAclaracion);
    expect(aclaracion?.respuesta).toBe('Respuesta oficial E2E');
  });

  test('UI: sección de aclaraciones visible en detalle de licitación', async ({ page, request }) => {
    // Obtener una licitación disponible vía API
    const licRes = await request.get('/api/v1/licitaciones?limit=5', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;
    if (!lics.length) {
      test.skip(true, 'Sin convocatorias disponibles');
      return;
    }
    const idLicitacion = lics[0].id_licitacion;

    // Inyectar sesión antes de que la página cargue
    await page.addInitScript(({ t, u }: { t: string; u: Record<string, unknown> }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: providerToken, u: meData });

    await page.goto(`/frontend/proveedor/licitacion.html?id=${idLicitacion}`);
    await expect(page.locator('h1')).not.toContainText('Detalle de licitación', { timeout: 15_000 });

    await expect(page.locator('#aclaraciones-section')).toBeVisible({ timeout: 10_000 });
    await expect(page.locator('#aclaraciones-list')).not.toContainText('Cargando', { timeout: 15_000 });
  });
});
