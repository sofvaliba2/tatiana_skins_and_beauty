<?php
$titulo = 'Mi perfil';
$activo = 'admin_perfil';
require __DIR__ . '/../layout/admin_header.php';
$u = \App\Core\Auth::usuario();
?>

<div class="page-title">
    <div>
        <h1>Mi perfil</h1>
        <p class="muted">Datos personales y contraseña de acceso al panel.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <div class="panel-head"><h3>Datos personales</h3></div>
        <form method="post" action="<?= ruta('admin_perfil') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="datos">
            <div class="form-grid">
                <div class="field"><label>Nombre *</label><input class="input" name="nombre" required maxlength="50" value="<?= e($usuario['nombre'] ?? '') ?>"></div>
                <div class="field"><label>Apellido paterno *</label><input class="input" name="ap_paterno" required maxlength="50" value="<?= e($usuario['ap_paterno'] ?? '') ?>"></div>
                <div class="field"><label>Apellido materno</label><input class="input" name="ap_materno" maxlength="50" value="<?= e($usuario['ap_materno'] ?? '') ?>"></div>
                <div class="field"><label>Teléfono</label><input class="input" name="telefono" maxlength="20" value="<?= e($usuario['telefono'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Correo de acceso</label><input class="input" value="<?= e($cuenta['correo'] ?? '') ?>" disabled></div>
            <div class="field"><label>Rol</label><input class="input" value="<?= e($u['rol'] ?? '') ?>" disabled></div>
            <button class="btn btn-primary" type="submit">Guardar cambios</button>
        </form>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= icon('key', 18) ?> Cambiar contraseña</h3></div>
        <form method="post" action="<?= ruta('admin_perfil') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="password">
            <div class="field"><label>Contraseña actual *</label><input class="input" type="password" name="password_actual" required></div>
            <div class="field"><label>Nueva contraseña * <small>(mín. 8)</small></label><input class="input" type="password" name="password_nueva" required minlength="8"></div>
            <div class="field"><label>Repetir contraseña *</label><input class="input" type="password" name="password_rep" required></div>
            <button class="btn btn-primary btn-block" type="submit">Actualizar contraseña</button>
        </form>
        <?php if (!empty($errores)): ?>
            <div class="alert warn" style="margin-top:1rem">
                <?php foreach ($errores as $lista): foreach ($lista as $m): ?><span><?= e($m) ?></span><?php endforeach; endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
