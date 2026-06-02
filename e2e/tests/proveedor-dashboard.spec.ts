import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Proveedor dashboard KPIs', () => {
  test('metricas endpoint returns valid KPI data for provider', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    const meRes = await request.get('/api/v1/me', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(meRes.status()).toBe(200);
    const me = await meRes.json();
    const idProveedor = me.data?.id_proveedor;
    expect(idProveedor).toBeTruthy();

    const metricasRes = await request.get(`/api/v1/proveedores/${idProveedor}/metricas`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(metricasRes.status()).toBe(200);
    const payload = await metricasRes.json();
    expect(payload.success).toBe(true);
    expect(typeof payload.data.total_participaciones).toBe('number');
    expect(typeof payload.data.total_propuestas).toBe('number');
    expect(typeof payload.data.total_ganadas).toBe('number');
    expect(typeof payload.data.tasa_ganancia).toBe('number');
    expect(typeof payload.data.monto_total_propuesto).toBe('number');
    expect(typeof payload.data.monto_total_adjudicado).toBe('number');
    expect(typeof payload.data.contratos_vigentes).toBe('number');
    expect(Array.isArray(payload.data.participaciones_por_mes)).toBeTruthy();
    expect(typeof payload.data.distribucion_por_tipo).toBe('object');
    expect(Array.isArray(payload.data.ultimas_participaciones)).toBeTruthy();
  });

  test('tendencia endpoint returns quarterly data', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    const meRes = await request.get('/api/v1/me', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const me = await meRes.json();
    const idProveedor = me.data?.id_proveedor;

    const tendenciaRes = await request.get(`/api/v1/proveedores/${idProveedor}/metricas/tendencia`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(tendenciaRes.status()).toBe(200);
    const payload = await tendenciaRes.json();
    expect(payload.success).toBe(true);
    expect(Array.isArray(payload.data)).toBeTruthy();
    if (payload.data.length > 0) {
      expect(payload.data[0]).toHaveProperty('trimestre');
      expect(payload.data[0]).toHaveProperty('participaciones');
      expect(payload.data[0]).toHaveProperty('monto_propuesto');
    }
  });

  test('metricas endpoint rejects cross-provider access', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const otherId = 9999;
    const res = await request.get(`/api/v1/proveedores/${otherId}/metricas`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect([403, 404]).toContain(res.status());
  });

  test('admin can access provider metricas', async ({ request }) => {
    const adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);
    const res = await request.get('/api/v1/proveedores/1/metricas', {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect([200, 404]).toContain(res.status());
  });

  test('dashboard page renders KPI cards and charts', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    await expect(page.locator('#kpi-participaciones')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('#kpi-tasa')).toBeVisible();
    await expect(page.locator('#kpi-monto')).toBeVisible();
    await expect(page.locator('#kpi-contratos')).toBeVisible();

    const participacionesText = await page.locator('#kpi-participaciones').textContent();
    expect(participacionesText).not.toBeNull();
    expect(Number(participacionesText?.trim())).toBeGreaterThanOrEqual(0);

    const chartBars = page.locator('#chart-participaciones-mes');
    await expect(chartBars).toBeVisible({ timeout: 10000 });

    const chartDona = page.locator('#chart-distribucion-tipo');
    await expect(chartDona).toBeVisible({ timeout: 10000 });
  });

  test('dashboard page shows ultimas participaciones table', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    const tbody = page.locator('#ultimas-participaciones-body');
    await expect(tbody).toBeVisible({ timeout: 15000 });
    await expect(tbody).not.toContainText('Cargando', { timeout: 15000 });
  });
});
