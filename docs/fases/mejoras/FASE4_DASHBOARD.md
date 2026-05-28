# Fase 4 — Dashboard analítico con métricas

**Estado:** ✅ Completada — 2026-05-27
**Commits:**
- `5cd120cec1f0277bd65b4ab4b7fc7fcc9a2c86f6` — feat principal
- `3060035c0ce3bdc72ba728317e14785e141387f6` — chore gitignore cache
**Deployment Railway:** `1805d20d-36f8-44e0-860c-61d178207c1e`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Habilitar un panel de métricas operativas y de cumplimiento sobre el ciclo de contratación, con visualizaciones interactivas para administradores. Las consultas pesadas se sirven desde cache file-based con TTL para mantener latencia baja.

## 2. Cambios entregados

### 2.1 Backend

| Archivo | Responsabilidad |
|---|---|
| `app/repositories/MetricasRepository.php` | Queries SQL: tiempo de ciclo (DATEDIFF), top proveedores, montos mensuales (CTE recursivo MySQL 8 + LEFT JOIN para meses vacíos), cumplimiento (% a tiempo + distribución por estado), dependencias para filtros. |
| `app/helpers/SimpleCache.php` | Cache file-based con TTL: `remember`, `get`, `set`, `forget`, `flush`. Hash SHA-256 como filename, namespace por dominio. |
| `app/services/MetricasService.php` | Orquesta repo + cache. TTL 300s configurable via `METRICS_CACHE_TTL`. Formatea respuestas (procedimiento humano, label de mes, redondeos). |
| `app/controllers/MetricasController.php` | 6 endpoints HTTP, sanitización de filtros (`from`/`to` ISO-8601, `id_dependencia` int>0). |

#### Bug encontrado y corregido

PDO con `ATTR_EMULATE_PREPARES = false` (configuración por defecto del proyecto) **no permite reusar el mismo nombre de placeholder** múltiples veces en una query. El CTE recursivo de `montosMensuales` usaba `:from` y `:to` cuatro veces. Se renombraron a `from_cte`, `to_cte`, `from_lic`, `to_lic`, `from_adj`, `to_adj`, `id_dep_lic`, `id_dep_adj`.

### 2.2 Endpoints expuestos (todos `ADMINISTRADOR`)

| Método | Ruta | Filtros | Descripción |
|---|---|---|---|
| `GET` | `/api/v1/admin/metricas/tiempo-ciclo` | `from`, `to`, `id_dependencia` | Días promedio publicación → adjudicación, por tipo de procedimiento. |
| `GET` | `/api/v1/admin/metricas/proveedores-top` | `from`, `to`, `limit≤50` | Top N por monto adjudicado. |
| `GET` | `/api/v1/admin/metricas/montos-mensuales` | `from`, `to`, `id_dependencia` | Serie mensual: licitaciones creadas + contratos adjudicados + montos. |
| `GET` | `/api/v1/admin/metricas/cumplimiento` | `from`, `to`, `id_dependencia` | % de fallos emitidos dentro de fecha programada + distribución de licitaciones por estado. |
| `GET` | `/api/v1/admin/metricas/dependencias` | (sin filtros) | Lista de dependencias con conteo, para alimentar selectores. |
| `POST` | `/api/v1/admin/metricas/flush-cache` | — | Vacía la cache de métricas. |

#### Estrategia de cache

- **Namespace:** `metricas`
- **TTL:** 300s (5 min) configurable
- **Key:** `{base}:{md5(filtros_ordenados)}`
- **Storage:** `storage/cache/metricas/{sha256(key)}.json`
- **Flush:** endpoint dedicado + botón "Actualizar" en UI

### 2.3 Frontend

`frontend/admin/dashboard.html` ampliado con una sección **"Métricas analíticas"**:

- **Filtros:** rango de fechas (`from`, `to`) + select de dependencia.
- **4 KPIs:**
  - % de licitaciones a tiempo (verde)
  - Cantidad con atraso (ámbar)
  - Días de desviación promedio
  - Top proveedor adjudicado (nombre + monto + total contratos)
- **4 gráficas Chart.js 4.4.0** (CDN, sin build):
  - **Bar vertical** — Tiempo de ciclo por tipo de procedimiento (días)
  - **Line** — Montos adjudicados por mes (con tooltip MXN)
  - **Bar horizontal** — Top 10 proveedores adjudicados (truncamiento a 25 chars)
  - **Doughnut** — Distribución por estado (paleta de 8 colores)
- **Botón "Actualizar":** llama `POST /flush-cache` y recarga.
- **Status visual:** "Cargando..." → "Actualizado" (auto-oculta a los 2s) o "Error: ..."
- **Fallback "Sin datos":** cada gráfica muestra mensaje cuando la serie viene vacía.

