-- Migración: imágenes para los servicios del catálogo
-- Base de datos: tatiana_skins_and_beauty_corregida
-- Ejecutar: mysql -u root tatiana_skins_and_beauty_corregida < database/migration_servicios_imagenes.sql

ALTER TABLE `servicio`
    ADD COLUMN IF NOT EXISTS `imagen_url` VARCHAR(255) DEFAULT NULL AFTER `descripcion`;
