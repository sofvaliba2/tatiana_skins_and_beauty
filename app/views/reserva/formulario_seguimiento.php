<?php
/**
 * Formulario de seguimiento: se muestra desde la 2.ª reserva.
 * Variables opcionales: $ultimo (array con el historial anterior)
 */
$titulo = 'Seguimiento de tu piel';
$activo = 'servicios';
$ultimo = $ultimo ?? [];
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">¡Qué bueno verte de nuevo!</span>
        <h1>¿Cómo ha estado tu piel?</h1>
        <p>Ya conocemos tu historial, solo queremos saber cómo te fue desde la última visita.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container" style="max-width:820px">
        <?php $paso = 3; require __DIR__ . '/_stepper.php'; ?>

        <?php if ($ultimo): ?>
        <div class="alert info">
            <?= icon('leaf', 20) ?>
            <span>
                <b>Tu historial guardado:</b> piel <?= e($ultimo['tipo_piel'] ?? '—') ?> ·
                objetivo: <?= e($ultimo['objetivo'] ?? '—') ?> ·
                alergias: <?= e($ultimo['alergias'] ?? 'ninguna') ?>.
                No necesitas volver a llenarlo.
            </span>
        </div>
        <?php endif; ?>

        <form class="form-card" method="post" action="<?= ruta('reserva_recibo') ?>">
            <?= csrf() ?>
            <input type="hidden" name="tipo_formulario" value="seguimiento">

            <div class="field">
                <span class="label">¿Cómo te fue con el tratamiento anterior?</span>
                <div class="chips">
                    <?php foreach (['Excelente', 'Bien', 'Regular', 'No vi cambios', 'Tuve molestias'] as $r): ?>
                        <label class="chip"><input type="radio" name="resultado" value="<?= e($r) ?>"><span><?= e($r) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>¿Qué crema o producto utilizaste después?</label>
                    <input class="input" name="crema_utilizada" placeholder="Ej: Sérum humectante">
                </div>
                <div class="field">
                    <label>¿Desde cuándo lo usas?</label>
                    <select class="input" name="tiempo_uso">
                        <option>Menos de 1 semana</option><option>1 a 2 semanas</option><option>Más de un mes</option><option>No usé ninguno</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <span class="label">¿Tuviste alguna reacción?</span>
                <div class="chips">
                    <?php foreach (['Ninguna', 'Enrojecimiento', 'Picazón', 'Resequedad', 'Granitos nuevos'] as $r): ?>
                        <label class="chip"><input type="checkbox" name="reacciones[]" value="<?= e($r) ?>"><span><?= e($r) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field">
                <label>¿Cambió tu objetivo o tu rutina?</label>
                <textarea class="input" name="cambios" placeholder="Cuéntanos qué ha cambiado desde tu última cita..."></textarea>
            </div>

            <div class="field">
                <label>¿Nueva alergia o algo importante?</label>
                <input class="input" name="alergias_nuevas" placeholder="Solo si hay algo nuevo">
            </div>

            <button class="btn btn-primary btn-block" type="submit">Guardar y ver mi recibo</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