### 2.4 Tests E2E

`e2e/tests/admin-dashboard-metricas.spec.ts` — **9 casos**:

1. ✅ `tiempo-ciclo` responde con `series[]` y `meta.cached_for_seconds`
2. ✅ `proveedores-top` respeta `limit` (1..50)
3. ✅ `montos-mensuales` devuelve ≥12 meses con shape correcto (mes, mes_label, conteos, monto)
4. ✅ `cumplimiento` devuelve `resumen` + `distribucion_estado`
5. ✅ `dependencias` retorna items con id/nombre/total_licitaciones
6. ✅ `flush-cache` devuelve `archivos_eliminados`
7. ✅ Proveedor recibe **403** en endpoints de métricas
8. ✅ Filtros `from`/`to` se reflejan en `meta.filtros`
9. ✅ UI dashboard renderiza heading + 4 títulos de gráficas tras la carga

## 3. Verificación en producción

```bash
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' | jq -r .data.token)

# Distribución por estado
curl -s "https://sgplopypc.up.railway.app/api/v1/admin/metricas/cumplimiento" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.distribucion_estado'
# → [{"estado_proceso":"EN_ACLARACIONES","total":1}, ...]

# Serie mensual
curl -s "https://sgplopypc.up.railway.app/api/v1/admin/metricas/montos-mensuales" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.series | length'
# → 12

# Cache flush
curl -s -X POST "https://sgplopypc.up.railway.app/api/v1/admin/metricas/flush-cache" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.archivos_eliminados'
```

## 4. Resultados E2E

```
Fase 4 (admin-dashboard-metricas):  9 passed
Smoke regresivo:
  Fase 1 (admin-auditoria):         5 passed
  Fase 2 (admin-plantillas):        6 passed
  Fase 2 (admin-reportes-export):   6 passed
  Fase 3 (datos-abiertos):         10 passed
                                  ─────────
TOTAL:                            36 passed / 0 failed
```

Sin regresiones de fases anteriores.

## 5. Estado de BD productiva

Al cierre de la fase, la BD productiva sólo tiene seeds:

- 3 licitaciones (`SEED-ACLARACIONES`, `SEED-RECEPCION`, `SEED-ADJUDICADA`)
- 0 contratos efectivos
- 0 participaciones registradas
- 4 dependencias (Salud, Educación, Comunicaciones, Hacienda)

Por eso `tiempoCiclo` y `proveedoresTop` devuelven series vacías; las gráficas muestran el fallback "Sin datos". El endpoint `montosMensuales` sí devuelve los 12 meses con cero montos pero el último mes muestra **3 licitaciones creadas**, validando que la lógica funciona.

## 6. Decisiones técnicas

### Cache file-based en lugar de Redis/Memcached
Mantenemos el stack mínimo (PHP + MySQL + Apache). Para el volumen actual del sistema, una cache file-based con TTL es suficiente y elimina dependencias operativas. Si la carga crece, el helper `SimpleCache` se puede extender a un backend Redis sin cambiar la API pública del servicio.

### Chart.js 4.4.0 vía CDN
Consistente con el resto del frontend (Tailwind, Phosphor también vía CDN). No introduce un pipeline de build. Versión específica para reproducibilidad.

### CTE recursivo para meses
Garantiza que la serie devuelve **todos** los meses del rango incluso sin datos (gráfica de línea sin huecos). Requiere MySQL 8+ (Railway usa 8.x).

### `storage/cache/` excluido de git
Los archivos `.json` de cache son artefactos runtime. Se agregaron a `.gitignore` después del primer commit accidental.

## 7. Próxima fase

Avanzar a **Fase 5 — Autenticación multifactor (MFA/2FA)** según `docs/fases/mejoras/FASES_MEJORAS.md`.

---

## Anexo — Plantilla de cierre

```text
Commits:       5cd120c (feat) → 3060035 (chore gitignore)
HEAD final:    3060035c0ce3bdc72ba728317e14785e141387f6
Deployment:    1805d20d-36f8-44e0-860c-61d178207c1e
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 4:    9 passed / 0 failed
E2E regresión: 27 passed / 0 failed (Fases 1, 2, 3)
Endpoints:     GET  /admin/metricas/tiempo-ciclo
               GET  /admin/metricas/proveedores-top
               GET  /admin/metricas/montos-mensuales
               GET  /admin/metricas/cumplimiento
               GET  /admin/metricas/dependencias
               POST /admin/metricas/flush-cache
Frontend:      4 gráficas Chart.js + 4 KPIs + filtros + botón Actualizar
Cache:         File-based, TTL 300s, namespace 'metricas'
```
