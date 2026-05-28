-- Migración 016: Firma electrónica avanzada (e.firma/FIEL) en contratos
-- Agrega columnas para almacenar metadatos de la firma e.firma del SAT.
-- NUNCA se almacena el archivo .key ni el password — sólo metadatos del certificado.
-- Idempotente: usa information_schema checks.

-- 1) RFC del firmante (extraído del certificado X.509)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND COLUMN_NAME = 'efirma_rfc');
SET @sql = IF(@col = 0,
    'ALTER TABLE contrato ADD COLUMN efirma_rfc VARCHAR(13) NULL AFTER firmado_por',
    'SELECT "efirma_rfc ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Nombre del titular del certificado (CN del Subject)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND COLUMN_NAME = 'efirma_titular');
SET @sql = IF(@col = 0,
    'ALTER TABLE contrato ADD COLUMN efirma_titular VARCHAR(300) NULL AFTER efirma_rfc',
    'SELECT "efirma_titular ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Número de serie del certificado (hexadecimal)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND COLUMN_NAME = 'efirma_serial');
SET @sql = IF(@col = 0,
    'ALTER TABLE contrato ADD COLUMN efirma_serial VARCHAR(40) NULL AFTER efirma_titular',
    'SELECT "efirma_serial ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 4) Fecha de firma (cuando se aplicó la e.firma)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND COLUMN_NAME = 'efirma_fecha');
SET @sql = IF(@col = 0,
    'ALTER TABLE contrato ADD COLUMN efirma_fecha DATETIME NULL AFTER efirma_serial',
    'SELECT "efirma_fecha ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 5) Hash SHA-256 del documento firmado (para verificación posterior)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND COLUMN_NAME = 'efirma_hash_documento');
SET @sql = IF(@col = 0,
    'ALTER TABLE contrato ADD COLUMN efirma_hash_documento VARCHAR(64) NULL AFTER efirma_fecha',
    'SELECT "efirma_hash_documento ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6) Firma digital (base64 de la firma PKCS#1) — para verificación criptográfica
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND COLUMN_NAME = 'efirma_firma_b64');
SET @sql = IF(@col = 0,
    'ALTER TABLE contrato ADD COLUMN efirma_firma_b64 TEXT NULL AFTER efirma_hash_documento',
    'SELECT "efirma_firma_b64 ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 7) Índice para búsquedas por RFC firmante
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contrato' AND INDEX_NAME = 'idx_contrato_efirma_rfc');
SET @sql = IF(@idx = 0,
    'CREATE INDEX idx_contrato_efirma_rfc ON contrato (efirma_rfc)',
    'SELECT "idx_contrato_efirma_rfc ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
