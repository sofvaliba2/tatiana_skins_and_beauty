<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Tokens de un solo uso para recuperar la contraseña.
 * El token en claro solo viaja por correo; en BD se guarda únicamente su hash SHA-256.
 */
final class RecuperacionPassword extends Model
{
    public const MINUTOS_VIGENCIA = 60;

    /**
     * Crea un token para el correo indicado.
     * Devuelve ['token' => string, 'correo' => string] o null si la cuenta no existe.
     * Nunca revela al usuario si el correo está registrado.
     */
    public static function crear(string $correo): ?array
    {
        $correo = mb_strtolower(trim($correo));
        $cuenta = Usuario::cuentaPorCorreo($correo);
        if ($cuenta === null) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        self::run(
            'INSERT INTO recuperacion_password (id_usuario, token_hash, expira_en, ip)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . self::MINUTOS_VIGENCIA . ' MINUTE), ?)',
            [(string)$cuenta['id_usuario'], hash('sha256', $token), SeguridadLogin::ip()]
        );
        return ['token' => $token, 'correo' => $correo];
    }

    /** Devuelve el registro del token si es válido, no usado y no vencido. */
    public static function validar(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return self::one(
            'SELECT id, id_usuario, expira_en, usado_en
             FROM recuperacion_password
             WHERE token_hash = ? AND usado_en IS NULL AND expira_en > NOW()
             LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    /** Marca el token como usado e invalida cualquier otro token pendiente del usuario. */
    public static function consumir(string $token, string $idUsuario): void
    {
        self::run(
            'UPDATE recuperacion_password SET usado_en = NOW()
             WHERE token_hash = ? AND id_usuario = ?',
            [hash('sha256', $token), $idUsuario]
        );
        self::run(
            'UPDATE recuperacion_password SET usado_en = NOW()
             WHERE id_usuario = ? AND usado_en IS NULL',
            [$idUsuario]
        );
    }

    /** Limpieza oportunista de tokens vencidos/usados (evita crecimiento infinito). */
    public static function limpiar(): void
    {
        self::run('DELETE FROM recuperacion_password WHERE expira_en < DATE_SUB(NOW(), INTERVAL 1 DAY) OR usado_en IS NOT NULL');
    }
}
