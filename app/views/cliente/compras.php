<?php
$titulo = 'Mis compras';
$activo = 'panel';
$carrito = count($_SESSION['carrito'] ?? []);
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container">
        <div class="section-head" style="text-align:left">
            <span class="eyebrow">Historial de compras</span>
            <h2>Mis compras</h2>
        </div>

        <?php if (empty($compras)): ?>
            <?= vacio('Aún no has comprado productos. ¡Visita el catálogo!', 'cart') ?>
            <div style="text-align:center;margin-top:1rem"><a href="<?= ruta('catalogo') ?>" class="btn btn-primary">Ver catálogo</a></div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr><th>Fecha</th><th>N° de compra</th><th>Método de pago</th><th>Ítems</th><th>Total</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($compras as $c): ?>
                    <tr>
                        <td><?= e(date('d/m/Y', strtotime($c['fecha_compra']))) ?></td>
                        <td><?= e($c['nro_compra'] ?? '#' . $c['id_compra']) ?></td>
                        <td><?= e($c['nombre_metodoPago'] ?? 'Por definir') ?></td>
                        <td><?= (int)$c['items'] ?></td>
                        <td><?= bs($c['total_pagar']) ?></td>
                        <td><a class="btn btn-outline btn-sm" href="<?= ruta('detalle_compra', ['id' => $c['id_compra']]) ?>">Ver detalle</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?= paginacion('mis_compras', [], $total, $pagina, $porPagina) ?>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
