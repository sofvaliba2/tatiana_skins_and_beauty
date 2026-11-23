<?php
declare(strict_types=1);

namespace App\Models;

final class Producto extends Model
{
    private const SELECT = 'SELECT p.*, c.nombre_categoria
                FROM producto p
                LEFT JOIN categoria_producto c ON c.id_categoria = p.id_categoria';

    public static function categorias(): array
    {
        return self::all('SELECT * FROM categoria_producto ORDER BY nombre_categoria');
    }

    public static function buscar(string $q, string $categoria, int $pagina, int $porPagina, bool $incluirInactivos = false): array
    {
        $sql = self::SELECT . ' WHERE 1';
        if (!$incluirInactivos) {
            $sql .= ' AND p.activo = 1';
        }
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (p.nombre_producto LIKE ? OR p.descripcion LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($categoria !== '') {
            $sql .= ' AND p.id_categoria = ?';
            $params[] = $categoria;
        }
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY p.nombre_producto LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    public static function porId(string $id): ?array
    {
        return self::one(self::SELECT . ' WHERE p.id_producto = ?', [$id]);
    }

    public static function relacionados(string $idCategoria, string $idExcluir, int $limite = 4): array
    {
        return self::all(
            self::SELECT . ' WHERE p.activo = 1 AND p.id_categoria = ? AND p.id_producto <> ? LIMIT ' . $limite,
            [$idCategoria, $idExcluir]
        );
    }

    public static function destacados(int $limite = 4): array
    {
        return self::all(
            'SELECT p.*, cp.nombre_categoria AS categoria
             FROM producto p
             LEFT JOIN categoria_producto cp ON cp.id_categoria = p.id_categoria
             WHERE p.activo = 1
             ORDER BY p.precio DESC
             LIMIT ' . $limite
        );
    }

    public static function conStock(int $limite = 6): array
    {
        return self::all(self::SELECT . ' WHERE p.activo = 1 AND p.cantidad > 0 ORDER BY p.precio DESC LIMIT ' . $limite);
    }

    public static function paraCarrito(string $id): ?array
    {
        return self::one(
            'SELECT id_producto, nombre_producto, descripcion, precio, cantidad AS stock, imagen_url
             FROM producto WHERE id_producto = ? AND activo = 1',
            [$id]
        );
    }

    public static function stock(string $id): int
    {
        return (int)self::value('SELECT cantidad FROM producto WHERE id_producto = ?', [$id]);
    }

    public static function lista(bool $incluirInactivos = false): array
    {
        $where = $incluirInactivos ? '' : ' WHERE activo = 1';
        return self::all('SELECT id_producto, nombre_producto, precio, cantidad FROM producto' . $where . ' ORDER BY nombre_producto');
    }

    /**
     * Productos con poco stock. La consulta vive en MySQL:
     * sp_productos_stock_bajo (ver database/migration_objetos_negocio.sql).
     */
    public static function stockBajo(int $umbral = 3): array
    {
        return self::llamar('sp_productos_stock_bajo', [$umbral]);
    }

    /** Conteo de stock bajo mediante fn_contar_stock_bajo (MySQL). */
    public static function contarStockBajo(int $umbral = 3): int
    {
        return (int)self::value('SELECT fn_contar_stock_bajo(?)', [$umbral]);
    }

    public static function descontarStock(string $id, int $cantidad): void
    {
        self::run('UPDATE producto SET cantidad = cantidad - ? WHERE id_producto = ?', [$cantidad, $id]);
    }

    /** Descuenta stock solo si hay unidades suficientes (evita sobreventa). */
    public static function descontarStockSeguro(string $id, int $cantidad): bool
    {
        return self::run(
            'UPDATE producto SET cantidad = cantidad - ? WHERE id_producto = ? AND cantidad >= ?',
            [$cantidad, $id, $cantidad]
        ) > 0;
    }

    public static function guardar(?string $idEditar, array $datos): array
    {
        return self::transaccion(function () use ($idEditar, $datos) {
            if ($idEditar !== null) {
                self::run('UPDATE producto SET ' . self::sets($datos) . ' WHERE id_producto = ?', [...array_values($datos), $idEditar]);
                return [$idEditar, false];
            }
            return [Ids::insertarConCodigo('producto', 'id_producto', 'PROD', 2, $datos), true];
        });
    }

    /**
     * Cuenta las relaciones históricas del producto. Si alguna existe, no se
     * debe borrar físicamente: se aplica baja lógica para conservar compras,
     * promociones y recomendaciones de servicios.
     */
    public static function relaciones(string $id): array
    {
        return [
            'compras' => (int)self::value('SELECT COUNT(*) FROM detalle_compra WHERE id_producto = ?', [$id]),
            'promociones' => (int)self::value('SELECT COUNT(*) FROM promocion WHERE id_producto = ?', [$id]),
            'servicios' => (int)self::value('SELECT COUNT(*) FROM servicio_producto WHERE id_producto = ?', [$id]),
        ];
    }

    public static function tieneHistorial(string $id): bool
    {
        return array_sum(self::relaciones($id)) > 0;
    }

    /** Baja lógica: el producto deja de mostrarse pero su historial se conserva. */
    public static function desactivar(string $id): bool
    {
        return self::run('UPDATE producto SET activo = 0 WHERE id_producto = ? AND activo = 1', [$id]) > 0;
    }

    public static function activar(string $id): bool
    {
        return self::run('UPDATE producto SET activo = 1 WHERE id_producto = ? AND activo = 0', [$id]) > 0;
    }

    /**
     * Eliminación física solo permitida cuando no hay relaciones históricas.
     * Devuelve false si el producto tiene historial (el llamador aplica baja lógica).
     */
    public static function eliminar(string $id): bool
    {
        if (self::tieneHistorial($id)) {
            return false;
        }
        return self::transaccion(function () use ($id) {
            return self::run('DELETE FROM producto WHERE id_producto = ?', [$id]) > 0;
        });
    }
}
