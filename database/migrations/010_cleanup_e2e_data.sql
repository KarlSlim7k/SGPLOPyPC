-- Migracion 010: limpieza fisica de datos E2E en produccion
-- Idempotente: elimina solo registros con marcadores E2E explicitos.

SET @db = DATABASE();

DROP TEMPORARY TABLE IF EXISTS tmp_e2e_licitacion;
CREATE TEMPORARY TABLE tmp_e2e_licitacion AS
SELECT id_licitacion
FROM licitacion
WHERE COALESCE(is_test, 0) = 1
   OR numero_licitacion LIKE 'E2E-%'
   OR descripcion_proyecto LIKE '%[E2E_TEST_DATASET]%';

DROP TEMPORARY TABLE IF EXISTS tmp_e2e_usuario;
CREATE TEMPORARY TABLE tmp_e2e_usuario AS
SELECT id_usuario
FROM usuario
WHERE email LIKE 'e2e.%@example.com'
   OR email LIKE 'proveedor.hardening.%@example.com';

DROP TEMPORARY TABLE IF EXISTS tmp_e2e_proveedor;
CREATE TEMPORARY TABLE tmp_e2e_proveedor AS
SELECT id_proveedor
FROM proveedor
WHERE id_usuario IN (SELECT id_usuario FROM tmp_e2e_usuario)
   OR nombre_empresa LIKE 'Proveedor Fase5 %'
   OR nombre_empresa LIKE 'Empresa E2E %'
   OR nombre_empresa LIKE 'Proveedor Hardening %';

DELETE d
FROM documento d
JOIN tmp_e2e_licitacion t ON t.id_licitacion = d.id_licitacion;

DELETE d
FROM documento d
JOIN contrato c ON c.id_contrato = d.id_contrato
JOIN tmp_e2e_licitacion t ON t.id_licitacion = c.id_licitacion;

DELETE d
FROM documento d
JOIN propuesta pr ON pr.id_propuesta = d.id_propuesta
JOIN participacion pa ON pa.id_participacion = pr.id_participacion
JOIN tmp_e2e_licitacion t ON t.id_licitacion = pa.id_licitacion;

DELETE d
FROM documento d
JOIN tmp_e2e_proveedor p ON p.id_proveedor = d.id_proveedor;

DELETE d
FROM documento d
JOIN tmp_e2e_usuario u ON u.id_usuario = d.subido_por;

DELETE c
FROM contrato c
WHERE c.id_licitacion IN (SELECT id_licitacion FROM tmp_e2e_licitacion)
   OR c.id_proveedor IN (SELECT id_proveedor FROM tmp_e2e_proveedor);

DELETE n
FROM notificacion n
WHERE n.id_licitacion IN (SELECT id_licitacion FROM tmp_e2e_licitacion)
   OR n.id_usuario_destino IN (SELECT id_usuario FROM tmp_e2e_usuario);

DELETE h
FROM historial_cambio h
WHERE h.id_usuario IN (SELECT id_usuario FROM tmp_e2e_usuario);

DELETE pa
FROM participacion pa
WHERE pa.id_licitacion IN (SELECT id_licitacion FROM tmp_e2e_licitacion)
   OR pa.id_proveedor IN (SELECT id_proveedor FROM tmp_e2e_proveedor);

DELETE l
FROM licitacion l
WHERE l.id_licitacion IN (SELECT id_licitacion FROM tmp_e2e_licitacion);

DELETE p
FROM proveedor p
WHERE p.id_proveedor IN (SELECT id_proveedor FROM tmp_e2e_proveedor);

DELETE u
FROM usuario u
WHERE u.id_usuario IN (SELECT id_usuario FROM tmp_e2e_usuario);

DROP TEMPORARY TABLE IF EXISTS tmp_e2e_proveedor;
DROP TEMPORARY TABLE IF EXISTS tmp_e2e_usuario;
DROP TEMPORARY TABLE IF EXISTS tmp_e2e_licitacion;
