import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Proveedor Soporte', () => {
  test('soporte.html carga y muestra formulario nuevo ticket', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/soporte.html');
    await page.waitForURL('**/frontend/proveedor/soporte.html');

    await expect(page.getByRole('heading', { name: /Soporte/i })).toBeVisible({ timeout: 15000 });
    await expect(page.locator('#btn-nuevo-ticket')).toBeVisible();

    // Click nuevo ticket
    await page.locator('#btn-nuevo-ticket').click();
    await expect(page.locator('#ticket-asunto')).toBeVisible();
    await expect(page.locator('#ticket-descripcion')).toBeVisible();
    await expect(page.locator('#ticket-prioridad')).toBeVisible();
  });

  test('crear ticket y verificar que aparece en la lista', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/soporte.html');

    // Click nuevo ticket
    await page.locator('#btn-nuevo-ticket').click();

    const asunto = 'E2E Test Ticket ' + Date.now();
    await page.locator('#ticket-asunto').fill(asunto);
    await page.locator('#ticket-descripcion').fill('Descripcion de prueba automatizada para ticket de soporte.');
    await page.locator('#ticket-prioridad').selectOption('MEDIA');

    await page.locator('#btn-crear').click();

    // Wait for form to hide and list to update
    await expect(page.locator('#form-section')).toBeHidden({ timeout: 10000 });
    await expect(page.locator('#tickets-list')).toBeVisible({ timeout: 10000 });

    // Verify the ticket appears in the list
    const ticketCard = page.locator('#tickets-list').getByText(asunto);
    await expect(ticketCard.first()).toBeVisible({ timeout: 10000 });
  });

  test('abrir ticket y agregar respuesta', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/soporte.html');

    // Create a ticket first
    await page.locator('#btn-nuevo-ticket').click();
    const asunto = 'E2E Respuesta ' + Date.now();
    await page.locator('#ticket-asunto').fill(asunto);
    await page.locator('#ticket-descripcion').fill('Ticket para probar respuestas.');
    await page.locator('#btn-crear').click();
    await expect(page.locator('#form-section')).toBeHidden({ timeout: 10000 });

    // Click on the ticket card
    const ticketCard = page.locator('#tickets-list').getByText(asunto);
    await ticketCard.first().click();

    // Detail section should be visible
    await expect(page.locator('#detail-section')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#detail-asunto')).toHaveText(asunto);

    // Add a response
    const mensaje = 'Esta es una respuesta de prueba E2E.';
    await page.locator('#respuesta-mensaje').fill(mensaje);
    await page.locator('#btn-responder').click();

    // Response should appear in the thread
    await expect(page.locator('#respuestas-list').getByText(mensaje)).toBeVisible({ timeout: 10000 });
  });

  test('centro proveedor tiene enlace a soporte', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    const link = page.getByRole('link', { name: /Soporte/i });
    await expect(link).toBeVisible({ timeout: 10000 });
    await link.click();
    await page.waitForURL('**/frontend/proveedor/soporte.html');
    await expect(page.getByRole('heading', { name: /Soporte/i })).toBeVisible();
  });

  test('api tickets devuelve estructura correcta', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // Create ticket via API
    const createRes = await request.post('/api/v1/tickets', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { asunto: 'API Test ' + Date.now(), descripcion: 'Desc API', prioridad: 'BAJA' },
    });
    expect(createRes.ok()).toBeTruthy();
    const createBody = await createRes.json();
    expect(createBody.success).toBe(true);
    expect(createBody.data).toHaveProperty('id_ticket');
    const ticketId = createBody.data.id_ticket;

    // List my tickets
    const listRes = await request.get('/api/v1/tickets/mios', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(listRes.ok()).toBeTruthy();
    const listBody = await listRes.json();
    expect(listBody.success).toBe(true);
    expect(listBody.data).toHaveProperty('items');
    expect(listBody.data).toHaveProperty('resumen');
    expect(Array.isArray(listBody.data.items)).toBe(true);

    // Get detail
    const detailRes = await request.get(`/api/v1/tickets/${ticketId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(detailRes.ok()).toBeTruthy();
    const detailBody = await detailRes.json();
    expect(detailBody.success).toBe(true);
    expect(detailBody.data).toHaveProperty('respuestas');
    expect(Array.isArray(detailBody.data.respuestas)).toBe(true);

    // Add response
    const respRes = await request.post(`/api/v1/tickets/${ticketId}/respuestas`, {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { mensaje: 'Respuesta API test' },
    });
    expect(respRes.ok()).toBeTruthy();
    const respBody = await respRes.json();
    expect(respBody.success).toBe(true);
    expect(respBody.data).toHaveProperty('id_respuesta');
  });
});
