# Fase 5 — Verificación de accesibilidad (WCAG 2.1 AA)

Fecha de ejecución: 2026-05-10  
Herramienta: `@axe-core/playwright`

## Páginas auditadas
- `/`
- `/evaluacion.php`
- `/historial.php`
- `/contratos.php`
- `/resultados.php`
- `/registro.php`

## Resultado por página

| Página | Critical | Serious |
|---|---|---|
| `/` | ninguno | ninguno |
| `/evaluacion.php` | ninguno | ninguno |
| `/historial.php` | ninguno | ninguno |
| `/contratos.php` | ninguno | ninguno |
| `/resultados.php` | ninguno | ninguno |
| `/registro.php` | ninguno | ninguno |

## Correcciones aplicadas
1. `select-name`: filtros `select` de historial y contratos tienen etiqueta accesible.
2. `label`: inputs de archivo en registro tienen etiquetas y descripciones asociadas.
3. `color-contrast`: se agregó `frontend/shared/public-accessibility.css` para reforzar contraste en vistas públicas.
4. `link-in-text-block`: enlaces legales de registro tienen subrayado visible.

## Estado
- La prueba E2E `e2e/tests/public-accessibility.spec.ts` corre como gate estricto.
- El gate falla ante cualquier violación `critical` o `serious` de Axe.
- Resultado verificado contra producción Railway: 6/6 passed.

## Comando verificado

```bash
cd e2e && E2E_BASE_URL='https://sgplopypc-production.up.railway.app' \
  npx playwright test tests/public-accessibility.spec.ts --reporter=list
```
