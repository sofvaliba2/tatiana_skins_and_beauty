<?php
/**
 * Front controller (router) del sistema.
 * Rutas: index.php?v=vista [&accion=...&id=...]
 * Protege rutas privadas según rol y conecta con la base de datos.
 */
declare(strict_types=1);

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$controlador_dir = __DIR__ . '/../app/controllers/';

/**
 * Mapa de rutas: vista => [archivo, clase, método, nivel]
 * 'publico'  → accesible sin sesión
 * 'cliente'  → requiere sesión (ideal para clientes)
 * 'personal' → requiere ROL01 o ROL02
 * 'admin'    → requiere ROL01
 */
$rutas = [
    'home'                => ['home.php', \App\Controllers\Home::class, 'index', 'publico'],
    'servicios'           => ['home.php', \App\Controllers\Home::class, 'servicios', 'publico'],
    'ayuda'               => ['home.php', \App\Controllers\Home::class, 'ayuda', 'publico'],

    'catalogo'            => ['catalogo.php', \App\Controllers\Catalogo::class, 'index', 'publico'],
    'producto'            => ['catalogo.php', \App\Controllers\Catalogo::class, 'detalle', 'publico'],
    'carrito'             => ['catalogo.php', \App\Controllers\Catalogo::class, 'carrito', 'cliente'],
    'agregar_carrito'     => ['catalogo.php', \App\Controllers\Catalogo::class, 'agregar', 'cliente'],
    'actualizar_carrito'  => ['catalogo.php', \App\Controllers\Catalogo::class, 'actualizar', 'cliente'],
    'quitar_carrito'      => ['catalogo.php', \App\Controllers\Catalogo::class, 'quitar', 'cliente'],
    'checkout'            => ['catalogo.php', \App\Controllers\Catalogo::class, 'checkout', 'cliente'],

    'login'               => ['auth.php', \App\Controllers\AuthController::class, 'login', 'publico'],
    'registro'            => ['auth.php', \App\Controllers\AuthController::class, 'registro', 'publico'],
    'recuperar'           => ['auth.php', \App\Controllers\AuthController::class, 'recuperar', 'publico'],
    'logout'              => ['auth.php', \App\Controllers\AuthController::class, 'logout', 'publico'],

    'panel'               => ['cliente.php', \App\Controllers\Cliente::class, 'panel', 'cliente'],
    'perfil'              => ['cliente.php', \App\Controllers\Cliente::class, 'perfil', 'cliente'],
    'mis_compras'         => ['cliente.php', \App\Controllers\Cliente::class, 'compras', 'cliente'],
    'detalle_compra'      => ['cliente.php', \App\Controllers\Cliente::class, 'detalleCompra', 'cliente'],

    'reserva_nueva'       => ['reserva.php', \App\Controllers\Reserva::class, 'nueva', 'cliente'],
    'reserva_pago'        => ['reserva.php', \App\Controllers\Reserva::class, 'pago', 'cliente'],
    'reserva_formulario'  => ['reserva.php', \App\Controllers\Reserva::class, 'formulario', 'cliente'],
    'reserva_seguimiento' => ['reserva.php', \App\Controllers\Reserva::class, 'seguimiento', 'cliente'],
    'reserva_recibo'      => ['reserva.php', \App\Controllers\Reserva::class, 'recibo', 'cliente'],
    'mis_reservas'        => ['reserva.php', \App\Controllers\Reserva::class, 'misReservas', 'cliente'],
    'recomendaciones'     => ['reserva.php', \App\Controllers\Reserva::class, 'recomendaciones', 'cliente'],

    'admin_dashboard'     => ['admin.php', \App\Controllers\Admin::class, 'dashboard', 'personal'],
    'admin_clientes'      => ['admin.php', \App\Controllers\Admin::class, 'clientes', 'personal'],
    'admin_citas'         => ['admin.php', \App\Controllers\Admin::class, 'citas', 'personal'],
    'admin_inventario'    => ['admin.php', \App\Controllers\Admin::class, 'inventario', 'personal'],
    'admin_ventas'        => ['admin.php', \App\Controllers\Admin::class, 'ventas', 'personal'],
    'admin_servicios'     => ['admin.php', \App\Controllers\Admin::class, 'servicios', 'personal'],
    'admin_sucursales'    => ['admin_sistema.php', \App\Controllers\AdminSistema::class, 'sucursales', 'admin'],
    'admin_usuarios'      => ['admin_sistema.php', \App\Controllers\AdminSistema::class, 'usuarios', 'admin'],
    'admin_reportes'      => ['admin_sistema.php', \App\Controllers\AdminSistema::class, 'reportes', 'personal'],
    'admin_notificaciones'=> ['admin_sistema.php', \App\Controllers\AdminSistema::class, 'notificaciones', 'personal'],
    'admin_perfil'        => ['admin_sistema.php', \App\Controllers\AdminSistema::class, 'perfil', 'personal'],
    'admin_config'        => ['admin_sistema.php', \App\Controllers\AdminSistema::class, 'config', 'admin'],

    'error_403'           => ['errores.php', \App\Controllers\Errores::class, 'denegado', 'publico'],
    'error_404'           => ['errores.php', \App\Controllers\Errores::class, 'noEncontrado', 'publico'],
];

$vista = (string)($_GET['v'] ?? 'home');

if (!isset($rutas[$vista])) {
    http_response_code(404);
    require $controlador_dir . 'errores.php';
    (new \App\Controllers\Errores())->noEncontrado();
    exit;
}

[$archivo, $clase, $metodo, $nivel] = $rutas[$vista];

/* ---------- Protección de rutas ---------- */
$usuario = Auth::usuario();

if ($nivel !== 'publico' && $usuario === null) {
    Flash::error('Inicia sesión para continuar.');
    header('Location: ' . BASE_URL . '/index.php?v=login&next=' . urlencode($vista));
    exit;
}
if ($nivel === 'admin' && ($usuario['id_rol'] ?? '') !== 'ROL01') {
    http_response_code(403);
    require $controlador_dir . 'errores.php';
    (new \App\Controllers\Errores())->denegado();
    exit;
}
if ($nivel === 'personal' && !in_array($usuario['id_rol'] ?? '', ['ROL01', 'ROL02'], true)) {
    http_response_code(403);
    require $controlador_dir . 'errores.php';
    (new \App\Controllers\Errores())->denegado();
    exit;
}
if ($vista === 'login' && $usuario !== null) {
    header('Location: ' . BASE_URL . '/index.php?v=' . ($usuario['id_rol'] === 'ROL03' ? 'panel' : 'admin_dashboard'));
    exit;
}
if ($vista === 'registro' && $usuario !== null) {
    header('Location: ' . BASE_URL . '/index.php?v=' . ($usuario['id_rol'] === 'ROL03' ? 'panel' : 'admin_dashboard'));
    exit;
}

/* ---------- CSRF para todas las peticiones POST ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !Csrf::validar()) {
    http_response_code(403);
    exit('Sesión expirada o envío no válido. <a href="' . BASE_URL . '/index.php?v=home">Volver al inicio</a>');
}

/* ---------- Ejecuta la acción ---------- */
require_once $controlador_dir . 'errores.php';

try {
    if (!is_file($controlador_dir . $archivo)) {
        throw new \RuntimeException('Módulo en construcción: ' . $archivo);
    }
    require_once $controlador_dir . $archivo;
    $controlador = new $clase();
    $controlador->$metodo();
} catch (\Throwable $e) {
    error_log('[APP] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    (new \App\Controllers\Errores())->error($e);
}
