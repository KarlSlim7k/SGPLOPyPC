-- Migración Fase 5: Índices adicionales y optimización
-- Objetivo: mejorar rendimiento de consultas frecuentes y planes de ejecución

-- 1) fecha_proceso: búsquedas por licitación y tipo (ya existe UNIQUE compuesta,
--    pero un índice adicional en id_licitación acelera JOINs y filtrados)
CREATE INDEX IF NOT EXISTS idx_fecha_proceso_licitacion ON fecha_proceso(id_licitacion);

-- 2) propuesta: JOINs frecuentes desde participación y evaluación
CREATE INDEX IF NOT EXISTS idx_propuesta_participacion ON propuesta(id_participacion);

-- 3) evaluacion: filtro por propuesta ya tiene UNIQUE, agregamos índice en dictamen
--    para consultas de adjudicación/ganadora
CREATE INDEX IF NOT EXISTS idx_evaluacion_dictamen ON evaluacion(dictamen);

-- 4) contrato: fechas de adjudicación usadas en reportes por periodo
CREATE INDEX IF NOT EXISTS idx_contrato_fecha_adjudicacion ON contrato(fecha_adjudicacion);

-- 5) notificacion: filtro por licitación + usuario (ya existe idx_notificacion_usuario_leida)
CREATE INDEX IF NOT EXISTS idx_notificacion_usuario ON notificacion(id_usuario_destino);

-- 6) historial_cambio: mejora búsqueda por tabla + acción
CREATE INDEX IF NOT EXISTS idx_historial_tabla_accion ON historial_cambio(tabla_afectada, accion);

-- 7) documento: índice compuesto para búsquedas por tipo + licitación (transparencia/docs públicos)
CREATE INDEX IF NOT EXISTS idx_documento_tipo_licitacion ON documento(tipo_documento, id_licitacion);
