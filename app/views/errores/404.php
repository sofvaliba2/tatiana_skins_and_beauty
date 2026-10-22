<?php
$titulo = 'Página no encontrada';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="text-align:center;max-width:520px">
        <div class="script" style="font-size:5rem;color:var(--morado);line-height:1">404</div>
        <h1 style="margin:.5rem 0">Página no encontrada</h1>
        <p class="muted">La página que buscas no existe o fue movida. Puede que el enlace esté desactualizado.</p>
        <div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem">
            <a href="<?= ruta('home') ?>" class="btn btn-primary">Volver al inicio</a>
            <a href="<?= ruta('catalogo') ?>" class="btn btn-outline">Ver catálogo</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
