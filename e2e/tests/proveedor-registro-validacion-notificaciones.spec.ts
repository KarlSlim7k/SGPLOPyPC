import { expect, test, type APIRequestContext } from '@playwright/test';
import { fakeIp, loginToken, rlHeaders } from './helpers';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

test.describe('Bloque D: registro → validación → login + notificaciones', () => {
  const uid = Date.now();
  const ip = fakeIp();
  const newEmail = `e2e.bloque.d.${uid}@example.com`;
  const newPassword = 'BloqueDTest1!';
  let newProveedorId = 0;
  let adminToken = '';
  let providerToken = '';
  let providerMe: Record<string, unknown> = {};

  test.beforeAll(async ({ request }) => {
    adminToken = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
  });

  test('API: registro público crea proveedor en PENDIENTE', async ({ request }) => {
    const res = await request.post('/api/v1/public/proveedores/registro', {
      data: {
        nombre_empresa: `Empresa E2E BloqueD ${uid} SA de CV`,
        representante_legal: `Rep BloqueD ${uid}`,
        registro_fiscal: `BD${uid}RFC`,
        regimen_fiscal: '601',
        domicilio: `Calle BloqueD ${uid}, Ciudad`,
        nombre_contacto: `Contacto BloqueD ${uid}`,
        cargo: 'Director',
        email: newEmail,
        telefono: '5551234567',
        password: newPassword,
        accepted_terms: true,
      },
      headers: rlHeaders(ip),
    });
    expect(res.status()).toBe(201);
    const payload = await res.json();
    expect(payload?.data?.token).toBeTruthy();
    providerToken = payload.data.token;

    const meRes = await request.get('/api/v1/me', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    const me = (await meRes.json())?.data;
    expect(me?.rol).toBe('PROVEEDOR');
    expect(me?.proveedor?.estatus).toBe('PENDIENTE');
    newProveedorId = me?.id_proveedor || me?.proveedor?.id_proveedor;
    expect(newProveedorId).toBeTruthy();
  });

  test('API: proveedor PENDIENTE no puede inscribirse en licitaciones', async ({ request }) => {
    const token = providerToken;
    const licRes = await request.get('/api/v1/licitaciones?limit=5', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;
    const activa = lics.find((l) => ['PUBLICADA', 'EN_ACLARACIONES', 'RECEPCION_PROPUESTAS'].includes(String(l.estado_proceso)));
    if (!activa) { test.skip(true, 'Sin licitaciones activas'); return; }

    const inscRes = await request.post(`/api/v1/licitaciones/${activa.id_licitacion}/participaciones`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(inscRes.status()).toBe(422);
    const msg = ((await inscRes.json())?.errors || []).join(' ').toLowerCase();
    expect(msg).toContain('validado');
  });

  test('API: admin valida el proveedor → estatus VALIDADO', async ({ request }) => {
    expect(newProveedorId, 'id_proveedor debe estar disponible del test anterior').toBeTruthy();

    const patch = await request.patch(`/api/v1/proveedores/${newProveedorId}/estatus`, {
      headers: { Authorization: `Bearer ${adminToken}` },
      data: { estatus: 'VALIDADO' },
    });
    expect(patch.status()).toBe(200);

    providerToken = await loginToken(request, newEmail, newPassword, fakeIp());
    const meRes = await request.get('/api/v1/me', {
      headers: { Authorization: `Bearer ${providerToken}` },
    });
    providerMe = (await meRes.json())?.data ?? {};
    expect(providerMe?.proveedor?.estatus).toBe('VALIDADO');
  });

  test('UI: login del proveedor recién validado redirige a centro', async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'X-Forwarded-For': fakeIp() });
    await page.goto('/frontend/auth/login.html');
    await page.locator('#email').fill(newEmail);
    await page.locator('#password').fill(newPassword);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();
    await page.waitForURL('**/frontend/proveedor/centro.html', { timeout: 20_000 });
    await expect(page.getByRole('heading', { name: /bienvenido/i })).toBeVisible();

    const badge = page.getByTestId('estatus-proveedor');
    await expect(badge).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(badge).toHaveAttribute('data-estatus', 'VALIDADO');
  });

  test('API: admin puede enviar notificación al proveedor', async ({ request }) => {
    expect(newProveedorId, 'id_proveedor debe estar disponible').toBeTruthy();

    const provRes = await request.get(`/api/v1/proveedores/${newProveedorId}`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    const prov = (await provRes.json())?.data;
    const idUsuario = prov?.id_usuario;
    expect(idUsuario).toBeTruthy();

    const notifRes = await request.post('/api/v1/notificaciones', {
      headers: { Authorization: `Bearer ${adminToken}` },
      data: {
        id_usuario_destino: idUsuario,
        tipo_notificacion: 'GENERAL',
        titulo: `Bienvenido E2E ${uid}`,
        mensaje: 'Tu perfil ha sido validado. Ya puedes participar en licitaciones.',
      },
    });
    expect([201, 200]).toContain(notifRes.status());
  });

  test('API: proveedor lista notificaciones y puede marcar como leída', async ({ request }) => {
    const token = providerToken || await loginToken(request, newEmail, newPassword, fakeIp());

    const listRes = await request.get('/api/v1/notificaciones/mias', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(listRes.status()).toBe(200);
    const items = ((await listRes.json())?.data || []) as Array<Record<string, unknown>>;
    expect(items.length).toBeGreaterThan(0);

    const noLeida = items.find((n) => !n.leida);
    if (noLeida) {
      const patch = await request.patch(`/api/v1/notificaciones/${noLeida.id_notificacion}/leida`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      expect(patch.status()).toBe(200);

      const listRes2 = await request.get('/api/v1/notificaciones/mias', {
        headers: { Authorization: `Bearer ${token}` },
      });
      const items2 = ((await listRes2.json())?.data || []) as Array<Record<string, unknown>>;
      const marcada = items2.find((n) => String(n.id_notificacion) === String(noLeida.id_notificacion));
      expect(marcada?.leida).toBeTruthy();
    }
  });

  test('UI: página de notificaciones carga y muestra bandeja', async ({ page }) => {
    expect(providerToken, 'providerToken debe estar disponible').toBeTruthy();

    await page.addInitScript(({ t, u }: { t: string; u: unknown }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: providerToken, u: providerMe });

    await page.goto('/frontend/proveedor/notificaciones.html');
    await expect(page.getByRole('heading', { name: /Mis notificaciones/i })).toBeVisible();
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('#rows')).not.toContainText('Cargando notificaciones');
    await expect(page.locator('#stat-total')).not.toHaveText('0');
  });
});
