-- ============================================================
-- Migración: módulo de empleados (ROL02)
-- Base de datos: tatiana_skins_and_beauty_stea
-- Motor: MariaDB 10.4 / MySQL 5.7+
--
-- Esta migración SOLO agrega una columna y un índice. No elimina
-- ni modifica datos existentes.
--
-- Ejecutar una sola vez (es idempotente):
--   mysql -u root -p tatiana_skins_and_beauty_stea < database/migration_empleados.sql
-- o pegarla en phpMyAdmin > pestaña SQL.
-- ============================================================

SET @db := DATABASE();

-- 1) Permite registrar servicios dentro del detalle de una venta.
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `detalle_compra` ADD COLUMN `id_servicio` VARCHAR(10) NULL AFTER `id_producto`',
        'SELECT ''La columna detalle_compra.id_servicio ya existe.'' AS aviso'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'detalle_compra'
      AND COLUMN_NAME = 'id_servicio'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Índice para consultar ventas por servicio.
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `detalle_compra` ADD KEY `idx_detalle_compra_servicio` (`id_servicio`)',
        'SELECT ''El índice idx_detalle_compra_servicio ya existe.'' AS aviso'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'detalle_compra'
      AND INDEX_NAME = 'idx_detalle_compra_servicio'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3) Verificación.
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'detalle_compra'
  AND COLUMN_NAME = 'id_servicio';

-- 4) Métodos de pago base (idempotente, no sobrescribe los existentes).
INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`)
SELECT 'MP01', 'Efectivo', 'efectivo', 'activo' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `metodo_pago` WHERE `id_metodoPago` = 'MP01');

INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`)
SELECT 'MP02', 'Transferencia', 'transferencia', 'activo' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `metodo_pago` WHERE `id_metodoPago` = 'MP02');

INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`)
SELECT 'MP03', 'QR', 'qr', 'activo' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `metodo_pago` WHERE `id_metodoPago` = 'MP03');

INSERT INTO `metodo_pago` (`id_metodoPago`, `nombre_metodoPago`, `tipo`, `estado`)
SELECT 'MP04', 'Tarjeta', 'tarjeta', 'activo' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `metodo_pago` WHERE `id_metodoPago` = 'MP04');

-- 5) Verificación de métodos de pago.
SELECT `id_metodoPago`, `nombre_metodoPago`, `estado`
FROM `metodo_pago`
ORDER BY `id_metodoPago`;
