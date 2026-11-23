<?php
declare(strict_types=1);

/**
 * Configuración centralizada del servidor SMTP.
 *
 * Las credenciales NO se guardan en este archivo ni en archivos públicos:
 * se leen de variables de entorno / .env (archivo ignorado por Git).
 * Soporta Gmail (contraseña de aplicación) y Brevo SMTP, o cualquier
 * servidor SMTP estándar.
 */

if (!function_exists('env')) {
    require_once __DIR__ . '/config.php';
}

$fromAddress = trim(env('MAIL_FROM', ''));
if ($fromAddress === '') {
    // Gmail exige que el From sea la cuenta autenticada. Si no se indicó
    // MAIL_FROM, se toma automáticamente SMTP_USER para evitar ese error.
    $fromAddress = trim(env('SMTP_USER', ''));
}
if ($fromAddress === '') {
    $fromAddress = 'no-reply@tatianaskinandbeauty.bo';
}

return [
    'host'         => trim(env('SMTP_HOST', '')),
    'port'         => max(1, (int)env('SMTP_PORT', '587')),
    'encryption'   => strtolower(trim(env('SMTP_ENCRYPTION', 'tls'))), // tls | ssl | none
    'auth'         => env('SMTP_AUTH', 'true') === 'true',
    'user'         => trim(env('SMTP_USER', '')),
    'pass'         => env('SMTP_PASS', ''),
    'timeout'      => max(5, (int)env('SMTP_TIMEOUT', '15')),
    'debug'        => max(0, min(4, (int)env('SMTP_DEBUG', '0'))), // 0 = silencioso; los logs van a error_log
    'from_address' => $fromAddress,
    'from_name'    => trim(env('MAIL_FROM_NAME', 'Tatiana Skin and Beauty')),
    'link_base'    => rtrim(trim(env('MAIL_LINK_BASE', '')), '/'),
];
