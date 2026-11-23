<?php
declare(strict_types=1);

namespace App\Models;

final class Reporte extends Model
{
    /**
     * Filtro de sucursal reutilizable. El valor 'NULL' representa las ventas
     * o reservas que no tienen sucursal asignada en la base de datos.
     */
    private static function filtroSucursal(string $alias, ?string $sucursal, array &$params): string
    {
        if ($sucursal === null || $sucursal === '') {
            return '';
        }
        if ($sucursal === 'NULL') {
            return ' AND ' . $alias . '.id_sucursal IS NULL';
        }
        $params[] = $sucursal;
        return ' AND ' . $alias . '.id_sucursal = ?';
    }

    public static function resumenCompras(string $desde, string $hasta, ?string $sucursal = null): array
    {
        $params = [$desde, $hasta];
        $sql = 'SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto
                FROM compra WHERE fecha_compra BETWEEN ? AND ?'
            . self::filtroSucursal('compra', $sucursal, $params);
        return self::one($sql, $params) ?? ['compras' => 0, 'monto' => 0];
    }

    public static function resumenReservas(string $desde, string $hasta, ?string $sucursal = null): array
    {
        $params = [$desde, $hasta];
        $sql = "SELECT COUNT(*) AS total,
                        SUM(estado = 'atendida') AS atendidas,
                        SUM(estado = 'cancelada') AS canceladas
                 FROM reserva WHERE fecha BETWEEN ? AND ?"
            . self::filtroSucursal('reserva', $sucursal, $params);
        return self::one($sql, $params) ?? ['total' => 0, 'atendidas' => 0, 'canceladas' => 0];
    }

    public static function porProducto(string $desde, string $hasta, ?int $limite = 10, ?string $sucursal = null): array
    {
        $params = [$desde, $hasta];
        $sql = 'SELECT p.nombre_producto, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS total
                FROM detalle_compra d
                JOIN producto p ON p.id_producto = d.id_producto
                JOIN compra c ON c.id_compra = d.id_compra
                WHERE c.fecha_compra BETWEEN ? AND ?'
            . self::filtroSucursal('c', $sucursal, $params)
            . ' GROUP BY d.id_producto ORDER BY total DESC'
            . ($limite !== null ? ' LIMIT ' . $limite : '');
        return self::all($sql, $params);
    }

    public static function porServicio(string $desde, string $hasta, ?int $limite = 10, ?string $sucursal = null): array
    {
        $params = [$desde, $hasta];
        $sql = 'SELECT s.nombre, COUNT(*) AS citas, SUM(dr.subtotal) AS total
                FROM detalle_reserva dr
                JOIN servicio s ON s.id_servicio = dr.id_servicio
                JOIN reserva r ON r.id_reserva = dr.id_reserva
                WHERE r.fecha BETWEEN ? AND ? AND r.estado <> "cancelada"'
            . self::filtroSucursal('r', $sucursal, $params)
            . ' GROUP BY dr.id_servicio ORDER BY citas DESC'
            . ($limite !== null ? ' LIMIT ' . $limite : '');
        return self::all($sql, $params);
    }

