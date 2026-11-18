<?php
declare(strict_types=1);

namespace App\Models;

final class Reporte extends Model
{
    public static function resumenCompras(string $desde, string $hasta): array
    {
        return self::one(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto FROM compra WHERE fecha_compra BETWEEN ? AND ?',
            [$desde, $hasta]
        ) ?? ['compras' => 0, 'monto' => 0];
    }

    public static function resumenReservas(string $desde, string $hasta): array
    {
        return self::one(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'atendida') AS atendidas,
                    SUM(estado = 'cancelada') AS canceladas
             FROM reserva WHERE fecha BETWEEN ? AND ?",
            [$desde, $hasta]
        ) ?? ['total' => 0, 'atendidas' => 0, 'canceladas' => 0];
    }

    public static function porProducto(string $desde, string $hasta, ?int $limite = 10): array
    {
        return self::all(
            'SELECT p.nombre_producto, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS total
             FROM detalle_compra d
             JOIN producto p ON p.id_producto = d.id_producto
             JOIN compra c ON c.id_compra = d.id_compra
             WHERE c.fecha_compra BETWEEN ? AND ?
             GROUP BY d.id_producto
             ORDER BY total DESC' . ($limite !== null ? ' LIMIT ' . $limite : ''),
            [$desde, $hasta]
        );
    }

    public static function porServicio(string $desde, string $hasta, ?int $limite = 10): array
    {
        return self::all(
            'SELECT s.nombre, COUNT(*) AS citas, SUM(dr.subtotal) AS total
             FROM detalle_reserva dr
             JOIN servicio s ON s.id_servicio = dr.id_servicio
             JOIN reserva r ON r.id_reserva = dr.id_reserva
             WHERE r.fecha BETWEEN ? AND ? AND r.estado <> "cancelada"
             GROUP BY dr.id_servicio
             ORDER BY citas DESC' . ($limite !== null ? ' LIMIT ' . $limite : ''),
            [$desde, $hasta]
        );
    }

    public static function topClientes(string $desde, string $hasta, int $limite = 10): array
    {
        return self::all(
            'SELECT CONCAT(u.nombre, " ", u.ap_paterno) AS cliente,
                    COUNT(DISTINCT c.id_compra) AS compras,
                    COUNT(DISTINCT r.id_reserva) AS reservas,
                    COALESCE((SELECT SUM(total_pagar) FROM compra c2 WHERE c2.id_usuario = u.id_usuario), 0) AS gastado
             FROM usuario u
             LEFT JOIN compra c ON c.id_usuario = u.id_usuario AND c.fecha_compra BETWEEN ? AND ?
             LEFT JOIN reserva r ON r.id_usuario = u.id_usuario AND r.fecha BETWEEN ? AND ?
             WHERE u.id_rol = "ROL03"
             GROUP BY u.id_usuario
             HAVING compras > 0 OR reservas > 0
             ORDER BY gastado DESC, reservas DESC
             LIMIT ' . $limite,
            [$desde, $hasta, $desde, $hasta]
        );
    }

    /**
     * Serie temporal para los gráficos: ingresos/compras y reservas por día.
     * Si el rango supera 62 días se agrupa por mes para que el gráfico sea legible.
     *
     * @return array{etiquetas:array<int,string>,compras:array<int,int>,montos:array<int,float>,reservas:array<int,int>,por_mes:bool}
     */
    public static function serie(string $desde, string $hasta): array
    {
        $inicio = new \DateTimeImmutable($desde);
        $fin = new \DateTimeImmutable($hasta);
        $dias = (int)$inicio->diff($fin)->days + 1;
        $porMes = $dias > 62;
        $formato = $porMes ? '%Y-%m' : '%Y-%m-%d';

        $compras = self::all(
            "SELECT DATE_FORMAT(fecha_compra, '" . $formato . "') AS periodo,
                    COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto
             FROM compra WHERE fecha_compra BETWEEN ? AND ?
             GROUP BY periodo ORDER BY periodo",
            [$desde, $hasta]
        );
        $reservas = self::all(
            "SELECT DATE_FORMAT(fecha, '" . $formato . "') AS periodo, COUNT(*) AS reservas
             FROM reserva WHERE fecha BETWEEN ? AND ? AND estado <> 'cancelada'
             GROUP BY periodo ORDER BY periodo",
            [$desde, $hasta]
        );

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
    public static function porMetodoPago(string $desde, string $hasta): array
    {
        return self::all(
            "SELECT COALESCE(m.nombre_metodoPago, 'Sin método') AS nombre,
                    COUNT(*) AS compras, COALESCE(SUM(c.total_pagar),0) AS total
             FROM compra c
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             WHERE c.fecha_compra BETWEEN ? AND ?
             GROUP BY c.id_metodoPago, m.nombre_metodoPago
             ORDER BY total DESC",
            [$desde, $hasta]
        );
    }

    public static function comprasDetalle(string $desde, string $hasta): array
    {
        return self::all(
            'SELECT c.nro_compra, c.fecha_compra, CONCAT(u.nombre," ",u.ap_paterno) AS cliente, m.nombre_metodoPago, c.total_pagar
             FROM compra c
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             WHERE c.fecha_compra BETWEEN ? AND ? ORDER BY c.fecha_compra',
            [$desde, $hasta]
        );
    }
}
