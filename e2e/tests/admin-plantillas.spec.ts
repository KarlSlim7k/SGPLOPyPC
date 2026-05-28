import { expect, test } from '@playwright/test';
import { fakeIp, loginToken, loginUI } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Admin plantillas — CRUD y protección de predefinidas', () => {
  test('lista incluye al menos las 5 plantillas predefinidas', async ({ request }) => {
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    const res = await request.get('/api/v1/admin/plantillas?activa=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok(), `list failed ${res.status()}: ${await res.text()}`).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    const items = body.data.items as Array<{ id_plantilla: number; nombre: string; tipo: string; es_predefinida: boolean; activa: boolean }>;
    expect(items.length).toBeGreaterThanOrEqual(5);

    const tipos = items.filter((i) => i.es_predefinida).map((i) => i.tipo).sort();
    for (const t of ['ACTA_ACLARACIONES', 'ACTA_APERTURA', 'ACTA_FALLO', 'DICTAMEN', 'RESUMEN_LICITACION']) {
      expect(tipos, `Falta plantilla predefinida tipo=${t}`).toContain(t);
    }
  });

  test('plantilla predefinida no se puede eliminar (409)', async ({ request }) => {
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    const list = await request.get('/api/v1/admin/plantillas?activa=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const items = (await list.json()).data.items;
    const predefinida = items.find((i: any) => i.es_predefinida);
    expect(predefinida).toBeDefined();

    const del = await request.delete(`/api/v1/admin/plantillas/${predefinida.id_plantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(del.status()).toBe(409);
  });

  test('plantilla predefinida no se puede actualizar (409)', async ({ request }) => {
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    const list = await request.get('/api/v1/admin/plantillas?activa=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const predefinida = (await list.json()).data.items.find((i: any) => i.es_predefinida);

    const upd = await request.put(`/api/v1/admin/plantillas/${predefinida.id_plantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
      data: { nombre: 'Hackeada', contenido_html: '<p>x</p>' },
    });
    expect(upd.status()).toBe(409);
  });

  test('CRUD completo de plantilla personalizada', async ({ request }) => {
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());

    // Create
    const nombre = 'E2E Plantilla ' + Date.now();
    const create = await request.post('/api/v1/admin/plantillas', {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        nombre,
        descripcion: 'Plantilla creada por E2E',
        tipo: 'PERSONALIZADA',
        contenido_html: '<html><body><h1>{{numero_licitacion}}</h1><p>{{descripcion_proyecto}}</p></body></html>',
        variables_esperadas: 'numero_licitacion,descripcion_proyecto',
        activa: 1,
      },
    });
    expect(create.status()).toBe(201);
    const idPlantilla = (await create.json()).data.id_plantilla;
    expect(idPlantilla).toBeGreaterThan(0);

    // Read
    const read = await request.get(`/api/v1/admin/plantillas/${idPlantilla}?with_content=1`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(read.ok()).toBeTruthy();
    const readBody = await read.json();
    expect(readBody.data.nombre).toBe(nombre);
    expect(readBody.data.es_predefinida).toBe(false);
    expect(readBody.data.contenido_html).toContain('{{numero_licitacion}}');

    // Update
    const upd = await request.put(`/api/v1/admin/plantillas/${idPlantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
      data: { descripcion: 'Actualizada por E2E', activa: 0 },
    });
    expect(upd.ok()).toBeTruthy();

    // Delete
    const del = await request.delete(`/api/v1/admin/plantillas/${idPlantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(del.ok()).toBeTruthy();

    const after = await request.get(`/api/v1/admin/plantillas/${idPlantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(after.status()).toBe(404);
  });

  test('proveedor no puede acceder al CRUD de plantillas (403)', async ({ request }) => {
    const proveedorEmail = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
    const proveedorPass = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';
    const token = await loginToken(request, proveedorEmail, proveedorPass, fakeIp());

    const res = await request.get('/api/v1/admin/plantillas', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(403);
  });

  test('UI admin lista plantillas predefinidas y abre editor', async ({ page }) => {
    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, '**/frontend/admin/dashboard.html');

    // Navegar al módulo Plantillas desde el sidebar
    await page.getByRole('link', { name: /^plantillas$/i }).first().click();
    await page.waitForURL('**/frontend/admin/plantillas/index.html');
    await expect(page.getByRole('heading', { name: /plantillas de reporte/i })).toBeVisible();

    // Verifica que aparezcan las predefinidas
    await page.waitForFunction(() => {
      const tb = document.querySelector('#plantilla-tbody');
      return tb && !tb.textContent?.includes('Cargando');
    }, { timeout: 15000 });

    const filas = await page.locator('#plantilla-tbody tr').count();
    expect(filas).toBeGreaterThanOrEqual(5);

    // Abre el editor en modo lectura (predefinida)
    await page.getByRole('link', { name: /^ver$/i }).first().click();
    await page.waitForURL('**/frontend/admin/plantillas/editor.html?id=*');
    await expect(page.locator('#f-html')).toBeDisabled();
  });
});
