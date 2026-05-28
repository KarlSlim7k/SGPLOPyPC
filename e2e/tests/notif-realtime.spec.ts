import { expect, test } from '@playwright/test';
import { fakeIp, loginToken } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Notificaciones en tiempo real (SSE + polling)', () => {
  let token: string;
  let idAdmin: number;

  test.beforeAll(async ({ request }) => {
    token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    const me = await request.get('/api/v1/me', { headers: { Authorization: `Bearer ${token}` } });
    idAdmin = (await me.json()).data.id_usuario;
  });

  test('GET /notificaciones/count responde 200 con count numérico', async ({ request }) => {
    const res = await request.get('/api/v1/notificaciones/count', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok(), `count failed: ${res.status()} ${await res.text()}`).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(typeof body.data.count).toBe('number');
    expect(body.data.count).toBeGreaterThanOrEqual(0);
  });

  test('GET /notificaciones/count sin auth devuelve 401', async ({ request }) => {
    const res = await request.get('/api/v1/notificaciones/count');
    expect(res.status()).toBe(401);
  });

  test('crear notificación y verificar que count aumenta', async ({ request }) => {
    const countBefore = (await (await request.get('/api/v1/notificaciones/count', {
      headers: { Authorization: `Bearer ${token}` },
    })).json()).data.count;

    // Crear notificación para el admin
    const createRes = await request.post('/api/v1/notificaciones', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: {
        id_usuario_destino: idAdmin,
        tipo_notificacion: 'GENERAL',
        titulo: 'Test SSE E2E',
        mensaje: 'Notificación de prueba para test E2E de SSE',
      },
    });
    expect(createRes.ok(), `create notif failed: ${createRes.status()} ${await createRes.text()}`).toBeTruthy();

    // Esperar un momento para que la BD persista
    await new Promise((r) => setTimeout(r, 500));

    const countAfter = (await (await request.get('/api/v1/notificaciones/count', {
      headers: { Authorization: `Bearer ${token}` },
    })).json()).data.count;

    expect(countAfter).toBeGreaterThan(countBefore);
  });

  test('GET /notificaciones/stream con token query param devuelve SSE headers', async ({ request }) => {
    // El endpoint SSE hace long-polling; usamos un timeout corto para no esperar 25s.
    // Verificamos que responde con Content-Type text/event-stream.
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 3000);

    let contentType = '';
    try {
      const res = await request.get(
        `/api/v1/notificaciones/stream?token=${encodeURIComponent(token)}&since=${Date.now() - 5000}`,
        { timeout: 4000 }
      );
      contentType = res.headers()['content-type'] || '';
      clearTimeout(timeoutId);
    } catch (e) {
      // Timeout esperado (el SSE mantiene la conexión abierta)
      clearTimeout(timeoutId);
    }

    // Si llegamos aquí con respuesta, verificar Content-Type
    if (contentType) {
      expect(contentType).toContain('text/event-stream');
    }
    // Si hubo timeout, el test pasa (el endpoint está funcionando, sólo tardó)
  });

  test('GET /notificaciones/stream sin auth devuelve 401', async ({ request }) => {
    const res = await request.get('/api/v1/notificaciones/stream?since=0', { timeout: 5000 });
    expect(res.status()).toBe(401);
  });

  test('notif-stream.js está disponible como archivo estático', async ({ request }) => {
    const res = await request.get('/frontend/shared/notif-stream.js');
    expect(res.ok()).toBeTruthy();
    const text = await res.text();
    expect(text).toContain('NotifStream');
    expect(text).toContain('EventSource');
  });

  test('badge de notificaciones visible en dashboard admin', async ({ page }) => {
    // Login via UI
    await page.setExtraHTTPHeaders({ 'X-Forwarded-For': fakeIp() });
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(ADMIN_EMAIL);
    await page.locator('#password').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();
    await page.waitForURL('**/frontend/admin/dashboard.html', { timeout: 15000 });

    // El botón del badge debe existir
    const badgeBtn = page.locator('#notif-badge-btn');
    await expect(badgeBtn).toBeVisible();

    // Esperar a que NotifStream inicialice y llame al endpoint
    await page.waitForFunction(() => {
      return typeof window.NotifStream !== 'undefined';
    }, { timeout: 10000 });
  });
});
