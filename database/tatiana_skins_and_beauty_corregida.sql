-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-11-2026 a las 12:41:56
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
-- Base de datos: `tatiana_skins_and_beauty_corregida`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_producto`
--

CREATE TABLE `categoria_producto` (
  `id_categoria` varchar(10) NOT NULL,
  `nombre_categoria` varchar(50) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria_producto`
--

INSERT INTO `categoria_producto` (`id_categoria`, `nombre_categoria`, `tipo`) VALUES
('CAT01', 'Cuidado Facial', 'Cosmética'),
('CAT02', 'Cuidado Corporal', 'Cosmética');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_servicio`
--

CREATE TABLE `categoria_servicio` (
  `id_categoriaS` varchar(10) NOT NULL,
  `nombre_cservicio` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria_servicio`
--

INSERT INTO `categoria_servicio` (`id_categoriaS`, `nombre_cservicio`) VALUES
('CS01', 'depilaciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL,
  `fecha_compra` date DEFAULT NULL,
  `nro_compra` varchar(20) DEFAULT NULL,
  `total_pagar` decimal(10,2) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL,
  `id_metodoPago` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`id_compra`, `fecha_compra`, `nro_compra`, `total_pagar`, `id_usuario`, `id_metodoPago`) VALUES
(1, '2026-10-19', 'C-000001', 250.00, 'CLI0001', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `compras_completas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `compras_completas` (
`id_compra` int(11)
,`nro_compra` varchar(20)
,`fecha_compra` date
,`total_pagar` decimal(10,2)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`nombre_metodoPago` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `compras_por_metodo_pago`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `compras_por_metodo_pago` (
`nombre_metodoPago` varchar(50)
,`id_compra` int(11)
,`nro_compra` varchar(20)
,`fecha_compra` date
,`total_pagar` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `compras_por_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `compras_por_usuario` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`id_compra` int(11)
,`nro_compra` varchar(20)
,`fecha_compra` date
,`total_pagar` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta_usuario`
--

CREATE TABLE `cuenta_usuario` (
  `id_cuentaUsuario` int(11) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `id_rol` varchar(10) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuenta_usuario`
--

INSERT INTO `cuenta_usuario` (`id_cuentaUsuario`, `correo`, `password`, `id_rol`, `id_usuario`) VALUES
(1, 'admin.test@test.bo', '$2y$10$giIeZgzp0iUt40grmIPXTegLPwbHEB/rYdp120cRQ7fJEDXriD7Fm', 'ROL01', 'ADM001'),
(2, 'quispeliz143@gmail.com', '$2y$10$iHb/rElQP17UgxbrnXjmYOSXQ/aTZ5oxJTOhX3Nxa0rSNEavz.cJi', 'ROL03', 'CLI0001'),
(3, 'morenosamantha016@gmail.com', '3Zf4ONFiZepW03KNXmKnK5xEB4rLSATZrGVQ0nzTcxs=', 'ROL03', 'CLI0002'),
(4, 'luzzegarra@gmail.com', '4CwETg7A9MqjyL4ZxqbMxAmV7rjbk8wokqQ6uYJh4Gc=', 'ROL03', 'CLI0003'),
(5, 'empleado@empleado.com', '$2y$10$FJnAWRPqr/ZWliLFGxSw0.qVBwoQDxl0azxfpObuQcJKHixieOlGu', 'ROL02', 'EMP001');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compra`
--

CREATE TABLE `detalle_compra` (
  `id_detalle` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `id_compra` int(11) DEFAULT NULL,
  `id_producto` varchar(10) DEFAULT NULL,
  `id_servicio` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_compra`
--

INSERT INTO `detalle_compra` (`id_detalle`, `cantidad`, `precio_unitario`, `subtotal`, `id_compra`, `id_producto`, `id_servicio`) VALUES
(1, 1, 250.00, 250.00, 1, 'PROD02', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `detalle_compras`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `detalle_compras` (
`id_compra` int(11)
,`nro_compra` varchar(20)
,`fecha_compra` date
,`nombre_producto` varchar(80)
,`cantidad` int(11)
,`precio_unitario` decimal(10,2)
,`subtotal` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_reserva`
--

CREATE TABLE `detalle_reserva` (
  `id_detalle` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `id_servicio` varchar(10) DEFAULT NULL,
  `id_reserva` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_reserva`
--

INSERT INTO `detalle_reserva` (`id_detalle`, `cantidad`, `precio_unitario`, `subtotal`, `id_servicio`, `id_reserva`) VALUES
(1, 1, 140.00, 140.00, 'SERV02', 1),
(2, 1, 100.00, 100.00, 'SERV03', 2),
(3, 1, 15.00, 15.00, 'SERV01', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_cliente`
--

CREATE TABLE `historial_cliente` (
  `id_historial` int(11) NOT NULL,
  `alergias` text DEFAULT NULL,
  `objetivo_piel` text DEFAULT NULL,
  `abierto_recomendaciones` tinyint(1) DEFAULT NULL,
  `rutina_actual` text DEFAULT NULL,
  `observaciones_sesion` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT NULL,
  `id_reserva` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_cliente`
--

INSERT INTO `historial_cliente` (`id_historial`, `alergias`, `objetivo_piel`, `abierto_recomendaciones`, `rutina_actual`, `observaciones_sesion`, `fecha_registro`, `id_reserva`) VALUES
(1, NULL, NULL, 0, NULL, 'Notas de reserva: Tratamiento con cremas nivea', '2026-10-21 20:14:57', 1),
(2, 'ninguna', 'Piel: Seca · Objetivos: Hidratación', 1, '', '', '2026-11-15 20:28:55', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_sucursal`
--

CREATE TABLE `horario_sucursal` (
  `id_horario` int(11) NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') DEFAULT NULL,
  `hora_apertura` time DEFAULT NULL,
  `hora_cierre` time DEFAULT NULL,
  `es_activo` tinyint(1) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_sucursal`
--

INSERT INTO `horario_sucursal` (`id_horario`, `dia_semana`, `hora_apertura`, `hora_cierre`, `es_activo`, `id_sucursal`) VALUES
(1, 'Lunes', '09:00:00', '19:00:00', 1, 'SUC01'),
(2, 'Martes', NULL, NULL, 0, 'SUC01'),
(3, 'Miercoles', '09:00:00', '19:00:00', 1, 'SUC01'),
(4, 'Jueves', NULL, NULL, 0, 'SUC01'),
(5, 'Viernes', '09:00:00', '19:00:00', 1, 'SUC01'),
(6, 'Sabado', NULL, NULL, 0, 'SUC01'),
(7, 'Domingo', '09:00:00', '19:00:00', 1, 'SUC01'),
(8, 'Lunes', NULL, NULL, 0, 'SUC02'),
(9, 'Martes', NULL, NULL, 0, 'SUC02'),
(10, 'Miercoles', NULL, NULL, 0, 'SUC02'),
(11, 'Jueves', NULL, NULL, 0, 'SUC02'),
(12, 'Viernes', NULL, NULL, 0, 'SUC02'),
(13, 'Sabado', NULL, NULL, 0, 'SUC02'),
(14, 'Domingo', NULL, NULL, 0, 'SUC02');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `idx_reservas_sucursal`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `idx_reservas_sucursal` (
`nombre_sucursal` varchar(50)
,`id_reserva` int(11)
,`fecha_reserva` date
,`estado_reserva` varchar(15)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `idx_reserva_estado`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `idx_reserva_estado` (
`id_reserva` int(11)
,`fecha_reserva` date
,`estado_reserva` varchar(15)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `idx_servicios_mas_reservados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `idx_servicios_mas_reservados` (
`nombre_servicio` varchar(80)
,`total_unidades_vendidas` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `ingresos_reservas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `ingresos_reservas` (
`id_reserva` int(11)
,`fecha` date
,`estado` varchar(15)
,`total_ingreso` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodo_pago`
--

CREATE TABLE `metodo_pago` (
  `id_metodoPago` varchar(10) NOT NULL,
  `nombre_metodoPago` varchar(50) NOT NULL,
  `tipo` varchar(30) DEFAULT NULL,
  `estado` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metodo_pago`
--

INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`) VALUES
('MP01', 'Efectivo', 'Presencial', 'activo'),
('MP02', 'QR / Transferencia', 'Digital', 'activo'),
('MP03', 'QR', 'qr', 'activo'),
('MP04', 'Tarjeta', 'tarjeta', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_reserva`
--

CREATE TABLE `pago_reserva` (
  `id_pago` int(11) NOT NULL,
  `id_reserva` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'anticipo',
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_metodoPago` varchar(10) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'por_verificar',
  `comprobante` varchar(255) DEFAULT NULL,
  `codigo` varchar(40) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT NULL,
  `fecha_verificacion` datetime DEFAULT NULL,
  `verificado_por` varchar(100) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago_reserva`
--

INSERT INTO `pago_reserva` (`id_pago`, `id_reserva`, `tipo`, `monto`, `id_metodoPago`, `estado`, `comprobante`, `codigo`, `fecha_registro`, `fecha_verificacion`, `verificado_por`, `observacion`) VALUES
(1, 3, 'anticipo', 15.00, 'MP03', 'por_verificar', 'uploads/comprobantes/res_3_20261115202951_3e53db.jpg', 'PAG-20261115-C67B0E9C3840', '2026-11-15 20:29:51', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` varchar(10) NOT NULL,
  `nombre_producto` varchar(80) DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `imagen_url` varchar(200) DEFAULT NULL,
  `id_categoria` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre_producto`, `descripcion`, `precio`, `cantidad`, `imagen_url`, `id_categoria`) VALUES
('PROD01', 'Sérum Humectante', 'Sérum facial con ácido hialurónico', 120.00, 5, NULL, 'CAT01'),
('PROD02', 'Crema Antiage', 'Crema de noche regeneradora', 250.00, 14, NULL, 'CAT01'),
('PROD03', 'Exfoliante Corporal', 'Exfoliante de café y coco', 85.00, 8, NULL, 'CAT02'),
('PROD04', 'Mascarilla Hidratante', 'Mascarilla', 30.00, 100, 'uploads/productos/prod_20261019_011035_256628.jpg', 'CAT01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promocion`
--

CREATE TABLE `promocion` (
  `id_promocion` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `id_producto` varchar(10) DEFAULT NULL,
  `id_servicio` varchar(10) DEFAULT NULL,
  `precio_original` decimal(10,2) NOT NULL,
  `precio_promocional` decimal(10,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` varchar(10) NOT NULL DEFAULT 'activa',
  `imagen_url` varchar(200) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `tipo_tratamiento` varchar(30) DEFAULT NULL,
  `zona_tratamiento` varchar(50) DEFAULT NULL,
  `respuestas` longtext DEFAULT NULL,
  `precio_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `anticipo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_pagado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado_pago` varchar(20) NOT NULL DEFAULT 'sin_pago',
  `fecha_pago` datetime DEFAULT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `codigo_pago` varchar(40) DEFAULT NULL,
  `estado` varchar(15) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL,
  `id_metodoPago` varchar(10) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`id_reserva`, `fecha`, `hora`, `tipo_tratamiento`, `zona_tratamiento`, `respuestas`, `precio_total`, `anticipo`, `monto_pagado`, `saldo_pendiente`, `estado_pago`, `fecha_pago`, `comprobante`, `codigo_pago`, `estado`, `id_sucursal`, `id_metodoPago`, `id_usuario`) VALUES
(1, '2026-10-23', '11:00:00', 'depilacion', NULL, NULL, 140.00, 30.00, 0.00, 140.00, 'sin_pago', NULL, NULL, NULL, 'atendida', 'SUC01', NULL, 'CLI0001'),
(2, '2026-10-30', '14:00:00', 'depilacion', NULL, NULL, 100.00, 30.00, 0.00, 100.00, 'sin_pago', NULL, NULL, NULL, 'cancelada', 'SUC01', NULL, 'CLI0001'),
(3, '2026-11-16', '13:00:00', 'facial', NULL, '{\"tipo_tratamiento\":\"facial\",\"observaciones\":\"\",\"tipo_piel\":\"Seca\",\"objetivos\":[\"Hidratación\"],\"tratamiento_previo\":\"no\",\"alergias\":\"no\"}', 15.00, 15.00, 0.00, 0.00, 'por_verificar', NULL, 'uploads/comprobantes/res_3_20261115202951_3e53db.jpg', 'PAG-20261115-C67B0E9C3840', 'confirmada', 'SUC01', 'MP03', 'CLI0001');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `reservas_por_metodo_pago`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `reservas_por_metodo_pago` (
`nombre_metodoPago` varchar(50)
,`id_reserva` int(11)
,`fecha` date
,`hora` time
,`estado` varchar(15)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` varchar(10) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`) VALUES
('ROL01', 'administrador'),
('ROL02', 'personal'),
('ROL03', 'cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` varchar(10) NOT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT NULL,
  `id_categoriaS` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio`
--

INSERT INTO `servicio` (`id_servicio`, `nombre`, `descripcion`, `precio`, `duracion_minutos`, `id_categoriaS`) VALUES
('SERV01', 'Hidrafacial', 'Hidratante', 15.00, 30, NULL),
('SERV02', 'depilacion facial', 'depilacion con laser facial', 140.00, 30, 'CS01'),
('SERV03', 'depilacion zona-axilas', 'depilacion en el area de las axilas', 100.00, 30, 'CS01'),
('SERV04', 'depilacion zona-media pierna', 'depilacion en el area de la media pierna', 180.00, 30, NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `servicio_categoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `servicio_categoria` (
`nombre_servicio` varchar(80)
,`descripcion` varchar(200)
,`precio` decimal(10,2)
,`nombre_categoria` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `servicio_por_categoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `servicio_por_categoria` (
`nombre_categoria` varchar(100)
,`id_servicio` varchar(10)
,`nombre_servicio` varchar(80)
,`precio` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `servicio_precio_duracion`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `servicio_precio_duracion` (
`nombre` varchar(80)
,`precio` decimal(10,2)
,`duracion_minutos` int(11)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_producto`
--

CREATE TABLE `servicio_producto` (
  `id_servicio` varchar(10) NOT NULL,
  `id_producto` varchar(10) NOT NULL,
  `recomendado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio_producto`
--

INSERT INTO `servicio_producto` (`id_servicio`, `id_producto`, `recomendado`) VALUES
('SERV01', 'PROD01', 1),
('SERV01', 'PROD02', 1),
('SERV01', 'PROD04', 1),
('SERV02', 'PROD01', 1),
('SERV03', 'PROD03', 1),
('SERV04', 'PROD03', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `id_sucursal` varchar(10) NOT NULL,
  `nombre_sucursal` varchar(50) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`id_sucursal`, `nombre_sucursal`, `direccion`, `telefono`) VALUES
('SUC01', 'SOPOCACHI', 'Av. Arce frente al multicine, Edificio Mila Piso 2, Of 5', '76543210'),
('SUC02', 'CALACOTO', 'Calle 23 de Calacoto, esquina Ballivián, Torre Faith – Piso 5, Oficina 5B', '78919889');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `total_compras_por_fecha`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `total_compras_por_fecha` (
`fecha_compra` date
,`cantidad_compras` bigint(21)
,`total_compras` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `total_gastado_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `total_gastado_usuario` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`cantidad_compras` bigint(21)
,`total_gastado` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` varchar(100) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ap_paterno` varchar(50) DEFAULT NULL,
  `ap_materno` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `id_rol` varchar(10) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `ap_paterno`, `ap_materno`, `telefono`, `fecha_nacimiento`, `id_rol`, `id_sucursal`) VALUES
('ADM001', 'Admin', 'Prueba', '', '', NULL, 'ROL01', NULL),
('CLI0001', 'Maria', 'Quispe', 'Mamani', '64170832', '2004-03-12', 'ROL03', NULL),
('CLI0002', 'sam', 'moreno', 'zambrano', '21563465135', '2006-06-01', 'ROL03', NULL),
('CLI0003', 'luz', 'Zegarra', 'Apaza', '54658464', '2026-07-16', 'ROL03', NULL),
('EMP001', 'Tatiana', 'Maceda', 'Bustillos', '78935241', NULL, 'ROL02', 'SUC01');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_clientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_clientes` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`telefono` varchar(20)
,`fecha_nacimiento` date
,`nombre_rol` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_clientes_con_reservas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_clientes_con_reservas` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`id_reserva` int(11)
,`estado` varchar(15)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_cuentas_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_cuentas_usuario` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`telefono` varchar(20)
,`nombre_rol` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_detalle_reservas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_detalle_reservas` (
`id_reserva` int(11)
,`fecha_reserva` date
,`hora_reserva` time
,`nombre_servicio` varchar(80)
,`cantidad` int(11)
,`precio_unitario` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_empleados_por_sucursal`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_empleados_por_sucursal` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`telefono` varchar(20)
,`nombre_rol` varchar(50)
,`nombre_sucursal` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_historial_clientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_historial_clientes` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`id_reserva` int(11)
,`estado` varchar(15)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_precios_productos_categoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_precios_productos_categoria` (
`nombre_categoria` varchar(50)
,`nombre_producto` varchar(80)
,`precio` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_productos_categoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_productos_categoria` (
`id_producto` varchar(10)
,`nombre_producto` varchar(80)
,`descripcion` varchar(200)
,`precio` decimal(10,2)
,`cantidad` int(11)
,`nombre_categoria` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_productos_mas_comprados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_productos_mas_comprados` (
`id_producto` varchar(10)
,`nombre_producto` varchar(80)
,`total_comprado` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_productos_por_categoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_productos_por_categoria` (
`nombre_categoria` varchar(50)
,`id_producto` varchar(10)
,`nombre_producto` varchar(80)
,`precio` decimal(10,2)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_productos_stock`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_productos_stock` (
`id_producto` varchar(10)
,`nombre_producto` varchar(80)
,`precio` decimal(10,2)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_productos_stock_bajo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_productos_stock_bajo` (
`id_producto` varchar(10)
,`nombre_producto` varchar(80)
,`precio` decimal(10,2)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_promociones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_promociones` (
`id` varchar(10)
,`id_promocion` varchar(10)
,`titulo` varchar(100)
,`nombre` varchar(100)
,`descripcion` varchar(255)
,`id_producto` varchar(10)
,`id_servicio` varchar(10)
,`tipo_articulo` varchar(17)
,`articulo` varchar(100)
,`precio_original` decimal(10,2)
,`precio_promocional` decimal(10,2)
,`descuento` decimal(17,2)
,`fecha_inicio` date
,`fecha_fin` date
,`estado` varchar(10)
,`estado_calculado` varchar(10)
,`dias_restantes` int(7)
,`imagen_url` varchar(200)
,`fecha_registro` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_promociones_activas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_promociones_activas` (
`id` varchar(10)
,`id_promocion` varchar(10)
,`titulo` varchar(100)
,`nombre` varchar(100)
,`descripcion` varchar(255)
,`id_producto` varchar(10)
,`id_servicio` varchar(10)
,`tipo_articulo` varchar(17)
,`articulo` varchar(100)
,`precio_original` decimal(10,2)
,`precio_promocional` decimal(10,2)
,`descuento` decimal(17,2)
,`fecha_inicio` date
,`fecha_fin` date
,`estado` varchar(10)
,`estado_calculado` varchar(10)
,`dias_restantes` int(7)
,`imagen_url` varchar(200)
,`fecha_registro` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_promociones_por_vencer`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_promociones_por_vencer` (
`id` varchar(10)
,`id_promocion` varchar(10)
,`titulo` varchar(100)
,`nombre` varchar(100)
,`descripcion` varchar(255)
,`id_producto` varchar(10)
,`id_servicio` varchar(10)
,`tipo_articulo` varchar(17)
,`articulo` varchar(100)
,`precio_original` decimal(10,2)
,`precio_promocional` decimal(10,2)
,`descuento` decimal(17,2)
,`fecha_inicio` date
,`fecha_fin` date
,`estado` varchar(10)
,`estado_calculado` varchar(10)
,`dias_restantes` int(7)
,`imagen_url` varchar(200)
,`fecha_registro` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_promociones_vencidas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_promociones_vencidas` (
`id` varchar(10)
,`id_promocion` varchar(10)
,`titulo` varchar(100)
,`nombre` varchar(100)
,`descripcion` varchar(255)
,`id_producto` varchar(10)
,`id_servicio` varchar(10)
,`tipo_articulo` varchar(17)
,`articulo` varchar(100)
,`precio_original` decimal(10,2)
,`precio_promocional` decimal(10,2)
,`descuento` decimal(17,2)
,`fecha_inicio` date
,`fecha_fin` date
,`estado` varchar(10)
,`estado_calculado` varchar(10)
,`dias_restantes` int(7)
,`imagen_url` varchar(200)
,`fecha_registro` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_sucursales_horarios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_sucursales_horarios` (
`id_sucursal` varchar(10)
,`nombre_sucursal` varchar(50)
,`direccion` varchar(150)
,`telefono` varchar(20)
,`dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo')
,`hora_apertura` time
,`hora_cierre` time
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_sucursales_personal`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_sucursales_personal` (
`id_sucursal` varchar(10)
,`nombre_sucursal` varchar(50)
,`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`nombre_rol` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_usuarios_completos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_usuarios_completos` (
`id_usuario` varchar(100)
,`nombre` varchar(50)
,`ap_paterno` varchar(50)
,`ap_materno` varchar(50)
,`telefono` varchar(20)
,`fecha_nacimiento` date
,`nombre_rol` varchar(50)
,`nombre_sucursal` varchar(50)
,`direccion` varchar(150)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_ventas_por_producto`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_ventas_por_producto` (
`id_producto` varchar(10)
,`nombre_producto` varchar(80)
,`cantidad_vendida` decimal(32,0)
,`total_vendido` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `compras_completas`
--
DROP TABLE IF EXISTS `compras_completas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `compras_completas`  AS SELECT `c`.`id_compra` AS `id_compra`, `c`.`nro_compra` AS `nro_compra`, `c`.`fecha_compra` AS `fecha_compra`, `c`.`total_pagar` AS `total_pagar`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `mp`.`nombre_metodoPago` AS `nombre_metodoPago` FROM ((`compra` `c` join `usuario` `u` on(`c`.`id_usuario` = `u`.`id_usuario`)) left join `metodo_pago` `mp` on(`c`.`id_metodoPago` = `mp`.`id_metodoPago`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `compras_por_metodo_pago`
--
DROP TABLE IF EXISTS `compras_por_metodo_pago`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `compras_por_metodo_pago`  AS SELECT `mp`.`nombre_metodoPago` AS `nombre_metodoPago`, `c`.`id_compra` AS `id_compra`, `c`.`nro_compra` AS `nro_compra`, `c`.`fecha_compra` AS `fecha_compra`, `c`.`total_pagar` AS `total_pagar` FROM (`compra` `c` join `metodo_pago` `mp` on(`c`.`id_metodoPago` = `mp`.`id_metodoPago`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `compras_por_usuario`
--
DROP TABLE IF EXISTS `compras_por_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `compras_por_usuario`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `c`.`id_compra` AS `id_compra`, `c`.`nro_compra` AS `nro_compra`, `c`.`fecha_compra` AS `fecha_compra`, `c`.`total_pagar` AS `total_pagar` FROM (`compra` `c` join `usuario` `u` on(`c`.`id_usuario` = `u`.`id_usuario`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `detalle_compras`
--
DROP TABLE IF EXISTS `detalle_compras`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `detalle_compras`  AS SELECT `c`.`id_compra` AS `id_compra`, `c`.`nro_compra` AS `nro_compra`, `c`.`fecha_compra` AS `fecha_compra`, `p`.`nombre_producto` AS `nombre_producto`, `dc`.`cantidad` AS `cantidad`, `dc`.`precio_unitario` AS `precio_unitario`, `dc`.`subtotal` AS `subtotal` FROM ((`compra` `c` join `detalle_compra` `dc` on(`c`.`id_compra` = `dc`.`id_compra`)) join `producto` `p` on(`dc`.`id_producto` = `p`.`id_producto`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `idx_reservas_sucursal`
--
DROP TABLE IF EXISTS `idx_reservas_sucursal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `idx_reservas_sucursal`  AS SELECT `s`.`nombre_sucursal` AS `nombre_sucursal`, `r`.`id_reserva` AS `id_reserva`, `r`.`fecha` AS `fecha_reserva`, `r`.`estado` AS `estado_reserva` FROM (`reserva` `r` join `sucursal` `s` on(`r`.`id_sucursal` = `s`.`id_sucursal`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `idx_reserva_estado`
--
DROP TABLE IF EXISTS `idx_reserva_estado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `idx_reserva_estado`  AS SELECT `reserva`.`id_reserva` AS `id_reserva`, `reserva`.`fecha` AS `fecha_reserva`, `reserva`.`estado` AS `estado_reserva` FROM `reserva` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `idx_servicios_mas_reservados`
--
DROP TABLE IF EXISTS `idx_servicios_mas_reservados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `idx_servicios_mas_reservados`  AS SELECT `s`.`nombre` AS `nombre_servicio`, sum(`dr`.`cantidad`) AS `total_unidades_vendidas` FROM (((`servicio` `s` join `detalle_reserva` `dr` on(`s`.`id_servicio` = `dr`.`id_servicio`)) join `reserva` `r` on(`dr`.`id_reserva` = `r`.`id_reserva`)) join `cuenta_usuario` `cu` on(`r`.`id_usuario` = `cu`.`id_usuario`)) WHERE `cu`.`id_rol` = 'ROL03' GROUP BY `s`.`id_servicio`, `s`.`nombre` ORDER BY sum(`dr`.`cantidad`) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `ingresos_reservas`
--
DROP TABLE IF EXISTS `ingresos_reservas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ingresos_reservas`  AS SELECT `r`.`id_reserva` AS `id_reserva`, `r`.`fecha` AS `fecha`, `r`.`estado` AS `estado`, sum(`dr`.`subtotal`) AS `total_ingreso` FROM (`reserva` `r` join `detalle_reserva` `dr` on(`r`.`id_reserva` = `dr`.`id_reserva`)) GROUP BY `r`.`id_reserva`, `r`.`fecha`, `r`.`estado` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `reservas_por_metodo_pago`
--
DROP TABLE IF EXISTS `reservas_por_metodo_pago`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `reservas_por_metodo_pago`  AS SELECT `mp`.`nombre_metodoPago` AS `nombre_metodoPago`, `r`.`id_reserva` AS `id_reserva`, `r`.`fecha` AS `fecha`, `r`.`hora` AS `hora`, `r`.`estado` AS `estado` FROM (`reserva` `r` join `metodo_pago` `mp` on(`r`.`id_metodoPago` = `mp`.`id_metodoPago`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `servicio_categoria`
--
DROP TABLE IF EXISTS `servicio_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `servicio_categoria`  AS SELECT `s`.`nombre` AS `nombre_servicio`, `s`.`descripcion` AS `descripcion`, `s`.`precio` AS `precio`, `cs`.`nombre_cservicio` AS `nombre_categoria` FROM (`categoria_servicio` `cs` join `servicio` `s` on(`cs`.`id_categoriaS` = `s`.`id_categoriaS`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `servicio_por_categoria`
--
DROP TABLE IF EXISTS `servicio_por_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `servicio_por_categoria`  AS SELECT `cs`.`nombre_cservicio` AS `nombre_categoria`, `s`.`id_servicio` AS `id_servicio`, `s`.`nombre` AS `nombre_servicio`, `s`.`precio` AS `precio` FROM (`categoria_servicio` `cs` join `servicio` `s` on(`cs`.`id_categoriaS` = `s`.`id_categoriaS`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `servicio_precio_duracion`
--
DROP TABLE IF EXISTS `servicio_precio_duracion`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `servicio_precio_duracion`  AS SELECT `servicio`.`nombre` AS `nombre`, `servicio`.`precio` AS `precio`, `servicio`.`duracion_minutos` AS `duracion_minutos` FROM `servicio` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `total_compras_por_fecha`
--
DROP TABLE IF EXISTS `total_compras_por_fecha`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `total_compras_por_fecha`  AS SELECT `compra`.`fecha_compra` AS `fecha_compra`, count(`compra`.`id_compra`) AS `cantidad_compras`, sum(`compra`.`total_pagar`) AS `total_compras` FROM `compra` GROUP BY `compra`.`fecha_compra` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `total_gastado_usuario`
--
DROP TABLE IF EXISTS `total_gastado_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `total_gastado_usuario`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, count(`c`.`id_compra`) AS `cantidad_compras`, sum(`c`.`total_pagar`) AS `total_gastado` FROM (`compra` `c` join `usuario` `u` on(`c`.`id_usuario` = `u`.`id_usuario`)) GROUP BY `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_clientes`
--
DROP TABLE IF EXISTS `vw_clientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_clientes`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `u`.`telefono` AS `telefono`, `u`.`fecha_nacimiento` AS `fecha_nacimiento`, `r`.`nombre_rol` AS `nombre_rol` FROM (`usuario` `u` join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) WHERE `r`.`nombre_rol` = 'cliente' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_clientes_con_reservas`
--
DROP TABLE IF EXISTS `vw_clientes_con_reservas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_clientes_con_reservas`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `r`.`id_reserva` AS `id_reserva`, `r`.`estado` AS `estado` FROM ((`usuario` `u` join `reserva` `r` on(`u`.`id_usuario` = `r`.`id_usuario`)) join `rol` `ro` on(`u`.`id_rol` = `ro`.`id_rol`)) WHERE `ro`.`nombre_rol` = 'cliente' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_cuentas_usuario`
--
DROP TABLE IF EXISTS `vw_cuentas_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cuentas_usuario`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `u`.`telefono` AS `telefono`, `r`.`nombre_rol` AS `nombre_rol` FROM ((`cuenta_usuario` `c` join `usuario` `u` on(`c`.`id_usuario` = `u`.`id_usuario`)) join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_detalle_reservas`
--
DROP TABLE IF EXISTS `vw_detalle_reservas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_detalle_reservas`  AS SELECT `r`.`id_reserva` AS `id_reserva`, `r`.`fecha` AS `fecha_reserva`, `r`.`hora` AS `hora_reserva`, `s`.`nombre` AS `nombre_servicio`, `dr`.`cantidad` AS `cantidad`, `dr`.`precio_unitario` AS `precio_unitario` FROM ((`reserva` `r` join `detalle_reserva` `dr` on(`r`.`id_reserva` = `dr`.`id_reserva`)) join `servicio` `s` on(`dr`.`id_servicio` = `s`.`id_servicio`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_empleados_por_sucursal`
--
DROP TABLE IF EXISTS `vw_empleados_por_sucursal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empleados_por_sucursal`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `u`.`telefono` AS `telefono`, `r`.`nombre_rol` AS `nombre_rol`, `s`.`nombre_sucursal` AS `nombre_sucursal` FROM ((`usuario` `u` join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) join `sucursal` `s` on(`u`.`id_sucursal` = `s`.`id_sucursal`)) WHERE `r`.`nombre_rol` <> 'cliente' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_historial_clientes`
--
DROP TABLE IF EXISTS `vw_historial_clientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_historial_clientes`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `r`.`id_reserva` AS `id_reserva`, `r`.`estado` AS `estado` FROM ((`historial_cliente` `h` join `reserva` `r` on(`h`.`id_reserva` = `r`.`id_reserva`)) join `usuario` `u` on(`r`.`id_usuario` = `u`.`id_usuario`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_precios_productos_categoria`
--
DROP TABLE IF EXISTS `vw_precios_productos_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_precios_productos_categoria`  AS SELECT `c`.`nombre_categoria` AS `nombre_categoria`, `p`.`nombre_producto` AS `nombre_producto`, `p`.`precio` AS `precio` FROM (`producto` `p` join `categoria_producto` `c` on(`p`.`id_categoria` = `c`.`id_categoria`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_productos_categoria`
--
DROP TABLE IF EXISTS `vw_productos_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productos_categoria`  AS SELECT `p`.`id_producto` AS `id_producto`, `p`.`nombre_producto` AS `nombre_producto`, `p`.`descripcion` AS `descripcion`, `p`.`precio` AS `precio`, `p`.`cantidad` AS `cantidad`, `c`.`nombre_categoria` AS `nombre_categoria` FROM (`producto` `p` join `categoria_producto` `c` on(`p`.`id_categoria` = `c`.`id_categoria`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_productos_mas_comprados`
--
DROP TABLE IF EXISTS `vw_productos_mas_comprados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productos_mas_comprados`  AS SELECT `p`.`id_producto` AS `id_producto`, `p`.`nombre_producto` AS `nombre_producto`, sum(`dc`.`cantidad`) AS `total_comprado` FROM (`producto` `p` join `detalle_compra` `dc` on(`p`.`id_producto` = `dc`.`id_producto`)) GROUP BY `p`.`id_producto`, `p`.`nombre_producto` ORDER BY sum(`dc`.`cantidad`) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_productos_por_categoria`
--
DROP TABLE IF EXISTS `vw_productos_por_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productos_por_categoria`  AS SELECT `c`.`nombre_categoria` AS `nombre_categoria`, `p`.`id_producto` AS `id_producto`, `p`.`nombre_producto` AS `nombre_producto`, `p`.`precio` AS `precio`, `p`.`cantidad` AS `stock` FROM (`categoria_producto` `c` join `producto` `p` on(`c`.`id_categoria` = `p`.`id_categoria`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_productos_stock`
--
DROP TABLE IF EXISTS `vw_productos_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productos_stock`  AS SELECT `producto`.`id_producto` AS `id_producto`, `producto`.`nombre_producto` AS `nombre_producto`, `producto`.`precio` AS `precio`, `producto`.`cantidad` AS `stock` FROM `producto` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_productos_stock_bajo`
--
DROP TABLE IF EXISTS `vw_productos_stock_bajo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productos_stock_bajo`  AS SELECT `producto`.`id_producto` AS `id_producto`, `producto`.`nombre_producto` AS `nombre_producto`, `producto`.`precio` AS `precio`, `producto`.`cantidad` AS `stock` FROM `producto` WHERE `producto`.`cantidad` <= 5 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_promociones`
--
DROP TABLE IF EXISTS `vw_promociones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_promociones`  AS SELECT `p`.`id_promocion` AS `id`, `p`.`id_promocion` AS `id_promocion`, `p`.`nombre` AS `titulo`, `p`.`nombre` AS `nombre`, `p`.`descripcion` AS `descripcion`, `p`.`id_producto` AS `id_producto`, `p`.`id_servicio` AS `id_servicio`, CASE WHEN `p`.`id_servicio` is not null AND `p`.`id_producto` is not null THEN 'producto_servicio' WHEN `p`.`id_servicio` is not null THEN 'servicio' WHEN `p`.`id_producto` is not null THEN 'producto' ELSE 'general' END AS `tipo_articulo`, coalesce(`pr`.`nombre_producto`,`s`.`nombre`,`p`.`nombre`) AS `articulo`, `p`.`precio_original` AS `precio_original`, `p`.`precio_promocional` AS `precio_promocional`, CASE WHEN `p`.`precio_original` > 0 THEN round((`p`.`precio_original` - `p`.`precio_promocional`) / `p`.`precio_original` * 100,2) ELSE 0 END AS `descuento`, `p`.`fecha_inicio` AS `fecha_inicio`, `p`.`fecha_fin` AS `fecha_fin`, `p`.`estado` AS `estado`, CASE WHEN `p`.`estado` <> 'activa' THEN 'inactiva' WHEN curdate() < `p`.`fecha_inicio` THEN 'programada' WHEN curdate() > `p`.`fecha_fin` THEN 'vencida' ELSE 'activa' END AS `estado_calculado`, to_days(`p`.`fecha_fin`) - to_days(curdate()) AS `dias_restantes`, `p`.`imagen_url` AS `imagen_url`, `p`.`fecha_registro` AS `fecha_registro` FROM ((`promocion` `p` left join `producto` `pr` on(`pr`.`id_producto` = `p`.`id_producto`)) left join `servicio` `s` on(`s`.`id_servicio` = `p`.`id_servicio`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_promociones_activas`
--
DROP TABLE IF EXISTS `vw_promociones_activas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_promociones_activas`  AS SELECT `vw_promociones`.`id` AS `id`, `vw_promociones`.`id_promocion` AS `id_promocion`, `vw_promociones`.`titulo` AS `titulo`, `vw_promociones`.`nombre` AS `nombre`, `vw_promociones`.`descripcion` AS `descripcion`, `vw_promociones`.`id_producto` AS `id_producto`, `vw_promociones`.`id_servicio` AS `id_servicio`, `vw_promociones`.`tipo_articulo` AS `tipo_articulo`, `vw_promociones`.`articulo` AS `articulo`, `vw_promociones`.`precio_original` AS `precio_original`, `vw_promociones`.`precio_promocional` AS `precio_promocional`, `vw_promociones`.`descuento` AS `descuento`, `vw_promociones`.`fecha_inicio` AS `fecha_inicio`, `vw_promociones`.`fecha_fin` AS `fecha_fin`, `vw_promociones`.`estado` AS `estado`, `vw_promociones`.`estado_calculado` AS `estado_calculado`, `vw_promociones`.`dias_restantes` AS `dias_restantes`, `vw_promociones`.`imagen_url` AS `imagen_url`, `vw_promociones`.`fecha_registro` AS `fecha_registro` FROM `vw_promociones` WHERE `vw_promociones`.`estado` = 'activa' AND curdate() between `vw_promociones`.`fecha_inicio` and `vw_promociones`.`fecha_fin` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_promociones_por_vencer`
--
DROP TABLE IF EXISTS `vw_promociones_por_vencer`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_promociones_por_vencer`  AS SELECT `vw_promociones`.`id` AS `id`, `vw_promociones`.`id_promocion` AS `id_promocion`, `vw_promociones`.`titulo` AS `titulo`, `vw_promociones`.`nombre` AS `nombre`, `vw_promociones`.`descripcion` AS `descripcion`, `vw_promociones`.`id_producto` AS `id_producto`, `vw_promociones`.`id_servicio` AS `id_servicio`, `vw_promociones`.`tipo_articulo` AS `tipo_articulo`, `vw_promociones`.`articulo` AS `articulo`, `vw_promociones`.`precio_original` AS `precio_original`, `vw_promociones`.`precio_promocional` AS `precio_promocional`, `vw_promociones`.`descuento` AS `descuento`, `vw_promociones`.`fecha_inicio` AS `fecha_inicio`, `vw_promociones`.`fecha_fin` AS `fecha_fin`, `vw_promociones`.`estado` AS `estado`, `vw_promociones`.`estado_calculado` AS `estado_calculado`, `vw_promociones`.`dias_restantes` AS `dias_restantes`, `vw_promociones`.`imagen_url` AS `imagen_url`, `vw_promociones`.`fecha_registro` AS `fecha_registro` FROM `vw_promociones` WHERE `vw_promociones`.`estado` = 'activa' AND curdate() between `vw_promociones`.`fecha_inicio` and `vw_promociones`.`fecha_fin` AND to_days(`vw_promociones`.`fecha_fin`) - to_days(curdate()) <= 7 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_promociones_vencidas`
--
DROP TABLE IF EXISTS `vw_promociones_vencidas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_promociones_vencidas`  AS SELECT `vw_promociones`.`id` AS `id`, `vw_promociones`.`id_promocion` AS `id_promocion`, `vw_promociones`.`titulo` AS `titulo`, `vw_promociones`.`nombre` AS `nombre`, `vw_promociones`.`descripcion` AS `descripcion`, `vw_promociones`.`id_producto` AS `id_producto`, `vw_promociones`.`id_servicio` AS `id_servicio`, `vw_promociones`.`tipo_articulo` AS `tipo_articulo`, `vw_promociones`.`articulo` AS `articulo`, `vw_promociones`.`precio_original` AS `precio_original`, `vw_promociones`.`precio_promocional` AS `precio_promocional`, `vw_promociones`.`descuento` AS `descuento`, `vw_promociones`.`fecha_inicio` AS `fecha_inicio`, `vw_promociones`.`fecha_fin` AS `fecha_fin`, `vw_promociones`.`estado` AS `estado`, `vw_promociones`.`estado_calculado` AS `estado_calculado`, `vw_promociones`.`dias_restantes` AS `dias_restantes`, `vw_promociones`.`imagen_url` AS `imagen_url`, `vw_promociones`.`fecha_registro` AS `fecha_registro` FROM `vw_promociones` WHERE curdate() > `vw_promociones`.`fecha_fin` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_sucursales_horarios`
--
DROP TABLE IF EXISTS `vw_sucursales_horarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_sucursales_horarios`  AS SELECT `s`.`id_sucursal` AS `id_sucursal`, `s`.`nombre_sucursal` AS `nombre_sucursal`, `s`.`direccion` AS `direccion`, `s`.`telefono` AS `telefono`, `h`.`dia_semana` AS `dia_semana`, `h`.`hora_apertura` AS `hora_apertura`, `h`.`hora_cierre` AS `hora_cierre` FROM (`sucursal` `s` join `horario_sucursal` `h` on(`s`.`id_sucursal` = `h`.`id_sucursal`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_sucursales_personal`
--
DROP TABLE IF EXISTS `vw_sucursales_personal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_sucursales_personal`  AS SELECT `s`.`id_sucursal` AS `id_sucursal`, `s`.`nombre_sucursal` AS `nombre_sucursal`, `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `r`.`nombre_rol` AS `nombre_rol` FROM ((`sucursal` `s` join `usuario` `u` on(`s`.`id_sucursal` = `u`.`id_sucursal`)) join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) WHERE `r`.`nombre_rol` <> 'cliente' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_usuarios_completos`
--
DROP TABLE IF EXISTS `vw_usuarios_completos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_usuarios_completos`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `u`.`telefono` AS `telefono`, `u`.`fecha_nacimiento` AS `fecha_nacimiento`, `r`.`nombre_rol` AS `nombre_rol`, `s`.`nombre_sucursal` AS `nombre_sucursal`, `s`.`direccion` AS `direccion` FROM ((`usuario` `u` left join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) left join `sucursal` `s` on(`u`.`id_sucursal` = `s`.`id_sucursal`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_ventas_por_producto`
--
DROP TABLE IF EXISTS `vw_ventas_por_producto`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ventas_por_producto`  AS SELECT `p`.`id_producto` AS `id_producto`, `p`.`nombre_producto` AS `nombre_producto`, sum(`dc`.`cantidad`) AS `cantidad_vendida`, sum(`dc`.`subtotal`) AS `total_vendido` FROM ((`producto` `p` join `detalle_compra` `dc` on(`p`.`id_producto` = `dc`.`id_producto`)) join `compra` `c` on(`dc`.`id_compra` = `c`.`id_compra`)) GROUP BY `p`.`id_producto`, `p`.`nombre_producto` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria_producto`
--
ALTER TABLE `categoria_producto`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `categoria_servicio`
--
ALTER TABLE `categoria_servicio`
  ADD PRIMARY KEY (`id_categoriaS`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `idx_compra_fecha` (`fecha_compra`),
  ADD KEY `fk_usuarioCompra` (`id_usuario`),
  ADD KEY `fk_metodoPagoCompra` (`id_metodoPago`);

--
-- Indices de la tabla `cuenta_usuario`
--
ALTER TABLE `cuenta_usuario`
  ADD PRIMARY KEY (`id_cuentaUsuario`),
  ADD UNIQUE KEY `uk_cuenta_correo` (`correo`),
  ADD KEY `fk_rolCuenta` (`id_rol`),
  ADD KEY `fk_usuarioCuenta` (`id_usuario`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_compra_maestro` (`id_compra`),
  ADD KEY `fk_detalle_compra_producto` (`id_producto`),
  ADD KEY `idx_detalle_compra_servicio` (`id_servicio`);

--
-- Indices de la tabla `detalle_reserva`
--
ALTER TABLE `detalle_reserva`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_reserva_servicio` (`id_servicio`),
  ADD KEY `fk_detalle_reserva_maestro` (`id_reserva`);

--
-- Indices de la tabla `historial_cliente`
--
ALTER TABLE `historial_cliente`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_historial_reserva` (`id_reserva`);

--
-- Indices de la tabla `horario_sucursal`
--
ALTER TABLE `horario_sucursal`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `fk_horario_sucursal` (`id_sucursal`);

--
-- Indices de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  ADD PRIMARY KEY (`id_metodoPago`);

--
-- Indices de la tabla `pago_reserva`
--
ALTER TABLE `pago_reserva`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `uk_pago_codigo` (`codigo`),
  ADD KEY `idx_pago_reserva` (`id_reserva`),
  ADD KEY `fk_pago_metodoPago` (`id_metodoPago`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `idx_producto_nombre` (`nombre_producto`),
  ADD KEY `fk_producto_categoria` (`id_categoria`);

--
-- Indices de la tabla `promocion`
--
ALTER TABLE `promocion`
  ADD PRIMARY KEY (`id_promocion`),
  ADD KEY `idx_promocion_fechas` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_promocion_estado` (`estado`),
  ADD KEY `idx_promocion_producto` (`id_producto`),
  ADD KEY `idx_promocion_servicio` (`id_servicio`);

--
-- Indices de la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id_reserva`),
  ADD UNIQUE KEY `uk_reserva_codigo_pago` (`codigo_pago`),
  ADD KEY `fk_reserva_sucursal` (`id_sucursal`),
  ADD KEY `fk_reserva_metodoPago` (`id_metodoPago`),
  ADD KEY `fk_reserva_usuario` (`id_usuario`),
  ADD KEY `idx_reserva_agenda` (`fecha`,`hora`,`id_sucursal`,`estado`),
  ADD KEY `idx_reserva_pago` (`estado_pago`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_servicio`),
  ADD KEY `fk_servicio_categoria` (`id_categoriaS`);

--
-- Indices de la tabla `servicio_producto`
--
ALTER TABLE `servicio_producto`
  ADD PRIMARY KEY (`id_servicio`,`id_producto`),
  ADD KEY `fk_sp_producto` (`id_producto`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`id_sucursal`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `idx_usuario_rol` (`id_rol`),
  ADD KEY `idx_usuario_sucursal` (`id_sucursal`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cuenta_usuario`
--
ALTER TABLE `cuenta_usuario`
  MODIFY `id_cuentaUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_reserva`
--
ALTER TABLE `detalle_reserva`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `historial_cliente`
--
ALTER TABLE `historial_cliente`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pago_reserva`
--
ALTER TABLE `pago_reserva`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_metodoPagoCompra` FOREIGN KEY (`id_metodoPago`) REFERENCES `metodo_pago` (`id_metodoPago`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuarioCompra` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cuenta_usuario`
--
ALTER TABLE `cuenta_usuario`
  ADD CONSTRAINT `fk_rolCuenta` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuarioCuenta` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD CONSTRAINT `fk_detalle_compra_maestro` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_compra_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE SET NULL;

--
-- Filtros para la tabla `detalle_reserva`
--
ALTER TABLE `detalle_reserva`
  ADD CONSTRAINT `fk_detalle_reserva_maestro` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_reserva_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE SET NULL;

--
-- Filtros para la tabla `historial_cliente`
--
ALTER TABLE `historial_cliente`
  ADD CONSTRAINT `fk_historial_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE SET NULL;

--
-- Filtros para la tabla `horario_sucursal`
--
ALTER TABLE `horario_sucursal`
  ADD CONSTRAINT `fk_horario_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pago_reserva`
--
ALTER TABLE `pago_reserva`
  ADD CONSTRAINT `fk_pago_metodoPago` FOREIGN KEY (`id_metodoPago`) REFERENCES `metodo_pago` (`id_metodoPago`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pago_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_producto` (`id_categoria`) ON DELETE SET NULL;

--
-- Filtros para la tabla `promocion`
--
ALTER TABLE `promocion`
  ADD CONSTRAINT `fk_promocion_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_promocion_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_metodoPago` FOREIGN KEY (`id_metodoPago`) REFERENCES `metodo_pago` (`id_metodoPago`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reserva_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD CONSTRAINT `fk_servicio_categoria` FOREIGN KEY (`id_categoriaS`) REFERENCES `categoria_servicio` (`id_categoriaS`) ON DELETE SET NULL;

--
-- Filtros para la tabla `servicio_producto`
--
ALTER TABLE `servicio_producto`
  ADD CONSTRAINT `fk_sp_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sp_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuario_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
