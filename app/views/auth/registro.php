<?php
require_once __DIR__ . '/../layout/helpers.php';
$titulo = 'Crear cuenta';
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
            <h2>Empieza tu historial de <span class="script" style="color:var(--lila);font-size:1.3em">cuidado</span></h2>
            <p>Con tu cuenta guardamos tus alergias, objetivos y tratamientos para que cada visita sea mejor que la anterior.</p>
        </div>
        <small style="opacity:.7">Cuidado · Confianza · Belleza real</small>
    </div>

    <div class="auth-main">
        <div class="auth-box" style="width:min(520px,100%)">
            <h1>Crear cuenta</h1>
            <p class="sub">Solo te tomará un minuto.</p>

            <?= flashes() ?>
            <?php if (!empty($errores['_general'])): ?>
                <div class="alert warn"><?= e($errores['_general'][0]) ?></div>
            <?php endif; ?>

            <form class="form-card" method="post" action="<?= ruta('registro') ?>">
                <?= csrf() ?>
                <div class="form-grid">
                    <div class="field"><label>Nombre *</label><input class="input<?= clase_campo($errores, 'nombre') ?>" name="nombre" placeholder="María" required value="<?= e($_POST['nombre'] ?? '') ?>"><?= error_campo($errores, 'nombre') ?></div>
                    <div class="field"><label>Apellido paterno *</label><input class="input<?= clase_campo($errores, 'ap_paterno') ?>" name="ap_paterno" placeholder="López" required value="<?= e($_POST['ap_paterno'] ?? '') ?>"><?= error_campo($errores, 'ap_paterno') ?></div>
                    <div class="field"><label>Apellido materno</label><input class="input" name="ap_materno" placeholder="García" value="<?= e($_POST['ap_materno'] ?? '') ?>"></div>
                    <div class="field"><label>Teléfono *</label><input class="input<?= clase_campo($errores, 'telefono') ?>" name="telefono" placeholder="71234567" required value="<?= e($_POST['telefono'] ?? '') ?>"><?= error_campo($errores, 'telefono') ?></div>
                    <div class="field full"><label>Fecha de nacimiento</label><input class="input" type="date" name="fecha_nacimiento" value="<?= e($_POST['fecha_nacimiento'] ?? '') ?>"></div>
                    <div class="field full"><label>Correo electrónico *</label><input class="input<?= clase_campo($errores, 'correo') ?>" type="email" name="correo" placeholder="tucorreo@gmail.com" required value="<?= e($_POST['correo'] ?? '') ?>"><?= error_campo($errores, 'correo') ?></div>
                    <div class="field"><label>Contraseña *</label><input class="input<?= clase_campo($errores, 'password') ?>" type="password" name="password" minlength="8" required><?= error_campo($errores, 'password') ?></div>
                    <div class="field"><label>Repetir contraseña *</label><input class="input<?= clase_campo($errores, 'password2') ?>" type="password" name="password2" minlength="8" required><?= error_campo($errores, 'password2') ?></div>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Crear mi cuenta</button>
            </form>

            <p class="auth-foot">¿Ya tienes cuenta? <a href="<?= ruta('login') ?>">Ingresa</a></p>
        </div>
    </div>
</div>
</body>
</html>
