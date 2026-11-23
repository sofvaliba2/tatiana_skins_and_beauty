<?php
$titulo = 'Inicio';
$activo = 'home';
require __DIR__ . '/../layout/header.php';
?>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow">Centro de cuidado de la piel</span>
            <h1>Descubre tu <span class="script">piel real</span></h1>
            <p class="lead">
                Te acompañamos en cada paso del proceso con una guía honesta, personalizada y profesional.
                Reserva tu cita en línea y llega con todo listo.
            </p>
            <div class="hero-cta">
                <a href="<?= ruta('reserva_nueva') ?>" class="btn btn-primary"><?= icon('calendar', 18) ?> Reservar mi cita</a>
                <a href="<?= ruta('catalogo') ?>" class="btn btn-outline">Ver productos</a>
            </div>
            <div class="hero-stats">
                <div><strong><?= (int)($stats['clientes'] ?? 0) ?></strong><span>Clientes registradas</span></div>
                <div><strong><?= (int)($stats['sucursales'] ?? 0) ?></strong><span>Sucursales</span></div>
                <div><strong><?= (int)($stats['servicios'] ?? 0) ?></strong><span>Servicios</span></div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-arch"><?= lips('lips') ?></div>
            <div class="float-card a">
                <span class="dot"><?= icon('check', 18) ?></span>
                <div><strong>Cita confirmada</strong><span class="muted">Hydrafacial · 10:30</span></div>
            </div>
            <div class="float-card b">
                <span class="dot"><?= icon('sparkle', 18) ?></span>
                <div><strong>Rutina para ti</strong><span class="muted">Según tu tipo de piel</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Cómo funciona</span>
            <h2>Tu cita en cuatro pasos</h2>
            <p>Sin llamadas ni cuadernos: reservas desde tu celular y nosotras nos encargamos del resto.</p>
        </div>
        <div class="pasos">
            <div class="paso"><h3>Elige tu servicio</h3><p>Escoge el tratamiento, la sucursal, el día y una hora disponible.</p></div>
            <div class="paso"><h3>Asegura con un anticipo</h3><p>Paga un pequeño monto por QR y sube tu comprobante.</p></div>
            <div class="paso"><h3>Cuéntanos de tu piel</h3><p>Un breve formulario con alergias, objetivos y rutina actual.</p></div>
            <div class="paso"><h3>Recibe recomendaciones</h3><p>Cremas y cuidados sugeridos según tu experiencia con nosotras.</p></div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Nuestros tratamientos</span>
            <h2>Servicios destacados</h2>
        </div>
        <div class="grid-3">
            <?php if (empty($servicios)): ?>
                <p class="muted" style="grid-column:1/-1;text-align:center;padding:2rem 0">Pronto publicaremos nuestros tratamientos. <a href="<?= ruta('ayuda') ?>">Contáctanos</a>.</p>
            <?php endif; ?>
            <?php foreach (($servicios ?? []) as $s): ?>
                <?php $fotoServicio = trim((string)($s['imagen_url'] ?? '')); ?>
            <article class="card servicio-card">
                <?php if ($fotoServicio !== '' && archivo_existe($fotoServicio)): ?>
                    <div class="foto"><img src="<?= e(url_archivo($fotoServicio)) ?>" alt="<?= e($s['nombre']) ?>"></div>
                <?php else: ?>
                    <div class="ico"><?= icon($s['icono'] ?? 'sparkle', 28) ?></div>
                <?php endif; ?>
                <h3><?= e($s['nombre']) ?></h3>
                <p class="muted" style="font-size:.92rem"><?= e($s['descripcion']) ?></p>
                <div class="meta"><span><?= icon('clock', 15) ?> <?= (int)$s['duracion_minutos'] ?> min</span></div>
                <div class="precio">Bs. <?= number_format($s['precio'], 0) ?></div>
                <a href="<?= ruta('reserva_nueva') ?>" class="btn btn-outline btn-sm" style="margin-top:1rem">Reservar</a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($promociones)): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Ofertas vigentes</span>
            <h2>Promociones</h2>
            <p>Aprovecha nuestros descuentos en tratamientos y productos seleccionados.</p>
        </div>
        <div class="grid-3">
            <?php foreach ($promociones as $p):
                $imagen = trim((string)($p['imagen_url'] ?? ''));
                $dias = (int)($p['dias_restantes'] ?? 0);
                $enlace = !empty($p['id_producto'])
                    ? ruta('producto', ['id' => $p['id_producto']])
                    : (!empty($p['id_servicio']) ? ruta('reserva_nueva', ['servicio' => $p['id_servicio']]) : ruta('promociones'));
            ?>
            <article class="card promo-card">
                <div class="producto-img">
                    <?php if ($imagen !== ''): ?>
                        <img src="<?= e(url_archivo($imagen)) ?>" alt="<?= e($p['nombre']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
                    <?php else: ?>
                        <?= icon('heart', 40) ?>
                    <?php endif; ?>
                    <span class="promo-tag">-<?= number_format((float)$p['descuento'], 0) ?>%</span>
                </div>
                <div class="producto-body">
                    <span class="cat"><?= e($p['articulo'] ?? 'Promoción') ?></span>
                    <h3><?= e($p['nombre']) ?></h3>
                    <div class="producto-foot">
                        <span>
                            <s class="muted" style="font-size:.85rem"><?= bs($p['precio_original']) ?></s>
                            <span class="precio-lg"><?= bs($p['precio_promocional']) ?></span>
                        </span>
                        <a class="btn btn-primary btn-sm" href="<?= e($enlace) ?>">Ver</a>
                    </div>
                    <small class="muted" style="display:block;margin-top:.6rem"><?= icon('clock', 13) ?> Hasta <?= e(date('d/m/Y', strtotime((string)$p['fecha_fin']))) ?><?= $dias <= 7 ? ' · ¡Últimos ' . $dias . ' día(s)!' : '' ?></small>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1.8rem">
            <a class="btn btn-outline" href="<?= ruta('promociones') ?>">Ver todas las promociones</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="valores section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Lo que nos define</span>
            <h2>Valores de marca</h2>
        </div>
        <div class="valores-grid">
            <div class="valor"><div class="num">1</div><h3>Acompañamiento</h3><p>Caminamos contigo en cada etapa del cuidado de tu piel.</p></div>
            <div class="valor"><div class="num">2</div><h3>Transparencia</h3><p>Explicaciones sencillas y claras, sin letra chica.</p></div>
            <div class="valor"><div class="num">3</div><h3>Confianza</h3><p>Autoridad técnica con trato cercano y empático.</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Tienda</span>
            <h2>Productos para tu piel</h2>
        </div>
        <div class="grid-4">
            <?php if (empty($productos)): ?>
                <p class="muted" style="grid-column:1/-1;text-align:center;padding:2rem 0">La tienda está en preparación. <a href="<?= ruta('catalogo') ?>">Ver catálogo</a>.</p>
            <?php endif; ?>
            <?php foreach (array_slice(($productos ?? []), 0, 4) as $p): ?>
            <article class="card">
                <div class="producto-img"><?= frasco() ?></div>
                <div class="producto-body">
                    <span class="cat"><?= e($p['categoria']) ?></span>
                    <h3><?= e($p['nombre_producto']) ?></h3>
                    <div class="producto-foot">
                        <span class="precio-lg">Bs. <?= number_format($p['precio'], 0) ?></span>
                        <a class="btn btn-primary btn-sm" href="<?= ruta('catalogo') ?>">Ver</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="cta-band">
            <span class="eyebrow" style="color:var(--lila)">Tu piel, nuestra prioridad</span>
            <h2 style="margin-top:.5rem">La mejor versión de tu piel</h2>
            <p>Reserva hoy y empieza tu historial de cuidado personalizado.</p>
            <a href="<?= ruta('registro') ?>" class="btn btn-light">Crear mi cuenta</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
