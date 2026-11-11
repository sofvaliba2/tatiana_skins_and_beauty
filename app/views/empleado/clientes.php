<?php
$titulo = 'Clientes';
$activo = 'empleado_clientes';
require __DIR__ . '/../layout/empleado_header.php';
$ficha = $ficha ?? null;
$historial = $historial ?? [];
$reservas = $reservas ?? [];
$ultima = $historial[0] ?? null;
$sucursalNombre = (string)($sucursal_actual['nombre_sucursal'] ?? 'tu sucursal');
?>

<div class="page-title">
    <div>
        <h1>Clientes</h1>
        <p class="muted">Clientas vinculadas a <?= e($sucursalNombre) ?>. Busca por nombre o apellido.</p>
    </div>
    <span class="muted" style="font-size:.9rem"><?= (int)($total ?? 0) ?> clienta(s)</span>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="empleado_clientes">
            <div class="search" style="flex:1">
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Nombre, apellido, teléfono o correo..." value="<?= e($q ?? '') ?>">
            </div>
            <button class="btn btn-outline" type="submit">Buscar</button>
            <?php if (($q ?? '') !== ''): ?>
                <a class="btn btn-outline" href="<?= ruta('empleado_clientes') ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($clientes)): ?>
            <?= vacio(($q ?? '') !== '' ? 'Ninguna clienta coincide con la búsqueda.' : 'No hay clientas vinculadas a esta sucursal.', 'users') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Clienta</th><th>Teléfono</th><th>Correo</th><th>Citas</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td>
                        <div class="person">
                            <span class="avatar"><?= e(mb_substr((string)$c['nombre'], 0, 1)) ?></span>
                            <span>
                                <b><?= e(trim($c['nombre'] . ' ' . $c['ap_paterno'] . ' ' . $c['ap_materno'])) ?></b><br>
                                <small class="muted"><?= e($c['id_usuario']) ?></small>
                            </span>
                        </div>
                    </td>
                    <td><?= e($c['telefono'] ?: '—') ?></td>
                    <td><?= e($c['correo'] ?: '—') ?></td>
                    <td><?= (int)$c['reservas'] ?></td>
                    <td>
                        <div class="acts">
                            <a class="ic-btn" href="<?= ruta('empleado_clientes', ['ficha' => $c['id_usuario'], 'q' => $q ?? '']) ?>" title="Ver ficha"><?= icon('eye', 16) ?></a>
                            <a class="ic-btn" href="<?= ruta('empleado_historial', ['cliente' => $c['id_usuario']]) ?>" title="Historial de piel"><?= icon('history', 16) ?></a>
                            <a class="ic-btn" href="<?= ruta('empleado_citas', ['cliente' => $c['id_usuario']]) ?>" title="Ver citas"><?= icon('calendar', 16) ?></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?= paginacion('empleado_clientes', ['q' => $q ?? ''], $total ?? 0, $pagina ?? 1, 10) ?>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3>Ficha de la clienta</h3></div>
        <?php if ($ficha === null): ?>
            <?= vacio('Selecciona "Ver ficha" para consultar los datos y el historial de una clienta.', 'file') ?>
        <?php else: ?>
            <div class="person" style="margin-bottom:1.2rem">
                <span class="avatar" style="width:46px;height:46px"><?= e(mb_substr((string)$ficha['nombre'], 0, 1)) ?></span>
                <div>
                    <b style="color:var(--morado)"><?= e(trim($ficha['nombre'] . ' ' . $ficha['ap_paterno'] . ' ' . $ficha['ap_materno'])) ?></b><br>
                    <small class="muted">
                        <?= e($ficha['telefono'] ?: 'Sin teléfono') ?>
                        <?= !empty($ficha['fecha_nacimiento']) ? ' · Nac.: ' . e(date('d/m/Y', strtotime((string)$ficha['fecha_nacimiento']))) : '' ?>
                    </small>
                </div>
            </div>

            <div class="ficha">
                <div class="it"><small>Correo</small><?= e($ficha['correo'] ?: '—') ?></div>
                <div class="it"><small>Citas en la sucursal</small><?= (int)($ficha['reservas'] ?? count($reservas)) ?></div>
                <div class="it alergia"><small>Alergias registradas</small><?= e($ultima['alergias'] ?? '') ?: 'Ninguna registrada' ?></div>
                <div class="it"><small>Objetivo / tipo de piel</small><?= e($ultima['objetivo_piel'] ?? '') ?: 'Sin registro' ?></div>
                <div class="it"><small>Rutina actual</small><?= e($ultima['rutina_actual'] ?? '') ?: 'Sin registro' ?></div>
            </div>

            <div style="display:flex;gap:.6rem;margin-top:1.2rem;flex-wrap:wrap">
                <a class="btn btn-primary btn-sm" href="<?= ruta('empleado_historial', ['cliente' => $ficha['id_usuario']]) ?>"><?= icon('history', 16) ?> Historial</a>
                <a class="btn btn-outline btn-sm" href="<?= ruta('empleado_citas', ['cliente' => $ficha['id_usuario']]) ?>"><?= icon('calendar', 16) ?> Citas</a>
                <a class="btn btn-outline btn-sm" href="<?= ruta('empleado_ventas', ['cliente' => $ficha['id_usuario']]) ?>"><?= icon('cart', 16) ?> Vender</a>
            </div>

            <h3 style="margin:1.6rem 0 .75rem;font-size:1.1rem">Últimas citas</h3>
            <?php if (empty($reservas)): ?>
                <p class="muted" style="font-size:.9rem">Sin citas registradas en esta sucursal.</p>
            <?php else: ?>
                <?php foreach (array_slice($reservas, 0, 4) as $r): ?>
                    <div class="summary-row">
                        <span><?= e(date('d/m/Y', strtotime((string)$r['fecha']))) ?> · <?= e(substr((string)$r['hora'], 0, 5)) ?><br><small class="muted"><?= e($r['servicios'] ?? 'Sin servicio') ?></small></span>
                        <span><?= estado((string)$r['estado']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
