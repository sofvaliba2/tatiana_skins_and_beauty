-- Migración: seguridad de acceso (intentos de login + recuperación de contraseña)
-- Base de datos: tatiana_skins_and_beauty_corregida
-- Ejecutar: mysql -u root tatiana_skins_and_beauty_corregida < database/migration_seguridad_acceso.sql

-- Intentos fallidos por cuenta + IP. Persiste en servidor: no se puede evadir
-- recargando la página o cerrando el navegador.
CREATE TABLE IF NOT EXISTS `seguridad_login` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `correo` VARCHAR(100) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `intentos` SMALLINT(6) NOT NULL DEFAULT 0,
    `bloqueado_hasta` DATETIME DEFAULT NULL,
    `ultimo_intento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `actualizado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_seguridad_correo_ip` (`correo`, `ip`),
    KEY `idx_seguridad_bloqueo` (`bloqueado_hasta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tokens de un solo uso para restablecer contraseña. Solo se guarda el hash SHA-256;
-- el token en claro viaja únicamente en el correo.
CREATE TABLE IF NOT EXISTS `recuperacion_password` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `id_usuario` VARCHAR(100) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expira_en` DATETIME NOT NULL,
    `usado_en` DATETIME DEFAULT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `ip` VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_recuperacion_token` (`token_hash`),
    KEY `idx_recuperacion_usuario` (`id_usuario`),
    KEY `idx_recuperacion_expira` (`expira_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
