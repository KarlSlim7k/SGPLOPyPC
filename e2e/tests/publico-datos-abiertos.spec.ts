import { expect, test } from '@playwright/test';
import { loginUI, loginToken } from './helpers';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';

test.describe('Público datos abiertos OCDS', () => {
  test('puede navegar a datos abiertos desde centro', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await page.getByRole('link', { name: /Datos abiertos/i }).click();
    await page.waitForURL('**/frontend/publico/datos-abiertos.html');

    await expect(page.getByRole('heading', { name: /Datos abiertos/i })).toBeVisible();
  });

  test('datos-abiertos.html carga tabla con releases', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/datos-abiertos.html');
    await page.waitForURL('**/frontend/publico/datos-abiertos.html');

    await expect(page.locator('#releases-rows')).not.toContainText('Cargando', { timeout: 15000 });
    const rows = page.locator('#releases-rows tr');
    const count = await rows.count();
    expect(count).toBeGreaterThan(0);
  });

  test('filtros aplican y actualizan tabla', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/datos-abiertos.html');
    await page.waitForURL('**/frontend/publico/datos-abiertos.html');

    await expect(page.locator('#releases-rows')).not.toContainText('Cargando', { timeout: 15000 });

    // Aplicar filtro de estado
    await page.locator('#filtro-estado').selectOption('PUBLICADA');
    await page.locator('#btn-aplicar').click();

    await expect(page.locator('#releases-rows')).not.toContainText('Cargando', { timeout: 15000 });
  });

  test('API: descarga de release-package responde JSON válido', async ({ request }) => {
    const token = await loginToken(request, PUBLIC_EMAIL, PUBLIC_PASSWORD);
    const res = await request.get('/api/v1/datos-abiertos/release-package?download=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const contentType = res.headers()['content-type'] || '';
    expect(contentType).toContain('application/json');
    const body = await res.json();
    expect(body).toHaveProperty('releases');
    expect(Array.isArray(body.releases)).toBe(true);
  });
});
