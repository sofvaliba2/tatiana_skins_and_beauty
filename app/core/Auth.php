<?php
declare(strict_types=1);

namespace App\Core;

use PDOException;

final class Auth
{
    private const TIMEOUT = 7200;

    public static function intentar(string $correo, string $password): bool
    {
        $cuenta = Database::one(
            'SELECT c.id_cuentaUsuario, c.correo, c.password, c.id_rol, c.id_usuario,
                    r.nombre_rol,
                    u.nombre, u.ap_paterno, u.ap_materno, u.telefono, u.id_sucursal
             FROM cuenta_usuario c
             JOIN rol r ON r.id_rol = c.id_rol
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             WHERE c.correo = ?
             LIMIT 1',
            [mb_strtolower(trim($correo))]
        );

        if ($cuenta === null || !self::verificarPassword($password, (string)$cuenta['password'], (string)$cuenta['correo'])) {
            return false;
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
        return self::usuario()['id_rol'] ?? '' === 'ROL01';
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

    public static function hashPassword(string $password, string $correo): string
    {
        $sal = substr(hash('sha256', $correo . '|' . APP_KEY), 0, 16);
        return base64_encode(hash_hmac('sha256', $password . '|' . $sal, APP_KEY, true));
    }

    public static function verificarPassword(string $password, string $guardado, string $correo): bool
    {
        if ($guardado === '') {
            return false;
        }
        return hash_equals(self::hashPassword($password, $correo), $guardado);
    }
}
