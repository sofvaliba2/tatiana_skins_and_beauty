<?php
require_once __DIR__ . '/../layout/helpers.php';
$titulo = 'Ingresar';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Tatiana Skin and Beauty</title>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Italiana&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="auth">
    <div class="auth-side">
        <?= logo(true) ?>
        <div>
            <h2>La mejor versión de <span class="script" style="color:var(--lila);font-size:1.3em">tu piel</span></h2>
            <p>Ingresa para reservar tus citas, ver tu historial y recibir recomendaciones pensadas para ti.</p>
        </div>
        <small style="opacity:.7">Cuidado · Confianza · Belleza real</small>
    </div>

    <div class="auth-main">
        <div class="auth-box">
            <h1>Bienvenida</h1>
            <p class="sub">Ingresa con tu correo y contraseña.</p>

            <?= flashes() ?>

            <?php if (!empty($errores['_general'])): ?>
                <div class="alert warn"><?= e($errores['_general'][0]) ?></div>
            <?php endif; ?>

            <form class="form-card" method="post" action="<?= ruta('login') ?>">
                <?= csrf() ?>
                <?php if (!empty($next)): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
                <div class="field">
                    <label for="correo">Correo electrónico</label>
                    <input class="input<?= clase_campo($errores, 'correo') ?>" type="email" id="correo" name="correo" placeholder="tucorreo@gmail.com" required value="<?= e($_POST['correo'] ?? '') ?>">
                    <?= error_campo($errores, 'correo') ?>
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <input class="input" type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
            </form>

            <p class="auth-foot"><a href="<?= ruta('recuperar') ?>">¿Olvidaste tu contraseña?</a></p>
            <p class="auth-foot" style="margin-top:.5rem">¿Aún no tienes cuenta? <a href="<?= ruta('registro') ?>">Regístrate</a></p>
            <p class="auth-foot" style="margin-top:.5rem"><a href="<?= ruta('home') ?>">← Volver al inicio</a></p>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/js/app.js"></script>
</body>
</html>
