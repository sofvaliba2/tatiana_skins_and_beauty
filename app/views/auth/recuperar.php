<?php
$titulo = 'Recuperar contraseña';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:480px">
        <div class="card" style="padding:2rem">
            <h1 style="font-size:1.7rem;margin-bottom:.5rem">Recuperar contraseña</h1>
            <p class="muted" style="margin-bottom:1.5rem">
                Escribe el correo de tu cuenta. Como no enviamos correos automáticos, el personal del centro te ayudará a restablecer tu contraseña en la sucursal más cercana, verificando tu identidad.
            </p>
            <?php if (!empty($enviado) && empty($errores)): ?>
                <div class="alert ok"><?= icon('check', 18) ?> <span>Solicitud registrada. Acércate a cualquier sucursal con tu documento de identidad y el personal restablecerá tu contraseña.</span></div>
            <?php else: ?>
                <form method="post" action="<?= ruta('recuperar') ?>">
                    <?= csrf() ?>
                    <div class="field">
                        <label for="correo">Correo electrónico</label>
                        <input class="input<?= clase_campo($errores, 'correo') ?>" type="email" id="correo" name="correo" required value="<?= e($_POST['correo'] ?? '') ?>">
                        <?= error_campo($errores, 'correo') ?>
                    </div>
                    <button class="btn btn-primary" type="submit" style="width:100%">Registrar solicitud</button>
                </form>
            <?php endif; ?>
            <p class="muted" style="margin-top:1.5rem;text-align:center;font-size:.92rem">
                <a href="<?= ruta('login') ?>">Volver a iniciar sesión</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
