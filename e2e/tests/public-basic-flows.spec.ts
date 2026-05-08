import { expect, test } from '@playwright/test';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';

test.describe('Public site basic flows', () => {
  test('landing and public pages render', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { name: /gestión de/i })).toBeVisible();

    await page.goto('/evaluacion.php');
    await expect(page.getByRole('heading', { name: /procesos en evaluación/i })).toBeVisible();

    await page.goto('/historial.php');
    await expect(page.getByRole('heading', { name: /historial de licitaciones/i })).toBeVisible();

    await page.goto('/contratos.php');
    await expect(page.getByRole('heading', { name: /contratos adjudicados/i })).toBeVisible();
  });

  test('public user can log in and is redirected to public center', async ({ page }) => {
    await page.goto('/frontend/auth/login.html');

    await page.locator('#email').fill(PUBLIC_EMAIL);
    await page.locator('#password').fill(PUBLIC_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesión/i }).click();

    await page.waitForURL('**/frontend/publico/centro.html');
    await expect(page.getByRole('heading', { name: /bienvenido/i })).toBeVisible();
  });
});
