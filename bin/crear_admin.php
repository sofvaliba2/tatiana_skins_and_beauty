<?php
/**
 * Crea (o restablece) una cuenta de administrador desde la línea de comandos.
 *
 * Uso:
 *   php bin/crear_admin.php correo@ejemplo.com "Nombre" "Apellido"
 *
 * Si el correo ya existe, se restablece su contraseña (se genera e imprime).
 * No modifica otras tablas.
 */
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Ids;

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

$existe = Database::one('SELECT id_usuario FROM cuenta_usuario WHERE correo = ?', [$correo]);
$clave = bin2hex(random_bytes(4));

if ($existe !== null) {
    Database::run('UPDATE cuenta_usuario SET password = ?, id_rol = "ROL01" WHERE correo = ?', [
        Auth::hashPassword($clave, $correo),
        $correo,
    ]);
    Database::run('UPDATE usuario SET id_rol = "ROL01" WHERE id_usuario = ?', [$existe['id_usuario']]);
    echo "Contraseña restablecida para {$correo}.\n";
} else {
    $id = Ids::insertarConCodigo('usuario', 'id_usuario', 'ADM', 3, [
        'nombre'          => $nombre,
        'ap_paterno'      => $apPaterno,
        'ap_materno'      => '',
        'telefono'        => '',
        'fecha_nacimiento' => null,
        'id_rol'          => 'ROL01',
        'id_sucursal'     => null,
    ]);
    Ids::insertarConEntero('cuenta_usuario', 'id_cuentaUsuario', [
        'correo'     => $correo,
        'password'   => Auth::hashPassword($clave, $correo),
        'id_rol'     => 'ROL01',
        'id_usuario' => $id,
    ]);
    echo "Administrador creado: {$id}\n";
}

echo "Correo: {$correo}\n";
echo "Clave:  {$clave}\n";
echo "Entra en: index.php?v=login\n";
