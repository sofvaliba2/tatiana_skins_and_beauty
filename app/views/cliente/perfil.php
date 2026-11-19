<?php
$titulo = 'Mi perfil';
$activo = 'panel';
$carrito = count($_SESSION['carrito'] ?? []);
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:640px">
        <p style="margin-bottom:1rem"><?= enlace_volver('panel', [], 'Volver a mi panel') ?></p>
        <h1 style="font-size:1.8rem;margin-bottom:1.5rem">Mi perfil</h1>

        <div class="card" style="padding:2rem;margin-bottom:1.5rem">
            <h2 style="font-size:1.2rem;margin-bottom:1rem">Datos personales</h2>
            <form method="post" action="<?= ruta('perfil') ?>" data-guardar>
                <?= csrf() ?>
                <input type="hidden" name="accion" value="datos">
                <div class="grid-2" style="gap:1rem">
                    <div class="field">
                        <label for="nombre">Nombre *</label>
                        <input class="input<?= clase_campo($errores, 'nombre') ?>" id="nombre" name="nombre" required maxlength="50" value="<?= e($usuario['nombre']) ?>">
                        <?= error_campo($errores, 'nombre') ?>
                    </div>
                    <div class="field">
                        <label for="ap_paterno">Apellido paterno *</label>
                        <input class="input<?= clase_campo($errores, 'ap_paterno') ?>" id="ap_paterno" name="ap_paterno" required maxlength="50" value="<?= e($usuario['ap_paterno']) ?>">
                        <?= error_campo($errores, 'ap_paterno') ?>
                    </div>
                    <div class="field">
                        <label for="ap_materno">Apellido materno</label>
                        <input class="input" id="ap_materno" name="ap_materno" maxlength="50" value="<?= e($usuario['ap_materno']) ?>">
                    </div>
                    <div class="field">
                        <label for="telefono">Teléfono</label>
                        <input class="input<?= clase_campo($errores, 'telefono') ?>" id="telefono" name="telefono" maxlength="20" value="<?= e($usuario['telefono']) ?>">
                        <?= error_campo($errores, 'telefono') ?>
                    </div>
                    <div class="field">
                        <label for="fecha_nacimiento">Fecha de nacimiento</label>
                        <input class="input<?= clase_campo($errores, 'fecha_nacimiento') ?>" type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($usuario['fecha_nacimiento']) ?>">
                        <?= error_campo($errores, 'fecha_nacimiento') ?>
                    </div>
                    <div class="field">
                        <label>Correo</label>
                        <input class="input" value="<?= e($cuenta['correo'] ?? '') ?>" disabled>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:1rem">Guardar cambios</button>
            </form>
        </div>

        <div class="card" style="padding:2rem">
            <h2 style="font-size:1.2rem;margin-bottom:1rem">Cambiar contraseña</h2>
            <form method="post" action="<?= ruta('perfil') ?>" data-guardar>
                <?= csrf() ?>
                <input type="hidden" name="accion" value="password">
                <?= password_campo('password_actual', $errores, 'Contraseña actual *') ?>
                <div class="grid-2" style="gap:1rem">
                    <?= password_campo('password_nueva', $errores, 'Nueva contraseña * <small>(mín. 8 caracteres)</small>', '••••••••', 'new-password', true, 8) ?>
                    <?= password_campo('password_rep', $errores, 'Repetir contraseña *', '••••••••', 'new-password') ?>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:1rem">Actualizar contraseña</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
