import { expect, test } from '@playwright/test';
import { loginUI } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

function datePlusDays(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

async function loginAsAdmin(page: import('@playwright/test').Page): Promise<void> {
  await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, '**/frontend/admin/dashboard.html');
}

test.describe('Admin deep flows', () => {
  test.describe.configure({ timeout: 120000 });

  test('create convocatoria and validate publish in listing', async ({ page }) => {
    const uid = Date.now();
    const clave = `E2E-LIC-${uid}`;

    await loginAsAdmin(page);
    await page.goto('/frontend/admin/convocatorias/create.html');

    await expect.poll(async () => page.locator('#dependencia option').count(), { timeout: 15000 }).toBeGreaterThan(1);

    await page.locator('#objeto').fill(`Convocatoria E2E ${uid} para validación end-to-end`);
    await page.locator('#clave').fill(clave);
    await page.locator('#tipo').selectOption('lp');
    await page.locator('#dependencia').selectOption({ index: 1 });
    await page.locator('#presupuesto').fill('1250000');
    await page.locator('#ubicacion').fill('Xalapa, Veracruz');
    await page.locator('#junta').fill(datePlusDays(3));
    await page.locator('#recepcion').fill(datePlusDays(7));
    await page.locator('#apertura').fill(datePlusDays(8));
    await page.locator('#fallo').fill(datePlusDays(12));

    const publishDialog = page.waitForEvent('dialog', { timeout: 15000 });
    await page.locator('button[type="submit"]').click();
    const dialog = await publishDialog;
    expect(dialog.message().toLowerCase()).toContain('publicada');
    await dialog.accept();

    await page.waitForURL('**/frontend/admin/convocatorias/index.html');

    const search = page.locator('input[type="search"]');
    await search.fill(clave);

    const row = page.locator('tbody tr', { hasText: clave }).first();
    await expect(row).toBeVisible();

    await expect(page.locator('tbody tr', { hasText: clave }).first()).toContainText(/Publicada|PUBLICADA/i);
  });

  test('update proveedor status and restore original value', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/frontend/admin/proveedores/index.html');

    await page.waitForTimeout(1500);

    const editableCount = await page.locator('.change-status').count();
    if (editableCount === 0) {
      await expect(page.locator('tbody')).toContainText(/Sin proveedores|No se pudieron cargar proveedores/i);
      test.skip(true, 'No hay proveedores mutables disponibles en este entorno.');
    }

    const firstSelect = page.locator('.change-status').first();
    await expect(firstSelect).toBeVisible({ timeout: 15000 });

    const providerId = await firstSelect.getAttribute('data-id');
    expect(providerId).toBeTruthy();

    const original = await firstSelect.inputValue();
    const target = original === 'VALIDADO' ? 'PENDIENTE' : 'VALIDADO';

    await firstSelect.evaluate((el: HTMLSelectElement, value) => {
      el.value = value;
      el.dispatchEvent(new Event('change', { bubbles: true }));
    }, target);

    const providerSelector = page.locator(`.change-status[data-id="${providerId}"]`);
    await expect(providerSelector).toHaveValue(target, { timeout: 15000 });

    await providerSelector.evaluate((el: HTMLSelectElement, value) => {
      el.value = value;
      el.dispatchEvent(new Event('change', { bubbles: true }));
    }, original);
    await expect(page.locator(`.change-status[data-id="${providerId}"]`)).toHaveValue(original, { timeout: 15000 });
  });

  test('edit perfil and restore original name', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/frontend/admin/configuracion/index.html');

    const nombreEl = page.locator('#cfg-nombre');
    const emailEl = page.locator('#cfg-email');
    await expect(nombreEl).not.toHaveText('-', { timeout: 15000 });

    const originalName = (await nombreEl.textContent())?.trim() || 'Administrador Demo';
    const originalEmail = (await emailEl.textContent())?.trim() || ADMIN_EMAIL;
    const updatedName = `${originalName} E2E`;

    await page.locator('#btn-editar-perfil').click();
    await page.locator('#input-nombre').fill(updatedName);
    await page.locator('#input-email').fill(originalEmail);
    await page.locator('#perfil-form button[type="submit"]').click();

    await expect(page.locator('#cfg-nombre')).toHaveText(updatedName, { timeout: 15000 });

    await page.locator('#btn-editar-perfil').click();
    await page.locator('#input-nombre').fill(originalName);
    await page.locator('#input-email').fill(originalEmail);
    await page.locator('#perfil-form button[type="submit"]').click();

    await expect(page.locator('#cfg-nombre')).toHaveText(originalName, { timeout: 15000 });
  });

  test('dark mode preference applies and persists', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/frontend/admin/configuracion/index.html');

    const darkToggle = page.locator('#pref-dark');
    const darkSwitch = page.locator('label:has(#pref-dark)');
    const initial = await darkToggle.isChecked();

    await darkSwitch.click();

    await expect.poll(async () => page.evaluate(() => document.body.classList.contains('theme-dark')), { timeout: 10000 }).toBe(!initial);

    await page.reload();
    await expect.poll(async () => page.evaluate(() => document.body.classList.contains('theme-dark')), { timeout: 10000 }).toBe(!initial);

    await darkSwitch.click();

    await expect.poll(async () => page.evaluate(() => document.body.classList.contains('theme-dark')), { timeout: 10000 }).toBe(initial);
  });

  test('export report CSV', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/frontend/admin/reportes/index.html');

    const downloadPromise = page.waitForEvent('download', { timeout: 15000 });
    await page.locator('#btn-export-licitaciones').click();

    const download = await downloadPromise;
    expect(download.suggestedFilename().toLowerCase()).toContain('.csv');
  });
});
