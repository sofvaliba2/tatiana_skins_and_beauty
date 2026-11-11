<?php
$titulo = 'Detalle de reserva';
$activo = 'mis_reservas';
require __DIR__ . '/../layout/header.php';

$idReserva = (int)($reserva['id_reserva'] ?? 0);
$resumen = $resumen ?? ['total' => 0, 'anticipo' => 0, 'pagado' => 0, 'saldo' => 0, 'estado' => 'sin_pago'];
$estadoPago = (string)($resumen['estado'] ?? 'sin_pago');
$esEfectivo = (string)($reserva['id_metodoPago'] ?? '') === 'MP01';
$cliente = trim(implode(' ', array_filter([
    (string)($reserva['nombre'] ?? ''),
    (string)($reserva['ap_paterno'] ?? ''),
    (string)($reserva['ap_materno'] ?? ''),
])));
if ($cliente === '') {
    $cliente = (string)($reserva['nombre_usuario'] ?? 'Cliente');
}
$puedeCancelar = in_array((string)($reserva['estado'] ?? ''), ['pendiente', 'confirmada'], true)
    && (float)($resumen['pagado'] ?? 0) <= 0
    && !in_array($estadoPago, ['por_verificar', 'anticipo_confirmado', 'parcial', 'pagado'], true);
