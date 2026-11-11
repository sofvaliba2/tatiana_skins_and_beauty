<?php
declare(strict_types=1);

/**
 * Restablece las contraseñas de los usuarios de prueba con valores conocidos.
 *
 * Uso:
 *   C:\xampp\php\php.exe bin\reset_passwords.php
 *   C:\xampp\php\php.exe bin\reset_passwords.php --solo-admin
 *
 * Solo se ejecuta por CLI. No toca datos de reservas, compras ni configuración.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Models\Usuario;

if (PHP_SAPI !== 'cli') {
    exit("Solo se ejecuta desde la linea de comandos.\n");
}

$soloAdmin = in_array('--solo-admin', $argv, true);

$adminCorreo = 'admin.test@test.bo';
$adminClave  = 'TatianaAdmin2026!';

$clienteCorreo = 'quispeliz143@gmail.com';
$clienteClave  = 'TatianaCliente2026!';

$salida = [];

try {
    $r = Usuario::crearOReiniciarAdmin($adminCorreo, 'Administrador', 'Principal', $adminClave);
    $salida[] = ($r['creado'] ? 'Administrador creado' : 'Contraseña del administrador restablecida')
        . ' (' . $r['id'] . '): ' . $adminCorreo . ' / ' . $adminClave;

    if (!$soloAdmin) {
        $cuenta = Usuario::cuentaPorCorreo($clienteCorreo);
        if ($cuenta !== null) {
            Usuario::cambiarPassword((string)$cuenta['id_usuario'], $clienteClave);
            $salida[] = 'Contraseña del cliente restablecida (' . $cuenta['id_usuario'] . '): '
                . $clienteCorreo . ' / ' . $clienteClave;
        } else {
            $salida[] = 'Cliente no encontrado: ' . $clienteCorreo . ' (no se creó nada).';
        }
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

foreach ($salida as $linea) {
    echo $linea . "\n";
}
echo "Listo. Ingresa en index.php?v=login\n";
