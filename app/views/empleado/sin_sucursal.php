<?php
$titulo = 'Sucursal no asignada';
$activo = '';
require __DIR__ . '/../layout/empleado_header.php';
?>

<div class="page-title">
    <div>
        <h1>Sucursal no asignada</h1>
        <p class="muted">Tu cuenta de empleado todavía no tiene una sucursal activa.</p>
    </div>
</div>

<section class="panel">
    <div class="empty" style="padding:3rem 1rem">
        <?= icon('pin', 48) ?>
        <h3 style="margin:.8rem 0 .4rem">No podemos abrir este módulo</h3>
        <p class="muted" style="max-width:520px;margin:0 auto">
            Para consultar citas, clientas e historial, el administrador debe asignarte una sucursal desde
            <b>Usuarios</b>. Luego vuelve a iniciar sesión para actualizar tus datos de trabajo.
        </p>
        <div style="margin-top:1.4rem">
            <a class="btn btn-outline" href="<?= ruta('empleado_perfil') ?>"><?= icon('user', 16) ?> Ir a mi perfil</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
