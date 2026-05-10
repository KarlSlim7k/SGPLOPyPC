import { expect, test } from '@playwright/test';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

async function loginToken(request: import('@playwright/test').APIRequestContext, email: string, password: string) {
  const res = await request.post('/api/v1/auth/login', { data: { email, password } });
  expect(res.ok()).toBeTruthy();
  const payload = await res.json();
  return payload.data.token as string;
}

test.describe('Aclaraciones proveedor', () => {
  test('API: proveedor no puede enviar aclaración en licitación fuera de EN_ACLARACIONES', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // Buscar una licitación que NO esté en EN_ACLARACIONES
    const licRes = await request.get('/api/v1/licitaciones?limit=50', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(licRes.status()).toBe(200);
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;
    const noAclaracion = lics.find((l) => l.estado_proceso !== 'EN_ACLARACIONES');

    if (noAclaracion) {
      const res = await request.post(`/api/v1/licitaciones/${noAclaracion.id_licitacion}/aclaraciones`, {
        headers: { Authorization: `Bearer ${token}` },
        data: { pregunta: 'Pregunta de prueba E2E' },
      });
      expect(res.status()).toBe(422);
      const payload = await res.json();
      expect((payload?.errors || []).join(' ').toLowerCase()).toContain('aclaraciones');
    }
  });

  test('API: proveedor puede enviar aclaración y admin puede responder en licitación EN_ACLARACIONES', async ({ request }) => {
    const adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    const providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // Buscar licitación EN_ACLARACIONES
    const licRes = await request.get('/api/v1/licitaciones?limit=50&estado=EN_ACLARACIONES', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(licRes.status()).toBe(200);
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;

    if (!lics.length) {
      test.skip(true, 'No hay licitaciones EN_ACLARACIONES en producción — test omitido');
      return;
    }

    const lic = lics[0];
    const idLicitacion = lic.id_licitacion;

    // Proveedor envía aclaración
    const postRes = await request.post(`/api/v1/licitaciones/${idLicitacion}/aclaraciones`, {
      headers: { Authorization: `Bearer ${providerToken}` },
      data: { pregunta: `Pregunta E2E ${Date.now()}` },
    });
    expect([201, 200]).toContain(postRes.status());
    const postPayload = await postRes.json();
    const idAclaracion = postPayload?.data?.id_aclaracion;
    expect(idAclaracion).toBeTruthy();

    // Proveedor lista sus aclaraciones
    const listRes = await request.get(`/api/v1/licitaciones/${idLicitacion}/aclaraciones`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(listRes.status()).toBe(200);
    const items = (await listRes.json())?.data || [];
    expect(items.some((i: { id_aclaracion: number }) => i.id_aclaracion === idAclaracion)).toBeTruthy();

    // Admin responde
    const patchRes = await request.patch(`/api/v1/aclaraciones/${idAclaracion}/respuesta`, {
      headers: { Authorization: `Bearer ${adminToken}` },
      data: { respuesta: 'Respuesta oficial E2E' },
    });
    expect(patchRes.status()).toBe(200);

    // Proveedor ve la respuesta
    const listRes2 = await request.get(`/api/v1/licitaciones/${idLicitacion}/aclaraciones`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const items2 = (await listRes2.json())?.data || [];
    const aclaracion = items2.find((i: { id_aclaracion: number }) => i.id_aclaracion === idAclaracion);
    expect(aclaracion?.respuesta).toBe('Respuesta oficial E2E');
  });

  test('UI: sección de aclaraciones visible en detalle de licitación', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(PROVIDER_EMAIL);
    await page.locator('#password').fill(PROVIDER_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();
    await page.waitForURL('**/frontend/proveedor/centro.html');

    await page.goto('/frontend/proveedor/convocatorias.html');
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15_000 });

    const detailLink = page.getByRole('link', { name: /Ver detalle/i }).first();
    if (!(await detailLink.count())) {
      test.skip(true, 'Sin convocatorias disponibles');
      return;
    }
    await detailLink.click();
    await page.waitForURL(/\/frontend\/proveedor\/licitacion\.html\?id=\d+/);

    await expect(page.getByText(/Aclaraciones/i)).toBeVisible({ timeout: 10_000 });
    await expect(page.locator('#aclaraciones-list')).not.toContainText('Cargando', { timeout: 15_000 });
  });
});
