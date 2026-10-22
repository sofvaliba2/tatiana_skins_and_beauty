<?php
$titulo = 'Reservar cita';
$activo = 'servicios';
require __DIR__ . '/../layout/header.php';
?>

<div class="page-head">
    <div class="container">
        <span class="eyebrow">Reserva en línea</span>
        <h1>Agenda tu cita</h1>
        <p>Elige tratamiento, sucursal y una hora disponible.</p>
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
        <form method="post" action="<?= ruta('reserva_nueva') ?>" class="book-layout">
            <?= csrf() ?>
            <div class="form-card">
                <div class="field">
                    <span class="label">1. Elige tu servicio</span>
                    <div class="option-cards">
                        <?php foreach ($servicios as $i => $s): ?>
                        <label class="opt">
                            <input type="radio" name="servicio" value="<?= e($s['id_servicio']) ?>"
                                   data-nombre="<?= e($s['nombre']) ?>"
                                   data-precio="<?= (float)$s['precio'] ?>"
                                   data-duracion="<?= (int)$s['duracion_minutos'] ?>"
                                   <?= ($i === 0 && ($_POST['servicio'] ?? '') === '') || (($_POST['servicio'] ?? '') === $s['id_servicio']) ? 'checked' : '' ?>>
                            <div class="box">
                                <h4><?= e($s['nombre']) ?></h4>
                                <small><?= icon('clock', 13) ?> <?= (int)$s['duracion_minutos'] ?> min · <?= e($s['categoria'] ?? 'General') ?></small>
                                <div class="p">Bs. <?= number_format((float)$s['precio'], 0) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?= error_campo($errores, 'servicio') ?>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label>2. Sucursal</label>
                        <select class="input" name="sucursal" onchange="this.form.submit()">
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= e($s['id_sucursal']) ?>" <?= (($_POST['sucursal'] ?? '') === $s['id_sucursal']) ? 'selected' : '' ?>><?= e($s['nombre_sucursal']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= error_campo($errores, 'sucursal') ?>
                    </div>
                    <div class="field">
                        <label>3. Fecha</label>
                        <input class="input" type="date" name="fecha" value="<?= e($_POST['fecha'] ?? date('Y-m-d', strtotime('+1 day'))) ?>" min="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
                        <?= error_campo($errores, 'fecha') ?>
                    </div>
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
                            <input type="radio" name="hora" value="<?= e($h['hora']) ?>" <?= !$h['libre'] ? 'disabled' : '' ?> <?= (($_POST['hora'] ?? '') === $h['hora']) ? 'checked' : '' ?>>
                            <span><?= e($h['hora']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?= error_campo($errores, 'hora') ?>
                    <span class="hint">Solo se muestran horarios dentro del horario de atención de la sucursal. Las horas tachadas ya están ocupadas.</span>
                </div>

                <div class="field">
                    <label>Notas para el personal (opcional)</label>
                    <textarea class="input" name="notas" placeholder="¿Algo que debamos saber antes de tu visita?"><?= e($_POST['notas'] ?? '') ?></textarea>
                </div>
            </div>

            <aside class="card card-pad summary">
                <h3 style="margin-bottom:1rem">Resumen de tu reserva</h3>
                <div class="summary-row"><span>Servicio</span><span id="res-nombre">—</span></div>
                <div class="summary-row"><span>Duración</span><span id="res-duracion">—</span></div>
                <div class="summary-row"><span>Precio</span><span id="res-precio">Bs. 0</span></div>
                <div class="summary-row total"><span>Anticipo</span><strong>Bs. 30</strong></div>
                <div class="alert info" style="margin:1.2rem 0">
                    <?= icon('shield', 20) ?>
                    <span>Tu cita se confirma al pagar el anticipo por QR. El saldo se paga el día del tratamiento.</span>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Continuar al pago</button>
            </aside>
        </form>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var radios = document.querySelectorAll('input[name="servicio"]');
    function pintar() {
        var sel = document.querySelector('input[name="servicio"]:checked');
        if (!sel) return;
        document.getElementById('res-nombre').textContent = sel.dataset.nombre;
        document.getElementById('res-duracion').textContent = sel.dataset.duracion + ' min';
        document.getElementById('res-precio').textContent = 'Bs. ' + Number(sel.dataset.precio).toFixed(0);
    }
    radios.forEach(function (r) { r.addEventListener('change', pintar); });
    pintar();
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
