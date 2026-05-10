-- Seed 007: datos de muestra para cubrir estados faltantes en E2E
-- Idempotente: usa INSERT ... ON DUPLICATE KEY UPDATE / IF NOT EXISTS

-- 1. Licitación EN_ACLARACIONES (para test de aclaraciones)
INSERT INTO licitacion (
  numero_licitacion, id_dependencia, id_usuario_responsable,
  tipo_procedimiento, descripcion_proyecto, presupuesto_estimado,
  ubicacion_proyecto, estado_proceso, fecha_creacion, fecha_actualizacion
)
SELECT
  'SEED-ACLARACIONES-001', 1, 2,
  'LICITACION_PUBLICA', 'Proyecto de prueba en fase de aclaraciones',
  500000.00, 'Ciudad de México', 'EN_ACLARACIONES', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM licitacion WHERE numero_licitacion = 'SEED-ACLARACIONES-001'
);

-- Fechas del proceso para la licitación EN_ACLARACIONES
SET @id_lic_acl = (SELECT id_licitacion FROM licitacion WHERE numero_licitacion = 'SEED-ACLARACIONES-001' LIMIT 1);

INSERT INTO fecha_proceso (id_licitacion, tipo_fecha, fecha_programada)
SELECT @id_lic_acl, 'PUBLICACION_CONVOCATORIA', DATE_SUB(NOW(), INTERVAL 10 DAY)
WHERE @id_lic_acl IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM fecha_proceso WHERE id_licitacion = @id_lic_acl AND tipo_fecha = 'PUBLICACION_CONVOCATORIA');

INSERT INTO fecha_proceso (id_licitacion, tipo_fecha, fecha_programada)
SELECT @id_lic_acl, 'JUNTA_ACLARACIONES', DATE_ADD(NOW(), INTERVAL 5 DAY)
WHERE @id_lic_acl IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM fecha_proceso WHERE id_licitacion = @id_lic_acl AND tipo_fecha = 'JUNTA_ACLARACIONES');

INSERT INTO fecha_proceso (id_licitacion, tipo_fecha, fecha_programada)
SELECT @id_lic_acl, 'RECEPCION_PROPUESTAS', DATE_ADD(NOW(), INTERVAL 15 DAY)
WHERE @id_lic_acl IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM fecha_proceso WHERE id_licitacion = @id_lic_acl AND tipo_fecha = 'RECEPCION_PROPUESTAS');

-- 2. Licitación RECEPCION_PROPUESTAS (para test de propuestas editables)
INSERT INTO licitacion (
  numero_licitacion, id_dependencia, id_usuario_responsable,
  tipo_procedimiento, descripcion_proyecto, presupuesto_estimado,
  ubicacion_proyecto, estado_proceso, fecha_creacion, fecha_actualizacion
)
SELECT
  'SEED-RECEPCION-001', 2, 2,
  'LICITACION_PUBLICA', 'Proyecto de prueba en recepción de propuestas',
  750000.00, 'Guadalajara, Jalisco', 'RECEPCION_PROPUESTAS', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM licitacion WHERE numero_licitacion = 'SEED-RECEPCION-001'
);

SET @id_lic_rec = (SELECT id_licitacion FROM licitacion WHERE numero_licitacion = 'SEED-RECEPCION-001' LIMIT 1);

INSERT INTO fecha_proceso (id_licitacion, tipo_fecha, fecha_programada)
SELECT @id_lic_rec, 'PUBLICACION_CONVOCATORIA', DATE_SUB(NOW(), INTERVAL 20 DAY)
WHERE @id_lic_rec IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM fecha_proceso WHERE id_licitacion = @id_lic_rec AND tipo_fecha = 'PUBLICACION_CONVOCATORIA');

INSERT INTO fecha_proceso (id_licitacion, tipo_fecha, fecha_programada)
SELECT @id_lic_rec, 'RECEPCION_PROPUESTAS', DATE_ADD(NOW(), INTERVAL 10 DAY)
WHERE @id_lic_rec IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM fecha_proceso WHERE id_licitacion = @id_lic_rec AND tipo_fecha = 'RECEPCION_PROPUESTAS');

-- 3. Contrato EN_FORMALIZACION sin firma (para test de firma de contrato)
-- Requiere una licitación ADJUDICADA y el proveedor VALIDADO (id=1)
INSERT INTO licitacion (
  numero_licitacion, id_dependencia, id_usuario_responsable,
  tipo_procedimiento, descripcion_proyecto, presupuesto_estimado,
  ubicacion_proyecto, estado_proceso, fecha_creacion, fecha_actualizacion
)
SELECT
  'SEED-ADJUDICADA-001', 3, 2,
  'LICITACION_PUBLICA', 'Proyecto adjudicado para prueba de contrato',
  1200000.00, 'Monterrey, Nuevo León', 'ADJUDICADA', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM licitacion WHERE numero_licitacion = 'SEED-ADJUDICADA-001'
);

SET @id_lic_adj = (SELECT id_licitacion FROM licitacion WHERE numero_licitacion = 'SEED-ADJUDICADA-001' LIMIT 1);

INSERT INTO contrato (
  id_licitacion, id_proveedor, numero_contrato,
  monto_contrato, fecha_adjudicacion, fecha_inicio, fecha_fin,
  estatus, fecha_creacion
)
SELECT
  @id_lic_adj, 1, 'SEED-CONTRATO-FORMA-001',
  1200000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY),
  'EN_FORMALIZACION', NOW()
WHERE @id_lic_adj IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM contrato WHERE numero_contrato = 'SEED-CONTRATO-FORMA-001'
  );
