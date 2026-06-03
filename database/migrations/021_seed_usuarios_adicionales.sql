-- Migración 021: Usuarios adicionales de prueba por rol
-- 2 admin, 2 proveedor, 2 público

DELETE FROM usuario WHERE email IN (
  'admin2@demo.mx', 'admin3@demo.mx',
  'proveedor2@demo.mx', 'proveedor3@demo.mx',
  'publico2@demo.mx', 'publico3@demo.mx'
);

INSERT INTO usuario (nombre, email, contrasena_hash, rol, activo, fecha_registro, ultimo_acceso) VALUES
('Administrador Demo 2', 'admin2@demo.mx', '$2y$12$NqKvkGdIawCmHLWaWiwNxOQanUWyNZKrZUD/fU653W5qBJdmQTmeq', 'ADMINISTRADOR', 1, NOW(), NULL),
('Administrador Demo 3', 'admin3@demo.mx', '$2y$12$NzzGHo5iDlZx3iDrAAblP.uVTwrMI9r3dAkby89Iw3mPxqLWBszzG', 'ADMINISTRADOR', 1, NOW(), NULL),
('Proveedor Demo 2', 'proveedor2@demo.mx', '$2y$12$KsNaITjZ16aEmySadnWhpubIojnc6HAtd8l.uoeOxJwTKQTyWON16', 'PROVEEDOR', 1, NOW(), NULL),
('Proveedor Demo 3', 'proveedor3@demo.mx', '$2y$12$uWrq6Wz0ddYxtbudV99EMOjdeQF7NmkfAZ2fR3HNKXJcmVU9FkSMi', 'PROVEEDOR', 1, NOW(), NULL),
('Usuario Público Demo 2', 'publico2@demo.mx', '$2y$12$bm86BgCnaCcvBlJEzslldulqyCGPQHeyxfmzzgBaJd1vXgWha2zzy', 'PUBLICO', 1, NOW(), NULL),
('Usuario Público Demo 3', 'publico3@demo.mx', '$2y$12$/fP4BYQKHRDW5HdDeKde8eBe2dF04Zrq5UFIYCJh81LuwUxITmGiu', 'PUBLICO', 1, NOW(), NULL);
