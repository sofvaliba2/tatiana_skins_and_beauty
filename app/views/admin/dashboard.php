<?php
$titulo = 'Inicio';
$activo = 'admin_dashboard';
require __DIR__ . '/../layout/admin_header.php';
$max = max(array_merge([1], $ventas_semana ?? []));
$u = \App\Core\Auth::usuario();
?>

<div class="welcome">
    <div>
        <h1>Hola, <span class="script"><?= e($u['nombre'] ?? 'equipo') ?></span></h1>
        <p class="muted">Bienvenida a Tatiana Skin and Beauty. Tu espacio de trabajo para gestionar el cuidado de la piel.</p>
    </div>
    <span class="script" style="font-size:2rem;text-align:right;line-height:1">“La mejor versión<br>de tu piel”</span>
</div>

<div class="kpis">
    <div class="kpi"><span class="ico"><?= icon('user', 24) ?></span><div><small>Clientes</small><strong><?= (int)($kpi['clientes'] ?? 0) ?></strong><small class="muted">registradas</small></div></div>
    <div class="kpi"><span class="ico"><?= icon('calendar', 24) ?></span><div><small>Citas de hoy</small><strong><?= (int)($kpi['citas'] ?? 0) ?></strong><small class="muted">sin cancelar</small></div></div>
    <div class="kpi"><span class="ico"><?= icon('cart', 24) ?></span><div><small>Ventas del día</small><strong><?= bs($kpi['ventas'] ?? 0) ?></strong><small class="muted"><?= date('d/m/Y') ?></small></div></div>
    <div class="kpi"><span class="ico"><?= icon('box', 24) ?></span><div><small>Stock bajo</small><strong><?= (int)($kpi['stock_bajo'] ?? 0) ?></strong><small style="color:var(--warn,#c47f00)">productos ≤ 3</small></div></div>
</div>

<div class="panel-grid">
    <section class="panel">
        <div class="panel-head">
            <h3>Citas de hoy</h3>
            <a class="link" href="<?= ruta('admin_citas', ['fecha' => date('Y-m-d')]) ?>">Ver todas →</a>
        </div>
        <?php if (empty($citas_hoy)): ?>
            <?= vacio('No hay citas para hoy.', 'calendar') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Hora</th><th>Cliente</th><th>Servicio</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($citas_hoy as $c): ?>
                <tr>
                    <td><b><?= e(substr((string)$c['hora'], 0, 5)) ?></b></td>
                    <td><div class="person"><span class="avatar"><?= e(mb_substr($c['cliente'], 0, 1)) ?></span><?= e($c['cliente']) ?></div></td>
                    <td><?= e($c['servicio']) ?></td>
                    <td><?= estado((string)$c['estado']) ?></td>
                    <td><div class="acts">
                        <a class="ic-btn" href="<?= ruta('admin_citas', ['ficha' => $c['id_reserva']]) ?>" aria-label="Ver ficha"><?= icon('eye', 16) ?></a>
                        <?php if ($c['estado'] === 'pendiente'): ?>
                        <form method="post" action="<?= ruta('admin_citas') ?>" style="display:inline">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="confirmada">
                            <button class="ic-btn" type="submit" title="Confirmar anticipo"><?= icon('check', 16) ?></button>
                        </form>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>Resumen de ventas</h3><span class="muted" style="font-size:.85rem">Últimos 7 días</span></div>
        <div class="bars" style="margin-bottom:2rem">
            <?php foreach (($ventas_semana ?? []) as $i => $v): ?>
                <div class="bar" style="height:<?= max(6, round($v / $max * 100)) ?>%" title="<?= bs($v) ?>"><span><?= e(($dias ?? [])[$i] ?? '') ?></span></div>
            <?php endforeach; ?>
        </div>
        <div class="summary-row"><span>Ventas de hoy</span><b><?= bs($kpi['ventas'] ?? 0) ?></b></div>
        <div class="summary-row"><span>Últimos 7 días</span><b><?= bs(array_sum($ventas_semana ?? [])) ?></b></div>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h3>Acciones rápidas</h3></div>
    <div class="quick">
        <a href="<?= ruta('admin_clientes') ?>"><?= icon('user', 26) ?><br>Registrar cliente</a>
        <a href="<?= ruta('admin_citas') ?>"><?= icon('calendar', 26) ?><br>Gestionar citas</a>
        <a href="<?= ruta('admin_ventas') ?>"><?= icon('cart', 26) ?><br>Registrar venta</a>
        <a href="<?= ruta('admin_inventario') ?>"><?= icon('box', 26) ?><br>Agregar producto</a>
    </div>
</section>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
