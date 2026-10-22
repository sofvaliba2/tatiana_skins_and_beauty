<?php
$titulo = 'Acceso restringido';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="text-align:center;max-width:520px">
        <div class="script" style="font-size:5rem;color:var(--morado);line-height:1">403</div>
        <h1 style="margin:.5rem 0">No tienes acceso</h1>
        <p class="muted">Esta sección es solo para personal autorizado de Tatiana Skin and Beauty. Si crees que es un error, contacta con el centro.</p>
        <div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem">
            <a href="<?= ruta('home') ?>" class="btn btn-primary">Volver al inicio</a>
            <?php if (!\App\Core\Auth::estaLogueado()): ?>
                <a href="<?= ruta('login') ?>" class="btn btn-outline">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
