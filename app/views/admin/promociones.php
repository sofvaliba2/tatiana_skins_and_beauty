<?php
$titulo = 'Promociones';
$activo = 'admin_promociones';
require __DIR__ . '/../layout/admin_header.php';
$editar = $editar ?? null;
$resumen = $resumen ?? ['total' => 0, 'activa' => 0, 'programada' => 0, 'vencida' => 0, 'inactiva' => 0];
$estadoFiltro = $estadoFiltro ?? '';
$q = $q ?? '';

$etiquetasEstado = [
    'activa'     => 'Activa',
    'programada' => 'Programada',
    'vencida'    => 'Vencida',
    'inactiva'   => 'Inactiva',
];
?>

<div class="page-title">
    <div>
        <h1>Promociones</h1>
        <p class="muted">Descuentos vigentes para productos y servicios del centro.</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <?= btn_volver('admin_dashboard', [], 'Panel') ?>
        <a class="btn btn-primary" href="<?= ruta('admin_promociones', ['nuevo' => 1]) ?>"><?= icon('plus', 18) ?> Nueva promoción</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><span class="ico"><?= icon('heart', 24) ?></span><div><small>Activas</small><strong><?= (int)$resumen['activa'] ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('clock', 24) ?></span><div><small>Programadas</small><strong><?= (int)$resumen['programada'] ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('calendar', 24) ?></span><div><small>Vencidas</small><strong><?= (int)$resumen['vencida'] ?></strong></div></div>
    <div class="kpi"><span class="ico"><?= icon('box', 24) ?></span><div><small>Total</small><strong><?= (int)$resumen['total'] ?></strong></div></div>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="admin_promociones">
            <div class="search" style="flex:1">
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Buscar promoción..." value="<?= e($q) ?>">
            </div>
            <select class="input" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach ($etiquetasEstado as $k => $texto): ?>
                    <option value="<?= e($k) ?>" <?= $estadoFiltro === $k ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline" type="submit"><?= icon('filter', 16) ?> Filtrar</button>
            <?php if ($q !== '' || $estadoFiltro !== ''): ?>
                <a class="btn btn-outline" href="<?= ruta('admin_promociones') ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($promociones)): ?>
            <?= vacio('No hay promociones con estos filtros. Crea la primera en el formulario lateral.', 'heart') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Imagen</th><th>Promoción</th><th>Artículo</th><th>Precio</th><th>Descuento</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($promociones as $p):
                $imagen = trim((string)($p['imagen_url'] ?? ''));
                $calc = (string)($p['estado_calculado'] ?? 'inactiva');
                $estadoBase = (string)($p['estado'] ?? 'activa');
            ?>
                <tr>
                    <td>
                        <?php if ($imagen !== ''): ?>
                            <img src="<?= e(url_archivo($imagen)) ?>" alt="<?= e($p['nombre']) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:10px">
                        <?php else: ?>
                            <span style="display:inline-flex;width:52px;height:52px;align-items:center;justify-content:center;background:var(--lila-claro);border-radius:10px;color:var(--morado)"><?= icon('heart', 22) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><b><?= e($p['nombre']) ?></b><br><small class="muted"><?= e(mb_strimwidth((string)($p['descripcion'] ?? ''), 0, 60, '…')) ?></small></td>
                    <td><?= e($p['articulo'] ?? '—') ?><br><small class="muted"><?= e(ucfirst(str_replace('_', ' + ', (string)($p['tipo_articulo'] ?? '')))) ?></small></td>
                    <td>
                        <span class="muted" style="text-decoration:line-through"><?= bs($p['precio_original']) ?></span><br>
                        <b style="color:var(--morado)"><?= bs($p['precio_promocional']) ?></b>
                    </td>
                    <td><span class="badge-st st-vip">-<?= number_format((float)$p['descuento'], 0) ?>%</span></td>
                    <td><small><?= e(date('d/m/Y', strtotime((string)$p['fecha_inicio']))) ?><br>→ <?= e(date('d/m/Y', strtotime((string)$p['fecha_fin']))) ?></small></td>
                    <td>
                        <span class="badge-st st-<?= e($calc) ?>"><?= e($etiquetasEstado[$calc] ?? ucfirst($calc)) ?></span>
                        <?php if ($calc === 'activa' && (int)$p['dias_restantes'] <= 7): ?>
                            <br><small class="muted">Vence en <?= (int)$p['dias_restantes'] ?> día(s)</small>
                        <?php endif; ?>
                    </td>
                    <td><div class="acts" style="flex-wrap:wrap">
                        <a class="ic-btn" href="<?= ruta('admin_promociones', ['editar' => $p['id_promocion'], 'q' => $q, 'estado' => $estadoFiltro]) ?>" title="Editar"><?= icon('edit', 16) ?></a>
                        <form method="post" action="<?= ruta('admin_promociones') ?>" style="display:inline" data-confirm="¿<?= $estadoBase === 'activa' ? 'Desactivar' : 'Activar' ?> la promoción <?= e($p['nombre']) ?>?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_promocion" value="<?= e($p['id_promocion']) ?>">
                            <input type="hidden" name="nuevo_estado" value="<?= $estadoBase === 'activa' ? 'inactiva' : 'activa' ?>">
                            <button class="ic-btn" type="submit" title="<?= $estadoBase === 'activa' ? 'Desactivar' : 'Activar' ?>"><?= icon($estadoBase === 'activa' ? 'eye' : 'check', 16) ?></button>
                        </form>
                        <form method="post" action="<?= ruta('admin_promociones') ?>" style="display:inline" data-confirm="¿Eliminar la promoción <?= e($p['nombre']) ?>? Esta acción no se puede deshacer.">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_promocion" value="<?= e($p['id_promocion']) ?>">
                            <button class="ic-btn del" type="submit" title="Eliminar"><?= icon('trash', 16) ?></button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?= paginacion('admin_promociones', ['q' => $q, 'estado' => $estadoFiltro], $total, $pagina, 10) ?>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= $editar !== null ? 'Editar promoción' : 'Nueva promoción' ?></h3></div>
        <form method="post" action="<?= ruta('admin_promociones') ?>" enctype="multipart/form-data" data-guardar>
            <?= csrf() ?>
            <input type="hidden" name="accion" value="guardar">
            <?php if ($editar !== null): ?><input type="hidden" name="id_promocion" value="<?= e($editar['id_promocion']) ?>"><?php endif; ?>

            <div class="field"><label>Nombre *</label><input class="input" name="nombre" required maxlength="100" placeholder="Ej: Semana de hidratación" value="<?= e($editar['nombre'] ?? '') ?>"></div>
            <div class="field"><label>Descripción</label><textarea class="input" name="descripcion" rows="2" maxlength="255" placeholder="Detalle de la promoción..."><?= e($editar['descripcion'] ?? '') ?></textarea></div>

            <div class="field"><label>Producto asociado</label>
                <select class="input" name="id_producto">
                    <option value="">Sin producto</option>
                    <?php foreach (($productos ?? []) as $pr): ?>
                        <option value="<?= e($pr['id_producto']) ?>" <?= (($editar['id_producto'] ?? '') === $pr['id_producto']) ? 'selected' : '' ?>>
                            <?= e($pr['nombre_producto']) ?> — <?= bs($pr['precio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Servicio asociado</label>
                <select class="input" name="id_servicio">
                    <option value="">Sin servicio</option>
                    <?php foreach (($servicios ?? []) as $s): ?>
                        <option value="<?= e($s['id_servicio']) ?>" <?= (($editar['id_servicio'] ?? '') === $s['id_servicio']) ? 'selected' : '' ?>>
                            <?= e($s['nombre']) ?> — <?= bs($s['precio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="muted" style="font-size:.75rem">Asocia al menos un producto o un servicio.</small>
            </div>

            <div class="form-grid">
                <div class="field"><label>Precio original (Bs.) *</label><input class="input" type="number" step="0.01" min="0.01" name="precio_original" required value="<?= e($editar['precio_original'] ?? '') ?>"></div>
                <div class="field"><label>Precio promocional (Bs.) *</label><input class="input" type="number" step="0.01" min="0" name="precio_promocional" required value="<?= e($editar['precio_promocional'] ?? '') ?>"></div>
            </div>

            <div class="form-grid">
                <div class="field"><label>Inicio *</label><input class="input" type="date" name="fecha_inicio" required value="<?= e($editar['fecha_inicio'] ?? date('Y-m-d')) ?>"></div>
                <div class="field"><label>Fin *</label><input class="input" type="date" name="fecha_fin" required value="<?= e($editar['fecha_fin'] ?? date('Y-m-d', strtotime('+14 day'))) ?>"></div>
            </div>

            <div class="field"><label>Estado</label>
                <select class="input" name="estado">
                    <option value="activa" <?= (($editar['estado'] ?? 'activa') === 'activa') ? 'selected' : '' ?>>Activa</option>
                    <option value="inactiva" <?= (($editar['estado'] ?? '') === 'inactiva') ? 'selected' : '' ?>>Inactiva</option>
                </select>
                <small class="muted" style="font-size:.75rem">El estado final (activa/programada/vencida) se calcula con las fechas.</small>
            </div>

            <div class="field"><label>Imagen (JPG, PNG o WEBP, máx. 2 MB)</label><input class="input" type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></div>
            <?php if ($editar !== null && !empty($editar['imagen_url'])): ?>
                <p class="muted" style="font-size:.8rem;margin-top:-.4rem">Imagen actual: <a class="link" href="<?= e(url_archivo($editar['imagen_url'])) ?>" target="_blank">ver</a></p>
            <?php endif; ?>

            <div style="display:flex;gap:.6rem;margin-top:1rem">
                <?php if ($editar !== null): ?>
                    <a href="<?= ruta('admin_promociones', ['q' => $q, 'estado' => $estadoFiltro]) ?>" class="btn btn-outline" style="flex:1" data-cancelar>Cancelar</a>
                <?php else: ?>
                    <button type="reset" class="btn btn-outline" style="flex:1">Limpiar</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="flex:1"><?= $editar !== null ? 'Guardar' : 'Crear promoción' ?></button>
            </div>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
