import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';

test.describe('Público favoritos', () => {
  test('puede navegar a favoritos desde centro', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await page.getByRole('link', { name: /Mis favoritos/i }).click();
    await page.waitForURL('**/frontend/publico/favoritos.html');

    await expect(page.getByRole('heading', { name: /Mis licitaciones favoritas/i })).toBeVisible();
  });

  test('centro muestra conteo de favoritos', async ({ page }) => {
    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForURL('**/frontend/publico/centro.html');

    await expect(page.locator('#publico-fav-count')).toBeVisible({ timeout: 10000 });
  });

  test('marca y desmarca favorito desde convocatoria y verifica en lista', async ({ page, request }) => {
    const token = await loginToken(request, PUBLIC_EMAIL, PUBLIC_PASSWORD);

    // Obtener convocatorias públicas
    const convRes = await request.get('/api/v1/public/convocatorias?limit=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const convPayload = await convRes.json();
    const convocatorias = convPayload?.data?.items || [];

    if (!convocatorias.length) {
      test.skip(true, 'Sin convocatorias disponibles');
      return;
    }

    const licitacion = convocatorias[0];

    // Quitar favorito preexistente si existe
    await request.delete(`/api/v1/favoritos/${licitacion.id_licitacion}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    await loginUI(page, PUBLIC_EMAIL, PUBLIC_PASSWORD, '**/frontend/publico/centro.html');

    // Ir a convocatoria
    await page.goto(`/convocatoria.php?id=${licitacion.id_licitacion}`);
    await page.waitForURL(`**/convocatoria.php?id=${licitacion.id_licitacion}`);

    // Esperar a que cargue el botón de favorito
    await expect(page.locator('#fav-btn')).toBeVisible({ timeout: 15000 });

    // Marcar como favorito
    await page.locator('#fav-btn').click();
    await expect(page.locator('#fav-btn')).toContainText('Guardada', { timeout: 10000 });

    // Ir a favoritos y verificar que aparece
    await page.goto('/frontend/publico/favoritos.html');
    await page.waitForURL('**/frontend/publico/favoritos.html');
    await expect(page.getByRole('heading', { name: /Mis licitaciones favoritas/i })).toBeVisible();
    await expect(page.locator('#favoritos-lista')).toContainText(licitacion.numero_licitacion || '', { timeout: 15000 });

    // Quitar de favoritos desde la lista
    const unfavBtn = page.locator('button[data-unfav]');
    await expect(unfavBtn).toBeVisible();
    await unfavBtn.click();

    // Verificar que desaparece de la lista
    await expect(page.locator('#favoritos-empty')).toBeVisible({ timeout: 15000 });
  });

  test('API: no se puede duplicar favorito', async ({ request }) => {
    const token = await loginToken(request, PUBLIC_EMAIL, PUBLIC_PASSWORD);

    const convRes = await request.get('/api/v1/public/convocatorias?limit=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const convPayload = await convRes.json();
    const convocatorias = convPayload?.data?.items || [];

    if (!convocatorias.length) {
      test.skip(true, 'Sin convocatorias disponibles');
      return;
    }

    const id = convocatorias[0].id_licitacion;

    // Limpiar
    await request.delete(`/api/v1/favoritos/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    // Agregar
    const post1 = await request.post('/api/v1/favoritos', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { id_licitacion: id },
    });
    expect(post1.status()).toBe(201);

    // Intentar duplicar
    const post2 = await request.post('/api/v1/favoritos', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { id_licitacion: id },
    });
    expect(post2.status()).toBe(422);

    // Limpiar
    await request.delete(`/api/v1/favoritos/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
  });
});
