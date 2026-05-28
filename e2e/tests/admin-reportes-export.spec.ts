import { expect, test } from '@playwright/test';
import { fakeIp, loginToken } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Admin reportes — exportación con plantilla', () => {
  let token: string;
  let idPlantillaResumen: number;
  let idLicitacion: number;

  test.beforeAll(async ({ request }) => {
    token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());

    // Buscar plantilla Resumen
    const list = await request.get('/api/v1/admin/plantillas?activa=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const items = (await list.json()).data.items;
    const resumen = items.find((p: any) => p.tipo === 'RESUMEN_LICITACION');
    expect(resumen, 'Plantilla RESUMEN_LICITACION no encontrada').toBeDefined();
    idPlantillaResumen = resumen.id_plantilla;

    // Buscar primera licitación
    const lics = await request.get('/api/v1/licitaciones?limit=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const licsBody = await lics.json();
    const arr = Array.isArray(licsBody.data) ? licsBody.data : (licsBody.data?.items || licsBody.data || []);
    expect(arr.length, 'No hay licitaciones para usar como entidad de prueba').toBeGreaterThan(0);
    idLicitacion = arr[0].id_licitacion;
  });

  test('genera PDF válido (magic bytes %PDF)', async ({ request }) => {
    const res = await request.post('/api/v1/reportes/generar', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: {
        id_plantilla: idPlantillaResumen,
        entidad: 'licitacion',
        id_entidad: idLicitacion,
        formato: 'pdf',
      },
    });
    expect(res.ok(), `generar pdf falló: ${res.status()} ${await res.text()}`).toBeTruthy();
    expect(res.headers()['content-type']).toContain('application/pdf');
    expect(res.headers()['content-disposition']).toContain('attachment');

    const buf = await res.body();
    expect(buf.length).toBeGreaterThan(500);
    // %PDF
    expect(buf[0]).toBe(0x25);
    expect(buf[1]).toBe(0x50);
    expect(buf[2]).toBe(0x44);
    expect(buf[3]).toBe(0x46);
  });

  test('genera DOCX válido (magic bytes ZIP/OOXML)', async ({ request }) => {
    const res = await request.post('/api/v1/reportes/generar', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: {
        id_plantilla: idPlantillaResumen,
        entidad: 'licitacion',
        id_entidad: idLicitacion,
        formato: 'docx',
      },
    });
    expect(res.ok()).toBeTruthy();
    expect(res.headers()['content-type']).toContain('wordprocessingml');

    const buf = await res.body();
    expect(buf.length).toBeGreaterThan(1000);
    // PK\x03\x04
    expect(buf[0]).toBe(0x50);
    expect(buf[1]).toBe(0x4b);
    expect(buf[2]).toBe(0x03);
    expect(buf[3]).toBe(0x04);
  });

  test('genera Markdown con front-matter YAML', async ({ request }) => {
    const res = await request.post('/api/v1/reportes/generar', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: {
        id_plantilla: idPlantillaResumen,
        entidad: 'licitacion',
        id_entidad: idLicitacion,
        formato: 'md',
      },
    });
    expect(res.ok()).toBeTruthy();
    expect(res.headers()['content-type']).toContain('text/markdown');

    const text = await res.text();
    expect(text.startsWith('---\n')).toBe(true);
    expect(text).toContain('plantilla:');
    expect(text).toContain('tipo: RESUMEN_LICITACION');
  });

  test('rechaza formato inválido (422)', async ({ request }) => {
    const res = await request.post('/api/v1/reportes/generar', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: {
        id_plantilla: idPlantillaResumen,
        entidad: 'licitacion',
        id_entidad: idLicitacion,
        formato: 'xml',
      },
    });
    expect(res.status()).toBe(422);
  });

  test('rechaza entidad inexistente (404)', async ({ request }) => {
    const res = await request.post('/api/v1/reportes/generar', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: {
        id_plantilla: idPlantillaResumen,
        entidad: 'licitacion',
        id_entidad: 999999,
        formato: 'pdf',
      },
    });
    expect(res.status()).toBe(404);
  });

  test('proveedor no puede generar reportes (403)', async ({ request }) => {
    const proveedorEmail = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
    const proveedorPass = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';
    const provToken = await loginToken(request, proveedorEmail, proveedorPass, fakeIp());

    const res = await request.post('/api/v1/reportes/generar', {
      headers: { Authorization: `Bearer ${provToken}`, 'Content-Type': 'application/json' },
      data: {
        id_plantilla: idPlantillaResumen,
        entidad: 'licitacion',
        id_entidad: idLicitacion,
        formato: 'pdf',
      },
    });
    expect(res.status()).toBe(403);
  });
});
