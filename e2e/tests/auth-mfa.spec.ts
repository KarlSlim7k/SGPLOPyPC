import { expect, test } from '@playwright/test';
import { fakeIp, loginToken, rlHeaders } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('MFA — autenticación de dos factores (TOTP)', () => {
  let token: string;

  test.beforeAll(async ({ request }) => {
    token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    // Asegurar que MFA esté desactivado antes de los tests
    await request.post('/api/v1/me/mfa/disable', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { password: ADMIN_PASSWORD, code: '000000' },
    });
    // Ignorar error si ya estaba desactivado
  });

  test('enroll devuelve secret, otpauth_url y qr_url', async ({ request }) => {
    const res = await request.post('/api/v1/me/mfa/enroll', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    });
    expect(res.ok(), `enroll failed: ${res.status()} ${await res.text()}`).toBeTruthy();
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(typeof body.data.secret).toBe('string');
    expect(body.data.secret.length).toBe(32);
    expect(body.data.otpauth_url).toContain('otpauth://totp/');
    expect(body.data.qr_url).toContain('chart.googleapis.com');
  });

  test('confirm con código incorrecto devuelve 422', async ({ request }) => {
    // Primero enrolar para tener secreto temporal
    await request.post('/api/v1/me/mfa/enroll', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    });

    const res = await request.post('/api/v1/me/mfa/confirm', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { code: '000000' },
    });
    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.success).toBe(false);
    expect(Array.isArray(body.errors)).toBe(true);
  });

  test('flujo completo: enroll → confirm → login con MFA → disable', async ({ request }) => {
    // 1. Enrolar
    const enrollRes = await request.post('/api/v1/me/mfa/enroll', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    });
    expect(enrollRes.ok()).toBeTruthy();
    const { secret } = (await enrollRes.json()).data;

    // 2. Generar código TOTP válido usando el endpoint de test (via API interna)
    // Como no podemos ejecutar PHP aquí, usamos el endpoint de login para verificar
    // que el flujo funciona con un código generado en el servidor.
    // Estrategia: confirmar con código generado via PHP en el servidor.
    const confirmRes = await request.post('/api/v1/me/mfa/confirm', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { code: await generateTotpCode(secret) },
    });
    expect(confirmRes.ok(), `confirm failed: ${confirmRes.status()} ${await confirmRes.text()}`).toBeTruthy();
    const confirmBody = await confirmRes.json();
    expect(Array.isArray(confirmBody.data.backup_codes)).toBe(true);
    expect(confirmBody.data.backup_codes.length).toBe(8);
    const backupCodes = confirmBody.data.backup_codes as string[];

    // 3. Login debe requerir MFA ahora
    const loginRes = await request.post('/api/v1/auth/login', {
      headers: rlHeaders(fakeIp()),
      data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
    });
    expect(loginRes.ok()).toBeTruthy();
    const loginBody = await loginRes.json();
    expect(loginBody.data.requires_mfa).toBe(true);
    expect(typeof loginBody.data.mfa_token).toBe('string');
    const mfaToken = loginBody.data.mfa_token;

    // 4. Completar login con código TOTP
    const mfaLoginRes = await request.post('/api/v1/auth/login/mfa', {
      headers: rlHeaders(fakeIp()),
      data: { mfa_token: mfaToken, code: await generateTotpCode(secret) },
    });
    expect(mfaLoginRes.ok(), `mfa login failed: ${mfaLoginRes.status()} ${await mfaLoginRes.text()}`).toBeTruthy();
    const mfaLoginBody = await mfaLoginRes.json();
    expect(typeof mfaLoginBody.data.token).toBe('string');
    expect(mfaLoginBody.data.usuario.email).toBe(ADMIN_EMAIL);

    // 5. Login con mfa_token inválido devuelve 401
    const badMfaRes = await request.post('/api/v1/auth/login/mfa', {
      headers: rlHeaders(fakeIp()),
      data: { mfa_token: mfaToken, code: '000000' },
    });
    expect(badMfaRes.status()).toBe(401);

    // 6. Usar backup code para login
    const loginRes2 = await request.post('/api/v1/auth/login', {
      headers: rlHeaders(fakeIp()),
      data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
    });
    const mfaToken2 = (await loginRes2.json()).data.mfa_token;
    const backupLoginRes = await request.post('/api/v1/auth/login/mfa', {
      headers: rlHeaders(fakeIp()),
      data: { mfa_token: mfaToken2, code: backupCodes[0] },
    });
    expect(backupLoginRes.ok(), `backup code login failed: ${backupLoginRes.status()} ${await backupLoginRes.text()}`).toBeTruthy();

    // 7. Desactivar MFA
    const newToken = (await backupLoginRes.json()).data.token;
    const disableRes = await request.post('/api/v1/me/mfa/disable', {
      headers: { Authorization: `Bearer ${newToken}`, 'Content-Type': 'application/json' },
      data: { password: ADMIN_PASSWORD, code: await generateTotpCode(secret) },
    });
    expect(disableRes.ok(), `disable failed: ${disableRes.status()} ${await disableRes.text()}`).toBeTruthy();

    // 8. Login normal sin MFA
    const loginFinalRes = await request.post('/api/v1/auth/login', {
      headers: rlHeaders(fakeIp()),
      data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
    });
    const finalBody = await loginFinalRes.json();
    expect(finalBody.data.requires_mfa).toBeFalsy();
    expect(typeof finalBody.data.token).toBe('string');
  });

  test('enroll rechaza si MFA ya está activo', async ({ request }) => {
    // Activar MFA primero
    const enrollRes = await request.post('/api/v1/me/mfa/enroll', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    });
    const { secret } = (await enrollRes.json()).data;
    await request.post('/api/v1/me/mfa/confirm', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { code: await generateTotpCode(secret) },
    });

    // Intentar enrolar de nuevo
    const res = await request.post('/api/v1/me/mfa/enroll', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    });
    expect(res.status()).toBe(409);

    // Limpiar: desactivar MFA
    await request.post('/api/v1/me/mfa/disable', {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      data: { password: ADMIN_PASSWORD, code: await generateTotpCode(secret) },
    });
  });
});

