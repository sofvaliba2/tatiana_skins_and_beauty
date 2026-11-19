-- Migración: SMTP + límite de solicitudes de recuperación
-- Base de datos: tatiana_skins_and_beauty_corregida
-- Ejecutar: mysql -u root tatiana_skins_and_beauty_corregida < database/migration_smtp_recuperacion.sql
--
-- La tabla `recuperacion_password` ya existe y contiene todo lo necesario:
-- token_hash, expira_en, usado_en, creado_en e ip. No se crea otra tabla.
-- Solo se añade un índice para consultar rápido las solicitudes por IP
-- dentro de la ventana de protección contra solicitudes excesivas.

SET @existe_indice = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'recuperacion_password'
      AND INDEX_NAME = 'idx_recuperacion_ip'
);

SET @sql_indice = IF(
    @existe_indice = 0,
    'ALTER TABLE `recuperacion_password` ADD KEY `idx_recuperacion_ip` (`ip`, `creado_en`)',
    'SELECT 1'
);

PREPARE stmt_indice FROM @sql_indice;
EXECUTE stmt_indice;
DEALLOCATE PREPARE stmt_indice;
