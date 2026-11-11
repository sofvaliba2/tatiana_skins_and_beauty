<?php
declare(strict_types=1);

namespace App\Models;

final class Compra extends Model
{
    /**
     * Registra una compra. Los precios y el stock SIEMPRE se leen de MySQL:
     * lo que llega del carrito (sesión o formulario) no puede alterar importes.
     */
    public static function crear(string $idUsuario, ?string $idMetodo, array $items): array
    {
        return self::transaccion(function () use ($idUsuario, $idMetodo, $items) {
            $detalle = [];
            $total = 0.0;

            foreach ($items as $item) {
                $idProducto = (string)($item['id'] ?? $item['id_producto'] ?? '');
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($idProducto === '' || $cantidad <= 0) {
                    throw new \RuntimeException('El carrito contiene un producto no válido.');
                }
                $producto = Producto::paraCarrito($idProducto);
                if ($producto === null) {
                    throw new \RuntimeException('Un producto del carrito ya no existe.');
                }
                if ((int)$producto['stock'] < $cantidad) {
                    throw new \RuntimeException('No hay stock suficiente de ' . $producto['nombre_producto'] . '.');
                }
                $precio = (float)$producto['precio'];
                if ($precio < 0) {
                    throw new \RuntimeException('El precio de un producto no es válido.');
                }
                $cantidad = min($cantidad, (int)$producto['stock']);
                $subtotal = $precio * $cantidad;
                $total += $subtotal;
                $detalle[] = [
                    'id'       => $idProducto,
                    'cantidad' => $cantidad,
                    'precio'   => $precio,
                    'subtotal' => $subtotal,
                ];
            }

            if ($detalle === []) {
                throw new \RuntimeException('El carrito está vacío.');
            }

            $nro = 'C-' . str_pad((string)((int)self::value('SELECT COALESCE(MAX(id_compra),0) FROM compra') + 1), 6, '0', STR_PAD_LEFT);
            $idCompra = Ids::insertarConEntero('compra', 'id_compra', [
                'fecha_compra'  => date('Y-m-d'),
                'nro_compra'    => $nro,
                'total_pagar'   => $total,
                'id_usuario'    => $idUsuario,
                'id_metodoPago' => $idMetodo,
            ]);
            foreach ($detalle as $item) {
                // Descuento condicional: si otra compra agotó el stock, se revierte todo.
                if (!Producto::descontarStockSeguro($item['id'], $item['cantidad'])) {
                    throw new \RuntimeException('El stock cambió mientras confirmabas la compra. Revisa tu carrito.');
                }
                Ids::insertarConEntero('detalle_compra', 'id_detalle', [
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal'        => $item['subtotal'],
                    'id_compra'       => $idCompra,
                    'id_producto'     => $item['id'],
                ]);
            }
            return ['id' => $idCompra, 'nro' => $nro, 'total' => $total];
        });
    }

    public static function deUsuario(string $idUsuario, int $pagina, int $porPagina): array
    {
        $total = (int)self::value('SELECT COUNT(*) FROM compra WHERE id_usuario = ?', [$idUsuario]);
        $filas = self::all(
            'SELECT c.*, m.nombre_metodoPago,
                    (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items
             FROM compra c
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             WHERE c.id_usuario = ?
             ORDER BY c.fecha_compra DESC, c.id_compra DESC
             LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina),
            [$idUsuario]
        );
        return ['filas' => $filas, 'total' => $total];
    }

    public static function recientesDeUsuario(string $idUsuario, int $limite = 3): array
    {
        return self::all(
            'SELECT * FROM compra WHERE id_usuario = ? ORDER BY fecha_compra DESC, id_compra DESC LIMIT ' . $limite,
            [$idUsuario]
        );
    }

    public static function porId(int $id): ?array
    {
        return self::one(
            'SELECT c.*, m.nombre_metodoPago, u.nombre, u.ap_paterno, u.ap_materno
             FROM compra c
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             WHERE c.id_compra = ?',
            [$id]
        );
    }

    public static function items(int $idCompra): array
    {
        return self::all(
            'SELECT d.*, p.nombre_producto, p.imagen_url, p.id_categoria
             FROM detalle_compra d
             JOIN producto p ON p.id_producto = d.id_producto
             WHERE d.id_compra = ?',
            [$idCompra]
        );
    }

    public static function ventas(string $fecha, int $pagina, int $porPagina): array
    {
        $sql = 'SELECT c.*, CONCAT(u.nombre, " ", u.ap_paterno) AS cliente, m.nombre_metodoPago,
                       (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items
                FROM compra c
                LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
                LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
                WHERE 1';
        $params = [];
        if ($fecha !== '') {
            $sql .= ' AND c.fecha_compra = ?';
            $params[] = $fecha;
        }
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY c.fecha_compra DESC, c.id_compra DESC LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    public static function totales(string $fecha = ''): array
    {
        return self::one(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto FROM compra' . ($fecha !== '' ? ' WHERE fecha_compra = ?' : ''),
            $fecha !== '' ? [$fecha] : []
        ) ?? ['compras' => 0, 'monto' => 0];
    }

    public static function totalDelDia(string $fecha): float
    {
        return (float)self::value('SELECT COALESCE(SUM(total_pagar),0) FROM compra WHERE fecha_compra = ?', [$fecha]);
    }

    public static function ultimosDias(int $dias = 7): array
    {
        $etiquetas = [];
        $montos = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $etiquetas[] = date('D', strtotime($fecha));
            $montos[] = self::totalDelDia($fecha);
        }
        return ['dias' => $etiquetas, 'montos' => $montos];
    }
}
