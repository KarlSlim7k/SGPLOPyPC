import { expect, test } from '@playwright/test';
import { fakeIp, loginToken, loginUI, rlHeaders } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Admin auditoría — bitácora de acciones', () => {
  test('login OK genera evento LOGIN_OK consultable vía API', async ({ request }) => {
    const ip = fakeIp();
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, ip);

    // Esperar 500ms para asegurar que el insert async/sync haya persistido.
    await new Promise((r) => setTimeout(r, 500));

    const res = await request.get('/api/v1/admin/auditoria?accion=LOGIN_OK&limit=10', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok(), `auditoria list failed ${res.status()}: ${await res.text()}`).toBeTruthy();

    const body = await res.json();
    expect(body.success).toBe(true);
    expect(Array.isArray(body.data.items)).toBe(true);
    expect(body.data.items.length).toBeGreaterThan(0);

    const ultimo = body.data.items[0];
    expect(ultimo.accion).toBe('LOGIN_OK');
    expect(ultimo.usuario).not.toBeNull();
    expect(ultimo.usuario.email).toBe(ADMIN_EMAIL);
    expect(typeof ultimo.request_id).toBe('string');
    expect(ultimo.request_id.length).toBeGreaterThan(0);
  });

  test('login fallido genera evento LOGIN_FALLIDO sin exponer razón', async ({ request }) => {
    const ip = fakeIp();
    // Intento fallido (password incorrecto)
    const failRes = await request.post('/api/v1/auth/login', {
      data: { email: ADMIN_EMAIL, password: 'wrong-password-xyz' },
      headers: rlHeaders(ip),
    });
    expect(failRes.status()).toBe(401);
    const failBody = await failRes.json();
    expect(failBody.message).toMatch(/credenciales/i);

    // Login correcto para obtener token y consultar
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    await new Promise((r) => setTimeout(r, 500));

    const res = await request.get('/api/v1/admin/auditoria?accion=LOGIN_FALLIDO&limit=20', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.data.items.length).toBeGreaterThan(0);

    const evento = body.data.items.find((e: any) =>
      e.valores_nuevos && e.valores_nuevos.email_intentado === ADMIN_EMAIL
    );
    expect(evento, 'no se encontró evento LOGIN_FALLIDO con email_intentado=admin').toBeDefined();
    expect(evento.accion).toBe('LOGIN_FALLIDO');
    expect(['BAD_PASSWORD', 'USER_NOT_FOUND', 'USER_INACTIVE']).toContain(evento.valores_nuevos.razon);
  });

  test('endpoint admin/auditoria rechaza acceso a no-administradores', async ({ request }) => {
    const proveedorEmail = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
    const proveedorPass = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';
    const token = await loginToken(request, proveedorEmail, proveedorPass, fakeIp());

    const res = await request.get('/api/v1/admin/auditoria?limit=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(403);
  });

  test('admin puede ver y filtrar la bitácora desde la UI', async ({ page }) => {
    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, '**/frontend/admin/dashboard.html');

    // Navegar al módulo Auditoría desde el sidebar
    await page.getByRole('link', { name: /auditor(?:í|i)a/i }).first().click();
    await page.waitForURL('**/frontend/admin/auditoria/index.html');
    await expect(page.getByRole('heading', { name: /bit(?:á|a)cora de auditor(?:í|i)a/i })).toBeVisible();

    // La tabla debe cargar al menos una fila o un mensaje de "sin eventos"
    const tbody = page.locator('#audit-tbody');
    await expect(tbody).toBeVisible();
    await page.waitForFunction(() => {
      const tb = document.querySelector('#audit-tbody');
      return tb && !tb.textContent?.includes('Cargando');
    }, { timeout: 15000 });

    // Aplicar filtro por LOGIN_OK
    await page.locator('#f-accion').selectOption('LOGIN_OK');
    await page.getByRole('button', { name: /aplicar/i }).click();

    await page.waitForFunction(() => {
      const kpi = document.querySelector('#kpi-total');
      return kpi && kpi.textContent !== '—';
    }, { timeout: 10000 });

    const total = await page.locator('#kpi-total').textContent();
    expect(Number(total)).toBeGreaterThanOrEqual(0);

    // Limpiar filtros
    await page.locator('#btn-clear').click();
    await page.waitForFunction(() => {
      const kpi = document.querySelector('#kpi-filtros');
      return kpi && kpi.textContent === '0';
    }, { timeout: 10000 });
  });

  test('logout queda registrado como evento LOGOUT', async ({ request }) => {
    const ip = fakeIp();
    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, ip);

    const logoutRes = await request.post('/api/v1/auth/logout', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(logoutRes.ok(), `logout failed ${logoutRes.status()}`).toBeTruthy();

    // Volver a loguear (token nuevo) para consultar
    const token2 = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    await new Promise((r) => setTimeout(r, 500));

    const res = await request.get('/api/v1/admin/auditoria?accion=LOGOUT&limit=5', {
      headers: { Authorization: `Bearer ${token2}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.data.items.length).toBeGreaterThan(0);
    expect(body.data.items[0].accion).toBe('LOGOUT');
  });
});
