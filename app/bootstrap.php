<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

function env(string $clave, string $def = ''): string
{
    $archivo = BASE_PATH . '/.env';
    static $vars = null;
    if ($vars === null) {
        $vars = [];
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
define('DB_NAME', env('DB_NAME', 'tatiana_skins_and_beauty_stea'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

date_default_timezone_set('America/La_Paz');
mb_internal_encoding('UTF-8');

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/.storage/logs/php_error.log');

spl_autoload_register(function (string $clase): void {
    $prefijo = 'App\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $rel = str_replace('\\', '/', substr($clase, strlen($prefijo)));
    $ruta = BASE_PATH . '/app/' . $rel . '.php';
    if (is_readable($ruta)) {
        require $ruta;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('TATIANA_SESSID');
    session_start();
}

require_once BASE_PATH . '/app/views/layout/helpers.php';
