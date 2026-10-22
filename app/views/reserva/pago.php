<?php
$titulo = 'Pago del anticipo';
$activo = 'servicios';
require __DIR__ . '/../layout/header.php';

/**
 * QR de ejemplo (patrón decorativo). Sustituir por la imagen real del QR
 * del centro estético: <img src="img/qr_tatiana.png">
 */
function qr_demo(): string
{
    $n = 25; $c = 8; $out = '';
    mt_srand(2026);
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $esquina = ($x < 7 && $y < 7) || ($x > $n - 8 && $y < 7) || ($x < 7 && $y > $n - 8);
            if ($esquina) { continue; }
            if (mt_rand(0, 100) > 52) { $out .= '<rect x="' . $x * $c . '" y="' . $y * $c . '" width="' . $c . '" height="' . $c . '"/>'; }
        }
    }
    foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$x, $y]) {
        $X = $x * $c; $Y = $y * $c;
        $out .= '<rect x="' . $X . '" y="' . $Y . '" width="' . 7 * $c . '" height="' . 7 * $c . '"/>'
              . '<rect x="' . ($X + $c) . '" y="' . ($Y + $c) . '" width="' . 5 * $c . '" height="' . 5 * $c . '" fill="#fff"/>'
              . '<rect x="' . ($X + 2 * $c) . '" y="' . ($Y + 2 * $c) . '" width="' . 3 * $c . '" height="' . 3 * $c . '"/>';
    }
    return '<svg viewBox="0 0 ' . $n * $c . ' ' . $n * $c . '" fill="#54458e">' . $out . '</svg>';
}
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Paso 2</span>
        <h1>Paga tu anticipo</h1>
        <p>Escanea el QR, realiza el pago y sube tu comprobante.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <?php $paso = 2; require __DIR__ . '/_stepper.php'; ?>

        <div class="book-layout" style="grid-template-columns:1fr 1fr">
            <div class="form-card qr-box">
                <span class="eyebrow">Escanea para pagar</span>
                <div class="qr"><?= qr_demo() ?></div>
                <div class="precio-lg" style="font-size:2.2rem">Bs. 30.00</div>
                <p class="muted" style="font-size:.9rem;margin-top:.3rem">Tatiana Skin and Beauty</p>
                <div class="alert info" style="margin-top:1.4rem;text-align:left">
                    <?= icon('clock', 20) ?>
                    <span>Tu horario queda reservado por <b>30 minutos</b> mientras envías el comprobante.</span>
                </div>
            </div>

            <form class="form-card" method="post" enctype="multipart/form-data" action="<?= ruta('reserva_formulario') ?>">
                <?= csrf() ?>
                <h3 style="margin-bottom:1.2rem">Tu reserva</h3>
                <?php $it = $items[0] ?? null; ?>
                <div class="summary-row"><span>Servicio</span><span><?= e($it['nombre'] ?? '—') ?></span></div>
                <div class="summary-row"><span>Duración</span><span><?= (int)($it['duracion_minutos'] ?? 0) ?> min</span></div>
                <div class="summary-row"><span>Fecha</span><span><?= e(date('d/m/Y', strtotime($reserva['fecha']))) ?> · <?= e(substr((string)$reserva['hora'], 0, 5)) ?></span></div>
                <div class="summary-row"><span>Sucursal</span><span><?= e($sucursal['nombre_sucursal'] ?? '—') ?></span></div>
                <div class="summary-row total"><span>Total del servicio</span><strong>Bs. <?= number_format((float)$total, 0) ?></strong></div>

                <div class="field" style="margin-top:1.4rem">
                    <label>Método de pago con el que transferiste</label>
                    <?php if ($metodos): ?>
                        <select class="input<?= clase_campo($errores, 'id_metodoPago') ?>" name="id_metodoPago" required>
                            <option value="">— Selecciona —</option>
                            <?php foreach ($metodos as $m): ?>
                                <option value="<?= e($m['id_metodoPago']) ?>" <?= (($_POST['id_metodoPago'] ?? '') === $m['id_metodoPago']) ? 'selected' : '' ?>>
                                    <?= e($m['nombre_metodoPago']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p class="hint">Aún no hay métodos de pago registrados; el personal te indicará cómo pagar.</p>
                    <?php endif; ?>
                    <?= error_campo($errores, 'id_metodoPago') ?>
                </div>

                <div class="field" style="margin-top:1.4rem">
                    <span class="label">Comprobante de pago</span>
                    <label class="upload">
                        <?= icon('upload', 34) ?>
                        <b style="color:var(--morado)">Sube la captura de tu pago</b>
                        <div class="hint">JPG o PNG · máximo 2 MB</div>
                        <input type="file" name="comprobante" accept="image/png,image/jpeg" required>
                    </label>
                    <?= error_campo($errores, 'comprobante') ?>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Enviar comprobante y continuar</button>
                <p class="hint muted" style="text-align:center;margin-top:.8rem;font-size:.8rem">
                    El personal verificará tu pago y confirmará tu cita.
                </p>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
