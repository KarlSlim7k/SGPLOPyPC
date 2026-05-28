-- Migración 012: Auditoría extendida
-- Fase 1 de mejoras: agrega user_agent, request_id e índices a historial_cambio
-- Idempotente: usa IF NOT EXISTS donde aplica

-- 1) Ampliar enum de acción para soportar eventos de auth y operaciones especiales
ALTER TABLE historial_cambio
    MODIFY COLUMN accion ENUM(
        'CREAR',
        'ACTUALIZAR',
        'ELIMINAR',
        'FIRMAR',
        'LOGIN_OK',
        'LOGIN_FALLIDO',
        'LOGOUT',
        'PASSWORD_CHANGE',
        'PASSWORD_RESET',
        'EXPORT',
        'CONSULTA'
    ) NOT NULL;

-- 2) Agregar columna user_agent (capturado del header HTTP)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'historial_cambio'
                     AND COLUMN_NAME = 'user_agent');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE historial_cambio ADD COLUMN user_agent VARCHAR(500) NULL AFTER ip_origen',
    'SELECT "user_agent ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Agregar columna request_id (correlación de eventos por petición)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'historial_cambio'
                     AND COLUMN_NAME = 'request_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE historial_cambio ADD COLUMN request_id VARCHAR(40) NULL AFTER user_agent',
    'SELECT "request_id ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Permitir id_usuario NULL para registrar eventos de login fallido (donde aún no hay sesión)
ALTER TABLE historial_cambio MODIFY COLUMN id_usuario INT NULL;

-- 5) Permitir id_registro_afectado = 0 para eventos no asociados a un registro específico (login)
ALTER TABLE historial_cambio MODIFY COLUMN id_registro_afectado INT NOT NULL DEFAULT 0;

-- 6) Índices para consultas frecuentes de auditoría
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'historial_cambio'
                     AND INDEX_NAME = 'idx_historial_usuario_fecha');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_historial_usuario_fecha ON historial_cambio (id_usuario, fecha_accion)',
    'SELECT "idx_historial_usuario_fecha ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'historial_cambio'
                     AND INDEX_NAME = 'idx_historial_request_id');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_historial_request_id ON historial_cambio (request_id)',
    'SELECT "idx_historial_request_id ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'historial_cambio'
                     AND INDEX_NAME = 'idx_historial_fecha');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_historial_fecha ON historial_cambio (fecha_accion)',
    'SELECT "idx_historial_fecha ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
