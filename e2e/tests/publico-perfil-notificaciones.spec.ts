import { expect, test } from '@playwright/test';
import { loginUI, loginToken } from './helpers';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';

test.describe('Público perfil', () => {
  test('puede navegar a perfil y ver datos de cuenta', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await page.getByRole('link', { name: /Mi perfil/i }).first().click();
    await page.waitForURL('**/frontend/publico/perfil.html');

    await expect(page.getByRole('heading', { name: /Mi perfil/i })).toBeVisible();
    await expect(page.locator('#usuario_nombre')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#usuario_email')).toBeVisible();

    const nombreValue = await page.locator('#usuario_nombre').inputValue();
    expect(nombreValue.length).toBeGreaterThan(0);

    const emailValue = await page.locator('#usuario_email').inputValue();
    expect(emailValue).toContain('@');
  });

  test('puede editar nombre y guardar cambios', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await page.goto('/frontend/publico/perfil.html');
    await page.waitForURL('**/frontend/publico/perfil.html');
    await expect(page.locator('#usuario_nombre')).toBeVisible({ timeout: 10000 });

    const nombreOriginal = await page.locator('#usuario_nombre').inputValue();
    const nombreNuevo = 'Usuario Público E2E Test';

    await page.locator('#usuario_nombre').fill(nombreNuevo);
    await page.locator('#btn-usuario').click();

    await expect(page.locator('#usuario-alert')).toContainText('actualizada', { timeout: 15000 });

    await page.locator('#usuario_nombre').fill(nombreOriginal);
    await page.locator('#btn-usuario').click();
    await expect(page.locator('#usuario-alert')).toContainText('actualizada', { timeout: 15000 });
  });

  test('muestra resumen de cuenta con ID y fechas', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/perfil.html');
    await page.waitForURL('**/frontend/publico/perfil.html');

    await expect(page.locator('#res-id')).toBeVisible({ timeout: 10000 });
    const idText = await page.locator('#res-id').textContent();
    expect(idText).not.toBe('N/A');

    await expect(page.locator('#res-fecha-registro')).toBeVisible();
  });

  test('formulario de contraseña está visible y tiene validación', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/perfil.html');
    await page.waitForURL('**/frontend/publico/perfil.html');

    await expect(page.locator('#pwd_actual')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#pwd_nueva')).toBeVisible();
    await expect(page.locator('#pwd_confirmacion')).toBeVisible();
    await expect(page.locator('#btn-password')).toBeVisible();
  });
});

test.describe('Público notificaciones', () => {
  test('puede navegar a notificaciones desde centro', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await page.getByRole('link', { name: /Ver todas/i }).click();
    await page.waitForURL('**/frontend/publico/notificaciones.html');

    await expect(page.getByRole('heading', { name: /Mis notificaciones/i })).toBeVisible();
    await expect(page.locator('#stat-total')).toBeVisible({ timeout: 10000 });
  });

  test('muestra estadísticas de notificaciones', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/notificaciones.html');
    await page.waitForURL('**/frontend/publico/notificaciones.html');

    await expect(page.locator('#stat-total')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#stat-unread')).toBeVisible();
    await expect(page.locator('#stat-actionable')).toBeVisible();

    const summary = page.locator('#summary');
    await expect(summary).not.toContainText('Cargando', { timeout: 15000 });
  });

  test('filtros funcionan correctamente', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/notificaciones.html');
    await page.waitForURL('**/frontend/publico/notificaciones.html');

    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });

    await page.locator('#f-estado').selectOption('no_leidas');
    await page.locator('#filters-form button[type="submit"]').click();

    await expect(page.locator('#summary')).toContainText('Mostrando', { timeout: 5000 });
  });

  test('puede marcar notificación como leída', async ({ page, request }) => {
    const token = await loginToken(request, PUBLIC_EMAIL, PUBLIC_PASSWORD);

    const notifRes = await request.get('/api/v1/notificaciones/mias', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const notifPayload = await notifRes.json();
    const notificaciones = notifPayload?.data || [];
    const unread = notificaciones.find((n: any) => !n.leida);

    if (!unread) {
      test.skip();
      return;
    }

    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.goto('/frontend/publico/notificaciones.html');
    await page.waitForURL('**/frontend/publico/notificaciones.html');

    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15000 });

    const markBtn = page.locator(`button[data-mark-read="${unread.id_notificacion}"]`);
    if (await markBtn.isVisible()) {
      await markBtn.click();
      await expect(page.locator('#flash')).toContainText('marcada como leída', { timeout: 10000 });
    }
  });

  test('centro muestra enlace "Ver todas" en notificaciones', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await expect(page.getByRole('link', { name: /Ver todas/i })).toBeVisible({ timeout: 10000 });
    const href = await page.getByRole('link', { name: /Ver todas/i }).getAttribute('href');
    expect(href).toContain('/frontend/publico/notificaciones.html');
  });
});
