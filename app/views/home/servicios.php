<?php
$titulo = 'Servicios';
$activo = 'servicios';
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Tratamientos</span>
        <h1>Nuestros servicios</h1>
        <p>Elige el tratamiento ideal y reserva tu horario en línea.</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="tabs" style="margin-bottom:2rem">
            <a class="tab active" href="#">Todos</a>
            <?php foreach (($categorias ?? []) as $c): ?>
                <a class="tab" href="#"><?= e($c) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="grid-3">
            <?php foreach (($servicios ?? []) as $s): ?>
            <article class="card servicio-card">
                <div class="ico"><?= icon($s['icono'] ?? 'sparkle', 28) ?></div>
                <h3><?= e($s['nombre']) ?></h3>
                <p class="muted" style="font-size:.92rem"><?= e($s['descripcion']) ?></p>
                <div class="meta"><span><?= icon('clock', 15) ?> <?= (int)$s['duracion'] ?> min</span></div>
                <div class="precio">Bs. <?= number_format($s['precio'], 0) ?></div>
                <a href="<?= ruta('reserva_nueva') ?>" class="btn btn-primary btn-sm" style="margin-top:1rem">Reservar</a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
