<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Correo;

if (PHP_SAPI !== 'cli') {
    exit("Solo se ejecuta desde la línea de comandos.\n");
}

$cfg = require BASE_PATH . '/config/smtp.php';

echo 'Servidor: ' . $cfg['host'] . ':' . $cfg['port'] . ' (' . $cfg['encryption'] . ")\n";
echo 'Usuario SMTP: ' . ($cfg['user'] !== '' ? 'configurado' : 'VACÍO') . "\n";
echo 'Contraseña SMTP: ' . ($cfg['pass'] !== '' ? 'configurada' : 'VACÍA') . "\n";
echo 'Remitente (From): ' . $cfg['from_address'] . "\n";
echo 'Base del enlace: ' . ($cfg['link_base'] !== '' ? $cfg['link_base'] : 'VACÍA (se usa APP_URL)') . "\n";

if (str_contains($cfg['host'], 'gmail.com') && $cfg['from_address'] !== $cfg['user']) {
    echo "AVISO: con Gmail, MAIL_FROM debe ser igual a SMTP_USER (o un alias verificado).\n";
}
if (str_contains($cfg['link_base'], 'tu-dominio-real')) {
    echo "AVISO: MAIL_LINK_BASE todavía es un dominio de ejemplo; los enlaces no servirán.\n";
}
echo "\n";

if (!Correo::configuracionCompleta()) {
    fwrite(STDERR, "FALLO: faltan datos SMTP en .env (SMTP_HOST, SMTP_USER o SMTP_PASS).\n");
    exit(1);
}

// Envío de prueba a la propia cuenta SMTP: no crea tokens ni usa el módulo de recuperación.
$enviado = Correo::enviar(
    $cfg['user'],
    'Prueba SMTP · Tatiana Skin and Beauty',
    '<p>Si recibes este correo, la configuración SMTP funciona correctamente.</p>',
    'Si recibes este correo, la configuración SMTP funciona correctamente.'
);

if ($enviado) {
    echo "OK: el servidor SMTP aceptó el mensaje. Revisa la bandeja y el spam de " . $cfg['user'] . ".\n";
    exit(0);
}

fwrite(STDERR, 'FALLO: ' . (Correo::ultimoError() ?? 'error desconocido') . "\n");
fwrite(STDERR, "Revisa también storage/logs/php_error.log.\n");
exit(1);
