-- Migración 005: Tabla aclaracion para junta de aclaraciones
-- Idempotente: usa CREATE TABLE IF NOT EXISTS y ALTER TABLE condicional

CREATE TABLE IF NOT EXISTS aclaracion (
  id_aclaracion   INT NOT NULL AUTO_INCREMENT,
  id_licitacion   INT NOT NULL,
  id_proveedor    INT NOT NULL,
  pregunta        TEXT NOT NULL,
  respuesta       TEXT NULL,
  respondida_por  INT NULL,
  fecha_pregunta  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_respuesta DATETIME NULL,
  PRIMARY KEY (id_aclaracion),
  CONSTRAINT fk_aclaracion_licitacion FOREIGN KEY (id_licitacion) REFERENCES licitacion(id_licitacion) ON DELETE CASCADE,
  CONSTRAINT fk_aclaracion_proveedor  FOREIGN KEY (id_proveedor)  REFERENCES proveedor(id_proveedor)  ON DELETE CASCADE,
  CONSTRAINT fk_aclaracion_respondida FOREIGN KEY (respondida_por) REFERENCES usuario(id_usuario)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices (idempotentes)
SET @db = DATABASE();

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db AND table_name = 'aclaracion' AND index_name = 'idx_aclaracion_licitacion') = 0,
  'CREATE INDEX idx_aclaracion_licitacion ON aclaracion(id_licitacion)',
  'SELECT "idx_aclaracion_licitacion already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db AND table_name = 'aclaracion' AND index_name = 'idx_aclaracion_proveedor') = 0,
  'CREATE INDEX idx_aclaracion_proveedor ON aclaracion(id_proveedor)',
  'SELECT "idx_aclaracion_proveedor already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
