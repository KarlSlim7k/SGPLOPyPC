import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor Reputacion Detalle', () => {
  test('reputacion.html carga con datos y muestra score', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/reputacion.html');
    await page.waitForURL('**/frontend/proveedor/reputacion.html');

    // Verificar heading
    await expect(page.getByRole('heading', { name: /Mi reputaci/i })).toBeVisible({ timeout: 15000 });

    // Score card debe ser visible y mostrar datos
    const scoreCard = page.locator('#score-card');
    await expect(scoreCard).toBeVisible({ timeout: 10000 });
    const scoreValue = page.locator('#score-valor');
    await expect(scoreValue).not.toHaveText('—');

    // Badge de nivel visible
    const nivelBadge = page.locator('#score-nivel');
    await expect(nivelBadge).toBeVisible();
    await expect(nivelBadge).not.toHaveText('—');

    // Total evaluaciones visible
    const scoreTotal = page.locator('#score-total');
    await expect(scoreTotal).toBeVisible();
    await expect(scoreTotal).not.toHaveText('—');
  });

  test('reputacion.html muestra grafica radar', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/reputacion.html');

    const radarSection = page.locator('#radar-section');
    await expect(radarSection).toBeVisible({ timeout: 10000 });
    await expect(page.locator('canvas#radar-chart')).toBeAttached();
  });

  test('reputacion.html muestra tabla de evaluaciones', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/reputacion.html');

    const table = page.locator('#evaluaciones-table');
    await expect(table).toBeVisible({ timeout: 10000 });

    const rows = page.locator('#evaluaciones-body tr');
    await expect(rows.first()).toBeVisible();
    expect(await rows.count()).toBeGreaterThan(0);
  });

  test('perfil proveedor tiene enlace a reputacion.html', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/perfil.html');
    await page.waitForURL('**/frontend/proveedor/perfil.html');

    const link = page.getByRole('link', { name: /Ver historial completo/i });
    await expect(link).toBeVisible({ timeout: 10000 });

    await link.click();
    await page.waitForURL('**/frontend/proveedor/reputacion.html');
    await expect(page.getByRole('heading', { name: /Mi reputaci/i })).toBeVisible();
  });

  test('api reputacion devuelve desglose y evaluaciones', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const res = await request.get('/api/v1/proveedores/1/reputacion', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.data).toHaveProperty('score');
    expect(body.data).toHaveProperty('total_evaluaciones');
    expect(body.data).toHaveProperty('evaluaciones');
    expect(body.data).toHaveProperty('desglose');
    expect(Array.isArray(body.data.evaluaciones)).toBe(true);
    expect(body.data.desglose).toHaveProperty('puntualidad_promedio');
    expect(body.data.desglose).toHaveProperty('calidad_promedio');
    expect(body.data.desglose).toHaveProperty('comunicacion_promedio');
    expect(body.data.desglose).toHaveProperty('cumplimiento_alcance_promedio');
  });
});
