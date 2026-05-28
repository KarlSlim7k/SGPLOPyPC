-- Migración 017: Calificación y reputación de proveedores
-- Tabla de evaluaciones post-contrato y columna score_reputacion en proveedor.
-- Idempotente.

-- 1) Tabla de evaluaciones post-contrato
CREATE TABLE IF NOT EXISTS proveedor_evaluacion_postcontrato (
    id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
    id_contrato INT NOT NULL,
    id_proveedor INT NOT NULL,
    -- Criterios de evaluación (1-5)
    puntualidad TINYINT NOT NULL,
    calidad TINYINT NOT NULL,
    comunicacion TINYINT NOT NULL,
    cumplimiento_alcance TINYINT NOT NULL,
    -- Promedio calculado al insertar
    promedio DECIMAL(3,2) NOT NULL,
    comentarios TEXT NULL,
    id_usuario_evaluador INT NOT NULL,
    fecha_evaluacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_eval_contrato FOREIGN KEY (id_contrato)
        REFERENCES contrato(id_contrato) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_eval_proveedor FOREIGN KEY (id_proveedor)
        REFERENCES proveedor(id_proveedor) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_eval_evaluador FOREIGN KEY (id_usuario_evaluador)
        REFERENCES usuario(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
    -- Un contrato sólo puede evaluarse una vez
    CONSTRAINT uq_eval_contrato UNIQUE (id_contrato),
    -- Validar rango 1-5
    CONSTRAINT chk_puntualidad CHECK (puntualidad BETWEEN 1 AND 5),
    CONSTRAINT chk_calidad CHECK (calidad BETWEEN 1 AND 5),
    CONSTRAINT chk_comunicacion CHECK (comunicacion BETWEEN 1 AND 5),
    CONSTRAINT chk_cumplimiento CHECK (cumplimiento_alcance BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proveedor_evaluacion_postcontrato'
              AND INDEX_NAME = 'idx_eval_proveedor');
SET @sql = IF(@idx = 0,
    'CREATE INDEX idx_eval_proveedor ON proveedor_evaluacion_postcontrato (id_proveedor)',
    'SELECT "idx_eval_proveedor ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Columna score_reputacion en proveedor (promedio ponderado de todas sus evaluaciones)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proveedor'
              AND COLUMN_NAME = 'score_reputacion');
SET @sql = IF(@col = 0,
    'ALTER TABLE proveedor ADD COLUMN score_reputacion DECIMAL(3,2) NULL AFTER especialidad',
    'SELECT "score_reputacion ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Columna total_evaluaciones para mostrar cuántas evaluaciones tiene el proveedor
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proveedor'
              AND COLUMN_NAME = 'total_evaluaciones');
SET @sql = IF(@col = 0,
    'ALTER TABLE proveedor ADD COLUMN total_evaluaciones INT NOT NULL DEFAULT 0 AFTER score_reputacion',
    'SELECT "total_evaluaciones ya existe"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
