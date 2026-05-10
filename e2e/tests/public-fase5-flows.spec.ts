import { expect, test, type APIRequestContext } from '@playwright/test';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';

async function loginToken(request: APIRequestContext, email: string, password: string): Promise<string> {
  for (let attempt = 0; attempt < 3; attempt += 1) {
    const response = await request.post('/api/v1/auth/login', { data: { email, password } });
    if (response.status() === 429 && attempt < 2) {
      await new Promise((r) => setTimeout(r, 2_000));
      continue;
    }
    expect(response.ok(), `login ${email} -> ${response.status()}`).toBeTruthy();
    const payload = await response.json();
    expect(payload?.data?.token).toBeTruthy();
    return payload.data.token as string;
  }
  throw new Error(`No se pudo autenticar a ${email}`);
}

test.describe('Fase 5 público: registro, navegación y rate limit', () => {
  test.describe.configure({ mode: 'serial' });

  test('UI: navegación pública a evaluación, historial, contratos y resultados', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { name: /gestión de/i })).toBeVisible();

    await page.goto('/evaluacion.php');
    await expect(page.getByRole('heading', { name: /procesos en evaluación/i })).toBeVisible();

    await page.goto('/historial.php');
    await expect(page.getByRole('heading', { name: /historial de licitaciones/i })).toBeVisible();

    await page.goto('/contratos.php');
    await expect(page.getByRole('heading', { name: /contratos adjudicados/i })).toBeVisible();

    await page.goto('/resultados.php');
    await expect(page.getByRole('heading', { name: /resultados de adjudicación/i })).toBeVisible();
  });

  test('UI: flujo completo de registro público de proveedor y login posterior', async ({ page, request }) => {
    const uid = Date.now();
    const email = `e2e.fase5.publico.${uid}@example.com`;
    const password = 'Fase5Publico1!';

    await page.goto('/registro.php');
    await page.locator('#razon-social').fill(`Proveedor Fase5 ${uid} SA de CV`);
    await page.locator('#rfc').fill(`F5P${uid}RFC`);
    await page.locator('#regimen').selectOption({ index: 1 });
    await page.locator('#domicilio-fiscal').fill(`Calle Fase5 ${uid}, Ciudad`);
    await page.locator('#nombre-contacto').fill(`Contacto Fase5 ${uid}`);
    await page.locator('#cargo').fill('Representante Legal');
    await page.locator('#email').fill(email);
    await page.locator('#telefono').fill('5551234567');
    await page.locator('#password').fill(password);
    await page.locator('input[name="especialidad[]"]').first().check();
    await page.locator('#terms').check();

    await page.locator('#registro-submit').click();
    await expect(page.locator('#registro-status')).toContainText(/registro completado/i);
    await page.waitForURL('**/frontend/auth/login.html', { timeout: 20_000 });

    // El proveedor recién creado debe poder autenticarse y acceder a su centro.
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();
    await page.waitForURL('**/frontend/proveedor/centro.html', { timeout: 20_000 });
    await expect(page.getByRole('heading', { name: /bienvenido/i })).toBeVisible();

    // Sanity API: token funcional para /me
    const token = await loginToken(request, email, password);
    const meRes = await request.get('/api/v1/me', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(meRes.status()).toBe(200);
  });

  test('API: endpoints públicos de lectura aplican rate limiting (HTTP 429)', async ({ request }) => {
    // Tolerancia si otro test dejó la IP en ventana activa.
    const first = await request.get('/api/v1/public/estadisticas');
    expect([200, 429]).toContain(first.status());

    let got429 = first.status() === 429;
    for (let i = 0; i < 30 && !got429; i += 1) {
      const res = await request.get('/api/v1/public/estadisticas');
      if (res.status() === 429) {
        got429 = true;
        const body = await res.json();
        expect(String(body?.message || '').toLowerCase()).toContain('demasiadas solicitudes');
        break;
      }
      expect(res.status(), `iteración ${i + 1}`).toBe(200);
    }

    expect(got429, 'debe alcanzarse 429 al exceder la ventana de lectura pública').toBeTruthy();
  });

  test('API: rol PUBLICO autenticado mantiene acceso de solo lectura pública', async ({ request }) => {
    const token = await loginToken(request, PUBLIC_EMAIL, PUBLIC_PASSWORD);

    const allowed = await request.get('/api/v1/public/convocatorias?limit=2', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(allowed.status()).toBe(200);

    const denied = await request.get('/api/v1/proveedores?page=1&limit=2', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(denied.status()).toBe(403);
  });
});
