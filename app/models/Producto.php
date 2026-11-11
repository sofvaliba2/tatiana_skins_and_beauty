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

    public static function buscar(string $q, string $categoria, int $pagina, int $porPagina): array
    {
        $sql = self::SELECT . ' WHERE 1';
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
            self::SELECT . ' WHERE p.id_categoria = ? AND p.id_producto <> ? LIMIT ' . $limite,
            [$idCategoria, $idExcluir]
        );
    }

    public static function destacados(int $limite = 4): array
    {
        return self::all(
            'SELECT p.*, cp.nombre_categoria AS categoria
             FROM producto p
             LEFT JOIN categoria_producto cp ON cp.id_categoria = p.id_categoria
             ORDER BY p.precio DESC
             LIMIT ' . $limite
        );
    }

    public static function conStock(int $limite = 6): array
    {
        return self::all(self::SELECT . ' WHERE p.cantidad > 0 ORDER BY p.precio DESC LIMIT ' . $limite);
    }

    public static function paraCarrito(string $id): ?array
    {
        return self::one(
            'SELECT id_producto, nombre_producto, descripcion, precio, cantidad AS stock, imagen_url
             FROM producto WHERE id_producto = ?',
            [$id]
        );
    }

    public static function stock(string $id): int
    {
        return (int)self::value('SELECT cantidad FROM producto WHERE id_producto = ?', [$id]);
    }

    public static function lista(): array
    {
        return self::all('SELECT id_producto, nombre_producto, precio, cantidad FROM producto ORDER BY nombre_producto');
    }

    public static function stockBajo(int $umbral = 3): array
    {
        return self::all(self::SELECT . ' WHERE p.cantidad <= ? ORDER BY p.cantidad ASC', [$umbral]);
    }

    public static function contarStockBajo(int $umbral = 3): int
    {
        return (int)self::value('SELECT COUNT(*) FROM producto WHERE cantidad <= ?', [$umbral]);
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
}
