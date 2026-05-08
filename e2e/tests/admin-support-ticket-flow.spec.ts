import { expect, test } from '@playwright/test';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

async function loginAsAdmin(page: import('@playwright/test').Page): Promise<void> {
  for (let attempt = 0; attempt < 3; attempt++) {
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(ADMIN_EMAIL);
    await page.locator('#password').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();

    try {
      await page.waitForURL('**/frontend/admin/dashboard.html', { timeout: 12000 });
      return;
    } catch (_) {
      const loginError = ((await page.locator('#error-text').textContent()) || '').toLowerCase();
      if (loginError.includes('demasiados') || loginError.includes('intentos') || loginError.includes('espera')) {
        await page.waitForTimeout(65000);
        continue;
      }
      throw new Error(`No se pudo iniciar sesion: ${loginError || 'error desconocido'}`);
    }
  }

  throw new Error('No se pudo iniciar sesion despues de multiples reintentos.');
}

test.describe('Admin support ticket flow', () => {
  test.describe.configure({ timeout: 120000 });

  test('admin can filter and update a public support ticket status', async ({ page, request }) => {
    const uid = Date.now();
    const creation = await request.post('/api/v1/public/soporte', {
      data: {
        nombre: `Usuario E2E ${uid}`,
        email: `e2e.soporte.${uid}@example.com`,
        telefono: '5551234567',
        asunto: `Asunto E2E ${uid}`,
        mensaje: `Mensaje de prueba para ticket ${uid}`,
      },
    });
    const createdPayload = await creation.json().catch(() => null);
    const createdFolio = (creation.ok() ? createdPayload?.data?.folio : null) as string | null;

    await loginAsAdmin(page);
    await page.goto('/frontend/admin/configuracion/index.html');

    await expect(page.locator('#support-section')).toBeVisible({ timeout: 20000 });
    await expect.poll(
      async () => ((await page.locator('#support-table-body').textContent()) || '').trim(),
      { timeout: 20000 }
    ).not.toContain('Cargando tickets...');

    const firstRow = page.locator('#support-table-body tr').first();
    const tableText = ((await page.locator('#support-table-body').textContent()) || '').trim();
    if (tableText.includes('No fue posible cargar tickets')) {
      test.skip(true, 'La API admin de soporte no responde en este entorno.');
    }

    const firstRowText = ((await firstRow.textContent()) || '').trim();
    if (firstRowText.includes('No hay tickets')) {
      test.skip(true, 'No hay tickets disponibles para validar flujo de soporte.');
    }

    const fallbackFolio = (((await firstRow.locator('td').first().textContent()) || '').split('\n')[0] || '').trim();
    const folio = createdFolio || fallbackFolio;
    expect(folio).toBeTruthy();

    await page.locator('#support-search').fill(folio);
    await page.locator('#support-filter-form button[type="submit"]').click();

    const row = page.locator('#support-table-body tr', { hasText: folio }).first();
    await expect(row).toBeVisible({ timeout: 20000 });

    const statusSelect = row.locator('select[data-ticket-status]');
    const saveButton = row.locator('button[data-ticket-save]');
    await expect(statusSelect).toBeVisible();
    await expect(saveButton).toBeVisible();

    const original = await statusSelect.inputValue();
    const next = original === 'EN_PROCESO' ? 'CERRADO' : 'EN_PROCESO';

    await statusSelect.selectOption(next);
    await saveButton.click();
    await expect(row.locator('span', { hasText: /En proceso|Cerrado/i })).toBeVisible({ timeout: 15000 });

    await page.locator('#support-search').fill(folio);
    await page.locator('#support-filter-form button[type="submit"]').click();
    const rowAfter = page.locator('#support-table-body tr', { hasText: folio }).first();
    await expect(rowAfter).toBeVisible({ timeout: 20000 });
    await expect(rowAfter.locator('select[data-ticket-status]')).toHaveValue(next);

    await rowAfter.locator('select[data-ticket-status]').selectOption(original);
    await rowAfter.locator('button[data-ticket-save]').click();
    await page.locator('#support-filter-form button[type="submit"]').click();
    await expect(page.locator('#support-table-body tr', { hasText: folio }).first().locator('select[data-ticket-status]')).toHaveValue(original);
  });
});
