<?php
declare(strict_types=1);

namespace App\Models;

final class Historial extends Model
{
    public static function ultimoDeUsuario(string $idUsuario): ?array
    {
        return self::one(
            'SELECT h.* FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             WHERE r.id_usuario = ? AND h.objetivo_piel IS NOT NULL
             ORDER BY h.fecha_registro DESC LIMIT 1',
            [$idUsuario]
        );
    }

    public static function deUsuario(string $idUsuario): array
    {
        return self::all(
            'SELECT h.*, r.fecha, r.hora, r.estado AS estado_reserva, r.id_sucursal,
                    s.nombre_sucursal,
                    (SELECT GROUP_CONCAT(sv.nombre SEPARATOR ", ")
                     FROM detalle_reserva dr JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                     WHERE dr.id_reserva = r.id_reserva) AS servicios
             FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             WHERE r.id_usuario = ?
             ORDER BY h.fecha_registro DESC, h.id_historial DESC',
            [$idUsuario]
        );
    }

    /** Historial de una clienta visto desde la sucursal de un empleado. */
    public static function deUsuarioDeSucursal(string $idUsuario, string $idSucursal): array
    {
        return self::all(
            'SELECT h.*, r.fecha, r.hora, r.estado AS estado_reserva, r.id_sucursal, r.id_usuario,
                    s.nombre_sucursal,
                    (SELECT GROUP_CONCAT(sv.nombre SEPARATOR ", ")
                     FROM detalle_reserva dr JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                     WHERE dr.id_reserva = r.id_reserva) AS servicios
             FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             WHERE r.id_usuario = ? AND r.id_sucursal = ?
             ORDER BY h.fecha_registro DESC, h.id_historial DESC',
            [$idUsuario, $idSucursal]
        );
    }

    public static function porReserva(int $idReserva): ?array
    {
        return self::one(
            'SELECT * FROM historial_cliente WHERE id_reserva = ? ORDER BY id_historial DESC LIMIT 1',
            [$idReserva]
        );
    }

    public static function contarVisitas(string $idUsuario): int
    {
        return (int)self::value(
            'SELECT COUNT(*) FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             WHERE r.id_usuario = ?',
            [$idUsuario]
        );
    }

    public static function registrarNota(int $idReserva, string $observaciones): int
    {
        return Ids::insertarConEntero('historial_cliente', 'id_historial', [
            'alergias'                => null,
            'objetivo_piel'           => null,
            'abierto_recomendaciones' => 0,
            'rutina_actual'           => null,
            'observaciones_sesion'    => $observaciones,
            'fecha_registro'          => date('Y-m-d H:i:s'),
            'id_reserva'              => $idReserva,
        ]);
    }

    public static function guardarInicial(int $idReserva, array $datos): void
    {
        $existente = self::one('SELECT id_historial FROM historial_cliente WHERE id_reserva = ?', [$idReserva]);
        if ($existente) {
            self::run(
                'UPDATE historial_cliente SET alergias = ?, objetivo_piel = ?, abierto_recomendaciones = ?, rutina_actual = ?, observaciones_sesion = ?, fecha_registro = ? WHERE id_reserva = ?',
                [
                    $datos['alergias'], $datos['objetivo_piel'], $datos['abierto_recomendaciones'],
                    $datos['rutina_actual'], $datos['observaciones_sesion'], date('Y-m-d H:i:s'), $idReserva,
                ]
            );
            return;
        }
        Ids::insertarConEntero('historial_cliente', 'id_historial', $datos + [
            'fecha_registro' => date('Y-m-d H:i:s'),
            'id_reserva'     => $idReserva,
        ]);
    }

    public static function agregarSeguimiento(int $idReserva, array $previo, string $observaciones, string $alergiasNuevas): int
    {
        return Ids::insertarConEntero('historial_cliente', 'id_historial', [
            'alergias'                => $alergiasNuevas !== '' ? $alergiasNuevas : $previo['alergias'],
            'objetivo_piel'           => $previo['objetivo_piel'],
            'abierto_recomendaciones' => $previo['abierto_recomendaciones'],
            'rutina_actual'           => $previo['rutina_actual'],
            'observaciones_sesion'    => $observaciones,
            'fecha_registro'          => date('Y-m-d H:i:s'),
            'id_reserva'              => $idReserva,
        ]);
    }

    public static function guardarObservacion(int $idReserva, string $observaciones): void
    {
        $hist = self::one('SELECT id_historial FROM historial_cliente WHERE id_reserva = ?', [$idReserva]);
        if ($hist !== null) {
            self::run('UPDATE historial_cliente SET observaciones_sesion = ? WHERE id_reserva = ?', [$observaciones, $idReserva]);
        } else {
            self::registrarNota($idReserva, $observaciones);
        }
    }

    /**
     * Añade una nueva entrada de seguimiento sin sobrescribir la ficha
     * anterior de la clienta. Queda vinculada a la reserva de la sesión.
     */
    public static function registrarSeguimiento(int $idReserva, array $datos): int
    {
        return Ids::insertarConEntero('historial_cliente', 'id_historial', [
            'alergias'                => $datos['alergias'] ?? null,
            'objetivo_piel'           => $datos['objetivo_piel'] ?? null,
            'abierto_recomendaciones' => !empty($datos['abierto_recomendaciones']) ? 1 : 0,
            'rutina_actual'           => $datos['rutina_actual'] ?? null,
            'observaciones_sesion'    => $datos['observaciones_sesion'] ?? null,
            'fecha_registro'          => date('Y-m-d H:i:s'),
            'id_reserva'              => $idReserva,
        ]);
    }
}
