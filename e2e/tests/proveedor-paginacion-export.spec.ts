import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor paginacion y exportacion CSV', () => {
  test('participaciones endpoint devuelve pagination metadata', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/participaciones/mias?page=1&per_page=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const payload = await res.json();
    expect(payload.success).toBe(true);
    expect(typeof payload.data.page).toBe('number');
    expect(typeof payload.data.per_page).toBe('number');
    expect(typeof payload.data.total).toBe('number');
    expect(typeof payload.data.total_pages).toBe('number');
    expect(Array.isArray(payload.data.items)).toBeTruthy();
  });

  test('contratos endpoint devuelve pagination metadata', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/contratos/mios?page=1&per_page=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const payload = await res.json();
    expect(payload.success).toBe(true);
    expect(typeof payload.data.page).toBe('number');
    expect(typeof payload.data.per_page).toBe('number');
    expect(typeof payload.data.total_pages).toBe('number');
  });

  test('propuestas endpoint devuelve pagination metadata', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/propuestas/mias?page=1&per_page=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const payload = await res.json();
    expect(payload.success).toBe(true);
    expect(typeof payload.data.page).toBe('number');
    expect(typeof payload.data.total_pages).toBe('number');
  });

  test('paginacion renderiza botones en participaciones.html', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/participaciones.html');
    await page.waitForURL('**/frontend/proveedor/participaciones.html');

    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });

    const pagination = page.locator('#pagination-container');
    await expect(pagination).toBeAttached({ timeout: 5000 });
  });

  test('paginacion renderiza botones en contratos.html', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/contratos.html');
    await page.waitForURL('**/frontend/proveedor/contratos.html');

    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });

    const pagination = page.locator('#pagination-container');
    await expect(pagination).toBeAttached({ timeout: 5000 });
  });

  test('boton exportar CSV visible en participaciones', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/participaciones.html');
    await page.waitForURL('**/frontend/proveedor/participaciones.html');

    const exportBtn = page.locator('#btn-export-csv');
    await expect(exportBtn).toBeVisible({ timeout: 10000 });
  });

  test('boton exportar CSV visible en contratos', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/contratos.html');
    await page.waitForURL('**/frontend/proveedor/contratos.html');

    const exportBtn = page.locator('#btn-export-csv');
    await expect(exportBtn).toBeVisible({ timeout: 10000 });
  });

  test('exportar participaciones CSV descarga archivo valido', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/participaciones/mias/export.csv', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const contentType = res.headers()['content-type'] || '';
    expect(contentType).toContain('csv');
    const body = await res.text();
    expect(body).toContain('Licitación');
  });

  test('exportar contratos CSV descarga archivo valido', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/contratos/mios/export.csv', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const contentType = res.headers()['content-type'] || '';
    expect(contentType).toContain('csv');
    const body = await res.text();
    expect(body).toContain('Número Contrato');
  });

  test('exportar propuestas CSV descarga archivo valido', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/propuestas/mias/export.csv', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const contentType = res.headers()['content-type'] || '';
    expect(contentType).toContain('csv');
    const body = await res.text();
    expect(body).toContain('Licitación');
  });
});
