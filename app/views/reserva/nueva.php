<?php
$titulo = 'Reservar cita';
$activo = 'servicios';
require __DIR__ . '/../layout/header.php';

$selServicio = (string)($idServicio ?? ($servicios[0]['id_servicio'] ?? ''));
$selSucursal = (string)($idSucursal ?? ($sucursales[0]['id_sucursal'] ?? ''));
$selFecha    = (string)($fecha ?? date('Y-m-d', strtotime('+1 day')));
$selHora     = (string)($hora ?? '');
$selMetodo   = (string)($metodoSeleccionado ?? '');
$zonas       = \App\Models\Servicio::zonasDepilacion();
$tipoPielSel = (string)($_POST['tipo_piel'] ?? '');
$objetivosSel = array_map('strval', (array)($_POST['objetivo'] ?? []));
$zonaSel     = strtolower(trim((string)($_POST['zona'] ?? '')));
$tiposPiel   = ['Normal', 'Seca', 'Grasa', 'Mixta', 'Sensible', 'No lo sé'];
$objetivos   = ['Hidratación', 'Luminosidad', 'Antiedad', 'Control de acné', 'Manchas', 'Poros'];
$servicioPorId = [];
foreach ($servicios as $s) {
    $servicioPorId[(string)$s['id_servicio']] = $s;
}
$tipoSel = isset($servicioPorId[$selServicio]) ? \App\Models\Servicio::tipoTratamiento($servicioPorId[$selServicio]) : 'facial';
$preguntasZona = [
    'axilas'  => ['axilas_desodorante' => '¿Usas desodorante o antitranspirante?', 'axilas_irritacion' => '¿Tienes irritación en las axilas?'],
    'piernas' => ['piernas_exfoliacion' => '¿Exfolias la zona regularmente?', 'piernas_reseca' => '¿Sientes la piel reseca?'],
    'brazos'  => ['brazos_exfoliacion' => '¿Exfolias la zona regularmente?', 'brazos_reseca' => '¿Sientes la piel reseca?'],
    'rostro'  => ['rostro_acidos' => '¿Usaste retinoides o ácidos en los últimos 7 días?', 'rostro_sensible' => '¿Tu rostro es sensible?'],
    'bozo'    => ['bozo_acidos' => '¿Usaste retinoides o ácidos en los últimos 7 días?', 'bozo_sensible' => '¿La zona es sensible?'],
    'cejas'   => ['cejas_acidos' => '¿Usaste retinoides o ácidos en los últimos 7 días?', 'cejas_sensible' => '¿La zona es sensible?'],
    'pies'    => ['pies_hongos' => '¿Tienes hongos en los pies?', 'pies_heridas' => '¿Tienes heridas o grietas?'],
    'otras'   => ['otras_sensibilidad' => '¿La zona es sensible?'],
];
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Reserva en línea</span>
        <h1>Agenda tu cita</h1>
        <p>Elige tratamiento, sucursal, una hora disponible y cuéntanos sobre tu piel.</p>
    </div>
