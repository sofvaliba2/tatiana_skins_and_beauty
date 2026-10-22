<?php
$titulo = 'Cuéntanos de tu piel';
$activo = 'servicios';
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Primera visita</span>
        <h1>Cuéntanos de tu piel</h1>
        <p>Con estas respuestas dejamos todo listo para el día de tu tratamiento.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container" style="max-width:820px">
        <?php $paso = 3; require __DIR__ . '/_stepper.php'; ?>

        <form class="form-card" method="post" action="<?= ruta('reserva_recibo') ?>">
            <?= csrf() ?>
            <input type="hidden" name="tipo_formulario" value="inicial">

            <div class="field">
                <span class="label">¿Cuál es tu tipo de piel?</span>
                <div class="chips">
                    <?php foreach (['Normal', 'Seca', 'Grasa', 'Mixta', 'Sensible', 'No lo sé'] as $t): ?>
                        <label class="chip"><input type="radio" name="tipo_piel" value="<?= e($t) ?>"><span><?= e($t) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field">
                <span class="label">¿Qué quieres lograr? (puedes elegir varias)</span>
                <div class="chips">
                    <?php foreach (['Hidratación', 'Acné / granitos', 'Manchas', 'Antiedad', 'Poros', 'Luminosidad', 'Cicatrices', 'Relajación'] as $o): ?>
                        <label class="chip"><input type="checkbox" name="objetivo[]" value="<?= e($o) ?>"><span><?= e($o) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field">
                <label for="alergias">¿Tienes alergias a alguna crema o ingrediente?</label>
                <textarea class="input" id="alergias" name="alergias" placeholder="Ej: perfumes, ácido salicílico, látex... Escribe «ninguna» si no tienes."></textarea>
                <span class="hint">Esta información es importante para tu seguridad.</span>
            </div>

            <div class="field">
                <label for="rutina">¿Cuál es tu rutina actual de cuidado?</label>
                <textarea class="input" id="rutina" name="rutina_actual" placeholder="Productos que usas por la mañana y por la noche..."></textarea>
            </div>

            <div class="field">
                <label for="obs">Algo más que debamos saber</label>
                <textarea class="input" id="obs" name="observaciones" placeholder="Embarazo, medicación, tratamientos recientes..."></textarea>
            </div>

            <div class="switch-row" style="margin-bottom:1.6rem">
                <div>
                    <b style="color:var(--morado)">¿Quieres recibir recomendaciones de productos?</b>
                    <div class="hint muted" style="font-size:.82rem">Te sugeriremos cremas según tu piel y tu experiencia con nosotras.</div>
                </div>
                <label class="chip"><input type="checkbox" name="abierto_recomendaciones" value="1" checked><span>Sí, quiero</span></label>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Guardar y ver mi recibo</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
