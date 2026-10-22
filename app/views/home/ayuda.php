<?php
$titulo = 'Ayuda y contacto';
$activo = 'ayuda';
require __DIR__ . '/../layout/header.php';

$dias = ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'];
$porSucursal = [];
foreach (($sucursales ?? []) as $h) {
    $porSucursal[$h['id_sucursal']] = [
        'sucursal' => $h,
        'horarios' => [],
    ];
    if ($h['dia_semana'] !== null) {
        $porSucursal[$h['id_sucursal']]['horarios'][] = $h;
    }
}
?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Estamos para ayudarte</span>
            <h2>Preguntas frecuentes</h2>
        </div>
        <div class="grid-3">
            <article class="card">
                <h3><?= icon('calendar', 20) ?> ¿Cómo reservo?</h3>
                <p class="muted">Crea tu cuenta, elige el servicio y la sucursal, selecciona día y hora disponibles, paga el anticipo por QR y sube el comprobante. Te confirmamos por el sistema.</p>
            </article>
            <article class="card">
                <h3><?= icon('clock', 20) ?> ¿Puedo cancelar?</h3>
                <p class="muted">Sí. En "Mis reservas" puedes cancelar mientras la cita esté pendiente o confirmada. Para cambios de hora, cancela y reserva de nuevo.</p>
            </article>
            <article class="card">
                <h3><?= icon('shield', 20) ?> ¿Guardan mis datos?</h3>
                <p class="muted">Tu historial (alergias, objetivos y rutina) se usa únicamente para personalizar tu tratamiento y recomendarte productos. Nunca lo compartimos.</p>
            </article>
            <article class="card">
                <h3><?= icon('key', 20) ?> Olvidé mi contraseña</h3>
                <p class="muted">Escríbenos o acércate a cualquier sucursal y el personal restablece tu contraseña al instante, verificando tu identidad.</p>
            </article>
            <article class="card">
                <h3><?= icon('drop', 20) ?> ¿Qué llevo a la cita?</h3>
                <p class="muted">Nada especial: solo tu piel sin maquillaje si es facial. El resto (insumos y equipos) lo tenemos nosotros.</p>
            </article>
            <article class="card">
                <h3><?= icon('cart', 20) ?> ¿Cómo compro productos?</h3>
                <p class="muted">Añade al carrito desde el catálogo, elige el método de pago y confirma. Te avisamos cuando tu pedido esté listo para recoger o entrega.</p>
            </article>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Visítanos</span>
            <h2>Nuestras sucursales</h2>
        </div>
        <?php if (empty($porSucursal)): ?>
            <?= vacio('Pronto publicaremos nuestras sucursales y horarios.', 'pin') ?>
        <?php endif; ?>
        <div class="grid-2">
            <?php foreach ($porSucursal as $datos): $s = $datos['sucursal']; ?>
            <article class="card" style="padding:1.8rem">
                <h3><?= icon('pin', 20) ?> <?= e($s['nombre_sucursal']) ?></h3>
                <p class="muted" style="margin:.5rem 0"><?= e($s['direccion'] ?? 'Dirección por confirmar') ?></p>
                <?php if (!empty($s['telefono'])): ?>
                    <p class="muted"><?= icon('phone', 15) ?> <?= e($s['telefono']) ?></p>
                <?php endif; ?>
                <?php if ($datos['horarios'] !== []): ?>
                    <table style="width:100%;margin-top:1rem;font-size:.92rem">
                        <?php foreach ($datos['horarios'] as $h): ?>
                        <tr>
                            <td style="padding:.25rem 0;color:var(--morado)"><?= e($h['dia_semana']) ?></td>
                            <td style="padding:.25rem 0;text-align:right">
                                <?= $h['hora_apertura'] !== null
                                    ? e(substr((string)$h['hora_apertura'], 0, 5) . ' – ' . substr((string)$h['hora_cierre'], 0, 5))
                                    : 'Cerrado' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="muted" style="font-size:.88rem;margin-top:.75rem">Horarios no publicados aún. Contáctanos para coordinar tu visita.</p>
                <?php endif; ?>
                <a href="<?= ruta('reserva_nueva', ['sucursal' => $s['id_sucursal']]) ?>" class="btn btn-outline btn-sm" style="margin-top:1rem">Reservar aquí</a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container" style="max-width:640px">
        <div class="cta-band">
            <h2>¿Necesitas hablar con nosotras?</h2>
            <p>Escríbenos a <b>hola@tatiana.bo</b> o llama al <b>+591 700 00000</b>. Respondemos de lunes a sábado.</p>
            <a href="<?= ruta('reserva_nueva') ?>" class="btn btn-light">Reservar mi cita</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
