-- Migración 015: Autenticación multifactor (MFA/2FA) TOTP
-- Agrega columnas a la tabla usuario para soportar TOTP (RFC 6238).
-- Idempotente: usa IF NOT EXISTS / information_schema checks.

-- 1) mfa_secret: clave base32 del TOTP (cifrada en reposo si se configura APP_KEY)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'mfa_secret');
SET @sql = IF(@col = 0,
    'ALTER TABLE usuario ADD COLUMN mfa_secret VARCHAR(64) NULL AFTER contrasena_hash',
    'SELECT "mfa_secret ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) mfa_enabled: flag que activa el segundo factor
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'mfa_enabled');
SET @sql = IF(@col = 0,
    'ALTER TABLE usuario ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_secret',
    'SELECT "mfa_enabled ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) mfa_backup_codes: JSON array de códigos de respaldo (hasheados con bcrypt)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'mfa_backup_codes');
SET @sql = IF(@col = 0,
    'ALTER TABLE usuario ADD COLUMN mfa_backup_codes TEXT NULL AFTER mfa_enabled',
    'SELECT "mfa_backup_codes ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 4) Índice para búsquedas de usuarios con MFA activo (útil para reportes de seguridad)
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND INDEX_NAME = 'idx_usuario_mfa_enabled');
SET @sql = IF(@idx = 0,
    'CREATE INDEX idx_usuario_mfa_enabled ON usuario (mfa_enabled)',
    'SELECT "idx_usuario_mfa_enabled ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
