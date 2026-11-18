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
    public static function crear(
        string $idUsuario,
        ?string $idMetodo,
        array $items,
        ?string $idSucursal = null,
        string $estadoPago = 'pagado',
        ?array $comprobante = null
    ): array {
        if ($items === []) {
            throw new \DomainException('Agrega al menos un producto o servicio a la venta.');
        }
        if (count($items) > 50) {
            throw new \DomainException('La venta admite como máximo 50 líneas.');
        }

        $estadosValidos = ['pagado', 'por_verificar', 'pendiente_efectivo', 'rechazado', 'sin_pago'];
        if (!in_array($estadoPago, $estadosValidos, true)) {
            $estadoPago = 'pagado';
        }

        return self::transaccion(function () use ($idUsuario, $idMetodo, $items, $idSucursal, $estadoPago, $comprobante) {
            $detalle = [];
            $total = 0.0;
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

            $datosCompra = [
                'fecha_compra'  => date('Y-m-d'),
                'nro_compra'    => $nro,
                'total_pagar'   => $total,
                'id_usuario'    => $idUsuario,
                'id_metodoPago' => $idMetodo,
                'estado_pago'   => $estadoPago,
            ];
            if ($idSucursal !== null && $idSucursal !== '') {
                $datosCompra['id_sucursal'] = $idSucursal;
            }

            $codigo = '';
            if (is_array($comprobante) && trim((string)($comprobante['ruta'] ?? '')) !== '') {
                $codigo = 'CMP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
                $datosCompra['estado_pago']        = 'por_verificar';
                $datosCompra['codigo_pago']        = $codigo;
                $datosCompra['comprobante']        = trim((string)($comprobante['ruta'] ?? ''));
                $datosCompra['comprobante_nombre'] = trim((string)($comprobante['nombre'] ?? ''));
                $datosCompra['comprobante_tipo']   = trim((string)($comprobante['tipo'] ?? ''));
                $datosCompra['fecha_pago']         = date('Y-m-d H:i:s');
                if (is_string($comprobante['datos'] ?? null) && $comprobante['datos'] !== '') {
                    $datosCompra['comprobante_datos'] = $comprobante['datos'];
                }
            }

            $idCompra = Ids::insertarConEntero('compra', 'id_compra', $datosCompra);
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
            return [
                'id'          => $idCompra,
                'nro'         => $nro,
                'total'       => $total,
                'items'       => $detalle,
                'codigo'      => $codigo,
                'estado_pago' => (string)$datosCompra['estado_pago'],
            ];
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
             WHERE c.id_sucursal = ?
                OR c.id_usuario IN (
                 SELECT u2.id_usuario FROM usuario u2
                 WHERE u2.id_rol = "ROL03"
                   AND (u2.id_sucursal = ? OR EXISTS (
                       SELECT 1 FROM reserva r
                       WHERE r.id_usuario = u2.id_usuario AND r.id_sucursal = ?
                   ))
             )
             ORDER BY c.fecha_compra DESC, c.id_compra DESC
             LIMIT ' . max(1, $limite),
            [$idSucursal, $idSucursal, $idSucursal]
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

    /** Comprobante de pago de una compra (binario en MySQL o ruta en disco). */
    public static function comprobantePago(int $idCompra): ?array
    {
        $compra = self::one(
            'SELECT comprobante, comprobante_nombre, comprobante_tipo, comprobante_datos
             FROM compra WHERE id_compra = ?',
            [$idCompra]
        );
        if ($compra === null) {
            return null;
        }
        $ruta = trim((string)($compra['comprobante'] ?? ''));
        $datos = $compra['comprobante_datos'] ?? null;
        if ($ruta === '' && !(is_string($datos) && $datos !== '')) {
            return null;
        }
        return [
            'ruta'   => $ruta,
            'nombre' => trim((string)($compra['comprobante_nombre'] ?? '')),
            'tipo'   => trim((string)($compra['comprobante_tipo'] ?? '')),
            'datos'  => $datos,
        ];
    }

    /** El personal confirma el pago de una compra con comprobante pendiente. */
    public static function confirmarPago(int $idCompra, string $verificadoPor = ''): bool
    {
        $compra = self::one('SELECT id_compra, estado_pago FROM compra WHERE id_compra = ?', [$idCompra]);
        if ($compra === null || (string)($compra['estado_pago'] ?? '') !== 'por_verificar') {
            return false;
        }
        $afectadas = self::run(
            "UPDATE compra
             SET estado_pago = 'pagado', fecha_verificacion = ?, verificado_por = ?, observacion = NULL
             WHERE id_compra = ? AND estado_pago = 'por_verificar'",
            [date('Y-m-d H:i:s'), $verificadoPor, $idCompra]
        );
        return $afectadas === 1;
    }

    /**
     * Rechaza el comprobante y devuelve las unidades al inventario, porque la
     * compra no se completó. Solo aplica a compras en revisión.
     */
    public static function rechazarPago(int $idCompra, string $motivo, string $verificadoPor = ''): bool
    {
        return (bool)self::transaccion(function () use ($idCompra, $motivo, $verificadoPor) {
            $compra = self::one(
                'SELECT id_compra, estado_pago FROM compra WHERE id_compra = ? FOR UPDATE',
                [$idCompra]
            );
            if ($compra === null || (string)($compra['estado_pago'] ?? '') !== 'por_verificar') {
                return false;
            }
            $productos = self::all(
                'SELECT id_producto, cantidad FROM detalle_compra
                 WHERE id_compra = ? AND id_producto IS NOT NULL',
                [$idCompra]
            );
            foreach ($productos as $p) {
                self::run(
                    'UPDATE producto SET cantidad = cantidad + ? WHERE id_producto = ?',
                    [(int)$p['cantidad'], (string)$p['id_producto']]
                );
            }
            self::run(
                "UPDATE compra
                 SET estado_pago = 'rechazado', observacion = ?, fecha_verificacion = ?, verificado_por = ?
                 WHERE id_compra = ? AND estado_pago = 'por_verificar'",
                [mb_substr(trim($motivo), 0, 255), date('Y-m-d H:i:s'), $verificadoPor, $idCompra]
            );
            return true;
        });
    }

    /** Compras del sitio con comprobante pendiente de revisión. */
    public static function pendientesPorVerificar(int $limite = 20): array
    {
        return self::all(
            "SELECT c.*, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente,
                    m.nombre_metodoPago
             FROM compra c
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             WHERE c.estado_pago = 'por_verificar'
             ORDER BY c.fecha_pago ASC, c.id_compra ASC
             LIMIT " . max(1, $limite)
        );
    }

    public static function contarPagosPorVerificar(): int
    {
        return (int)self::value("SELECT COUNT(*) FROM compra WHERE estado_pago = 'por_verificar'");
    }

    /** Compras con comprobante pendiente asignadas a una sucursal concreta. */
    public static function contarPagosPorVerificarSucursal(string $idSucursal): int
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM compra WHERE estado_pago = 'por_verificar' AND id_sucursal = ?",
            [$idSucursal]
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

    /**
     * Ventas con filtros combinables de día exacto, mes y año sobre la fecha
     * real guardada en MySQL.
     */
    public static function ventas(string $fecha, int $pagina, int $porPagina, string $mes = '', string $anio = ''): array
    {
        [$where, $params] = self::filtroFecha($fecha, $mes, $anio);
        $sql = 'SELECT c.*, CONCAT(u.nombre, " ", u.ap_paterno) AS cliente, m.nombre_metodoPago,
                       (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items,
                       (SELECT p.imagen_url FROM detalle_compra d2
                          JOIN producto p ON p.id_producto = d2.id_producto
                         WHERE d2.id_compra = c.id_compra AND p.imagen_url IS NOT NULL AND p.imagen_url <> ""
                         ORDER BY d2.id_detalle LIMIT 1) AS imagen_url
                FROM compra c
                LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
                LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
                WHERE 1' . $where;
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY c.fecha_compra DESC, c.id_compra DESC LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    public static function totales(string $fecha = '', string $mes = '', string $anio = ''): array
    {
        [$where, $params] = self::filtroFecha($fecha, $mes, $anio);
        return self::one(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto FROM compra WHERE 1' . $where,
            $params
        ) ?? ['compras' => 0, 'monto' => 0];
    }

    /** Años con ventas registradas (para el filtro de año). */
    public static function anios(): array
    {
        return array_map('intval', array_column(
            self::all('SELECT DISTINCT YEAR(fecha_compra) AS anio FROM compra WHERE fecha_compra IS NOT NULL ORDER BY anio DESC'),
            'anio'
        ));
    }

    /** Construye el WHERE de fecha (día/mes/año) y sus parámetros. */
    private static function filtroFecha(string $fecha, string $mes, string $anio): array
    {
        $where = '';
        $params = [];
        if ($fecha !== '') {
            $where .= ' AND fecha_compra = ?';
            $params[] = $fecha;
        }
        if ($anio !== '') {
            $where .= ' AND YEAR(fecha_compra) = ?';
            $params[] = (int)$anio;
        }
        if ($mes !== '') {
            $where .= ' AND MONTH(fecha_compra) = ?';
            $params[] = (int)$mes;
        }
        return [$where, $params];
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
