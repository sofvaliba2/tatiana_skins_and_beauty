<?php
/** Agenda de citas + ficha de piel de la cita seleccionada. */
$titulo = 'Citas';
$activo = 'admin_citas';
require __DIR__ . '/../layout/admin_header.php';
$fc = $fichaCliente ?? null;
?>

<div class="page-title">
    <div>
        <h1>Citas</h1>
        <p class="muted">Agenda de reservas, anticipos y ficha de piel.</p>
    </div>
    <span class="muted" style="font-size:.9rem"><?= count($reservas ?? []) ?> cita(s) en la vista</span>
</div>

<div class="split">
    <section class="panel">
        <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="v" value="admin_citas">
            <input class="input" type="date" name="fecha" value="<?= e($fecha ?? '') ?>">
            <select class="input" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach (['pendiente', 'confirmada', 'atendida', 'cancelada'] as $est): ?>
                    <option value="<?= $est ?>" <?= ($estado ?? '') === $est ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="input" name="sucursal">
                <option value="">Todas las sucursales</option>
                <?php foreach (($sucursales ?? []) as $s): ?>
                    <option value="<?= e($s['id_sucursal']) ?>" <?= ($idSucursal ?? '') === $s['id_sucursal'] ? 'selected' : '' ?>><?= e($s['nombre_sucursal']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline" type="submit"><?= icon('filter', 16) ?> Filtrar</button>
            <?php if (($fecha ?? '') !== '' || ($estado ?? '') !== '' || ($idSucursal ?? '') !== ''): ?>
                <a class="btn btn-outline" href="<?= ruta('admin_citas') ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($reservas)): ?>
            <?= vacio('No hay citas con estos filtros.', 'calendar') ?>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Fecha</th><th>Hora</th><th>Cliente</th><th>Servicio</th><th>Sucursal</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($reservas as $c): $comprobante = is_file(BASE_PATH . '/public/uploads/comprobantes/res_' . $c['id_reserva'] . '.jpg'); ?>
                <tr>
                    <td><?= e(date('d/m/Y', strtotime($c['fecha']))) ?></td>
                    <td><b><?= e(substr((string)$c['hora'], 0, 5)) ?></b></td>
                    <td><div class="person"><span class="avatar"><?= e(mb_substr($c['nombre'], 0, 1)) ?></span><?= e(trim($c['nombre'] . ' ' . $c['ap_paterno'])) ?></div></td>
                    <td><?= e($c['servicios'] ?? '—') ?></td>
                    <td><?= e($c['nombre_sucursal'] ?? '—') ?></td>
                    <td><?= bs($c['total']) ?></td>
                    <td><?= estado((string)$c['estado']) ?><?php if ($comprobante): ?> <a class="link" href="<?= BASE_URL ?>/uploads/comprobantes/res_<?= (int)$c['id_reserva'] ?>.jpg" target="_blank" title="Ver comprobante"><?= icon('eye', 14) ?></a><?php endif; ?></td>
                    <td><div class="acts">
                        <a class="ic-btn" href="<?= ruta('admin_citas', array_filter(['ficha' => $c['id_reserva'], 'fecha' => $fecha ?? '', 'estado' => $estado ?? '', 'sucursal' => $idSucursal ?? ''])) ?>" title="Ver ficha"><?= icon('eye', 16) ?></a>
                        <?php if ($c['estado'] === 'pendiente'): ?>
                        <form method="post" action="<?= ruta('admin_citas') ?>" style="display:inline" data-confirm="¿Confirmar el pago y la cita #<?= (int)$c['id_reserva'] ?>?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="confirmada">
                            <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                            <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                            <input type="hidden" name="sucursal" value="<?= e($idSucursal ?? '') ?>">
                            <button class="ic-btn" type="submit" title="Confirmar anticipo"><?= icon('check', 16) ?></button>
                        </form>
                        <?php endif; ?>
                        <?php if (in_array($c['estado'], ['confirmada', 'pendiente'], true)): ?>
                        <form method="post" action="<?= ruta('admin_citas') ?>" style="display:inline" data-confirm="¿Marcar la cita #<?= (int)$c['id_reserva'] ?> como atendida?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="atendida">
                            <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                            <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                            <input type="hidden" name="sucursal" value="<?= e($idSucursal ?? '') ?>">
                            <button class="ic-btn" type="submit" title="Marcar atendida"><?= icon('clock', 16) ?></button>
                        </form>
                        <form method="post" action="<?= ruta('admin_citas') ?>" style="display:inline" data-confirm="¿Cancelar la cita #<?= (int)$c['id_reserva'] ?>?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="cancelada">
                            <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                            <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                            <input type="hidden" name="sucursal" value="<?= e($idSucursal ?? '') ?>">
                            <button class="ic-btn del" type="submit" title="Cancelar cita"><?= icon('trash', 16) ?></button>
                        </form>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-head"><h3>Ficha de piel</h3></div>
        <?php if ($fc === null): ?>
            <?= vacio('Selecciona "Ver ficha" en una cita para ver el historial de la clienta.', 'file') ?>
        <?php else: $cl = $fc['cliente']; ?>
        <div class="person" style="margin-bottom:1.2rem">
            <span class="avatar" style="width:46px;height:46px"><?= e(mb_substr($cl['nombre'], 0, 1)) ?></span>
            <div><b style="color:var(--morado)"><?= e(trim($cl['nombre'] . ' ' . $cl['ap_paterno'] . ' ' . $cl['ap_materno'])) ?></b><br>
            <small class="muted"><?= e($cl['telefono'] ?: 'Sin teléfono') ?><?= $cl['fecha_nacimiento'] ? ' · Nac.: ' . e(date('d/m/Y', strtotime($cl['fecha_nacimiento']))) : '' ?></small></div>
        </div>

        <?php if (empty($fc['historial'])): ?>
            <p class="muted" style="font-size:.9rem">Esta clienta aún no completó el formulario de piel. La próxima vez que reserve, deberá llenarlo (inicial o seguimiento).</p>
        <?php else: $h = $fc['historial'][0]; ?>
            <div class="ficha">
                <div class="it alergia"><small>⚠ Alergias</small><?= e($h['alergias'] ?: 'ninguna registrada') ?></div>
                <div class="it"><small>Objetivo / tipo de piel</small><?= e($h['objetivo_piel'] ?: '—') ?></div>
                <div class="it"><small>Rutina actual</small><?= e($h['rutina_actual'] ?: '—') ?></div>
                <div class="it"><small>Recomendaciones abiertas</small><?= $h['abierto_recomendaciones'] ? 'Sí' : 'No' ?></div>
            </div>

            <h3 style="margin:1.6rem 0 1rem;font-size:1.15rem">Historial de sesiones</h3>
            <div class="timeline">
                <?php foreach ($fc['historial'] as $hist): ?>
                    <div class="ev"><b>Sesión <?= e(date('d/m/Y', strtotime($hist['fecha_registro']))) ?></b><br><small class="muted"><?= e(mb_strimwidth((string)($hist['observaciones_sesion'] ?: 'Sin observaciones'), 0, 110, '…')) ?></small></div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?= ruta('admin_citas') ?>" style="margin-top:1.4rem">
                <?= csrf() ?>
                <input type="hidden" name="accion" value="observacion">
                <input type="hidden" name="id_reserva" value="<?= (int)$cl['id_reserva'] ?>">
                <div class="field">
                    <label>Observaciones de la sesión</label>
                    <textarea class="input" name="observaciones" rows="3" placeholder="Notas del tratamiento..."></textarea>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Guardar observaciones</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($fc['compras'])): ?>
            <h3 style="margin:1.6rem 0 .75rem;font-size:1.15rem">Últimas compras</h3>
            <?php foreach ($fc['compras'] as $cc): ?>
                <div class="summary-row"><span><?= e(date('d/m/Y', strtotime($cc['fecha_compra']))) ?> · <?= e($cc['nro_compra'] ?? '#' . $cc['id_compra']) ?></span><b><?= bs($cc['total_pagar']) ?></b></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
