-- Migración: procedimientos, funciones y triggers de negocio (inventario, ventas, citas y promociones)
-- Base de datos: tatiana_skins_and_beauty_corregida
-- Ejecutar:
--   mysql -u root tatiana_skins_and_beauty_corregida < database/migration_objetos_negocio.sql
--
-- UBICACIÓN EN EL PROYECTO
--   Definición SQL ................ database/migration_objetos_negocio.sql
--   Llamadas desde backend ........ app/models/Producto.php
--                                   app/models/Compra.php
--                                   app/models/Reserva.php
--   Conexión ...................... app/core/Database.php (Database::pdo())
--   Auditoría generada ............ tablas auditoria_inventario y auditoria_negocio (MySQL)
--   Frontend ...................... NINGUNO. Las vistas y public/js/app.js solo envían
--                                   datos a rutas PHP; nunca llaman MySQL directamente.
--
-- IMPORTANTE: estos objetos NO descuentan stock. El descuento ya lo hace
-- Compra::crear() dentro de una transacción con FOR UPDATE y actualización
-- condicional. Aquí solo se consulta, se validan fechas de promoción y se
-- registra auditoría; así no hay doble descuento de inventario.

-- ---------------------------------------------------------------------------
-- Tablas de auditoría (solo eventos; no duplican productos ni ventas)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria_inventario` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_producto` VARCHAR(10) NOT NULL,
    `nombre_producto` VARCHAR(80) DEFAULT NULL,
    `campo` VARCHAR(20) NOT NULL,
    `valor_anterior` VARCHAR(80) DEFAULT NULL,
    `valor_nuevo` VARCHAR(80) DEFAULT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `idx_auditoria_inventario_producto` (`id_producto`, `creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `auditoria_negocio` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `evento` VARCHAR(40) NOT NULL,
    `entidad` VARCHAR(30) NOT NULL,
    `id_entidad` VARCHAR(40) DEFAULT NULL,
    `detalle` VARCHAR(255) DEFAULT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `idx_auditoria_negocio_evento` (`evento`, `creado_en`),
    KEY `idx_auditoria_negocio_entidad` (`entidad`, `id_entidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- Procedimiento: productos con stock bajo (solo lectura)
-- Lo usa Producto::stockBajo() en app/models/Producto.php
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_productos_stock_bajo`;
DELIMITER //
CREATE PROCEDURE `sp_productos_stock_bajo`(IN p_umbral INT)
BEGIN
    SELECT p.id_producto, p.nombre_producto, p.cantidad, p.precio,
           c.nombre_categoria
    FROM `producto` p
    LEFT JOIN `categoria_producto` c ON c.id_categoria = p.id_categoria
    WHERE p.activo = 1
      AND p.cantidad <= p_umbral
    ORDER BY p.cantidad ASC, p.nombre_producto ASC;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Procedimiento: resumen de ventas con filtros opcionales (null = sin filtro)
-- Lo usa Compra::totales() en app/models/Compra.php
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_resumen_ventas`;
DELIMITER //
CREATE PROCEDURE `sp_resumen_ventas`(IN p_fecha DATE, IN p_mes INT, IN p_anio INT)
BEGIN
    SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar), 0) AS monto
    FROM `compra`
    WHERE (p_fecha IS NULL OR fecha_compra = p_fecha)
      AND (p_mes IS NULL OR MONTH(fecha_compra) = p_mes)
      AND (p_anio IS NULL OR YEAR(fecha_compra) = p_anio);
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Procedimiento: agenda de citas de un día, opcionalmente por sucursal
-- Lo usa Reserva::citasDelDia() y Reserva::citasDelDiaSucursal()
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_agenda_dia`;
DELIMITER //
CREATE PROCEDURE `sp_agenda_dia`(IN p_fecha DATE, IN p_sucursal VARCHAR(10))
BEGIN
    SELECT r.id_reserva, r.hora, r.estado, r.estado_pago, r.id_sucursal,
           r.id_usuario, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente,
           u.telefono,
           COALESCE(GROUP_CONCAT(s.nombre SEPARATOR ', '), 'Sin servicio') AS servicio
    FROM `reserva` r
    LEFT JOIN `usuario` u ON u.id_usuario = r.id_usuario
    LEFT JOIN `detalle_reserva` dr ON dr.id_reserva = r.id_reserva
    LEFT JOIN `servicio` s ON s.id_servicio = dr.id_servicio
    WHERE r.fecha = p_fecha
      AND (p_sucursal IS NULL OR r.id_sucursal = p_sucursal)
    GROUP BY r.id_reserva
    ORDER BY r.hora ASC, r.id_reserva ASC;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Funciones escalares usadas por los modelos
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS `fn_total_ventas_dia`;
DELIMITER //
CREATE FUNCTION `fn_total_ventas_dia`(p_fecha DATE)
RETURNS DECIMAL(12,2)
READS SQL DATA
BEGIN
    DECLARE v_total DECIMAL(12,2) DEFAULT 0;
    SELECT COALESCE(SUM(total_pagar), 0) INTO v_total
    FROM `compra`
    WHERE fecha_compra = p_fecha;
    RETURN v_total;
END//
DELIMITER ;

DROP FUNCTION IF EXISTS `fn_contar_stock_bajo`;
DELIMITER //
CREATE FUNCTION `fn_contar_stock_bajo`(p_umbral INT)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*) INTO v_total
    FROM `producto`
    WHERE activo = 1 AND cantidad <= p_umbral;
    RETURN v_total;
END//
DELIMITER ;

DROP FUNCTION IF EXISTS `fn_citas_pendientes_sucursal`;
DELIMITER //
CREATE FUNCTION `fn_citas_pendientes_sucursal`(p_sucursal VARCHAR(10))
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*) INTO v_total
    FROM `reserva`
    WHERE id_sucursal = p_sucursal
      AND estado NOT IN ('cancelada', 'completada');
    RETURN v_total;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Trigger: auditoría de cambios de stock, precio o estado en productos.
-- NO modifica la cantidad: solo registra el cambio que ya hizo PHP.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_producto_au`;
DELIMITER //
CREATE TRIGGER `trg_producto_au`
AFTER UPDATE ON `producto`
FOR EACH ROW
BEGIN
    IF NOT (OLD.`cantidad` <=> NEW.`cantidad`) THEN
        INSERT INTO `auditoria_inventario` (`id_producto`, `nombre_producto`, `campo`, `valor_anterior`, `valor_nuevo`)
        VALUES (NEW.`id_producto`, NEW.`nombre_producto`, 'cantidad',
                CAST(OLD.`cantidad` AS CHAR), CAST(NEW.`cantidad` AS CHAR));
    END IF;
    IF NOT (OLD.`precio` <=> NEW.`precio`) THEN
        INSERT INTO `auditoria_inventario` (`id_producto`, `nombre_producto`, `campo`, `valor_anterior`, `valor_nuevo`)
        VALUES (NEW.`id_producto`, NEW.`nombre_producto`, 'precio',
                CAST(OLD.`precio` AS CHAR), CAST(NEW.`precio` AS CHAR));
    END IF;
    IF NOT (OLD.`activo` <=> NEW.`activo`) THEN
        INSERT INTO `auditoria_inventario` (`id_producto`, `nombre_producto`, `campo`, `valor_anterior`, `valor_nuevo`)
        VALUES (NEW.`id_producto`, NEW.`nombre_producto`, 'activo',
                CAST(OLD.`activo` AS CHAR), CAST(NEW.`activo` AS CHAR));
    END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Triggers de promociones:
