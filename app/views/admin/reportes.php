<?php
$titulo = 'Reportes';
$activo = 'admin_reportes';
require __DIR__ . '/../layout/admin_header.php';
$mes = $mes ?? '';
$anio = $anio ?? '';
$anios = $anios ?? [(int)date('Y')];
$meses = ['1' => 'Enero', '2' => 'Febrero', '3' => 'Marzo', '4' => 'Abril', '5' => 'Mayo', '6' => 'Junio', '7' => 'Julio', '8' => 'Agosto', '9' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
$hayFiltros = $mes !== '' || $anio !== '';

// Datos para los gráficos (con valores seguros si la vista se usa sin el controlador).
$serie = $serie ?? ['etiquetas' => [], 'compras' => [], 'montos' => [], 'reservas' => [], 'por_mes' => false];
$porMetodo = $porMetodo ?? [];
$maxMonto = max(array_merge([1.0], array_map('floatval', $serie['montos'] ?: [0])));
$maxReservas = max(array_merge([1], array_map('intval', $serie['reservas'] ?: [0])));
$totalMonto = array_sum($serie['montos']);
$totalReservas = array_sum($serie['reservas']);
$pasoEtiquetas = count($serie['etiquetas']) > 24 ? 5 : (count($serie['etiquetas']) > 12 ? 2 : 1);
$periodoTexto = !empty($serie['por_mes']) ? 'por mes' : 'por día';

$maxProducto = 0.0;
foreach ($porProducto as $p) {
    $maxProducto = max($maxProducto, (float)$p['total']);
}
$maxServicio = 0;
foreach ($porServicio as $s) {
    $maxServicio = max($maxServicio, (int)$s['citas']);
}

$colores = ['#54458e', '#a47cb8', '#cbb2d2', '#b8801f', '#8fb08a', '#6e6490'];
$totalMetodos = 0.0;
foreach ($porMetodo as $m) {
    $totalMetodos += (float)$m['total'];
}
$stops = [];
$leyenda = [];
$acum = 0.0;
foreach ($porMetodo as $i => $m) {
    $porc = $totalMetodos > 0 ? ((float)$m['total'] / $totalMetodos) * 100 : 0;
    $color = $colores[$i % count($colores)];
    $stops[] = $color . ' ' . round($acum, 2) . '% ' . round($acum + $porc, 2) . '%';
    $acum += $porc;
    $leyenda[] = [
        'nombre' => (string)$m['nombre'],
        'compras' => (int)$m['compras'],
        'total' => (float)$m['total'],
        'porc' => $porc,
        'color' => $color,
    ];
}
$donut = $stops ? 'conic-gradient(' . implode(', ', $stops) . ')' : 'conic-gradient(var(--lila-claro) 0 100%)';
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
    <div class="field" style="margin:0"><label>Mes</label>
        <select class="input" name="mes">
            <option value="">Todos los meses</option>
            <?php foreach ($meses as $num => $nombre): ?>
                <option value="<?= $num ?>" <?= $mes === (string)$num ? 'selected' : '' ?>><?= e($nombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field" style="margin:0"><label>Año</label>
        <select class="input" name="anio">
            <option value="">Todos los años</option>
            <?php foreach ($anios as $a): ?>
                <option value="<?= (int)$a ?>" <?= $anio === (string)$a ? 'selected' : '' ?>><?= (int)$a ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary" type="submit"><?= icon('filter', 16) ?> Aplicar</button>
    <?php if ($hayFiltros): ?>
        <a class="btn btn-outline" href="<?= ruta('admin_reportes', ['desde' => $desde, 'hasta' => $hasta]) ?>"><?= icon('trash', 14) ?> Limpiar</a>
    <?php endif; ?>
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
        <div class="panel-head">
            <h3>Ingresos de la tienda</h3>
            <span class="muted" style="font-size:.85rem"><?= e($periodoTexto) ?> · <?= bs($totalMonto) ?> en total</span>
        </div>
        <?php if ($totalMonto <= 0): ?>
            <?= vacio('Sin ventas en este rango.', 'chart') ?>
        <?php else: ?>
        <div class="chart-bars">
            <?php foreach ($serie['etiquetas'] as $i => $etq): ?>
                <div class="chart-col" title="<?= e($etq) ?>: <?= bs($serie['montos'][$i]) ?>">
                    <div class="chart-track">
                        <div class="chart-bar" style="height:<?= max(2, (int)round((float)$serie['montos'][$i] / $maxMonto * 100)) ?>%"></div>
                    </div>
                    <span class="chart-x"><?= $i % $pasoEtiquetas === 0 ? e($etq) : '' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="muted" style="font-size:.8rem;margin-top:.8rem">Cada barra representa el ingreso registrado en el período.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>Métodos de pago</h3>
            <span class="muted" style="font-size:.85rem"><?= count($porMetodo) ?> método(s)</span>
        </div>
        <?php if (empty($porMetodo) || $totalMetodos <= 0): ?>
            <?= vacio('Sin pagos registrados en este rango.', 'chart') ?>
        <?php else: ?>
        <div class="donut-wrap">
            <div class="donut" style="background:<?= e($donut) ?>"></div>
            <div class="donut-legend">
                <?php foreach ($leyenda as $l): ?>
                    <div class="legend-item">
                        <span class="dot" style="background:<?= e($l['color']) ?>"></span>
                        <span><?= e($l['nombre']) ?> <small class="muted">(<?= (int)$l['compras'] ?>)</small></span>
                        <span class="legend-monto"><?= bs($l['total']) ?> · <?= (int)round($l['porc']) ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
</div>

<div class="panel-grid" style="grid-template-columns:1.5fr 1fr">
    <section class="panel">
        <div class="panel-head">
            <h3>Reservas</h3>
            <span class="muted" style="font-size:.85rem"><?= e($periodoTexto) ?> · <?= (int)$totalReservas ?> en total</span>
        </div>
        <?php if ($totalReservas <= 0): ?>
            <?= vacio('Sin reservas en este rango.', 'calendar') ?>
        <?php else: ?>
        <div class="chart-bars">
            <?php foreach ($serie['etiquetas'] as $i => $etq): ?>
                <div class="chart-col" title="<?= e($etq) ?>: <?= (int)$serie['reservas'][$i] ?> reserva(s)">
                    <div class="chart-track">
                        <div class="chart-bar reserva" style="height:<?= max(2, (int)round((int)$serie['reservas'][$i] / $maxReservas * 100)) ?>%"></div>
                    </div>
                    <span class="chart-x"><?= $i % $pasoEtiquetas === 0 ? e($etq) : '' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="muted" style="font-size:.8rem;margin-top:.8rem">No se cuentan las reservas canceladas.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Servicios más reservados</h3></div>
        <?php if (empty($porServicio)): ?>
            <?= vacio('Sin reservas en este rango.', 'sparkle') ?>
        <?php else: ?>
        <div class="hbars">
            <?php foreach ($porServicio as $s): ?>
                <div class="hbar">
                    <div class="hbar-top">
                        <span><?= e($s['nombre']) ?></span>
                        <b><?= (int)$s['citas'] ?> citas · <?= bs($s['total']) ?></b>
                    </div>
                    <div class="hbar-track"><i style="width:<?= $maxServicio > 0 ? max(3, (int)round((int)$s['citas'] / $maxServicio * 100)) : 0 ?>%"></i></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<section class="panel" style="margin-bottom:1.4rem">
    <div class="panel-head"><h3>Productos más vendidos</h3><span class="muted" style="font-size:.85rem">Por ingreso</span></div>
    <?php if (empty($porProducto)): ?>
        <?= vacio('Sin ventas en este rango.', 'box') ?>
    <?php else: ?>
    <div class="hbars">
        <?php foreach ($porProducto as $p): ?>
            <div class="hbar">
                <div class="hbar-top">
                    <span><?= e($p['nombre_producto']) ?></span>
                    <b><?= (int)$p['unidades'] ?> u. · <?= bs($p['total']) ?></b>
                </div>
                <div class="hbar-track"><i style="width:<?= $maxProducto > 0 ? max(3, (int)round((float)$p['total'] / $maxProducto * 100)) : 0 ?>%"></i></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

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
