<?php
/**
 * Cabecera pública (cliente).
 * Variables: $titulo (string), $activo (string)
 */
require_once __DIR__ . '/helpers.php';
$titulo = $titulo ?? 'Tatiana Skin and Beauty';
$activo = $activo ?? '';
$u = \App\Core\Auth::usuario();
$carritoCount = count($_SESSION['carrito'] ?? []);
$pendientes = 0;
if ($u !== null) {
    try {
        $pendientes = (int)\App\Core\Database::value(
            "SELECT COUNT(*) FROM reserva WHERE id_usuario = ? AND estado = 'pendiente'",
            [$u['id']]
        );
    } catch (\Throwable $e) {
        $pendientes = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Tatiana Skin and Beauty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Italiana&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<header class="nav">
    <div class="container nav-inner">
        <?= logo() ?>
        <nav class="nav-links" id="menu">
            <a href="<?= ruta('home') ?>"     class="<?= $activo === 'home' ? 'active' : '' ?>">Inicio</a>
            <a href="<?= ruta('servicios') ?>" class="<?= $activo === 'servicios' ? 'active' : '' ?>">Servicios</a>
            <a href="<?= ruta('catalogo') ?>"  class="<?= $activo === 'catalogo' ? 'active' : '' ?>">Productos</a>
            <a href="<?= ruta('ayuda') ?>" class="<?= $activo === 'ayuda' ? 'active' : '' ?>">Ayuda</a>
            <?php if ($u !== null): ?>
                <a href="<?= ruta('mis_reservas') ?>" class="<?= $activo === 'mis_reservas' ? 'active' : '' ?>">Mis reservas</a>
                <a href="<?= ruta('panel') ?>" class="<?= $activo === 'panel' ? 'active' : '' ?>">Mi cuenta</a>
            <?php endif; ?>
        </nav>
        <div class="nav-actions">
            <a href="<?= ruta('carrito') ?>" class="cart-link" aria-label="Carrito">
                <?= icon('cart', 20) ?>
                <?php if ($carritoCount > 0): ?><span class="badge"><?= $carritoCount ?></span><?php endif; ?>
            </a>
            <?php if ($u !== null): ?>
                <?php if ($pendientes > 0): ?>
                    <a href="<?= ruta('mis_reservas') ?>" class="btn btn-outline btn-sm"><?= icon('clock', 15) ?> <?= $pendientes ?> pendiente<?= $pendientes > 1 ? 's' : '' ?></a>
                <?php endif; ?>
                <form method="post" action="<?= ruta('logout') ?>" style="display:inline">
                    <?= csrf() ?>
                    <button class="btn btn-outline btn-sm" type="submit" title="Cerrar sesión de <?= e($u['nombre']) ?>">Salir</button>
                </form>
            <?php else: ?>
                <a href="<?= ruta('login') ?>" class="btn btn-outline btn-sm">Ingresar</a>
            <?php endif; ?>
            <a href="<?= $u !== null ? ruta($u['id_rol'] === 'ROL03' ? 'reserva_nueva' : 'admin_dashboard') : ruta('reserva_nueva') ?>" class="btn btn-primary btn-sm">
                <?= $u !== null && $u['id_rol'] !== 'ROL03' ? 'Panel' : 'Reservar cita' ?>
            </a>
            <button class="burger" aria-label="Menú" onclick="document.getElementById('menu').classList.toggle('open')"><?= icon('menu', 26) ?></button>
        </div>
    </div>
</header>
<div class="container"><?= flashes() ?></div>
