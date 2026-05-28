-- Migración 013: Reportes con plantillas
-- Fase 2 de mejoras: tablas para plantillas reutilizables y assets (logos/firmas)

-- 1) Tabla plantilla_reporte
CREATE TABLE IF NOT EXISTS plantilla_reporte (
    id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500) NULL,
    tipo ENUM(
        'ACTA_APERTURA',
        'ACTA_ACLARACIONES',
        'ACTA_FALLO',
        'DICTAMEN',
        'CONTRATO',
        'RESUMEN_LICITACION',
        'PERSONALIZADA'
    ) NOT NULL,
    -- Contenido HTML con placeholders {{variable}} para renderizar a PDF (Dompdf) y DOCX (PHPWord HTML import)
    contenido_html LONGTEXT NOT NULL,
    -- Estructura JSON opcional del editor visual (pdfme), guardada para reedición fiel
    contenido_json LONGTEXT NULL,
    -- Variables esperadas (CSV o JSON de nombres). Documenta qué placeholders consume la plantilla.
    variables_esperadas TEXT NULL,
    id_usuario_creador INT NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    es_predefinida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_plantilla_creador FOREIGN KEY (id_usuario_creador)
        REFERENCES usuario(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para búsquedas frecuentes
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'plantilla_reporte'
                     AND INDEX_NAME = 'idx_plantilla_tipo_activa');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_plantilla_tipo_activa ON plantilla_reporte (tipo, activa)',
    'SELECT "idx_plantilla_tipo_activa ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Tabla plantilla_asset (logos, firmas, sellos)
CREATE TABLE IF NOT EXISTS plantilla_asset (
    id_asset INT AUTO_INCREMENT PRIMARY KEY,
    id_plantilla INT NOT NULL,
    tipo ENUM('LOGO', 'FIRMA', 'SELLO', 'OTRO') NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    ruta_relativa VARCHAR(500) NOT NULL,
    -- Posición opcional para que el render coloque el asset (en mm desde origen)
    pos_x DECIMAL(8,2) NULL,
    pos_y DECIMAL(8,2) NULL,
    ancho_mm DECIMAL(8,2) NULL,
    alto_mm DECIMAL(8,2) NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asset_plantilla FOREIGN KEY (id_plantilla)
        REFERENCES plantilla_reporte(id_plantilla) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'plantilla_asset'
                     AND INDEX_NAME = 'idx_asset_plantilla_tipo');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_asset_plantilla_tipo ON plantilla_asset (id_plantilla, tipo)',
    'SELECT "idx_asset_plantilla_tipo ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
