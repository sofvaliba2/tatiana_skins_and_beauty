<?php
declare(strict_types=1);

namespace App\Models;

final class Compra extends Model
{
    /**
     * Registra una venta con productos y/o servicios.
     *
     * Los precios y el stock se leen de la base de datos dentro de la misma
     * transacción. El navegador nunca define el precio ni descuenta stock.
     *
     * Formatos aceptados por cada ítem:
     *   ['tipo' => 'producto', 'id' => 'PROD01', 'cantidad' => 2]
     *   ['tipo' => 'servicio', 'id' => 'SERV01', 'cantidad' => 1]
     * También se mantiene el formato anterior ['id' => ..., 'precio' => ..., 'cantidad' => ...].
     */
    public static function crear(string $idUsuario, ?string $idMetodo, array $items): array
    {
        if ($items === []) {
            throw new \DomainException('Agrega al menos un producto o servicio a la venta.');
        }
        if (count($items) > 50) {
            throw new \DomainException('La venta admite como máximo 50 líneas.');
        }

        return self::transaccion(function () use ($idUsuario, $idMetodo, $items) {
            $total = 0.0;
            $detalle = [];

            foreach ($items as $item) {
                $tipo = strtolower(trim((string)($item['tipo'] ?? '')));
                if ($tipo === '' && isset($item['id_servicio']) && !isset($item['id_producto'])) {
                    $tipo = 'servicio';
                }
                if ($tipo === '') {
                    $tipo = 'producto';
                }
                if (!in_array($tipo, ['producto', 'servicio'], true)) {
                    throw new \DomainException('El tipo de ítem de la venta no es válido.');
                }

                $id = trim((string)($item['id'] ?? $item[$tipo === 'producto' ? 'id_producto' : 'id_servicio'] ?? ''));
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($id === '') {
                    throw new \DomainException('Selecciona un producto o servicio en cada línea de la venta.');
                }
                if ($cantidad < 1 || $cantidad > 999) {
                    throw new \DomainException('Cada cantidad debe estar entre 1 y 999 unidades.');
                }

                if ($tipo === 'producto') {
                    $producto = self::one(
                        'SELECT id_producto, nombre_producto, precio, cantidad
                         FROM producto WHERE id_producto = ? FOR UPDATE',
                        [$id]
                    );
                    if ($producto === null) {
                        throw new \DomainException('El producto seleccionado ya no existe.');
                    }
                    if ((int)$producto['cantidad'] < $cantidad) {
                        throw new \DomainException(
                            'Stock insuficiente de ' . $producto['nombre_producto'] .
                            '. Disponible: ' . (int)$producto['cantidad'] . ' unidades.'
                        );
                    }
                    $precio = (float)$producto['precio'];
                    $subtotal = round($precio * $cantidad, 2);
                    $afectadas = self::run(
                        'UPDATE producto SET cantidad = cantidad - ? WHERE id_producto = ? AND cantidad >= ?',
                        [$cantidad, $id, $cantidad]
                    );
                    if ($afectadas !== 1) {
                        throw new \DomainException('No se pudo reservar el stock del producto. Intenta nuevamente.');
                    }
                    $detalle[] = [
                        'tipo'            => 'producto',
                        'id_producto'     => $id,
                        'id_servicio'     => null,
                        'nombre'          => (string)$producto['nombre_producto'],
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal'        => $subtotal,
                    ];
                } else {
                    $servicio = self::one(
                        'SELECT id_servicio, nombre, precio FROM servicio WHERE id_servicio = ?',
                        [$id]
                    );
                    if ($servicio === null) {
                        throw new \DomainException('El servicio seleccionado ya no existe.');
                    }
                    $precio = (float)$servicio['precio'];
                    $subtotal = round($precio * $cantidad, 2);
                    $detalle[] = [
                        'tipo'            => 'servicio',
                        'id_producto'     => null,
                        'id_servicio'     => $id,
                        'nombre'          => (string)$servicio['nombre'],
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal'        => $subtotal,
                    ];
                }
                $total = round($total + $subtotal, 2);
            }

            $nro = 'C-' . str_pad((string)((int)self::value('SELECT COALESCE(MAX(id_compra),0) FROM compra') + 1), 6, '0', STR_PAD_LEFT);
            $idCompra = Ids::insertarConEntero('compra', 'id_compra', [
                'fecha_compra'  => date('Y-m-d'),
                'nro_compra'    => $nro,
                'total_pagar'   => $total,
                'id_usuario'    => $idUsuario,
                'id_metodoPago' => $idMetodo,
            ]);
            foreach ($detalle as $linea) {
                Ids::insertarConEntero('detalle_compra', 'id_detalle', [
                    'cantidad'        => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'subtotal'        => $linea['subtotal'],
                    'id_compra'       => $idCompra,
                    'id_producto'     => $linea['id_producto'],
                    'id_servicio'     => $linea['id_servicio'],
                ]);
            }
            return ['id' => $idCompra, 'nro' => $nro, 'total' => $total, 'items' => $detalle];
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

    /** Ventas recientes para el panel del empleado. */
    public static function recientes(int $limite = 10): array
    {
        return self::all(
            'SELECT c.*, CONCAT(u.nombre, " ", u.ap_paterno) AS cliente, m.nombre_metodoPago,
                    (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items
             FROM compra c
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             ORDER BY c.fecha_compra DESC, c.id_compra DESC
             LIMIT ' . max(1, $limite)
        );
    }

    /** Ventas recientes de clientas vinculadas a una sucursal. */
    public static function recientesDeSucursal(string $idSucursal, int $limite = 10): array
    {
        return self::all(
            'SELECT c.*, CONCAT(u.nombre, " ", u.ap_paterno) AS cliente, m.nombre_metodoPago,
                    (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items
             FROM compra c
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             WHERE c.id_usuario IN (
                 SELECT u2.id_usuario FROM usuario u2
                 WHERE u2.id_rol = "ROL03"
                   AND (u2.id_sucursal = ? OR EXISTS (
                       SELECT 1 FROM reserva r
                       WHERE r.id_usuario = u2.id_usuario AND r.id_sucursal = ?
                   ))
             )
             ORDER BY c.fecha_compra DESC, c.id_compra DESC
             LIMIT ' . max(1, $limite),
            [$idSucursal, $idSucursal]
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
            'SELECT d.*, p.nombre_producto, p.imagen_url, p.id_categoria,
                    s.nombre AS nombre_servicio, s.duracion_minutos,
                    COALESCE(p.nombre_producto, s.nombre, "Ítem") AS nombre,
                    CASE WHEN d.id_servicio IS NOT NULL AND d.id_servicio <> "" THEN "servicio" ELSE "producto" END AS tipo
             FROM detalle_compra d
             LEFT JOIN producto p ON p.id_producto = d.id_producto
             LEFT JOIN servicio s ON s.id_servicio = d.id_servicio
             WHERE d.id_compra = ?
             ORDER BY d.id_detalle',
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
