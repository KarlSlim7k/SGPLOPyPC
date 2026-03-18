-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql303.infinityfree.com
-- Tiempo de generación: 18-03-2026 a las 12:13:04
-- Versión del servidor: 11.4.10-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_39815580_sgplopypc`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contrato`
--

CREATE TABLE `contrato` (
  `id_contrato` int(11) NOT NULL,
  `id_licitacion` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `numero_contrato` varchar(50) NOT NULL,
  `monto_contrato` decimal(18,2) NOT NULL,
  `fecha_adjudicacion` date NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estatus` enum('EN_FORMALIZACION','VIGENTE','EN_EJECUCION','CONCLUIDO','RESCINDIDO') NOT NULL DEFAULT 'EN_FORMALIZACION',
  `fecha_creacion` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dependencia`
--

CREATE TABLE `dependencia` (
  `id_dependencia` int(11) NOT NULL,
  `nombre` varchar(250) NOT NULL,
  `siglas` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email_contacto` varchar(200) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documento`
--

CREATE TABLE `documento` (
  `id_documento` int(11) NOT NULL,
  `nombre_archivo` varchar(300) NOT NULL,
  `ruta_almacenamiento` varchar(500) NOT NULL,
  `tipo_documento` enum('BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACTA_PROCESO','PROPUESTA_TECNICA','PROPUESTA_ECONOMICA','DOC_COMPLEMENTARIA','DOC_LEGAL_PROVEEDOR','DOC_CONTRATO','ACLARACION','DICTAMEN') NOT NULL,
  `id_licitacion` int(11) DEFAULT NULL,
  `id_propuesta` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_contrato` int(11) DEFAULT NULL,
  `id_evaluacion` int(11) DEFAULT NULL,
  `VERSION` int(11) NOT NULL DEFAULT 1,
  `subido_por` int(11) NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp(),
  `tamano_bytes` bigint(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluacion`
--

CREATE TABLE `evaluacion` (
  `id_evaluacion` int(11) NOT NULL,
  `id_propuesta` int(11) NOT NULL,
  `id_evaluador` int(11) NOT NULL,
  `puntaje_tecnico` decimal(5,2) DEFAULT NULL,
  `puntaje_economico` decimal(5,2) DEFAULT NULL,
  `puntaje_total` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `dictamen` enum('SOLVENTE','NO_SOLVENTE','DESCALIFICADA') DEFAULT NULL,
  `fecha_evaluacion` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fecha_proceso`
--

CREATE TABLE `fecha_proceso` (
  `id_fecha_proceso` int(11) NOT NULL,
  `id_licitacion` int(11) NOT NULL,
  `tipo_fecha` enum('PUBLICACION_CONVOCATORIA','JUNTA_ACLARACIONES','RECEPCION_PROPUESTAS','APERTURA_PROPUESTAS','FALLO_ADJUDICACION') NOT NULL,
  `fecha_programada` datetime NOT NULL,
  `fecha_real` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_cambio`
--

CREATE TABLE `historial_cambio` (
  `id_historial` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tabla_afectada` varchar(50) NOT NULL,
  `id_registro_afectado` int(11) NOT NULL,
  `accion` enum('CREAR','ACTUALIZAR','ELIMINAR') NOT NULL,
  `valores_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `licitacion`
--

CREATE TABLE `licitacion` (
  `id_licitacion` int(11) NOT NULL,
  `numero_licitacion` varchar(50) NOT NULL,
  `id_dependencia` int(11) NOT NULL,
  `id_usuario_responsable` int(11) NOT NULL,
  `tipo_procedimiento` enum('LICITACION_PUBLICA','INVITACION_RESTRINGIDA','ADJUDICACION_DIRECTA') NOT NULL,
  `descripcion_proyecto` text NOT NULL,
  `presupuesto_estimado` decimal(18,2) NOT NULL,
  `ubicacion_proyecto` varchar(500) DEFAULT NULL,
  `estado_proceso` enum('BORRADOR','PUBLICADA','EN_ACLARACIONES','RECEPCION_PROPUESTAS','EN_EVALUACION','ADJUDICADA','DESIERTA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion`
--

CREATE TABLE `notificacion` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario_destino` int(11) NOT NULL,
  `id_licitacion` int(11) DEFAULT NULL,
  `tipo_notificacion` enum('CONVOCATORIA_PUBLICADA','ACLARACION','RESULTADO_EVALUACION','ADJUDICACION','CAMBIO_ESTADO','GENERAL') NOT NULL,
  `titulo` varchar(300) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_envio` datetime NOT NULL,
  `fecha_lectura` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participacion`
--

CREATE TABLE `participacion` (
  `id_participacion` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_licitacion` int(11) NOT NULL,
  `fecha_inscripcion` datetime NOT NULL,
  `estatus` enum('INSCRITO','PROPUESTA_ENVIADA','DESCALIFICADO','GANADOR','NO_GANADOR') NOT NULL DEFAULT 'INSCRITO'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuesta`
--

CREATE TABLE `propuesta` (
  `id_propuesta` int(11) NOT NULL,
  `id_participacion` int(11) NOT NULL,
  `monto_propuesta` decimal(18,2) DEFAULT NULL,
  `descripcion_tecnica` text DEFAULT NULL,
  `fecha_envio` datetime NOT NULL,
  `cumple_requisitos` tinyint(1) DEFAULT NULL,
  `estatus` enum('RECIBIDA','EN_REVISION','ACEPTADA','RECHAZADA') NOT NULL DEFAULT 'RECIBIDA'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_empresa` varchar(250) NOT NULL,
  `representante_legal` varchar(200) NOT NULL,
  `registro_fiscal` varchar(20) NOT NULL,
  `domicilio` text NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `especialidad` varchar(300) DEFAULT NULL,
  `estatus` enum('PENDIENTE','VALIDADO','RECHAZADO','SUSPENDIDO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `rol` enum('PUBLICO','PROVEEDOR','ADMINISTRADOR') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `email`, `contrasena_hash`, `rol`, `activo`, `fecha_registro`, `ultimo_acceso`) VALUES
(1, 'Joaquín Hernández', 'joaqher@gmail.com', 'joaq1234', 'PUBLICO', 1, '2026-03-18 00:00:00', '2026-03-18 00:00:00');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contrato`
--
ALTER TABLE `contrato`
  ADD PRIMARY KEY (`id_contrato`),
  ADD UNIQUE KEY `numero_contrato` (`numero_contrato`),
  ADD UNIQUE KEY `id_licitacion` (`id_licitacion`),
  ADD KEY `fk_cont_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `dependencia`
--
ALTER TABLE `dependencia`
  ADD PRIMARY KEY (`id_dependencia`),
  ADD UNIQUE KEY `email_contacto` (`email_contacto`);

--
-- Indices de la tabla `documento`
--
ALTER TABLE `documento`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `fk_doc_licitacion` (`id_licitacion`),
  ADD KEY `fk_doc_propuesta` (`id_propuesta`),
  ADD KEY `fk_doc_proveedor` (`id_proveedor`),
  ADD KEY `fk_doc_contrato` (`id_contrato`),
  ADD KEY `fk_doc_evaluacion` (`id_evaluacion`),
  ADD KEY `fk_doc_usuario` (`subido_por`);

--
-- Indices de la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD UNIQUE KEY `id_propuesta` (`id_propuesta`),
  ADD KEY `fk_eval_usuario` (`id_evaluador`);

--
-- Indices de la tabla `fecha_proceso`
--
ALTER TABLE `fecha_proceso`
  ADD PRIMARY KEY (`id_fecha_proceso`),
  ADD UNIQUE KEY `uq_licitacion_tipo` (`id_licitacion`,`tipo_fecha`);

--
-- Indices de la tabla `licitacion`
--
ALTER TABLE `licitacion`
  ADD PRIMARY KEY (`id_licitacion`),
  ADD UNIQUE KEY `numero_licitacion` (`numero_licitacion`),
  ADD KEY `fk_licitacion_dependencia` (`id_dependencia`),
  ADD KEY `fk_licitacion_usuario` (`id_usuario_responsable`);

--
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `fk_notif_usuario` (`id_usuario_destino`),
  ADD KEY `fk_notif_licitacion` (`id_licitacion`);

--
-- Indices de la tabla `participacion`
--
ALTER TABLE `participacion`
  ADD PRIMARY KEY (`id_participacion`),
  ADD UNIQUE KEY `id_proveedor` (`id_proveedor`,`id_licitacion`),
  ADD KEY `fk_part_licitacion` (`id_licitacion`);

--
-- Indices de la tabla `propuesta`
--
ALTER TABLE `propuesta`
  ADD PRIMARY KEY (`id_propuesta`),
  ADD UNIQUE KEY `id_participacion` (`id_participacion`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD UNIQUE KEY `registro_fiscal` (`registro_fiscal`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contrato`
--
ALTER TABLE `contrato`
  MODIFY `id_contrato` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `dependencia`
--
ALTER TABLE `dependencia`
  MODIFY `id_dependencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documento`
--
ALTER TABLE `documento`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fecha_proceso`
--
ALTER TABLE `fecha_proceso`
  MODIFY `id_fecha_proceso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_cambio`
--
ALTER TABLE `historial_cambio`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `licitacion`
--
ALTER TABLE `licitacion`
  MODIFY `id_licitacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `participacion`
--
ALTER TABLE `participacion`
  MODIFY `id_participacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propuesta`
--
ALTER TABLE `propuesta`
  MODIFY `id_propuesta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
