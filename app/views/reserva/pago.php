<?php
$titulo = 'Pago del anticipo';
$activo = 'servicios';
require __DIR__ . '/../layout/header.php';

$idReserva = (int)($reserva['id_reserva'] ?? 0);
$qrImagen = trim((string)PAGO_QR_IMAGEN);
$qrSrc = '';
$qrDisponible = false;
if ($qrImagen !== '') {
    if (preg_match('#^https?://#i', $qrImagen)) {
        $qrSrc = $qrImagen;
        $qrDisponible = true;
    } else {
        $rel = ltrim(str_replace('\\', '/', $qrImagen), '/');
        $rel = preg_replace('#^public/#', '', $rel);
        if (is_file(BASE_PATH . '/public/' . $rel)) {
            $qrSrc = url_archivo($rel);
            $qrDisponible = true;
        }
    }
}
$digitales = array_values(array_filter($metodos ?? [], static fn (array $m): bool => (string)$m['id_metodoPago'] !== 'MP01'));
$item = $items[0] ?? null;
$metodoSel = (string)($_POST['id_metodoPago'] ?? '');
if ($metodoSel === '') {
    $metodoSel = $digitales !== [] ? (string)$digitales[0]['id_metodoPago'] : '';
}
?>
<div class="page-head">
    <div class="container">
        <span class="eyebrow">Paso 2</span>
        <h1>Paga tu anticipo</h1>
        <p>Transfiere el anticipo con los datos reales del centro y sube tu comprobante.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <?php $paso = 2; require __DIR__ . '/_stepper.php'; ?>

        <?php if (!empty($errores['_general'])): ?>
            <div class="alert warn"><?= e($errores['_general'][0]) ?></div>
        <?php endif; ?>

        <div class="book-layout" style="grid-template-columns:1fr 1fr">
            <div class="form-card qr-box">
                <span class="eyebrow">Anticipo a transferir</span>

                <?php if ($qrDisponible): ?>
                    <div class="qr">
                        <img src="<?= e($qrSrc) ?>" alt="QR de pago del centro" style="width:100%;height:100%;object-fit:contain">
                    </div>
                    <p class="muted" style="font-size:.85rem">Escanea el QR con la app de tu banco.</p>
                <?php else: ?>
                    <div class="alert warn" style="text-align:left;margin-top:1rem">
                        <?= icon('shield', 20) ?>
                        <span><b>QR real aún no configurado.</b> No mostramos un QR decorativo para no inducir a error. Configura <code>PAGO_QR_IMAGEN</code> (imagen del QR del banco) o usa los datos de transferencia que aparecen abajo.</span>
                    </div>
                <?php endif; ?>

                <div class="precio-lg" style="font-size:2.2rem"><?= bs($anticipo) ?></div>
                <p class="muted" style="font-size:.9rem;margin-top:.3rem">Anticipo · <?= e(PAGO_QR_TITULAR) ?></p>

                <?php if (PAGO_QR_BANCO !== '' || PAGO_QR_CUENTA !== '' || PAGO_QR_PAYLOAD !== ''): ?>
                    <div class="recibo-total" style="text-align:left;margin-top:1.2rem">
                        <?php if (PAGO_QR_BANCO !== ''): ?><div class="row"><span>Banco</span><span><?= e(PAGO_QR_BANCO) ?></span></div><?php endif; ?>
                        <?php if (PAGO_QR_CUENTA !== ''): ?><div class="row"><span>Cuenta</span><span><?= e(PAGO_QR_CUENTA) ?></span></div><?php endif; ?>
                        <?php if (PAGO_QR_PAYLOAD !== ''): ?><div class="row"><span>Datos</span><span style="word-break:break-all;max-width:60%;text-align:right"><?= e(PAGO_QR_PAYLOAD) ?></span></div><?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="summary-row" style="margin-top:1rem"><span>Total del servicio</span><span><?= bs($total) ?></span></div>
                <div class="summary-row"><span>Anticipo</span><span><?= bs($anticipo) ?></span></div>
                <div class="summary-row"><span>Saldo en sucursal</span><span><?= bs($saldo) ?></span></div>

                <div class="alert info" style="margin-top:1.2rem;text-align:left">
                    <?= icon('clock', 20) ?>
                    <span>Envía el comprobante para que el personal verifique el anticipo. La cita se confirma cuando el pago es validado.</span>
                </div>
            </div>

            <form class="form-card" method="post" enctype="multipart/form-data" action="<?= ruta('reserva_pago', ['id' => $idReserva]) ?>">
                <?= csrf() ?>
                <h3 style="margin-bottom:1.2rem">Tu reserva</h3>
                <div class="summary-row"><span>Servicio</span><span><?= e($item['nombre'] ?? '—') ?></span></div>
                <div class="summary-row"><span>Duración</span><span><?= (int)($item['duracion_minutos'] ?? 0) ?> min</span></div>
                <div class="summary-row"><span>Fecha</span><span><?= e(date('d/m/Y', strtotime((string)$reserva['fecha']))) ?> · <?= e(substr((string)$reserva['hora'], 0, 5)) ?></span></div>
                <div class="summary-row"><span>Sucursal</span><span><?= e($sucursal['nombre_sucursal'] ?? '—') ?></span></div>
                <div class="summary-row"><span>Código de pago</span><span><?= e($reserva['codigo_pago'] ?? '—') ?></span></div>
                <div class="summary-row total"><span>Total del servicio</span><strong><?= bs($total) ?></strong></div>
                <div class="summary-row"><span>Anticipo a verificar</span><span><?= bs($anticipo) ?></span></div>
                <div class="summary-row"><span>Saldo en sucursal</span><span><?= bs($saldo) ?></span></div>

                <div class="field" style="margin-top:1.4rem">
                    <label for="id_metodoPago">Método con el que transferiste</label>
                    <?php if ($digitales): ?>
                        <select class="input<?= clase_campo($errores, 'id_metodoPago') ?>" id="id_metodoPago" name="id_metodoPago">
                            <?php foreach ($digitales as $m): ?>
                                <option value="<?= e($m['id_metodoPago']) ?>" <?= $metodoSel === (string)$m['id_metodoPago'] ? 'selected' : '' ?>>
                                    <?= e($m['nombre_metodoPago']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="alert warn" style="margin:0">No hay métodos de pago digitales configurados. Contacta al centro para coordinar el anticipo.</div>
                    <?php endif; ?>
                    <?= error_campo($errores, 'id_metodoPago') ?>
                </div>

                <div class="field" style="margin-top:1.4rem">
                    <span class="label">Comprobante de pago</span>
                    <label class="upload">
                        <?= icon('upload', 34) ?>
                        <b style="color:var(--morado)">Sube la captura de tu pago</b>
                        <div class="hint">JPG, PNG o WEBP · máximo 2 MB</div>
                        <input type="file" name="comprobante" accept="image/png,image/jpeg,image/webp">
                    </label>
                    <?= error_campo($errores, 'comprobante') ?>
                </div>
                <button class="btn btn-primary btn-block" type="submit" <?= $digitales ? '' : 'disabled' ?>>Enviar comprobante y continuar</button>
                <p class="hint muted" style="text-align:center;margin-top:.8rem;font-size:.8rem">
                    El personal verificará tu pago y confirmará tu cita. No marcaremos el pago como recibido automáticamente.
                </p>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
