<?php
$titulo = 'Mi perfil';
$activo = 'empleado_perfil';
require __DIR__ . '/../layout/empleado_header.php';
$nombreSucursal = (string)($sucursal_actual['nombre_sucursal'] ?? 'Sin sucursal asignada');
?>

<div class="page-title">
    <div>
        <h1>Mi perfil</h1>
        <p class="muted">Datos personales, sucursal de trabajo y contraseña de acceso al panel.</p>
    </div>
</div>

<?php if (empty($sucursal_actual)): ?>
    <div class="alert warn" style="margin-bottom:1.2rem">
        Tu cuenta no tiene una sucursal activa asignada. Pide al administrador que la asigne para usar la agenda, las clientas y las ventas.
    </div>
<?php endif; ?>

<div class="split">
    <section class="panel">
        <div class="panel-head"><h3>Datos personales</h3></div>
        <form method="post" action="<?= ruta('empleado_perfil') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="datos">
            <div class="form-grid">
                <div class="field">
                    <label>Nombre *</label>
                    <input class="input<?= clase_campo($errores, 'nombre') ?>" name="nombre" required maxlength="50" value="<?= e($usuario['nombre'] ?? '') ?>">
                    <?= error_campo($errores, 'nombre') ?>
                </div>
                <div class="field">
                    <label>Apellido paterno *</label>
                    <input class="input<?= clase_campo($errores, 'ap_paterno') ?>" name="ap_paterno" required maxlength="50" value="<?= e($usuario['ap_paterno'] ?? '') ?>">
                    <?= error_campo($errores, 'ap_paterno') ?>
                </div>
                <div class="field">
                    <label>Apellido materno</label>
                    <input class="input" name="ap_materno" maxlength="50" value="<?= e($usuario['ap_materno'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Teléfono</label>
                    <input class="input<?= clase_campo($errores, 'telefono') ?>" name="telefono" maxlength="20" value="<?= e($usuario['telefono'] ?? '') ?>">
                    <?= error_campo($errores, 'telefono') ?>
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label>Correo de acceso</label>
                    <input class="input" value="<?= e($cuenta['correo'] ?? '') ?>" disabled>
                </div>
                <div class="field">
                    <label>Sucursal</label>
                    <input class="input" value="<?= e($nombreSucursal) ?>" disabled>
                </div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:1rem">Guardar cambios</button>
        </form>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= icon('key', 18) ?> Cambiar contraseña</h3></div>
        <form method="post" action="<?= ruta('empleado_perfil') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="password">
            <div class="field">
                <label>Contraseña actual *</label>
                <input class="input<?= clase_campo($errores, 'password_actual') ?>" type="password" name="password_actual" required>
                <?= error_campo($errores, 'password_actual') ?>
            </div>
            <div class="field">
                <label>Nueva contraseña * <small>(mín. 8)</small></label>
                <input class="input<?= clase_campo($errores, 'password_nueva') ?>" type="password" name="password_nueva" required minlength="8">
                <?= error_campo($errores, 'password_nueva') ?>
            </div>
            <div class="field">
                <label>Repetir contraseña *</label>
                <input class="input<?= clase_campo($errores, 'password_rep') ?>" type="password" name="password_rep" required>
                <?= error_campo($errores, 'password_rep') ?>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Actualizar contraseña</button>
        </form>
        <?php if (!empty($errores)): ?>
            <div class="alert warn" style="margin-top:1rem">
                <?php foreach ($errores as $lista): foreach ($lista as $m): ?><span><?= e($m) ?></span><?php endforeach; endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
