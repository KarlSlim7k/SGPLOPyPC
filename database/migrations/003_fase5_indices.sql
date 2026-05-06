-- Migración Fase 5: Índices adicionales y optimización
-- Objetivo: mejorar rendimiento de consultas frecuentes y planes de ejecución
-- Nota: MySQL no soporta CREATE INDEX IF NOT EXISTS de forma portable, por eso
-- se usa SQL dinámico contra information_schema para mantener idempotencia.

SET @db = DATABASE();

-- 1) fecha_proceso: búsquedas por licitación y tipo
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'fecha_proceso'
       AND index_name = 'idx_fecha_proceso_licitacion') = 0,
    'CREATE INDEX idx_fecha_proceso_licitacion ON fecha_proceso(id_licitacion)',
    'SELECT "idx_fecha_proceso_licitacion already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) propuesta: JOINs frecuentes desde participación y evaluación
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'propuesta'
       AND index_name = 'idx_propuesta_participacion') = 0,
    'CREATE INDEX idx_propuesta_participacion ON propuesta(id_participacion)',
    'SELECT "idx_propuesta_participacion already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) evaluacion: índice para consultas de adjudicación/ganadora
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'evaluacion'
       AND index_name = 'idx_evaluacion_dictamen') = 0,
    'CREATE INDEX idx_evaluacion_dictamen ON evaluacion(dictamen)',
    'SELECT "idx_evaluacion_dictamen already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 4) contrato: fechas de adjudicación usadas en reportes por periodo
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'contrato'
       AND index_name = 'idx_contrato_fecha_adjudicacion') = 0,
    'CREATE INDEX idx_contrato_fecha_adjudicacion ON contrato(fecha_adjudicacion)',
    'SELECT "idx_contrato_fecha_adjudicacion already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 5) notificacion: filtro por usuario destino
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'notificacion'
       AND index_name = 'idx_notificacion_usuario') = 0,
    'CREATE INDEX idx_notificacion_usuario ON notificacion(id_usuario_destino)',
    'SELECT "idx_notificacion_usuario already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6) historial_cambio: mejora búsqueda por tabla + acción
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'historial_cambio'
       AND index_name = 'idx_historial_tabla_accion') = 0,
    'CREATE INDEX idx_historial_tabla_accion ON historial_cambio(tabla_afectada, accion)',
    'SELECT "idx_historial_tabla_accion already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 7) documento: índice compuesto para búsquedas por tipo + licitación
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = @db
       AND table_name = 'documento'
       AND index_name = 'idx_documento_tipo_licitacion') = 0,
    'CREATE INDEX idx_documento_tipo_licitacion ON documento(tipo_documento, id_licitacion)',
    'SELECT "idx_documento_tipo_licitacion already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