    public static function topClientes(string $desde, string $hasta, int $limite = 10, ?string $sucursal = null): array
    {
        $paramsGasto = [$desde, $hasta];
        $filtroGasto = self::filtroSucursal('c2', $sucursal, $paramsGasto);
        $paramsCompra = [$desde, $hasta];
        $filtroCompra = self::filtroSucursal('c', $sucursal, $paramsCompra);
        $paramsReserva = [$desde, $hasta];
        $filtroReserva = self::filtroSucursal('r', $sucursal, $paramsReserva);

        $sql = 'SELECT CONCAT(u.nombre, " ", u.ap_paterno) AS cliente,
                        COUNT(DISTINCT c.id_compra) AS compras,
                        COUNT(DISTINCT r.id_reserva) AS reservas,
                        COALESCE((SELECT SUM(c2.total_pagar) FROM compra c2
                                  WHERE c2.id_usuario = u.id_usuario AND c2.fecha_compra BETWEEN ? AND ?'
                                  . $filtroGasto . '), 0) AS gastado
                 FROM usuario u
                 LEFT JOIN compra c ON c.id_usuario = u.id_usuario AND c.fecha_compra BETWEEN ? AND ?'
                 . $filtroCompra . '
                 LEFT JOIN reserva r ON r.id_usuario = u.id_usuario AND r.fecha BETWEEN ? AND ?'
                 . $filtroReserva . '
                 WHERE u.id_rol = "ROL03"
                 GROUP BY u.id_usuario
                 HAVING compras > 0 OR reservas > 0
                 ORDER BY gastado DESC, reservas DESC
                 LIMIT ' . $limite;
        return self::all($sql, array_merge($paramsGasto, $paramsCompra, $paramsReserva));
    }

    /**
     * Serie temporal para los gráficos: ingresos/compras y reservas por día.
     * Si el rango supera 62 días se agrupa por mes para que el gráfico sea legible.
     *
     * @return array{etiquetas:array<int,string>,compras:array<int,int>,montos:array<int,float>,reservas:array<int,int>,por_mes:bool}
     */
    public static function serie(string $desde, string $hasta, ?string $sucursal = null): array
    {
        $inicio = new \DateTimeImmutable($desde);
        $fin = new \DateTimeImmutable($hasta);
        $dias = (int)$inicio->diff($fin)->days + 1;
        $porMes = $dias > 62;
        $formato = $porMes ? '%Y-%m' : '%Y-%m-%d';

        $params = [$desde, $hasta];
        $sqlCompras = "SELECT DATE_FORMAT(fecha_compra, '" . $formato . "') AS periodo,
                              COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto
                       FROM compra WHERE fecha_compra BETWEEN ? AND ?"
            . self::filtroSucursal('compra', $sucursal, $params)
            . ' GROUP BY periodo ORDER BY periodo';
        $compras = self::all($sqlCompras, $params);

        $paramsRes = [$desde, $hasta];
        $sqlReservas = "SELECT DATE_FORMAT(fecha, '" . $formato . "') AS periodo, COUNT(*) AS reservas
                        FROM reserva WHERE fecha BETWEEN ? AND ? AND estado <> 'cancelada'"
            . self::filtroSucursal('reserva', $sucursal, $paramsRes)
            . ' GROUP BY periodo ORDER BY periodo';
        $reservas = self::all($sqlReservas, $paramsRes);

        $idxCompras = [];
        foreach ($compras as $c) {
            $idxCompras[(string)$c['periodo']] = $c;
        }
        $idxReservas = [];
        foreach ($reservas as $r) {
            $idxReservas[(string)$r['periodo']] = (int)$r['reservas'];
        }

        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $etiquetas = $cantidades = $montos = $citas = [];
        $cursor = $porMes ? $inicio->modify('first day of this month') : $inicio;
        while ($cursor <= $fin) {
            $clave = $cursor->format($porMes ? 'Y-m' : 'Y-m-d');
            $etiquetas[] = $porMes
                ? $meses[(int)$cursor->format('n') - 1] . ' ' . $cursor->format('y')
                : $cursor->format('d/m');
            $cantidades[] = (int)($idxCompras[$clave]['compras'] ?? 0);
            $montos[] = (float)($idxCompras[$clave]['monto'] ?? 0);
            $citas[] = (int)($idxReservas[$clave] ?? 0);
            $cursor = $cursor->modify($porMes ? 'first day of next month' : '+1 day');
        }

        return [
            'etiquetas' => $etiquetas,
            'compras'   => $cantidades,
            'montos'    => $montos,
            'reservas'  => $citas,
            'por_mes'   => $porMes,
        ];
    }

    /** Distribución de las ventas de productos por método de pago. */
    public static function porMetodoPago(string $desde, string $hasta, ?string $sucursal = null): array
    {
        $params = [$desde, $hasta];
        $sql = "SELECT COALESCE(m.nombre_metodoPago, 'Sin método') AS nombre,
                        COUNT(*) AS compras, COALESCE(SUM(c.total_pagar),0) AS total
                 FROM compra c
                 LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
                 WHERE c.fecha_compra BETWEEN ? AND ?"
            . self::filtroSucursal('c', $sucursal, $params)
            . ' GROUP BY c.id_metodoPago, m.nombre_metodoPago ORDER BY total DESC';
        return self::all($sql, $params);
    }

    /**
     * Resumen de cada sucursal con el mismo formato, más los registros sin sucursal.
     * Si se indica una sucursal, solo devuelve esa fila (o la de "Sin sucursal").
     */
    public static function porSucursal(string $desde, string $hasta, ?string $sucursal = null): array
    {
        $columnas = static function (string $comp, string $res): string {
            return '(SELECT COUNT(*) FROM compra c WHERE c.fecha_compra BETWEEN ? AND ? AND ' . $comp . ') AS compras,
                    (SELECT COALESCE(SUM(c.total_pagar),0) FROM compra c WHERE c.fecha_compra BETWEEN ? AND ? AND ' . $comp . ') AS monto,
                    (SELECT COUNT(*) FROM reserva r WHERE r.fecha BETWEEN ? AND ? AND ' . $res . ') AS reservas,
                    (SELECT COUNT(*) FROM reserva r WHERE r.fecha BETWEEN ? AND ? AND ' . $res . " AND r.estado = 'atendida') AS atendidas,
                    (SELECT COUNT(*) FROM reserva r WHERE r.fecha BETWEEN ? AND ? AND " . $res . " AND r.estado = 'cancelada') AS canceladas";
        };
        $params = [];
        for ($i = 0; $i < 5; $i++) {
            $params[] = $desde;
            $params[] = $hasta;
        }

        $sqlSucursales = $columnas('c.id_sucursal = s.id_sucursal', 'r.id_sucursal = s.id_sucursal');
        $filas = self::all(
            'SELECT s.id_sucursal, s.nombre_sucursal, ' . $sqlSucursales . '
             FROM sucursal s ORDER BY s.nombre_sucursal',
            $params
        );

        $sqlSin = $columnas('c.id_sucursal IS NULL', 'r.id_sucursal IS NULL');
        $sin = self::one('SELECT ' . $sqlSin, $params) ?? [];
        if ((int)($sin['compras'] ?? 0) > 0 || (int)($sin['reservas'] ?? 0) > 0) {
            $filas[] = [
                'id_sucursal'     => 'NULL',
                'nombre_sucursal' => 'Sin sucursal asignada',
                'compras'         => (int)($sin['compras'] ?? 0),
                'monto'           => (float)($sin['monto'] ?? 0),
                'reservas'        => (int)($sin['reservas'] ?? 0),
                'atendidas'       => (int)($sin['atendidas'] ?? 0),
                'canceladas'      => (int)($sin['canceladas'] ?? 0),
            ];
        }

        if ($sucursal !== null && $sucursal !== '') {
            $filas = array_values(array_filter(
                $filas,
                static fn (array $f): bool => (string)$f['id_sucursal'] === $sucursal
            ));
        }
        return $filas;
    }

    public static function comprasDetalle(string $desde, string $hasta, ?string $sucursal = null): array
    {
        $params = [$desde, $hasta];
        $sql = 'SELECT c.nro_compra, c.fecha_compra, CONCAT(u.nombre," ",u.ap_paterno) AS cliente,
                        m.nombre_metodoPago, c.total_pagar, COALESCE(s.nombre_sucursal, "Sin sucursal") AS sucursal
                 FROM compra c
                 LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
                 LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
                 LEFT JOIN sucursal s ON s.id_sucursal = c.id_sucursal
                 WHERE c.fecha_compra BETWEEN ? AND ?'
            . self::filtroSucursal('c', $sucursal, $params)
            . ' ORDER BY c.fecha_compra';
        return self::all($sql, $params);
    }
}
