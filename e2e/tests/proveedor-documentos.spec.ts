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

test.describe('Proveedor documentos', () => {
  test('provider can upload, list and download own legal document', async ({ request }) => {
    const providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const filename = `documento-legal-e2e-${Date.now()}.png`;
    const png1x1 = Buffer.from(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/luz1NwAAAABJRU5ErkJggg==',
      'base64'
    );

    const upload = await request.post('/api/v1/documentos/upload', {
      headers: { Authorization: `Bearer ${providerToken}` },
      multipart: {
        tipo_documento: 'DOC_LEGAL_PROVEEDOR',
        archivo: {
          name: filename,
          mimeType: 'image/png',
          buffer: png1x1,
        },
      },
    });
    expect(upload.status()).toBe(201);
    const uploadPayload = await upload.json();
    const idDocumento = uploadPayload?.data?.id_documento;
    expect(idDocumento).toBeTruthy();

    const list = await request.get('/api/v1/documentos/mios?context=proveedor&tipo_documento=DOC_LEGAL_PROVEEDOR', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(list.status()).toBe(200);
    const listPayload = await list.json();
    expect((listPayload?.data?.items || []).some((item: { id_documento: number }) => item.id_documento === idDocumento)).toBeTruthy();

    const download = await request.get(`/api/v1/documentos/${idDocumento}/download`, {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(download.status()).toBe(200);
    expect((await download.body()).length).toBeGreaterThan(0);
  });

  test('admin is rejected from provider scoped documents endpoint', async ({ request }) => {
    const adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    const response = await request.get('/api/v1/documentos/mios', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(response.status()).toBe(403);
  });

  test('provider can open documentos module from center', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(PROVIDER_EMAIL);
    await page.locator('#password').fill(PROVIDER_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();

    await page.waitForURL('**/frontend/proveedor/centro.html');
    await page.getByRole('link', { name: /Documentos/i }).first().click();

    await page.waitForURL('**/frontend/proveedor/documentos.html');
    await expect(page.getByRole('heading', { name: /Documentos/i })).toBeVisible();
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });
  });
});
