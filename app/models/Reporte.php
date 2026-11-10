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
