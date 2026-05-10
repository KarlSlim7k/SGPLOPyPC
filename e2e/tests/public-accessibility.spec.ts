import { expect, test } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const PAGES = [
  '/',
  '/evaluacion.php',
  '/historial.php',
  '/contratos.php',
  '/resultados.php',
  '/registro.php',
];

test.describe('Accesibilidad pública WCAG 2.1 AA', () => {
  for (const path of PAGES) {
    test(`Axe sin violaciones críticas/serias en ${path}`, async ({ page }) => {
      await page.goto(path);

      const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();

      const criticalOrSerious = results.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
      expect(criticalOrSerious, JSON.stringify(criticalOrSerious, null, 2)).toEqual([]);
    });
  }
});
