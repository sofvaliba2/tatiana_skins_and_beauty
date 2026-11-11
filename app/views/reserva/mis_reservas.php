<?php
$titulo = 'Mis reservas';
$activo = 'mis_reservas';
require __DIR__ . '/../layout/header.php';
$meses = ['', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
$reservas = $reservas ?? [];
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Tu espacio</span>
        <h1>Mis reservas</h1>
        <p>Revisa tus citas, el estado de tu pago y el saldo pendiente.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container" style="max-width:920px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.4rem;flex-wrap:wrap;gap:1rem">
            <h2 style="font-size:1.8rem">Próximas y pasadas</h2>
            <a class="btn btn-primary btn-sm" href="<?= ruta('reserva_nueva') ?>"><?= icon('plus', 16) ?> Nueva reserva</a>
        </div>

        <?php if (!$reservas): ?>
            <div class="card card-pad">
                <?= vacio('Aún no tienes reservas. Agenda tu primera cita.', 'calendar') ?>
                <div style="text-align:center">
                    <a class="btn btn-primary" href="<?= ruta('reserva_nueva') ?>">Reservar ahora</a>
                </div>
            </div>
        <?php else: ?>
        <div class="card card-pad">
            <?php foreach ($reservas as $r):
                $idReserva = (int)$r['id_reserva'];
                $t = strtotime((string)$r['fecha']);
                $resumen = \App\Models\Reserva::resumenPago($r);
                $estadoPago = (string)$resumen['estado'];
                $esEfectivo = (string)($r['id_metodoPago'] ?? '') === 'MP01';
                $puedeCancelar = in_array((string)$r['estado'], ['pendiente', 'confirmada'], true)
                    && (float)$resumen['pagado'] <= 0
                    && !in_array($estadoPago, ['por_verificar', 'anticipo_confirmado', 'parcial', 'pagado'], true);
            ?>
            <div class="res-item">
                <div class="res-date">
                    <b><?= date('d', $t) ?></b>
                    <small><?= $meses[(int)date('n', $t)] ?></small>
                </div>
                <div>
                    <h3 style="font-size:1.2rem"><?= e($r['servicios'] ?? 'Tratamiento') ?></h3>
                    <span class="muted" style="font-size:.88rem">
                        <?= icon('clock', 14) ?> <?= e(substr((string)$r['hora'], 0, 5)) ?>
                        <?= icon('pin', 14) ?> <?= e($r['nombre_sucursal'] ?? 'Sucursal') ?>
                    </span>
                    <div class="summary-row" style="max-width:420px;font-size:.86rem">
                        <span>Total <?= bs($resumen['total']) ?> · Anticipo <?= bs($resumen['anticipo']) ?></span>
                        <span>Pagado <?= bs($resumen['pagado']) ?> · Saldo <?= bs($resumen['saldo']) ?></span>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-top:.4rem">
                        <?= estado((string)$r['estado']) ?>
                        <?= estado_pago($estadoPago) ?>
                        <?php if ($r['id_metodoPago'] ?? ''): ?>
                            <span class="muted" style="font-size:.8rem"><?= e($r['nombre_metodoPago'] ?? '') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right;display:flex;flex-direction:column;gap:.5rem;align-items:flex-end">
                    <a class="link" href="<?= ruta('reserva_recibo', ['id' => $idReserva]) ?>">Ver detalle</a>
                    <?php if (in_array($estadoPago, ['sin_pago', 'rechazado'], true) && !$esEfectivo): ?>
                        <a class="link" href="<?= ruta('reserva_pago', ['id' => $idReserva]) ?>">Pagar anticipo</a>
                    <?php endif; ?>
                    <?php if ($r['estado'] === 'atendida'): ?>
                        <a class="link" href="<?= ruta('recomendaciones') ?>">Mis recomendaciones</a>
                    <?php endif; ?>
                    <?php if ($puedeCancelar): ?>
                        <form method="post" action="<?= ruta('mis_reservas') ?>" data-confirm="¿Cancelar la reserva #<?= $idReserva ?>?" style="display:inline">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="cancelar">
                            <input type="hidden" name="id_reserva" value="<?= $idReserva ?>">
                            <button class="link" type="submit" style="background:none;border:0;cursor:pointer;color:var(--danger)">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
