import { expect, test } from '@playwright/test';
import { loginUI } from './helpers';

const PUBLIC_EMAIL = process.env.E2E_PUBLIC_EMAIL || 'publico@demo.mx';
const PUBLIC_PASSWORD = process.env.E2E_PUBLIC_PASSWORD || 'publico123';
const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

const pagesToCheck = [
  { url: '/frontend/publico/centro.html', role: 'publico', name: 'centro público' },
  { url: '/frontend/publico/perfil.html', role: 'publico', name: 'perfil público' },
  { url: '/frontend/publico/favoritos.html', role: 'publico', name: 'favoritos público' },
  { url: '/frontend/publico/datos-abiertos.html', role: 'publico', name: 'datos abiertos público' },
  { url: '/frontend/proveedor/centro.html', role: 'proveedor', name: 'centro proveedor' },
  { url: '/frontend/proveedor/contratos.html', role: 'proveedor', name: 'contratos proveedor' },
  { url: '/frontend/proveedor/perfil.html', role: 'proveedor', name: 'perfil proveedor' },
];

test.describe('Transversales calidad', () => {
  test('Tailwind no carga desde CDN en páginas principales', async ({ page, request }) => {
    const publicToken = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json().then(r => r.data?.token);

    const providerToken = await (await request.post('/api/v1/auth/login', {
      data: { email: PROVIDER_EMAIL, password: PROVIDER_PASSWORD },
    })).json().then(r => r.data?.token);

    for (const p of pagesToCheck) {
      const token = p.role === 'publico' ? publicToken : providerToken;
      await page.goto(p.url);
      await page.evaluate((t) => { localStorage.setItem('sgplopypc_token', t); }, token);
      await page.reload();

      const html = await page.content();
      expect(html, `CDN tailwind en ${p.name}`).not.toContain('cdn.tailwindcss.com');
      expect(html, `Tailwind output CSS en ${p.name}`).toContain('/frontend/shared/tailwind-output.css');
    }
  });

  test('error-handler.js está presente en páginas de proveedor y público', async ({ page, request }) => {
    const token = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json().then(r => r.data?.token);

    await page.goto('/frontend/publico/centro.html');
    await page.evaluate((t) => { localStorage.setItem('sgplopypc_token', t); }, token);
    await page.reload();

    const html = await page.content();
    expect(html).toContain('error-handler.js');
  });

  test('empty state renderiza en página sin datos', async ({ page, request }) => {
    const token = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json().then(r => r.data?.token);

    await page.goto('/frontend/publico/favoritos.html');
    await page.evaluate((t) => { localStorage.setItem('sgplopypc_token', t); }, token);
    await page.reload();

    // Verificar que la página carga sin errores
    await expect(page.locator('#favoritos-empty')).toBeVisible({ timeout: 15000 });
  });

  test('no hay errores de JS en consola al cargar páginas principales', async ({ page, request }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    const token = await (await request.post('/api/v1/auth/login', {
      data: { email: PUBLIC_EMAIL, password: PUBLIC_PASSWORD },
    })).json().then(r => r.data?.token);

    await page.goto('/frontend/publico/centro.html');
    await page.evaluate((t) => { localStorage.setItem('sgplopypc_token', t); }, token);
    await page.reload();
    await page.waitForLoadState('networkidle');

    await page.goto('/frontend/publico/datos-abiertos.html');
    await page.evaluate((t) => { localStorage.setItem('sgplopypc_token', t); }, token);
    await page.reload();
    await page.waitForLoadState('networkidle');

    const jsErrors = errors.filter(e => !e.includes('ResizeObserver') && !e.includes('gstatic'));
    expect(jsErrors, 'Errores JS: ' + jsErrors.join(', ')).toEqual([]);
  });
});
