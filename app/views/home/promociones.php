<?php
$titulo = 'Promociones';
$activo = 'promociones';
require __DIR__ . '/../layout/header.php';
$promociones = $promociones ?? [];
$porVencer = $porVencer ?? [];

/** Enlace natural de una promoción según el artículo asociado. */
$enlacePromo = static function (array $p): string {
    if (!empty($p['id_producto'])) {
        return ruta('producto', ['id' => $p['id_producto']]);
    }
    if (!empty($p['id_servicio'])) {
        return ruta('reserva_nueva', ['servicio' => $p['id_servicio']]);
    }
    return ruta('home');
};
?>

<section class="page-head">
    <div class="container">
        <span class="eyebrow">Ofertas vigentes</span>
        <h1>Promociones</h1>
        <p>Aprovecha nuestros descuentos en tratamientos y productos seleccionados.</p>
    </div>
</section>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <?php if (empty($promociones)): ?>
            <?= vacio('Por ahora no hay promociones vigentes. Vuelve pronto.', 'heart') ?>
        <?php else: ?>
        <div class="grid-3">
            <?php foreach ($promociones as $p):
                $imagen = trim((string)($p['imagen_url'] ?? ''));
                $dias = (int)($p['dias_restantes'] ?? 0);
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
                    <p class="muted" style="font-size:.9rem"><?= e($p['descripcion'] ?? '') ?></p>
                    <div class="producto-foot">
                        <span>
                            <s class="muted" style="font-size:.85rem"><?= bs($p['precio_original']) ?></s>
                            <span class="precio-lg"><?= bs($p['precio_promocional']) ?></span>
                        </span>
                        <a class="btn btn-primary btn-sm" href="<?= e($enlacePromo($p)) ?>">Aprovechar</a>
                    </div>
                    <small class="muted" style="display:block;margin-top:.6rem">
                        <?= icon('clock', 13) ?> Vigente hasta <?= e(date('d/m/Y', strtotime((string)$p['fecha_fin']))) ?>
                        <?= $dias <= 7 ? ' · ¡Últimos ' . $dias . ' día(s)!' : '' ?>
                    </small>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:1rem;justify-content:center;margin-top:2.5rem;flex-wrap:wrap">
            <?= btn_volver('home', [], 'Volver al inicio') ?>
            <a class="btn btn-primary" href="<?= ruta('reserva_nueva') ?>"><?= icon('calendar', 18) ?> Reservar mi cita</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
