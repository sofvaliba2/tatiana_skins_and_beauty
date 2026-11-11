SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- TABLAS MAESTRAS (Sin Dependencias Externas)
-- --------------------------------------------------------

CREATE TABLE `rol` (
  `id_rol` varchar(10) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rol` (`id_rol`, `nombre_rol`) VALUES
('ROL01', 'administrador'),
('ROL02', 'personal'),
('ROL03', 'cliente');

-- --------------------------------------------------------

CREATE TABLE `sucursal` (
  `id_sucursal` varchar(10) NOT NULL,
  `nombre_sucursal` varchar(50) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sucursal` (`id_sucursal`, `nombre_sucursal`, `direccion`, `telefono`) VALUES
('SUC01', 'SOPOCACHI', 'Av. Arce frente al multicine, Edificio Mila Piso 2, Of 5', '76543210'),
('SUC02', 'CALACOTO', 'Calle 23 de Calacoto, esquina Ballivián, Torre Faith – Piso 5, Oficina 5B', '78919889');

-- --------------------------------------------------------

CREATE TABLE `categoria_producto` (
  `id_categoria` varchar(10) NOT NULL,
  `nombre_categoria` varchar(50) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categoria_producto` (`id_categoria`, `nombre_categoria`, `tipo`) VALUES
('CAT01', 'Cuidado Facial', 'Cosmética'),
('CAT02', 'Cuidado Corporal', 'Cosmética');

-- --------------------------------------------------------

CREATE TABLE `categoria_servicio` (
  `id_categoriaS` varchar(10) NOT NULL,
  `nombre_cservicio` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_categoriaS`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categoria_servicio` (`id_categoriaS`, `nombre_cservicio`) VALUES
('CS01', 'depilaciones');

-- --------------------------------------------------------

CREATE TABLE `metodo_pago` (
  `id_metodoPago` varchar(10) NOT NULL,
  `nombre_metodoPago` varchar(50) NOT NULL,
  `tipo` varchar(30) DEFAULT NULL,
  `estado` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id_metodoPago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`) VALUES
('MP01', 'Efectivo', 'Presencial', 'Activo');

-- --------------------------------------------------------
-- TABLAS SECUNDARIAS (Con Dependencias)
-- --------------------------------------------------------

CREATE TABLE `usuario` (
  `id_usuario` varchar(100) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ap_paterno` varchar(50) DEFAULT NULL,
  `ap_materno` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `id_rol` varchar(10) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  KEY `idx_usuario_rol` (`id_rol`),
  KEY `idx_usuario_sucursal` (`id_sucursal`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE SET NULL,
  CONSTRAINT `fk_usuario_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuario` (`id_usuario`, `nombre`, `ap_paterno`, `ap_materno`, `telefono`, `fecha_nacimiento`, `id_rol`, `id_sucursal`) VALUES
('ADM001', 'Admin', 'Prueba', '', '', NULL, 'ROL01', NULL),
('CLI0001', 'Maria', 'Quispe', 'Mamani', '64170832', '2004-03-12', 'ROL03', NULL),
('CLI0002', 'sam', 'moreno', 'zambrano', '21563465135', '2006-06-01', 'ROL03', NULL),
('CLI0003', 'luz', 'Zegarra', 'Apaza', '54658464', '2026-07-16', 'ROL03', NULL);

-- --------------------------------------------------------

CREATE TABLE `cuenta_usuario` (
  `id_cuentaUsuario` int(11) NOT NULL AUTO_INCREMENT,
  `correo` varchar(100) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `id_rol` varchar(10) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_cuentaUsuario`),
  CONSTRAINT `fk_rolCuenta` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE SET NULL,
  CONSTRAINT `fk_usuarioCuenta` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cuenta_usuario` (`id_cuentaUsuario`, `correo`, `password`, `id_rol`, `id_usuario`) VALUES
(1, 'admin.test@test.bo', '9NQSYTo5tABnEL2TYlXk5zo++GB/zEY7ou0+0ZJzv6M=', 'ROL01', 'ADM001'),
(2, 'quispeliz143@gmail.com', 'hqS3+cD42fwOBlPJEG3R23/qnB+z8Wq9vZ+fdUA4R9w=', 'ROL03', 'CLI0001'),
(3, 'morenosamantha016@gmail.com', '3Zf4ONFiZepW03KNXmKnK5xEB4rLSATZrGVQ0nzTcxs=', 'ROL03', 'CLI0002'),
(4, 'luzzegarra@gmail.com', '4CwETg7A9MqjyL4ZxqbMxAmV7rjbk8wokqQ6uYJh4Gc=', 'ROL03', 'CLI0003');

-- --------------------------------------------------------

CREATE TABLE `horario_sucursal` (
  `id_horario` int(11) NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') DEFAULT NULL,
  `hora_apertura` time DEFAULT NULL,
  `hora_cierre` time DEFAULT NULL,
  `es_activo` tinyint(1) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_horario`),
  CONSTRAINT `fk_horario_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `producto` (
  `id_producto` varchar(10) NOT NULL,
  `nombre_producto` varchar(80) DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `imagen_url` varchar(200) DEFAULT NULL,
  `id_categoria` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `idx_producto_nombre` (`nombre_producto`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_producto` (`id_categoria`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `producto` (`id_producto`, `nombre_producto`, `descripcion`, `precio`, `cantidad`, `imagen_url`, `id_categoria`) VALUES
('PROD01', 'Sérum Humectante', 'Sérum facial con ácido hialurónico', 120.00, 5, NULL, 'CAT01'),
('PROD02', 'Crema Antiage', 'Crema de noche regeneradora', 250.00, 14, NULL, 'CAT01'),
('PROD03', 'Exfoliante Corporal', 'Exfoliante de café y coco', 85.00, 8, NULL, 'CAT02'),
('PROD04', 'Mascarilla Hidratante', 'Mascarilla', 30.00, 100, 'uploads/productos/prod_20261019_011035_256628.jpg', 'CAT01');

-- --------------------------------------------------------

CREATE TABLE `servicio` (
  `id_servicio` varchar(10) NOT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT NULL,
  `id_categoriaS` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_servicio`),
  CONSTRAINT `fk_servicio_categoria` FOREIGN KEY (`id_categoriaS`) REFERENCES `categoria_servicio` (`id_categoriaS`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `servicio` (`id_servicio`, `nombre`, `descripcion`, `precio`, `duracion_minutos`, `id_categoriaS`) VALUES
('SERV01', 'Hidrafacial', 'Hidratante', 15.00, 30, NULL),
('SERV02', 'depilacion facial', 'depilacion con laser facial', 140.00, 30, 'CS01'),
('SERV03', 'depilacion zona-axilas', 'depilacion en el area de las axilas', 100.00, 30, 'CS01'),
('SERV04', 'depilacion zona-media pierna', 'depilacion en el area de la media pierna', 180.00, 30, NULL);

-- --------------------------------------------------------

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_compra` date DEFAULT NULL,
  `nro_compra` varchar(20) DEFAULT NULL,
  `total_pagar` decimal(10,2) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL,
  `id_metodoPago` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_compra`),
  KEY `idx_compra_fecha` (`fecha_compra`),
  CONSTRAINT `fk_usuarioCompra` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_metodoPagoCompra` FOREIGN KEY (`id_metodoPago`) REFERENCES `metodo_pago` (`id_metodoPago`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `compra` (`id_compra`, `fecha_compra`, `nro_compra`, `total_pagar`, `id_usuario`, `id_metodoPago`) VALUES
(1, '2026-10-19', 'C-000001', 250.00, 'CLI0001', NULL);

-- --------------------------------------------------------

CREATE TABLE `detalle_compra` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `id_compra` int(11) DEFAULT NULL,
  `id_producto` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  CONSTRAINT `fk_detalle_compra_maestro` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_compra_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `detalle_compra` (`id_detalle`, `cantidad`, `precio_unitario`, `subtotal`, `id_compra`, `id_producto`) VALUES
(1, 1, 250.00, 250.00, 1, 'PROD02');

-- --------------------------------------------------------

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `estado` varchar(15) DEFAULT NULL,
  `id_sucursal` varchar(10) DEFAULT NULL,
  `id_metodoPago` varchar(10) DEFAULT NULL,
  `id_usuario` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  CONSTRAINT `fk_reserva_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_metodoPago` FOREIGN KEY (`id_metodoPago`) REFERENCES `metodo_pago` (`id_metodoPago`) ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reserva` (`id_reserva`, `fecha`, `hora`, `estado`, `id_sucursal`, `id_metodoPago`, `id_usuario`) VALUES
(1, '2026-10-23', '11:00:00', 'pendiente', 'SUC01', NULL, 'CLI0001'),
(2, '2026-10-30', '14:00:00', 'pendiente', 'SUC01', NULL, 'CLI0001');

-- --------------------------------------------------------

CREATE TABLE `detalle_reserva` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `id_servicio` varchar(10) DEFAULT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  CONSTRAINT `fk_detalle_reserva_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE SET NULL,
  CONSTRAINT `fk_detalle_reserva_maestro` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `detalle_reserva` (`id_detalle`, `cantidad`, `precio_unitario`, `subtotal`, `id_servicio`, `id_reserva`) VALUES
(1, 1, 140.00, 140.00, 'SERV02', 1),
(2, 1, 100.00, 100.00, 'SERV03', 2);

-- --------------------------------------------------------

CREATE TABLE `historial_cliente` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `alergias` text DEFAULT NULL,
  `objetivo_piel` text DEFAULT NULL,
  `abierto_recomendaciones` tinyint(1) DEFAULT NULL,
  `rutina_actual` text DEFAULT NULL,
  `observaciones_sesion` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  CONSTRAINT `fk_historial_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `historial_cliente` (`id_historial`, `alergias`, `objetivo_piel`, `abierto_recomendaciones`, `rutina_actual`, `observaciones_sesion`, `fecha_registro`, `id_reserva`) VALUES
(1, NULL, NULL, 0, NULL, 'Notas de reserva: Tratamiento con cremas nivea', '2026-10-21 20:14:57', 1);

-- --------------------------------------------------------
-- DEFINICIÓN DE VISTAS (Corregido Filtro a Minúsculas)
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `compras_completas` AS 
SELECT `c`.`id_compra`, `c`.`nro_compra`, `c`.`fecha_compra`, `c`.`total_pagar`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `mp`.`nombre_metodoPago` 
FROM `compra` `c`
JOIN `usuario` `u` ON `c`.`id_usuario` = `u`.`id_usuario`
LEFT JOIN `metodo_pago` `mp` ON `c`.`id_metodoPago` = `mp`.`id_metodoPago`;

CREATE OR REPLACE VIEW `compras_por_metodo_pago` AS 
SELECT `mp`.`nombre_metodoPago`, `c`.`id_compra`, `c`.`nro_compra`, `c`.`fecha_compra`, `c`.`total_pagar` 
FROM `compra` `c` 
JOIN `metodo_pago` `mp` ON `c`.`id_metodoPago` = `mp`.`id_metodoPago`;

CREATE OR REPLACE VIEW `compras_por_usuario` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `c`.`id_compra`, `c`.`nro_compra`, `c`.`fecha_compra`, `c`.`total_pagar` 
FROM `compra` `c` 
JOIN `usuario` `u` ON `c`.`id_usuario` = `u`.`id_usuario`;

CREATE OR REPLACE VIEW `detalle_compras` AS 
SELECT `c`.`id_compra`, `c`.`nro_compra`, `c`.`fecha_compra`, `p`.`nombre_producto`, `dc`.`cantidad`, `dc`.`precio_unitario`, `dc`.`subtotal` 
FROM `compra` `c` 
JOIN `detalle_compra` `dc` ON `c`.`id_compra` = `dc`.`id_compra` 
JOIN `producto` `p` ON `dc`.`id_producto` = `p`.`id_producto`;

CREATE OR REPLACE VIEW `idx_reservas_sucursal` AS 
SELECT `s`.`nombre_sucursal`, `r`.`id_reserva`, `r`.`fecha` AS `fecha_reserva`, `r`.`estado` AS `estado_reserva` 
FROM `reserva` `r` 
JOIN `sucursal` `s` ON `r`.`id_sucursal` = `s`.`id_sucursal`;

CREATE OR REPLACE VIEW `idx_reserva_estado` AS 
SELECT `id_reserva`, `fecha` AS `fecha_reserva`, `estado` AS `estado_reserva` 
FROM `reserva`;

CREATE OR REPLACE VIEW `idx_servicios_mas_reservados` AS 
SELECT `s`.`nombre` AS `nombre_servicio`, SUM(`dr`.`cantidad`) AS `total_unidades_vendidas` 
FROM `servicio` `s` 
JOIN `detalle_reserva` `dr` ON `s`.`id_servicio` = `dr`.`id_servicio` 
JOIN `reserva` `r` ON `dr`.`id_reserva` = `r`.`id_reserva` 
JOIN `cuenta_usuario` `cu` ON `r`.`id_usuario` = `cu`.`id_usuario` 
WHERE `cu`.`id_rol` = 'ROL03' 
GROUP BY `s`.`id_servicio`, `s`.`nombre` 
ORDER BY SUM(`dr`.`cantidad`) DESC;

CREATE OR REPLACE VIEW `ingresos_reservas` AS 
SELECT `r`.`id_reserva`, `r`.`fecha`, `r`.`estado`, SUM(`dr`.`subtotal`) AS `total_ingreso` 
FROM `reserva` `r` 
JOIN `detalle_reserva` `dr` ON `r`.`id_reserva` = `dr`.`id_reserva` 
GROUP BY `r`.`id_reserva`, `r`.`fecha`, `r`.`estado`;

CREATE OR REPLACE VIEW `reservas_por_metodo_pago` AS 
SELECT `mp`.`nombre_metodoPago`, `r`.`id_reserva`, `r`.`fecha`, `r`.`hora`, `r`.`estado` 
FROM `reserva` `r` 
JOIN `metodo_pago` `mp` ON `r`.`id_metodoPago` = `mp`.`id_metodoPago`;

CREATE OR REPLACE VIEW `servicio_categoria` AS 
SELECT `s`.`nombre` AS `nombre_servicio`, `s`.`descripcion`, `s`.`precio`, `cs`.`nombre_cservicio` AS `nombre_categoria` 
FROM `categoria_servicio` `cs` 
JOIN `servicio` `s` ON `cs`.`id_categoriaS` = `s`.`id_categoriaS`;

CREATE OR REPLACE VIEW `servicio_por_categoria` AS 
SELECT `cs`.`nombre_cservicio` AS `nombre_categoria`, `s`.`id_servicio`, `s`.`nombre` AS `nombre_servicio`, `s`.`precio` 
FROM `categoria_servicio` `cs` 
JOIN `servicio` `s` ON `cs`.`id_categoriaS` = `s`.`id_categoriaS`;

CREATE OR REPLACE VIEW `servicio_precio_duracion` AS 
SELECT `nombre`, `precio`, `duracion_minutos` 
FROM `servicio`;

CREATE OR REPLACE VIEW `total_compras_por_fecha` AS 
SELECT `fecha_compra`, COUNT(`id_compra`) AS `cantidad_compras`, SUM(`total_pagar`) AS `total_compras` 
FROM `compra` 
GROUP BY `fecha_compra`;

CREATE OR REPLACE VIEW `total_gastado_usuario` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, COUNT(`c`.`id_compra`) AS `cantidad_compras`, SUM(`c`.`total_pagar`) AS `total_gastado` 
FROM `compra` `c` 
JOIN `usuario` `u` ON `c`.`id_usuario` = `u`.`id_usuario` 
GROUP BY `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`;

CREATE OR REPLACE VIEW `vw_clientes` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `u`.`telefono`, `u`.`fecha_nacimiento`, `r`.`nombre_rol` 
FROM `usuario` `u` 
JOIN `rol` `r` ON `u`.`id_rol` = `r`.`id_rol` 
WHERE `r`.`nombre_rol` = 'cliente';

CREATE OR REPLACE VIEW `vw_clientes_con_reservas` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `r`.`id_reserva`, `r`.`estado` 
FROM `usuario` `u` 
JOIN `reserva` `r` ON `u`.`id_usuario` = `r`.`id_usuario` 
JOIN `rol` `ro` ON `u`.`id_rol` = `ro`.`id_rol` 
WHERE `ro`.`nombre_rol` = 'cliente';

CREATE OR REPLACE VIEW `vw_cuentas_usuario` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `u`.`telefono`, `r`.`nombre_rol` 
FROM `cuenta_usuario` `c` 
JOIN `usuario` `u` ON `c`.`id_usuario` = `u`.`id_usuario` 
JOIN `rol` `r` ON `u`.`id_rol` = `r`.`id_rol`;

CREATE OR REPLACE VIEW `vw_detalle_reservas` AS 
SELECT `r`.`id_reserva`, `r`.`fecha` AS `fecha_reserva`, `r`.`hora` AS `hora_reserva`, `s`.`nombre` AS `nombre_servicio`, `dr`.`cantidad`, `dr`.`precio_unitario` 
FROM `reserva` `r` 
JOIN `detalle_reserva` `dr` ON `r`.`id_reserva` = `dr`.`id_reserva` 
JOIN `servicio` `s` ON `dr`.`id_servicio` = `s`.`id_servicio`;

CREATE OR REPLACE VIEW `vw_empleados_por_sucursal` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `u`.`telefono`, `r`.`nombre_rol`, `s`.`nombre_sucursal` 
FROM `usuario` `u` 
JOIN `rol` `r` ON `u`.`id_rol` = `r`.`id_rol` 
JOIN `sucursal` `s` ON `u`.`id_sucursal` = `s`.`id_sucursal` 
WHERE `r`.`nombre_rol` <> 'cliente';

CREATE OR REPLACE VIEW `vw_historial_clientes` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `r`.`id_reserva`, `r`.`estado` 
FROM `historial_cliente` `h` 
JOIN `reserva` `r` ON `h`.`id_reserva` = `r`.`id_reserva` 
JOIN `usuario` `u` ON `r`.`id_usuario` = `u`.`id_usuario`;

CREATE OR REPLACE VIEW `vw_precios_productos_categoria` AS 
SELECT `c`.`nombre_categoria`, `p`.`nombre_producto`, `p`.`precio` 
FROM `producto` `p` 
JOIN `categoria_producto` `c` ON `p`.`id_categoria` = `c`.`id_categoria`;

CREATE OR REPLACE VIEW `vw_productos_categoria` AS 
SELECT `p`.`id_producto`, `p`.`nombre_producto`, `p`.`descripcion`, `p`.`precio`, `p`.`cantidad`, `c`.`nombre_categoria` 
FROM `producto` `p` 
JOIN `categoria_producto` `c` ON `p`.`id_categoria` = `c`.`id_categoria`;

CREATE OR REPLACE VIEW `vw_productos_mas_comprados` AS 
SELECT `p`.`id_producto`, `p`.`nombre_producto`, SUM(`dc`.`cantidad`) AS `total_comprado` 
FROM `producto` `p` 
JOIN `detalle_compra` `dc` ON `p`.`id_producto` = `dc`.`id_producto` 
GROUP BY `p`.`id_producto`, `p`.`nombre_producto` 
ORDER BY SUM(`dc`.`cantidad`) DESC;

CREATE OR REPLACE VIEW `vw_productos_por_categoria` AS 
SELECT `c`.`nombre_categoria`, `p`.`id_producto`, `p`.`nombre_producto`, `p`.`precio`, `p`.`cantidad` AS `stock` 
FROM `categoria_producto` `c` 
JOIN `producto` `p` ON `c`.`id_categoria` = `p`.`id_categoria`;

CREATE OR REPLACE VIEW `vw_productos_stock` AS 
SELECT `id_producto`, `nombre_producto`, `precio`, `cantidad` AS `stock` 
FROM `producto`;

CREATE OR REPLACE VIEW `vw_productos_stock_bajo` AS 
SELECT `id_producto`, `nombre_producto`, `precio`, `cantidad` AS `stock` 
FROM `producto` 
WHERE `cantidad` <= 5;

CREATE OR REPLACE VIEW `vw_sucursales_horarios` AS 
SELECT `s`.`id_sucursal`, `s`.`nombre_sucursal`, `s`.`direccion`, `s`.`telefono`, `h`.`dia_semana`, `h`.`hora_apertura`, `h`.`hora_cierre` 
FROM `sucursal` `s` 
JOIN `horario_sucursal` `h` ON `s`.`id_sucursal` = `h`.`id_sucursal`;

CREATE OR REPLACE VIEW `vw_sucursales_personal` AS 
SELECT `s`.`id_sucursal`, `s`.`nombre_sucursal`, `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `r`.`nombre_rol` 
FROM `sucursal` `s` 
JOIN `usuario` `u` ON `s`.`id_sucursal` = `u`.`id_sucursal` 
JOIN `rol` `r` ON `u`.`id_rol` = `r`.`id_rol` 
WHERE `r`.`nombre_rol` <> 'cliente';

CREATE OR REPLACE VIEW `vw_usuarios_completos` AS 
SELECT `u`.`id_usuario`, `u`.`nombre`, `u`.`ap_paterno`, `u`.`ap_materno`, `u`.`telefono`, `u`.`fecha_nacimiento`, `r`.`nombre_rol`, `s`.`nombre_sucursal`, `s`.`direccion` 
FROM `usuario` `u` 
LEFT JOIN `rol` `r` ON `u`.`id_rol` = `r`.`id_rol` 
LEFT JOIN `sucursal` `s` ON `u`.`id_sucursal` = `s`.`id_sucursal`;

CREATE OR REPLACE VIEW `vw_ventas_por_producto` AS 
SELECT `p`.`id_producto`, `p`.`nombre_producto`, SUM(`dc`.`cantidad`) AS `cantidad_vendida`, SUM(`dc`.`subtotal`) AS `total_vendido` 
FROM `producto` `p` 
JOIN `detalle_compra` `dc` ON `p`.`id_producto` = `dc`.`id_producto` 
JOIN `compra` `c` ON `dc`.`id_compra` = `c`.`id_compra` 
GROUP BY `p`.`id_producto`, `p`.`nombre_producto`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;