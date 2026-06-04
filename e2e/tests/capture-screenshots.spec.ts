import { test, expect } from '@playwright/test';
import { loginUI } from './helpers';
import * as fs from 'fs';
import * as path from 'path';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';

const PROVIDER_EMAIL = process.env.E2E_PROVIDER_EMAIL || 'proveedor@demo.mx';
const PROVIDER_PASSWORD = process.env.E2E_PROVIDER_PASSWORD || 'proveedor123';

const PUBLICO_EMAIL = process.env.E2E_PUBLICO_EMAIL || 'publico@demo.mx';
const PUBLICO_PASSWORD = process.env.E2E_PUBLICO_PASSWORD || 'publico123';

test.describe('Capture manual user screenshots', () => {
  const outputDir = path.resolve(__dirname, '../../docs/guias/imagenes/manual_usuario');

  test.beforeAll(() => {
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }
  });

  test('Capture screenshots', async ({ page }) => {
    test.setTimeout(120000);
    // Set viewport size for consistent, premium screenshots
    await page.setViewportSize({ width: 1440, height: 900 });

    // 1. Landing Page
    console.log('Capturing Landing Page...');
    await page.goto('/');
    await page.waitForTimeout(3000); // Wait for content & animations
    await page.screenshot({ path: path.join(outputDir, 'landing.png') });

    // 2. Login Page
    console.log('Capturing Login Page...');
    await page.goto('/frontend/auth/login.html');
    await page.waitForTimeout(1500);
    await page.screenshot({ path: path.join(outputDir, 'login.png') });

    // 3. Admin Login & Screens
    console.log('Logging in as Admin...');
    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, '**/frontend/admin/dashboard.html');
    await page.waitForTimeout(4000); // Wait for charts and metrics to load
    await page.screenshot({ path: path.join(outputDir, 'dashboard-admin.png') });

    console.log('Capturing Admin Convocatorias...');
    await page.goto('/frontend/admin/convocatorias/index.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'convocatorias-admin.png') });

    console.log('Capturing Admin Evaluaciones...');
    await page.goto('/frontend/admin/evaluacion/index.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'evaluacion-admin.png') });

    // Logout Admin
    console.log('Logging out Admin...');
    await page.goto('/frontend/admin/dashboard.html');
    await page.waitForTimeout(1000);
    const logoutBtn = page.getByRole('button', { name: /cerrar sesi(?:ó|o)n/i }).first();
    if (await logoutBtn.isVisible()) {
      await logoutBtn.click();
      await page.waitForURL('**/frontend/auth/login.html');
    }

    // 4. Supplier (Proveedor) Login & Screens
    console.log('Logging in as Proveedor...');
    await loginUI(page, PROVIDER_EMAIL, PROVIDER_PASSWORD, '**/frontend/proveedor/centro.html');
    await page.waitForTimeout(4000); // Wait for charts
    await page.screenshot({ path: path.join(outputDir, 'dashboard-proveedor.png') });

    console.log('Capturing Proveedor Convocatorias List...');
    await page.goto('/frontend/proveedor/convocatorias.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'licitaciones-listado.png') });

    console.log('Capturing Proveedor Convocatoria Detail...');
    // We try to open a specific licitacion detail (id=1 usually exists, otherwise default)
    await page.goto('/frontend/proveedor/licitacion.html?id=1');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'detalle-licitacion.png') });

    console.log('Capturing Proveedor Participaciones...');
    await page.goto('/frontend/proveedor/participaciones.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'participaciones.png') });

    console.log('Capturing Proveedor Propuestas...');
    await page.goto('/frontend/proveedor/propuestas.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'propuestas.png') });

    console.log('Capturing Proveedor Documentos...');
    await page.goto('/frontend/proveedor/documentos.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'documentos.png') });

    console.log('Capturing Proveedor Contratos...');
    await page.goto('/frontend/proveedor/contratos.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'contratos.png') });

    console.log('Capturing Proveedor Perfil...');
    await page.goto('/frontend/proveedor/perfil.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'perfil-proveedor.png') });

    console.log('Capturing Proveedor Soporte...');
    await page.goto('/frontend/proveedor/soporte.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'soporte.png') });

    console.log('Capturing Proveedor Notificaciones...');
    await page.goto('/frontend/proveedor/notificaciones.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'notificaciones.png') });

    // Logout Proveedor
    console.log('Logging out Proveedor...');
    const provLogoutBtn = page.getByRole('button', { name: /cerrar sesi(?:ó|o)n/i }).first();
    if (await provLogoutBtn.isVisible()) {
      await provLogoutBtn.click();
      await page.waitForURL('**/frontend/auth/login.html');
    }

    // 5. Publico Login & Screens
    console.log('Logging in as Publico...');
    await loginUI(page, PUBLICO_EMAIL, PUBLICO_PASSWORD, '**/frontend/publico/centro.html');
    await page.waitForTimeout(4000);
    await page.screenshot({ path: path.join(outputDir, 'dashboard-publico.png') });

    console.log('Capturing Publico Datos Abiertos...');
    await page.goto('/frontend/publico/datos-abiertos.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'datos-abiertos.png') });

    console.log('Capturing Publico Favoritos...');
    await page.goto('/frontend/publico/favoritos.html');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(outputDir, 'favoritos-publico.png') });

    console.log('Screenshots captured successfully!');
  });
});
