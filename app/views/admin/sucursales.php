<?php
$titulo = 'Sucursales';
$activo = 'admin_sucursales';
require __DIR__ . '/../layout/admin_header.php';
$editar = $editar ?? null;
?>

<div class="page-title">
    <div>
        <h1>Sucursales</h1>
        <p class="muted">Locales, horarios de atención y disponibilidad para reservas.</p>
    </div>
    <a class="btn btn-primary" href="<?= ruta('admin_sucursales', ['nuevo' => 1]) ?>"><?= icon('plus', 18) ?> Nueva sucursal</a>
</div>

<div class="split">
    <section class="panel">
        <?php if (empty($sucursales)): ?>
            <?= vacio('No hay sucursales registradas. Crea la primera en el formulario lateral.', 'pin') ?>
        <?php else: foreach ($sucursales as $s): ?>
            <div class="panel-head" style="margin-top:1rem">
                <h3><?= icon('pin', 18) ?> <?= e($s['nombre_sucursal']) ?> <small class="muted"><?= e($s['id_sucursal']) ?></small></h3>
                <a class="link" href="<?= ruta('admin_sucursales', ['editar' => $s['id_sucursal']]) ?>">Editar datos →</a>
            </div>
            <p class="muted" style="font-size:.9rem"><?= e($s['direccion'] ?: 'Sin dirección') ?><?= $s['telefono'] ? ' · ' . e($s['telefono']) : '' ?></p>

            <form method="post" action="<?= ruta('admin_sucursales') ?>">
                <?= csrf() ?>
                <input type="hidden" name="accion" value="horario">
                <input type="hidden" name="id_sucursal" value="<?= e($s['id_sucursal']) ?>">
                <table class="table">
                    <thead><tr><th>Día</th><th>Activo</th><th>Apertura</th><th>Cierre</th></tr></thead>
                    <tbody>
                    <?php
                    $porDia = [];
                    foreach (($horarios[$s['id_sucursal']] ?? []) as $h) {
                        $porDia[$h['dia_semana']] = $h;
                    }
                    foreach (['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'] as $dia):
                        $h = $porDia[$dia] ?? null;
                        $act = $h !== null && (int)$h['es_activo'] === 1;
                    ?>
                        <tr>
                            <td><b><?= $dia ?></b></td>
                            <td><input type="checkbox" name="dia[<?= $dia ?>]" <?= $act ? 'checked' : '' ?>></td>
                            <td><input class="input" type="time" name="apertura[<?= $dia ?>]" value="<?= $act ? e(substr((string)$h['hora_apertura'], 0, 5)) : '09:00' ?>" style="width:120px"></td>
                            <td><input class="input" type="time" name="cierre[<?= $dia ?>]" value="<?= $act ? e(substr((string)$h['hora_cierre'], 0, 5)) : '19:00' ?>" style="width:120px"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <button class="btn btn-primary btn-sm" type="submit" style="margin:.75rem 0 1.5rem">Guardar horario de <?= e($s['nombre_sucursal']) ?></button>
            </form>
        <?php endforeach; endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= $editar !== null ? 'Editar sucursal' : 'Nueva sucursal' ?></h3></div>
        <form method="post" action="<?= ruta('admin_sucursales') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="sucursal">
            <?php if ($editar !== null): ?><input type="hidden" name="id_sucursal" value="<?= e($editar['id_sucursal']) ?>"><?php endif; ?>
            <div class="field"><label>Nombre *</label><input class="input" name="nombre_sucursal" required maxlength="50" value="<?= e($editar['nombre_sucursal'] ?? '') ?>"></div>
            <div class="field"><label>Dirección</label><textarea class="input" name="direccion" rows="2" maxlength="150"><?= e($editar['direccion'] ?? '') ?></textarea></div>
            <div class="field"><label>Teléfono</label><input class="input" name="telefono" maxlength="20" value="<?= e($editar['telefono'] ?? '') ?>"></div>
            <div style="display:flex;gap:.6rem">
                <?php if ($editar !== null): ?>
                    <a href="<?= ruta('admin_sucursales') ?>" class="btn btn-outline" style="flex:1">Cancelar</a>
                <?php else: ?>
                    <button type="reset" class="btn btn-outline" style="flex:1">Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="flex:1"><?= $editar !== null ? 'Guardar' : 'Crear' ?></button>
            </div>
        </form>
        <p class="muted" style="font-size:.82rem;margin-top:1.25rem">Al crear una sucursal se generan los 7 días para su horario; actívalos en la tabla de la izquierda.</p>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
