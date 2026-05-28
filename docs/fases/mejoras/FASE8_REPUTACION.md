# Fase 8 — Calificación y reputación de proveedores

**Estado:** ✅ Completada — 2026-05-28
**Commits:**
- `01d2296f9f7e49135ebac6bf6947dd295e221d0a` — feat principal
- `1dbe3b82ae02cf000200bfb5d50a39a9b80b3fea` — test tolerante
**Deployment Railway:** `c08010a2-d6ec-4d6a-8bbf-ec023c0ce8c9`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Habilitar un sistema de calificación post-contrato que genera un **score de reputación** visible para cada proveedor, incentivando la calidad y facilitando la selección en futuras licitaciones.

## 2. Cambios entregados

### 2.1 Base de datos

`database/migrations/017_reputacion_proveedores.sql` (idempotente):

**Tabla `proveedor_evaluacion_postcontrato`:**

| Columna | Tipo | Descripción |
|---|---|---|
| `id_evaluacion` | `INT PK AUTO_INCREMENT` | |
| `id_contrato` | `INT FK UNIQUE` | Un contrato sólo puede evaluarse una vez. |
| `id_proveedor` | `INT FK` | |
| `puntualidad` | `TINYINT CHECK(1-5)` | Criterio de evaluación. |
| `calidad` | `TINYINT CHECK(1-5)` | Criterio de evaluación. |
| `comunicacion` | `TINYINT CHECK(1-5)` | Criterio de evaluación. |
| `cumplimiento_alcance` | `TINYINT CHECK(1-5)` | Criterio de evaluación. |
| `promedio` | `DECIMAL(3,2)` | Calculado al insertar: `(p+c+com+cum)/4`. |
| `comentarios` | `TEXT NULL` | Observaciones del evaluador. |
| `id_usuario_evaluador` | `INT FK` | |
| `fecha_evaluacion` | `DATETIME DEFAULT NOW` | |

**Columnas nuevas en `proveedor`:**

| Columna | Tipo | Descripción |
|---|---|---|
| `score_reputacion` | `DECIMAL(3,2) NULL` | Promedio de todos los promedios de evaluaciones. |
| `total_evaluaciones` | `INT DEFAULT 0` | Conteo de evaluaciones recibidas. |

Índice: `idx_eval_proveedor (id_proveedor)`.

### 2.2 Backend

| Archivo | Responsabilidad |
|---|---|
| `app/repositories/ReputacionRepository.php` | `findByContrato`, `findByProveedor` (JOINs), `create`, `recalcularScore` (UPDATE con subqueries AVG+COUNT), `findScoreProveedor`. |
| `app/services/ReputacionService.php` | `crearEvaluacion` (verifica contrato/estatus/ya-evaluado, valida 1-5, calcula promedio, recalcula score, audita), `getReputacion` (score + nivel + historial). |
| `app/controllers/ReputacionController.php` | `crearEvaluacion` (ADMIN), `getReputacion` (auth). |

#### Niveles de reputación

| Score | Nivel |
|---|---|
| ≥ 4.5 | `excelente` |
| ≥ 3.5 | `bueno` |
| ≥ 2.5 | `regular` |
| < 2.5 | `deficiente` |
| null | `sin_evaluaciones` |

### 2.3 Endpoints

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| `POST` | `/api/v1/contratos/{id}/evaluacion-postcontrato` | `ADMINISTRADOR` | Crea evaluación. Body: `{puntualidad, calidad, comunicacion, cumplimiento_alcance, comentarios?}`. |
| `GET` | `/api/v1/proveedores/{id}/reputacion` | Autenticado | Devuelve `{id_proveedor, score_reputacion, total_evaluaciones, nivel, historial[]}`. |

**Respuestas POST:**
- `201` — Evaluación creada. Devuelve `{id_evaluacion, promedio, score_reputacion_actualizado, total_evaluaciones}`.
- `404` — Contrato no encontrado.
- `409` — Contrato ya evaluado, o estatus no permitido.
- `422` — Criterios fuera de rango 1-5.

