<?php
declare(strict_types=1);

namespace App\Models;

final class Reserva extends Model
{
    public const ESTADOS = ['pendiente', 'confirmada', 'atendida', 'cancelada'];

    public static function contarPendientes(): int
    {
        return (int)self::value("SELECT COUNT(*) FROM reserva WHERE estado = 'pendiente'");
    }

    public static function contarPendientesDeUsuario(string $idUsuario): int
    {
        return (int)self::value("SELECT COUNT(*) FROM reserva WHERE id_usuario = ? AND estado = 'pendiente'", [$idUsuario]);
    }

    public static function contarDelDia(string $fecha): int
    {
        return (int)self::value("SELECT COUNT(*) FROM reserva WHERE fecha = ? AND estado <> 'cancelada'", [$fecha]);
    }

    public static function estaOcupada(string $fecha, string $hora, string $idSucursal): bool
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM reserva
             WHERE fecha = ? AND hora = ? AND id_sucursal = ? AND estado <> 'cancelada'",
            [$fecha, $hora, $idSucursal]
        ) > 0;
    }

    public static function horasOcupadas(string $fecha, string $idSucursal): array
    {
        return array_column(
            self::all("SELECT hora FROM reserva WHERE fecha = ? AND id_sucursal = ? AND estado <> 'cancelada'", [$fecha, $idSucursal]),
            'hora'
        );
    }

    public static function porId(int $id): ?array
    {
        return self::one('SELECT * FROM reserva WHERE id_reserva = ?', [$id]);
    }

    public static function items(int $idReserva): array
    {
        return self::all(
            'SELECT dr.*, s.nombre, s.duracion_minutos
             FROM detalle_reserva dr
             JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE dr.id_reserva = ?',
            [$idReserva]
        );
    }

    public static function deUsuario(string $idUsuario): array
    {
        return self::all(
            'SELECT r.*, s.nombre_sucursal, m.nombre_metodoPago,
                    (SELECT GROUP_CONCAT(sv.nombre SEPARATOR " · ")
                     FROM detalle_reserva dr JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                     WHERE dr.id_reserva = r.id_reserva) AS servicios,
                    (SELECT COALESCE(SUM(dr2.subtotal),0) FROM detalle_reserva dr2 WHERE dr2.id_reserva = r.id_reserva) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.id_usuario = ?
             ORDER BY r.fecha DESC, r.hora DESC',
            [$idUsuario]
        );
    }

    public static function proximasDeUsuario(string $idUsuario, string $desde, int $limite = 3): array
    {
        return self::all(
            'SELECT r.*, s.nombre_sucursal,
                    GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                    COALESCE(SUM(dr.subtotal), 0) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
             WHERE r.id_usuario = ? AND r.fecha >= ? AND r.estado <> "cancelada"
             GROUP BY r.id_reserva
             ORDER BY r.fecha, r.hora
             LIMIT ' . $limite,
            [$idUsuario, $desde]
        );
    }

    public static function citasDelDia(string $fecha): array
    {
        return self::all(
            "SELECT r.id_reserva, r.hora, r.estado,
                    CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente,
                    COALESCE(GROUP_CONCAT(s.nombre SEPARATOR ', '), 'Sin servicio') AS servicio
             FROM reserva r
             LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE r.fecha = ?
             GROUP BY r.id_reserva
             ORDER BY r.hora",
            [$fecha]
        );
    }

    public static function resumenDelDia(string $fecha): array
    {
        return self::all(
            "SELECT r.id_reserva, r.hora, r.estado, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente
             FROM reserva r JOIN usuario u ON u.id_usuario = r.id_usuario
             WHERE r.fecha = ? AND r.estado <> 'cancelada'
             ORDER BY r.hora",
            [$fecha]
        );
    }

    public static function pendientesDetalle(): array
    {
        return self::all(
            "SELECT r.id_reserva, r.fecha, r.hora, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente, u.telefono,
                    s.nombre_sucursal, m.nombre_metodoPago
             FROM reserva r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.estado = 'pendiente'
             ORDER BY r.fecha, r.hora"
        );
    }

    public static function filtrar(string $fecha, string $estado, string $idSucursal, int $limite = 100): array
    {
        $sql = 'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, s.nombre_sucursal, m.nombre_metodoPago,
                       GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                       COALESCE(SUM(dr.subtotal), 0) AS total
                FROM reserva r
                LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
                LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
                LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
                LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
                LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                WHERE 1';
        $params = [];
        if ($fecha !== '') {
            $sql .= ' AND r.fecha = ?';
            $params[] = $fecha;
        }
        if ($estado !== '') {
            $sql .= ' AND r.estado = ?';
            $params[] = $estado;
        }
        if ($idSucursal !== '') {
            $sql .= ' AND r.id_sucursal = ?';
            $params[] = $idSucursal;
        }
        $sql .= ' GROUP BY r.id_reserva ORDER BY r.fecha DESC, r.hora DESC LIMIT ' . $limite;
        return self::all($sql, $params);
    }

    public static function conCliente(int $idReserva): ?array
    {
        return self::one(
            'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, u.fecha_nacimiento
             FROM reserva r JOIN usuario u ON u.id_usuario = r.id_usuario
             WHERE r.id_reserva = ?',
            [$idReserva]
        );
    }

    public static function crear(string $idUsuario, string $idSucursal, string $fecha, string $hora, array $servicio, string $notas = ''): int
    {
        return (int)self::transaccion(function () use ($idUsuario, $idSucursal, $fecha, $hora, $servicio, $notas) {
            $idReserva = Ids::insertarConEntero('reserva', 'id_reserva', [
                'fecha'         => $fecha,
                'hora'          => $hora . ':00',
                'estado'        => 'pendiente',
                'id_sucursal'   => $idSucursal,
                'id_metodoPago' => null,
                'id_usuario'    => $idUsuario,
            ]);
            Ids::insertarConEntero('detalle_reserva', 'id_detalle', [
                'cantidad'        => 1,
                'precio_unitario' => $servicio['precio'],
                'subtotal'        => $servicio['precio'],
                'id_servicio'     => $servicio['id_servicio'],
                'id_reserva'      => $idReserva,
            ]);
            if ($notas !== '') {
                Historial::registrarNota($idReserva, 'Notas de reserva: ' . $notas);
            }
            return $idReserva;
        });
    }

    public static function asignarMetodoPago(int $idReserva, ?string $idMetodo): void
    {
        self::run('UPDATE reserva SET id_metodoPago = ? WHERE id_reserva = ?', [$idMetodo, $idReserva]);
    }

    public static function cambiarEstado(int $idReserva, string $estado): bool
    {
        if (!in_array($estado, self::ESTADOS, true) || self::porId($idReserva) === null) {
            return false;
        }
        self::run('UPDATE reserva SET estado = ? WHERE id_reserva = ?', [$estado, $idReserva]);
        return true;
    }

    public static function cancelarDeUsuario(int $idReserva, string $idUsuario): bool
    {
        $res = self::one('SELECT id_usuario, estado FROM reserva WHERE id_reserva = ?', [$idReserva]);
        if ($res === null || $res['id_usuario'] !== $idUsuario || !in_array($res['estado'], ['pendiente', 'confirmada'], true)) {
            return false;
        }
        self::run("UPDATE reserva SET estado = 'cancelada' WHERE id_reserva = ?", [$idReserva]);
        return true;
    }
}
