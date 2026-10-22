<?php

$titulo = 'Recibo de reserva';
$activo = 'mis_reservas';
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Paso 4</span>
        <h1>¡Reserva registrada!</h1>
        <p>Guarda tu recibo. Te esperamos el día de tu cita.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <?php $paso = 4; require __DIR__ . '/_stepper.php'; ?>

        <div class="recibo">
            <div class="recibo-head">
                <?= logo(true) ?>
                <div class="nro">Recibo N°<strong>R-<?= str_pad((string)($reserva['id_reserva'] ?? 1), 6, '0', STR_PAD_LEFT) ?></strong></div>
            </div>
            <div class="recibo-body">
                <div class="recibo-grid">
                    <div><small>Cliente</small><span><?= e($reserva['nombre_usuario'] ?? 'Cliente') ?></span></div>
                    <div><small>Estado</small><?= estado($reserva['estado'] ?? 'pendiente') ?></div>
                    <div><small>Fecha y hora</small><span><?= e(($reserva['fecha'] ?? '') . ' ' . ($reserva['hora'] ?? '')) ?></span></div>
                    <div><small>Sucursal</small><span><?= e($sucursal['nombre_sucursal'] ?? 'Sucursal principal') ?></span></div>
                    <div><small>Atiende</small><span>Por asignar</span></div>
                    <div><small>Método de pago</small><span><?= e($metodo ?? 'Por definir') ?></span></div>
                </div>

                <table class="table-simple">
                    <thead><tr><th>Servicio</th><th class="r">Duración</th><th class="r">Precio</th></tr></thead>
                    <tbody>
                        <?php 
                        $totalServicios = 0;
                        foreach (($items ?? []) as $item): 
                            $totalServicios += (float)$item['subtotal'];
                        ?>
                        <tr>
                            <td><?= e($item['nombre'] ?? 'Servicio') ?></td>
                            <td class="r"><?= (int)($item['duracion_minutos'] ?? 0) ?> min</td>
                            <td class="r">Bs. <?= number_format((float)$item['subtotal'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php 
                $anticipo = 30.00; // O el valor de anticipo que manejes en tu sistema
                $saldo = $totalServicios - $anticipo;
                if ($saldo < 0) $saldo = 0;
                ?>

                <div class="recibo-total">
                    <div class="row"><span>Total del servicio</span><span>Bs. <?= number_format($totalServicios, 2) ?></span></div>
                    <div class="row"><span>Anticipo pagado</span><span>− Bs. <?= number_format($anticipo, 2) ?></span></div>
                    <div class="row big"><span>Saldo a pagar en sucursal</span><b>Bs. <?= number_format($saldo, 2) ?></b></div>
                </div>

                <p class="recibo-note">
                    Este documento es un comprobante de reserva y no reemplaza a una factura.<br>
                    Cancelaciones o cambios con 24 horas de anticipación.
                </p>
            </div>
        </div>

        <div class="no-print" style="display:flex;gap:1rem;justify-content:center;margin-top:2rem;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="window.print()"><?= icon('print', 18) ?> Imprimir / guardar PDF</button>
            <a class="btn btn-outline" href="<?= ruta('mis_reservas') ?>">Ver mis reservas</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>