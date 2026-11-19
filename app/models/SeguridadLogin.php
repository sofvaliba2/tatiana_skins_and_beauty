<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Control server-side de intentos fallidos de inicio de sesión.
 * El estado vive en MySQL (cuenta + IP), por lo que no se puede evadir
 * recargando la página, cerrando el navegador o borrando cookies.
 */
final class SeguridadLogin extends Model
{
    public const MAX_INTENTOS = 5;
    public const MINUTOS_BLOQUEO = 15;

    public static function ip(): string
    {
        // Se usa REMOTE_ADDR (no X-Forwarded-For) para evitar cabeceras suplantables.
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        return mb_substr($ip === '' ? '127.0.0.1' : $ip, 0, 45);
    }

    /** Estado actual: intentos, si está bloqueado y segundos restantes. */
    public static function estado(string $correo, string $ip): array
    {
        $fila = self::one(
            'SELECT intentos, bloqueado_hasta FROM seguridad_login WHERE correo = ? AND ip = ?',
            [$correo, $ip]
        );
        if ($fila === null) {
            return ['intentos' => 0, 'segundos' => 0, 'bloqueado' => false];
        }

        $hasta = $fila['bloqueado_hasta'] !== null
            ? strtotime((string)$fila['bloqueado_hasta']) : null;

        // El bloqueo es temporal: al vencer se reinicia el contador.
        if ($hasta !== null && $hasta <= time()) {
            self::run(
                'UPDATE seguridad_login SET intentos = 0, bloqueado_hasta = NULL WHERE correo = ? AND ip = ?',
                [$correo, $ip]
            );
            return ['intentos' => 0, 'segundos' => 0, 'bloqueado' => false];
        }

        $segundos = $hasta !== null ? max(0, $hasta - time()) : 0;
        return [
            'intentos'  => (int)$fila['intentos'],
            'segundos'  => $segundos,
            'bloqueado' => $segundos > 0,
        ];
    }

    /** Registra un fallo y devuelve el estado resultante (bloqueando al llegar a 5). */
    public static function registrarFallo(string $correo, string $ip): array
    {
        self::run(
            'INSERT INTO seguridad_login (correo, ip, intentos, ultimo_intento)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultimo_intento = NOW()',
            [$correo, $ip]
        );

        $estado = self::estado($correo, $ip);
        if ($estado['intentos'] >= self::MAX_INTENTOS && !$estado['bloqueado']) {
            self::run(
                'UPDATE seguridad_login SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL '
                    . self::MINUTOS_BLOQUEO . ' MINUTE) WHERE correo = ? AND ip = ?',
                [$correo, $ip]
            );
            $estado = self::estado($correo, $ip);
        }
        return $estado;
    }

    /** El contador se borra por completo tras un inicio de sesión correcto. */
    public static function reiniciar(string $correo, string $ip): void
    {
        self::run('DELETE FROM seguridad_login WHERE correo = ? AND ip = ?', [$correo, $ip]);
    }

    public static function mensajeBloqueo(int $segundos): string
    {
        $minutos = intdiv($segundos, 60);
        $resto = $segundos % 60;
        if ($minutos > 0) {
            return 'Alcanzaste el límite de ' . self::MAX_INTENTOS
                . ' intentos fallidos. Tu acceso está bloqueado temporalmente. '
                . 'Intenta nuevamente en ' . $minutos . ' min'
                . ($resto > 0 ? ' y ' . $resto . ' s' : '') . '.';
        }
        return 'Alcanzaste el límite de ' . self::MAX_INTENTOS
            . ' intentos fallidos. Intenta nuevamente en ' . max(1, $segundos) . ' segundos.';
    }
}
