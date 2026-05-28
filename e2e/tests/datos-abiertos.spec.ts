import { expect, test } from '@playwright/test';
import { fakeIp, rlHeaders } from './helpers';

test.describe('API pública de datos abiertos — OCDS 1.1', () => {
  test('GET /datos-abiertos/releases responde 200 sin auth', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/releases?limit=5', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok(), `releases falló: ${res.status()} ${await res.text()}`).toBeTruthy();
    expect(res.headers()['access-control-allow-origin']).toBe('*');
    expect(res.headers()['content-type']).toContain('application/json');

    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.data.standard).toBe('OCDS 1.1');
    expect(Array.isArray(body.data.releases)).toBe(true);
    expect(body.data.pagination.page).toBe(1);
    expect(body.data.pagination.limit).toBe(5);
    expect(body.data.license).toContain('creativecommons');
  });

  test('cada release tiene campos OCDS obligatorios', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/releases?limit=10', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok()).toBeTruthy();
    const releases = (await res.json()).data.releases;
    expect(releases.length).toBeGreaterThan(0);

    for (const r of releases) {
      // Campos OCDS obligatorios en un release
      expect(r.ocid).toMatch(/^ocds-sgplopypc-/);
      expect(r.id).toContain(r.ocid);
      expect(typeof r.date).toBe('string');
      expect(Array.isArray(r.tag)).toBe(true);
      expect(r.tag.length).toBeGreaterThan(0);
      expect(r.initiationType).toBe('tender');
      expect(r.language).toBe('es');
      expect(Array.isArray(r.parties)).toBe(true);
      expect(r.buyer).toBeDefined();
      expect(r.buyer.id).toMatch(/^buyer-/);
      expect(r.tender).toBeDefined();
      expect(['planning', 'active', 'cancelled', 'unsuccessful', 'complete', 'withdrawn']).toContain(r.tender.status);
      expect(['open', 'selective', 'limited', 'direct']).toContain(r.tender.procurementMethod);
      expect(r.tender.value.currency).toBe('MXN');
    }
  });

  test('GET /datos-abiertos/releases/{ocid} devuelve un release específico (OCDS puro)', async ({ request }) => {
    // Primero obtener un OCID conocido
    const list = await request.get('/api/v1/datos-abiertos/releases?limit=1', {
      headers: rlHeaders(fakeIp()),
    });
    const releases = (await list.json()).data.releases;
    expect(releases.length).toBeGreaterThan(0);
    const ocid = releases[0].ocid;

    const res = await request.get(`/api/v1/datos-abiertos/releases/${encodeURIComponent(ocid)}`, {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok(), `single release falló: ${res.status()} ${await res.text()}`).toBeTruthy();
    expect(res.headers()['access-control-allow-origin']).toBe('*');

    const body = await res.json();
    // Estructura OCDS pura, NO está envuelto en {success, message, data}
    expect(body.ocid).toBe(ocid);
    expect(body.tender).toBeDefined();
    expect(body.parties).toBeDefined();
    expect(body.buyer).toBeDefined();
    // No debe tener clave 'success' (es OCDS puro)
    expect(body.success).toBeUndefined();
  });

  test('GET /datos-abiertos/releases/{ocid} con OCID inexistente devuelve 404', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/releases/ocds-sgplopypc-NO-EXISTE-9999', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.status()).toBe(404);
    expect(res.headers()['access-control-allow-origin']).toBe('*');
    const body = await res.json();
    expect(body.error).toBe('not_found');
  });

  test('GET /datos-abiertos/release-package devuelve un Release Package OCDS válido', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/release-package', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok()).toBeTruthy();
    expect(res.headers()['access-control-allow-origin']).toBe('*');

    const pkg = await res.json();
    expect(pkg.version).toBe('1.1');
    expect(pkg.uri).toContain('release-package');
    expect(pkg.publisher.name).toBe('SGPLOPyPC');
    expect(pkg.publisher.scheme).toBe('MX-GOB');
    expect(pkg.license).toContain('creativecommons');
    expect(pkg.publicationPolicy).toBeTruthy();
    expect(Array.isArray(pkg.releases)).toBe(true);
    expect(pkg.publishedDate).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
  });

  test('?download=1 en release-package fuerza descarga', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/release-package?download=1', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok()).toBeTruthy();
    const cd = res.headers()['content-disposition'] || '';
    expect(cd).toContain('attachment');
    expect(cd).toContain('sgplopypc-ocds-');
    expect(cd).toContain('.json');
  });

  test('CORS preflight (OPTIONS) responde 204 con headers correctos', async ({ request }) => {
    const res = await request.fetch('/api/v1/datos-abiertos/releases', {
      method: 'OPTIONS',
      headers: { 'Origin': 'https://example.com' },
    });
    expect(res.status()).toBe(204);
    expect(res.headers()['access-control-allow-origin']).toBe('*');
    expect(res.headers()['access-control-allow-methods']).toContain('GET');
  });

  test('borradores NO son visibles en datos abiertos', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/releases?estado=BORRADOR', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    // El filtro 'BORRADOR' no es válido (no está en allow-list), así que se ignora;
    // pero los releases que devuelve nunca deben tener tender.status=planning
    for (const r of body.data.releases) {
      expect(r.tender.status).not.toBe('planning');
      expect(r.tag).not.toContain('planning');
    }
  });

  test('filtro estado=ADJUDICADA devuelve sólo releases adjudicados', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/releases?estado=ADJUDICADA', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    for (const r of body.data.releases) {
      expect(r.tender.status).toBe('complete');
      expect(r.tag).toContain('award');
    }
  });

  test('cabeceras de cache y seguridad presentes', async ({ request }) => {
    const res = await request.get('/api/v1/datos-abiertos/releases?limit=1', {
      headers: rlHeaders(fakeIp()),
    });
    expect(res.ok()).toBeTruthy();
    expect(res.headers()['cache-control']).toContain('max-age=300');
    expect(res.headers()['x-content-type-options']).toBe('nosniff');
  });
});
