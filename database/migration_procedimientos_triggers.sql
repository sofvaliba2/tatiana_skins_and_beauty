-- Migración: procedimientos, funciones y triggers de seguridad
-- Base de datos: tatiana_skins_and_beauty_corregida
-- Ejecutar:
--   mysql -u root tatiana_skins_and_beauty_corregida < database/migration_procedimientos_triggers.sql
--
-- UBICACIÓN EN EL PROYECTO
--   Definición SQL ................ database/migration_procedimientos_triggers.sql
--   Llamadas desde backend ........ app/models/RecuperacionPassword.php
--   Auditoría generada ............ tabla auditoria_seguridad (MySQL)
--   Frontend ...................... NINGUNO. Las vistas solo envían datos a rutas PHP;
--                                   nunca llaman procedimientos ni triggers directamente.

-- ---------------------------------------------------------------------------
-- Tabla de auditoría (no duplica tokens ni cuentas: solo registra eventos)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria_seguridad` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `evento` VARCHAR(40) NOT NULL,
    `correo` VARCHAR(100) DEFAULT NULL,
    `id_usuario` VARCHAR(100) DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `detalle` VARCHAR(255) DEFAULT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `idx_auditoria_evento_fecha` (`evento`, `creado_en`),
    KEY `idx_auditoria_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- Procedimiento: limpieza de tokens vencidos/usados
-- Lo llama RecuperacionPassword::limpiar() antes de generar un token nuevo.
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_limpiar_recuperacion`;
DELIMITER //
CREATE PROCEDURE `sp_limpiar_recuperacion`(IN p_dias INT)
BEGIN
    DELETE FROM `recuperacion_password`
    WHERE `creado_en` < DATE_SUB(NOW(), INTERVAL p_dias DAY)
      AND (`expira_en` < NOW() OR `usado_en` IS NOT NULL);
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Funciones: ventana de protección contra solicitudes excesivas (60 minutos)
-- Las usa RecuperacionPassword::solicitudesRecientes() y ::segundosEspera().
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS `fn_solicitudes_recientes`;
DELIMITER //
CREATE FUNCTION `fn_solicitudes_recientes`(p_ip VARCHAR(45))
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*) INTO v_total
    FROM `recuperacion_password`
    WHERE `ip` = p_ip
      AND `creado_en` >= DATE_SUB(NOW(), INTERVAL 60 MINUTE);
    RETURN v_total;
END//
DELIMITER ;

DROP FUNCTION IF EXISTS `fn_segundos_espera_recuperacion`;
DELIMITER //
CREATE FUNCTION `fn_segundos_espera_recuperacion`(p_ip VARCHAR(45))
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_antiguo DATETIME DEFAULT NULL;
    SELECT MIN(`creado_en`) INTO v_antiguo
    FROM `recuperacion_password`
    WHERE `ip` = p_ip
      AND `creado_en` >= DATE_SUB(NOW(), INTERVAL 60 MINUTE);

    IF v_antiguo IS NULL THEN
        RETURN 3600;
    END IF;
    RETURN GREATEST(1, 3600 - TIMESTAMPDIFF(SECOND, v_antiguo, NOW()));
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Trigger: auditoría al solicitar un token de recuperación
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_recuperacion_password_ai`;
DELIMITER //
CREATE TRIGGER `trg_recuperacion_password_ai`
AFTER INSERT ON `recuperacion_password`
FOR EACH ROW
BEGIN
    DECLARE v_correo VARCHAR(100) DEFAULT NULL;
    SELECT `correo` INTO v_correo
    FROM `cuenta_usuario`
    WHERE `id_usuario` = NEW.`id_usuario`
    LIMIT 1;

    INSERT INTO `auditoria_seguridad` (`evento`, `correo`, `id_usuario`, `ip`, `detalle`)
    VALUES ('RECUPERACION_SOLICITADA', v_correo, NEW.`id_usuario`, NEW.`ip`, 'Token de un solo uso generado');
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Trigger: auditoría cuando el token se consume
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_recuperacion_password_au`;
DELIMITER //
CREATE TRIGGER `trg_recuperacion_password_au`
AFTER UPDATE ON `recuperacion_password`
FOR EACH ROW
BEGIN
    IF OLD.`usado_en` IS NULL AND NEW.`usado_en` IS NOT NULL THEN
        INSERT INTO `auditoria_seguridad` (`evento`, `id_usuario`, `ip`, `detalle`)
        VALUES ('RECUPERACION_USADA', NEW.`id_usuario`, NEW.`ip`, 'Token marcado como usado');
    END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Trigger: auditoría cuando cambia la contraseña de cualquier cuenta
-- No guarda la contraseña, solo el hecho del cambio.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_cuenta_usuario_password_au`;
DELIMITER //
CREATE TRIGGER `trg_cuenta_usuario_password_au`
AFTER UPDATE ON `cuenta_usuario`
FOR EACH ROW
BEGIN
    IF OLD.`password` <> NEW.`password` THEN
        INSERT INTO `auditoria_seguridad` (`evento`, `correo`, `id_usuario`, `detalle`)
        VALUES ('PASSWORD_CAMBIADA', NEW.`correo`, NEW.`id_usuario`, 'Contraseña actualizada');
    END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- Trigger: auditoría cuando se activa el bloqueo temporal por intentos fallidos
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_seguridad_login_bloqueo_au`;
DELIMITER //
CREATE TRIGGER `trg_seguridad_login_bloqueo_au`
AFTER UPDATE ON `seguridad_login`
FOR EACH ROW
BEGIN
    IF OLD.`bloqueado_hasta` IS NULL AND NEW.`bloqueado_hasta` IS NOT NULL THEN
        INSERT INTO `auditoria_seguridad` (`evento`, `correo`, `ip`, `detalle`)
        VALUES ('LOGIN_BLOQUEADO', OLD.`correo`, OLD.`ip`, 'Bloqueo temporal por intentos fallidos');
    END IF;
END//
DELIMITER ;
