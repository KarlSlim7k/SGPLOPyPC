-- Migración 008: separar datos de prueba E2E de datos públicos
-- Agrega bandera is_test y marca registros detectados como dataset de prueba.
-- Idempotente para ejecución repetida.

SET @db = DATABASE();

-- 1) Columna is_test en licitacion
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'licitacion' AND column_name = 'is_test') = 0,
  'ALTER TABLE licitacion ADD COLUMN is_test TINYINT(1) NOT NULL DEFAULT 0 AFTER estado_proceso',
  'SELECT "licitacion.is_test already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Índice para filtros públicos
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db AND table_name = 'licitacion' AND index_name = 'idx_licitacion_is_test_estado') = 0,
  'CREATE INDEX idx_licitacion_is_test_estado ON licitacion (is_test, estado_proceso, fecha_actualizacion)',
  'SELECT "idx_licitacion_is_test_estado already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Marcar dataset E2E existente
UPDATE licitacion
SET is_test = 1
WHERE numero_licitacion LIKE 'E2E-%'
   OR descripcion_proyecto LIKE '%[E2E_TEST_DATASET]%';
