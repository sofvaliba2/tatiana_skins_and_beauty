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

            <?php if (!empty($enviado) && empty($errores)): ?>
                <?php if (!empty($devLink)): ?>
                    <!-- Panel persistente: NO usa clase "alert" para que app.js no lo oculte. -->
                    <div class="recuperacion-dev">
                        <strong><?= icon('key', 18) ?> Modo desarrollo / pruebas</strong>
                        <p>
                            En este entorno no hay servidor de correo configurado, por eso el enlace
                            se muestra aquí en pantalla. Cópialo o haz clic para crear tu nueva contraseña.
                        </p>
                        <?php if (!empty($mailError)): ?>
                            <p class="muted">El envío por correo falló (SMTP no configurado), pero el enlace de abajo funciona igual.</p>
                        <?php endif; ?>
                        <a class="btn btn-primary btn-block" href="<?= e($devLink) ?>">Abrir enlace de restablecimiento</a>
                        <p class="muted" style="margin-top:.75rem;font-size:.82rem;word-break:break-all">
                            O copia esta dirección: <?= e($devLink) ?>
                        </p>
                        <p class="muted" style="margin-top:.5rem;font-size:.82rem">
                            Vence en <?= \App\Models\RecuperacionPassword::MINUTOS_VIGENCIA ?> minutos y solo se puede usar una vez.
                        </p>
                    </div>
                <?php endif; ?>
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
