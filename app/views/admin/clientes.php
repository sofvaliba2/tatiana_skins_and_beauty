<?php
$titulo = 'Clientes';
$activo = 'admin_clientes';
require __DIR__ . '/../layout/admin_header.php';
$editar = $editar ?? null;
?>

<div class="page-title">
    <div>
        <h1>Clientes</h1>
        <p class="muted">Administra la información de tus clientes.</p>
    </div>
    <a class="btn btn-primary" href="<?= ruta('admin_clientes', ['nuevo' => 1]) ?>"><?= icon('plus', 18) ?> Nuevo cliente</a>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="admin_clientes">
            <div class="search" style="flex:1">
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Buscar por nombre, teléfono o correo..." value="<?= e($q ?? '') ?>">
            </div>
            <button class="btn btn-outline" type="submit">Buscar</button>
            <?php if (($q ?? '') !== ''): ?><a class="btn btn-outline" href="<?= ruta('admin_clientes') ?>">Limpiar</a><?php endif; ?>
        </form>

        <?php if (empty($clientes)): ?>
            <?= vacio(($q ?? '') !== '' ? 'Ningún cliente coincide con la búsqueda.' : 'Aún no hay clientes registrados.', 'users') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>ID</th><th>Cliente</th><th>Teléfono</th><th>Correo</th><th>Reservas</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><?= e($c['id_usuario']) ?></td>
                    <td><div class="person"><span class="avatar"><?= e(mb_substr($c['nombre'], 0, 1)) ?></span><?= e(trim($c['nombre'] . ' ' . $c['ap_paterno'] . ' ' . $c['ap_materno'])) ?></div></td>
                    <td><?= e($c['telefono'] ?: '—') ?></td>
                    <td><?= e($c['correo'] ?: '—') ?></td>
                    <td><?= (int)$c['reservas'] ?></td>
                    <td><div class="acts">
                        <a class="ic-btn" href="<?= ruta('admin_citas', ['q' => $c['id_usuario']]) ?>" title="Ver citas"><?= icon('calendar', 16) ?></a>
                        <a class="ic-btn" href="<?= ruta('admin_clientes', ['editar' => $c['id_usuario'], 'q' => $q ?? '']) ?>" aria-label="Editar"><?= icon('edit', 16) ?></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?= paginacion('admin_clientes', ['q' => $q ?? ''], $total, $pagina, 10) ?>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3><?= $editar ? 'Editar cliente' : 'Nuevo cliente' ?></h3></div>
        <form method="post" action="<?= ruta('admin_clientes') ?>">
            <?= csrf() ?>
            <?php if ($editar !== null): ?>
                <input type="hidden" name="id_usuario" value="<?= e($editar['id_usuario']) ?>">
                <p class="muted" style="font-size:.88rem;margin-bottom:1rem">Editando: <b><?= e($editar['id_usuario']) ?></b></p>
            <?php endif; ?>
            <div class="field"><label>Nombre *</label><input class="input" name="nombre" required maxlength="50" value="<?= e($editar['nombre'] ?? '') ?>"></div>
            <div class="field"><label>Apellido paterno *</label><input class="input" name="ap_paterno" required maxlength="50" value="<?= e($editar['ap_paterno'] ?? '') ?>"></div>
            <div class="field"><label>Apellido materno</label><input class="input" name="ap_materno" maxlength="50" value="<?= e($editar['ap_materno'] ?? '') ?>"></div>
            <div class="field"><label>Teléfono *</label><input class="input" name="telefono" required maxlength="20" placeholder="Ej: 700 00000" value="<?= e($editar['telefono'] ?? '') ?>"></div>
            <div class="field"><label>Fecha de nacimiento</label><input class="input" type="date" name="fecha_nacimiento" value="<?= e($editar['fecha_nacimiento'] ?? '') ?>"></div>
            <?php if ($editar === null): ?>
                <div class="field"><label>Correo electrónico *</label><input class="input" type="email" name="correo" required placeholder="cliente@correo.com"></div>
                <div class="field"><label>Contraseña * <small>(mín. 8 caracteres)</small></label><input class="input" type="password" name="password" required minlength="8"></div>
            <?php else: ?>
                <div class="field"><label>Correo</label><input class="input" value="<?= e($editar['correo'] ?? '') ?>" disabled></div>
                <p class="muted" style="font-size:.82rem">Para restablecer la contraseña del cliente, hazlo desde <b>Usuarios</b> o pide al administrador.</p>
            <?php endif; ?>
            <div style="display:flex;gap:.6rem;margin-top:1rem">
                <?php if ($editar !== null): ?>
                    <a href="<?= ruta('admin_clientes', ['q' => $q ?? '']) ?>" class="btn btn-outline" style="flex:1">Cancelar</a>
                <?php else: ?>
                    <button type="reset" class="btn btn-outline" style="flex:1">Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="flex:1"><?= $editar ? 'Guardar' : 'Registrar' ?></button>
            </div>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
