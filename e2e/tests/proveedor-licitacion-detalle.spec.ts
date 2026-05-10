import { expect, test } from '@playwright/test';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor detalle licitacion', () => {
  test('provider can open licitacion detail from convocatorias', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(PROVIDER_EMAIL);
    await page.locator('#password').fill(PROVIDER_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();

    await page.waitForURL('**/frontend/proveedor/centro.html');
    await page.getByRole('link', { name: /Convocatorias/i }).first().click();
    await page.waitForURL('**/frontend/proveedor/convocatorias.html');

    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });
    const detailLink = page.getByRole('link', { name: /Ver detalle/i }).first();
    await expect(detailLink).toBeVisible({ timeout: 15000 });
    await detailLink.click();

    await page.waitForURL(/\/frontend\/proveedor\/licitacion\.html\?id=\d+/);
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.getByText(/Cronograma/i)).toBeVisible();
    await expect(page.getByText(/Reglas de participación/i)).toBeVisible();
    await expect(page.getByText(/Documentos de la convocatoria/i)).toBeVisible();
  });
});
