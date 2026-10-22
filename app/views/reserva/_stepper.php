<?php
/** Indicador de pasos de la reserva. Requiere $paso (1-4). */
$pasos_reserva = ['Servicio y horario', 'Pago del anticipo', 'Tu piel', 'Recibo'];
?>
<div class="stepper">
    <?php foreach ($pasos_reserva as $i => $nombre): $n = $i + 1;
        $c = $n < $paso ? 'done' : ($n === $paso ? 'active' : ''); ?>
        <?php if ($i > 0): ?><span class="sep"></span><?php endif; ?>
        <div class="st <?= $c ?>"><b><?= $n < $paso ? '✓' : $n ?></b><span><?= e($nombre) ?></span></div>
    <?php endforeach; ?>
</div>