/**
 * Genera un código TOTP válido para el secreto dado.
 * Implementación JS del algoritmo HOTP/TOTP (RFC 6238) para los tests.
 */
async function generateTotpCode(secret: string): Promise<string> {
  const base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  const cleanSecret = secret.toUpperCase().replace(/[^A-Z2-7]/g, '');
  let bits = '';
  for (const c of cleanSecret) {
    const idx = base32Chars.indexOf(c);
    if (idx === -1) continue;
    bits += idx.toString(2).padStart(5, '0');
  }
  const bytes = new Uint8Array(Math.floor(bits.length / 8));
  for (let i = 0; i < bytes.length; i++) {
    bytes[i] = parseInt(bits.slice(i * 8, i * 8 + 8), 2);
  }

  const counter = Math.floor(Date.now() / 1000 / 30);
  const counterBytes = new Uint8Array(8);
  let c = counter;
  for (let i = 7; i >= 0; i--) {
    counterBytes[i] = c & 0xff;
    c = Math.floor(c / 256);
  }

  const key = await crypto.subtle.importKey('raw', bytes, { name: 'HMAC', hash: 'SHA-1' }, false, ['sign']);
  const sig = await crypto.subtle.sign('HMAC', key, counterBytes);
  const hash = new Uint8Array(sig);
  const offset = hash[hash.length - 1] & 0x0f;
  const code = (
    ((hash[offset] & 0x7f) << 24) |
    ((hash[offset + 1] & 0xff) << 16) |
    ((hash[offset + 2] & 0xff) << 8) |
    (hash[offset + 3] & 0xff)
  ) % 1000000;
  return code.toString().padStart(6, '0');
}
