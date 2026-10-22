<?php
$titulo = 'Algo salió mal';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="text-align:center;max-width:560px">
        <div class="script" style="font-size:5rem;color:var(--morado);line-height:1">Ups</div>
        <h1 style="margin:.5rem 0">Error interno (500)</h1>
        <p class="muted">Ocurrió un problema inesperado. Nuestro equipo fue notificado; vuelve a intentarlo en unos minutos.</p>
        <?php if (!empty($detalle)): ?>
            <p class="muted" style="font-size:.8rem;margin-top:1rem">Detalles técnicos: <?= e($detalle) ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem">
            <a href="<?= ruta('home') ?>" class="btn btn-primary">Volver al inicio</a>
            <a href="javascript:location.reload()" class="btn btn-outline">Reintentar</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
