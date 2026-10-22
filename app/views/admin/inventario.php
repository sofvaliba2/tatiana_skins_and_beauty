<?php
$titulo = 'Inventario';
$activo = 'admin_inventario';
require __DIR__ . '/../layout/admin_header.php';
$editar = $editar ?? null;
?>

<div class="page-title">
    <div>
        <h1>Inventario</h1>
        <p class="muted">Productos, precios y stock disponible.</p>
    </div>
    <a class="btn btn-primary" href="<?= ruta('admin_inventario', ['nuevo' => 1]) ?>"><?= icon('plus', 18) ?> Agregar producto</a>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="admin_inventario">
            <div class="search" style="flex:1">
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Buscar producto..." value="<?= e($q ?? '') ?>">
            </div>
            <button class="btn btn-outline" type="submit">Buscar</button>
            <?php if (($q ?? '') !== ''): ?><a class="btn btn-outline" href="<?= ruta('admin_inventario') ?>">Limpiar</a><?php endif; ?>
        </form>

        <?php if (empty($productos)): ?>
            <?= vacio(($q ?? '') !== '' ? 'Ningún producto coincide con la búsqueda.' : 'El inventario está vacío. Agrega el primer producto.', 'box') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($productos as $p): $bajo = (int)$p['cantidad'] <= 3; ?>
                <tr>
                    <td><div class="person"><span class="cart-thumb" style="width:40px;height:40px;border-radius:10px"><?= frasco() ?></span><b><?= e($p['nombre_producto']) ?></b></div></td>
                    <td><?= e($p['nombre_categoria'] ?? 'Sin categoría') ?></td>
                    <td><?= bs($p['precio']) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:.7rem">
                            <div class="stock-bar"><i class="<?= $bajo ? 'low' : '' ?>" style="width:<?= min(100, (int)$p['cantidad'] * 5) ?>%"></i></div>
                            <span class="badge-st <?= $bajo ? 'st-pendiente' : 'st-activo' ?>"><?= (int)$p['cantidad'] ?></span>
                        </div>
                    </td>
                    <td><div class="acts">
                        <a class="ic-btn" href="<?= ruta('admin_inventario', ['editar' => $p['id_producto'], 'q' => $q ?? '']) ?>" aria-label="Editar"><?= icon('edit', 16) ?></a>
                        <a class="ic-btn" href="<?= ruta('producto', ['id' => $p['id_producto']]) ?>" target="_blank" title="Ver en la tienda"><?= icon('eye', 16) ?></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?= paginacion('admin_inventario', ['q' => $q ?? ''], $total, $pagina, 10) ?>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= $editar !== null ? 'Editar producto' : 'Nuevo producto' ?></h3></div>
        <?php if ($editar !== null): $e1 = $editar; ?>
            <p class="muted" style="font-size:.88rem;margin-bottom:1rem">Editando: <b><?= e($e1['nombre_producto']) ?></b> (<?= e($e1['id_producto']) ?>)</p>
        <?php endif; ?>
        <form method="post" action="<?= ruta('admin_inventario') ?>" enctype="multipart/form-data">
            <?= csrf() ?>
            <?php if ($editar !== null): ?><input type="hidden" name="id_producto" value="<?= e($editar['id_producto']) ?>"><?php endif; ?>
            <div class="field"><label>Nombre *</label><input class="input" name="nombre_producto" required maxlength="80" placeholder="Ej: Sérum humectante" value="<?= e($editar['nombre_producto'] ?? '') ?>"></div>
            <div class="field"><label>Descripción</label><textarea class="input" name="descripcion" rows="2" maxlength="200" placeholder="Breve descripción..."><?= e($editar['descripcion'] ?? '') ?></textarea></div>
            <div class="form-grid">
                <div class="field"><label>Precio (Bs.) *</label><input class="input" type="number" step="0.01" min="0" name="precio" required value="<?= e($editar['precio'] ?? '') ?>"></div>
                <div class="field"><label>Cantidad *</label><input class="input" type="number" min="0" name="cantidad" required value="<?= e($editar['cantidad'] ?? '0') ?>"></div>
            </div>
            <div class="field"><label>Categoría</label>
                <select class="input" name="id_categoria">
                    <option value="">Sin categoría</option>
                    <?php foreach (($categorias ?? []) as $c): ?>
                        <option value="<?= e($c['id_categoria']) ?>" <?= (($editar['id_categoria'] ?? '') === $c['id_categoria']) ? 'selected' : '' ?>><?= e($c['nombre_categoria']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Imagen</label>
                <label class="upload" style="padding:1.2rem"><?= icon('upload', 24) ?><div class="hint">JPG, PNG o WEBP · máx. 2 MB</div><input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></label>
            </div>
            <div style="display:flex;gap:.6rem">
                <?php if ($editar !== null): ?>
                    <a href="<?= ruta('admin_inventario', ['q' => $q ?? '']) ?>" class="btn btn-outline" style="flex:1">Cancelar</a>
                <?php else: ?>
                    <button type="reset" class="btn btn-outline" style="flex:1">Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="flex:1"><?= $editar !== null ? 'Guardar' : 'Guardar producto' ?></button>
            </div>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
