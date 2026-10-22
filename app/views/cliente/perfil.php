<?php
$titulo = 'Mi perfil';
$activo = 'panel';
$carrito = count($_SESSION['carrito'] ?? []);
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:640px">
        <h1 style="font-size:1.8rem;margin-bottom:1.5rem">Mi perfil</h1>

        <div class="card" style="padding:2rem;margin-bottom:1.5rem">
            <h2 style="font-size:1.2rem;margin-bottom:1rem">Datos personales</h2>
            <form method="post" action="<?= ruta('perfil') ?>">
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
            <form method="post" action="<?= ruta('perfil') ?>">
                <?= csrf() ?>
                <input type="hidden" name="accion" value="password">
                <div class="field">
                    <label for="password_actual">Contraseña actual *</label>
                    <input class="input<?= clase_campo($errores, 'password_actual') ?>" type="password" id="password_actual" name="password_actual" required>
                    <?= error_campo($errores, 'password_actual') ?>
                </div>
                <div class="grid-2" style="gap:1rem">
                    <div class="field">
                        <label for="password_nueva">Nueva contraseña * <small>(mín. 8 caracteres)</small></label>
                        <input class="input<?= clase_campo($errores, 'password_nueva') ?>" type="password" id="password_nueva" name="password_nueva" required minlength="8">
                        <?= error_campo($errores, 'password_nueva') ?>
                    </div>
                    <div class="field">
                        <label for="password_rep">Repetir contraseña *</label>
                        <input class="input<?= clase_campo($errores, 'password_rep') ?>" type="password" id="password_rep" name="password_rep" required>
                        <?= error_campo($errores, 'password_rep') ?>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:1rem">Actualizar contraseña</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
