-- ---------------------------------------------------------------------------
-- Comprobantes de pago guardados dentro de MySQL
--
-- Los comprobantes que envían las clientas se guardan como imagen binaria en
-- pago_reserva (además de la copia en disco que ya existía). Así el personal
-- puede verlos aunque el archivo se mueva o se borre del servidor.
--
-- Ejecutar una sola vez sobre la base de datos:
--   mysql -uroot tatiana_skins_and_beauty_corregida < database/migration_comprobantes_bd.sql
-- ---------------------------------------------------------------------------

ALTER TABLE `pago_reserva`
    ADD COLUMN IF NOT EXISTS `comprobante_nombre` VARCHAR(255) NULL AFTER `comprobante`,
    ADD COLUMN IF NOT EXISTS `comprobante_tipo` VARCHAR(60) NULL AFTER `comprobante_nombre`,
    ADD COLUMN IF NOT EXISTS `comprobante_datos` LONGBLOB NULL AFTER `comprobante_tipo`;

-- ---------------------------------------------------------------------------
-- Categorías de servicios al día
-- ---------------------------------------------------------------------------

INSERT INTO `categoria_servicio` (`id_categoriaS`, `nombre_cservicio`) VALUES
    ('CS01', 'Depilaciones'),
    ('CS02', 'Faciales'),
    ('CS03', 'Corporales')
ON DUPLICATE KEY UPDATE `nombre_cservicio` = VALUES(`nombre_cservicio`);

UPDATE `servicio` SET `id_categoriaS` = 'CS02' WHERE `id_servicio` = 'SERV01';
UPDATE `servicio` SET `id_categoriaS` = 'CS01' WHERE `id_servicio` IN ('SERV02', 'SERV03', 'SERV04');
