-- Migración 006: columnas de firma del proveedor en tabla contrato
-- Idempotente: usa ALTER TABLE ... IF NOT EXISTS (MariaDB 10.x compatible)

SET @db = DATABASE();

-- fecha_firma_proveedor
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'contrato' AND column_name = 'fecha_firma_proveedor') = 0,
  'ALTER TABLE contrato ADD COLUMN fecha_firma_proveedor DATETIME NULL DEFAULT NULL',
  'SELECT "fecha_firma_proveedor already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- firmado_por (id_usuario del proveedor que firmó)
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'contrato' AND column_name = 'firmado_por') = 0,
  'ALTER TABLE contrato ADD COLUMN firmado_por INT NULL DEFAULT NULL',
  'SELECT "firmado_por already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
