<?php
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AdminSistemaController;
use App\Controllers\AuthController;
use App\Controllers\CatalogoController;
use App\Controllers\ClienteController;
use App\Controllers\EmpleadoController;
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
    'detalle_compra'       => [ClienteController::class, 'detalleCompra', 'cliente_personal'],

    // Reservas
    'reserva_nueva'        => [ReservaController::class, 'nueva', 'cliente'],
    'reserva_pago'         => [ReservaController::class, 'pago', 'cliente'],
    'reserva_formulario'   => [ReservaController::class, 'formulario', 'cliente'],
    'reserva_seguimiento'  => [ReservaController::class, 'seguimiento', 'cliente'],
    'reserva_recibo'       => [ReservaController::class, 'recibo', 'cliente'],
    'mis_reservas'         => [ReservaController::class, 'misReservas', 'cliente'],
    'recomendaciones'      => [ReservaController::class, 'recomendaciones', 'cliente'],

    // Administración (solo ROL01)
    'admin_dashboard'      => [AdminController::class, 'dashboard', 'admin'],
    'admin_clientes'       => [AdminController::class, 'clientes', 'admin'],
    'admin_citas'          => [AdminController::class, 'citas', 'admin'],
    'admin_inventario'     => [AdminController::class, 'inventario', 'admin'],
    'admin_ventas'         => [AdminController::class, 'ventas', 'admin'],
    'admin_servicios'      => [AdminController::class, 'servicios', 'admin'],
    'admin_sucursales'     => [AdminSistemaController::class, 'sucursales', 'admin'],
    'admin_usuarios'       => [AdminSistemaController::class, 'usuarios', 'admin'],
    'admin_reportes'       => [AdminSistemaController::class, 'reportes', 'admin'],
    'admin_notificaciones' => [AdminSistemaController::class, 'notificaciones', 'admin'],
    'admin_perfil'         => [AdminSistemaController::class, 'perfil', 'admin'],
    'admin_config'         => [AdminSistemaController::class, 'config', 'admin'],

    // Panel del empleado (solo ROL02)
    'empleado_dashboard'      => [EmpleadoController::class, 'dashboard', 'empleado'],
    'empleado_clientes'       => [EmpleadoController::class, 'clientes', 'empleado'],
    'empleado_citas'          => [EmpleadoController::class, 'citas', 'empleado'],
    'empleado_disponibilidad' => [EmpleadoController::class, 'disponibilidad', 'empleado'],
    'empleado_historial'      => [EmpleadoController::class, 'historial', 'empleado'],
    'empleado_ventas'         => [EmpleadoController::class, 'ventas', 'empleado'],
    'empleado_comprobante'    => [EmpleadoController::class, 'comprobante', 'empleado'],
    'empleado_perfil'         => [EmpleadoController::class, 'perfil', 'empleado'],

    // Errores
    'error_403'            => [ErroresController::class, 'denegado', 'publico'],
    'error_404'            => [ErroresController::class, 'noEncontrado', 'publico'],
];
