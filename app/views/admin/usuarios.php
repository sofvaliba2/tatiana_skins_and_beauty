<?php
$titulo = 'Usuarios';
$activo = 'admin_usuarios';
require __DIR__ . '/../layout/admin_header.php';
$editar = null;
if (($_GET['editar'] ?? '') !== '') {
    foreach ($usuarios as $u) {
        if ($u['id_usuario'] === $_GET['editar']) {
            $editar = $u;
            break;
        }
    }
}
?>

<div class="page-title">
    <div>
        <h1>Usuarios del personal</h1>
        <p class="muted">Administradores y personal (ROL01 / ROL02) con acceso al panel.</p>
    </div>
    <a class="btn btn-primary" href="<?= ruta('admin_usuarios', ['nuevo' => 1]) ?>"><?= icon('plus', 18) ?> Nuevo usuario</a>
</div>

<div class="split">
    <section class="panel">
        <?php if (empty($usuarios)): ?>
            <?= vacio('Aún no hay usuarios del personal. Crea el primero en el formulario lateral.', 'shield') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>ID</th><th>Usuario</th><th>Rol</th><th>Correo</th><th>Sucursal</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u['id_usuario']) ?></td>
                    <td><div class="person"><span class="avatar"><?= e(mb_substr($u['nombre'], 0, 1)) ?></span><?= e(trim($u['nombre'] . ' ' . $u['ap_paterno'])) ?></div></td>
                    <td><span class="badge-st <?= $u['id_rol'] === 'ROL01' ? 'st-confirmada' : 'st-activo' ?>"><?= e($u['nombre_rol']) ?></span></td>
                    <td><?= e($u['correo'] ?: '—') ?></td>
                    <td><?= e($u['nombre_sucursal'] ?: '—') ?></td>
                    <td><div class="acts">
                        <a class="ic-btn" href="<?= ruta('admin_usuarios', ['editar' => $u['id_usuario']]) ?>" aria-label="Editar"><?= icon('edit', 16) ?></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= $editar !== null ? 'Editar usuario' : 'Nuevo usuario' ?></h3></div>
        <form method="post" action="<?= ruta('admin_usuarios') ?>">
            <?= csrf() ?>
            <?php if ($editar !== null): ?><input type="hidden" name="id_usuario" value="<?= e($editar['id_usuario']) ?>"><?php endif; ?>
            <div class="field"><label>Nombre *</label><input class="input" name="nombre" required maxlength="50" value="<?= e($editar['nombre'] ?? '') ?>"></div>
            <div class="field"><label>Apellido paterno *</label><input class="input" name="ap_paterno" required maxlength="50" value="<?= e($editar['ap_paterno'] ?? '') ?>"></div>
            <div class="field"><label>Apellido materno</label><input class="input" name="ap_materno" maxlength="50" value="<?= e($editar['ap_materno'] ?? '') ?>"></div>
            <div class="field"><label>Teléfono</label><input class="input" name="telefono" maxlength="20" value="<?= e($editar['telefono'] ?? '') ?>"></div>
            <div class="field"><label>Rol *</label>
                <select class="input" name="id_rol" required>
                    <option value="ROL02" <?= ($editar['id_rol'] ?? '') === 'ROL02' ? 'selected' : '' ?>>Personal</option>
                    <option value="ROL01" <?= ($editar['id_rol'] ?? '') === 'ROL01' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="field"><label>Sucursal</label>
                <select class="input" name="id_sucursal">
                    <option value="">Sin sucursal asignada</option>
                    <?php foreach (($sucursales ?? []) as $s): ?>
                        <option value="<?= e($s['id_sucursal']) ?>" <?= (($editar['id_sucursal'] ?? '') === $s['id_sucursal']) ? 'selected' : '' ?>><?= e($s['nombre_sucursal']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($editar === null): ?>
                <div class="field"><label>Correo electrónico *</label><input class="input" type="email" name="correo" required placeholder="personal@tatiana.bo"></div>
                <div class="field"><label>Contraseña * <small>(mín. 8)</small></label><input class="input" type="password" name="password" required minlength="8"></div>
            <?php else: ?>
                <div class="field"><label>Correo</label><input class="input" value="<?= e($editar['correo'] ?? '') ?>" disabled></div>
                <div class="field"><label>Nueva contraseña <small>(opcional, mín. 8)</small></label><input class="input" type="password" name="nueva_password" minlength="8" placeholder="Dejar vacío para no cambiar"></div>
            <?php endif; ?>
            <div style="display:flex;gap:.6rem;margin-top:1rem">
                <?php if ($editar !== null): ?>
                    <a href="<?= ruta('admin_usuarios') ?>" class="btn btn-outline" style="flex:1">Cancelar</a>
                <?php else: ?>
                    <button type="reset" class="btn btn-outline" style="flex:1">Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="flex:1"><?= $editar !== null ? 'Guardar' : 'Crear usuario' ?></button>
            </div>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
