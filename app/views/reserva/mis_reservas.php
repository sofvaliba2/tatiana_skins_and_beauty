<?php
$titulo = 'Mis reservas';
$activo = 'mis_reservas';
require __DIR__ . '/../layout/header.php';
$meses = ['', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Tu espacio</span>
        <h1>Mis reservas</h1>
        <p>Revisa tus citas y el estado de tu anticipo.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container" style="max-width:860px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.4rem;flex-wrap:wrap;gap:1rem">
            <h2 style="font-size:1.8rem">Próximas y pasadas</h2>
            <a class="btn btn-primary btn-sm" href="<?= ruta('reserva_nueva') ?>"><?= icon('plus', 16) ?> Nueva reserva</a>
        </div>

        <div class="card card-pad">
            <?php foreach (($reservas ?? []) as $r): $t = strtotime($r['fecha']); ?>
            <div class="res-item">
                <div class="res-date"><b><?= date('d', $t) ?></b><small><?= $meses[(int)date('n', $t)] ?></small></div>
                <div>
                    <h3 style="font-size:1.25rem"><?= e($r['servicios'] ?? 'Tratamiento facial') ?></h3>
<span class="muted" style="font-size:.88rem">
    <?= icon('clock', 14) ?> <?= e($r['hora']) ?> 
    <?= icon('pin', 14) ?> <?= e($r['nombre_sucursal'] ?? 'Sucursal') ?> 
    Anticipo Bs. <?= number_format((float)($r['total'] ?? 0), 0) ?>
</span>
                </div>
                <div style="text-align:right;display:flex;flex-direction:column;gap:.5rem;align-items:flex-end">
                    <?= estado($r['estado']) ?>
                    <a class="link" href="<?= ruta('reserva_recibo') ?>">Ver recibo</a>
                    <?php if ($r['estado'] === 'atendida'): ?>
                        <a class="link" href="<?= ruta('recomendaciones') ?>">Mis recomendaciones</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
