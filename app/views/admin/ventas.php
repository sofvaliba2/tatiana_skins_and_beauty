<?php
$titulo = 'Ventas';
$activo = 'admin_ventas';
require __DIR__ . '/../layout/admin_header.php';
?>

<div class="page-title">
    <div>
        <h1>Ventas</h1>
        <p class="muted">Compras de productos registradas.</p>
    </div>
    <span class="muted" style="font-size:.9rem"><?= date('d/m/Y') ?></span>
</div>

<div class="kpis" style="grid-template-columns:repeat(3,1fr)">
    <div class="kpi"><span class="ico"><?= icon('cart', 24) ?></span><div><small>Compras<?= ($fecha ?? '') !== '' ? ' del día' : ' totales' ?></small><strong><?= (int)($totales['compras'] ?? 0) ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('chart', 24) ?></span><div><small>Monto acumulado</small><strong><?= bs($totales['monto'] ?? 0) ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('box', 24) ?></span><div><small>En pantalla</small><strong><?= (int)($total ?? 0) ?></strong><small class="muted">registros</small></div></div>
</div>

<div class="split">
<section class="panel">
    <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
        <input type="hidden" name="v" value="admin_ventas">
        <input class="input" type="date" name="fecha" value="<?= e($fecha ?? '') ?>">
        <button class="btn btn-outline" type="submit">Filtrar por fecha</button>
        <?php if (($fecha ?? '') !== ''): ?><a class="btn btn-outline" href="<?= ruta('admin_ventas') ?>">Todas</a><?php endif; ?>
    </form>

    <?php if (empty($ventas)): ?>
        <?= vacio('No hay compras registradas todavía.', 'cart') ?>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>N° compra</th><th>Fecha</th><th>Cliente</th><th>Método</th><th>Ítems</th><th>Total</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($ventas as $v): ?>
            <tr>
                <td><b><?= e($v['nro_compra'] ?? '#' . $v['id_compra']) ?></b></td>
                <td><?= e(date('d/m/Y', strtotime($v['fecha_compra']))) ?></td>
                <td><div class="person"><span class="avatar"><?= e(mb_substr($v['cliente'] ?? '?', 0, 1)) ?></span><?= e($v['cliente'] ?? 'Cliente eliminado') ?></div></td>
                <td><?= e($v['nombre_metodoPago'] ?? 'Por definir') ?></td>
                <td><?= (int)$v['items'] ?></td>
                <td><b><?= bs($v['total_pagar']) ?></b></td>
                <td><div class="acts"><a class="ic-btn" href="<?= ruta('detalle_compra', ['id' => $v['id_compra']]) ?>" target="_blank" aria-label="Ver detalle"><?= icon('eye', 16) ?></a></div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= paginacion('admin_ventas', ['fecha' => $fecha ?? ''], $total, $pagina, 10) ?>
    <?php endif; ?>
</section>

<aside class="panel">
    <div class="panel-head"><h3><?= icon('plus', 18) ?> Registrar venta</h3></div>
    <p class="muted" style="font-size:.85rem;margin-bottom:1rem">Venta directa en mostrador: se descuenta del stock automáticamente.</p>
    <form method="post" action="<?= ruta('admin_ventas') ?>">
        <?= csrf() ?>
        <div class="field">
            <label>Cliente *</label>
            <select class="input" name="id_usuario" required>
                <option value="">Selecciona un cliente...</option>
                <?php foreach (($clientes ?? []) as $cl): ?>
                    <option value="<?= e($cl['id_usuario']) ?>"><?= e(trim($cl['nombre'] . ' ' . $cl['ap_paterno'])) ?> (<?= e($cl['id_usuario']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Producto *</label>
            <select class="input" name="id_producto" required>
                <option value="">Selecciona un producto...</option>
                <?php foreach (($productos ?? []) as $pr): ?>
                    <option value="<?= e($pr['id_producto']) ?>" <?= (int)$pr['cantidad'] === 0 ? 'disabled' : '' ?>>
                        <?= e($pr['nombre_producto']) ?> — <?= bs($pr['precio']) ?> (stock: <?= (int)$pr['cantidad'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Cantidad *</label><input class="input" type="number" name="cantidad" min="1" value="1" required></div>
        <div class="field">
            <label>Método de pago</label>
            <select class="input" name="id_metodoPago">
                <option value="">Por definir</option>
                <?php foreach (($metodos ?? []) as $m): ?>
                    <option value="<?= e($m['id_metodoPago']) ?>"><?= e($m['nombre_metodoPago']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Registrar venta</button>
    </form>
</aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
