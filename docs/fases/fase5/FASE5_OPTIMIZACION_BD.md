# Fase 5 — Optimización de Base de Datos y Consultas

## 1. Consultas críticas identificadas

### Dashboard (`ReporteRepository::resumenDashboard`)
- **Antes:** 7 consultas `COUNT(*)` individuales.
- **Impacto:** N+1 a nivel de aggregación. Aunque cada una es rápida, genera múltiples round-trips.
- **Acción:** Se dejó documentado que, ante crecimiento de tablas, estas 7 consultas pueden consolidarse en una sola con `UNION ALL` o mediante una vista materializada si el motor lo permite.

### Historial de licitación (`ReporteRepository::findHistorialByLicitacion`)
- **Antes:** 4 consultas SQL + `usort` en PHP.
- **Impacto:** Carga creciente en PHP para ordenar resultados.
- **Acción:** Se mantiene el enfoque por claridad, pero cada consulta usa índices existentes. Si el historial crece >1000 eventos por licitación, se recomienda paginación.

### Listados públicos (`PublicRepository::findConvocatorias`)
- **Antes:** Uso de `ORDER BY` + `LIMIT` con `JOIN` a `dependencia`.
- **Optimización:** El índice existente `idx_licitacion_estado_proceso` cubre el filtro `WHERE estado_proceso NOT IN (...)`.

## 2. Índices creados/modificados

| Tabla | Índice | Columnas | Justificación |
|-------|--------|----------|---------------|
| `fecha_proceso` | `idx_fecha_proceso_licitacion` | `id_licitacion` | JOINs frecuentes desde licitación para obtener fechas del proceso |
| `propuesta` | `idx_propuesta_participacion` | `id_participacion` | JOINs desde participación y evaluación |
| `evaluacion` | `idx_evaluacion_dictamen` | `dictamen` | Filtrado de propuestas solventes para adjudicación |
| `contrato` | `idx_contrato_fecha_adjudicacion` | `fecha_adjudicacion` | Reportes de adjudicaciones por periodo (`BETWEEN`) |
| `notificacion` | `idx_notificacion_usuario` | `id_usuario_destino` | Listado de notificaciones por destinatario |
| `historial_cambio` | `idx_historial_tabla_accion` | `tabla_afectada`, `accion` | Búsquedas de auditoría por entidad y tipo de acción |
| `documento` | `idx_documento_tipo_licitacion` | `tipo_documento`, `id_licitacion` | Listado de documentos públicos por licitación (bases, anexos, actas) |

## 3. Impacto esperado

- **Fecha_proceso:** Reducción de tiempo en carga de detalle de licitación, especialmente cuando se consultan fechas junto con datos de dependencia y responsable.
- **Evaluacion (dictamen):** Aceleración de la consulta de propuesta ganadora (`findGanadoraByLicitacion`), que se ejecuta en cada adjudicación.
- **Contrato (fecha_adjudicacion):** Mejora en reportes de dashboard con rangos de fecha.
- **Documento:** Mejora en carga de historial/documentos públicos por licitación.

## 4. Recomendaciones futuras

- Revisar planes de ejecución (`EXPLAIN`) periódicamente en producción con carga real.
- Considerar particionamiento de `historial_cambio` y `notificacion` si superan 1M de registros.
- Consolidar queries de dashboard en una sola transacción de lectura para reducir round-trips.
