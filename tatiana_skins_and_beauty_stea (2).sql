-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-10-2026 a las 03:06:07
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
-- Base de datos: `tatiana_skins_and_beauty_stea`
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
  `password` varchar(50) DEFAULT NULL,
  `id_rol` varchar(10) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuenta_usuario`
--

INSERT INTO `cuenta_usuario` (`id_cuentaUsuario`, `correo`, `password`, `id_rol`, `id_usuario`) VALUES
(1, 'admin.test@test.bo', '9NQSYTo5tABnEL2TYlXk5zo++GB/zEY7ou0+0ZJzv6M=', 'ROL01', 'ADM001'),
(2, 'quispeliz143@gmail.com', 'hqS3+cD42fwOBlPJEG3R23/qnB+z8Wq9vZ+fdUA4R9w=', 'ROL03', 'CLI0001'),
(3, 'morenosamantha016@gmail.com', '3Zf4ONFiZepW03KNXmKnK5xEB4rLSATZrGVQ0nzTcxs=', 'ROL03', 'CLI0002'),
(4, 'luzzegarra@gmail.com', '4CwETg7A9MqjyL4ZxqbMxAmV7rjbk8wokqQ6uYJh4Gc=', 'ROL03', 'CLI0003');

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
  `id_producto` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_compra`
--

INSERT INTO `detalle_compra` (`id_detalle`, `cantidad`, `precio_unitario`, `subtotal`, `id_compra`, `id_producto`) VALUES
(1, 1, 250.00, 250.00, 1, 'PROD02');

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
(2, 1, 100.00, 100.00, 'SERV03', 2);

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
(1, NULL, NULL, 0, NULL, 'Notas de reserva: Tratamiento con cremas nivea', '2026-10-21 20:14:57', 1);

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
-- Estructura de tabla para la tabla `reserva`
--

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `estado` varchar(15) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL,
  `id_metodoPago` varchar(10) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`id_reserva`, `fecha`, `hora`, `estado`, `id_sucursal`, `id_metodoPago`, `id_usuario`) VALUES
