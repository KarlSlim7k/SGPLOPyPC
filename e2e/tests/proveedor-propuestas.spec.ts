import { expect, test } from '@playwright/test';
import { fakeIp, loginToken, loginUI } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor propuestas', () => {
  test('provider can list own proposals and admin is rejected', async ({ request }) => {
    const providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const providerRes = await request.get('/api/v1/propuestas/mias?page=1&limit=20', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    expect(providerRes.status()).toBe(200);
    const providerPayload = await providerRes.json();
    expect(Array.isArray(providerPayload?.data?.items)).toBeTruthy();
    expect(typeof providerPayload?.data?.total).toBe('number');

    const adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    const adminRes = await request.get('/api/v1/propuestas/mias?page=1&limit=20', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(adminRes.status()).toBe(403);
  });

  test('provider can open propuestas module from center', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');
    await page.getByRole('link', { name: /Mis propuestas/i }).first().click();

    await page.waitForURL('**/frontend/proveedor/propuestas.html');
    await expect(page.getByRole('heading', { name: /Mis propuestas/i })).toBeVisible();
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });
  });
});
