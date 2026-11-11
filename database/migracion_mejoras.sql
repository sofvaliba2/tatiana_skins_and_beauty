-- ============================================================================
--  Tatiana Skin and Beauty
--  Migración de mejoras: contraseñas seguras, anticipos, pagos, respuestas
--  dinámicas de reserva y relación servicio - producto.
--
--  Ejecutar una sola vez sobre la base existente:
--    mysql -u root tatiana_skins_and_beauty_corregida < database/migracion_mejoras.sql
--
--  Es idempotente en la medida de lo posible (IF NOT EXISTS / INSERT IGNORE).
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Contraseñas modernas (password_hash) y correo único
-- ---------------------------------------------------------------------------
ALTER TABLE `cuenta_usuario`
    MODIFY `password` VARCHAR(255) NULL;

ALTER TABLE `cuenta_usuario`
    ADD UNIQUE KEY IF NOT EXISTS `uk_cuenta_correo` (`correo`);

-- ---------------------------------------------------------------------------
-- 2. Nuevos datos de la reserva: tratamiento, respuestas y estado de pago
-- ---------------------------------------------------------------------------
ALTER TABLE `reserva`
    ADD COLUMN IF NOT EXISTS `tipo_tratamiento` VARCHAR(30) NULL AFTER `hora`,
    ADD COLUMN IF NOT EXISTS `zona_tratamiento` VARCHAR(50) NULL AFTER `tipo_tratamiento`,
    ADD COLUMN IF NOT EXISTS `respuestas` LONGTEXT NULL AFTER `zona_tratamiento`,
    ADD COLUMN IF NOT EXISTS `precio_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `respuestas`,
    ADD COLUMN IF NOT EXISTS `anticipo` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `precio_total`,
    ADD COLUMN IF NOT EXISTS `monto_pagado` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `anticipo`,
    ADD COLUMN IF NOT EXISTS `saldo_pendiente` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `monto_pagado`,
    ADD COLUMN IF NOT EXISTS `estado_pago` VARCHAR(20) NOT NULL DEFAULT 'sin_pago' AFTER `saldo_pendiente`,
    ADD COLUMN IF NOT EXISTS `fecha_pago` DATETIME NULL AFTER `estado_pago`,
    ADD COLUMN IF NOT EXISTS `comprobante` VARCHAR(255) NULL AFTER `fecha_pago`,
    ADD COLUMN IF NOT EXISTS `codigo_pago` VARCHAR(40) NULL AFTER `comprobante`;

ALTER TABLE `reserva`
    ADD INDEX IF NOT EXISTS `idx_reserva_agenda` (`fecha`, `hora`, `id_sucursal`, `estado`),
    ADD INDEX IF NOT EXISTS `idx_reserva_pago` (`estado_pago`),
    ADD UNIQUE KEY IF NOT EXISTS `uk_reserva_codigo_pago` (`codigo_pago`);

-- ---------------------------------------------------------------------------
-- 3. Historial/auditoría de pagos de reservas
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pago_reserva` (
    `id_pago` INT(11) NOT NULL AUTO_INCREMENT,
    `id_reserva` INT(11) NOT NULL,
    `tipo` VARCHAR(20) NOT NULL DEFAULT 'anticipo',
    `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `id_metodoPago` VARCHAR(10) DEFAULT NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'por_verificar',
    `comprobante` VARCHAR(255) DEFAULT NULL,
    `codigo` VARCHAR(40) DEFAULT NULL,
    `fecha_registro` DATETIME DEFAULT NULL,
    `fecha_verificacion` DATETIME DEFAULT NULL,
    `verificado_por` VARCHAR(100) DEFAULT NULL,
    `observacion` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_pago`),
    UNIQUE KEY `uk_pago_codigo` (`codigo`),
    KEY `idx_pago_reserva` (`id_reserva`),
    KEY `fk_pago_metodoPago` (`id_metodoPago`),
    CONSTRAINT `fk_pago_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE,
    CONSTRAINT `fk_pago_metodoPago` FOREIGN KEY (`id_metodoPago`) REFERENCES `metodo_pago` (`id_metodoPago`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 4. Relación real servicio -> productos recomendados (solo catálogo existente)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `servicio_producto` (
    `id_servicio` VARCHAR(10) NOT NULL,
    `id_producto` VARCHAR(10) NOT NULL,
    `recomendado` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_servicio`, `id_producto`),
    KEY `fk_sp_producto` (`id_producto`),
    CONSTRAINT `fk_sp_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE,
    CONSTRAINT `fk_sp_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 5. Métodos de pago: normalizar estado y agregar QR / Transferencia
