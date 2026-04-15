-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-03-2026 a las 18:52:09
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
-- Base de datos: `emermedica`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `coordinador_cedula` varchar(10) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `estado` enum('Activa','Inactiva') NOT NULL DEFAULT 'Activa',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_base_asesores`
--

CREATE TABLE `asignacion_base_asesores` (
  `id_base_asesor` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `estado` enum('Activa','Inactiva') DEFAULT 'Activa',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `base_clientes`
--

CREATE TABLE `base_clientes` (
  `id_base` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `total_clientes` int(11) NOT NULL,
  `TOTAL_OBLIGACIONES` int(11) NOT NULL,
  `creado_por` varchar(10) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carga_csv_tareas`
--

CREATE TABLE `carga_csv_tareas` (
  `id_carga` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `coordinador_cedula` varchar(10) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `nombre_archivo` varchar(100) NOT NULL,
  `cedulas_subidas` int(11) NOT NULL,
  `cedulas_encontradas` int(11) NOT NULL,
  `cedulas_no_encontradas` int(11) NOT NULL,
  `tarea_id` int(11) NOT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `cedula` varchar(10) NOT NULL,
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
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo',
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
  `gestionado` enum('Si','No') DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id_factura` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `numero_factura` varchar(100) NOT NULL,
  `numero_contrato` varchar(100) NOT NULL,
  `rmt` varchar(10) NOT NULL,
  `saldo` decimal(15,2) NOT NULL,
  `franja` varchar(50) NOT NULL,
  `dias_mora` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_gestion`
--

CREATE TABLE `historial_gestion` (
  `id_gestion` int(11) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `tarea_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `canal_contacto` varchar(50) NOT NULL,
  `nivel1_tipo` varchar(100) NOT NULL,
  `nivel2_tipo` varchar(100) NOT NULL,
  `nivel3_tipo` varchar(100) NOT NULL,
  `nivel4_tipo` varchar(100) NOT NULL,
  `observaciones` mediumtext DEFAULT NULL,
  `telefono_contacto` varchar(10) NOT NULL,
  `duracion_segundos` int(11) NOT NULL,
  `llamada_telefonica` enum('Si','No') DEFAULT NULL,
  `email` enum('Si','No') DEFAULT NULL,
  `sms` enum('Si','No') DEFAULT NULL,
  `correo_fisico` enum('Si','No') DEFAULT NULL,
  `whatsapp` enum('Si','No') DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `valor_pago` decimal(15,2) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL,
  `nombre_tarea` varchar(100) NOT NULL,
  `coordinador_cedula` varchar(10) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `estado` enum('Pendiente','En progreso','Completa','Cancelada') DEFAULT 'Pendiente',
  `clientes_asignados` text DEFAULT NULL,
  `obligaciones_asignados` text DEFAULT NULL,
  `base_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiempos`
--

CREATE TABLE `tiempos` (
  `id_tiempo` int(11) NOT NULL,
  `asesor_cedula` varchar(10) NOT NULL,
  `fecha` date DEFAULT curdate(),
  `tipo_registro` enum('sesion','break','almuerzo','baño','capacitacion','retroalimentacion','gestion') NOT NULL,
  `hora_inicio` datetime DEFAULT current_timestamp(),
  `hora_fin` datetime DEFAULT NULL,
  `estado` enum('activa','finalizada') DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `cedula` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña_hash` varchar(250) NOT NULL,
  `extension` varchar(4) NOT NULL,
  `sip_password` varchar(50) NOT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `rol` enum('administrador','coordinador','asesor') NOT NULL DEFAULT 'asesor',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `fk_coordinador_usuario` (`coordinador_cedula`),
  ADD KEY `fk_asesor_usuario` (`asesor_cedula`);

--
-- Indices de la tabla `asignacion_base_asesores`
--
ALTER TABLE `asignacion_base_asesores`
  ADD PRIMARY KEY (`id_base_asesor`),
  ADD KEY `fk_asignacion_base` (`base_id`),
  ADD KEY `fk_asignacion_usuario` (`asesor_cedula`);

--
-- Indices de la tabla `base_clientes`
--
ALTER TABLE `base_clientes`
  ADD PRIMARY KEY (`id_base`);

--
-- Indices de la tabla `carga_csv_tareas`
--
ALTER TABLE `carga_csv_tareas`
  ADD PRIMARY KEY (`id_carga`),
  ADD KEY `fk_base_carga_csv` (`base_id`),
  ADD KEY `fk_coordinador_carga_csv` (`coordinador_cedula`),
  ADD KEY `fk_asesor_carga_csv` (`asesor_cedula`),
  ADD KEY `fk_tareas_carga_csv` (`tarea_id`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `fk_base_cliente` (`base_id`);

--
-- Indices de la tabla `detalle_tareas`
--
ALTER TABLE `detalle_tareas`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_tareas` (`tarea_id`),
  ADD KEY `fk_detalle_cliente` (`cliente_id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `fk_factura_clientes` (`cliente_id`);

--
-- Indices de la tabla `historial_gestion`
--
ALTER TABLE `historial_gestion`
  ADD PRIMARY KEY (`id_gestion`),
  ADD KEY `fk_asesor_gestion` (`asesor_cedula`),
  ADD KEY `fk_tarea_gestion` (`tarea_id`),
  ADD KEY `fk_cliente_gestion` (`cliente_id`),
  ADD KEY `fk_factura_gestion` (`factura_id`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `fk_tareas_coordinador` (`coordinador_cedula`),
  ADD KEY `fk_tareas_asesor` (`asesor_cedula`),
  ADD KEY `fk_tareas_base_clientes` (`base_id`);

--
-- Indices de la tabla `tiempos`
--
ALTER TABLE `tiempos`
  ADD PRIMARY KEY (`id_tiempo`),
  ADD KEY `fk_tiempos_usuarios` (`asesor_cedula`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`cedula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
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
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_tareas`
--
ALTER TABLE `detalle_tareas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_gestion`
--
ALTER TABLE `historial_gestion`
  MODIFY `id_gestion` int(11) NOT NULL AUTO_INCREMENT;

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
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `fk_asesor_usuario` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_coordinador_usuario` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `asignacion_base_asesores`
--
ALTER TABLE `asignacion_base_asesores`
  ADD CONSTRAINT `fk_asignacion_base` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_asignacion_usuario` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `carga_csv_tareas`
--
ALTER TABLE `carga_csv_tareas`
  ADD CONSTRAINT `fk_asesor_carga_csv` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_base_carga_csv` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_coordinador_carga_csv` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_tareas_carga_csv` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id_tarea`);

--
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `fk_base_cliente` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`);

--
-- Filtros para la tabla `detalle_tareas`
--
ALTER TABLE `detalle_tareas`
  ADD CONSTRAINT `fk_detalle_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id_cliente`),
  ADD CONSTRAINT `fk_detalle_tareas` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id_tarea`);

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_factura_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `historial_gestion`
--
ALTER TABLE `historial_gestion`
  ADD CONSTRAINT `fk_asesor_gestion` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_cliente_gestion` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id_cliente`),
  ADD CONSTRAINT `fk_factura_gestion` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id_factura`),
  ADD CONSTRAINT `fk_tarea_gestion` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id_tarea`);

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `fk_tareas_asesor` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `fk_tareas_base_clientes` FOREIGN KEY (`base_id`) REFERENCES `base_clientes` (`id_base`),
  ADD CONSTRAINT `fk_tareas_coordinador` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `tiempos`
--
ALTER TABLE `tiempos`
  ADD CONSTRAINT `fk_tiempos_usuarios` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
