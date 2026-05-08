import { expect, test } from '@playwright/test';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

async function loginToken(request: import('@playwright/test').APIRequestContext, email: string, password: string): Promise<string> {
  const response = await request.post('/api/v1/auth/login', {
    data: { email, password },
  });
  expect(response.ok()).toBeTruthy();
  const payload = await response.json();
  expect(payload?.data?.token).toBeTruthy();
  return payload.data.token as string;
}

test.describe('Proveedor hardening API', () => {
  test('public registration accepts legacy terms and rejects missing acceptance', async ({ request }) => {
    const uid = Date.now();

    const okResponse = await request.post('/api/v1/public/proveedores/registro', {
      data: {
        nombre_empresa: `Proveedor Hardening ${uid} SA de CV`,
        representante_legal: `Representante ${uid}`,
        registro_fiscal: `HRD${uid}RFC`,
        regimen_fiscal: '601',
        domicilio: `Calle ${uid}, Ciudad`,
        nombre_contacto: `Contacto ${uid}`,
        cargo: 'Director General',
        email: `proveedor.hardening.${uid}@example.com`,
        telefono: '5551234567',
        password: 'Password1234',
        terms: true,
      },
    });
    expect(okResponse.status()).toBe(201);

    const failResponse = await request.post('/api/v1/public/proveedores/registro', {
      data: {
        nombre_empresa: `Proveedor Hardening Fail ${uid} SA de CV`,
        representante_legal: `Representante Fail ${uid}`,
        registro_fiscal: `HRF${uid}RFC`,
        regimen_fiscal: '601',
        domicilio: `Calle Fail ${uid}, Ciudad`,
        nombre_contacto: `Contacto Fail ${uid}`,
        cargo: 'Director General',
        email: `proveedor.hardening.fail.${uid}@example.com`,
        telefono: '5551234567',
        password: 'Password1234',
        accepted_terms: false,
      },
    });
    expect(failResponse.status()).toBe(422);
    const failPayload = await failResponse.json();
    expect((failPayload?.errors || []).join(' ')).toContain('Debes aceptar los términos');
  });

  test('participaciones list enforces auth and admin role', async ({ request }) => {
    const noAuth = await request.get('/api/v1/participaciones?page=1&limit=5');
    expect(noAuth.status()).toBe(401);

    const providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const providerRes = await request.get('/api/v1/participaciones?page=1&limit=5', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(providerRes.status()).toBe(403);

    const adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    const adminRes = await request.get('/api/v1/participaciones?page=1&limit=5&q=E2E', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(adminRes.status()).toBe(200);
    const adminPayload = await adminRes.json();
    expect(adminPayload?.data).toBeTruthy();
    expect(Array.isArray(adminPayload.data.items)).toBeTruthy();
    expect(typeof adminPayload.data.total).toBe('number');
  });

  test('public estadisticas exposes total and active providers keys', async ({ request }) => {
    const response = await request.get('/api/v1/public/estadisticas');
    expect(response.status()).toBe(200);
    const payload = await response.json();
    const data = payload?.data || {};
    expect(typeof data.proveedores_registrados_total).toBe('number');
    expect(typeof data.proveedores_activos).toBe('number');
    expect(data.proveedores_registrados).toBe(data.proveedores_registrados_total);
  });
});
