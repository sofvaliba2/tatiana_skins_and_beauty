<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOStatement;

abstract class Model
{
    private static function ejecutar(string $sql, array $params): PDOStatement
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    protected static function all(string $sql, array $params = []): array
    {
        return self::ejecutar($sql, $params)->fetchAll();
    }

    protected static function one(string $sql, array $params = []): ?array
    {
        $fila = self::ejecutar($sql, $params)->fetch();
        return $fila === false ? null : $fila;
    }

    protected static function value(string $sql, array $params = []): mixed
    {
        $v = self::ejecutar($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    protected static function run(string $sql, array $params = []): int
    {
        return self::ejecutar($sql, $params)->rowCount();
    }

    protected static function transaccion(callable $fn): mixed
    {
        Database::begin();
        try {
            $r = $fn();
            Database::commit();
            return $r;
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    protected static function contar(string $sql, array $params = []): int
    {
        return (int)self::value('SELECT COUNT(*) FROM (' . $sql . ') t', $params);
    }

    protected static function sets(array $datos): string
    {
        return implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($datos)));
    }

    public static function existeValor(string $tabla, string $columna, string $valor, ?string $excluirColumna = null, ?string $excluirValor = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `{$tabla}` WHERE `{$columna}` = ?";
        $params = [$valor];
        if ($excluirColumna !== null && $excluirValor !== null) {
            $sql .= " AND `{$excluirColumna}` <> ?";
            $params[] = $excluirValor;
        }
        return (int)self::value($sql, $params) > 0;
    }
}
