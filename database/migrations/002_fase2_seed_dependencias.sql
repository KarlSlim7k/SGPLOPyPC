-- Migración Fase 2: Seed de dependencias de prueba
DELETE FROM dependencia WHERE nombre IN ('Secretaría de Obras Públicas', 'Secretaría de Educación', 'Secretaría de Salud', 'Instituto Municipal de Vivienda');

INSERT INTO dependencia (nombre, siglas, direccion, telefono, email_contacto, activa) VALUES
('Secretaría de Obras Públicas', 'SOP', 'Av. Independencia #100, Centro', '2281234567', 'sop@gobierno.gob.mx', 1),
('Secretaría de Educación', 'SEDUC', 'Calle Juárez #200, Centro', '2281234568', 'seduc@gobierno.gob.mx', 1),
('Secretaría de Salud', 'SSA', 'Av. Revolución #300, Centro', '2281234569', 'ssa@gobierno.gob.mx', 1),
('Instituto Municipal de Vivienda', 'IMV', 'Calle 5 de Mayo #400, Centro', '2281234570', 'imv@gobierno.gob.mx', 1);