</div>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <?php $paso = 1; require __DIR__ . '/_stepper.php'; ?>

        <?php if (!empty($errores['_general'])): ?>
            <div class="alert warn"><?= e($errores['_general'][0]) ?></div>
        <?php endif; ?>

        <?php if (!$servicios): ?>
            <div class="empty">
                <?= icon('leaf', 40) ?>
                <p>Aún no hay servicios publicados. Vuelve pronto o contáctanos para agendar manualmente.</p>
                <a class="btn btn-outline" href="<?= ruta('home') ?>">Volver al inicio</a>
            </div>
        <?php else: ?>
        <form method="post" action="<?= ruta('reserva_nueva') ?>" class="book-layout" id="form-reserva">
            <?= csrf() ?>
            <div class="form-card">
                <div class="field">
                    <span class="label">1. Elige tu servicio</span>
                    <div class="option-cards">
                        <?php foreach ($servicios as $s): ?>
                        <label class="opt">
                            <input type="radio" name="servicio" value="<?= e($s['id_servicio']) ?>"
                                   data-nombre="<?= e($s['nombre']) ?>"
                                   data-precio="<?= (float)$s['precio'] ?>"
                                   data-duracion="<?= (int)$s['duracion_minutos'] ?>"
                                   data-tipo="<?= e(\App\Models\Servicio::tipoTratamiento($s)) ?>"
                                   <?= $selServicio === (string)$s['id_servicio'] ? 'checked' : '' ?>>
                            <div class="box">
                                <h4><?= e($s['nombre']) ?></h4>
                                <small><?= icon('clock', 13) ?> <?= (int)$s['duracion_minutos'] ?> min · <?= e($s['categoria'] ?? 'General') ?></small>
                                <div class="p"><?= bs($s['precio']) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?= error_campo($errores, 'servicio') ?>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="sucursal">2. Sucursal</label>
                        <select class="input" id="sucursal" name="sucursal">
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= e($s['id_sucursal']) ?>" <?= $selSucursal === (string)$s['id_sucursal'] ? 'selected' : '' ?>><?= e($s['nombre_sucursal']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= error_campo($errores, 'sucursal') ?>
                    </div>
                    <div class="field">
                        <label for="fecha">3. Fecha</label>
                        <input class="input" id="fecha" type="date" name="fecha" value="<?= e($selFecha) ?>" min="<?= date('Y-m-d') ?>">
                        <?= error_campo($errores, 'fecha') ?>
                    </div>
                </div>

                <div class="field">
                    <button class="btn btn-outline btn-sm" type="submit" name="accion" value="horarios">
                        <?= icon('calendar', 16) ?> Actualizar horarios disponibles
                    </button>
                    <span class="hint">Cambia la sucursal o la fecha y pulsa este botón para recalcular la agenda. No crea la reserva.</span>
                </div>

                <div class="field">
                    <span class="label">4. Horas disponibles</span>
                    <?php if (!$horas): ?>
                        <div class="empty" style="padding:1.5rem 1rem">
                            <p>No hay horarios configurados para esa sucursal y fecha. Elige otra combinación o contáctanos.</p>
                        </div>
                    <?php else: ?>
                    <div class="slots">
                        <?php foreach ($horas as $h): ?>
                        <label class="slot">
                            <input type="radio" name="hora" value="<?= e($h['hora']) ?>" <?= !$h['libre'] ? 'disabled' : '' ?> <?= $selHora === $h['hora'] ? 'checked' : '' ?>>
                            <span><?= e($h['hora']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?= error_campo($errores, 'hora') ?>
                    <span class="hint">Las horas tachadas ya están ocupadas. Solo se muestran horarios dentro del horario de atención.</span>
                </div>

                <!-- Preguntas dinámicas: cambian según el servicio elegido -->
                <div class="field">
                    <span class="label">5. Cuéntanos sobre tu piel</span>
                </div>

                <div id="panel-facial" class="panel-preguntas">
                    <div class="form-grid">
                        <div class="field">
                            <label for="tipo_piel">Tipo de piel</label>
                            <select class="input<?= clase_campo($errores, 'tipo_piel') ?>" id="tipo_piel" name="tipo_piel">
                                <option value="">— Selecciona —</option>
                                <?php foreach ($tiposPiel as $tp): ?>
                                    <option value="<?= e($tp) ?>" <?= $tipoPielSel === $tp ? 'selected' : '' ?>><?= e($tp) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= error_campo($errores, 'tipo_piel') ?>
                        </div>
                        <div class="field">
                            <label>Objetivo del tratamiento</label>
                            <div class="chips">
                                <?php foreach ($objetivos as $i => $obj): $idObj = 'obj_' . $i; ?>
                                <label class="chip" for="<?= $idObj ?>">
                                    <input type="checkbox" id="<?= $idObj ?>" name="objetivo[]" value="<?= e($obj) ?>" <?= in_array($obj, $objetivosSel, true) ? 'checked' : '' ?>>
                                    <span><?= e($obj) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?= error_campo($errores, 'objetivo') ?>
                            <span class="hint">Puedes elegir más de uno.</span>
                        </div>
                    </div>

                    <div class="field">
                        <label>¿Te has realizado un tratamiento facial antes?</label>
                        <?= radios_si_no('tratamiento_previo', (string)($_POST['tratamiento_previo'] ?? '')) ?>
                        <?= error_campo($errores, 'tratamiento_previo') ?>
                    </div>
                    <div class="field" data-si-no="tratamiento_previo" hidden>
                        <label for="tratamiento_previo_detalle">¿Cuál y cuándo?</label>
                        <input class="input" id="tratamiento_previo_detalle" type="text" name="tratamiento_previo_detalle" maxlength="150" value="<?= e($_POST['tratamiento_previo_detalle'] ?? '') ?>" placeholder="Ej.: limpieza facial hace 3 meses">
                        <?= error_campo($errores, 'tratamiento_previo_detalle') ?>
                    </div>
                </div>

                <div id="panel-depilacion" class="panel-preguntas">
                    <div class="form-grid">
                        <div class="field">
                            <label for="zona">Zona a depilar</label>
                            <select class="input<?= clase_campo($errores, 'zona') ?>" id="zona" name="zona">
                                <option value="">— Selecciona la zona —</option>
                                <?php foreach ($zonas as $clave => $nombre): ?>
                                    <option value="<?= e($clave) ?>" <?= $zonaSel === $clave ? 'selected' : '' ?>><?= e($nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= error_campo($errores, 'zona') ?>
                        </div>
                        <div class="field" id="zona-otra-wrap" hidden>
                            <label for="zona_otra">Describe la zona</label>
                            <input class="input" id="zona_otra" type="text" name="zona_otra" maxlength="60" value="<?= e($_POST['zona_otra'] ?? '') ?>" placeholder="Ej.: línea del abdomen">
                            <?= error_campo($errores, 'zona_otra') ?>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label>¿Te has depilado antes?</label>
                            <?= radios_si_no('depil_previa', (string)($_POST['depil_previa'] ?? '')) ?>
                            <?= error_campo($errores, 'depil_previa') ?>
                        </div>
                        <div class="field">
                            <label>¿Tienes la piel sensible?</label>
                            <?= radios_si_no('sensibilidad', (string)($_POST['sensibilidad'] ?? '')) ?>
                            <?= error_campo($errores, 'sensibilidad') ?>
                        </div>
                    </div>

                    <?php foreach ($preguntasZona as $claveZona => $preguntas): ?>
                        <div class="zona-bloque" data-zona="<?= e($claveZona) ?>" hidden>
                            <?php foreach ($preguntas as $campo => $etiqueta): ?>
                                <div class="field">
                                    <label><?= e($etiqueta) ?></label>
                                    <?= radios_si_no($campo, (string)($_POST[$campo] ?? '')) ?>
                                    <?= error_campo($errores, $campo) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="field">
                    <label>¿Tienes alergias o sensibilidades?</label>
                    <?= radios_si_no('alergias', (string)($_POST['alergias'] ?? '')) ?>
                    <?= error_campo($errores, 'alergias') ?>
                </div>
                <div class="field" data-si-no="alergias" hidden>
                    <label for="alergias_detalle">Describe tus alergias</label>
                    <textarea class="input" id="alergias_detalle" name="alergias_detalle" maxlength="300" placeholder="Medicamentos, productos, látex..."><?= e($_POST['alergias_detalle'] ?? '') ?></textarea>
                    <?= error_campo($errores, 'alergias_detalle') ?>
                </div>

                <div class="field">
                    <label for="observaciones">Observaciones para el personal (opcional)</label>
                    <textarea class="input" id="observaciones" name="observaciones" maxlength="600" placeholder="¿Algo que debamos saber antes de tu visita?"><?= e($_POST['observaciones'] ?? '') ?></textarea>
                    <?= error_campo($errores, 'observaciones') ?>
                </div>

                <div class="field">
                    <label for="id_metodoPago">6. Método de pago</label>
                    <?php if ($metodos): ?>
                        <select class="input<?= clase_campo($errores, 'id_metodoPago') ?>" id="id_metodoPago" name="id_metodoPago">
                            <?php foreach ($metodos as $m): ?>
                                <option value="<?= e($m['id_metodoPago']) ?>" <?= $selMetodo === (string)$m['id_metodoPago'] ? 'selected' : '' ?>>
                                    <?= e($m['nombre_metodoPago']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p class="hint">Aún no hay métodos de pago activos; el personal te indicará cómo pagar.</p>
                    <?php endif; ?>
                    <?= error_campo($errores, 'id_metodoPago') ?>
                    <span class="hint">Con QR/transferencia abonas un anticipo de <?= bs($anticipoMonto) ?> (o el total si el servicio cuesta menos). Con efectivo pagas el total en sucursal el día de tu cita.</span>
                </div>
            </div>

            <aside class="card card-pad summary">
                <h3 style="margin-bottom:1rem">Resumen de tu reserva</h3>
                <div class="summary-row"><span>Servicio</span><span id="res-nombre">—</span></div>
                <div class="summary-row"><span>Duración</span><span id="res-duracion">—</span></div>
                <div class="summary-row"><span>Precio</span><span id="res-precio"><?= bs(0) ?></span></div>
                <div class="summary-row"><span>Anticipo</span><span id="res-anticipo"><?= bs(0) ?></span></div>
                <div class="summary-row total"><span>Saldo en sucursal</span><strong id="res-saldo"><?= bs(0) ?></strong></div>
                <div class="alert info" id="res-nota" style="margin:1.2rem 0">
                    <?= icon('shield', 20) ?>
                    <span id="res-nota-texto">Selecciona un método de pago para ver el anticipo.</span>
                </div>

                <div id="res-productos" style="margin-bottom:1rem"></div>

                <button class="btn btn-primary btn-block" type="submit">Continuar</button>
                <p class="hint muted" style="text-align:center;margin-top:.8rem;font-size:.8rem">
                    Si eliges efectivo, la reserva queda pendiente de pago en sucursal. Si eliges QR, deberás enviar el comprobante del anticipo.
                </p>
            </aside>
        </form>

        <?php foreach ($productosPorServicio as $sid => $prods): ?>
            <template id="prod-servicio-<?= e($sid) ?>">
                <?php if ($prods): ?>
                    <span class="label" style="display:block;margin-bottom:.5rem">Productos recomendados</span>
                    <?php foreach ($prods as $p): ?>
                        <div class="summary-row" style="font-size:.86rem">
                            <a class="link" href="<?= ruta('producto', ['id' => $p['id']]) ?>"><?= e($p['nombre']) ?></a>
                            <span><?= bs($p['precio']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </template>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var ANTICIPO = <?= json_encode((float)$anticipoMonto) ?>;
    var panelFacial = document.getElementById('panel-facial');
    var panelDepil = document.getElementById('panel-depilacion');
    var zonaSelect = document.getElementById('zona');
    var metodoSelect = document.getElementById('id_metodoPago');
    var ultimoTipo = null;

    function tipoSeleccionado() {
        var s = document.querySelector('input[name="servicio"]:checked');
        return s ? s.dataset.tipo : 'facial';
    }

    function limpiar(panel) {
        if (!panel) return;
        panel.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.type === 'radio' || el.type === 'checkbox') {
                el.checked = false;
            } else if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });
    }

    function habilitarPanel(panel, activo) {
        if (!panel) return;
        panel.hidden = !activo;
        panel.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.disabled = !activo;
        });
    }

    function actualizarZona() {
        if (!zonaSelect) return;
        var valor = zonaSelect.value;
        document.querySelectorAll('.zona-bloque').forEach(function (bloque) {
            var activo = bloque.dataset.zona === valor;
            bloque.hidden = !activo;
            bloque.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !activo || panelDepil.hidden;
            });
        });
        var otra = document.getElementById('zona-otra-wrap');
        if (otra) {
            var mostrar = valor === 'otras';
            otra.hidden = !mostrar;
            otra.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !mostrar || panelDepil.hidden;
            });
        }
    }

    function refrescarDetalles() {
        document.querySelectorAll('[data-si-no]').forEach(function (bloque) {
            var grupo = bloque.dataset.siNo;
            var activo = false;
            if (grupo) {
                var sel = document.querySelector('input[name="' + grupo + '"]:checked:not(:disabled)');
                activo = !!sel && sel.value === 'si';
            }
            bloque.hidden = !activo;
            bloque.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !activo;
            });
        });
    }

    function actualizarResumen() {
        var s = document.querySelector('input[name="servicio"]:checked');
        if (!s) return;
        var precio = parseFloat(s.dataset.precio || '0');
        var efectivo = metodoSelect && metodoSelect.value === 'MP01';
        var anticipo = efectivo ? 0 : Math.min(ANTICIPO, precio);
        var saldo = Math.max(0, precio - anticipo);
        var nombreMetodo = metodoSelect && metodoSelect.options[metodoSelect.selectedIndex]
            ? metodoSelect.options[metodoSelect.selectedIndex].text : '';
        var fmt = function (n) { return 'Bs. ' + n.toFixed(2); };

        document.getElementById('res-nombre').textContent = s.dataset.nombre || '—';
        document.getElementById('res-duracion').textContent = (s.dataset.duracion || '0') + ' min';
        document.getElementById('res-precio').textContent = fmt(precio);
        document.getElementById('res-anticipo').textContent = fmt(anticipo);
        document.getElementById('res-saldo').textContent = fmt(saldo);

        var nota = document.getElementById('res-nota-texto');
        if (nota) {
            nota.textContent = efectivo
                ? 'Pago en efectivo: abonas el total en sucursal. No se requiere QR ni comprobante.'
                : 'Anticipo por QR/transferencia (' + nombreMetodo + '): ' + fmt(anticipo) + '. El saldo de ' + fmt(saldo) + ' se paga el día de la cita.';
        }

        var cont = document.getElementById('res-productos');
        if (cont) {
            cont.innerHTML = '';
            var tpl = document.getElementById('prod-servicio-' + s.value);
            if (tpl) {
                cont.appendChild(tpl.content.cloneNode(true));
            }
        }
    }

    function refrescarPaneles(inicial) {
        var tipo = tipoSeleccionado();
        if (!inicial && tipo !== ultimoTipo) {
            if (tipo === 'facial') {
                limpiar(panelDepil);
            } else {
                limpiar(panelFacial);
            }
        }
        habilitarPanel(panelFacial, tipo === 'facial');
        habilitarPanel(panelDepil, tipo !== 'facial');
        actualizarZona();
        refrescarDetalles();
        actualizarResumen();
        ultimoTipo = tipo;
    }

    document.querySelectorAll('input[name="servicio"]').forEach(function (r) {
        r.addEventListener('change', function () { refrescarPaneles(false); });
    });
    if (metodoSelect) {
        metodoSelect.addEventListener('change', actualizarResumen);
    }
    if (zonaSelect) {
        zonaSelect.addEventListener('change', function () {
            actualizarZona();
            refrescarDetalles();
        });
    }
    document.querySelectorAll('#panel-facial input, #panel-depilacion input').forEach(function (r) {
        if (r.type === 'radio') {
            r.addEventListener('change', refrescarDetalles);
        }
    });

    refrescarPaneles(true);
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
