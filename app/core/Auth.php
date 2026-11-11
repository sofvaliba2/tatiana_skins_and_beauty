<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

final class Auth
{
    private const TIMEOUT = 7200;

    public static function intentar(string $correo, string $password): bool
    {
        $cuenta = Usuario::cuentaPorCorreo($correo);

        if ($cuenta === null || !self::verificarPassword($password, (string)$cuenta['password'], (string)$cuenta['correo'])) {
            return false;
        }

        // Migración transparente: al iniciar sesión, un hash antiguo se
        // reemplaza por password_hash() sin pedirle nada extra al usuario.
        if (self::debeRehashear((string)$cuenta['password'])) {
            try {
                Usuario::cambiarPassword((string)$cuenta['id_usuario'], $password);
            } catch (\Throwable $e) {
                error_log('[AUTH-REHASH] ' . $e->getMessage());
            }
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'         => (string)$cuenta['id_usuario'],
            'cuenta'     => (int)$cuenta['id_cuentaUsuario'],
            'correo'     => (string)$cuenta['correo'],
            'id_rol'     => (string)$cuenta['id_rol'],
            'rol'        => (string)$cuenta['nombre_rol'],
            'nombre'     => (string)$cuenta['nombre'],
            'apellidos'  => trim(($cuenta['ap_paterno'] ?? '') . ' ' . ($cuenta['ap_materno'] ?? '')),
            'telefono'   => (string)($cuenta['telefono'] ?? ''),
            'sucursal'   => (string)($cuenta['id_sucursal'] ?? ''),
            'ultimo_acceso' => time(),
        ];
        return true;
    }

    public static function usuario(): ?array
    {
        if (empty($_SESSION['usuario'])) {
            return null;
        }
        if (time() > (int)($_SESSION['usuario']['ultimo_acceso'] ?? 0) + self::TIMEOUT) {
            self::salir();
            return null;
        }
        $_SESSION['usuario']['ultimo_acceso'] = time();
        return $_SESSION['usuario'];
    }

    public static function estaLogueado(): bool
    {
        return self::usuario() !== null;
    }

    public static function esAdmin(): bool
    {
        return (self::usuario()['id_rol'] ?? '') === 'ROL01';
    }

    public static function esPersonal(): bool
    {
        $r = self::usuario()['id_rol'] ?? '';
        return $r === 'ROL01' || $r === 'ROL02';
    }

    public static function esCliente(): bool
    {
        return (self::usuario()['id_rol'] ?? '') === 'ROL03';
    }

    public static function id(): string
    {
        return self::usuario()['id'] ?? '';
    }

    public static function salir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function hashPassword(string $password, string $correo = ''): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verificarPassword(string $password, string $guardado, string $correo): bool
    {
        if ($guardado === '') {
            return false;
        }

        $info = password_get_info($guardado);
        if (($info['algoName'] ?? 'unknown') !== 'unknown') {
            return password_verify($password, $guardado);
        }

        // Compatibilidad con hashes HMAC-SHA256 del sistema anterior.
        $sal = substr(hash('sha256', $correo . '|' . APP_KEY), 0, 16);
        $legacy = base64_encode(hash_hmac('sha256', $password . '|' . $sal, APP_KEY, true));
        return hash_equals($legacy, $guardado);
    }

    private static function debeRehashear(string $guardado): bool
    {
        $info = password_get_info($guardado);
        return ($info['algoName'] ?? 'unknown') === 'unknown'
            || password_needs_rehash($guardado, PASSWORD_DEFAULT);
    }
}
