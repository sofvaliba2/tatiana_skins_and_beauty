<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/config.php';

date_default_timezone_set('America/La_Paz');
mb_internal_encoding('UTF-8');

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php_error.log');


spl_autoload_register(function (string $clase): void {
    $mapa = [
        'App\\Core\\'        => '/app/core/',
        'App\\Models\\'      => '/app/models/',
        'App\\Controllers\\' => '/app/controllers/',
    ];
    foreach ($mapa as $prefijo => $carpeta) {
        if (strncmp($clase, $prefijo, strlen($prefijo)) === 0) {
            $ruta = BASE_PATH . $carpeta . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
            if (is_readable($ruta)) {
                require $ruta;
            }
            return;
        }
    }
});

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
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
