import { expect, test } from '@playwright/test';
import { fakeIp, loginUI } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

async function loginAsAdmin(page: import('@playwright/test').Page): Promise<void> {
  await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, '**/frontend/admin/dashboard.html');
  await expect(page.getByRole('heading', { name: /panel de control/i })).toBeVisible();
}

test.describe('Admin auth and module smoke', () => {
  test('invalid login shows validation message', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');

    await page.locator('#email').fill(ADMIN_EMAIL);
    await page.locator('#password').fill('invalid-password-123');
    await page.getByRole('button', { name: /iniciar sesión/i }).click();

    await expect(page.locator('#error-msg')).toBeVisible();
    await expect(page.locator('#error-text')).toContainText(/credenciales|correo|contrase(?:ñ|n)a|sesi(?:ó|o)n|iniciar/i);
  });

  test('admin can login, navigate modules, and logout', async ({ page }) => {
    await loginAsAdmin(page);

    const modules = [
      { name: 'Convocatorias', url: '**/frontend/admin/convocatorias/index.html' },
      { name: 'Proveedores', url: '**/frontend/admin/proveedores/index.html' },
      { name: 'Propuestas', url: '**/frontend/admin/propuestas/index.html' },
      { name: 'Evaluación', url: '**/frontend/admin/evaluacion/index.html' },
      { name: 'Adjudicaciones', url: '**/frontend/admin/adjudicaciones/index.html' },
      { name: 'Reportes', url: '**/frontend/admin/reportes/index.html' },
      { name: 'Configuración', url: '**/frontend/admin/configuracion/index.html' },
    ];

    for (const module of modules) {
      await page.getByRole('link', { name: new RegExp(module.name, 'i') }).first().click();
      await page.waitForURL(module.url);
      await expect(page).toHaveURL(new RegExp(module.url.replace('**', '.*')));
      await expect(page.locator('h1, h2').first()).toBeVisible();
    }

    await page.getByRole('button', { name: /cerrar sesi(?:ó|o)n/i }).first().click();
    await page.waitForURL('**/frontend/auth/login.html');
    await expect(page).toHaveURL(/\/frontend\/auth\/login\.html/);

    await page.goto('/frontend/admin/dashboard.html');
    await page.waitForURL('**/frontend/auth/login.html');
    await expect(page).toHaveURL(/\/frontend\/auth\/login\.html/);
  });
});
