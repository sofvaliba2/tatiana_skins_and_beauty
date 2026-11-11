<?php
$titulo = 'Historial de piel';
$activo = 'empleado_historial';
require __DIR__ . '/../layout/empleado_header.php';
$ficha = $ficha ?? null;
$historial = $historial ?? [];
$reservas = $reservas ?? [];
$ultima = $historial[0] ?? null;
$sucursalNombre = (string)($sucursal_actual['nombre_sucursal'] ?? 'tu sucursal');
?>

<div class="page-title">
    <div>
        <h1>Historial de piel</h1>
        <p class="muted">Fichas y seguimientos de las sesiones atendidas en <?= e($sucursalNombre) ?>.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="empleado_historial">
            <div class="search" style="flex:1">
                <?= icon('search', 18) ?>
                <input class="input" type="search" name="q" placeholder="Buscar clienta por nombre o apellido..." value="<?= e($q ?? '') ?>">
            </div>
            <button class="btn btn-outline" type="submit">Buscar</button>
        </form>

        <?php if (empty($clientes)): ?>
            <?= vacio(($q ?? '') !== '' ? 'Ninguna clienta coincide con la búsqueda.' : 'No hay clientas vinculadas a esta sucursal.', 'users') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Clienta</th><th>Teléfono</th><th>Citas</th><th>Acciones</th></tr></thead>
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
                    <td><?= (int)$c['reservas'] ?></td>
                    <td>
                        <a class="ic-btn" href="<?= ruta('empleado_historial', ['cliente' => $c['id_usuario'], 'q' => $q ?? '']) ?>" title="Abrir historial"><?= icon('history', 16) ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3>Ficha de piel</h3></div>
        <?php if ($ficha === null): ?>
            <?= vacio('Selecciona una clienta para consultar y registrar su historial.', 'file') ?>
        <?php else: ?>
            <div class="person" style="margin-bottom:1.2rem">
                <span class="avatar" style="width:46px;height:46px"><?= e(mb_substr((string)$ficha['nombre'], 0, 1)) ?></span>
                <div>
                    <b style="color:var(--morado)"><?= e(trim($ficha['nombre'] . ' ' . $ficha['ap_paterno'] . ' ' . $ficha['ap_materno'])) ?></b><br>
                    <small class="muted"><?= e($ficha['telefono'] ?: 'Sin teléfono') ?> · <?= e($ficha['correo'] ?: 'Sin correo') ?></small>
                </div>
            </div>

            <?php if (empty($historial)): ?>
                <div class="alert warn" style="margin-bottom:1rem">Aún no hay registros de piel en esta sucursal.</div>
            <?php else: ?>
                <div class="ficha">
                    <div class="it alergia"><small>⚠ Alergias</small><?= e($ultima['alergias'] ?: 'ninguna registrada') ?></div>
                    <div class="it"><small>Objetivo / tipo de piel</small><?= e($ultima['objetivo_piel'] ?: '—') ?></div>
                    <div class="it"><small>Rutina actual</small><?= e($ultima['rutina_actual'] ?: '—') ?></div>
                    <div class="it"><small>Recomendaciones abiertas</small><?= !empty($ultima['abierto_recomendaciones']) ? 'Sí' : 'No' ?></div>
                </div>
            <?php endif; ?>

            <h3 style="margin:1.6rem 0 .8rem;font-size:1.1rem">Sesiones registradas</h3>
            <?php if (empty($historial)): ?>
                <p class="muted" style="font-size:.9rem">No hay sesiones registradas.</p>
            <?php else: ?>
                <div class="timeline" style="max-height:260px;overflow:auto;padding-right:.4rem">
                    <?php foreach ($historial as $h): ?>
                        <div class="ev">
                            <b><?= e(date('d/m/Y', strtotime((string)$h['fecha_registro']))) ?></b>
                            <?php if (!empty($h['servicios'])): ?><small class="muted"> · <?= e($h['servicios']) ?></small><?php endif; ?><br>
                            <small class="muted"><?= e($h['observaciones_sesion'] ?: 'Sin observaciones') ?></small>
                            <?php if (!empty($h['alergias'])): ?><br><small class="muted">Alergias: <?= e($h['alergias']) ?></small><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </aside>
</div>

<?php if ($ficha !== null): ?>
<section class="panel" style="margin-top:1.4rem">
    <div class="panel-head">
        <h3><?= icon('plus', 18) ?> Registrar seguimiento</h3>
        <span class="muted" style="font-size:.85rem">La ficha queda vinculada a una cita de la sucursal</span>
    </div>
    <?php
    $disponibles = array_values(array_filter($reservas, static fn ($r) => ($r['estado'] ?? '') !== 'cancelada'));
    ?>
    <?php if ($disponibles === []): ?>
        <div class="alert warn">Esta clienta no tiene citas activas en la sucursal. Agenda una cita antes de registrar el seguimiento.</div>
    <?php else: ?>
        <form method="post" action="<?= ruta('empleado_historial') ?>">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="seguimiento">
            <input type="hidden" name="id_usuario" value="<?= e($ficha['id_usuario']) ?>">
            <div class="form-grid">
                <div class="field">
                    <label>Cita / sesión *</label>
                    <select class="input" name="id_reserva" required>
                        <option value="">Selecciona la cita...</option>
                        <?php foreach ($disponibles as $r): ?>
                            <option value="<?= (int)$r['id_reserva'] ?>">
                                <?= e(date('d/m/Y', strtotime((string)$r['fecha']))) ?> · <?= e(substr((string)$r['hora'], 0, 5)) ?> · <?= e($r['servicios'] ?? 'Sin servicio') ?> (<?= e((string)$r['estado']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Alergias</label>
                    <input class="input" name="alergias" maxlength="500" value="<?= e($ultima['alergias'] ?? '') ?>" placeholder="Alergias conocidas">
                </div>
                <div class="field">
                    <label>Objetivo / tipo de piel</label>
                    <input class="input" name="objetivo_piel" maxlength="500" value="<?= e($ultima['objetivo_piel'] ?? '') ?>" placeholder="Objetivo de la clienta">
                </div>
                <div class="field">
                    <label>Rutina actual</label>
                    <input class="input" name="rutina_actual" maxlength="500" value="<?= e($ultima['rutina_actual'] ?? '') ?>" placeholder="Productos y rutina actual">
                </div>
            </div>
            <div class="field">
                <label>Observaciones de la sesión</label>
                <textarea class="input" name="observaciones_sesion" rows="3" maxlength="2000" placeholder="Respuesta de la piel, tratamiento aplicado, indicaciones..."></textarea>
            </div>
            <label style="display:flex;align-items:center;gap:.6rem;font-size:.9rem;margin-bottom:1rem">
                <input type="checkbox" name="abierto_recomendaciones" value="1" <?= !empty($ultima['abierto_recomendaciones']) ? 'checked' : '' ?>>
                Dejar recomendaciones abiertas para la próxima sesión
            </label>
            <button class="btn btn-primary" type="submit"><?= icon('check', 18) ?> Guardar seguimiento</button>
        </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
