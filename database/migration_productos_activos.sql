-- Migración: baja lógica de productos
-- Base de datos: tatiana_skins_and_beauty_corregida
-- Ejecutar: mysql -u root tatiana_skins_and_beauty_corregida < database/migration_productos_activos.sql

-- El producto no tiene columna de estado. Se agrega para poder desactivar
-- productos con historial en detalle_compra / promocion / servicio_producto
-- sin borrar información comercial importante.
ALTER TABLE `producto`
    ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `id_categoria`,
    ADD INDEX IF NOT EXISTS `idx_producto_activo` (`activo`);

UPDATE `producto` SET `activo` = 1 WHERE `activo` IS NULL;
