-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-04-2026 a las 15:47:19
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `emermedica_cobranza`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acuerdos`
--

CREATE TABLE `acuerdos` (
  `id_acuerdos` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `tipo_acuerdo` enum('total','cuotas','comite') NOT NULL,
  `valor_acuerdo` decimal(15,2) DEFAULT 0.00,
  `fecha_pago` date DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_cordinador`
--

CREATE TABLE `asignaciones_cordinador` (
  `id_asignacion` int(11) NOT NULL,
  `cordinador_cedula` varchar(10) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_base_asesores`
--

CREATE TABLE `asignacion_base_asesores` (
  `id_base_asesor` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `asesor_cedula` varchar(10) DEFAULT NULL,
  `estado` enum('activa','inactiva') DEFAULT 'activa',
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `base_clientes`
--

CREATE TABLE `base_clientes` (
  `id_base` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `total_clientes` int(11) NOT NULL,
  `total_obligaciones` int(11) NOT NULL,
  `creado_por` varchar(10) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carga_csv_tareas`
--

CREATE TABLE `carga_csv_tareas` (
  `id_carga` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `coordinador_cedula` varchar(10) NOT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `cedulas_subidas` int(11) DEFAULT NULL,
  `cedulas_encontradas` int(11) DEFAULT NULL,
  `cedulas_no_encontradas` int(11) DEFAULT NULL,
  `tarea_id` int(11) DEFAULT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `cedula` varchar(12) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ciudad` varchar(50) NOT NULL,
  `tel1` varchar(10) NOT NULL,
  `tel2` varchar(10) NOT NULL,
  `tel3` varchar(10) NOT NULL,
  `tel4` varchar(10) NOT NULL,
  `tel5` varchar(10) NOT NULL,
  `tel6` varchar(10) NOT NULL,
  `tel7` varchar(10) NOT NULL,
  `tel8` varchar(10) NOT NULL,
  `tel9` varchar(10) NOT NULL,
  `tel10` varchar(10) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_tareas`
--

CREATE TABLE `detalle_tareas` (
  `id_detalle` int(11) NOT NULL,
  `tarea_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `gestionado` enum('si','no') DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_gestiones`
--

CREATE TABLE `historial_gestiones` (
  `id_gestion` int(11) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `obligacion_id` int(11) NOT NULL,
  `telefono_contacto` varchar(10) NOT NULL,
  `forma_contacto` varchar(10) NOT NULL,
  `tipo_contacto` varchar(100) NOT NULL,
  `resultado_contacto` varchar(100) NOT NULL,
  `razon_especifica` varchar(100) NOT NULL,
  `observaciones` mediumtext DEFAULT NULL,
  `llamada_telefonica` enum('si','no') DEFAULT 'no',
  `email` enum('si','no') DEFAULT 'no',
  `sms` enum('si','no') DEFAULT 'no',
  `correo_fisico` enum('si','no') DEFAULT 'no',
  `whatsap` enum('si','no') DEFAULT 'no',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `duracion_segundos` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `obligaciones`
--

CREATE TABLE `obligaciones` (
  `id_obligacion` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `numero_factura` varchar(70) NOT NULL,
  `rmt` varchar(10) NOT NULL,
  `numero_contrato` varchar(50) NOT NULL,
  `saldo` decimal(15,2) NOT NULL DEFAULT 0.00,
  `dias_mora` int(11) NOT NULL,
  `franja` varchar(50) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL,
  `nombre_tarea` varchar(70) NOT NULL,
  `base_id` int(11) NOT NULL,
  `coordinador_cedula` varchar(10) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `estado` enum('pendiente','en progreso','completa','cancelada') DEFAULT 'pendiente',
  `clientes_asignados` text DEFAULT NULL,
  `obligaciones_asignadas` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_completa` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiempos`
--

CREATE TABLE `tiempos` (
  `id_tiempo` int(11) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `tipo_registro` enum('sesion','break','almuerzo','baño','capacitacion','retroalimentacion','gestion') DEFAULT NULL,
  `hora_inicio` datetime DEFAULT NULL,
  `hora_fin` datetime DEFAULT NULL,
  `estado` enum('activa','finalizada') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `cedula` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena_hash` varchar(250) NOT NULL,
  `estension` varchar(5) NOT NULL,
  `sip_password` varchar(50) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `rol` enum('administrador','cordinador','asesor') NOT NULL DEFAULT 'asesor',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_control_asignaciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_control_asignaciones` (
`nombre_base` varchar(100)
,`nombre_asesor` varchar(50)
,`estado_asignacion` enum('activa','inactiva')
,`fecha_asignacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_control_asignaciones`
--
DROP TABLE IF EXISTS `v_control_asignaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_control_asignaciones`  AS SELECT `b`.`nombre` AS `nombre_base`, `u`.`nombre` AS `nombre_asesor`, `aba`.`estado` AS `estado_asignacion`, `aba`.`fecha_asignacion` AS `fecha_asignacion` FROM ((`asignacion_base_asesores` `aba` join `base_clientes` `b` on(`aba`.`base_id` = `b`.`id_base`)) join `usuarios` `u` on(`aba`.`asesor_cedula` = `u`.`cedula`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acuerdos`
--
ALTER TABLE `acuerdos`
  ADD PRIMARY KEY (`id_acuerdos`),
  ADD KEY `fk_acuerdo_gestion` (`gestion_id`);

--
-- Indices de la tabla `asignaciones_cordinador`
--
ALTER TABLE `asignaciones_cordinador`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `fk_asignacion_asesor` (`asesor_cedula`),
  ADD KEY `idx_cordinador_estado` (`cordinador_cedula`,`estado`);

--
-- Indices de la tabla `asignacion_base_asesores`
--
ALTER TABLE `asignacion_base_asesores`
  ADD PRIMARY KEY (`id_base_asesor`),
  ADD KEY `fk_asignacion_base` (`base_id`),
  ADD KEY `idx_asesor_base_activa` (`asesor_cedula`,`estado`,`base_id`);

--
-- Indices de la tabla `base_clientes`
--
ALTER TABLE `base_clientes`
  ADD PRIMARY KEY (`id_base`),
  ADD KEY `idn_creador_estado` (`creado_por`,`estado`);

--
-- Indices de la tabla `carga_csv_tareas`
--
ALTER TABLE `carga_csv_tareas`
  ADD PRIMARY KEY (`id_carga`),
  ADD KEY `fk_base_carga` (`base_id`),
  ADD KEY `fk_base_asesor` (`asesor_cedula`),
  ADD KEY `fk_base_cordinador` (`coordinador_cedula`),
  ADD KEY `fk_carga_csv_tarea_rel` (`tarea_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `idx_base_cedula` (`base_id`,`cedula`),
  ADD KEY `idx_ciudad` (`ciudad`),
  ADD KEY `idx_estado_cliente` (`estado`);

--
-- Indices de la tabla `detalle_tareas`
--
ALTER TABLE `detalle_tareas`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_tarea_detalle` (`tarea_id`),
  ADD KEY `fk_cliente_detalle` (`cliente_id`);

--
-- Indices de la tabla `historial_gestiones`
--
ALTER TABLE `historial_gestiones`
  ADD PRIMARY KEY (`id_gestion`),
  ADD KEY `fk_gest_asesor` (`asesor_cedula`),
  ADD KEY `fk_gest_cliente` (`cliente_id`),
  ADD KEY `fk_gest_obligacion` (`obligacion_id`);

--
-- Indices de la tabla `obligaciones`
--
ALTER TABLE `obligaciones`
  ADD PRIMARY KEY (`id_obligacion`),
  ADD KEY `fk_obligacion_cliente` (`cliente_id`),
  ADD KEY `idx_identificadores_deuda` (`numero_factura`,`numero_contrato`),
  ADD KEY `idx_segmentacion_cobranza` (`franja`,`dias_mora`,`saldo`),
  ADD KEY `idx_fechas_obligacion` (`fecha_creacion`,`fecha_actualizacion`),
  ADD KEY `idx_relacion_base_cliente` (`base_id`,`cliente_id`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `fk_base_tarea` (`base_id`),
  ADD KEY `fk_coordinador_tarea` (`coordinador_cedula`),
  ADD KEY `fk_asesor_tarea` (`asesor_cedula`);

--
-- Indices de la tabla `tiempos`
--
ALTER TABLE `tiempos`
  ADD PRIMARY KEY (`id_tiempo`),
  ADD KEY `fk_usuarios_tiempo` (`asesor_cedula`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`cedula`),
  ADD UNIQUE KEY `uk_usuario` (`usuario`),
  ADD KEY `idx_estado_rol` (`estado`,`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acuerdos`
--
ALTER TABLE `acuerdos`
  MODIFY `id_acuerdos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones_cordinador`
--
ALTER TABLE `asignaciones_cordinador`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignacion_base_asesores`
--
ALTER TABLE `asignacion_base_asesores`
  MODIFY `id_base_asesor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `base_clientes`
--
ALTER TABLE `base_clientes`
  MODIFY `id_base` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carga_csv_tareas`
--
ALTER TABLE `carga_csv_tareas`
  MODIFY `id_carga` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_tareas`
--
ALTER TABLE `detalle_tareas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_gestiones`
--
ALTER TABLE `historial_gestiones`
  MODIFY `id_gestion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `obligaciones`
--
ALTER TABLE `obligaciones`
  MODIFY `id_obligacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tiempos`
--
ALTER TABLE `tiempos`
  MODIFY `id_tiempo` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acuerdos`
--
ALTER TABLE `acuerdos`
  ADD CONSTRAINT `fk_acuerdo_gestion` FOREIGN KEY (`gestion_id`) REFERENCES `historial_gestiones` (`id_gestion`);

--
-- Filtros para la tabla `asignaciones_cordinador`
--
ALTER TABLE `asignaciones_cordinador`
  ADD CONSTRAINT `fk_asignacion_asesor` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_asignacion_cordinador` FOREIGN KEY (`cordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `asignacion_base_asesores`
--
ALTER TABLE `asignacion_base_asesores`
  ADD CONSTRAINT `fk_asignacion_base` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_asignacion_usuario` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `base_clientes`
--
ALTER TABLE `base_clientes`
  ADD CONSTRAINT `fk_base_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`cedula`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `carga_csv_tareas`
--
ALTER TABLE `carga_csv_tareas`
  ADD CONSTRAINT `fk_base_asesor` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_base_carga` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_base_cordinador` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_carga_csv_tarea_rel` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id_tarea`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cliente_base` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_tareas`
--
ALTER TABLE `detalle_tareas`
  ADD CONSTRAINT `fk_cliente_detalle` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `fk_tarea_detalle` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id_tarea`);

--
-- Filtros para la tabla `historial_gestiones`
--
ALTER TABLE `historial_gestiones`
  ADD CONSTRAINT `fk_gest_asesor` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_gest_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `fk_gest_obligacion` FOREIGN KEY (`obligacion_id`) REFERENCES `obligaciones` (`id_obligacion`);

--
-- Filtros para la tabla `obligaciones`
--
ALTER TABLE `obligaciones`
  ADD CONSTRAINT `fk_obligacion_base` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_obligacion_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `fk_asesor_tarea` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_base_tarea` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_coordinador_tarea` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `tiempos`
--
ALTER TABLE `tiempos`
  ADD CONSTRAINT `fk_usuarios_tiempo` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
