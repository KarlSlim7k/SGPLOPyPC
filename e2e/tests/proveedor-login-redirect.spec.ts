import { expect, test } from '@playwright/test';
import { loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor login redirect', () => {
  test('redirects provider to provider center and can open convocatorias module', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');

    await page.locator('#email').fill(PROVIDER_EMAIL);
    await page.locator('#password').fill(PROVIDER_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();

    await page.waitForURL('**/frontend/proveedor/centro.html');
    await expect(page.getByRole('heading', { name: /bienvenido/i })).toBeVisible();

    await page.getByRole('link', { name: /Convocatorias/i }).first().click();
    await page.waitForURL('**/frontend/proveedor/convocatorias.html');
    await expect(page.getByRole('heading', { name: /Convocatorias disponibles/i })).toBeVisible();
  });
});
