<?php
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AdminSistemaController;
use App\Controllers\AuthController;
use App\Controllers\CatalogoController;
use App\Controllers\ClienteController;
use App\Controllers\ErroresController;
use App\Controllers\HomeController;
use App\Controllers\ReservaController;

return [
    // Sitio público
    'home'                 => [HomeController::class, 'index', 'publico'],
    'servicios'            => [HomeController::class, 'servicios', 'publico'],
    'ayuda'                => [HomeController::class, 'ayuda', 'publico'],

    // Catálogo y carrito
    'catalogo'             => [CatalogoController::class, 'index', 'publico'],
    'producto'             => [CatalogoController::class, 'detalle', 'publico'],
    'carrito'              => [CatalogoController::class, 'carrito', 'cliente'],
    'agregar_carrito'      => [CatalogoController::class, 'agregar', 'cliente'],
    'actualizar_carrito'   => [CatalogoController::class, 'actualizar', 'cliente'],
    'quitar_carrito'       => [CatalogoController::class, 'quitar', 'cliente'],
    'checkout'             => [CatalogoController::class, 'checkout', 'cliente'],

    // Autenticación
    'login'                => [AuthController::class, 'login', 'publico'],
    'registro'             => [AuthController::class, 'registro', 'publico'],
    'recuperar'            => [AuthController::class, 'recuperar', 'publico'],
    'logout'               => [AuthController::class, 'logout', 'publico'],

    // Panel del cliente
    'panel'                => [ClienteController::class, 'panel', 'cliente'],
    'perfil'               => [ClienteController::class, 'perfil', 'cliente'],
    'mis_compras'          => [ClienteController::class, 'compras', 'cliente'],
    'detalle_compra'       => [ClienteController::class, 'detalleCompra', 'cliente'],

    // Reservas
    'reserva_nueva'        => [ReservaController::class, 'nueva', 'cliente'],
    'reserva_pago'         => [ReservaController::class, 'pago', 'cliente'],
    'reserva_formulario'   => [ReservaController::class, 'formulario', 'cliente'],
    'reserva_seguimiento'  => [ReservaController::class, 'seguimiento', 'cliente'],
    'reserva_recibo'       => [ReservaController::class, 'recibo', 'cliente'],
    'mis_reservas'         => [ReservaController::class, 'misReservas', 'cliente'],
    'recomendaciones'      => [ReservaController::class, 'recomendaciones', 'cliente'],

    // Administración
    'admin_dashboard'      => [AdminController::class, 'dashboard', 'personal'],
    'admin_clientes'       => [AdminController::class, 'clientes', 'personal'],
    'admin_citas'          => [AdminController::class, 'citas', 'personal'],
    'admin_inventario'     => [AdminController::class, 'inventario', 'personal'],
    'admin_ventas'         => [AdminController::class, 'ventas', 'personal'],
    'admin_servicios'      => [AdminController::class, 'servicios', 'personal'],
    'admin_sucursales'     => [AdminSistemaController::class, 'sucursales', 'admin'],
    'admin_usuarios'       => [AdminSistemaController::class, 'usuarios', 'admin'],
    'admin_reportes'       => [AdminSistemaController::class, 'reportes', 'personal'],
    'admin_notificaciones' => [AdminSistemaController::class, 'notificaciones', 'personal'],
    'admin_perfil'         => [AdminSistemaController::class, 'perfil', 'personal'],
    'admin_config'         => [AdminSistemaController::class, 'config', 'admin'],

    // Errores
    'error_403'            => [ErroresController::class, 'denegado', 'publico'],
    'error_404'            => [ErroresController::class, 'noEncontrado', 'publico'],
];
