<?php
/**
 * Cabecera del panel interno (administrador / personal).
 * Variables: $titulo (string), $activo (string)
 */
require_once __DIR__ . '/helpers.php';
$titulo = $titulo ?? 'Panel';
$activo = $activo ?? '';
$u = \App\Core\Auth::usuario();
$esAdmin = ($u['id_rol'] ?? '') === 'ROL01';

function item_menu(string $vista, string $icono, string $texto, string $activo): string
{
    $c = $activo === $vista ? 'active' : '';
    return '<a class="item ' . $c . '" href="' . ruta($vista) . '">' . icon($icono, 20) . '<span>' . e($texto) . '</span></a>';
}

$avisosPendientes = 0;
try {
    $avisosPendientes = (int)\App\Core\Database::value("SELECT COUNT(*) FROM reserva WHERE estado = 'pendiente'");
} catch (\Throwable $e) {
    $avisosPendientes = 0;
}
$iniciales = mb_strtoupper(mb_substr($u['nombre'] ?? '?', 0, 1) . mb_substr($u['apellidos'] ?? '', 0, 1));
$rolTexto = ($u['id_rol'] ?? '') === 'ROL01' ? 'Administrador' : 'Personal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> Â· Panel Tatiana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Italiana&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="admin">
    <aside class="side">
        <?= logo(true) ?>
        <?= item_menu('admin_dashboard', 'home', 'Inicio', $activo) ?>

        <div class="grp">GestiÃ³n</div>
        <?= item_menu('admin_clientes', 'users', 'Clientes', $activo) ?>
        <?= item_menu('admin_citas', 'calendar', 'Citas', $activo) ?>
        <?= item_menu('admin_servicios', 'sparkle', 'Servicios', $activo) ?>

        <div class="grp">Comercial</div>
        <?= item_menu('admin_ventas', 'cart', 'Ventas', $activo) ?>
        <?= item_menu('admin_inventario', 'box', 'Inventario', $activo) ?>

        <div class="grp">Informes</div>
        <?= item_menu('admin_reportes', 'chart', 'Reportes', $activo) ?>
        <?= item_menu('admin_notificaciones', 'bell', 'Avisos' . ($avisosPendientes > 0 ? ' (' . $avisosPendientes . ')' : ''), $activo) ?>

        <?php if ($esAdmin): ?>
        <div class="grp">Sistema</div>
        <?= item_menu('admin_usuarios', 'shield', 'Usuarios', $activo) ?>
        <?= item_menu('admin_sucursales', 'pin', 'Sucursales', $activo) ?>
        <?= item_menu('admin_config', 'settings', 'ConfiguraciÃ³n', $activo) ?>
        <?php endif; ?>

        <div class="grp">Cuenta</div>
        <?= item_menu('admin_perfil', 'user', 'Mi perfil', $activo) ?>
        <?= item_menu('home', 'eye', 'Ver sitio', '') ?>

        <div class="quote">Cuidado Â· Confianza Â· Belleza real</div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="search">
                <?= icon('search', 18) ?>
                <span class="input" style="cursor:default;color:var(--morado)"><?= e($titulo) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:1rem">
                <?php if ($avisosPendientes > 0): ?>
                    <a href="<?= ruta('admin_notificaciones') ?>" class="ic-btn" title="<?= $avisosPendientes ?> pago(s) por verificar" style="position:relative">
                        <?= icon('bell', 22) ?>
                        <span class="badge" style="position:absolute;top:-6px;right:-8px;background:var(--warn,#c47f00);color:#fff"><?= $avisosPendientes ?></span>
                    </a>
                <?php else: ?>
                    <span style="color:var(--morado)" title="Sin avisos pendientes"><?= icon('bell', 22) ?></span>
                <?php endif; ?>
                <div class="user-pill">
                    <span class="avatar"><?= e($iniciales) ?></span>
                    <div><b>Hola, <?= e($u['nombre'] ?? 'Usuario') ?></b><small><?= $rolTexto ?></small></div>
                </div>
                <form method="post" action="<?= ruta('logout') ?>">
                    <?= csrf() ?>
                    <button class="btn btn-outline btn-sm" type="submit" title="Cerrar sesiÃ³n"><?= icon('logout', 16) ?> Salir</button>
                </form>
            </div>
        </div>
        <div class="content">
            <?= flashes() ?>

