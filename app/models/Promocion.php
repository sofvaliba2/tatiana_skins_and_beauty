<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Promociones comerciales (productos o servicios con precio rebajado).
 *
 * Toda la lectura se hace sobre las vistas de MySQL:
 *   - vw_promociones             (todas, con estado calculado por fechas)
 *   - vw_promociones_activas     (vigentes hoy)
 *   - vw_promociones_vencidas    (fecha_fin pasada)
 *   - vw_promociones_por_vencer  (activas con 7 días o menos de vigencia)
 */
final class Promocion extends Model
{
    public const ESTADOS = ['activa', 'inactiva'];

    /** Promociones vigentes hoy (para el sitio público y el panel). */
    public static function activas(int $limite = 0): array
    {
        $sql = 'SELECT * FROM vw_promociones_activas ORDER BY dias_restantes ASC, fecha_fin ASC';
        if ($limite > 0) {
            $sql .= ' LIMIT ' . $limite;
        }
        return self::all($sql);
    }

    /** Promociones activas que vencen en 7 días o menos. */
    public static function porVencer(): array
    {
        return self::all('SELECT * FROM vw_promociones_por_vencer ORDER BY fecha_fin ASC');
    }

    /** Promociones cuya fecha de fin ya pasó. */
    public static function vencidas(): array
    {
        return self::all('SELECT * FROM vw_promociones_vencidas ORDER BY fecha_fin DESC');
    }

    public static function porId(string $id): ?array
    {
        return self::one('SELECT * FROM vw_promociones WHERE id_promocion = ?', [$id]);
    }

    /**
     * Listado completo con filtros combinables de estado calculado y texto.
     * Devuelve filas paginadas y el total.
     */
    public static function buscar(string $q, string $estado, int $pagina, int $porPagina): array
    {
        [$sql, $params] = self::filtro($q, $estado);
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY
                    FIELD(estado_calculado, "activa", "programada", "vencida", "inactiva"),
                    fecha_fin DESC
                  LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    /** Todas las promociones sin paginar (usado para resúmenes). */
    public static function todas(): array
    {
        return self::all('SELECT * FROM vw_promociones ORDER BY fecha_fin DESC');
    }

    private static function filtro(string $q, string $estado): array
    {
        $sql = 'SELECT * FROM vw_promociones WHERE 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (nombre LIKE ? OR descripcion LIKE ? OR articulo LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $sql .= ' AND estado_calculado = ?';
            $params[] = $estado;
        }
        return [$sql, $params];
    }

    /** Conteo por estado calculado para las tarjetas del panel. */
    public static function resumen(): array
    {
        $resumen = ['total' => 0, 'activa' => 0, 'programada' => 0, 'vencida' => 0, 'inactiva' => 0];
        foreach (self::all('SELECT estado_calculado, COUNT(*) AS n FROM vw_promociones GROUP BY estado_calculado') as $f) {
            $clave = (string)$f['estado_calculado'];
            $n = (int)$f['n'];
            $resumen[$clave] = $n;
            $resumen['total'] += $n;
        }
        return $resumen;
    }

    /** Inserta o actualiza una promoción. Devuelve [id, creado]. */
    public static function guardar(?string $idEditar, array $datos): array
    {
        return self::transaccion(function () use ($idEditar, $datos) {
            if ($idEditar !== null) {
                self::run('UPDATE promocion SET ' . self::sets($datos) . ' WHERE id_promocion = ?', [...array_values($datos), $idEditar]);
                return [$idEditar, false];
            }
            return [Ids::insertarConCodigo('promocion', 'id_promocion', 'PROMO', 2, $datos), true];
        });
    }

    /** Activa o desactiva una promoción. */
    public static function cambiarEstado(string $id, string $estado): bool
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            return false;
        }
        return self::run('UPDATE promocion SET estado = ? WHERE id_promocion = ?', [$estado, $id]) > 0;
    }

    public static function eliminar(string $id): bool
    {
        return self::run('DELETE FROM promocion WHERE id_promocion = ?', [$id]) > 0;
    }
}
