<?php
$titulo = 'Pago de productos';
$activo = 'catalogo';
$items       = $items ?? [];
$total       = (float)($total ?? 0);
$digitales   = $digitales ?? [];
$sucursales  = $sucursales ?? [];
$errores     = $errores ?? [];
$idMetodo    = (string)($idMetodo ?? '');
$idSucursal  = (string)($idSucursal ?? '');
require __DIR__ . '/../layout/header.php';

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
if ($idMetodo === '' && $digitales !== []) {
    $idMetodo = (string)$digitales[0]['id_metodoPago'];
}
if ($idSucursal === '' && $sucursales !== []) {
    $idSucursal = (string)$sucursales[0]['id_sucursal'];
}
?>
<div class="page-head">
    <div class="container">
        <span class="eyebrow">Paso 2</span>
        <h1>Paga tu pedido</h1>
        <p>Transfiere el total con los datos reales del centro y sube tu comprobante.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <p style="margin-bottom:1.2rem"><?= enlace_volver('carrito', [], 'Volver a mi carrito') ?></p>

        <?php if (!empty($errores['_general'])): ?>
            <div class="alert warn"><?= e($errores['_general'][0]) ?></div>
        <?php endif; ?>

        <div class="book-layout" style="grid-template-columns:1fr 1fr">
            <div class="form-card qr-box">
                <span class="eyebrow">Total a transferir</span>

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

                <div class="precio-lg" style="font-size:2.2rem"><?= bs($total) ?></div>
                <p class="muted" style="font-size:.9rem;margin-top:.3rem">Total del pedido · <?= e(PAGO_QR_TITULAR) ?></p>

                <?php if (PAGO_QR_BANCO !== '' || PAGO_QR_CUENTA !== '' || PAGO_QR_PAYLOAD !== ''): ?>
                    <div class="recibo-total" style="text-align:left;margin-top:1.2rem">
                        <?php if (PAGO_QR_BANCO !== ''): ?><div class="row"><span>Banco</span><span><?= e(PAGO_QR_BANCO) ?></span></div><?php endif; ?>
                        <?php if (PAGO_QR_CUENTA !== ''): ?><div class="row"><span>Cuenta</span><span><?= e(PAGO_QR_CUENTA) ?></span></div><?php endif; ?>
                        <?php if (PAGO_QR_PAYLOAD !== ''): ?><div class="row"><span>Datos</span><span style="word-break:break-all;max-width:60%;text-align:right"><?= e(PAGO_QR_PAYLOAD) ?></span></div><?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="alert info" style="margin-top:1.2rem;text-align:left">
                    <?= icon('clock', 20) ?>
                    <span>Envía el comprobante para que el personal verifique tu pago. El pedido se prepara cuando el pago es validado.</span>
                </div>
            </div>

            <form class="form-card" method="post" enctype="multipart/form-data" action="<?= ruta('checkout') ?>">
                <?= csrf() ?>
                <h3 style="margin-bottom:1.2rem">Resumen del pedido</h3>

                <?php foreach ($items as $item): ?>
                    <div class="summary-row">
                        <span><?= e($item['nombre']) ?> × <?= (int)$item['cantidad'] ?></span>
                        <span><?= bs($item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="summary-row total"><span>Total del pedido</span><strong><?= bs($total) ?></strong></div>

                <div class="field" style="margin-top:1.4rem">
                    <label for="sucursal_retiro">Sucursal de retiro</label>
                    <?php if ($sucursales): ?>
                        <select class="input<?= clase_campo($errores, 'sucursal_retiro') ?>" id="sucursal_retiro" name="sucursal_retiro">
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= e($s['id_sucursal']) ?>" <?= $idSucursal === (string)$s['id_sucursal'] ? 'selected' : '' ?>>
                                    <?= e($s['nombre_sucursal']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="alert warn" style="margin:0">No hay sucursales configuradas. Contacta al centro para coordinar tu pedido.</div>
                    <?php endif; ?>
                    <?= error_campo($errores, 'sucursal_retiro') ?>
                </div>

                <div class="field" style="margin-top:1.4rem">
                    <label for="id_metodoPago">Método con el que transferiste</label>
                    <?php if ($digitales): ?>
                        <select class="input<?= clase_campo($errores, 'id_metodoPago') ?>" id="id_metodoPago" name="id_metodoPago">
                            <?php foreach ($digitales as $m): ?>
                                <option value="<?= e($m['id_metodoPago']) ?>" <?= $idMetodo === (string)$m['id_metodoPago'] ? 'selected' : '' ?>>
                                    <?= e($m['nombre_metodoPago']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="alert warn" style="margin:0">No hay métodos de pago digitales configurados. Contacta al centro para coordinar tu compra.</div>
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
                <button class="btn btn-primary btn-block" type="submit" <?= ($digitales && $sucursales) ? '' : 'disabled' ?>>Enviar comprobante y finalizar</button>
                <p class="hint muted" style="text-align:center;margin-top:.8rem;font-size:.8rem">
                    El personal verificará tu pago antes de entregar el pedido. No marcaremos el pago como recibido automáticamente.
                </p>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
