<?php
$titulo = 'Avisos';
$activo = 'admin_notificaciones';
require __DIR__ . '/../layout/admin_header.php';
?>

<div class="page-title">
    <div>
        <h1>Avisos del sistema</h1>
        <p class="muted">Pagos por verificar, stock bajo y citas de hoy.</p>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <h3><?= icon('clock', 18) ?> Pagos por verificar</h3>
            <span class="badge-st <?= $pendientes ? 'st-pendiente' : 'st-activo' ?>"><?= count($pendientes) ?></span>
    </div>
    <p class="muted" style="font-size:.88rem">Reservas con anticipo enviado cuyo comprobante aún no confirma el personal.</p>
    <?php if (empty($pendientes)): ?>
        <?= vacio('No hay pagos pendientes. ¡Todo al día!', 'check') ?>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Reserva</th><th>Fecha</th><th>Hora</th><th>Cliente</th><th>Teléfono</th><th>Sucursal</th><th>Comprobante</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pendientes as $p): $comp = is_file(BASE_PATH . '/public/uploads/comprobantes/res_' . $p['id_reserva'] . '.jpg'); ?>
            <tr>
                <td><b>#<?= (int)$p['id_reserva'] ?></b></td>
                <td><?= e(date('d/m/Y', strtotime($p['fecha']))) ?></td>
                <td><?= e(substr((string)$p['hora'], 0, 5)) ?></td>
                <td><?= e($p['cliente']) ?></td>
                <td><?= e($p['telefono'] ?: '—') ?></td>
                <td><?= e($p['nombre_sucursal'] ?? '—') ?></td>
                <td>
                    <?php if ($comp): ?>
                        <a class="link" href="<?= BASE_URL ?>/uploads/comprobantes/res_<?= (int)$p['id_reserva'] ?>.jpg" target="_blank">Ver comprobante</a>
                    <?php else: ?>
                        <span class="muted">No adjunto</span>
                    <?php endif; ?>
                </td>
                <td><a class="btn btn-outline btn-sm" href="<?= ruta('admin_citas', ['ficha' => $p['id_reserva'], 'estado' => 'pendiente']) ?>">Gestionar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<div class="panel-grid">
    <section class="panel">
        <div class="panel-head">
            <h3><?= icon('box', 18) ?> Stock bajo (≤ 3)</h3>
            <span class="badge-st <?= $stockBajo ? 'st-pendiente' : 'st-activo' ?>"><?= count($stockBajo) ?></span>
        </div>
        <?php if (empty($stockBajo)): ?>
            <?= vacio('Todos los productos tienen stock suficiente.', 'box') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Producto</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($stockBajo as $p): ?>
                <tr><td><?= e($p['nombre_producto']) ?><br><small class="muted"><?= e($p['nombre_categoria'] ?? '') ?></small></td><td><span class="badge-st st-pendiente"><?= (int)$p['cantidad'] ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a class="btn btn-outline btn-sm" href="<?= ruta('admin_inventario') ?>" style="margin-top:.75rem">Reponer stock</a>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3><?= icon('calendar', 18) ?> Citas de hoy</h3>
            <span class="badge-st st-activo"><?= count($citasHoy) ?></span>
        </div>
        <?php if (empty($citasHoy)): ?>
            <?= vacio('No hay citas agendadas para hoy.', 'calendar') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Hora</th><th>Cliente</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($citasHoy as $c): ?>
                <tr>
                    <td><b><?= e(substr((string)$c['hora'], 0, 5)) ?></b></td>
                    <td><?= e($c['cliente']) ?></td>
                    <td><?= estado((string)$c['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a class="btn btn-outline btn-sm" href="<?= ruta('admin_citas', ['fecha' => date('Y-m-d')]) ?>" style="margin-top:.75rem">Abrir agenda</a>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
