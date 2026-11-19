<?php
$titulo = 'Recuperar contraseña';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:480px">
        <div class="card" style="padding:2rem">
            <h1 style="font-size:1.7rem;margin-bottom:.5rem">Recuperar contraseña</h1>
            <p class="muted" style="margin-bottom:1.5rem">
                Escribe el correo con el que te registraste. Si está registrado, te enviaremos un enlace seguro para crear una nueva contraseña.
            </p>

            <?php if (!empty($errores['_general'])): ?>
                <div class="alert warn"><?= icon('shield', 18) ?> <span><?= e($errores['_general'][0]) ?></span></div>
            <?php endif; ?>

            <?php if (!empty($enviado) && empty($errores['_general'])): ?>
                <div class="recuperacion-ok">
                    <strong><?= icon('check', 18) ?> Revisa tu correo</strong>
                    <p>
                        Si la dirección está registrada, ya enviamos el enlace para crear tu nueva contraseña.
                        No olvides revisar también la carpeta de spam.
                    </p>
                </div>
                <p class="muted" style="margin-top:1.2rem;font-size:.9rem">
                    Si no recuerdas el correo con el que te registraste, solicita ayuda en la sucursal más cercana.
                </p>
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
