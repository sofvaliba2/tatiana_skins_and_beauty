<?php
$titulo  = 'Mi carrito';
$activo  = 'catalogo';
$items   = $items ?? [];
$carrito = count($items);
$total   = 0;
foreach ($items as $i) { $total += $i['precio'] * $i['cantidad']; }
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Tu selección</span>
        <h1>Mi carrito</h1>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container cart-layout">
        <div class="card card-pad">
            <?php if (!$items): ?>
                <p class="muted" style="text-align:center;padding:2rem">Tu carrito está vacío.</p>
            <?php endif; ?>
            <?php foreach ($items as $i): ?>
            <div class="cart-item">
                <div class="cart-thumb"><?= frasco() ?></div>
                <div>
                    <h3 style="font-size:1.2rem"><?= e($i['nombre']) ?></h3>
                    <span class="muted" style="font-size:.85rem">Bs. <?= number_format($i['precio'], 2) ?> c/u</span><br>
                    <div class="qty">
                        <button type="button">−</button><span><?= (int)$i['cantidad'] ?></span><button type="button">+</button>
                    </div>
                </div>
                <div style="text-align:right">
                    <div class="precio-lg">Bs. <?= number_format($i['precio'] * $i['cantidad'], 2) ?></div>
                    <button class="ic-btn del" style="margin:.5rem 0 0 auto" aria-label="Quitar"><?= icon('trash', 16) ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <aside class="card card-pad summary">
            <h3 style="margin-bottom:1rem">Resumen</h3>
            <div class="summary-row"><span>Subtotal</span><span>Bs. <?= number_format($total, 2) ?></span></div>
            <div class="summary-row"><span>Retiro en sucursal</span><span>Gratis</span></div>
            <div class="summary-row total"><span>Total</span><strong>Bs. <?= number_format($total, 2) ?></strong></div>

            <div class="field" style="margin-top:1.2rem">
                <label>Método de pago</label>
                <select class="input">
                    <option>QR del centro estético</option>
                    <option>Efectivo en sucursal</option>
                </select>
            </div>
            <div class="field">
                <label>Sucursal de retiro</label>
                <select class="input">
                    <?php foreach (($sucursales ?? []) as $s): ?><option><?= e($s) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary btn-block">Confirmar compra</button>
            <a href="<?= ruta('catalogo') ?>" class="btn btn-outline btn-block" style="margin-top:.6rem">Seguir comprando</a>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
