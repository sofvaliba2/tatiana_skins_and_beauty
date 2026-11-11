<?php
declare(strict_types=1);

function env(string $clave, string $def = ''): string
{
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $archivo = BASE_PATH . '/.env';
        if (is_readable($archivo)) {
            foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
                $linea = trim($linea);
                if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                    continue;
                }
                [$k, $v] = explode('=', $linea, 2);
                $vars[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }
    return $vars[$clave] ?? $def;
}

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');
define('APP_KEY', env('APP_KEY', ''));
define('APP_URL', rtrim(env('APP_URL', ''), '/'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'tatiana_skins_beauty_corregida'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
