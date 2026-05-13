-- Migración 011: Ajuste de datos demo para presentación
-- Idempotente. Limpia residuos QA, corrige nombre del proveedor demo,
-- y agrega participación + propuesta demo para flujo completo.

-- 1) Limpiar tickets residuales de QA (emails de prueba automatizada)
DELETE FROM soporte_ticket
WHERE email LIKE 'qa.cierre.%@example.com'
   OR email LIKE 'e2e.soporte.%@example.com';

-- 2) Corregir nombre del proveedor demo (nombre con timestamp E2E)
UPDATE proveedor
SET nombre_empresa = 'Constructora del Centro SA de CV',
    representante_legal = 'Juan Pérez Ramírez',
    registro_fiscal = 'CCE260101ABC',
    domicilio = 'Av. Principal #100, Centro, Ciudad de México',
    telefono = '5551234567',
    especialidad = 'Construcción de infraestructura pública'
WHERE id_usuario = (SELECT id_usuario FROM usuario WHERE email = 'proveedor@demo.mx' LIMIT 1)
  AND (nombre_empresa LIKE 'Proveedor E2E %' OR nombre_empresa LIKE 'Proveedor Demo%');

-- 3) Agregar participación demo en licitación RECEPCION_PROPUESTAS (si no existe)
SET @id_lic_rec = (SELECT id_licitacion FROM licitacion WHERE numero_licitacion = 'SEED-RECEPCION-001' LIMIT 1);
SET @id_prov = (SELECT id_proveedor FROM proveedor WHERE id_usuario = (SELECT id_usuario FROM usuario WHERE email = 'proveedor@demo.mx' LIMIT 1) LIMIT 1);

INSERT INTO participacion (id_licitacion, id_proveedor, estatus, fecha_inscripcion)
SELECT @id_lic_rec, @id_prov, 'PROPUESTA_ENVIADA', NOW()
WHERE @id_lic_rec IS NOT NULL AND @id_prov IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM participacion WHERE id_licitacion = @id_lic_rec AND id_proveedor = @id_prov
  );

-- 4) Agregar propuesta demo en esa participación (si no existe)
SET @id_part = (SELECT id_participacion FROM participacion WHERE id_licitacion = @id_lic_rec AND id_proveedor = @id_prov LIMIT 1);

INSERT INTO propuesta (id_participacion, monto_propuesta, descripcion_tecnica, estatus, fecha_envio)
SELECT @id_part, 720000.00,
  'Propuesta técnica con materiales de alta resistencia y metodología certificada ISO 9001.',
  'ENVIADA', NOW()
WHERE @id_part IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM propuesta WHERE id_participacion = @id_part);

-- 5) Agregar participación demo en licitación EN_ACLARACIONES (si no existe)
SET @id_lic_acl = (SELECT id_licitacion FROM licitacion WHERE numero_licitacion = 'SEED-ACLARACIONES-001' LIMIT 1);

INSERT INTO participacion (id_licitacion, id_proveedor, estatus, fecha_inscripcion)
SELECT @id_lic_acl, @id_prov, 'INSCRITO', NOW()
WHERE @id_lic_acl IS NOT NULL AND @id_prov IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM participacion WHERE id_licitacion = @id_lic_acl AND id_proveedor = @id_prov
  );

-- 6) Ticket de soporte demo (si no hay ninguno legítimo)
INSERT INTO soporte_ticket (folio, nombre, email, telefono, asunto, mensaje, estado, created_at, updated_at)
SELECT 'SUP-DEMO-2026-001', 'Ciudadano Demo', 'ciudadano@demo.mx', '5559876543',
  'Consulta sobre convocatoria SEED-ACLARACIONES-001',
  'Quisiera saber los requisitos técnicos para participar en la licitación SEED-ACLARACIONES-001.',
  'NUEVO', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM soporte_ticket WHERE folio = 'SUP-DEMO-2026-001');
