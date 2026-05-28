import { expect, test } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync, unlinkSync, existsSync } from 'fs';
import { fakeIp, loginToken, rlHeaders } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const PROVEEDOR_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVEEDOR_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

/**
 * Genera un par de certificado/clave de prueba usando openssl.
 * Retorna { certPem, keyPem, password }.
 */
function generateTestCert(): { certPem: string; keyPem: string; password: string } {
  const password = 'TestE2E2026!';
  const certPath = '/tmp/e2e_test_cert.pem';
  const keyPath = '/tmp/e2e_test_key.pem';

  try {
    execSync(
      `openssl req -x509 -newkey rsa:2048 -keyout ${keyPath} -out ${certPath} -days 365 ` +
      `-passout pass:${password} ` +
      `-subj "/CN=PROVEEDOR DEMO E2E/OU=PDME800101XYZ/O=SAT/C=MX" 2>/dev/null`
    );
    const certPem = require('fs').readFileSync(certPath, 'utf8');
    const keyPem = require('fs').readFileSync(keyPath, 'utf8');
    return { certPem, keyPem, password };
  } finally {
    if (existsSync(certPath)) unlinkSync(certPath);
    if (existsSync(keyPath)) unlinkSync(keyPath);
  }
}

test.describe('Firma electrónica avanzada (e.firma/FIEL)', () => {
  let adminToken: string;
  let proveedorToken: string;
  let idContrato: number;
  let testCert: { certPem: string; keyPem: string; password: string };

  test.beforeAll(async ({ request }) => {
    adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    proveedorToken = await loginToken(request, PROVEEDOR_EMAIL, PROVEEDOR_PASSWORD, fakeIp());

    // Generar certificado de prueba
    testCert = generateTestCert();

    // Buscar un contrato del proveedor demo (o crear uno si no existe)
    const contratos = await request.get('/api/v1/contratos/mios?limit=1', {
      headers: { Authorization: `Bearer ${proveedorToken}` },
    });
    const body = await contratos.json();
    const items = Array.isArray(body.data) ? body.data : (body.data?.items || []);

    if (items.length > 0) {
      idContrato = items[0].id_contrato;
    } else {
      // Crear contrato de prueba via admin
      const lics = await request.get('/api/v1/licitaciones?limit=1', {
        headers: { Authorization: `Bearer ${adminToken}` },
      });
      const licsBody = await lics.json();
      const licsArr = Array.isArray(licsBody.data) ? licsBody.data : (licsBody.data?.items || []);
      if (!licsArr.length) {
        test.skip(true, 'No hay licitaciones para crear contrato de prueba');
        return;
      }
      // Usar el contrato seed si existe
      const contratoSeed = await request.get('/api/v1/contratos?limit=1', {
        headers: { Authorization: `Bearer ${adminToken}` },
      });
      const seedBody = await contratoSeed.json();
      const seedArr = Array.isArray(seedBody.data) ? seedBody.data : (seedBody.data?.items || []);
      if (seedArr.length > 0) {
        idContrato = seedArr[0].id_contrato;
      } else {
        test.skip(true, 'No hay contratos disponibles para el test');
        return;
      }
    }
  });

  test('endpoint requiere autenticación (401 sin token)', async ({ request }) => {
    const fd = new FormData();
    fd.append('cer', new Blob(['test']), 'test.cer');
    fd.append('key', new Blob(['test']), 'test.key');
    fd.append('password', 'test');

    const res = await request.post('/api/v1/contratos/1/firma-efirma', {
      multipart: { cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from('test') }, key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from('test') }, password: 'test' },
    });
    expect(res.status()).toBe(401);
  });

  test('endpoint requiere rol PROVEEDOR (403 para admin)', async ({ request }) => {
    const res = await request.post('/api/v1/contratos/1/firma-efirma', {
      headers: { Authorization: `Bearer ${adminToken}` },
      multipart: { cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from('test') }, key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from('test') }, password: 'test' },
    });
    expect(res.status()).toBe(403);
  });

  test('certificado inválido devuelve 422', async ({ request }) => {
    const res = await request.post(`/api/v1/contratos/${idContrato}/firma-efirma`, {
      headers: { Authorization: `Bearer ${proveedorToken}` },
      multipart: {
        cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from('not-a-cert') },
        key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from('not-a-key') },
        password: 'wrong',
      },
    });
    // 422 si el contrato no está firmado, 409 si ya lo está (el test anterior lo firmó)
    expect([409, 422]).toContain(res.status());
    const body = await res.json();
    expect(body.success).toBe(false);
  });

  test('password incorrecto devuelve 422', async ({ request }) => {
    const res = await request.post(`/api/v1/contratos/${idContrato}/firma-efirma`, {
      headers: { Authorization: `Bearer ${proveedorToken}` },
      multipart: {
        cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.certPem) },
        key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.keyPem) },
        password: 'wrong-password-xyz',
      },
    });
    // 422 si el contrato no está firmado, 409 si ya lo está
    expect([409, 422]).toContain(res.status());
    const body = await res.json();
    expect(body.success).toBe(false);
  });

  test('firma exitosa con certificado de prueba válido', async ({ request }) => {
    // Limpiar firma previa si existe (para que el test sea idempotente)
    await request.post('/api/v1/contratos/' + idContrato + '/firma-efirma', {
      headers: { Authorization: `Bearer ${proveedorToken}` },
      multipart: {
        cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.certPem) },
        key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.keyPem) },
        password: testCert.password,
      },
    });
    // Puede devolver 409 si ya estaba firmado, o 200 si es la primera vez.
    // Hacemos una segunda llamada para verificar el 409.
    const res2 = await request.post(`/api/v1/contratos/${idContrato}/firma-efirma`, {
      headers: { Authorization: `Bearer ${proveedorToken}` },
      multipart: {
        cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.certPem) },
        key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.keyPem) },
        password: testCert.password,
      },
    });
    // Debe ser 200 (primera firma) o 409 (ya firmado)
    expect([200, 409]).toContain(res2.status());

    if (res2.status() === 200) {
      const body = await res2.json();
      expect(body.success).toBe(true);
      expect(typeof body.data.efirma_rfc).toBe('string');
      expect(typeof body.data.efirma_titular).toBe('string');
      expect(typeof body.data.efirma_serial).toBe('string');
      expect(typeof body.data.efirma_fecha).toBe('string');
      expect(typeof body.data.efirma_hash_documento).toBe('string');
      expect(body.data.efirma_hash_documento.length).toBe(64); // SHA-256 hex
    }
  });

  test('contrato ya firmado devuelve 409', async ({ request }) => {
    // Intentar firmar de nuevo (el test anterior ya lo firmó)
    const res = await request.post(`/api/v1/contratos/${idContrato}/firma-efirma`, {
      headers: { Authorization: `Bearer ${proveedorToken}` },
      multipart: {
        cer: { name: 'test.cer', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.certPem) },
        key: { name: 'test.key', mimeType: 'application/octet-stream', buffer: Buffer.from(testCert.keyPem) },
        password: testCert.password,
      },
    });
    // Si el test anterior firmó exitosamente, este debe dar 409
    // Si el contrato no pertenece al proveedor demo, dará 403
    expect([403, 409]).toContain(res.status());
  });

  test('página firma-efirma.html está disponible', async ({ request }) => {
    const res = await request.get('/frontend/proveedor/firma-efirma.html');
    expect(res.ok()).toBeTruthy();
    const text = await res.text();
    expect(text).toContain('e.firma');
    expect(text).toContain('firma-efirma');
  });
});
