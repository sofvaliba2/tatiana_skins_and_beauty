<?php
$titulo = 'Inicio';
$activo = 'empleado_dashboard';
require __DIR__ . '/../layout/empleado_header.php';
$nombre = (string)($usuario_actual['nombre'] ?? \App\Core\Auth::usuario()['nombre'] ?? 'equipo');
$sucursalNombre = (string)($sucursal_actual['nombre_sucursal'] ?? 'tu sucursal');
?>

<div class="welcome">
    <div>
        <h1>Hola, <span class="script"><?= e($nombre) ?></span></h1>
        <p class="muted">
            Estás trabajando en <b><?= e($sucursalNombre) ?></b>.
            Hoy es <?= e(date('d/m/Y', strtotime($hoy ?? date('Y-m-d')))) ?>.
        </p>
    </div>
    <span class="script" style="font-size:2rem;text-align:right;line-height:1">“La mejor versión<br>de tu piel”</span>
</div>

<div class="kpis">
    <div class="kpi">
        <span class="ico"><?= icon('calendar', 24) ?></span>
        <div><small>Citas de hoy</small><strong><?= (int)($totalCitasHoy ?? 0) ?></strong><small class="muted">sin cancelar</small></div>
    </div>
    <div class="kpi">
        <span class="ico"><?= icon('clock', 24) ?></span>
        <div><small>Pendientes</small><strong><?= (int)($pendientes ?? 0) ?></strong><small class="muted">por confirmar</small></div>
    </div>
    <div class="kpi">
        <span class="ico"><?= icon('sparkle', 24) ?></span>
        <div><small>Próximas citas</small><strong><?= (int)($totalProximas ?? 0) ?></strong><small class="muted">desde hoy</small></div>
    </div>
    <div class="kpi">
        <span class="ico"><?= icon('users', 24) ?></span>
        <div><small>Clientas</small><strong><?= (int)($totalClientes ?? 0) ?></strong><small class="muted">de la sucursal</small></div>
    </div>
</div>

<div class="panel-grid">
    <section class="panel">
        <div class="panel-head">
            <h3>Citas de hoy</h3>
            <a class="link" href="<?= ruta('empleado_citas', ['fecha' => $hoy ?? date('Y-m-d')]) ?>">Ver agenda →</a>
        </div>
        <?php if (empty($citasHoy)): ?>
            <?= vacio('No hay citas programadas para hoy.', 'calendar') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Hora</th><th>Clienta</th><th>Servicio</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($citasHoy as $c): ?>
                <tr>
                    <td><b><?= e(substr((string)$c['hora'], 0, 5)) ?></b></td>
                    <td>
                        <div class="person">
                            <span class="avatar"><?= e(mb_substr((string)$c['cliente'], 0, 1)) ?></span>
                            <?= e($c['cliente']) ?>
                        </div>
                    </td>
                    <td><?= e($c['servicio']) ?></td>
                    <td><?= estado((string)$c['estado']) ?></td>
                    <td>
                        <div class="acts">
                            <a class="ic-btn" href="<?= ruta('empleado_citas', ['ficha' => $c['id_reserva'], 'fecha' => $hoy ?? '']) ?>" title="Ver ficha"><?= icon('eye', 16) ?></a>
                            <?php if ($c['estado'] === 'pendiente'): ?>
                            <form method="post" action="<?= ruta('empleado_citas') ?>" style="display:inline" data-confirm="¿Confirmar la cita #<?= (int)$c['id_reserva'] ?>?">
                                <?= csrf() ?>
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                                <input type="hidden" name="nuevo_estado" value="confirmada">
                                <input type="hidden" name="fecha" value="<?= e($hoy ?? '') ?>">
                                <button class="ic-btn" type="submit" title="Confirmar"><?= icon('check', 16) ?></button>
                            </form>
                            <?php endif; ?>
                            <?php if (in_array($c['estado'], ['pendiente', 'confirmada'], true)): ?>
                            <form method="post" action="<?= ruta('empleado_citas') ?>" style="display:inline" data-confirm="¿Marcar la cita #<?= (int)$c['id_reserva'] ?> como atendida?">
                                <?= csrf() ?>
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                                <input type="hidden" name="nuevo_estado" value="atendida">
                                <input type="hidden" name="fecha" value="<?= e($hoy ?? '') ?>">
                                <button class="ic-btn" type="submit" title="Marcar atendida"><?= icon('clock', 16) ?></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h3>Próximas citas</h3>
            <a class="link" href="<?= ruta('empleado_citas') ?>">Ver todas →</a>
        </div>
        <?php if (empty($proximas)): ?>
            <?= vacio('No hay próximas citas registradas.', 'sparkle') ?>
        <?php else: ?>
            <?php foreach ($proximas as $p): ?>
                <div class="agenda-mini">
                    <div class="agenda-date">
                        <b><?= e(date('d', strtotime((string)$p['fecha']))) ?></b>
                        <small><?= e(strtoupper(date('M', strtotime((string)$p['fecha'])))) ?></small>
                    </div>
                    <div style="min-width:0">
                        <b><?= e(substr((string)$p['hora'], 0, 5)) ?> · <?= e($p['cliente']) ?></b>
                        <small class="muted" style="display:block"><?= e($p['servicios'] ?? 'Sin servicio') ?></small>
                    </div>
                    <div style="margin-left:auto"><?= estado((string)$p['estado']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h3>Acciones rápidas</h3></div>
    <div class="quick">
        <a href="<?= ruta('empleado_citas') ?>"><?= icon('calendar', 26) ?><br>Nueva cita</a>
        <a href="<?= ruta('empleado_clientes') ?>"><?= icon('users', 26) ?><br>Buscar clienta</a>
        <a href="<?= ruta('empleado_historial') ?>"><?= icon('history', 26) ?><br>Historial de piel</a>
        <a href="<?= ruta('empleado_ventas') ?>"><?= icon('cart', 26) ?><br>Registrar venta</a>
    </div>
</section>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
