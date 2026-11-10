<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Models\Usuario;

if (PHP_SAPI !== 'cli') {
    exit("Solo se ejecuta desde la linea de comandos.\n");
}

$correo = mb_strtolower(trim($argv[1] ?? ''));
$nombre = trim($argv[2] ?? 'Administrador');
$apPaterno = trim($argv[3] ?? 'Principal');

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php bin/crear_admin.php correo@ejemplo.com \"Nombre\" \"Apellido\"\n");
    exit(1);
}

$clave = bin2hex(random_bytes(4));
$r = Usuario::crearOReiniciarAdmin($correo, $nombre, $apPaterno, $clave);

echo $r['creado'] ? "Administrador creado: {$r['id']}\n" : "Contraseña restablecida para {$correo}.\n";
echo "Correo: {$correo}\n";
echo "Clave:  {$clave}\n";
echo "Entra en: index.php?v=login\n";
