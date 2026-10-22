<?php
$titulo = 'Configuración';
$activo = 'admin_config';
require __DIR__ . '/../layout/admin_header.php';
?>

<div class="page-title">
    <div>
        <h1>Configuración</h1>
        <p class="muted">Métodos de pago aceptados por el centro.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <div class="panel-head"><h3>Métodos de pago</h3></div>
        <?php if (empty($metodos)): ?>
            <?= vacio('Sin métodos de pago. Crea el primero en el formulario lateral.', 'settings') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Uso</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($metodos as $m): ?>
                <tr>
                    <td><?= e($m['id_metodoPago']) ?></td>
                    <td><b><?= e($m['nombre_metodoPago']) ?></b></td>
                    <td><?= e($m['tipo'] ?? 'general') ?></td>
                    <td><?= $m['estado'] === 'inactivo' ? '<span class="badge-st st-cancelada">Inactivo</span>' : '<span class="badge-st st-activo">Activo</span>' ?></td>
                    <td><small class="muted"><?= (int)$m['compras'] ?> compra(s) · <?= (int)$m['reservas'] ?> reserva(s)</small></td>
                    <td><div class="acts">
                        <form method="post" action="<?= ruta('admin_config') ?>" style="display:inline">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_metodoPago" value="<?= e($m['id_metodoPago']) ?>">
                            <input type="hidden" name="nuevo_estado" value="<?= $m['estado'] === 'inactivo' ? 'activo' : 'inactivo' ?>">
                            <button class="ic-btn" type="submit" title="<?= $m['estado'] === 'inactivo' ? 'Activar' : 'Desactivar' ?>"><?= icon('check', 16) ?></button>
                        </form>
                        <form method="post" action="<?= ruta('admin_config') ?>" style="display:inline" data-confirm="¿Eliminar el método '<?= e($m['nombre_metodoPago']) ?>'?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_metodoPago" value="<?= e($m['id_metodoPago']) ?>">
                            <button class="ic-btn del" type="submit" aria-label="Eliminar"><?= icon('trash', 16) ?></button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <p class="muted" style="font-size:.85rem;margin-top:1rem">No se pueden eliminar métodos con reservas o compras asociadas: desactívalos.</p>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= icon('plus', 18) ?> Nuevo método</h3></div>
        <form method="post" action="<?= ruta('admin_config') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="crear">
            <div class="field"><label>Nombre *</label><input class="input" name="nombre_metodoPago" required maxlength="50" placeholder="Ej: QR BCP, Efectivo"></div>
            <div class="field"><label>Tipo</label>
                <select class="input" name="tipo">
                    <option value="general">General</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="qr">QR</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Crear método de pago</button>
        </form>
        <p class="muted" style="font-size:.82rem;margin-top:1.25rem">Estos métodos aparecen al reservar citas y al pagar en el carrito.</p>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
