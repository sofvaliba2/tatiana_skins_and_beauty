-- Pago con QR/comprobante para compras de productos (flujo del cliente).
-- Ejecutar una sola vez sobre tatiana_skins_and_beauty_corregida.

ALTER TABLE `compra`
  ADD COLUMN `estado_pago` varchar(20) NOT NULL DEFAULT 'pagado' AFTER `id_metodoPago`,
  ADD COLUMN `codigo_pago` varchar(40) DEFAULT NULL AFTER `estado_pago`,
  ADD COLUMN `comprobante` varchar(255) DEFAULT NULL AFTER `codigo_pago`,
  ADD COLUMN `comprobante_nombre` varchar(255) DEFAULT NULL AFTER `comprobante`,
  ADD COLUMN `comprobante_tipo` varchar(60) DEFAULT NULL AFTER `comprobante_nombre`,
  ADD COLUMN `comprobante_datos` longblob DEFAULT NULL AFTER `comprobante_tipo`,
  ADD COLUMN `fecha_pago` datetime DEFAULT NULL AFTER `comprobante_datos`,
  ADD COLUMN `fecha_verificacion` datetime DEFAULT NULL AFTER `fecha_pago`,
  ADD COLUMN `verificado_por` varchar(100) DEFAULT NULL AFTER `fecha_verificacion`,
  ADD COLUMN `observacion` varchar(255) DEFAULT NULL AFTER `verificado_por`,
  ADD COLUMN `id_sucursal` varchar(10) DEFAULT NULL AFTER `observacion`,
  ADD UNIQUE KEY `uk_compra_codigo_pago` (`codigo_pago`),
  ADD KEY `fk_sucursalCompra` (`id_sucursal`),
  ADD CONSTRAINT `fk_sucursalCompra` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL;
