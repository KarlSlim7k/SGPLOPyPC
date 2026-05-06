-- Migración Fase 1: Seed de usuarios de prueba con contraseñas hasheadas (bcrypt)
-- Ejecutar después de aplicar el esquema base (database/sql/if0_39815580_sgplopypc.sql)

-- Limpiar usuarios de prueba previos para evitar duplicados en re-ejecuciones controladas
DELETE FROM usuario WHERE email IN ('admin@sgplopypc.gob.mx', 'proveedor@demo.mx', 'publico@demo.mx');

-- Insertar usuarios de prueba
INSERT INTO usuario (nombre, email, contrasena_hash, rol, activo, fecha_registro, ultimo_acceso) VALUES
('Administrador Demo', 'admin@sgplopypc.gob.mx', '$2y$12$7ELTv5JtXI9fi3TO6hfD6OviARPLUwoAKGQXwYUtu36kQVxs5al7i', 'ADMINISTRADOR', 1, NOW(), NULL),
('Proveedor Demo', 'proveedor@demo.mx', '$2y$12$AkSMY0v0Fp0KcDPLpsWjT.qds.F6MluvhuIwQgRSR.2mV9W/WxdFq', 'PROVEEDOR', 1, NOW(), NULL),
('Usuario Público Demo', 'publico@demo.mx', '$2y$12$onZB5S.L812wTZy8ik.dzejPhVjChfMOK5fJ.k2X3Vc3bbYgbgeqi', 'PUBLICO', 1, NOW(), NULL);
