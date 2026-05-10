-- Migración 009: Fase 4 (integridad e índices adicionales)
-- 1) Índice compuesto en notificacion(id_usuario_destino, leida, fecha_envio)
-- 2) Restricción UNIQUE en fecha_proceso(id_licitacion, tipo_fecha) con limpieza previa

SET @db = DATABASE();

-- 1) Índice compuesto para consulta de bandeja de notificaciones
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'notificacion'
     AND index_name = 'idx_notificacion_usuario_leida_envio') = 0,
  'CREATE INDEX idx_notificacion_usuario_leida_envio ON notificacion(id_usuario_destino, leida, fecha_envio)',
  'SELECT "idx_notificacion_usuario_leida_envio already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Limpieza preventiva de duplicados para poder aplicar UNIQUE
DELETE fp_old
FROM fecha_proceso fp_old
INNER JOIN fecha_proceso fp_new
  ON fp_old.id_licitacion = fp_new.id_licitacion
 AND fp_old.tipo_fecha = fp_new.tipo_fecha
 AND fp_old.id_fecha_proceso < fp_new.id_fecha_proceso;

-- 3) UNIQUE por licitación y tipo de fecha
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'fecha_proceso'
     AND index_name = 'uq_fecha_proceso_licitacion_tipo'
     AND non_unique = 0) = 0,
  'ALTER TABLE fecha_proceso ADD CONSTRAINT uq_fecha_proceso_licitacion_tipo UNIQUE (id_licitacion, tipo_fecha)',
  'SELECT "uq_fecha_proceso_licitacion_tipo already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
