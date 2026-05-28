import { expect, test } from '@playwright/test';
import { fakeIp, loginToken } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVEEDOR_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVEEDOR_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Calificación y reputación de proveedores', () => {
  let adminToken: string;
  let proveedorToken: string;
  let idProveedor: number;
  let idContrato: number;

  test.beforeAll(async ({ request }) => {
    adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    proveedorToken = await loginToken(request, PROVEEDOR_EMAIL, PROVEEDOR_PASSWORD, fakeIp());

    // Obtener id_proveedor del proveedor demo
    const me = await request.get('/api/v1/me', { headers: { Authorization: `Bearer ${proveedorToken}` } });
    const meBody = await me.json();
    idProveedor = meBody.data.id_proveedor || meBody.data.proveedor?.id_proveedor;

    // Obtener un contrato del proveedor
    const contratos = await request.get('/api/v1/contratos/mios?limit=1', {
      headers: { Authorization: `Bearer ${proveedorToken}` },
    });
    const contratosBody = await contratos.json();
    const items = Array.isArray(contratosBody.data) ? contratosBody.data : (contratosBody.data?.items || []);
    if (items.length > 0) {
      idContrato = items[0].id_contrato;
    }
  });

  test('GET /proveedores/{id}/reputacion responde 200 con estructura correcta', async ({ request }) => {
    if (!idProveedor) test.skip(true, 'No se pudo obtener id_proveedor');

    const res = await request.get(`/api/v1/proveedores/${idProveedor}/reputacion`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    expect(res.ok(), `reputacion failed: ${res.status()} ${await res.text()}`).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.data.id_proveedor).toBe(idProveedor);
    expect(typeof body.data.total_evaluaciones).toBe('number');
    expect(['sin_evaluaciones', 'excelente', 'bueno', 'regular', 'deficiente']).toContain(body.data.nivel);
    expect(Array.isArray(body.data.historial)).toBe(true);
  });

  test('proveedor puede ver su propia reputación', async ({ request }) => {
    if (!idProveedor) test.skip(true, 'No se pudo obtener id_proveedor');

    const res = await request.get(`/api/v1/proveedores/${idProveedor}/reputacion`, {
      headers: { Authorization: `Bearer ${proveedorToken}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
  });

  test('GET /proveedores/{id}/reputacion sin auth devuelve 401', async ({ request }) => {
    const res = await request.get('/api/v1/proveedores/1/reputacion');
    expect(res.status()).toBe(401);
  });

  test('POST evaluacion-postcontrato requiere rol ADMINISTRADOR (403 para proveedor)', async ({ request }) => {
    const res = await request.post('/api/v1/contratos/1/evaluacion-postcontrato', {
      headers: { Authorization: `Bearer ${proveedorToken}`, 'Content-Type': 'application/json' },
      data: { puntualidad: 5, calidad: 5, comunicacion: 5, cumplimiento_alcance: 5 },
    });
    expect(res.status()).toBe(403);
  });

  test('POST evaluacion-postcontrato valida rango 1-5', async ({ request }) => {
    if (!idContrato) test.skip(true, 'No hay contrato disponible');

    const res = await request.post(`/api/v1/contratos/${idContrato}/evaluacion-postcontrato`, {
      headers: { Authorization: `Bearer ${adminToken}`, 'Content-Type': 'application/json' },
      data: { puntualidad: 6, calidad: 0, comunicacion: 3, cumplimiento_alcance: 3 },
    });
    // 422 si el contrato no está evaluado, 409 si ya lo está (el test anterior lo evaluó)
    expect([409, 422]).toContain(res.status());
    const body = await res.json();
    expect(body.success).toBe(false);
  });

  test('POST evaluacion-postcontrato crea evaluación y actualiza score', async ({ request }) => {
    if (!idContrato || !idProveedor) test.skip(true, 'No hay contrato/proveedor disponible');

    // Obtener score antes
    const antes = await request.get(`/api/v1/proveedores/${idProveedor}/reputacion`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    const antesBody = await antes.json();
    const totalAntes = antesBody.data.total_evaluaciones;

    // Crear evaluación (puede dar 409 si ya existe)
    const res = await request.post(`/api/v1/contratos/${idContrato}/evaluacion-postcontrato`, {
      headers: { Authorization: `Bearer ${adminToken}`, 'Content-Type': 'application/json' },
      data: {
        puntualidad: 5,
        calidad: 4,
        comunicacion: 5,
        cumplimiento_alcance: 4,
        comentarios: 'Test E2E evaluación',
      },
    });

    if (res.status() === 409) {
      // Ya evaluado — verificar que el score existe
      const despues = await request.get(`/api/v1/proveedores/${idProveedor}/reputacion`, {
        headers: { Authorization: `Bearer ${adminToken}` },
      });
      const despuesBody = await despues.json();
      expect(despuesBody.data.total_evaluaciones).toBeGreaterThan(0);
      expect(despuesBody.data.score_reputacion).not.toBeNull();
      return;
    }

    expect(res.status()).toBe(201);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(typeof body.data.promedio).toBe('number');
    expect(body.data.promedio).toBeGreaterThan(0);
    expect(body.data.promedio).toBeLessThanOrEqual(5);
    expect(body.data.score_reputacion_actualizado).not.toBeNull();

    // Verificar que el score se actualizó
    const despues = await request.get(`/api/v1/proveedores/${idProveedor}/reputacion`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    const despuesBody = await despues.json();
    expect(despuesBody.data.total_evaluaciones).toBeGreaterThan(totalAntes);
    expect(despuesBody.data.historial.length).toBeGreaterThan(0);
    expect(['excelente', 'bueno', 'regular', 'deficiente']).toContain(despuesBody.data.nivel);
  });

  test('POST evaluacion-postcontrato rechaza contrato ya evaluado (409)', async ({ request }) => {
    if (!idContrato) test.skip(true, 'No hay contrato disponible');

    // Intentar evaluar de nuevo
    const res = await request.post(`/api/v1/contratos/${idContrato}/evaluacion-postcontrato`, {
      headers: { Authorization: `Bearer ${adminToken}`, 'Content-Type': 'application/json' },
      data: { puntualidad: 3, calidad: 3, comunicacion: 3, cumplimiento_alcance: 3 },
    });
    expect([409, 422]).toContain(res.status()); // 409 si ya evaluado, 422 si estatus no permitido
  });
});
