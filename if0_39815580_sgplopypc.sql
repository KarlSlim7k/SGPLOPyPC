-- SQL Schema - SGPLOPyPC
-- Compatible con Railway (MariaDB 11+) y phpMyAdmin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `documento`;
DROP TABLE IF EXISTS `notificacion`;
DROP TABLE IF EXISTS `historial_cambio`;
DROP TABLE IF EXISTS `evaluacion`;
DROP TABLE IF EXISTS `propuesta`;
DROP TABLE IF EXISTS `participacion`;
DROP TABLE IF EXISTS `contrato`;
DROP TABLE IF EXISTS `fecha_proceso`;
DROP TABLE IF EXISTS `licitacion`;
DROP TABLE IF EXISTS `proveedor`;
DROP TABLE IF EXISTS `dependencia`;
DROP TABLE IF EXISTS `usuario`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `rol` enum('PUBLICO','PROVEEDOR','ADMINISTRADOR') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuario_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dependencia` (
  `id_dependencia` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(250) NOT NULL,
  `siglas` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email_contacto` varchar(200) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_dependencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nombre_empresa` varchar(250) NOT NULL,
  `representante_legal` varchar(200) NOT NULL,
  `registro_fiscal` varchar(20) NOT NULL,
  `domicilio` text NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `especialidad` varchar(300) DEFAULT NULL,
  `estatus` enum('PENDIENTE','VALIDADO','RECHAZADO','SUSPENDIDO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `uq_proveedor_usuario` (`id_usuario`),
  UNIQUE KEY `uq_proveedor_registro_fiscal` (`registro_fiscal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `licitacion` (
  `id_licitacion` int(11) NOT NULL AUTO_INCREMENT,
  `numero_licitacion` varchar(50) NOT NULL,
  `id_dependencia` int(11) NOT NULL,
  `id_usuario_responsable` int(11) NOT NULL,
  `tipo_procedimiento` enum('LICITACION_PUBLICA','INVITACION_RESTRINGIDA','ADJUDICACION_DIRECTA') NOT NULL,
  `descripcion_proyecto` text NOT NULL,
  `presupuesto_estimado` decimal(18,2) NOT NULL,
  `ubicacion_proyecto` varchar(500) DEFAULT NULL,
  `estado_proceso` enum('BORRADOR','PUBLICADA','EN_ACLARACIONES','RECEPCION_PROPUESTAS','EN_EVALUACION','ADJUDICADA','DESIERTA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_licitacion`),
  UNIQUE KEY `uq_licitacion_numero` (`numero_licitacion`),
  KEY `idx_licitacion_dependencia` (`id_dependencia`),
  KEY `idx_licitacion_usuario_responsable` (`id_usuario_responsable`),
  KEY `idx_licitacion_estado_proceso` (`estado_proceso`),
  KEY `idx_licitacion_tipo_procedimiento` (`tipo_procedimiento`),
  CONSTRAINT `ck_licitacion_presupuesto` CHECK (`presupuesto_estimado` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fecha_proceso` (
  `id_fecha_proceso` int(11) NOT NULL AUTO_INCREMENT,
  `id_licitacion` int(11) NOT NULL,
  `tipo_fecha` enum('PUBLICACION_CONVOCATORIA','JUNTA_ACLARACIONES','RECEPCION_PROPUESTAS','APERTURA_PROPUESTAS','FALLO_ADJUDICACION') NOT NULL,
  `fecha_programada` datetime NOT NULL,
  `fecha_real` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_fecha_proceso`),
  UNIQUE KEY `uq_fecha_proceso_licitacion_tipo` (`id_licitacion`,`tipo_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `participacion` (
  `id_participacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `id_licitacion` int(11) NOT NULL,
  `fecha_inscripcion` datetime NOT NULL,
  `estatus` enum('INSCRITO','PROPUESTA_ENVIADA','DESCALIFICADO','GANADOR','NO_GANADOR') NOT NULL DEFAULT 'INSCRITO',
  PRIMARY KEY (`id_participacion`),
  UNIQUE KEY `uq_participacion_proveedor_licitacion` (`id_proveedor`,`id_licitacion`),
  KEY `idx_participacion_licitacion` (`id_licitacion`),
  KEY `idx_participacion_proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `propuesta` (
  `id_propuesta` int(11) NOT NULL AUTO_INCREMENT,
  `id_participacion` int(11) NOT NULL,
  `monto_propuesta` decimal(18,2) DEFAULT NULL,
  `descripcion_tecnica` text DEFAULT NULL,
  `fecha_envio` datetime NOT NULL,
  `cumple_requisitos` tinyint(1) DEFAULT NULL,
  `estatus` enum('RECIBIDA','EN_REVISION','ACEPTADA','RECHAZADA') NOT NULL DEFAULT 'RECIBIDA',
  PRIMARY KEY (`id_propuesta`),
  UNIQUE KEY `uq_propuesta_participacion` (`id_participacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `evaluacion` (
  `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_propuesta` int(11) NOT NULL,
  `id_evaluador` int(11) NOT NULL,
  `puntaje_tecnico` decimal(5,2) DEFAULT NULL,
  `puntaje_economico` decimal(5,2) DEFAULT NULL,
  `puntaje_total` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `dictamen` enum('SOLVENTE','NO_SOLVENTE','DESCALIFICADA') DEFAULT NULL,
  `fecha_evaluacion` datetime NOT NULL,
  PRIMARY KEY (`id_evaluacion`),
  UNIQUE KEY `uq_evaluacion_propuesta` (`id_propuesta`),
  KEY `idx_evaluacion_evaluador` (`id_evaluador`),
  CONSTRAINT `ck_evaluacion_puntaje_tecnico` CHECK (`puntaje_tecnico` IS NULL OR `puntaje_tecnico` >= 0),
  CONSTRAINT `ck_evaluacion_puntaje_economico` CHECK (`puntaje_economico` IS NULL OR `puntaje_economico` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contrato` (
  `id_contrato` int(11) NOT NULL AUTO_INCREMENT,
  `id_licitacion` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `numero_contrato` varchar(50) NOT NULL,
  `monto_contrato` decimal(18,2) NOT NULL,
  `fecha_adjudicacion` date NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estatus` enum('EN_FORMALIZACION','VIGENTE','EN_EJECUCION','CONCLUIDO','RESCINDIDO') NOT NULL DEFAULT 'EN_FORMALIZACION',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_contrato`),
  UNIQUE KEY `uq_contrato_numero` (`numero_contrato`),
  UNIQUE KEY `uq_contrato_licitacion` (`id_licitacion`),
  KEY `idx_contrato_proveedor` (`id_proveedor`),
  CONSTRAINT `ck_contrato_monto` CHECK (`monto_contrato` > 0),
  CONSTRAINT `ck_contrato_fechas` CHECK (`fecha_inicio` IS NULL OR `fecha_fin` IS NULL OR `fecha_fin` >= `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notificacion` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario_destino` int(11) NOT NULL,
  `id_licitacion` int(11) DEFAULT NULL,
  `tipo_notificacion` enum('CONVOCATORIA_PUBLICADA','ACLARACION','RESULTADO_EVALUACION','ADJUDICACION','CAMBIO_ESTADO','GENERAL') NOT NULL,
  `titulo` varchar(300) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_envio` datetime NOT NULL,
  `fecha_lectura` datetime DEFAULT NULL,
  PRIMARY KEY (`id_notificacion`),
  KEY `idx_notificacion_licitacion` (`id_licitacion`),
  KEY `idx_notificacion_usuario_leida` (`id_usuario_destino`,`leida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `historial_cambio` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `tabla_afectada` varchar(50) NOT NULL,
  `id_registro_afectado` int(11) NOT NULL,
  `accion` enum('CREAR','ACTUALIZAR','ELIMINAR') NOT NULL,
  `valores_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_anteriores`)),
  `valores_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_nuevos`)),
  `ip_origen` varchar(45) DEFAULT NULL,
  `fecha_accion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `idx_historial_tabla_registro` (`tabla_afectada`,`id_registro_afectado`),
  KEY `idx_historial_fecha_accion` (`fecha_accion`),
  KEY `idx_historial_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documento` (
  `id_documento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_archivo` varchar(300) NOT NULL,
  `ruta_almacenamiento` varchar(500) NOT NULL,
  `tipo_documento` enum('BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACTA_PROCESO','PROPUESTA_TECNICA','PROPUESTA_ECONOMICA','DOC_COMPLEMENTARIA','DOC_LEGAL_PROVEEDOR','DOC_CONTRATO','ACLARACION','DICTAMEN') NOT NULL,
  `id_licitacion` int(11) DEFAULT NULL,
  `id_propuesta` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_contrato` int(11) DEFAULT NULL,
  `id_evaluacion` int(11) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `subido_por` int(11) NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp(),
  `tamano_bytes` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id_documento`),
  KEY `idx_documento_licitacion` (`id_licitacion`),
  KEY `idx_documento_propuesta` (`id_propuesta`),
  KEY `idx_documento_proveedor` (`id_proveedor`),
  KEY `idx_documento_contrato` (`id_contrato`),
  KEY `idx_documento_evaluacion` (`id_evaluacion`),
  KEY `idx_documento_subido_por` (`subido_por`),
  KEY `idx_documento_tipo_documento` (`tipo_documento`),
  CONSTRAINT `ck_documento_version` CHECK (`version` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `trg_documento_contexto_bi`
BEFORE INSERT ON `documento`
FOR EACH ROW
BEGIN
  IF NEW.`id_licitacion` IS NULL
     AND NEW.`id_propuesta` IS NULL
     AND NEW.`id_proveedor` IS NULL
     AND NEW.`id_contrato` IS NULL
     AND NEW.`id_evaluacion` IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'documento requiere al menos un contexto relacionado';
  END IF;
END$$

CREATE TRIGGER `trg_documento_contexto_bu`
BEFORE UPDATE ON `documento`
FOR EACH ROW
BEGIN
  IF NEW.`id_licitacion` IS NULL
     AND NEW.`id_propuesta` IS NULL
     AND NEW.`id_proveedor` IS NULL
     AND NEW.`id_contrato` IS NULL
     AND NEW.`id_evaluacion` IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'documento requiere al menos un contexto relacionado';
  END IF;
END$$
DELIMITER ;

ALTER TABLE `proveedor`
  ADD CONSTRAINT `fk_proveedor_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `licitacion`
  ADD CONSTRAINT `fk_licitacion_dependencia`
    FOREIGN KEY (`id_dependencia`) REFERENCES `dependencia` (`id_dependencia`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_licitacion_usuario_responsable`
    FOREIGN KEY (`id_usuario_responsable`) REFERENCES `usuario` (`id_usuario`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `fecha_proceso`
  ADD CONSTRAINT `fk_fecha_proceso_licitacion`
    FOREIGN KEY (`id_licitacion`) REFERENCES `licitacion` (`id_licitacion`)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `participacion`
  ADD CONSTRAINT `fk_participacion_proveedor`
    FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_participacion_licitacion`
    FOREIGN KEY (`id_licitacion`) REFERENCES `licitacion` (`id_licitacion`)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `propuesta`
  ADD CONSTRAINT `fk_propuesta_participacion`
    FOREIGN KEY (`id_participacion`) REFERENCES `participacion` (`id_participacion`)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `evaluacion`
  ADD CONSTRAINT `fk_evaluacion_propuesta`
    FOREIGN KEY (`id_propuesta`) REFERENCES `propuesta` (`id_propuesta`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evaluacion_usuario`
    FOREIGN KEY (`id_evaluador`) REFERENCES `usuario` (`id_usuario`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `contrato`
  ADD CONSTRAINT `fk_contrato_licitacion`
    FOREIGN KEY (`id_licitacion`) REFERENCES `licitacion` (`id_licitacion`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_contrato_proveedor`
    FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `notificacion`
  ADD CONSTRAINT `fk_notificacion_usuario`
    FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuario` (`id_usuario`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notificacion_licitacion`
    FOREIGN KEY (`id_licitacion`) REFERENCES `licitacion` (`id_licitacion`)
    ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE `historial_cambio`
  ADD CONSTRAINT `fk_historial_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `documento`
  ADD CONSTRAINT `fk_documento_licitacion`
    FOREIGN KEY (`id_licitacion`) REFERENCES `licitacion` (`id_licitacion`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_documento_propuesta`
    FOREIGN KEY (`id_propuesta`) REFERENCES `propuesta` (`id_propuesta`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_documento_proveedor`
    FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_documento_contrato`
    FOREIGN KEY (`id_contrato`) REFERENCES `contrato` (`id_contrato`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_documento_evaluacion`
    FOREIGN KEY (`id_evaluacion`) REFERENCES `evaluacion` (`id_evaluacion`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_documento_usuario`
    FOREIGN KEY (`subido_por`) REFERENCES `usuario` (`id_usuario`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

INSERT INTO `usuario` (`id_usuario`, `nombre`, `email`, `contrasena_hash`, `rol`, `activo`, `fecha_registro`, `ultimo_acceso`) VALUES
(1, 'Joaquin Hernandez', 'joaqher@gmail.com', 'joaq1234', 'PUBLICO', 1, '2026-03-18 00:00:00', '2026-03-18 00:00:00');

ALTER TABLE `usuario` AUTO_INCREMENT = 2;

COMMIT;