--   BEFORE INSERT/UPDATE validan fechas y precios (integridad en la BD).
--   Después se registra auditoría. No se guarda el historial en otra tabla
--   de promociones; solo el evento.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_promocion_bi`;
DELIMITER //
CREATE TRIGGER `trg_promocion_bi`
BEFORE INSERT ON `promocion`
FOR EACH ROW
BEGIN
    IF NEW.`fecha_fin` < NEW.`fecha_inicio` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    END IF;
    IF NEW.`precio_promocional` < 0 OR NEW.`precio_original` < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los precios de la promoción no pueden ser negativos.';
    END IF;
    IF NEW.`precio_promocional` > NEW.`precio_original` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El precio promocional no puede superar el precio original.';
    END IF;

    INSERT INTO `auditoria_negocio` (`evento`, `entidad`, `id_entidad`, `detalle`)
    VALUES ('PROMOCION_CREADA', 'promocion', NEW.`id_promocion`,
            CONCAT(NEW.`nombre`, ' | ', NEW.`fecha_inicio`, ' a ', NEW.`fecha_fin`));
END//
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_promocion_bu`;
DELIMITER //
CREATE TRIGGER `trg_promocion_bu`
BEFORE UPDATE ON `promocion`
FOR EACH ROW
BEGIN
    IF NOT (OLD.`fecha_inicio` <=> NEW.`fecha_inicio`)
       OR NOT (OLD.`fecha_fin` <=> NEW.`fecha_fin`) THEN
        IF NEW.`fecha_fin` < NEW.`fecha_inicio` THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        END IF;
    END IF;
    IF NOT (OLD.`precio_original` <=> NEW.`precio_original`)
       OR NOT (OLD.`precio_promocional` <=> NEW.`precio_promocional`) THEN
        IF NEW.`precio_promocional` < 0 OR NEW.`precio_original` < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los precios de la promoción no pueden ser negativos.';
        END IF;
        IF NEW.`precio_promocional` > NEW.`precio_original` THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El precio promocional no puede superar el precio original.';
        END IF;
    END IF;

    IF NOT (OLD.`estado` <=> NEW.`estado`) THEN
        INSERT INTO `auditoria_negocio` (`evento`, `entidad`, `id_entidad`, `detalle`)
        VALUES ('PROMOCION_ESTADO', 'promocion', NEW.`id_promocion`,
                CONCAT(CAST(OLD.`estado` AS CHAR), ' -> ', CAST(NEW.`estado` AS CHAR)));
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_promocion_ad`;
DELIMITER //
CREATE TRIGGER `trg_promocion_ad`
AFTER DELETE ON `promocion`
FOR EACH ROW
BEGIN
    INSERT INTO `auditoria_negocio` (`evento`, `entidad`, `id_entidad`, `detalle`)
    VALUES ('PROMOCION_ELIMINADA', 'promocion', OLD.`id_promocion`, OLD.`nombre`);
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Triggers: auditoría de cambios de pago/estado en ventas y reservas.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_compra_au`;
DELIMITER //
CREATE TRIGGER `trg_compra_au`
AFTER UPDATE ON `compra`
FOR EACH ROW
BEGIN
    IF NOT (OLD.`estado_pago` <=> NEW.`estado_pago`) THEN
        INSERT INTO `auditoria_negocio` (`evento`, `entidad`, `id_entidad`, `detalle`)
        VALUES ('COMPRA_PAGO_ACTUALIZADO', 'compra', CAST(NEW.`id_compra` AS CHAR),
                CONCAT(OLD.`estado_pago`, ' -> ', NEW.`estado_pago`));
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_reserva_au`;
DELIMITER //
CREATE TRIGGER `trg_reserva_au`
AFTER UPDATE ON `reserva`
FOR EACH ROW
BEGIN
    IF NOT (OLD.`estado` <=> NEW.`estado`) THEN
        INSERT INTO `auditoria_negocio` (`evento`, `entidad`, `id_entidad`, `detalle`)
        VALUES ('RESERVA_ESTADO', 'reserva', CAST(NEW.`id_reserva` AS CHAR),
                CONCAT(CAST(OLD.`estado` AS CHAR), ' -> ', CAST(NEW.`estado` AS CHAR)));
    END IF;
    IF NOT (OLD.`estado_pago` <=> NEW.`estado_pago`) THEN
        INSERT INTO `auditoria_negocio` (`evento`, `entidad`, `id_entidad`, `detalle`)
        VALUES ('RESERVA_PAGO', 'reserva', CAST(NEW.`id_reserva` AS CHAR),
                CONCAT(OLD.`estado_pago`, ' -> ', NEW.`estado_pago`));
    END IF;
END//
DELIMITER ;