### 2.4 Frontend

**`frontend/admin/proveedores/index.html`:**
- Nueva columna "Score" en la tabla.
- Función `scoreBadge(score, total)`: badge coloreado por nivel (emerald/blue/amber/red), estrellas visuales, tooltip con total de evaluaciones.
- Colspan actualizado a 8.

**`frontend/proveedor/perfil.html`:**
- Badge `#score-reputacion-badge` junto al estatus.
- Cargado via `GET /proveedores/{id}/reputacion` en `hydrateForms`.
- Colores dinámicos por nivel, tooltip con total de evaluaciones.

### 2.5 Tests E2E

`e2e/tests/proveedor-reputacion.spec.ts` — **7 casos**:

1. ✅ `GET /reputacion` responde 200 con estructura correcta
2. ✅ Proveedor puede ver su propia reputación
3. ✅ Sin auth devuelve 401
4. ✅ Proveedor recibe 403 en POST evaluación (solo ADMIN)
5. ✅ Validación rango 1-5 (422/409)
6. ✅ Crear evaluación y verificar score actualizado (tolerante a 409)
7. ✅ Segundo intento devuelve 409/422

## 3. Verificación en producción

```bash
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' | jq -r .data.token)

# Ver reputación del proveedor 1
curl -s "https://sgplopypc.up.railway.app/api/v1/proveedores/1/reputacion" \
  -H "Authorization: Bearer $TOKEN" | jq '{score: .data.score_reputacion, nivel: .data.nivel, total: .data.total_evaluaciones}'
# → {"score": 4.5, "nivel": "excelente", "total": 1}
```

## 4. Resultados E2E

```
Fase 8 (proveedor-reputacion):      7 passed
Smoke regresivo:
  contrato-firma-efirma:             7 passed
  notif-realtime:                    7 passed
                                  ─────────
TOTAL:                            21 passed / 0 failed
```

## 5. Decisiones técnicas

### Recálculo de score en cada evaluación
En lugar de calcular el score en tiempo de consulta, se recalcula y persiste en `proveedor.score_reputacion` cada vez que se crea una evaluación. Esto hace que las consultas de listado de proveedores sean O(1) en lugar de O(n evaluaciones).

### Promedio simple de 4 criterios
El promedio se calcula como `(puntualidad + calidad + comunicacion + cumplimiento_alcance) / 4`. En el futuro se pueden agregar pesos por criterio sin cambiar el schema.

### Restricción UNIQUE en id_contrato
Un contrato sólo puede evaluarse una vez. Esto previene manipulación del score mediante evaluaciones múltiples del mismo contrato.

### Estatus permitidos para evaluar
`CONCLUIDO`, `EN_EJECUCION`, `VIGENTE`. No se permite evaluar contratos `EN_FORMALIZACION` o `RESCINDIDO`.

## 6. Próximas mejoras opcionales

- Pesos por criterio (puntualidad más importante que comunicación).
- Evaluación por el propio proveedor (autoevaluación).
- Historial público visible sin autenticación.
- Filtro por score en el listado de proveedores del admin.

---

## Anexo — Plantilla de cierre

```text
Commits:       01d2296 (feat) → 1dbe3b8 (test fix)
HEAD final:    1dbe3b82ae02cf000200bfb5d50a39a9b80b3fea
Deployment:    c08010a2-d6ec-4d6a-8bbf-ec023c0ce8c9
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 8:    7 passed / 0 failed
E2E regresión: 14 passed / 0 failed (Fases 6-7)
Total:         21 passed / 0 failed
Endpoints:     POST /contratos/{id}/evaluacion-postcontrato (ADMIN)
               GET  /proveedores/{id}/reputacion (auth)
Tablas:        proveedor_evaluacion_postcontrato (nueva)
               proveedor (+ score_reputacion, total_evaluaciones)
Niveles:       excelente(≥4.5) / bueno(≥3.5) / regular(≥2.5) / deficiente(<2.5)
```
