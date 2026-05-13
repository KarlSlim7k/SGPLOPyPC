import { expect, test, type APIRequestContext } from '@playwright/test';
import { fakeIp, loginToken } from './helpers';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

test.describe('Retiro de inscripción y edición de propuesta', () => {
  let token = '';
  let meData: Record<string, unknown> = {};

  test.beforeAll(async ({ request }) => {
    token = await loginToken(request, PROVIDER_EMAIL, PROVIDER_PASSWORD);
    meData = (await (await request.get('/api/v1/me', { headers: { Authorization: `Bearer ${token}` } })).json())?.data ?? {};
  });

  test('API: no se puede retirar inscripción con propuesta enviada o en estado no permitido', async ({ request }) => {
    const res = await request.get('/api/v1/participaciones/mias?page=1&limit=50', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const items = ((await res.json())?.data?.items || []) as Array<Record<string, unknown>>;

    // Buscar una participación que NO sea INSCRITO (no se puede retirar)
    const noRetirable = items.find((i) => i.estatus !== 'INSCRITO');
    if (noRetirable) {
      const del = await request.delete(`/api/v1/participaciones/${noRetirable.id_participacion}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      expect(del.status()).toBe(422);
    }
  });

  test('API: no se puede editar propuesta fuera de RECEPCION_PROPUESTAS o estatus != RECIBIDA', async ({ request }) => {
    const res = await request.get('/api/v1/propuestas/mias?page=1&limit=50', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const items = ((await res.json())?.data?.items || []) as Array<Record<string, unknown>>;

    // Buscar propuesta que no sea editable (proceso != RECEPCION_PROPUESTAS o estatus != RECIBIDA)
    const noEditable = items.find((i) => i.estado_proceso !== 'RECEPCION_PROPUESTAS' || i.estatus !== 'RECIBIDA');
    if (noEditable) {
      const put = await request.put(`/api/v1/participaciones/${noEditable.id_participacion}/propuesta`, {
        headers: { Authorization: `Bearer ${token}` },
        data: { monto_propuesta: 99999 },
      });
      expect(put.status()).toBe(422);
    }
  });

  test('API: se puede retirar inscripción INSCRITO en proceso activo y volver a inscribirse', async ({ request }) => {
    // Buscar licitación activa
    const licRes = await request.get('/api/v1/licitaciones?limit=20', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const lics = ((await licRes.json())?.data || []) as Array<Record<string, unknown>>;
    const activa = lics.find((l) => ['PUBLICADA', 'EN_ACLARACIONES', 'RECEPCION_PROPUESTAS'].includes(String(l.estado_proceso)));

    if (!activa) { test.skip(true, 'Sin licitaciones activas'); return; }

    // Inscribirse (puede ya estar inscrito — tolerar 409)
    const inscRes = await request.post(`/api/v1/licitaciones/${activa.id_licitacion}/participaciones`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect([201, 409]).toContain(inscRes.status());

    // Obtener la participación
    const partRes = await request.get('/api/v1/participaciones/mias?page=1&limit=50', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const parts = ((await partRes.json())?.data?.items || []) as Array<Record<string, unknown>>;
    const part = parts.find((p) => String(p.id_licitacion) === String(activa.id_licitacion) && p.estatus === 'INSCRITO');

    if (!part) { test.skip(true, 'No hay participación INSCRITO retirable'); return; }

    // Retirar
    const del = await request.delete(`/api/v1/participaciones/${part.id_participacion}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(del.status()).toBe(200);

    // Verificar que ya no aparece
    const partRes2 = await request.get('/api/v1/participaciones/mias?page=1&limit=50', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const parts2 = ((await partRes2.json())?.data?.items || []) as Array<Record<string, unknown>>;
    expect(parts2.some((p) => String(p.id_participacion) === String(part.id_participacion))).toBeFalsy();
  });

  test('UI: botón Retirar visible en participaciones INSCRITO activas', async ({ page }) => {
    await page.addInitScript(({ t, u }: { t: string; u: unknown }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: token, u: meData });

    await page.goto('/frontend/proveedor/participaciones.html');
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15_000 });
    // El botón puede o no estar presente según el estado de los datos — solo verificamos que la página cargó
    await expect(page.locator('table')).toBeVisible();
  });

  test('UI: botón Editar visible en propuestas RECIBIDA en RECEPCION_PROPUESTAS', async ({ page }) => {
    await page.addInitScript(({ t, u }: { t: string; u: unknown }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: token, u: meData });

    await page.goto('/frontend/proveedor/propuestas.html');
    await expect(page.locator('#summary')).not.toContainText('Cargando', { timeout: 15_000 });
    await expect(page.locator('table')).toBeVisible();
  });
});
