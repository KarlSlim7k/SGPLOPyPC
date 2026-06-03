import { expect, test } from '@playwright/test';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

const pagesToCheck = [
  { url: '/frontend/publico/centro.html', role: 'PUBLICO', name: 'centro público' },
  { url: '/frontend/publico/perfil.html', role: 'PUBLICO', name: 'perfil público' },
  { url: '/frontend/publico/favoritos.html', role: 'PUBLICO', name: 'favoritos público' },
  { url: '/frontend/publico/datos-abiertos.html', role: 'PUBLICO', name: 'datos abiertos público' },
  { url: '/frontend/proveedor/centro.html', role: 'PROVEEDOR', name: 'centro proveedor' },
  { url: '/frontend/proveedor/contratos.html', role: 'PROVEEDOR', name: 'contratos proveedor' },
  { url: '/frontend/proveedor/perfil.html', role: 'PROVEEDOR', name: 'perfil proveedor' },
];

test.describe('Transversales calidad', () => {
  test('Tailwind no carga desde CDN en páginas principales', async ({ page, request }) => {
    const publicLogin = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json();
    const publicToken = publicLogin.data?.token;
    const publicUser = publicLogin.data?.usuario || {};

    const providerLogin = await (await request.post('/api/v1/auth/login', {
      data: { email: PROVIDER_EMAIL, password: PROVIDER_PASSWORD },
    })).json();
    const providerToken = providerLogin.data?.token;
    const providerUser = providerLogin.data?.usuario || {};

    for (const p of pagesToCheck) {
      const token = p.role === 'PUBLICO' ? publicToken : providerToken;
      const user = p.role === 'PUBLICO' ? publicUser : providerUser;
      await page.addInitScript(({ t, u }) => {
        localStorage.setItem('sgplopypc_token', t);
        localStorage.setItem('sgplopypc_user', JSON.stringify(u));
      }, { t: token, u: user });
      await page.goto(p.url);
      await page.reload();

      const html = await page.content();
      expect(html, `CDN tailwind en ${p.name}`).not.toContain('cdn.tailwindcss.com');
      expect(html, `Tailwind output CSS en ${p.name}`).toContain('/frontend/shared/tailwind-output.css');
    }
  });

  test('error-handler.js está presente en páginas de proveedor y público', async ({ page, request }) => {
    const login = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json();
    const token = login.data?.token;
    const user = login.data?.usuario || {};

    await page.addInitScript(({ t, u }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: token, u: user });
    await page.goto('/frontend/publico/centro.html');

    const html = await page.content();
    expect(html).toContain('error-handler.js');
  });

  test('empty state renderiza en página sin datos', async ({ page, request }) => {
    const login = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json();
    const token = login.data?.token;
    const user = login.data?.usuario || {};

    await page.addInitScript(({ t, u }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: token, u: user });
    await page.goto('/frontend/publico/favoritos.html');

    // Verificar que la página carga sin errores (lista o empty state)
    await expect(page.getByRole('heading', { name: /Mis licitaciones favoritas/i })).toBeVisible({ timeout: 15000 });
  });

  test('no hay errores de JS en consola al cargar páginas principales', async ({ page, request }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    const login = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json();
    const token = login.data?.token;
    const user = login.data?.usuario || {};

    await page.addInitScript(({ t, u }) => {
      localStorage.setItem('sgplopypc_token', t);
      localStorage.setItem('sgplopypc_user', JSON.stringify(u));
    }, { t: token, u: user });
    await page.goto('/frontend/publico/centro.html');
    await page.waitForLoadState('networkidle');

    await page.goto('/frontend/publico/datos-abiertos.html');
    await page.waitForLoadState('networkidle');

    const jsErrors = errors.filter(e => !e.includes('ResizeObserver') && !e.includes('gstatic'));
    expect(jsErrors, 'Errores JS: ' + jsErrors.join(', ')).toEqual([]);
  });
});
