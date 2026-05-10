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
| `/` | ninguno | `color-contrast` |
| `/evaluacion.php` | ninguno | `color-contrast` |
| `/historial.php` | `select-name` | `color-contrast` |
| `/contratos.php` | `select-name` | `color-contrast` |
| `/resultados.php` | ninguno | ninguno |
| `/registro.php` | `label` | `color-contrast`, `link-in-text-block` |

## Hallazgos clave
1. `select-name` (critical): filtros `select` sin nombre accesible en historial y contratos.
2. `label` (critical): inputs de archivo sin etiqueta accesible en registro.
3. `color-contrast` (serious): combinaciones de color con contraste insuficiente en varias vistas.
4. `link-in-text-block` (serious): enlaces de texto no distinguibles por algo más que color en registro.

## Estado
- Se agregó prueba E2E de auditoría: `e2e/tests/public-accessibility.spec.ts`.
- La prueba corre en CI como auditoría y no bloquea despliegue.

## Remediación recomendada
1. Agregar `<label for="...">` o `aria-label` a todos los `select` e `input type="file"`.
2. Ajustar tokens de color para cumplir contraste mínimo AA.
3. Subrayar o reforzar estado visual de enlaces dentro de bloques de texto.
