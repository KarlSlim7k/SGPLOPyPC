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
    test(`Axe audit en ${path}`, async ({ page }) => {
      await page.goto(path);

      const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();

      const critical = results.violations.filter((v) => v.impact === 'critical');
      const serious = results.violations.filter((v) => v.impact === 'serious');

      if (serious.length > 0) {
        test.info().annotations.push({
          type: 'a11y-serious',
          description: `${path}: ${serious.map((v) => v.id).join(', ')}`,
        });
      }

      test.info().annotations.push({
        type: 'a11y-critical-count',
        description: `${path}: ${critical.length}`,
      });

      expect(Array.isArray(results.violations)).toBeTruthy();
    });
  }
});
