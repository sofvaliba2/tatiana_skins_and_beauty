<?php
declare(strict_types=1);

namespace App\Models;

final class Sucursal extends Model
{
    public const DIAS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

    private const ORDEN_DIAS = 'FIELD(h.dia_semana, "Lunes","Martes","Miercoles","Jueves","Viernes","Sabado","Domingo")';

    public static function todas(): array
    {
        return self::all('SELECT * FROM sucursal ORDER BY nombre_sucursal');
    }

    public static function lista(): array
    {
        return self::all('SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal');
    }

    public static function porId(string $id): ?array
    {
        return self::one('SELECT * FROM sucursal WHERE id_sucursal = ?', [$id]);
    }

    public static function nombre(string $id): ?array
    {
        return self::one('SELECT nombre_sucursal FROM sucursal WHERE id_sucursal = ?', [$id]);
    }

    public static function conHorariosActivos(): array
    {
        return self::all(
            'SELECT s.*, h.dia_semana, h.hora_apertura, h.hora_cierre
             FROM sucursal s
             LEFT JOIN horario_sucursal h ON h.id_sucursal = s.id_sucursal AND h.es_activo = 1
             ORDER BY s.nombre_sucursal, ' . self::ORDEN_DIAS
        );
    }

    public static function horarios(string $idSucursal): array
    {
        return self::all(
            'SELECT h.* FROM horario_sucursal h WHERE h.id_sucursal = ? ORDER BY ' . self::ORDEN_DIAS,
            [$idSucursal]
        );
    }

    public static function horarioDelDia(string $idSucursal, string $dia): ?array
    {
        return self::one(
            'SELECT hora_apertura, hora_cierre FROM horario_sucursal
             WHERE id_sucursal = ? AND dia_semana = ? AND es_activo = 1',
            [$idSucursal, $dia]
        );
    }

    /** Día de la semana en el formato usado por horario_sucursal. */
    public static function diaSemana(string $fecha): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if ($d === false || $d->format('Y-m-d') !== $fecha) {
            return '';
        }
        $mapa = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
        ];
        return $mapa[(int)$d->format('N')] ?? '';
    }

    /** Horario activo de una sucursal para una fecha concreta. */
    public static function horarioParaFecha(string $idSucursal, string $fecha): ?array
    {
        $dia = self::diaSemana($fecha);
        if ($dia === '') {
            return null;
        }
        return self::horarioDelDia($idSucursal, $dia);
    }

    public static function guardar(?string $idEditar, array $datos): array
    {
        if ($idEditar !== null) {
            self::run('UPDATE sucursal SET ' . self::sets($datos) . ' WHERE id_sucursal = ?', [...array_values($datos), $idEditar]);
            return [$idEditar, false];
        }
        $id = Ids::insertarConCodigo('sucursal', 'id_sucursal', 'SUC', 2, $datos);
        foreach (self::DIAS as $dia) {
            Ids::insertarConEntero('horario_sucursal', 'id_horario', [
                'dia_semana'    => $dia,
                'hora_apertura' => null,
                'hora_cierre'   => null,
                'es_activo'     => 0,
                'id_sucursal'   => $id,
            ]);
        }
        return [$id, true];
    }

    public static function guardarHorario(string $idSucursal, array $dias, array $aperturas, array $cierres): void
    {
        foreach (self::DIAS as $dia) {
            $activo = isset($dias[$dia]) ? 1 : 0;
            $ap = $aperturas[$dia] ?? '';
            $ci = $cierres[$dia] ?? '';
            if ($activo && ($ap === '' || $ci === '')) {
                continue;
            }
            $existente = self::one('SELECT id_horario FROM horario_sucursal WHERE id_sucursal = ? AND dia_semana = ?', [$idSucursal, $dia]);
            if ($existente !== null) {
                self::run(
                    'UPDATE horario_sucursal SET hora_apertura = ?, hora_cierre = ?, es_activo = ? WHERE id_horario = ?',
                    [$activo ? $ap . ':00' : null, $activo ? $ci . ':00' : null, $activo, $existente['id_horario']]
                );
            } elseif ($activo) {
                Ids::insertarConEntero('horario_sucursal', 'id_horario', [
                    'dia_semana'    => $dia,
                    'hora_apertura' => $ap . ':00',
                    'hora_cierre'   => $ci . ':00',
                    'es_activo'     => 1,
                    'id_sucursal'   => $idSucursal,
                ]);
            }
        }
    }
}
