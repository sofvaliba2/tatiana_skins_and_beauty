<?php
$titulo = 'Citas y reservas';
$activo = 'empleado_citas';
require __DIR__ . '/../layout/empleado_header.php';
$fc = $fichaCliente ?? null;
$editarReserva = $editarReserva ?? null;
$editarServicio = $editarServicio ?? '';
$sucursalNombre = (string)($sucursal_actual['nombre_sucursal'] ?? 'tu sucursal');
$hoy = date('Y-m-d');
$filtros = ['fecha' => $fecha ?? '', 'estado' => $estado ?? '', 'cliente' => $idCliente ?? ''];
?>

<div class="page-title">
    <div>
        <h1>Citas y reservas</h1>
        <p class="muted">Agenda de <?= e($sucursalNombre) ?>. Los horarios respetan la apertura, el cierre y la duración de cada servicio.</p>
    </div>
    <span class="muted" style="font-size:.9rem"><?= count($reservas ?? []) ?> cita(s) en la vista</span>
</div>

<section class="panel">
    <form class="filters" method="get" action="<?= BASE_URL ?>/index.php">
        <input type="hidden" name="v" value="empleado_citas">
        <input class="input" type="date" name="fecha" value="<?= e($fecha ?? '') ?>">
        <select class="input" name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (\App\Models\Reserva::ESTADOS as $est): ?>
                <option value="<?= e($est) ?>" <?= ($estado ?? '') === $est ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="input" name="cliente">
            <option value="">Todas las clientas</option>
            <?php foreach (($clientes ?? []) as $cl): ?>
                <option value="<?= e($cl['id_usuario']) ?>" <?= ($idCliente ?? '') === $cl['id_usuario'] ? 'selected' : '' ?>>
                    <?= e(trim($cl['nombre'] . ' ' . $cl['ap_paterno'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline" type="submit"><?= icon('filter', 16) ?> Filtrar</button>
        <?php if (($fecha ?? '') !== '' || ($estado ?? '') !== '' || ($idCliente ?? '') !== ''): ?>
            <a class="btn btn-outline" href="<?= ruta('empleado_citas') ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($reservas)): ?>
        <?= vacio('No hay citas con estos filtros.', 'calendar') ?>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Fecha</th><th>Hora</th><th>Clienta</th><th>Servicio</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($reservas as $c): ?>
            <tr>
                <td><?= e(date('d/m/Y', strtotime((string)$c['fecha']))) ?></td>
                <td><b><?= e(substr((string)$c['hora'], 0, 5)) ?></b></td>
                <td>
                    <div class="person">
                        <span class="avatar"><?= e(mb_substr((string)$c['nombre'], 0, 1)) ?></span>
                        <span><?= e(trim($c['nombre'] . ' ' . $c['ap_paterno'])) ?><br><small class="muted"><?= e($c['telefono'] ?: '') ?></small></span>
                    </div>
                </td>
                <td><?= e($c['servicios'] ?? '—') ?></td>
                <td><?= bs($c['total'] ?? 0) ?></td>
                <td><?= estado((string)$c['estado']) ?></td>
                <td>
                    <div class="acts">
                        <a class="ic-btn" href="<?= ruta('empleado_citas', array_filter($filtros + ['ficha' => $c['id_reserva']])) ?>" title="Ver ficha"><?= icon('eye', 16) ?></a>
                        <a class="ic-btn" href="<?= ruta('empleado_citas', array_filter($filtros + ['editar' => $c['id_reserva']])) ?>" title="Reprogramar"><?= icon('edit', 16) ?></a>
                        <?php if ($c['estado'] === 'pendiente'): ?>
                        <form method="post" action="<?= ruta('empleado_citas') ?>" style="display:inline" data-confirm="¿Confirmar la cita #<?= (int)$c['id_reserva'] ?>?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="confirmada">
                            <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                            <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                            <input type="hidden" name="cliente" value="<?= e($idCliente ?? '') ?>">
                            <button class="ic-btn" type="submit" title="Confirmar"><?= icon('check', 16) ?></button>
                        </form>
                        <?php endif; ?>
                        <?php if (in_array($c['estado'], ['pendiente', 'confirmada'], true)): ?>
                        <form method="post" action="<?= ruta('empleado_citas') ?>" style="display:inline" data-confirm="¿Marcar la cita #<?= (int)$c['id_reserva'] ?> como atendida?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="atendida">
                            <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                            <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                            <input type="hidden" name="cliente" value="<?= e($idCliente ?? '') ?>">
                            <button class="ic-btn" type="submit" title="Marcar atendida"><?= icon('clock', 16) ?></button>
                        </form>
                        <?php endif; ?>
                        <?php if (in_array($c['estado'], ['pendiente', 'confirmada'], true)): ?>
                        <form method="post" action="<?= ruta('empleado_citas') ?>" style="display:inline" data-confirm="¿Cancelar la cita #<?= (int)$c['id_reserva'] ?>?">
                            <?= csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_reserva" value="<?= (int)$c['id_reserva'] ?>">
                            <input type="hidden" name="nuevo_estado" value="cancelada">
                            <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                            <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                            <input type="hidden" name="cliente" value="<?= e($idCliente ?? '') ?>">
                            <button class="ic-btn del" type="submit" title="Cancelar cita"><?= icon('trash', 16) ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<div class="panel-grid" style="margin-top:1.4rem">
    <section class="panel" id="nueva-cita">
        <div class="panel-head"><h3><?= icon('plus', 18) ?> Nueva cita</h3></div>
        <form method="post" action="<?= ruta('empleado_citas') ?>" data-agenda>
            <?= csrf() ?>
            <input type="hidden" name="accion" value="crear">
            <div class="form-grid">
                <div class="field">
                    <label>Clienta *</label>
                    <select class="input" name="id_usuario" required>
                        <option value="">Selecciona una clienta...</option>
                        <?php foreach (($clientes ?? []) as $cl): ?>
                            <option value="<?= e($cl['id_usuario']) ?>" <?= ($idCliente ?? '') === $cl['id_usuario'] ? 'selected' : '' ?>>
                                <?= e(trim($cl['nombre'] . ' ' . $cl['ap_paterno'])) ?> (<?= e($cl['id_usuario']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Servicio *</label>
                    <select class="input" name="id_servicio" required>
                        <option value="">Selecciona un servicio...</option>
                        <?php foreach (($servicios ?? []) as $s): ?>
                            <option value="<?= e($s['id_servicio']) ?>" data-duracion="<?= (int)$s['duracion_minutos'] ?>">
                                <?= e($s['nombre']) ?> · <?= bs($s['precio']) ?> · <?= (int)$s['duracion_minutos'] ?> min
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Fecha *</label>
                    <input class="input" type="date" name="fecha" min="<?= e($hoy) ?>" value="<?= e($fecha !== '' ? $fecha : $hoy) ?>" required>
                </div>
                <div class="field">
                    <label>Hora *</label>
                    <input class="input" type="time" name="hora" required>
                </div>
            </div>
            <div class="field">
                <label>Horarios disponibles <small class="muted">(según el horario de la sucursal)</small></label>
                <div class="avail-slots" aria-live="polite"><span class="muted" style="font-size:.85rem">Elige servicio y fecha para consultar.</span></div>
            </div>
            <div class="field">
                <label>Notas de la reserva</label>
                <textarea class="input" name="notas" rows="2" maxlength="500" placeholder="Indicaciones especiales..."></textarea>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('calendar', 18) ?> Registrar cita</button>
        </form>
    </section>

    <section class="panel">
        <?php if ($editarReserva !== null): ?>
            <div class="panel-head"><h3><?= icon('edit', 18) ?> Reprogramar cita #<?= (int)$editarReserva['id_reserva'] ?></h3></div>
            <p class="muted" style="font-size:.87rem;margin-bottom:1rem">
                Clienta: <b><?= e($editarReserva['id_usuario']) ?></b> ·
                Fecha actual: <?= e(date('d/m/Y', strtotime((string)$editarReserva['fecha']))) ?> <?= e(substr((string)$editarReserva['hora'], 0, 5)) ?>
            </p>
            <form method="post" action="<?= ruta('empleado_citas') ?>" data-agenda data-excluir="<?= (int)$editarReserva['id_reserva'] ?>">
                <?= csrf() ?>
                <input type="hidden" name="accion" value="reprogramar">
                <input type="hidden" name="id_reserva" value="<?= (int)$editarReserva['id_reserva'] ?>">
                <div class="field">
                    <label>Nuevo servicio *</label>
                    <select class="input" name="id_servicio" required>
                        <option value="">Selecciona un servicio...</option>
                        <?php foreach (($servicios ?? []) as $s): ?>
                            <option value="<?= e($s['id_servicio']) ?>" <?= $editarServicio === $s['id_servicio'] ? 'selected' : '' ?> data-duracion="<?= (int)$s['duracion_minutos'] ?>">
                                <?= e($s['nombre']) ?> · <?= bs($s['precio']) ?> · <?= (int)$s['duracion_minutos'] ?> min
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Nueva fecha *</label>
                        <input class="input" type="date" name="fecha" min="<?= e($hoy) ?>" value="<?= e($editarReserva['fecha']) ?>" required>
                    </div>
                    <div class="field">
                        <label>Nueva hora *</label>
                        <input class="input" type="time" name="hora" value="<?= e(substr((string)$editarReserva['hora'], 0, 5)) ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label>Horarios disponibles</label>
                    <div class="avail-slots" aria-live="polite"><span class="muted" style="font-size:.85rem">Consultando...</span></div>
                </div>
                <div style="display:flex;gap:.6rem">
                    <a class="btn btn-outline" style="flex:1" href="<?= ruta('empleado_citas', array_filter($filtros)) ?>">Cancelar</a>
                    <button class="btn btn-primary" style="flex:1" type="submit">Guardar cambio</button>
                </div>
            </form>
        <?php else: ?>
            <div class="panel-head"><h3><?= icon('clock', 18) ?> Horario de atención</h3></div>
            <p class="muted" style="font-size:.9rem">
                Cada cita debe quedar dentro del horario activo de <?= e($sucursalNombre) ?>.
                El sistema bloquea cruces de horario, citas duplicadas y fechas pasadas.
            </p>
            <div class="ficha">
                <div class="it"><small>Alerta de agenda</small>Las citas pendientes aparecen en la campana del panel.</div>
                <div class="it"><small>Reprogramación</small>Usa el ícono de lápiz en una cita para cambiar fecha, hora o servicio.</div>
                <div class="it"><small>Ficha de piel</small>Abre una cita y registra las observaciones de la sesión.</div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php if ($fc !== null): $cl = $fc['usuario']; $rv = $fc['reserva']; ?>
<section class="panel" style="margin-top:1.4rem">
    <div class="panel-head">
        <h3><?= icon('file', 18) ?> Ficha de piel · Cita #<?= (int)$rv['id_reserva'] ?></h3>
        <span class="muted" style="font-size:.85rem"><?= e(date('d/m/Y', strtotime((string)$rv['fecha']))) ?> · <?= e(substr((string)$rv['hora'], 0, 5)) ?></span>
    </div>
    <?php if ($cl === null): ?>
        <?= vacio('No se encontraron los datos de la clienta.', 'user') ?>
    <?php else: ?>
    <div class="split" style="grid-template-columns:1fr 1.3fr">
        <div>
            <div class="person" style="margin-bottom:1.2rem">
                <span class="avatar" style="width:46px;height:46px"><?= e(mb_substr((string)$cl['nombre'], 0, 1)) ?></span>
                <div>
                    <b style="color:var(--morado)"><?= e(trim($cl['nombre'] . ' ' . $cl['ap_paterno'] . ' ' . $cl['ap_materno'])) ?></b><br>
                    <small class="muted"><?= e($cl['telefono'] ?: 'Sin teléfono') ?><?= !empty($cl['correo']) ? ' · ' . e($cl['correo']) : '' ?></small>
                </div>
            </div>
            <?php if (empty($fc['historial'])): ?>
                <p class="muted" style="font-size:.9rem">Esta clienta aún no tiene una ficha de piel en esta sucursal.</p>
            <?php else: $h = $fc['historial'][0]; ?>
                <div class="ficha">
                    <div class="it alergia"><small>⚠ Alergias</small><?= e($h['alergias'] ?: 'ninguna registrada') ?></div>
                    <div class="it"><small>Objetivo / tipo de piel</small><?= e($h['objetivo_piel'] ?: '—') ?></div>
                    <div class="it"><small>Rutina actual</small><?= e($h['rutina_actual'] ?: '—') ?></div>
                    <div class="it"><small>Recomendaciones abiertas</small><?= !empty($h['abierto_recomendaciones']) ? 'Sí' : 'No' ?></div>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <h3 style="margin:0 0 .8rem;font-size:1.1rem">Historial de sesiones</h3>
            <?php if (empty($fc['historial'])): ?>
                <p class="muted" style="font-size:.9rem">Sin registros previos.</p>
            <?php else: ?>
                <div class="timeline" style="max-height:260px;overflow:auto;padding-right:.4rem">
                    <?php foreach ($fc['historial'] as $hist): ?>
                        <div class="ev">
                            <b><?= e(date('d/m/Y', strtotime((string)$hist['fecha_registro']))) ?></b>
                            <?php if (!empty($hist['servicios'])): ?><small class="muted"> · <?= e($hist['servicios']) ?></small><?php endif; ?><br>
                            <small class="muted"><?= e(mb_strimwidth((string)($hist['observaciones_sesion'] ?: 'Sin observaciones'), 0, 150, '…')) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= ruta('empleado_citas') ?>" style="margin-top:1.2rem">
                <?= csrf() ?>
                <input type="hidden" name="accion" value="observacion">
                <input type="hidden" name="id_reserva" value="<?= (int)$rv['id_reserva'] ?>">
                <input type="hidden" name="ficha" value="<?= (int)$rv['id_reserva'] ?>">
                <input type="hidden" name="fecha" value="<?= e($fecha ?? '') ?>">
                <input type="hidden" name="estado" value="<?= e($estado ?? '') ?>">
                <input type="hidden" name="cliente" value="<?= e($idCliente ?? '') ?>">
                <div class="field">
                    <label>Observaciones de la sesión</label>
                    <textarea class="input" name="observaciones" rows="3" required maxlength="2000" placeholder="Notas del tratamiento, respuesta de la piel, indicaciones..."></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Guardar observación</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<script>
(function () {
    'use strict';
    var endpoint = <?= json_encode(ruta('empleado_disponibilidad')) ?>;

    document.querySelectorAll('form[data-agenda]').forEach(function (form) {
        var fecha = form.querySelector('input[name="fecha"]');
        var servicio = form.querySelector('select[name="id_servicio"]');
        var hora = form.querySelector('input[name="hora"]');
        var slots = form.querySelector('.avail-slots');
        var excluir = form.getAttribute('data-excluir') || '';
        if (!fecha || !servicio || !hora || !slots) return;

        function pintar(horas, mensaje) {
            slots.innerHTML = '';
            if (!horas || !horas.length) {
                var p = document.createElement('span');
                p.className = 'muted';
                p.style.fontSize = '.85rem';
                p.textContent = mensaje || 'No hay horarios disponibles.';
                slots.appendChild(p);
                return;
            }
            horas.forEach(function (h) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'avail-chip' + (hora.value === h ? ' active' : '');
                b.textContent = h;
                b.addEventListener('click', function () {
                    hora.value = h;
                    slots.querySelectorAll('.avail-chip').forEach(function (x) { x.classList.remove('active'); });
                    b.classList.add('active');
                });
                slots.appendChild(b);
            });
        }

        function consultar() {
            if (!fecha.value || !servicio.value) return;
            slots.innerHTML = '<span class="muted" style="font-size:.85rem">Consultando horarios...</span>';
            var url = endpoint + '&fecha=' + encodeURIComponent(fecha.value) +
                '&servicio=' + encodeURIComponent(servicio.value) +
                (excluir ? '&excluir=' + encodeURIComponent(excluir) : '');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        pintar([], data.mensaje || 'No se pudo consultar la disponibilidad.');
                        return;
                    }
                    pintar(data.horas || [], data.mensaje);
                })
                .catch(function () {
                    pintar([], 'No se pudo consultar la disponibilidad.');
                });
        }

        fecha.addEventListener('change', consultar);
        servicio.addEventListener('change', consultar);
        consultar();
    });
})();
</script>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
