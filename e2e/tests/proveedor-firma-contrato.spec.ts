import { expect, test, type APIRequestContext } from '@playwright/test';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

async function loginToken(request: APIRequestContext, email: string, password: string) {
  for (let i = 0; i < 2; i++) {
    const res = await request.post('/api/v1/auth/login', { data: { email, password } });
    if (res.status() === 429 && i === 0) { await new Promise((r) => setTimeout(r, 65_000)); continue; }
    expect(res.ok(), `login ${res.status()}`).toBeTruthy();
    return (await res.json()).data.token as string;
  }
  throw new Error('loginToken failed');
}

test.describe('Firma de contrato por proveedor', () => {
  let providerToken = '';
  let adminToken = '';
  let meData: Record<string, unknown> = {};

  test.beforeAll(async ({ request }) => {
    providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    await new Promise((r) => setTimeout(r, 2_000));
    adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    meData = (await (await request.get('/api/v1/me', { headers: { Authorization: `Bearer ${providerToken}` } })).json())?.data ?? {};
  });

  test('API: no se puede firmar contrato que no es EN_FORMALIZACION', async ({ request }) => {
    const res = await request.get('/api/v1/contratos/mios?page=1&limit=20', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(res.status()).toBe(200);
    const items = ((await res.json())?.data?.items || []) as Array<Record<string, unknown>>;
    const noFormalizacion = items.find((c) => c.estatus !== 'EN_FORMALIZACION');
    if (noFormalizacion) {
      const post = await request.post(`/api/v1/contratos/${noFormalizacion.id_contrato}/firma`, {
        headers: { Authorization: `Bearer ${providerToken}` },
      });
      expect(post.status()).toBe(422);
      const payload = await post.json();
      expect(payload?.errors?.join?.(' ') || payload?.message || '').toMatch(/EN_FORMALIZACION|firmado/i);
    }
  });

  test('API: admin no puede usar el endpoint de firma', async ({ request }) => {
    const res = await request.post('/api/v1/contratos/1/firma', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(res.status()).toBe(403);
  });

  test('API: proveedor puede firmar contrato EN_FORMALIZACION', async ({ request }) => {
    const res = await request.get('/api/v1/contratos/mios?page=1&limit=20', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const items = ((await res.json())?.data?.items || []) as Array<Record<string, unknown>>;
    const formalizacion = items.find((c) => c.estatus === 'EN_FORMALIZACION' && !c.fecha_firma_proveedor);

    if (!formalizacion) { test.skip(true, 'Sin contratos EN_FORMALIZACION sin firmar'); return; }

    const post = await request.post(`/api/v1/contratos/${formalizacion.id_contrato}/firma`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(post.status()).toBe(200);

    // Verificar que fecha_firma_proveedor se registró
    const res2 = await request.get(`/api/v1/contratos/mios?page=1&limit=20&id_contrato=${formalizacion.id_contrato}`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const items2 = ((await res2.json())?.data?.items || []) as Array<Record<string, unknown>>;
    const firmado = items2.find((c) => String(c.id_contrato) === String(formalizacion.id_contrato));
    expect(firmado?.fecha_firma_proveedor).toBeTruthy();

    // No se puede firmar dos veces
    const post2 = await request.post(`/api/v1/contratos/${formalizacion.id_contrato}/firma`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(post2.status()).toBe(422);
  });

  test('UI: botón Firmar visible en contrato EN_FORMALIZACION sin firma', async ({ page, request }) => {
    const res = await request.get('/api/v1/contratos/mios?page=1&limit=5', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const items = ((await res.json())?.data?.items || []) as Array<Record<string, unknown>>;
    if (!items.length) { test.skip(true, 'Sin contratos'); return; }

    await page.addInitScript(({ t, u }: { t: string; u: unknown }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: providerToken, u: meData });

    await page.goto(`/frontend/proveedor/contrato.html?id=${items[0].id_contrato}`);
    await expect(page.locator('#numero')).not.toContainText('Cargando', { timeout: 15_000 });

    const enFormalizacion = items[0].estatus === 'EN_FORMALIZACION' && !items[0].fecha_firma_proveedor;
    if (enFormalizacion) {
      await expect(page.locator('#firmar-btn')).toBeVisible();
    } else {
      await expect(page.locator('#firma-badge')).toBeVisible();
    }
  });
});
