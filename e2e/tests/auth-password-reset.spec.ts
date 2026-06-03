import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Auth password reset', () => {
  test('password-forgot.html carga correctamente', async ({ page }) => {
    await page.goto('/frontend/auth/password-forgot.html');
    await expect(page.getByRole('heading', { name: /Recuperar contraseña/i })).toBeVisible();
    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#submit-btn')).toBeVisible();
  });

  test('password-reset.html carga correctamente', async ({ page }) => {
    await page.goto('/frontend/auth/password-reset.html');
    await expect(page.getByRole('heading', { name: /Restablecer contraseña/i })).toBeVisible();
    await expect(page.locator('#token')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.locator('#password-confirm')).toBeVisible();
    await expect(page.locator('#submit-btn')).toBeVisible();
  });

  test('password-reset.html recibe token desde query param', async ({ page }) => {
    await page.goto('/frontend/auth/password-reset.html?token=abc123');
    const tokenValue = await page.locator('#token').inputValue();
    expect(tokenValue).toBe('abc123');
  });

  test('login tiene enlace a recuperar contraseña', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');
    const link = page.locator('a[href="/frontend/auth/password-forgot.html"]');
    await expect(link).toBeVisible();
  });

  test('forgot password endpoint responde sin revelar existencia', async ({ request }) => {
    const res = await request.post('/api/v1/auth/password/forgot', {
      headers: { 'Content-Type': 'application/json' },
      data: { email: 'noexiste@inexistente.com' },
    });
    expect(res.status()).toBe(200);
    const payload = await res.json();
    expect(payload.success).toBe(true);
    // No debe revelar si el email existe o no
    expect(payload.message.toLowerCase()).toContain('instrucciones');
  });

  test('forgot password endpoint acepta email valido', async ({ request }) => {
    const res = await request.post('/api/v1/auth/password/forgot', {
      headers: { 'Content-Type': 'application/json' },
      data: { email: PROVIDER_EMAIL },
    });
    expect(res.status()).toBe(200);
    const payload = await res.json();
    expect(payload.success).toBe(true);
  });

  test('reset con token invalido devuelve error', async ({ request }) => {
    const res = await request.post('/api/v1/auth/password/reset', {
      headers: { 'Content-Type': 'application/json' },
      data: { token: 'token-invalido-123', password: 'NuevaPass123!' },
    });
    expect(res.status()).toBe(422);
    const payload = await res.json();
    expect(payload.success).toBe(false);
  });

  test('reset con contraseña debil devuelve error', async ({ request }) => {
    const res = await request.post('/api/v1/auth/password/reset', {
      headers: { 'Content-Type': 'application/json' },
      data: { token: 'abc', password: '12345678' },
    });
    expect(res.status()).toBe(422);
    const payload = await res.json();
    expect(payload.success).toBe(false);
  });

  test('perfil proveedor tiene formulario de cambio de contraseña', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/perfil.html');
    await page.waitForURL('**/frontend/proveedor/perfil.html');
    await expect(page.locator('#pwd_actual')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#pwd_nueva')).toBeVisible();
    await expect(page.locator('#pwd_confirmacion')).toBeVisible();
    await expect(page.locator('#btn-password')).toBeVisible();
  });

  test('password-reset valida fortaleza en frontend', async ({ page }) => {
    await page.goto('/frontend/auth/password-reset.html');
    await page.locator('#token').fill('abc');
    await page.locator('#password').fill('12345678');
    await page.locator('#password-confirm').fill('12345678');
    await page.locator('#submit-btn').click();

    await expect(page.locator('#feedback')).toContainText('no cumple', { timeout: 5000 });
  });
});