(1, '2026-10-23', '11:00:00', 'pendiente', 'SUC01', NULL, 'CLI0001'),
(2, '2026-10-30', '14:00:00', 'pendiente', 'SUC01', NULL, 'CLI0001');

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
`nombre_servicio` varchar(80)
,`precio` decimal(10,2)
,`duracion_minutos` int(11)
);

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
('CLI0003', 'luz', 'Zegarra', 'Apaza', '54658464', '2026-07-16', 'ROL03', NULL);

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `compras_completas`  AS SELECT `compra`.`id_compra` AS `id_compra`, `compra`.`nro_compra` AS `nro_compra`, `compra`.`fecha_compra` AS `fecha_compra`, `compra`.`total_pagar` AS `total_pagar`, `usuario`.`nombre` AS `nombre`, `usuario`.`ap_paterno` AS `ap_paterno`, `usuario`.`ap_materno` AS `ap_materno`, `metodo_pago`.`nombre_metodoPago` AS `nombre_metodoPago` FROM ((`compra` join `usuario` on(`compra`.`id_usuario` = `usuario`.`id_usuario`)) join `metodo_pago` on(`compra`.`id_metodoPago` = `metodo_pago`.`id_metodoPago`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `compras_por_metodo_pago`
--
DROP TABLE IF EXISTS `compras_por_metodo_pago`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `compras_por_metodo_pago`  AS SELECT `metodo_pago`.`nombre_metodoPago` AS `nombre_metodoPago`, `compra`.`id_compra` AS `id_compra`, `compra`.`nro_compra` AS `nro_compra`, `compra`.`fecha_compra` AS `fecha_compra`, `compra`.`total_pagar` AS `total_pagar` FROM (`compra` join `metodo_pago` on(`compra`.`id_metodoPago` = `metodo_pago`.`id_metodoPago`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `compras_por_usuario`
--
DROP TABLE IF EXISTS `compras_por_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `compras_por_usuario`  AS SELECT `usuario`.`id_usuario` AS `id_usuario`, `usuario`.`nombre` AS `nombre`, `usuario`.`ap_paterno` AS `ap_paterno`, `usuario`.`ap_materno` AS `ap_materno`, `compra`.`id_compra` AS `id_compra`, `compra`.`nro_compra` AS `nro_compra`, `compra`.`fecha_compra` AS `fecha_compra`, `compra`.`total_pagar` AS `total_pagar` FROM (`compra` join `usuario` on(`compra`.`id_usuario` = `usuario`.`id_usuario`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `detalle_compras`
--
DROP TABLE IF EXISTS `detalle_compras`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `detalle_compras`  AS SELECT `compra`.`id_compra` AS `id_compra`, `compra`.`nro_compra` AS `nro_compra`, `compra`.`fecha_compra` AS `fecha_compra`, `producto`.`nombre_producto` AS `nombre_producto`, `detalle_compra`.`cantidad` AS `cantidad`, `detalle_compra`.`precio_unitario` AS `precio_unitario`, `detalle_compra`.`subtotal` AS `subtotal` FROM ((`compra` join `detalle_compra` on(`compra`.`id_compra` = `detalle_compra`.`id_compra`)) join `producto` on(`detalle_compra`.`id_producto` = `producto`.`id_producto`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `idx_reservas_sucursal`
--
DROP TABLE IF EXISTS `idx_reservas_sucursal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `idx_reservas_sucursal`  AS SELECT `sucursal`.`nombre_sucursal` AS `nombre_sucursal`, `reserva`.`id_reserva` AS `id_reserva`, `reserva`.`fecha` AS `fecha_reserva`, `reserva`.`estado` AS `estado_reserva` FROM (`reserva` join `sucursal` on(`reserva`.`id_sucursal` = `sucursal`.`id_sucursal`)) ;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ingresos_reservas`  AS SELECT `reserva`.`id_reserva` AS `id_reserva`, `reserva`.`fecha` AS `fecha`, `reserva`.`estado` AS `estado`, sum(`detalle_reserva`.`subtotal`) AS `total_ingreso` FROM (`reserva` join `detalle_reserva` on(`reserva`.`id_reserva` = `detalle_reserva`.`id_reserva`)) GROUP BY `reserva`.`id_reserva`, `reserva`.`fecha`, `reserva`.`estado` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `reservas_por_metodo_pago`
--
DROP TABLE IF EXISTS `reservas_por_metodo_pago`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `reservas_por_metodo_pago`  AS SELECT `metodo_pago`.`nombre_metodoPago` AS `nombre_metodoPago`, `reserva`.`id_reserva` AS `id_reserva`, `reserva`.`fecha` AS `fecha`, `reserva`.`hora` AS `hora`, `reserva`.`estado` AS `estado` FROM (`reserva` join `metodo_pago` on(`reserva`.`id_metodoPago` = `metodo_pago`.`id_metodoPago`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `servicio_categoria`
--
DROP TABLE IF EXISTS `servicio_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `servicio_categoria`  AS SELECT `servicio`.`nombre` AS `nombre_servicio`, `servicio`.`descripcion` AS `descripcion`, `servicio`.`precio` AS `precio`, `categoria_servicio`.`nombre_cservicio` AS `nombre_categoria` FROM (`categoria_servicio` join `servicio` on(`categoria_servicio`.`id_categoriaS` = `servicio`.`id_categoriaS`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `servicio_por_categoria`
--
DROP TABLE IF EXISTS `servicio_por_categoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `servicio_por_categoria`  AS SELECT `categoria_servicio`.`nombre_cservicio` AS `nombre_categoria`, `servicio`.`id_servicio` AS `id_servicio`, `servicio`.`nombre` AS `nombre_servicio`, `servicio`.`precio` AS `precio` FROM (`categoria_servicio` join `servicio` on(`categoria_servicio`.`id_categoriaS` = `servicio`.`id_categoriaS`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `servicio_precio_duracion`
--
DROP TABLE IF EXISTS `servicio_precio_duracion`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `servicio_precio_duracion`  AS SELECT `servicio`.`nombre` AS `nombre_servicio`, `servicio`.`precio` AS `precio`, `servicio`.`duracion_minutos` AS `duracion_minutos` FROM `servicio` ;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `total_gastado_usuario`  AS SELECT `usuario`.`id_usuario` AS `id_usuario`, `usuario`.`nombre` AS `nombre`, `usuario`.`ap_paterno` AS `ap_paterno`, `usuario`.`ap_materno` AS `ap_materno`, count(`compra`.`id_compra`) AS `cantidad_compras`, sum(`compra`.`total_pagar`) AS `total_gastado` FROM (`compra` join `usuario` on(`compra`.`id_usuario` = `usuario`.`id_usuario`)) GROUP BY `usuario`.`id_usuario`, `usuario`.`nombre`, `usuario`.`ap_paterno`, `usuario`.`ap_materno` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_clientes`
--
DROP TABLE IF EXISTS `vw_clientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_clientes`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `u`.`telefono` AS `telefono`, `u`.`fecha_nacimiento` AS `fecha_nacimiento`, `r`.`nombre_rol` AS `nombre_rol` FROM (`usuario` `u` join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) WHERE `r`.`nombre_rol` = 'Cliente' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_clientes_con_reservas`
--
DROP TABLE IF EXISTS `vw_clientes_con_reservas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_clientes_con_reservas`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `r`.`id_reserva` AS `id_reserva`, `r`.`estado` AS `estado` FROM ((`usuario` `u` join `reserva` `r` on(`u`.`id_usuario` = `r`.`id_usuario`)) join `rol` `ro` on(`u`.`id_rol` = `ro`.`id_rol`)) WHERE `ro`.`nombre_rol` = 'Cliente' ;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empleados_por_sucursal`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `u`.`telefono` AS `telefono`, `r`.`nombre_rol` AS `nombre_rol`, `s`.`nombre_sucursal` AS `nombre_sucursal` FROM ((`usuario` `u` join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) join `sucursal` `s` on(`u`.`id_sucursal` = `s`.`id_sucursal`)) WHERE `r`.`nombre_rol` <> 'Cliente' ;

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
-- Estructura para la vista `vw_sucursales_horarios`
--
DROP TABLE IF EXISTS `vw_sucursales_horarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_sucursales_horarios`  AS SELECT `s`.`id_sucursal` AS `id_sucursal`, `s`.`nombre_sucursal` AS `nombre_sucursal`, `s`.`direccion` AS `direccion`, `s`.`telefono` AS `telefono`, `h`.`dia_semana` AS `dia_semana`, `h`.`hora_apertura` AS `hora_apertura`, `h`.`hora_cierre` AS `hora_cierre` FROM (`sucursal` `s` join `horario_sucursal` `h` on(`s`.`id_sucursal` = `h`.`id_sucursal`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_sucursales_personal`
--
DROP TABLE IF EXISTS `vw_sucursales_personal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_sucursales_personal`  AS SELECT `s`.`id_sucursal` AS `id_sucursal`, `s`.`nombre_sucursal` AS `nombre_sucursal`, `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre`, `u`.`ap_paterno` AS `ap_paterno`, `u`.`ap_materno` AS `ap_materno`, `r`.`nombre_rol` AS `nombre_rol` FROM ((`sucursal` `s` join `usuario` `u` on(`s`.`id_sucursal` = `u`.`id_sucursal`)) join `rol` `r` on(`u`.`id_rol` = `r`.`id_rol`)) WHERE `r`.`nombre_rol` <> 'Cliente' ;

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
  ADD KEY `fk_usuarioCompra` (`id_usuario`),
  ADD KEY `fk_metodoPagoCompra` (`id_metodoPago`),
  ADD KEY `idx_compra_fecha` (`fecha_compra`),
  ADD KEY `idx_compra_usuario` (`id_usuario`);

--
-- Indices de la tabla `cuenta_usuario`
--
ALTER TABLE `cuenta_usuario`
  ADD PRIMARY KEY (`id_cuentaUsuario`),
  ADD KEY `fk_rolCuenta` (`id_rol`),
  ADD KEY `fk_usuarioCuenta` (`id_usuario`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD KEY `idx_detalle_compra_producto` (`id_producto`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD KEY `idx_producto_nombre` (`nombre_producto`),
  ADD KEY `idx_producto_categoria` (`id_categoria`),
  ADD KEY `idx_producto_precio` (`precio`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD KEY `idx_usuario_rol` (`id_rol`),
  ADD KEY `idx_usuario_sucursal` (`id_sucursal`),
  ADD KEY `idx_usuario_sucursal_rol` (`id_sucursal`,`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
