<?php
$titulo = 'Servicios';
$activo = 'admin_servicios';
require __DIR__ . '/../layout/admin_header.php';
$editar = $editar ?? null;
?>

<div class="page-title">
    <div>
        <h1>Servicios</h1>
        <p class="muted">Catálogo de tratamientos y categorías del centro.</p>
    </div>
    <a class="btn btn-primary" href="<?= ruta('admin_servicios', ['nuevo' => 1]) ?>"><?= icon('plus', 18) ?> Nuevo servicio</a>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="admin_servicios">
            <div class="search" style="flex:1">
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Buscar servicio..." value="<?= e($q ?? '') ?>">
            </div>
            <button class="btn btn-outline" type="submit">Buscar</button>
        </form>

        <?php if (empty($servicios)): ?>
            <?= vacio('Aún no hay servicios. Crea el primero en el formulario lateral.', 'sparkle') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Código</th><th>Servicio</th><th>Categoría</th><th>Duración</th><th>Precio</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($servicios as $s): ?>
                <tr>
                    <td><?= e($s['id_servicio']) ?></td>
                    <td><b><?= e($s['nombre']) ?></b><br><small class="muted"><?= e(mb_strimwidth((string)($s['descripcion'] ?? ''), 0, 60, '…')) ?></small></td>
                    <td><?= e($s['categoria'] ?? '—') ?></td>
                    <td><?= (int)$s['duracion_minutos'] ?> min</td>
                    <td><b><?= bs($s['precio']) ?></b></td>
                    <td><div class="acts">
                        <a class="ic-btn" href="<?= ruta('admin_servicios', ['editar' => $s['id_servicio'], 'q' => $q ?? '']) ?>" aria-label="Editar"><?= icon('edit', 16) ?></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?= paginacion('admin_servicios', ['q' => $q ?? ''], $total, $pagina, 10) ?>
        <?php endif; ?>

        <div class="panel-head" style="margin-top:2rem"><h3>Categorías de servicios</h3></div>
        <?php if (empty($categorias)): ?>
            <p class="muted" style="font-size:.9rem">Sin categorías todavía.</p>
        <?php else: ?>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem">
                <?php foreach ($categorias as $c): ?>
                    <span class="badge-st st-activo"><?= e($c['nombre_cservicio']) ?> <small>(<?= e($c['id_categoriaS']) ?>)</small></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= ruta('admin_servicios') ?>" style="display:flex;gap:.6rem;align-items:flex-end">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="categoria">
            <div class="field" style="flex:1;margin:0">
                <label>Nueva categoría</label>
                <input class="input" name="nombre_cservicio" required maxlength="100" placeholder="Ej: Faciales">
            </div>
            <button class="btn btn-outline" type="submit">Crear</button>
        </form>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= $editar !== null ? 'Editar servicio' : 'Nuevo servicio' ?></h3></div>
        <form method="post" action="<?= ruta('admin_servicios') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="servicio">
            <?php if ($editar !== null): ?><input type="hidden" name="id_servicio" value="<?= e($editar['id_servicio']) ?>"><?php endif; ?>
            <div class="field"><label>Nombre *</label><input class="input" name="nombre" required maxlength="80" placeholder="Ej: Hidrafacial" value="<?= e($editar['nombre'] ?? '') ?>"></div>
            <div class="field"><label>Descripción</label><textarea class="input" name="descripcion" rows="2" maxlength="200" placeholder="Qué incluye el tratamiento..."><?= e($editar['descripcion'] ?? '') ?></textarea></div>
            <div class="form-grid">
                <div class="field"><label>Precio (Bs.) *</label><input class="input" type="number" step="0.01" min="0" name="precio" required value="<?= e($editar['precio'] ?? '') ?>"></div>
                <div class="field"><label>Duración (min) *</label><input class="input" type="number" min="5" step="5" name="duracion_minutos" required value="<?= e($editar['duracion_minutos'] ?? '30') ?>"></div>
            </div>
            <div class="field"><label>Categoría</label>
                <select class="input" name="id_categoriaS">
                    <option value="">Sin categoría</option>
                    <?php foreach (($categorias ?? []) as $c): ?>
                        <option value="<?= e($c['id_categoriaS']) ?>" <?= (($editar['id_categoriaS'] ?? '') === $c['id_categoriaS']) ? 'selected' : '' ?>><?= e($c['nombre_cservicio']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:.6rem;margin-top:1rem">
                <?php if ($editar !== null): ?>
                    <a href="<?= ruta('admin_servicios', ['q' => $q ?? '']) ?>" class="btn btn-outline" style="flex:1">Cancelar</a>
                <?php else: ?>
                    <button type="reset" class="btn btn-outline" style="flex:1">Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="flex:1"><?= $editar !== null ? 'Guardar' : 'Crear servicio' ?></button>
            </div>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
