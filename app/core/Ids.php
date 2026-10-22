<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Generador de identificadores: la base de datos no usa AUTO_INCREMENT,
 * por lo que los IDs se calculan con MAX()+1 bajo un candado GET_LOCK
 * para evitar duplicados ante peticiones simultáneas.
 */
final class Ids
{
    private static function lock(string $clave): void
    {
        $ok = Database::value('SELECT GET_LOCK(?, 10)', ['tatiana.' . $clave]);
        if ((string)$ok !== '1') {
            throw new \RuntimeException('No se pudo asignar un identificador. Reintenta.');
        }
    }

    private static function unlock(string $clave): void
    {
        Database::value('SELECT RELEASE_LOCK(?)', ['tatiana.' . $clave]);
    }

    public static function entero(string $tabla, string $columna): int
    {
        $clave = $tabla . '.' . $columna;
        self::lock($clave);
        try {
            $max = Database::value("SELECT COALESCE(MAX(`{$columna}`), 0) FROM `{$tabla}`");
            return (int)$max + 1;
        } finally {
            self::unlock($clave);
        }
    }

    public static function codigo(string $tabla, string $columna, string $prefijo, int $ancho = 2): string
    {
        $clave = $tabla . '.' . $columna;
        self::lock($clave);
        try {
            return self::siguienteCodigo($tabla, $columna, $prefijo, $ancho);
        } finally {
            self::unlock($clave);
        }
    }

    private static function siguienteCodigo(string $tabla, string $columna, string $prefijo, int $ancho): string
    {
        $max = Database::value(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(`{$columna}`, ?) AS UNSIGNED)), 0)
             FROM `{$tabla}`
             WHERE `{$columna}` LIKE ?",
            [strlen($prefijo) + 1, $prefijo . '%']
        );
        $n = (int)$max + 1;
        if ($n >= pow(10, $ancho)) {
            $ancho = strlen((string)$n);
        }
        return $prefijo . str_pad((string)$n, $ancho, '0', STR_PAD_LEFT);
    }

    public static function insertarConEntero(string $tabla, string $columna, array $datos): int
    {
        $clave = $tabla . '.' . $columna;
        self::lock($clave);
        try {
            $id = self::siguienteEntero($tabla, $columna);
            $datos[$columna] = $id;
            $cols = array_keys($datos);
            $sql = "INSERT INTO `{$tabla}` (`" . implode('`,`', $cols) . '`) VALUES (' .
                   implode(',', array_fill(0, count($cols), '?')) . ')';
            Database::run($sql, array_values($datos));
            return $id;
        } finally {
            self::unlock($clave);
        }
    }

    public static function insertarConCodigo(string $tabla, string $columna, string $prefijo, int $ancho, array $datos): string
    {
        $clave = $tabla . '.' . $columna;
        self::lock($clave);
        try {
            $id = self::siguienteCodigo($tabla, $columna, $prefijo, $ancho);
            $datos[$columna] = $id;
            $cols = array_keys($datos);
            $sql = "INSERT INTO `{$tabla}` (`" . implode('`,`', $cols) . '`) VALUES (' .
                   implode(',', array_fill(0, count($cols), '?')) . ')';
            Database::run($sql, array_values($datos));
            return $id;
        } finally {
            self::unlock($clave);
        }
    }

    private static function siguienteEntero(string $tabla, string $columna): int
    {
        $max = Database::value("SELECT COALESCE(MAX(`{$columna}`), 0) FROM `{$tabla}`");
        return (int)$max + 1;
    }
}