-- ---------------------------------------------------------------------------
UPDATE `metodo_pago` SET `estado` = LOWER(`estado`) WHERE `estado` IS NOT NULL;

INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`)
VALUES ('MP02', 'QR / Transferencia', 'Digital', 'activo')
ON DUPLICATE KEY UPDATE `nombre_metodoPago` = VALUES(`nombre_metodoPago`), `tipo` = VALUES(`tipo`);

-- ---------------------------------------------------------------------------
-- 6. Vínculos de ejemplo usando SOLO productos/servicios existentes.
--    (No se crean servicios ni productos ficticios.)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `servicio_producto` (`id_servicio`, `id_producto`, `recomendado`) VALUES
    ('SERV01', 'PROD01', 1),   -- Hidrafacial -> Sérum Humectante
    ('SERV01', 'PROD04', 1),   -- Hidrafacial -> Mascarilla Hidratante
    ('SERV01', 'PROD02', 1),   -- Hidrafacial -> Crema Antiage
    ('SERV02', 'PROD01', 1),   -- Depilación facial -> Sérum Humectante
    ('SERV03', 'PROD03', 1),   -- Depilación axilas -> Exfoliante Corporal
    ('SERV04', 'PROD03', 1);   -- Depilación piernas -> Exfoliante Corporal

-- ---------------------------------------------------------------------------
-- 7. Recalcular importes de reservas existentes
-- ---------------------------------------------------------------------------
UPDATE `reserva` r
LEFT JOIN (
    SELECT `id_reserva`, COALESCE(SUM(`subtotal`), 0) AS total
    FROM `detalle_reserva`
    GROUP BY `id_reserva`
) d ON d.`id_reserva` = r.`id_reserva`
SET
    r.`precio_total`    = COALESCE(d.total, 0),
    r.`anticipo`        = LEAST(30.00, COALESCE(d.total, 0)),
    r.`monto_pagado`    = 0.00,
    r.`saldo_pendiente` = COALESCE(d.total, 0),
    r.`estado_pago`     = CASE
        WHEN r.`id_metodoPago` = 'MP01' THEN 'pendiente_efectivo'
        ELSE 'sin_pago'
    END
WHERE r.`precio_total` = 0;

-- Clasificar las reservas existentes como facial / depilación.
UPDATE `reserva` r
JOIN `detalle_reserva` dr ON dr.`id_reserva` = r.`id_reserva`
JOIN `servicio` s ON s.`id_servicio` = dr.`id_servicio`
SET r.`tipo_tratamiento` = CASE
    WHEN LOWER(s.`nombre`) LIKE '%depil%' OR s.`id_categoriaS` = 'CS01' THEN 'depilacion'
    ELSE 'facial'
END
WHERE r.`tipo_tratamiento` IS NULL;

-- ---------------------------------------------------------------------------
-- 8. Verificación rápida
-- ---------------------------------------------------------------------------
SELECT 'cuenta_usuario.password' AS revision, COLUMN_TYPE AS detalle
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cuenta_usuario' AND COLUMN_NAME = 'password'
UNION ALL
SELECT 'reserva.columnas_pago', GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reserva'
  AND COLUMN_NAME IN ('precio_total','anticipo','monto_pagado','saldo_pendiente','estado_pago','fecha_pago','comprobante','codigo_pago','respuestas','tipo_tratamiento','zona_tratamiento')
UNION ALL
SELECT 'tablas_nuevas', GROUP_CONCAT(TABLE_NAME ORDER BY TABLE_NAME)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('pago_reserva','servicio_producto')
UNION ALL
SELECT 'metodos_pago', GROUP_CONCAT(CONCAT(id_metodoPago, ':', nombre_metodoPago, ':', estado) ORDER BY id_metodoPago)
FROM metodo_pago;
