<?php
$titulo = 'Mi panel';
$activo = 'panel';
$carrito = count($_SESSION['carrito'] ?? []);
$u = \App\Core\Auth::usuario();
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container">
        <div class="section-head" style="text-align:left">
            <span class="eyebrow">Hola, <?= e($u['nombre']) ?></span>
            <h2>Mi panel</h2>
        </div>

        <div class="grid-3" style="margin-bottom:2.5rem">
            <article class="card" style="padding:1.5rem;text-align:center">
                <div style="color:var(--morado)"><?= icon('calendar', 30) ?></div>
                <div style="font-size:2rem;font-weight:500"><?= (int)($totales['reservas'] ?? 0) ?></div>
                <p class="muted">Reservas hechas</p>
            </article>
            <article class="card" style="padding:1.5rem;text-align:center">
                <div style="color:var(--morado)"><?= icon('cart', 30) ?></div>
                <div style="font-size:2rem;font-weight:500"><?= (int)($totales['compras'] ?? 0) ?></div>
                <p class="muted">Compras realizadas</p>
            </article>
            <article class="card" style="padding:1.5rem;text-align:center">
                <div style="color:var(--morado)"><?= icon('heart', 30) ?></div>
                <div style="font-size:2rem;font-weight:500"><?= (int)($historial['visitas'] ?? 0) ?></div>
                <p class="muted">Sesiones en tu historial</p>
            </article>
        </div>

        <div class="section-head" style="text-align:left">
            <h2 style="font-size:1.4rem">Próximas citas</h2>
        </div>
        <?php if (empty($proximas)): ?>
            <?= vacio('No tienes citas próximas. ¡Reserva una y cuídate!', 'calendar') ?>
            <div style="text-align:center;margin-top:1rem"><a href="<?= ruta('reserva_nueva') ?>" class="btn btn-primary">Reservar mi cita</a></div>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Fecha</th><th>Hora</th><th>Servicio</th><th>Sucursal</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($proximas as $r): ?>
                    <tr>
                        <td><?= e(date('d/m/Y', strtotime($r['fecha']))) ?></td>
                        <td><?= e(substr((string)$r['hora'], 0, 5)) ?></td>
                        <td><?= e($r['servicios'] ?? '—') ?></td>
                        <td><?= e($r['nombre_sucursal'] ?? '—') ?></td>
                        <td><?= bs($r['total']) ?></td>
                        <td><?= estado((string)$r['estado']) ?></td>
                        <td><a class="btn btn-outline btn-sm" href="<?= ruta('reserva_recibo', ['id' => $r['id_reserva']]) ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($totales) && (float)($totales['gastado'] ?? 0) > 0): ?>
            <p class="muted" style="margin-top:1.5rem">Total gastado en la tienda: <b><?= bs($totales['gastado']) ?></b></p>
        <?php endif; ?>

        <?php if (!empty($compras)): ?>
        <div class="section-head" style="text-align:left;margin-top:2.5rem">
            <h2 style="font-size:1.4rem">Últimas compras</h2>
        </div>
        <table class="table">
            <thead><tr><th>Fecha</th><th>N° de compra</th><th>Total</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($compras as $c): ?>
                <tr>
                    <td><?= e(date('d/m/Y', strtotime($c['fecha_compra']))) ?></td>
                    <td><?= e($c['nro_compra'] ?? '#' . $c['id_compra']) ?></td>
                    <td><?= bs($c['total_pagar']) ?></td>
                    <td><a class="btn btn-outline btn-sm" href="<?= ruta('detalle_compra', ['id' => $c['id_compra']]) ?>">Detalle</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:2.5rem">
            <a href="<?= ruta('mis_reservas') ?>" class="btn btn-outline"><?= icon('calendar', 18) ?> Mis reservas</a>
            <a href="<?= ruta('mis_compras') ?>" class="btn btn-outline"><?= icon('cart', 18) ?> Mis compras</a>
            <a href="<?= ruta('recomendaciones') ?>" class="btn btn-outline"><?= icon('sparkle', 18) ?> Recomendaciones</a>
            <a href="<?= ruta('perfil') ?>" class="btn btn-outline"><?= icon('user', 18) ?> Editar perfil</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
