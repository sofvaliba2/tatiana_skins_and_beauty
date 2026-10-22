<?php
$titulo = 'Reportes';
$activo = 'admin_reportes';
require __DIR__ . '/../layout/admin_header.php';
?>

<div class="page-title">
    <div>
        <h1>Reportes</h1>
        <p class="muted">Resumen de ventas, servicios y clientes por rango de fechas.</p>
    </div>
</div>

<form class="filters panel" method="get" action="<?= BASE_URL ?>/index.php" style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1.5rem">
    <input type="hidden" name="v" value="admin_reportes">
    <div class="field" style="margin:0"><label>Desde</label><input class="input" type="date" name="desde" value="<?= e($desde) ?>"></div>
    <div class="field" style="margin:0"><label>Hasta</label><input class="input" type="date" name="hasta" value="<?= e($hasta) ?>"></div>
    <button class="btn btn-primary" type="submit"><?= icon('filter', 16) ?> Aplicar</button>
    <span style="flex:1"></span>
    <a class="btn btn-outline" href="<?= ruta('admin_reportes', ['desde' => $desde, 'hasta' => $hasta, 'export' => 'compras']) ?>"><?= icon('download', 16) ?> CSV compras</a>
    <a class="btn btn-outline" href="<?= ruta('admin_reportes', ['desde' => $desde, 'hasta' => $hasta, 'export' => 'productos']) ?>"><?= icon('download', 16) ?> CSV productos</a>
    <a class="btn btn-outline" href="<?= ruta('admin_reportes', ['desde' => $desde, 'hasta' => $hasta, 'export' => 'servicios']) ?>"><?= icon('download', 16) ?> CSV servicios</a>
</form>

<div class="kpis" style="grid-template-columns:repeat(4,1fr)">
    <div class="kpi"><span class="ico"><?= icon('cart', 24) ?></span><div><small>Compras</small><strong><?= (int)($resumen['compras'] ?? 0) ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('chart', 24) ?></span><div><small>Ingresos tienda</small><strong><?= bs($resumen['monto'] ?? 0) ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('calendar', 24) ?></span><div><small>Reservas</small><strong><?= (int)($reservas['total'] ?? 0) ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('check', 24) ?></span><div><small>Atendidas</small><strong><?= (int)($reservas['atendidas'] ?? 0) ?></strong><small class="muted"><?= (int)($reservas['canceladas'] ?? 0) ?> canceladas</small></div></div>
</div>

<div class="panel-grid">
    <section class="panel">
        <div class="panel-head"><h3>Productos más vendidos</h3></div>
        <?php if (empty($porProducto)): ?>
            <?= vacio('Sin ventas en este rango.', 'box') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($porProducto as $p): ?>
                <tr><td><?= e($p['nombre_producto']) ?></td><td><?= (int)$p['unidades'] ?></td><td><b><?= bs($p['total']) ?></b></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Servicios más reservados</h3></div>
        <?php if (empty($porServicio)): ?>
            <?= vacio('Sin reservas en este rango.', 'sparkle') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Servicio</th><th>Citas</th><th>Ingreso</th></tr></thead>
            <tbody>
            <?php foreach ($porServicio as $s): ?>
                <tr><td><?= e($s['nombre']) ?></td><td><?= (int)$s['citas'] ?></td><td><b><?= bs($s['total']) ?></b></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h3>Clientes destacados</h3><span class="muted" style="font-size:.85rem"><?= e(date('d/m/Y', strtotime($desde))) ?> — <?= e(date('d/m/Y', strtotime($hasta))) ?></span></div>
    <?php if (empty($topClientes)): ?>
        <?= vacio('Sin actividad de clientes en este rango.', 'users') ?>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Cliente</th><th>Compras</th><th>Reservas</th><th>Total gastado</th></tr></thead>
        <tbody>
        <?php foreach ($topClientes as $t): ?>
            <tr><td><?= e($t['cliente']) ?></td><td><?= (int)$t['compras'] ?></td><td><?= (int)$t['reservas'] ?></td><td><b><?= bs($t['gastado']) ?></b></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
