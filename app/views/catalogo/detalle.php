<?php
$titulo = $producto['nombre_producto'];
$activo = 'catalogo';
$carrito = count($_SESSION['carrito'] ?? []);
require __DIR__ . '/../layout/header.php';
?>
<section class="section">
    <div class="container">
        <p class="muted" style="font-size:.9rem;margin-bottom:1rem">
            <a href="<?= ruta('catalogo') ?>">Catálogo</a> /
            <?php if ($producto['id_categoria'] !== null): ?>
                <a href="<?= ruta('catalogo', ['cat' => $producto['id_categoria']]) ?>"><?= e($producto['nombre_categoria'] ?? 'Categoría') ?></a> /
            <?php endif; ?>
            <?= e($producto['nombre_producto']) ?>
        </p>

        <div style="display:grid;grid-template-columns:minmax(240px,380px) 1fr;gap:2.5rem;align-items:start" class="detalle-grid">
            <div class="card" style="padding:2rem;text-align:center">
                <div class="producto-img" style="height:240px;display:flex;align-items:center;justify-content:center">
                    <?php if (!empty($producto['imagen_url']) && is_file(BASE_PATH . '/public/' . $producto['imagen_url'])): ?>
                        <img src="<?= BASE_URL . '/' . e($producto['imagen_url']) ?>" alt="<?= e($producto['nombre_producto']) ?>" style="max-height:100%;border-radius:12px">
                    <?php else: ?>
                        <?= frasco() ?>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <?php if ($producto['nombre_categoria'] !== null): ?>
                    <span class="cat" style="font-size:.85rem;color:var(--morado)"><?= e($producto['nombre_categoria']) ?></span>
                <?php endif; ?>
                <h1 style="font-size:2rem;margin:.25rem 0 .75rem"><?= e($producto['nombre_producto']) ?></h1>
                <p class="muted"><?= e($producto['descripcion'] ?? '') ?></p>
                <div class="precio-lg" style="font-size:2rem;margin:1.25rem 0"><?= bs($producto['precio']) ?></div>

                <?php if ((int)$producto['cantidad'] > 0): ?>
                    <p style="color:#2e7d32;font-size:.92rem;margin-bottom:1rem"><?= icon('check', 15) ?> <?= (int)$producto['cantidad'] ?> unidad<?= (int)$producto['cantidad'] > 1 ? 'es' : '' ?> disponible<?= (int)$producto['cantidad'] > 1 ? 's' : '' ?></p>
                    <form method="post" action="<?= ruta('agregar_carrito') ?>" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
                        <?= csrf() ?>
                        <input type="hidden" name="id_producto" value="<?= e($producto['id_producto']) ?>">
                        <div class="field" style="margin:0">
                            <label for="cantidad">Cantidad</label>
                            <input class="input" style="width:90px" type="number" id="cantidad" name="cantidad" min="1" max="<?= (int)$producto['cantidad'] ?>" value="1">
                        </div>
                        <button class="btn btn-primary" type="submit"><?= icon('cart', 18) ?> Añadir al carrito</button>
                    </form>
                <?php else: ?>
                    <div class="alert warn">Producto agotado por el momento.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($relacionados)): ?>
        <div style="margin-top:3rem">
            <div class="section-head" style="text-align:left">
                <h2 style="font-size:1.4rem">También te puede interesar</h2>
            </div>
            <div class="grid-4">
                <?php foreach ($relacionados as $r): ?>
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
