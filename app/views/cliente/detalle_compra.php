<?php
$titulo = 'Compra ' . ($compra['nro_compra'] ?? '#' . $compra['id_compra']);
$activo = 'panel';
$carrito = count($_SESSION['carrito'] ?? []);
$totalItems = array_sum(array_column($items, 'cantidad'));

// Verificación de integridad del comprobante contra los datos de MySQL:
// cantidad × precio unitario = subtotal  y  suma de subtotales = total.
$sumaSubtotales = 0.0;
$itemsConError = 0;
foreach ($items as $i) {
    $cant = (int)($i['cantidad'] ?? 0);
    $unit = (float)($i['precio_unitario'] ?? 0);
    $sub  = (float)($i['subtotal'] ?? 0);
    $sumaSubtotales += $sub;
    if ($cant <= 0 || $unit < 0 || abs(round($unit * $cant, 2) - $sub) >= 0.01) {
        $itemsConError++;
    }
}
$totalCompra = (float)($compra['total_pagar'] ?? 0);
$cuadra = $itemsConError === 0 && abs(round($sumaSubtotales, 2) - $totalCompra) < 0.01;
$esEmpleado = \App\Core\Auth::esEmpleado();
$esAdmin = \App\Core\Auth::esAdmin();
$volverRuta = $esEmpleado ? ruta('empleado_ventas') : ($esAdmin ? ruta('admin_dashboard') : ruta('mis_compras'));
$volverTexto = $esEmpleado ? 'Ventas y pagos' : ($esAdmin ? 'Panel' : 'Mis compras');
$seguirRuta = $esEmpleado ? ruta('empleado_ventas') : ruta('catalogo');
$seguirTexto = $esEmpleado ? 'Volver a ventas' : 'Seguir comprando';
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container" style="max-width:760px">
        <p class="muted" style="font-size:.9rem;margin-bottom:1rem">
            <a href="<?= $volverRuta ?>"><?= e($volverTexto) ?></a> / <?= e($compra['nro_compra'] ?? '#' . $compra['id_compra']) ?>
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
                <?= vacio('Esta compra no tiene ítems registrados.', 'box') ?>
            <?php else: ?>
                <table class="table" style="margin-top:1.5rem">
                    <thead><tr><th>Ítem</th><th style="text-align:center">Cant.</th><th style="text-align:right">P. unitario</th><th style="text-align:right">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $i): ?>
                        <?php
                        $nombreItem = (string)($i['nombre'] ?? $i['nombre_producto'] ?? $i['nombre_servicio'] ?? 'Ítem');
                        $esServicio = ($i['tipo'] ?? 'producto') === 'servicio';
                        ?>
                        <?php
                        $imgItem = !$esServicio ? trim((string)($i['imagen_url'] ?? '')) : '';
                        $tieneImg = $imgItem !== '' && (preg_match('#^https?://#i', $imgItem) || is_file(BASE_PATH . '/public/' . $imgItem));
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:.7rem">
                                    <?php if ($tieneImg): ?>
                                        <img src="<?= e(url_archivo($imgItem)) ?>" alt="<?= e($nombreItem) ?>" style="width:38px;height:38px;object-fit:cover;border-radius:8px;flex:none">
                                    <?php else: ?>
                                        <span style="width:38px;height:38px;border-radius:8px;background:var(--lila-claro);display:flex;align-items:center;justify-content:center;color:var(--morado);flex:none"><?= icon($esServicio ? 'sparkle' : 'box', 18) ?></span>
                                    <?php endif; ?>
                                    <span>
                                        <?= e($nombreItem) ?>
                                        <small class="muted" style="display:block;font-size:.72rem"><?= $esServicio ? 'Servicio' : 'Producto' ?></small>
                                    </span>
                                </div>
                            </td>
                            <td style="text-align:center"><?= (int)$i['cantidad'] ?></td>
                            <td style="text-align:right"><?= bs($i['precio_unitario']) ?></td>
                            <td style="text-align:right"><?= bs($i['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right"><b><?= $totalItems ?> ítem<?= $totalItems > 1 ? 's' : '' ?></b></td>
                            <td style="text-align:right"><b><?= bs($compra['total_pagar']) ?></b></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>

            <?php if (!$cuadra): ?>
                <div class="alert warn" style="margin-top:1.2rem">
                    <?= icon('shield', 18) ?>
                    <span>Este comprobante presenta diferencias entre sus ítems y el total registrado. Pide al personal verificarlo antes de usarlo como referencia.</span>
                </div>
            <?php elseif (!empty($items)): ?>
                <p class="muted" style="font-size:.82rem;margin-top:1rem;display:flex;align-items:center;gap:.4rem">
                    <?= icon('check', 14) ?> Comprobante verificado: cantidades, precios y total coinciden con el registro en el sistema.
                </p>
            <?php endif; ?>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap">
                <button class="btn btn-outline" onclick="window.print()"><?= icon('print', 18) ?> Imprimir comprobante</button>
                <a href="<?= $seguirRuta ?>" class="btn btn-outline"><?= e($seguirTexto) ?></a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
