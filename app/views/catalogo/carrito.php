<?php
$titulo  = 'Mi carrito';
$activo  = 'catalogo';
$items   = $items ?? [];
$total   = 0;
foreach ($items as $i) {
    $total += (float)$i['subtotal'];
}
$errores     = $errores ?? [];
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Tu selección</span>
        <h1>Mi carrito</h1>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <p style="margin-bottom:1.2rem"><?= enlace_volver('catalogo', [], 'Volver al catálogo') ?></p>
    </div>
    <div class="container cart-layout">
        <div class="card card-pad">
            <?php if ($errores): ?>
                <div class="alert warn"><?= e(is_array($errores) ? implode(' ', array_map('strval', $errores)) : (string)$errores) ?></div>
            <?php endif; ?>

            <?php if (!$items): ?>
                <div style="text-align:center;padding:2rem">
                    <?= icon('cart', 40) ?>
                    <p class="muted" style="margin:.8rem 0 1.2rem">Tu carrito está vacío.</p>
                    <a class="btn btn-primary" href="<?= ruta('catalogo') ?>">Ver productos</a>
                </div>
            <?php endif; ?>

            <?php foreach ($items as $i):
                $img = trim((string)($i['imagen_url'] ?? ''));
                $imgRel = ltrim(str_replace('\\', '/', $img), '/');
                $imgRel = preg_replace('#^public/#', '', $imgRel);
                $tieneImg = $img !== '' && (preg_match('#^https?://#i', $img) || is_file(BASE_PATH . '/public/' . $imgRel));
                $src = preg_match('#^https?://#i', $img) ? $img : url_archivo($imgRel);
            ?>
            <div class="cart-item">
                <div class="cart-thumb">
                    <?php if ($tieneImg): ?>
                        <img src="<?= e($src) ?>" alt="<?= e($i['nombre']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
                    <?php else: ?>
                        <?= frasco() ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 style="font-size:1.2rem"><?= e($i['nombre']) ?></h3>
                    <?php if (!empty($i['descripcion'])): ?>
                        <span class="muted" style="font-size:.82rem;display:block"><?= e(mb_strimwidth((string)$i['descripcion'], 0, 90, '…')) ?></span>
                    <?php endif; ?>
                    <span class="muted" style="font-size:.85rem"><?= bs($i['precio']) ?> c/u · Stock: <?= (int)$i['stock'] ?></span>
                    <form method="post" action="<?= ruta('actualizar_carrito') ?>" style="margin-top:.4rem">
                        <?= csrf() ?>
                        <input type="hidden" name="id_producto" value="<?= e($i['id']) ?>">
                        <div class="qty">
                            <button type="submit" name="delta" value="-1" aria-label="Disminuir cantidad">−</button>
                            <span><?= (int)$i['cantidad'] ?></span>
                            <button type="submit" name="delta" value="1" aria-label="Aumentar cantidad">+</button>
                        </div>
                    </form>
                </div>
                <div style="text-align:right">
                    <div class="precio-lg"><?= bs($i['subtotal']) ?></div>
                    <form method="post" action="<?= ruta('quitar_carrito') ?>" style="margin-top:.5rem;display:flex;justify-content:flex-end" data-confirm="¿Quitar <?= e($i['nombre']) ?> del carrito?">
                        <?= csrf() ?>
                        <input type="hidden" name="id_producto" value="<?= e($i['id']) ?>">
                        <button class="ic-btn del" type="submit" aria-label="Quitar <?= e($i['nombre']) ?>"><?= icon('trash', 16) ?></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <aside class="card card-pad summary">
            <h3 style="margin-bottom:1rem">Resumen</h3>
            <div class="summary-row"><span>Artículos</span><span><?= count($items) ?></span></div>
            <div class="summary-row"><span>Subtotal</span><span><?= bs($total) ?></span></div>
            <div class="summary-row"><span>Retiro en sucursal</span><span>Gratis</span></div>
            <div class="summary-row total"><span>Total</span><strong><?= bs($total) ?></strong></div>

            <?php if ($items): ?>
            <a href="<?= ruta('checkout') ?>" class="btn btn-primary btn-block" style="margin-top:1.2rem">
                Continuar al pago
            </a>
            <p class="hint muted" style="text-align:center;margin-top:.6rem;font-size:.8rem">
                Verás el QR y los datos bancarios para transferir y subir tu comprobante.
            </p>
            <?php endif; ?>

            <a href="<?= ruta('catalogo') ?>" class="btn btn-outline btn-block" style="margin-top:.6rem">Seguir comprando</a>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
