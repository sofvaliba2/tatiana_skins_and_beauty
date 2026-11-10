<?php
declare(strict_types=1);

namespace App\Models;

final class MetodoPago extends Model
{
    public static function activos(): array
    {
        return self::all("SELECT * FROM metodo_pago WHERE estado = 'activo' OR estado IS NULL ORDER BY nombre_metodoPago");
    }

    public static function nombre(string $id): string
    {
        return (string)self::value('SELECT nombre_metodoPago FROM metodo_pago WHERE id_metodoPago = ?', [$id]);
    }

    public static function conUso(): array
    {
        return self::all(
            'SELECT m.*,
                    (SELECT COUNT(*) FROM compra c WHERE c.id_metodoPago = m.id_metodoPago) AS compras,
                    (SELECT COUNT(*) FROM reserva r WHERE r.id_metodoPago = m.id_metodoPago) AS reservas
             FROM metodo_pago m ORDER BY m.nombre_metodoPago'
        );
    }

    public static function existeNombre(string $nombre): bool
    {
        return self::one('SELECT id_metodoPago FROM metodo_pago WHERE nombre_metodoPago = ?', [$nombre]) !== null;
    }

    public static function crear(string $nombre, string $tipo): string
    {
        return Ids::insertarConCodigo('metodo_pago', 'id_metodoPago', 'MP', 2, [
            'nombre_metodoPago' => $nombre,
            'tipo'              => $tipo !== '' ? $tipo : 'general',
            'estado'            => 'activo',
        ]);
    }

    public static function cambiarEstado(string $id, string $estado): void
    {
        self::run('UPDATE metodo_pago SET estado = ? WHERE id_metodoPago = ?', [$estado, $id]);
    }

    public static function usos(string $id): int
    {
        return (int)self::value('SELECT COUNT(*) FROM compra WHERE id_metodoPago = ?', [$id])
             + (int)self::value('SELECT COUNT(*) FROM reserva WHERE id_metodoPago = ?', [$id]);
    }

    public static function eliminar(string $id): void
    {
        self::run('DELETE FROM metodo_pago WHERE id_metodoPago = ?', [$id]);
    }
}
