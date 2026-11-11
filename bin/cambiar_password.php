<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Models\Usuario;

if (PHP_SAPI !== 'cli') {
    exit("Solo se ejecuta desde la linea de comandos.\n");
}

$correo = mb_strtolower(trim($argv[1] ?? ''));
$clave = (string)($argv[2] ?? '');

if ($correo === '' || strlen($clave) < 8) {
    fwrite(STDERR, "Uso: php bin/cambiar_password.php correo@ejemplo.com nueva_clave (minimo 8 caracteres)\n");
    exit(1);
}

$cuenta = Usuario::cuentaPorCorreo($correo);
if ($cuenta === null) {
    fwrite(STDERR, "No existe una cuenta con el correo {$correo}.\n");
    exit(1);
}

Usuario::cambiarPassword((string)$cuenta['id_usuario'], $clave);
echo "Contraseña actualizada para {$correo} (rol: {$cuenta['nombre_rol']}).\n";