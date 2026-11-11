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

    public static function contarDelDiaSucursal(string $fecha, string $idSucursal): int
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM reserva WHERE fecha = ? AND id_sucursal = ? AND estado <> 'cancelada'",
            [$fecha, $idSucursal]
        );
    }

    public static function contarPendientesSucursal(string $idSucursal): int
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM reserva WHERE id_sucursal = ? AND estado = 'pendiente'",
            [$idSucursal]
        );
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

    /**
     * Comprueba si una cita nueva (con su duración) se cruza con otra cita
     * activa de la sucursal. Las citas canceladas no bloquean horarios.
     */
    public static function conflictoHorario(string $fecha, string $hora, string $idSucursal, int $duracion, ?int $excluir = null): bool
    {
        $inicio = strtotime($fecha . ' ' . $hora);
        if ($inicio === false) {
            return true;
        }
        $fin = $inicio + max(1, $duracion) * 60;
        $reservas = self::all(
            'SELECT r.id_reserva, r.hora,
                    COALESCE((
                        SELECT SUM(s.duracion_minutos)
                        FROM detalle_reserva dr
                        JOIN servicio s ON s.id_servicio = dr.id_servicio
                        WHERE dr.id_reserva = r.id_reserva
                    ), 30) AS duracion
             FROM reserva r
             WHERE r.fecha = ? AND r.id_sucursal = ? AND r.estado <> "cancelada"
               AND (? = 0 OR r.id_reserva <> ?)',
            [$fecha, $idSucursal, $excluir ?? 0, $excluir ?? 0]
        );

        foreach ($reservas as $r) {
            $inicioR = strtotime($fecha . ' ' . (string)$r['hora']);
            if ($inicioR === false) {
                continue;
            }
            $finR = $inicioR + max(1, (int)$r['duracion']) * 60;
            if ($inicio < $finR && $inicioR < $fin) {
                return true;
            }
        }
        return false;
    }

    /** Evita que la misma clienta tenga dos citas idénticas el mismo día y hora. */
    public static function citaDuplicadaCliente(string $idUsuario, string $fecha, string $hora, string $idSucursal, ?int $excluir = null): bool
    {
        return (int)self::value(
            'SELECT COUNT(*) FROM reserva
             WHERE id_usuario = ? AND fecha = ? AND hora = ? AND id_sucursal = ?
               AND estado <> "cancelada" AND (? = 0 OR id_reserva <> ?)',
            [$idUsuario, $fecha, self::horaConSegundos($hora), $idSucursal, $excluir ?? 0, $excluir ?? 0]
        ) > 0;
    }

    /**
     * Valida fecha, hora, horario de la sucursal y choque con otras citas.
     * Devuelve el motivo del rechazo o null si el espacio está disponible.
     */
    public static function motivoHorarioInvalido(
        string $fecha,
        string $hora,
        string $idSucursal,
        int $duracion,
        ?int $excluir = null
    ): ?string {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return 'Selecciona una fecha válida.';
        }
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora) !== 1) {
            return 'Selecciona una hora válida.';
        }
        $duracion = max(1, $duracion);
        $inicio = self::minutosDeHora($hora);
        if ($inicio === null) {
            return 'Selecciona una hora válida.';
        }
        $horario = Sucursal::horarioParaFecha($idSucursal, $fecha);
        if ($horario === null) {
            return 'La sucursal está cerrada ese día.';
        }
        $apertura = self::minutosDeHora((string)$horario['hora_apertura']);
        $cierre = self::minutosDeHora((string)$horario['hora_cierre']);
        if ($apertura === null || $cierre === null) {
            return 'La sucursal no tiene un horario configurado para ese día.';
        }
        if ($inicio < $apertura || $inicio + $duracion > $cierre) {
            $desde = substr((string)$horario['hora_apertura'], 0, 5);
            $hasta = substr((string)$horario['hora_cierre'], 0, 5);
            return 'La cita debe estar dentro del horario de atención (' . $desde . ' a ' . $hasta . ').';
        }
        if (self::conflictoHorario($fecha, $hora, $idSucursal, $duracion, $excluir)) {
            return 'Ese horario se cruza con otra cita de la sucursal. Elige otro.';
        }
        return null;
    }

    /**
     * Horas libres para un servicio dentro del horario de la sucursal.
     * Se consideran bloques de 30 minutos y la duración del servicio.
     */
    public static function horasDisponibles(
        string $fecha,
        string $idSucursal,
        int $duracion = 30,
        ?int $excluir = null
    ): array {
        $horario = Sucursal::horarioParaFecha($idSucursal, $fecha);
        if ($horario === null) {
            return [];
        }
        $apertura = self::minutosDeHora((string)$horario['hora_apertura']);
        $cierre = self::minutosDeHora((string)$horario['hora_cierre']);
        if ($apertura === null || $cierre === null) {
            return [];
        }
        $duracion = max(1, $duracion);
        $reservas = self::all(
            'SELECT r.id_reserva, r.hora,
                    COALESCE((
                        SELECT SUM(s.duracion_minutos)
                        FROM detalle_reserva dr
                        JOIN servicio s ON s.id_servicio = dr.id_servicio
                        WHERE dr.id_reserva = r.id_reserva
                    ), 30) AS duracion
             FROM reserva r
             WHERE r.fecha = ? AND r.id_sucursal = ? AND r.estado <> "cancelada"
               AND (? = 0 OR r.id_reserva <> ?)',
            [$fecha, $idSucursal, $excluir ?? 0, $excluir ?? 0]
        );
        $ocupados = [];
        foreach ($reservas as $r) {
            $inicioR = self::minutosDeHora((string)$r['hora']);
            if ($inicioR === null) {
                continue;
            }
            $ocupados[] = [$inicioR, $inicioR + max(1, (int)$r['duracion'])];
        }

        $ahora = strtotime('now');
        $horas = [];
        for ($min = $apertura; $min + $duracion <= $cierre; $min += 30) {
            $hora = str_pad((string)intdiv($min, 60), 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad((string)($min % 60), 2, '0', STR_PAD_LEFT);
            if (strtotime($fecha . ' ' . $hora) <= $ahora) {
                continue;
            }
            $choque = false;
            foreach ($ocupados as [$ini, $fin]) {
                if ($min < $fin && $ini < $min + $duracion) {
                    $choque = true;
                    break;
                }
            }
            if (!$choque) {
                $horas[] = $hora;
            }
        }
        return $horas;
    }

    private static function horaConSegundos(string $hora): string
    {
        $hora = trim($hora);
        if (preg_match('/^\d{2}:\d{2}$/', $hora) === 1) {
            return $hora . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora) === 1) {
            return $hora;
        }
        return $hora;
    }

    private static function minutosDeHora(string $hora): ?int
    {
        if (preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', trim($hora), $m) !== 1) {
            return null;
        }
        $h = (int)$m[1];
        $min = (int)$m[2];
        if ($h > 23 || $min > 59) {
            return null;
        }
        return $h * 60 + $min;
    }

    public static function porId(int $id): ?array
    {
        return self::one('SELECT * FROM reserva WHERE id_reserva = ?', [$id]);
    }

    public static function porIdDeSucursal(int $id, string $idSucursal): ?array
    {
        return self::one(
            'SELECT * FROM reserva WHERE id_reserva = ? AND id_sucursal = ?',
            [$id, $idSucursal]
        );
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

    public static function citasDelDiaSucursal(string $fecha, string $idSucursal): array
    {
        return self::all(
            "SELECT r.id_reserva, r.hora, r.estado, r.id_usuario,
                    CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente,
                    u.telefono,
                    COALESCE(GROUP_CONCAT(s.nombre SEPARATOR ', '), 'Sin servicio') AS servicio
             FROM reserva r
             LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE r.fecha = ? AND r.id_sucursal = ?
             GROUP BY r.id_reserva
             ORDER BY r.hora",
            [$fecha, $idSucursal]
        );
    }

    public static function contarProximasSucursal(string $idSucursal, string $desde): int
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM reserva
             WHERE id_sucursal = ? AND fecha >= ? AND estado <> 'cancelada'",
            [$idSucursal, $desde]
        );
    }

    public static function proximasDeSucursal(string $idSucursal, string $desde, int $limite = 6): array
    {
        return self::all(
            'SELECT r.*, CONCAT(u.nombre, " ", u.ap_paterno) AS cliente, u.telefono,
                    GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                    COALESCE(SUM(dr.subtotal), 0) AS total
             FROM reserva r
             LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
             WHERE r.id_sucursal = ? AND r.fecha >= ? AND r.estado <> "cancelada"
             GROUP BY r.id_reserva
             ORDER BY r.fecha, r.hora
             LIMIT ' . max(1, $limite),
            [$idSucursal, $desde]
        );
    }

    public static function deCliente(string $idUsuario): array
    {
        return self::all(
            'SELECT r.*, s.nombre_sucursal, m.nombre_metodoPago,
                    GROUP_CONCAT(sv.nombre SEPARATOR " · ") AS servicios,
                    COALESCE(SUM(dr.subtotal), 0) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
             WHERE r.id_usuario = ?
             GROUP BY r.id_reserva
             ORDER BY r.fecha DESC, r.hora DESC',
            [$idUsuario]
        );
    }

    /** Citas de una clienta vistas desde la sucursal de un empleado. */
    public static function deClienteDeSucursal(string $idUsuario, string $idSucursal): array
    {
        return self::all(
            'SELECT r.*, s.nombre_sucursal, m.nombre_metodoPago,
                    GROUP_CONCAT(sv.nombre SEPARATOR " · ") AS servicios,
                    COALESCE(SUM(dr.subtotal), 0) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
             WHERE r.id_usuario = ? AND r.id_sucursal = ?
             GROUP BY r.id_reserva
             ORDER BY r.fecha DESC, r.hora DESC',
            [$idUsuario, $idSucursal]
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

    public static function filtrar(string $fecha, string $estado, string $idSucursal, int $limite = 100, string $idCliente = ''): array
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
        if ($idCliente !== '') {
            $sql .= ' AND r.id_usuario = ?';
            $params[] = $idCliente;
        }
        $sql .= ' GROUP BY r.id_reserva ORDER BY r.fecha DESC, r.hora DESC LIMIT ' . $limite;
        return self::all($sql, $params);
    }

    public static function conCliente(int $idReserva): ?array
    {
        return self::one(
            'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, u.fecha_nacimiento, c.correo
             FROM reserva r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
             WHERE r.id_reserva = ?',
            [$idReserva]
        );
    }

    public static function crear(string $idUsuario, string $idSucursal, string $fecha, string $hora, array $servicio, string $notas = '', string $estado = 'pendiente'): int
    {
        $estado = in_array($estado, self::ESTADOS, true) ? $estado : 'pendiente';
        return (int)self::transaccion(function () use ($idUsuario, $idSucursal, $fecha, $hora, $servicio, $notas, $estado) {
            $idReserva = Ids::insertarConEntero('reserva', 'id_reserva', [
                'fecha'         => $fecha,
                'hora'          => $hora . ':00',
                'estado'        => $estado,
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

    /** Reprograma una cita y reemplaza su servicio (uso del personal). */
    public static function actualizar(int $idReserva, string $idSucursal, string $fecha, string $hora, array $servicio): void
    {
        self::transaccion(function () use ($idReserva, $idSucursal, $fecha, $hora, $servicio) {
            self::run(
                'UPDATE reserva SET fecha = ?, hora = ?, id_sucursal = ? WHERE id_reserva = ?',
                [$fecha, $hora . ':00', $idSucursal, $idReserva]
            );
            self::run('DELETE FROM detalle_reserva WHERE id_reserva = ?', [$idReserva]);
            Ids::insertarConEntero('detalle_reserva', 'id_detalle', [
                'cantidad'        => 1,
                'precio_unitario' => $servicio['precio'],
                'subtotal'        => $servicio['precio'],
                'id_servicio'     => $servicio['id_servicio'],
                'id_reserva'      => $idReserva,
            ]);
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
