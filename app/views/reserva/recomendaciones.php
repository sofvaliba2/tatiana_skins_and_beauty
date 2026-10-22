<?php
/** Recomendaciones personalizadas según historial y experiencia. */
$titulo = 'Recomendaciones para ti';
$activo = 'mis_reservas';
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Hechas para ti</span>
        <h1>Tus recomendaciones</h1>
        <p>Basadas en tu piel, tu tratamiento y cómo te fue con tus productos.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <div class="reco-hero">
            <div>
                <h2>Tu piel mixta pide hidratación ligera</h2>
                <p>Con tu objetivo de luminosidad y sin alergias registradas, estos productos combinan bien con tu rutina y con el tratamiento que recibiste.</p>
                <div class="reco-tags">
                    <span>Piel mixta</span><span>Objetivo: luminosidad</span><span>Sin alergias</span>
                </div>
            </div>
            <div style="width:110px;opacity:.9"><?= lips() ?></div>
        </div>

        <div class="grid-3">
            <?php foreach (($recomendados ?? []) as $p): ?>
            <article class="card">
                <div class="producto-img">
                    <?= frasco() ?>
                    <span class="match"><?= (int)$p['afinidad'] ?>% para ti</span>
                </div>
                <div class="producto-body">
                    <span class="cat"><?= e($p['categoria']) ?></span>
                    <h3><?= e($p['nombre']) ?></h3>
                    <p><?= e($p['motivo']) ?></p>
                    <div class="producto-foot">
                        <span class="precio-lg">Bs. <?= number_format($p['precio'], 0) ?></span>
                        <button class="btn btn-primary btn-sm"><?= icon('plus', 16) ?> Agregar</button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