$notaPago = match ($estadoPago) {
    'pendiente_efectivo' => 'Pago en efectivo pendiente: el personal registrará el pago cuando lo recibas en sucursal.',
    'por_verificar' => 'Comprobante recibido. El personal está verificando tu anticipo; la cita se confirmará al validarlo.',
    'anticipo_confirmado' => 'Anticipo confirmado. El saldo restante se paga en sucursal el día de tu cita.',
    'parcial' => 'Se registró un pago parcial. Aún queda un saldo por pagar en sucursal.',
    'pagado' => 'Pago completo registrado. ¡Gracias!',
    'rechazado' => 'El comprobante fue rechazado. Puedes enviar uno nuevo para que el personal lo verifique.',
    default => $esEfectivo
        ? 'Pago en efectivo pendiente en sucursal.'
        : 'Aún no se registró el anticipo. Envía tu comprobante para confirmar la cita.',
};
$tipoTratamiento = (string)($reserva['tipo_tratamiento'] ?? '');
$zonaTratamiento = (string)($reserva['zona_tratamiento'] ?? '');
$zonas = \App\Models\Servicio::zonasDepilacion();
if ($zonaTratamiento === 'otras' && !empty($respuestas['zona_otra'])) {
    $zonaTratamiento = (string)$respuestas['zona_otra'];
} elseif (isset($zonas[$zonaTratamiento])) {
    $zonaTratamiento = $zonas[$zonaTratamiento];
}
?>
<div class="page-head">
    <div class="container">
        <span class="eyebrow">Paso 3</span>
        <h1>Detalle de tu reserva</h1>
        <p>Guarda este comprobante. Te esperamos el día de tu cita.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <?php $paso = 3; require __DIR__ . '/_stepper.php'; ?>

        <div class="recibo">
            <div class="recibo-head">
                <?= logo(true) ?>
                <div class="nro">Reserva N°<strong>R-<?= str_pad((string)$idReserva, 6, '0', STR_PAD_LEFT) ?></strong></div>
            </div>
            <div class="recibo-body">
                <div class="recibo-grid">
                    <div><small>Cliente</small><span><?= e($cliente) ?></span></div>
                    <div><small>Estado de la cita</small><?= estado((string)($reserva['estado'] ?? 'pendiente')) ?></div>
                    <div><small>Fecha y hora</small><span><?= e(date('d/m/Y', strtotime((string)$reserva['fecha']))) ?> · <?= e(substr((string)$reserva['hora'], 0, 5)) ?></span></div>
                    <div><small>Sucursal</small><span><?= e($reserva['nombre_sucursal'] ?? 'Sucursal principal') ?></span></div>
                    <div><small>Método de pago</small><span><?= e($metodo ?? 'Por definir') ?></span></div>
                    <div><small>Estado del pago</small><?= estado_pago($estadoPago) ?></div>
                </div>

                <table class="table-simple">
                    <thead><tr><th>Servicio</th><th class="r">Duración</th><th class="r">Precio</th></tr></thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="3" class="muted">Sin detalle de servicio.</td></tr>
                        <?php else: foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['nombre'] ?? 'Servicio') ?></td>
                            <td class="r"><?= (int)($item['duracion_minutos'] ?? 0) ?> min</td>
                            <td class="r"><?= bs($item['subtotal'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <div class="recibo-total">
                    <div class="row"><span>Total del servicio</span><span><?= bs($resumen['total']) ?></span></div>
                    <div class="row"><span>Anticipo requerido</span><span><?= bs($resumen['anticipo']) ?></span></div>
                    <div class="row"><span>Monto pagado / verificado</span><span><?= bs($resumen['pagado']) ?></span></div>
                    <div class="row big"><span>Saldo pendiente</span><b><?= bs($resumen['saldo']) ?></b></div>
                </div>

                <div class="alert <?= in_array($estadoPago, ['pagado', 'anticipo_confirmado'], true) ? 'ok' : (in_array($estadoPago, ['rechazado'], true) ? 'warn' : 'info') ?>" style="margin-top:1.4rem">
                    <?= icon('shield', 20) ?>
                    <span><?= e($notaPago) ?></span>
                </div>

                <?php if (!empty($reserva['codigo_pago'])): ?>
                    <p class="hint muted" style="text-align:center;font-size:.8rem">Código de pago: <?= e($reserva['codigo_pago']) ?></p>
                <?php endif; ?>

                <?php if (!empty($reserva['comprobante'])): ?>
                    <p class="hint" style="text-align:center;font-size:.85rem">
                        <a class="link" href="<?= e(url_archivo($reserva['comprobante'])) ?>" target="_blank">Ver comprobante enviado</a>
                    </p>
                <?php endif; ?>

                <?php if (!empty($respuestas)): ?>
                <div style="margin-top:1.6rem">
                    <h3 style="font-size:1.15rem;margin-bottom:.8rem">Información de tu tratamiento</h3>
                    <div class="recibo-grid">
                        <?php if ($tipoTratamiento === 'depilacion'): ?>
                            <div><small>Zona</small><span><?= e($zonaTratamiento ?: '—') ?></span></div>
                            <div><small>Depilación previa</small><span><?= e(ucfirst((string)($respuestas['depil_previa'] ?? '—'))) ?></span></div>
                            <div><small>Piel sensible</small><span><?= e(ucfirst((string)($respuestas['sensibilidad'] ?? '—'))) ?></span></div>
                        <?php else: ?>
                            <div><small>Tipo de piel</small><span><?= e($respuestas['tipo_piel'] ?? '—') ?></span></div>
                            <div><small>Objetivos</small><span><?= e(implode(', ', (array)($respuestas['objetivos'] ?? []))) ?: '—' ?></span></div>
                            <div><small>Tratamiento facial previo</small><span><?= e(ucfirst((string)($respuestas['tratamiento_previo'] ?? '—'))) ?><?= !empty($respuestas['tratamiento_previo_detalle']) ? ' · ' . e($respuestas['tratamiento_previo_detalle']) : '' ?></span></div>
                        <?php endif; ?>
                        <div><small>Alergias</small><span><?= ($respuestas['alergias'] ?? 'no') === 'si' ? e($respuestas['alergias_detalle'] ?? 'Sí') : 'Ninguna' ?></span></div>
                        <?php if (!empty($respuestas['observaciones'])): ?>
                            <div style="grid-column:1 / -1"><small>Observaciones</small><span><?= e($respuestas['observaciones']) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <p class="recibo-note">
                    Este documento es un comprobante de reserva y no reemplaza a una factura.<br>
                    Cancelaciones o cambios con 24 horas de anticipación.
                </p>
            </div>
        </div>

        <?php if ($notaPago !== '' && in_array($estadoPago, ['sin_pago', 'rechazado'], true) && !$esEfectivo): ?>
            <div class="no-print" style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap">
                <a class="btn btn-primary" href="<?= ruta('reserva_pago', ['id' => $idReserva]) ?>"><?= icon('upload', 18) ?> Enviar comprobante del anticipo</a>
            </div>
        <?php endif; ?>

        <div class="no-print" style="display:flex;gap:1rem;justify-content:center;margin-top:2rem;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="window.print()"><?= icon('print', 18) ?> Imprimir / guardar PDF</button>
            <a class="btn btn-outline" href="<?= ruta('mis_reservas') ?>">Ver mis reservas</a>
            <?php if ($puedeCancelar): ?>
            <form method="post" action="<?= ruta('mis_reservas') ?>" data-confirm="¿Cancelar la reserva #<?= $idReserva ?>?" style="display:inline">
                <?= csrf() ?>
                <input type="hidden" name="accion" value="cancelar">
                <input type="hidden" name="id_reserva" value="<?= $idReserva ?>">
                <button class="btn btn-outline" type="submit"><?= icon('trash', 18) ?> Cancelar reserva</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($recomendados)): ?>
        <div style="margin-top:3rem">
            <div class="section-head" style="text-align:left">
                <h2 style="font-size:1.4rem">Productos recomendados para tu tratamiento</h2>
            </div>
            <div class="grid-4">
                <?php foreach ($recomendados as $r): ?>
                <article class="card">
                    <div class="producto-img"><?= frasco() ?></div>
                    <div class="producto-body">
                        <span class="cat"><?= e($r['nombre_categoria'] ?? '') ?></span>
                        <h3><?= e($r['nombre_producto']) ?></h3>
                        <div class="producto-foot">
                            <span class="precio-lg"><?= bs($r['precio']) ?></span>
                            <a class="btn btn-outline btn-sm" href="<?= ruta('producto', ['id' => $r['id_producto']]) ?>">Ver</a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
