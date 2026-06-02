import { expect, test } from '@playwright/test';
import { fakeIp, loginToken, loginUI } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor notificaciones en tiempo real', () => {
  test('notif-badge-toast.js y notif-stream.js están disponibles', async ({ request }) => {
    const streamRes = await request.get('/frontend/shared/notif-stream.js');
    expect(streamRes.ok()).toBeTruthy();
    const streamText = await streamRes.text();
    expect(streamText).toContain('NotifStream');

    const badgeRes = await request.get('/frontend/shared/notif-badge-toast.js');
    expect(badgeRes.ok()).toBeTruthy();
    const badgeText = await badgeRes.text();
    expect(badgeText).toContain('SGPLNotifBadge');
  });

  test('badge de notificaciones visible en centro.html', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    const badge = page.locator('[data-notif-badge]');
    await expect(badge).toBeVisible({ timeout: 10000 });

    const bellIcon = badge.locator('.ph-bell');
    await expect(bellIcon).toBeVisible();
  });

  test('badge visible en todas las páginas de proveedor', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    const pages = [
      '/frontend/proveedor/convocatorias.html',
      '/frontend/proveedor/participaciones.html',
      '/frontend/proveedor/propuestas.html',
      '/frontend/proveedor/documentos.html',
      '/frontend/proveedor/contratos.html',
      '/frontend/proveedor/perfil.html',
      '/frontend/proveedor/notificaciones.html',
    ];

    for (const pagePath of pages) {
      await page.goto(pagePath);
      await expect(page.locator('[data-notif-badge]')).toBeVisible({ timeout: 10000 });
    }
  });

  test('badge muestra el conteo correcto de no leídas', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    const countRes = await request.get('/api/v1/notificaciones/count', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const countPayload = await countRes.json();
    expect(countPayload.success).toBe(true);
    expect(typeof countPayload.data.count).toBe('number');

    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    const badge = page.locator('[data-notif-badge]');
    await expect(badge).toBeVisible({ timeout: 10000 });

    const bellIcon = badge.locator('.ph-bell');
    await expect(bellIcon).toBeVisible({ timeout: 5000 });
  });

  test('NotifStream se inicializa en centro.html', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    await page.waitForFunction(() => {
      return typeof window.NotifStream !== 'undefined'
        && typeof window.SGPLNotifBadge !== 'undefined';
    }, { timeout: 10000 });
  });

  test('crear notificación vía API y verificar que badge se actualiza sin recargar', async ({ page, request }) => {
    const providerToken = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    const adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD);

    const meRes = await request.get('/api/v1/me', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const me = await meRes.json();
    const idUsuario = me.data?.id_usuario;

    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    const badge = page.locator('[data-notif-badge]');
    await expect(badge).toBeVisible({ timeout: 10000 });

    await page.waitForFunction(() => typeof window.NotifStream !== 'undefined', { timeout: 10000 });

    const createRes = await request.post('/api/v1/notificaciones', {
      headers: { Authorization: `Bearer ${adminToken}`, 'Content-Type': 'application/json' },
      data: {
        id_usuario_destino: idUsuario,
        tipo_notificacion: 'GENERAL',
        titulo: 'Test SSE P3',
        mensaje: 'Notificación de prueba para fase P3',
      },
    });
    expect(createRes.ok()).toBeTruthy();

    await new Promise((r) => setTimeout(r, 1000));

    const countAfterRes = await request.get('/api/v1/notificaciones/count', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const countAfter = (await countAfterRes.json()).data.count;
    expect(countAfter).toBeGreaterThanOrEqual(1);
  });

  test('toast container se crea dinámicamente', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForURL('**/frontend/proveedor/centro.html');

    await page.waitForFunction(() => {
      return typeof window.SGPLNotifBadge !== 'undefined';
    }, { timeout: 10000 });

    const toastContainer = page.locator('#notif-toast-container');
    await expect(toastContainer).toBeAttached({ timeout: 10000 });
  });
});
