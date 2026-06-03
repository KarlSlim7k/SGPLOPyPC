import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor e.firma Navegacion', () => {
  test('contrato.html muestra informacion de firma y navegacion', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // Obtener un contrato del proveedor
    const contratosRes = await request.get('/api/v1/contratos/mios?page=1&limit=10', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(contratosRes.ok()).toBeTruthy();
    const contratosBody = await contratosRes.json();
    const contratos = contratosBody.data?.items || [];

    if (contratos.length === 0) {
      test.skip(true, 'El proveedor demo no tiene contratos');
      return;
    }

    const contrato = contratos[0];

    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/contrato.html?id=' + contrato.id_contrato);
    await page.waitForURL('**/frontend/proveedor/contrato.html?id=*');

    await expect(page.locator('#titulo')).toBeVisible({ timeout: 15000 });

    // Verificar que la seccion de firma existe
    const firmaBadge = page.locator('#firma-badge');
    await expect(firmaBadge).toBeVisible();

    // Si esta en EN_FORMALIZACION y sin firma, deberia haber boton e.firma
    if (contrato.estatus === 'EN_FORMALIZACION' && !contrato.fecha_firma_proveedor) {
      const efirmaLink = page.locator('#efirma-link');
      await expect(efirmaLink).toBeVisible();
      const href = await efirmaLink.getAttribute('href');
      expect(href).toContain('/frontend/proveedor/firma-efirma.html?id=' + contrato.id_contrato);
    }

    // Si ya tiene e.firma, el badge deberia mencionar e.firma
    if (contrato.efirma_firma_b64 || contrato.efirma_fecha) {
      await expect(firmaBadge).toContainText(/e\.firma/i);
    }
  });

  test('contratos.html muestra columna firma con iconos correctos', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    const contratosRes = await request.get('/api/v1/contratos/mios?page=1&limit=10', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(contratosRes.ok()).toBeTruthy();
    const contratosBody = await contratosRes.json();
    const contratos = contratosBody.data?.items || [];

    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/contratos.html');
    await page.waitForURL('**/frontend/proveedor/contratos.html');
    await expect(page.getByRole('heading', { name: /Mis contratos/i })).toBeVisible({ timeout: 15000 });

    if (contratos.length === 0) {
      // Verificar que la tabla existe incluso sin datos
      await expect(page.locator('#rows')).toBeVisible();
      return;
    }

    await expect(page.locator('#rows')).toBeVisible({ timeout: 15000 });

    // Verificar que la columna Firma tiene contenido en al menos una fila
    const firstRow = page.locator('#rows tr').first();
    await expect(firstRow).toBeVisible();
    const firmaCell = firstRow.locator('td').nth(7); // octava columna = Firma
    await expect(firmaCell).toBeVisible();
  });

  test('firma-efirma.html carga con id_contrato y muestra boton volver', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    const contratosRes = await request.get('/api/v1/contratos/mios?page=1&limit=10', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(contratosRes.ok()).toBeTruthy();
    const contratosBody = await contratosRes.json();
    const contratos = contratosBody.data?.items || [];

    if (contratos.length === 0) {
      test.skip(true, 'El proveedor demo no tiene contratos');
      return;
    }

    const idContrato = contratos[0].id_contrato;

    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/firma-efirma.html?id_contrato=' + idContrato);
    await page.waitForURL('**/frontend/proveedor/firma-efirma.html?id_contrato=*');

    await expect(page.getByRole('heading', { name: /Firma electrónica avanzada/i })).toBeVisible({ timeout: 15000 });

    const volverBtn = page.locator('#btn-volver-contrato');
    await expect(volverBtn).toBeVisible();
    const href = await volverBtn.getAttribute('href');
    expect(href).toContain('/frontend/proveedor/contrato.html?id=' + idContrato);
  });

  test('navegar de contrato a firma-efirma y volver', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    const contratosRes = await request.get('/api/v1/contratos/mios?page=1&limit=10', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(contratosRes.ok()).toBeTruthy();
    const contratosBody = await contratosRes.json();
    const contratos = contratosBody.data?.items || [];

    if (contratos.length === 0) {
      test.skip(true, 'El proveedor demo no tiene contratos');
      return;
    }

    const idContrato = contratos[0].id_contrato;

    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');

    // Ir a contrato
    await page.goto('/frontend/proveedor/contrato.html?id=' + idContrato);
    await page.waitForURL('**/frontend/proveedor/contrato.html?id=*');
    await expect(page.locator('#titulo')).toBeVisible({ timeout: 15000 });

    // Ir a firma-efirma directamente
    await page.goto('/frontend/proveedor/firma-efirma.html?id=' + idContrato);
    await page.waitForURL('**/frontend/proveedor/firma-efirma.html?id=*');
    await expect(page.getByRole('heading', { name: /Firma electrónica avanzada/i })).toBeVisible({ timeout: 15000 });

    // Volver al contrato
    await page.click('#btn-volver-contrato');
    await page.waitForURL('**/frontend/proveedor/contrato.html?id=' + idContrato);
    await expect(page.locator('#titulo')).toBeVisible({ timeout: 15000 });
  });
});
