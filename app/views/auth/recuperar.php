<?php
$titulo = 'Recuperar contraseña';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:480px">
        <div class="card" style="padding:2rem">
            <h1 style="font-size:1.7rem;margin-bottom:.5rem">Recuperar contraseña</h1>
            <p class="muted" style="margin-bottom:1.5rem">
                Escribe el correo de tu cuenta. Si está registrado, te enviaremos un enlace seguro para crear una nueva contraseña.
            </p>
            <?php if (!empty($enviado) && empty($errores)): ?>
                <div class="alert ok"><?= icon('check', 18) ?> <span>Si el correo está registrado, enviamos un enlace para restablecer tu contraseña. Revisa también tu carpeta de spam.</span></div>
                <?php if (!empty($devLink)): ?>
                    <div class="alert warn" style="margin-top:1rem">
                        <strong>Modo desarrollo:</strong> este enlace normalmente iría por correo.<br>
                        <a href="<?= e($devLink) ?>">Abrir enlace de restablecimiento</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <form method="post" action="<?= ruta('recuperar') ?>">
                    <?= csrf() ?>
                    <div class="field">
                        <label for="correo">Correo electrónico</label>
                        <input class="input<?= clase_campo($errores, 'correo') ?>" type="email" id="correo" name="correo" placeholder="tucorreo@gmail.com" required autocomplete="email" value="<?= e($_POST['correo'] ?? '') ?>">
                        <?= error_campo($errores, 'correo') ?>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Enviar enlace</button>
                </form>
            <?php endif; ?>
            <p class="muted" style="margin-top:1.5rem;text-align:center;font-size:.92rem">
                <a href="<?= ruta('login') ?>">Volver a iniciar sesión</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
