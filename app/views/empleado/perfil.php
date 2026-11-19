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
    <?= btn_volver('empleado_dashboard', [], 'Inicio') ?>
</div>

<?php if (empty($sucursal_actual)): ?>
    <div class="alert warn" style="margin-bottom:1.2rem">
        Tu cuenta no tiene una sucursal activa asignada. Pide al administrador que la asigne para usar la agenda, las clientas y las ventas.
    </div>
<?php endif; ?>

<div class="split">
    <section class="panel">
        <div class="panel-head"><h3>Datos personales</h3></div>
        <form method="post" action="<?= ruta('empleado_perfil') ?>" data-guardar>
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
        <form method="post" action="<?= ruta('empleado_perfil') ?>" data-guardar>
            <?= csrf() ?>
            <input type="hidden" name="accion" value="password">
            <?= password_campo('password_actual', $errores, 'Contraseña actual *') ?>
            <?= password_campo('password_nueva', $errores, 'Nueva contraseña * <small>(mín. 8)</small>', '••••••••', 'new-password', true, 8) ?>
            <?= password_campo('password_rep', $errores, 'Repetir contraseña *', '••••••••', 'new-password') ?>
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
