<?php
$titulo = 'Compra ' . ($compra['nro_compra'] ?? '#' . $compra['id_compra']);
$activo = 'panel';
$carrito = count($_SESSION['carrito'] ?? []);
$totalItems = array_sum(array_column($items, 'cantidad'));
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:760px">
        <p class="muted" style="font-size:.9rem;margin-bottom:1rem">
            <a href="<?= ruta('mis_compras') ?>">Mis compras</a> / <?= e($compra['nro_compra'] ?? '#' . $compra['id_compra']) ?>
        </p>
        <div class="card" style="padding:2rem">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;align-items:flex-start">
                <div>
                    <h1 style="font-size:1.6rem;margin:0"><?= e($compra['nro_compra'] ?? 'Compra #' . $compra['id_compra']) ?></h1>
                    <p class="muted" style="margin:.25rem 0 0"><?= e(date('d/m/Y', strtotime($compra['fecha_compra']))) ?> · <?= e($compra['nombre'] ?? '') ?> <?= e($compra['ap_paterno'] ?? '') ?></p>
                </div>
                <div style="text-align:right">
                    <p class="muted" style="margin:0;font-size:.9rem">Método de pago</p>
                    <p style="margin:0;font-weight:500"><?= e($compra['nombre_metodoPago'] ?? 'Por definir') ?></p>
                </div>
            </div>

            <?php if (empty($items)): ?>
                <?= vacio('Esta compra no tiene productos registrados.', 'box') ?>
            <?php else: ?>
                <table class="table" style="margin-top:1.5rem">
                    <thead><tr><th>Producto</th><th style="text-align:center">Cant.</th><th style="text-align:right">P. unitario</th><th style="text-align:right">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td><?= e($i['nombre_producto']) ?></td>
                            <td style="text-align:center"><?= (int)$i['cantidad'] ?></td>
                            <td style="text-align:right"><?= bs($i['precio_unitario']) ?></td>
                            <td style="text-align:right"><?= bs($i['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right"><b><?= $totalItems ?> producto<?= $totalItems > 1 ? 's' : '' ?></b></td>
                            <td style="text-align:right"><b><?= bs($compra['total_pagar']) ?></b></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap">
                <button class="btn btn-outline" onclick="window.print()"><?= icon('print', 18) ?> Imprimir comprobante</button>
                <a href="<?= ruta('catalogo') ?>" class="btn btn-outline">Seguir comprando</a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
