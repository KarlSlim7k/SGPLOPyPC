import { expect, test } from '@playwright/test';
import { fakeIp, loginToken, loginUI } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Admin dashboard — métricas analíticas (Fase 4)', () => {
  let token: string;

  test.beforeAll(async ({ request }) => {
    token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
  });

  test('GET /admin/metricas/tiempo-ciclo responde con series y meta', async ({ request }) => {
    const res = await request.get('/api/v1/admin/metricas/tiempo-ciclo', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(Array.isArray(body.data.series)).toBe(true);
    expect(body.data.meta.cached_for_seconds).toBeGreaterThan(0);
  });

  test('GET /admin/metricas/proveedores-top respeta limit (1..50)', async ({ request }) => {
    const res = await request.get('/api/v1/admin/metricas/proveedores-top?limit=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.data.limit).toBe(5);
    expect(Array.isArray(body.data.items)).toBe(true);
    expect(body.data.items.length).toBeLessThanOrEqual(5);
  });

  test('GET /admin/metricas/montos-mensuales devuelve serie de 12 meses por defecto', async ({ request }) => {
    const res = await request.get('/api/v1/admin/metricas/montos-mensuales', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(Array.isArray(body.data.series)).toBe(true);
    // Por defecto: rango de 12 meses (puede dar 12 o 13 entradas según día actual)
    expect(body.data.series.length).toBeGreaterThanOrEqual(12);
    for (const s of body.data.series) {
      expect(typeof s.mes).toBe('string');
      expect(typeof s.mes_label).toBe('string');
      expect(typeof s.licitaciones_creadas).toBe('number');
      expect(typeof s.contratos_adjudicados).toBe('number');
      expect(typeof s.monto_adjudicado).toBe('number');
    }
  });

  test('GET /admin/metricas/cumplimiento devuelve resumen y distribución por estado', async ({ request }) => {
    const res = await request.get('/api/v1/admin/metricas/cumplimiento', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.data.resumen).toBeDefined();
    expect(typeof body.data.resumen.total_evaluables).toBe('number');
    expect(typeof body.data.resumen.a_tiempo).toBe('number');
    expect(typeof body.data.resumen.con_atraso).toBe('number');
    expect(Array.isArray(body.data.distribucion_estado)).toBe(true);
  });

  test('GET /admin/metricas/dependencias lista dependencias para filtros', async ({ request }) => {
    const res = await request.get('/api/v1/admin/metricas/dependencias', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(Array.isArray(body.data.items)).toBe(true);
    expect(body.data.items.length).toBeGreaterThan(0);
    for (const d of body.data.items) {
      expect(typeof d.id_dependencia).toBe('number');
      expect(typeof d.nombre).toBe('string');
      expect(typeof d.total_licitaciones).toBe('number');
    }
  });

  test('POST /admin/metricas/flush-cache vacía la cache', async ({ request }) => {
    // Primero llenar la cache
    await request.get('/api/v1/admin/metricas/tiempo-ciclo', { headers: { Authorization: `Bearer ${token}` } });
    await request.get('/api/v1/admin/metricas/montos-mensuales', { headers: { Authorization: `Bearer ${token}` } });

    const res = await request.post('/api/v1/admin/metricas/flush-cache', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(typeof body.data.archivos_eliminados).toBe('number');
  });

  test('proveedor (no admin) recibe 403 en endpoints de métricas', async ({ request }) => {
    const proveedorEmail = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
    const proveedorPass = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';
    const provToken = await loginToken(request, proveedorEmail, proveedorPass, fakeIp());

    const res = await request.get('/api/v1/admin/metricas/tiempo-ciclo', {
      headers: { Authorization: `Bearer ${provToken}` },
    });
    expect(res.status()).toBe(403);
  });

  test('filtros from/to son aplicados', async ({ request }) => {
    const res = await request.get('/api/v1/admin/metricas/tiempo-ciclo?from=2026-01-01&to=2026-12-31', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.data.meta.filtros.from).toBe('2026-01-01');
    expect(body.data.meta.filtros.to).toBe('2026-12-31');
  });

  test('UI dashboard renderiza la sección de métricas con KPIs', async ({ page }) => {
    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, '**/frontend/admin/dashboard.html');

    // Esperar a que la sección de métricas exista
    await expect(page.getByRole('heading', { name: /m(?:é|e)tricas anal(?:í|i)ticas/i })).toBeVisible();

    // Esperar a que los KPIs ya no muestren '—' (cargados desde API o vacíos legítimos)
    await page.waitForFunction(() => {
      const el = document.querySelector('#metrics-status');
      // Cuando termina, status muestra 'Actualizado' y luego se oculta. Verificamos que ya no diga 'Cargando'.
      return el && (el.classList.contains('hidden') || (el.textContent || '').includes('Actualizado'));
    }, { timeout: 20000 });

    // Verificar que las cabeceras de las gráficas estén visibles
    await expect(page.getByText(/tiempo de ciclo/i)).toBeVisible();
    await expect(page.getByText(/montos adjudicados por mes/i)).toBeVisible();
    await expect(page.getByText(/top 10 proveedores/i)).toBeVisible();
    await expect(page.getByText(/distribuci(?:ó|o)n por estado/i)).toBeVisible();
  });
});
