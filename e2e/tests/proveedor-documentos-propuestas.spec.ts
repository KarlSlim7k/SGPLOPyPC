import { expect, test } from '@playwright/test';
import { loginToken, loginUI } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

// Buffer mínimo reconocido como PDF por finfo
function fakePdfBuffer(): Buffer {
  return Buffer.from('%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n171\n%%EOF');
}

test.describe('Proveedor Documentos y Propuestas', () => {
  test('subir documento legal, eliminar y verificar que desaparece', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // 1) Subir documento legal vía API
    const uploadRes = await request.post('/api/v1/documentos/upload', {
      headers: { Authorization: `Bearer ${token}` },
      multipart: {
        archivo: {
          name: 'test-legal.pdf',
          mimeType: 'application/pdf',
          buffer: fakePdfBuffer(),
        },
        tipo_documento: 'DOC_LEGAL_PROVEEDOR',
      },
    });
    expect(uploadRes.ok()).toBeTruthy();
    const uploadBody = await uploadRes.json();
    expect(uploadBody.success).toBe(true);
    const docId = uploadBody.data.id_documento;

    // 2) Ir a documentos.html y verificar que aparece
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/documentos.html');
    await page.waitForURL('**/frontend/proveedor/documentos.html');
    await expect(page.getByRole('heading', { name: /Documentos/i })).toBeVisible({ timeout: 15000 });

    // Esperar a que cargue la tabla
    await expect(page.locator('#rows')).not.toContainText('Cargando documentos', { timeout: 15000 });
    await expect(page.locator('#rows')).toContainText('test-legal.pdf', { timeout: 15000 });

    // 3) Click en Eliminar
    const deleteBtn = page.locator('button[data-delete]').first();
    await expect(deleteBtn).toBeVisible();

    await page.evaluate(() => { window.confirm = () => true; });
    await deleteBtn.click();

    // Esperar a que desaparezca
    await expect(page.locator('#rows')).not.toContainText('test-legal.pdf', { timeout: 10000 });
  });

  test('crear propuesta, retirar y verificar cambio a RETIRADA', async ({ page, request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // 1) Obtener participaciones del proveedor
    const partRes = await request.get('/api/v1/participaciones/mias?page=1&limit=100', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(partRes.ok()).toBeTruthy();
    const partBody = await partRes.json();
    const participaciones = partBody.data.items || [];

    let idParticipacion: number | null = null;

    // Buscar una participación en RECEPCION_PROPUESTAS sin propuesta para crear una
    const elegible = participaciones.find(
      (p: any) => p.estado_proceso === 'RECEPCION_PROPUESTAS' && !p.id_propuesta
    );

    if (elegible) {
      // Crear propuesta
      const propRes = await request.post(`/api/v1/participaciones/${elegible.id_participacion}/propuesta`, {
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
        data: { monto_propuesta: 123456.78, descripcion_tecnica: 'Propuesta de prueba E2E' },
      });
      expect(propRes.ok()).toBeTruthy();
      idParticipacion = elegible.id_participacion;
    } else {
      // Si ya hay propuesta RECIBIDA, usarla directamente
      const propRes = await request.get('/api/v1/propuestas/mias?page=1&limit=100', {
        headers: { Authorization: `Bearer ${token}` },
      });
      expect(propRes.ok()).toBeTruthy();
      const propBody = await propRes.json();
      const propuestas = propBody.data.items || [];
      const recibida = propuestas.find((p: any) => p.estatus === 'RECIBIDA' && p.estado_proceso === 'RECEPCION_PROPUESTAS');
      if (!recibida) {
        test.skip(true, 'No hay propuestas ni participaciones elegibles para este test');
        return;
      }
      idParticipacion = recibida.id_participacion;
    }

    // 2) Ir a propuestas.html
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/propuestas.html');
    await page.waitForURL('**/frontend/proveedor/propuestas.html');
    await expect(page.getByRole('heading', { name: /Mis propuestas/i })).toBeVisible({ timeout: 15000 });

    // Esperar a que cargue la tabla
    await expect(page.locator('#rows')).not.toContainText('Cargando propuestas', { timeout: 15000 });

    // Esperar a que la propuesta aparezca
    const row = page.locator('#rows tr').filter({ hasText: /RECIBIDA/ }).first();
    await expect(row).toBeVisible({ timeout: 15000 });

    // Verificar que hay botón Retirar
    const retirarBtn = row.locator('button[data-retirar]').first();
    await expect(retirarBtn).toBeVisible();

    // 3) Retirar propuesta
    await page.evaluate(() => { window.confirm = () => true; });
    await retirarBtn.click();

    // Esperar a que el badge cambie a RETIRADA
    await expect(page.locator('#rows')).toContainText('RETIRADA', { timeout: 10000 });
  });

  test('eliminar documento vinculado a propuesta evaluada devuelve 409', async ({ request }) => {
    const token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);

    // Obtener propuestas del proveedor
    const propRes = await request.get('/api/v1/propuestas/mias?page=1&limit=100', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(propRes.ok()).toBeTruthy();
    const propBody = await propRes.json();
    const propuestas = propBody.data.items || [];

    // Buscar propuesta evaluada (EN_REVISION, ACEPTADA o RECHAZADA)
    const evaluada = propuestas.find(
      (p: any) => ['EN_REVISION', 'ACEPTADA', 'RECHAZADA'].includes(p.estatus)
    );

    if (!evaluada) {
      test.skip(true, 'No hay propuestas evaluadas para este test');
      return;
    }

    // Subir documento vinculado a esa propuesta
    const buffer = Buffer.from('PDF contenido de prueba');
    const uploadRes = await request.post('/api/v1/documentos/upload', {
      headers: { Authorization: `Bearer ${token}` },
      multipart: {
        archivo: {
          name: 'test-evaluada.pdf',
          mimeType: 'application/pdf',
          buffer,
        },
        tipo_documento: 'PROPUESTA_TECNICA',
        id_propuesta: String(evaluada.id_propuesta),
      },
    });
    expect(uploadRes.ok()).toBeTruthy();
    const uploadBody = await uploadRes.json();
    const docId = uploadBody.data.id_documento;

    // Intentar eliminar → debe dar 409
    const delRes = await request.delete(`/api/v1/documentos/${docId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(delRes.status()).toBe(409);
    const delBody = await delRes.json();
    expect(delBody.success).toBe(false);
  });

  test('documentos.html muestra botón eliminar en filas', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/documentos.html');
    await expect(page.getByRole('heading', { name: /Documentos/i })).toBeVisible({ timeout: 15000 });

    // Si hay documentos, verificar que al menos uno tiene botón eliminar
    const rows = page.locator('#rows tr');
    const count = await rows.count();
    if (count > 0 && await rows.first().isVisible()) {
      const deleteBtn = rows.first().locator('button[data-delete]');
      await expect(deleteBtn).toBeVisible();
    }
  });

  test('propuestas.html muestra botón retirar en propuestas RECIBIDA', async ({ page }) => {
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.goto('/frontend/proveedor/propuestas.html');
    await expect(page.getByRole('heading', { name: /Mis propuestas/i })).toBeVisible({ timeout: 15000 });

    // Buscar fila con RECIBIDA
    const row = page.locator('#rows tr').filter({ hasText: /RECIBIDA/ }).first();
    if (await row.isVisible().catch(() => false)) {
      const retirarBtn = row.locator('button[data-retirar]').first();
      await expect(retirarBtn).toBeVisible();
    }
  });
});
