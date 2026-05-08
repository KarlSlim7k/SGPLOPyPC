-- Migración Fase 6: Cierre de funcionalidades PUBLICO
-- Incluye: recuperación de contraseña, soporte público y ampliación de proveedor

SET @db = DATABASE();

-- 1) Tabla password_reset_token
CREATE TABLE IF NOT EXISTS password_reset_token (
  id_password_reset_token INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(200) NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  request_ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_password_reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'password_reset_token'
     AND index_name = 'idx_prt_email') = 0,
  'CREATE INDEX idx_prt_email ON password_reset_token(email)',
  'SELECT "idx_prt_email already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'password_reset_token'
     AND index_name = 'idx_prt_token_hash') = 0,
  'CREATE INDEX idx_prt_token_hash ON password_reset_token(token_hash)',
  'SELECT "idx_prt_token_hash already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'password_reset_token'
     AND index_name = 'idx_prt_expires') = 0,
  'CREATE INDEX idx_prt_expires ON password_reset_token(expires_at)',
  'SELECT "idx_prt_expires already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Tabla soporte_ticket
CREATE TABLE IF NOT EXISTS soporte_ticket (
  id_soporte_ticket INT NOT NULL AUTO_INCREMENT,
  folio VARCHAR(50) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(200) NOT NULL,
  telefono VARCHAR(20) NULL,
  asunto VARCHAR(200) NOT NULL,
  mensaje TEXT NOT NULL,
  estado ENUM('NUEVO','EN_PROCESO','CERRADO') NOT NULL DEFAULT 'NUEVO',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_soporte_ticket),
  UNIQUE KEY uq_soporte_ticket_folio (folio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'soporte_ticket'
     AND index_name = 'idx_soporte_email') = 0,
  'CREATE INDEX idx_soporte_email ON soporte_ticket(email)',
  'SELECT "idx_soporte_email already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = @db
     AND table_name = 'soporte_ticket'
     AND index_name = 'idx_soporte_estado') = 0,
  'CREATE INDEX idx_soporte_estado ON soporte_ticket(estado)',
  'SELECT "idx_soporte_estado already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Ampliación mínima de proveedor
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db
     AND table_name = 'proveedor'
     AND column_name = 'regimen_fiscal') = 0,
  'ALTER TABLE proveedor ADD COLUMN regimen_fiscal VARCHAR(120) NULL AFTER registro_fiscal',
  'SELECT "proveedor.regimen_fiscal already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db
     AND table_name = 'proveedor'
     AND column_name = 'contacto_cargo') = 0,
  'ALTER TABLE proveedor ADD COLUMN contacto_cargo VARCHAR(120) NULL AFTER telefono',
  'SELECT "proveedor.contacto_cargo already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db
     AND table_name = 'proveedor'
     AND column_name = 'contacto_email') = 0,
  'ALTER TABLE proveedor ADD COLUMN contacto_email VARCHAR(200) NULL AFTER contacto_cargo',
  'SELECT "proveedor.contacto_email already exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
