-- Migración 019: Agregar RETIRADA al estatus de propuesta
-- Idempotente.

ALTER TABLE propuesta
MODIFY COLUMN estatus ENUM('RECIBIDA','EN_REVISION','ACEPTADA','RECHAZADA','RETIRADA') NOT NULL DEFAULT 'RECIBIDA';
