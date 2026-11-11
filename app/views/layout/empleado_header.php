<?php
/**
 * Cabecera del panel del empleado (ROL02).
 * Variables: $titulo (string), $activo (string),
 *            $sucursal_actual (array|null), $usuario_actual (array|null)
 */
require_once __DIR__ . '/helpers.php';
$titulo = $titulo ?? 'Panel';
$activo = $activo ?? '';
$u = \App\Core\Auth::usuario();
$sucursal_actual = $sucursal_actual ?? null;
$usuario_actual = $usuario_actual ?? null;
$nombreSucursal = (string)($sucursal_actual['nombre_sucursal'] ?? ($u['sucursal_nombre'] ?? 'Sin sucursal'));
$avisosPendientes = (int)($avisos_admin ?? 0);
$nombre = (string)($usuario_actual['nombre'] ?? $u['nombre'] ?? 'Equipo');
$apellidos = trim((string)($usuario_actual['ap_paterno'] ?? '') . ' ' . (string)($usuario_actual['ap_materno'] ?? ''));
$iniciales = mb_strtoupper(
    mb_substr((string)($usuario_actual['nombre'] ?? $u['nombre'] ?? '?'), 0, 1) .
    mb_substr((string)($usuario_actual['ap_paterno'] ?? ''), 0, 1)
);

function emp_item_menu(string $vista, string $icono, string $texto, string $activo, string $extra = ''): string
{
    $c = $activo === $vista ? 'active' : '';
    return '<a class="item ' . $c . '" href="' . ruta($vista) . '">' . icon($icono, 20) .
        '<span>' . e($texto) . $extra . '</span></a>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Panel Tatiana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Italiana&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="admin">
    <aside class="side">
        <?= logo(true) ?>

        <div class="branch-chip">
            <?= icon('pin', 16) ?>
            <span><small>Sucursal</small><b><?= e($nombreSucursal) ?></b></span>
        </div>

        <?= emp_item_menu('empleado_dashboard', 'home', 'Inicio', $activo) ?>

        <div class="grp">Atención</div>
        <?= emp_item_menu('empleado_clientes', 'users', 'Clientes', $activo) ?>
        <?= emp_item_menu('empleado_citas', 'calendar', 'Citas y reservas', $activo, $avisosPendientes > 0 ? ' (' . $avisosPendientes . ')' : '') ?>
        <?= emp_item_menu('empleado_historial', 'history', 'Historial de piel', $activo) ?>

        <div class="grp">Comercial</div>
        <?= emp_item_menu('empleado_ventas', 'cart', 'Ventas y pagos', $activo) ?>

        <div class="grp">Cuenta</div>
        <?= emp_item_menu('empleado_perfil', 'user', 'Mi perfil', $activo) ?>
        <form method="post" action="<?= ruta('logout') ?>" class="side-logout">
            <?= csrf() ?>
            <button class="item" type="submit"><?= icon('logout', 20) ?><span>Cerrar sesión</span></button>
        </form>

        <div class="quote">Cuidado · Confianza · Belleza real</div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="search">
                <?= icon('search', 18) ?>
                <span class="input" style="cursor:default;color:var(--morado)"><?= e($titulo) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:1rem">
                <?php if ($avisosPendientes > 0): ?>
                    <a href="<?= ruta('empleado_citas', ['estado' => 'pendiente']) ?>" class="ic-btn" title="<?= $avisosPendientes ?> cita(s) pendiente(s)" style="position:relative">
                        <?= icon('bell', 22) ?>
                        <span class="badge" style="position:absolute;top:-6px;right:-8px;background:var(--warn,#c47f00);color:#fff"><?= $avisosPendientes ?></span>
                    </a>
                <?php else: ?>
                    <span style="color:var(--morado)" title="Sin avisos pendientes"><?= icon('bell', 22) ?></span>
                <?php endif; ?>
                <div class="user-pill">
                    <span class="avatar"><?= e($iniciales ?: '?') ?></span>
                    <div>
                        <b>Hola, <?= e($nombre) ?></b>
                        <small><?= e($nombreSucursal) ?></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="content">
            <?= flashes() ?>
