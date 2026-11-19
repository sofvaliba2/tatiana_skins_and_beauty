<?php
$titulo = 'Nueva contraseña';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:480px">
        <div class="card" style="padding:2rem">
            <h1 style="font-size:1.7rem;margin-bottom:.5rem">Nueva contraseña</h1>
            <?php if (!empty($invalido)): ?>
                <div class="alert warn"><?= icon('shield', 18) ?> <span>Este enlace no es válido, ya fue usado o venció. Solicita uno nuevo desde la pantalla de recuperación.</span></div>
                <p style="margin-top:1.2rem"><a class="btn btn-primary" href="<?= ruta('recuperar') ?>">Solicitar nuevo enlace</a></p>
            <?php else: ?>
                <p class="muted" style="margin-bottom:1.5rem">Escribe tu nueva contraseña. Debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.</p>
                <?php if (!empty($errores['_general'])): ?>
                    <div class="alert warn"><?= e($errores['_general'][0]) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= ruta('restablecer') ?>" data-guardar>
                    <?= csrf() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <?= password_campo('password', $errores, 'Nueva contraseña', 'Nueva contraseña', 'new-password') ?>
                    <?= password_campo('password2', $errores, 'Confirmar contraseña', 'Repite la contraseña', 'new-password') ?>
                    <button class="btn btn-primary btn-block" type="submit">Guardar nueva contraseña</button>
                </form>
            <?php endif; ?>
            <p class="muted" style="margin-top:1.5rem;text-align:center;font-size:.92rem">
                <a href="<?= ruta('login') ?>">Volver a iniciar sesión</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
