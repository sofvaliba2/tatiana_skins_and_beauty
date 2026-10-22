<?php
$titulo = 'Productos';
$activo = 'catalogo';
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Tienda</span>
        <h1>Catálogo de productos</h1>
        <p>Cosmética profesional seleccionada para el cuidado de tu piel.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <div class="toolbar">
            <form class="search" method="get" action="<?= BASE_URL ?>/index.php" style="flex:1">
                <input type="hidden" name="v" value="catalogo">
                <?php if (($cat ?? '') !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Buscar producto..." value="<?= e($q ?? '') ?>">
            </form>
            <div class="tabs">
                <a class="tab<?= ($cat ?? '') === '' ? ' active' : '' ?>" href="<?= ruta('catalogo') ?>">Todos</a>
                <?php foreach (($categorias ?? []) as $c): ?>
                    <a class="tab<?= ($cat ?? '') === $c['id_categoria'] ? ' active' : '' ?>"
                       href="<?= ruta('catalogo', ['cat' => $c['id_categoria']]) ?>"><?= e($c['nombre_categoria']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($productos)): ?>
            <?= vacio(($q ?? '') !== '' || ($cat ?? '') !== '' ? 'Ningún producto coincide con tu búsqueda.' : 'Aún no hay productos en el catálogo.', 'box') ?>
        <?php else: ?>
        <div class="grid-4">
            <?php foreach ($productos as $p): $stock = (int)$p['cantidad']; ?>
            <article class="card">
                <div class="producto-img">
                    <?php if (!empty($p['imagen_url']) && is_file(BASE_PATH . '/public/' . $p['imagen_url'])): ?>
                        <img src="<?= BASE_URL . '/' . e($p['imagen_url']) ?>" alt="<?= e($p['nombre_producto']) ?>" style="max-height:100%;border-radius:12px">
                    <?php else: ?>
                        <?= frasco() ?>
                    <?php endif; ?>
                    <?php if ($stock <= 0): ?>
                        <span class="tag low">Agotado</span>
                    <?php elseif ($stock <= 5): ?>
                        <span class="tag low">Últimas <?= $stock ?> unidades</span>
                    <?php endif; ?>
                </div>
                <div class="producto-body">
                    <span class="cat"><?= e($p['nombre_categoria'] ?? 'General') ?></span>
                    <h3><a href="<?= ruta('producto', ['id' => $p['id_producto']]) ?>" style="color:inherit;text-decoration:none"><?= e($p['nombre_producto']) ?></a></h3>
                    <p><?= e($p['descripcion'] ?? '') ?></p>
                    <div class="producto-foot">
                        <span class="precio-lg"><?= bs($p['precio']) ?></span>
                        <?php if ($stock > 0): ?>
                            <form method="post" action="<?= ruta('agregar_carrito') ?>" style="display:inline">
                                <?= csrf() ?>
                                <input type="hidden" name="id_producto" value="<?= e($p['id_producto']) ?>">
                                <input type="hidden" name="cantidad" value="1">
                                <button class="btn btn-primary btn-sm" type="submit" title="Añadir al carrito"><?= icon('plus', 16) ?> Agregar</button>
                            </form>
                        <?php else: ?>
                            <a class="btn btn-outline btn-sm" href="<?= ruta('producto', ['id' => $p['id_producto']]) ?>">Ver</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?= paginacion('catalogo', ['q' => $q ?? '', 'cat' => $cat ?? ''], $total, $pagina, 8) ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
