import { test, expect } from '@playwright/test';
import { loginUI, loginToken, fakeIp } from './helpers';
import * as fs from 'fs';
import * as path from 'path';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || 'admin@sgplopypc.gob.mx';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'admin123';
const SHOTS = path.resolve(__dirname, '../diag-editor');

test.beforeAll(() => {
  fs.mkdirSync(SHOTS, { recursive: true });
});

function attachLogs(page: any, label: string, errors: string[]) {
  page.on('pageerror', (err: any) => errors.push(`${label} pageerror: ${err.message}`));
  page.on('console', (msg: any) => {
    if (msg.type() === 'error') {
      const t = msg.text();
      // ignorar errores ruidosos de CORS de fonts (test usa X-Forwarded-For)
      if (t.includes('CORS policy') || t.includes('Failed to load resource')) return;
      errors.push(`${label} console: ${t}`);
    }
  });
  page.on('requestfailed', (req: any) => {
    const u = req.url();
    if (u.includes('cdn.jsdelivr') || u.includes('gstatic') || u.includes('notificaciones/stream')) return;
    errors.push(`${label} reqfail: ${u} ${req.failure()?.errorText}`);
  });
}

test.describe('Diagnóstico editor de plantillas — flujos completos', () => {
  test.setTimeout(120000);

  test('1) crear nueva plantilla, cambiar estándar, guardar y recargar (debe persistir modo básico)', async ({ page }) => {
    const errors: string[] = [];
    attachLogs(page, '1', errors);
    page.on('dialog', async (d) => { await d.accept(); });

    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, /admin\/dashboard/, fakeIp());
    await page.goto('/frontend/admin/plantillas/editor.html');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `${SHOTS}/1a-nueva-inicial.png`, fullPage: true });

    expect(await page.locator('#modo-basico').isVisible()).toBe(true);
    expect(await page.locator('#modo-avanzado').isVisible()).toBe(false);

    // Cambiar a Acta de Fallo
    await page.locator('#f-estandar').selectOption('ACTA_FALLO');
    await page.waitForTimeout(400);
    expect(await page.locator('#b-secciones-list > div').count()).toBe(4);
    expect(await page.locator('#b-titulo').inputValue()).toBe('Acta de Fallo');
    await page.screenshot({ path: `${SHOTS}/1b-acta-fallo.png`, fullPage: true });

    // Llenar nombre y editar título
    const nombrePlantilla = 'Diag-1-' + Date.now();
    await page.locator('#f-nombre').fill(nombrePlantilla);
    await page.locator('#b-titulo').fill('ACTA DE FALLO MODIFICADA');

    // Editar contenido de la primera sección
    const primeraSeccion = page.locator('#b-secciones-list textarea[data-idx="0"]');
    await primeraSeccion.fill('CONTENIDO MODIFICADO {{licitante_ganador}}');
    await page.waitForTimeout(600);

    // Verificar que se sincroniza al HTML
    const htmlAntesGuardar = await page.locator('#f-html').inputValue();
    expect(htmlAntesGuardar.includes('SGPLOPYPC_BASICO_GENERATED')).toBe(true);
    expect(htmlAntesGuardar.includes('SGPLOPYPC_BASICO_STATE')).toBe(true);
    expect(htmlAntesGuardar.includes('CONTENIDO MODIFICADO')).toBe(true);
    expect(htmlAntesGuardar.includes('ACTA DE FALLO MODIFICADA')).toBe(true);

    // Guardar
    await page.locator('#btn-save').click();
    await page.waitForURL(/editor\.html\?id=\d+/, { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `${SHOTS}/1c-guardada.png`, fullPage: true });

    // Verificar que cargó en modo básico (porque tiene marker)
    expect(await page.locator('#modo-basico').isVisible()).toBe(true);
    expect(await page.locator('#modo-avanzado').isVisible()).toBe(false);
    expect(await page.locator('#b-titulo').inputValue()).toBe('ACTA DE FALLO MODIFICADA');
    const seccionesPostRecarga = await page.locator('#b-secciones-list > div').count();
    expect(seccionesPostRecarga).toBe(4);

    // Verificar que la sección modificada se restauró
    const primeraPostRecarga = await page.locator('#b-secciones-list textarea[data-idx="0"]').inputValue();
    expect(primeraPostRecarga).toBe('CONTENIDO MODIFICADO {{licitante_ganador}}');

    // Verificar que el form de assets está visible
    expect(await page.locator('#assets-card').isVisible()).toBe(true);
    expect(await page.locator('#form-asset').isVisible()).toBe(true);
    await page.screenshot({ path: `${SHOTS}/1d-recargada-basico.png`, fullPage: true });

    // Cleanup
    const url = page.url();
    const id = url.match(/id=(\d+)/)?.[1];
    if (id) {
      const token = await loginToken(page.request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
      await page.request.delete(`/api/v1/admin/plantillas/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
    }

    console.log('\n===== ERRORES TEST 1 =====');
    if (errors.length === 0) console.log('NINGUNO');
    else errors.forEach((e) => console.log('  -', e));
  });

  test('2) cambio Avanzado→Básico con confirm: aceptar regenera, cancelar mantiene', async ({ page, request }) => {
    const errors: string[] = [];
    attachLogs(page, '2', errors);

    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, /admin\/dashboard/, fakeIp());
    await page.goto('/frontend/admin/plantillas/editor.html');
    await page.waitForLoadState('networkidle');
    await page.locator('#f-estandar').selectOption('ACTA_FALLO');
    await page.waitForTimeout(400);

    // Capturar HTML básico generado
    const htmlBasico = await page.locator('#f-html').inputValue();
    expect(htmlBasico.length).toBeGreaterThan(2000);

    // Aceptar todos los dialogs por defecto
    let dialogCount = 0;
    let nextDialogAction: 'accept' | 'dismiss' = 'accept';
    page.on('dialog', async (d) => {
      dialogCount++;
      console.log(`  dialog #${dialogCount}: "${d.message().slice(0, 60)}..." → ${nextDialogAction}`);
      if (nextDialogAction === 'accept') await d.accept();
      else await d.dismiss();
    });

    // Ir a avanzado
    await page.locator('#tab-avanzado').click();
    await page.waitForTimeout(300);

    // Editar HTML manualmente
    const htmlManual = '<!DOCTYPE html><html><body><h1>HTML MANUAL EDITADO</h1></body></html>';
    await page.locator('#f-html').fill(htmlManual);
    await page.waitForTimeout(800);
    await page.screenshot({ path: `${SHOTS}/2a-avanzado-html-manual.png`, fullPage: true });

    // El warning debe aparecer porque el HTML no coincide con básico
    expect(await page.locator('#adv-warning').isVisible()).toBe(true);

    // Caso A: cancelar el confirm → debe quedarse en avanzado, HTML conservarse
    nextDialogAction = 'dismiss';
    await page.locator('#tab-basico').click();
    await page.waitForTimeout(500);
    expect(await page.locator('#modo-avanzado').isVisible()).toBe(true);
    expect(await page.locator('#f-html').inputValue()).toBe(htmlManual);
    await page.screenshot({ path: `${SHOTS}/2b-cancel-confirm-mantiene-avanzado.png`, fullPage: true });

    // Caso B: aceptar el confirm → debe ir a básico, HTML se regenera
    nextDialogAction = 'accept';
    await page.locator('#tab-basico').click();
    await page.waitForTimeout(500);
    expect(await page.locator('#modo-basico').isVisible()).toBe(true);
    const htmlPostVuelta = await page.locator('#f-html').inputValue();
    expect(htmlPostVuelta.includes('SGPLOPYPC_BASICO_GENERATED')).toBe(true);
    expect(htmlPostVuelta.includes('HTML MANUAL EDITADO')).toBe(false);
    await page.screenshot({ path: `${SHOTS}/2c-accept-confirm-regenera.png`, fullPage: true });

    console.log('\n===== ERRORES TEST 2 =====');
    if (errors.length === 0) console.log('NINGUNO');
    else errors.forEach((e) => console.log('  -', e));
  });

  test('3) editar plantilla predefinida — solo lectura, tabs siguen funcionando', async ({ page, request }) => {
    const errors: string[] = [];
    attachLogs(page, '3', errors);

    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    const list = await request.get('/api/v1/admin/plantillas?activa=1', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const items = (await list.json()).data.items;
    const predef = items.find((i: any) => i.es_predefinida && i.tipo === 'ACTA_FALLO');
    expect(predef).toBeTruthy();

    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, /admin\/dashboard/, fakeIp());
    await page.goto(`/frontend/admin/plantillas/editor.html?id=${predef.id_plantilla}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `${SHOTS}/3a-predefinida.png`, fullPage: true });

    // Botón guardar oculto
    expect(await page.locator('#btn-save').isVisible()).toBe(false);

    // Inputs de modo básico deshabilitados
    expect(await page.locator('#b-titulo').isDisabled()).toBe(true);
    expect(await page.locator('#f-html').isDisabled()).toBe(true);

    // Tabs deben seguir funcionando para visualizar
    expect(await page.locator('#tab-basico').isEnabled()).toBe(true);
    expect(await page.locator('#tab-avanzado').isEnabled()).toBe(true);

    await page.locator('#tab-avanzado').click();
    await page.waitForTimeout(300);
    expect(await page.locator('#modo-avanzado').isVisible()).toBe(true);
    const html = await page.locator('#f-html').inputValue();
    expect(html.length).toBeGreaterThan(50);
    await page.screenshot({ path: `${SHOTS}/3b-predefinida-avanzado.png`, fullPage: true });

    console.log('\n===== ERRORES TEST 3 =====');
    if (errors.length === 0) console.log('NINGUNO');
    else errors.forEach((e) => console.log('  -', e));
  });

  test('4) subir asset (logo) a una plantilla personalizada', async ({ page, request }) => {
    const errors: string[] = [];
    attachLogs(page, '4', errors);
    page.on('dialog', async (d) => { await d.accept(); });

    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());

    // Crear plantilla
    const create = await request.post('/api/v1/admin/plantillas', {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        nombre: 'Diag-asset-' + Date.now(),
        tipo: 'ACTA_FALLO',
        descripcion: 'Test subida asset',
        contenido_html: '<!DOCTYPE html><html><body><h1>{{numero_licitacion}}</h1></body></html>',
        variables_esperadas: 'numero_licitacion',
        activa: 1,
      },
    });
    expect(create.ok()).toBeTruthy();
    const created = (await create.json()).data;
    console.log('  plantilla creada:', created.id_plantilla);

    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, /admin\/dashboard/, fakeIp());
    await page.goto(`/frontend/admin/plantillas/editor.html?id=${created.id_plantilla}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    // Verificar que el form de asset existe y está visible
    expect(await page.locator('#assets-card').isVisible()).toBe(true);
    expect(await page.locator('#form-asset').isVisible()).toBe(true);

    // Crear archivo PNG temporal de prueba
    const tmpPath = '/tmp/test-logo-' + Date.now() + '.png';
    // PNG mínimo válido (1x1 transparente)
    const pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    fs.writeFileSync(tmpPath, Buffer.from(pngBase64, 'base64'));

    await page.locator('#asset-tipo').selectOption('LOGO');
    await page.locator('#asset-file').setInputFiles(tmpPath);
    await page.screenshot({ path: `${SHOTS}/4a-asset-pre-upload.png`, fullPage: true });

    // Capturar requests al backend
    const uploadPromise = page.waitForResponse((r) =>
      r.url().includes('/admin/plantillas/' + created.id_plantilla + '/assets') && r.request().method() === 'POST'
    );
    await page.locator('#form-asset button[type=submit]').click();
    const resp = await uploadPromise;
    console.log('  upload status:', resp.status());
    if (!resp.ok()) {
      console.log('  upload body:', await resp.text());
    }
    expect(resp.ok()).toBe(true);

    await page.waitForTimeout(2000);
    await page.screenshot({ path: `${SHOTS}/4b-asset-post-upload.png`, fullPage: true });

    // Verificar que el asset aparece en la lista
    const assetsHtml = await page.locator('#assets-list').innerHTML();
    expect(assetsHtml).toContain('LOGO');

    // Cleanup
    fs.unlinkSync(tmpPath);
    await request.delete(`/api/v1/admin/plantillas/${created.id_plantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    console.log('\n===== ERRORES TEST 4 =====');
    if (errors.length === 0) console.log('NINGUNO');
    else errors.forEach((e) => console.log('  -', e));
  });

  test('5) editar plantilla guardada en avanzado vuelve a cargar en avanzado', async ({ page, request }) => {
    const errors: string[] = [];
    attachLogs(page, '5', errors);

    const token = await loginToken(request, ADMIN_EMAIL, ADMIN_PASSWORD, fakeIp());
    const create = await request.post('/api/v1/admin/plantillas', {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        nombre: 'Diag-avanzado-' + Date.now(),
        tipo: 'PERSONALIZADA',
        contenido_html: '<!DOCTYPE html><html><body><h1>HTML SIN MARCADOR</h1></body></html>',
        variables_esperadas: null,
        activa: 1,
      },
    });
    expect(create.ok()).toBeTruthy();
    const created = (await create.json()).data;

    await loginUI(page, ADMIN_EMAIL, ADMIN_PASSWORD, /admin\/dashboard/, fakeIp());
    await page.goto(`/frontend/admin/plantillas/editor.html?id=${created.id_plantilla}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `${SHOTS}/5a-cargada-avanzado.png`, fullPage: true });

    // Debería cargar en avanzado porque no tiene marker
    expect(await page.locator('#modo-avanzado').isVisible()).toBe(true);
    expect(await page.locator('#modo-basico').isVisible()).toBe(false);
    const html = await page.locator('#f-html').inputValue();
    expect(html).toContain('HTML SIN MARCADOR');

    // El warning debe aparecer (HTML no coincide con básico)
    expect(await page.locator('#adv-warning').isVisible()).toBe(true);

    await request.delete(`/api/v1/admin/plantillas/${created.id_plantilla}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    console.log('\n===== ERRORES TEST 5 =====');
    if (errors.length === 0) console.log('NINGUNO');
    else errors.forEach((e) => console.log('  -', e));
  });
});
