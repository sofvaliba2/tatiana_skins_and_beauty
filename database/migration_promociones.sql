-- ============================================================
--  Módulo de Promociones - Tatiana Skin and Beauty
--  Base de datos: tatiana_skins_beauty_corregida
--  Aplicar con:
--     mysql -u root tatiana_skins_beauty_corregida < database/migration_promociones.sql
--  Solo AGREGA la tabla `promocion` y 4 vistas. No modifica
--  ninguna tabla existente ni borra datos.
-- ============================================================

-- ------------------------------------------------------------
-- 1) Tabla de promociones
--    Una promoción apunta a un producto o a un servicio (o a
--    ambos). El descuento (%) se calcula en las vistas a partir
--    de los precios, para que nunca quede desincronizado.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promocion (
    id_promocion       VARCHAR(10)   NOT NULL,
    nombre             VARCHAR(100)  NOT NULL,
    descripcion        VARCHAR(255)  DEFAULT NULL,
    id_producto        VARCHAR(10)   DEFAULT NULL,
    id_servicio        VARCHAR(10)   DEFAULT NULL,
    precio_original    DECIMAL(10,2) NOT NULL,
    precio_promocional DECIMAL(10,2) NOT NULL,
    fecha_inicio       DATE          NOT NULL,
    fecha_fin          DATE          NOT NULL,
    estado             VARCHAR(10)   NOT NULL DEFAULT 'activa',
    imagen_url         VARCHAR(200)  DEFAULT NULL,
    fecha_registro     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_promocion),
    KEY idx_promocion_fechas (fecha_inicio, fecha_fin),
    KEY idx_promocion_estado (estado),
    KEY idx_promocion_producto (id_producto),
    KEY idx_promocion_servicio (id_servicio),
    CONSTRAINT fk_promocion_producto FOREIGN KEY (id_producto)
        REFERENCES producto (id_producto) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_promocion_servicio FOREIGN KEY (id_servicio)
        REFERENCES servicio (id_servicio) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 2) Vista general (todas las promociones)
--    Columnas: id, título, descripción, artículo asociado,
--    precio original, precio promocional, % descuento,
--    fechas, estado (manual) y estado_calculado (por fechas),
--    días restantes e imagen.
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_promociones AS
SELECT
    p.id_promocion                            AS id,
    p.id_promocion,
    p.nombre                                  AS titulo,
    p.nombre,
    p.descripcion,
    p.id_producto,
    p.id_servicio,
    CASE
        WHEN p.id_servicio IS NOT NULL AND p.id_producto IS NOT NULL THEN 'producto_servicio'
        WHEN p.id_servicio IS NOT NULL THEN 'servicio'
        WHEN p.id_producto IS NOT NULL THEN 'producto'
        ELSE 'general'
    END                                       AS tipo_articulo,
    COALESCE(pr.nombre_producto, s.nombre, p.nombre) AS articulo,
    p.precio_original,
    p.precio_promocional,
    CASE
        WHEN p.precio_original > 0
        THEN ROUND((p.precio_original - p.precio_promocional) / p.precio_original * 100, 2)
        ELSE 0
    END                                       AS descuento,
    p.fecha_inicio,
    p.fecha_fin,
    p.estado,
    CASE
        WHEN p.estado <> 'activa'        THEN 'inactiva'
        WHEN CURDATE() < p.fecha_inicio  THEN 'programada'
        WHEN CURDATE() > p.fecha_fin     THEN 'vencida'
        ELSE 'activa'
    END                                       AS estado_calculado,
    DATEDIFF(p.fecha_fin, CURDATE())          AS dias_restantes,
    p.imagen_url,
    p.fecha_registro
FROM promocion p
LEFT JOIN producto pr ON pr.id_producto = p.id_producto
LEFT JOIN servicio s  ON s.id_servicio  = p.id_servicio;

-- ------------------------------------------------------------
-- 3) Promociones activas (vigentes hoy y habilitadas)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_promociones_activas AS
SELECT * FROM vw_promociones
WHERE estado = 'activa'
  AND CURDATE() BETWEEN fecha_inicio AND fecha_fin;

-- ------------------------------------------------------------
-- 4) Promociones vencidas (su fecha de fin ya pasó)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_promociones_vencidas AS
SELECT * FROM vw_promociones
WHERE CURDATE() > fecha_fin;

-- ------------------------------------------------------------
-- 5) Promociones por vencer (activas y a 7 días o menos de
--    su fecha de fin)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_promociones_por_vencer AS
SELECT * FROM vw_promociones
WHERE estado = 'activa'
  AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
  AND DATEDIFF(fecha_fin, CURDATE()) <= 7;
