<?php
$titulo = 'Ventas y pagos';
$activo = 'empleado_ventas';
require __DIR__ . '/../layout/empleado_header.php';
$sucursalNombre = (string)($sucursal_actual['nombre_sucursal'] ?? 'tu sucursal');
$clienteSeleccionado = (string)($_GET['cliente'] ?? '');
$hayMetodos = !empty($metodos);
?>

<div class="page-title">
    <div>
        <h1>Ventas y pagos</h1>
        <p class="muted">Venta directa de productos y servicios para las clientas de <?= e($sucursalNombre) ?>.</p>
    </div>
    <span class="muted" style="font-size:.9rem"><?= date('d/m/Y') ?></span>
</div>

<?php if (!$hayMetodos): ?>
    <div class="alert warn" style="margin-bottom:1.2rem">
        No hay métodos de pago activos. Pide al administrador que los configure en <b>Configuración</b> antes de registrar ventas con pago.
    </div>
<?php endif; ?>

<div class="split">
    <section class="panel">
        <div class="panel-head"><h3><?= icon('cart', 18) ?> Registrar venta</h3></div>
        <form method="post" action="<?= ruta('empleado_ventas') ?>" id="sale-form">
            <?= csrf() ?>
            <input type="hidden" name="accion" value="venta">

            <div class="field">
                <label>Clienta *</label>
                <select class="input" name="id_usuario" required>
                    <option value="">Selecciona una clienta...</option>
                    <?php foreach (($clientes ?? []) as $cl): ?>
                        <option value="<?= e($cl['id_usuario']) ?>" <?= $clienteSeleccionado === $cl['id_usuario'] ? 'selected' : '' ?>>
                            <?= e(trim($cl['nombre'] . ' ' . $cl['ap_paterno'])) ?> (<?= e($cl['id_usuario']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sale-head">
                <span>Producto o servicio</span>
                <span>Cant.</span>
                <span>P. unitario</span>
                <span>Subtotal</span>
                <span></span>
            </div>
            <div class="sale-lines" id="sale-lines"></div>
            <button class="btn btn-outline btn-sm" type="button" id="add-line" style="margin-top:.6rem">
                <?= icon('plus', 16) ?> Agregar producto o servicio
            </button>

            <div class="summary-row total" style="margin-top:1.2rem">
                <span>Total de la venta</span>
                <strong id="sale-total">Bs. 0.00</strong>
            </div>

            <div class="field">
                <label>Método de pago <?= $hayMetodos ? '*' : '' ?></label>
                <select class="input" name="id_metodoPago" <?= $hayMetodos ? 'required' : '' ?>>
                    <option value=""><?= $hayMetodos ? 'Selecciona un método...' : 'Por definir' ?></option>
                    <?php foreach (($metodos ?? []) as $m): ?>
                        <option value="<?= e($m['id_metodoPago']) ?>"><?= e($m['nombre_metodoPago']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn btn-primary btn-block" type="submit" style="margin-top:1rem">
                <?= icon('check', 18) ?> Confirmar venta
            </button>
            <p class="muted" style="font-size:.8rem;margin-top:.75rem;text-align:center">
                El stock se descuenta automáticamente. No se permite vender más unidades de las disponibles.
            </p>
        </form>
    </section>

    <aside class="panel">
        <div class="panel-head">
            <h3>Últimas ventas</h3>
            <span class="muted" style="font-size:.82rem">de la sucursal</span>
        </div>
        <?php if (empty($ventas)): ?>
            <?= vacio('Todavía no hay ventas registradas para clientas de esta sucursal.', 'cart') ?>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Venta</th><th>Clienta</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($ventas as $v): ?>
                    <tr>
                        <td>
                            <b><?= e($v['nro_compra'] ?? '#' . $v['id_compra']) ?></b><br>
                            <small class="muted"><?= e($v['fecha_compra'] ? date('d/m/Y', strtotime((string)$v['fecha_compra'])) : 'Sin fecha') ?></small>
                        </td>
                        <td>
                            <?= e($v['cliente'] ?? 'Clienta eliminada') ?><br>
                            <small class="muted"><?= e($v['nombre_metodoPago'] ?? 'Por definir') ?></small>
                        </td>
                        <td><b><?= bs($v['total_pagar']) ?></b></td>
                        <td>
                            <a class="ic-btn" href="<?= ruta('empleado_comprobante', ['id' => $v['id_compra']]) ?>" title="Ver comprobante"><?= icon('eye', 16) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </aside>
</div>

<template id="sale-row-template">
    <div class="sale-row">
        <select class="input sale-item" name="item[]" required>
            <option value="">Selecciona un ítem...</option>
            <optgroup label="Servicios">
                <?php foreach (($servicios ?? []) as $s): ?>
                    <option value="servicio:<?= e($s['id_servicio']) ?>" data-tipo="servicio" data-precio="<?= e((string)$s['precio']) ?>">
                        <?= e($s['nombre']) ?> · <?= bs($s['precio']) ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Productos">
                <?php foreach (($productos ?? []) as $p): ?>
                    <option value="producto:<?= e($p['id_producto']) ?>" data-tipo="producto" data-precio="<?= e((string)$p['precio']) ?>" data-stock="<?= (int)$p['cantidad'] ?>" <?= (int)$p['cantidad'] <= 0 ? 'disabled' : '' ?>>
                        <?= e($p['nombre_producto']) ?> · <?= bs($p['precio']) ?> (stock: <?= (int)$p['cantidad'] ?>)
                    </option>
                <?php endforeach; ?>
            </optgroup>
        </select>
        <input class="input sale-qty" type="number" name="cantidad[]" value="1" min="1" max="999" required>
        <span class="sale-unit">Bs. 0.00</span>
        <span class="sale-sub">Bs. 0.00</span>
        <button class="ic-btn del sale-remove" type="button" title="Quitar línea"><?= icon('trash', 16) ?></button>
    </div>
</template>

<script>
(function () {
    'use strict';
    var contenedor = document.getElementById('sale-lines');
    var plantilla = document.getElementById('sale-row-template');
    var totalEl = document.getElementById('sale-total');
    if (!contenedor || !plantilla || !totalEl) return;

    function recalcular() {
        var total = 0;
        contenedor.querySelectorAll('.sale-row').forEach(function (fila) {
            var select = fila.querySelector('.sale-item');
            var cantidad = fila.querySelector('.sale-qty');
            var opcion = select.options[select.selectedIndex];
            var precio = opcion ? parseFloat(opcion.getAttribute('data-precio') || '0') : 0;
            var tipo = opcion ? opcion.getAttribute('data-tipo') : '';
            var stock = opcion ? parseInt(opcion.getAttribute('data-stock') || '', 10) : NaN;
            var cant = Math.max(1, parseInt(cantidad.value || '1', 10) || 1);

            if (tipo === 'producto' && !isNaN(stock)) {
                cantidad.max = String(Math.max(0, stock));
            } else {
                cantidad.removeAttribute('max');
            }

            var subtotal = precio * cant;
            fila.querySelector('.sale-unit').textContent = 'Bs. ' + precio.toFixed(2);
            fila.querySelector('.sale-sub').textContent = 'Bs. ' + subtotal.toFixed(2);
            total += subtotal;
        });
        totalEl.textContent = 'Bs. ' + total.toFixed(2);
    }

    function agregarLinea() {
        var nodo = plantilla.content.cloneNode(true);
        contenedor.appendChild(nodo);
        recalcular();
    }

    document.getElementById('add-line').addEventListener('click', agregarLinea);
    contenedor.addEventListener('input', recalcular);
    contenedor.addEventListener('change', recalcular);
    contenedor.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('.sale-remove') : null;
        if (!btn) return;
        var filas = contenedor.querySelectorAll('.sale-row');
        if (filas.length > 1) {
            btn.closest('.sale-row').remove();
        } else {
            filas[0].querySelector('.sale-item').value = '';
            filas[0].querySelector('.sale-qty').value = '1';
        }
        recalcular();
    });

    document.getElementById('sale-form').addEventListener('submit', function (ev) {
        var hayItem = false;
        contenedor.querySelectorAll('.sale-item').forEach(function (s) {
            if (s.value !== '') hayItem = true;
        });
        if (!hayItem) {
            ev.preventDefault();
            window.alert('Agrega al menos un producto o servicio a la venta.');
        }
    });

    agregarLinea();
})();
</script>

<?php require __DIR__ . '/../layout/empleado_footer.php'; ?>
