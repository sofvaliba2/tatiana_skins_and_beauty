<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Validate;

final class Cliente extends Controller
{
    public function panel(): void
    {
        $id = Auth::id();
        $proximas = Database::all(
            'SELECT r.*, s.nombre_sucursal,
                    GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                    COALESCE(SUM(dr.subtotal), 0) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
             WHERE r.id_usuario = ? AND r.fecha >= CURDATE() AND r.estado <> "cancelada"
             GROUP BY r.id_reserva
             ORDER BY r.fecha, r.hora
             LIMIT 3',
            [$id]
        );
        $compras = Database::all(
            'SELECT * FROM compra WHERE id_usuario = ? ORDER BY fecha_compra DESC, id_compra DESC LIMIT 3',
            [$id]
        );
        $totales = Database::one(
            'SELECT COUNT(*) AS reservas,
                    (SELECT COUNT(*) FROM compra WHERE id_usuario = ?) AS compras,
                    (SELECT COALESCE(SUM(total_pagar),0) FROM compra WHERE id_usuario = ?) AS gastado',
            [$id, $id]
        );
        $historial = Database::one(
            'SELECT COUNT(*) AS visitas FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             WHERE r.id_usuario = ?',
            [$id]
        );
        $this->vista('cliente/panel.php', compact('proximas', 'compras', 'totales', 'historial'));
    }

    public function perfil(): void
    {
        $id = Auth::id();
        $errores = [];
        $usuario = Database::one('SELECT * FROM usuario WHERE id_usuario = ?', [$id]);
        $cuenta = Database::one('SELECT correo FROM cuenta_usuario WHERE id_usuario = ?', [$id]);

        if ($this->esPost()) {
            $accion = $this->texto('accion', 'datos');
            if ($accion === 'datos') {
                $v = Validate::make()
                    ->texto('nombre', 'El nombre', 2, 50)
                    ->texto('ap_paterno', 'El apellido paterno', 2, 50)
                    ->telefono('telefono')
                    ->fecha('fecha_nacimiento', 'de nacimiento');
                if ($v->fallido()) {
                    $errores = $v->errores();
                } else {
                    Database::run(
                        'UPDATE usuario SET nombre = ?, ap_paterno = ?, ap_materno = ?, telefono = ?, fecha_nacimiento = ? WHERE id_usuario = ?',
                        [
                            trim((string)$_POST['nombre']),
                            trim((string)$_POST['ap_paterno']),
                            trim((string)($_POST['ap_materno'] ?? '')),
                            trim((string)($_POST['telefono'] ?? '')),
                            ($_POST['fecha_nacimiento'] ?? '') !== '' ? $_POST['fecha_nacimiento'] : null,
                            $id,
                        ]
                    );
                    Flash::ok('Tus datos fueron actualizados.');
                    $this->redirigir('perfil');
                }
            } elseif ($accion === 'password') {
                $actual = (string)($_POST['password_actual'] ?? '');
                $nueva = (string)($_POST['password_nueva'] ?? '');
                $rep = (string)($_POST['password_rep'] ?? '');
                $correo = (string)($cuenta['correo'] ?? '');
                if (!Auth::verificarPassword($actual, (string)Database::value('SELECT password FROM cuenta_usuario WHERE id_usuario = ?', [$id]), $correo)) {
                    $v = Validate::make();
                    $v->fallo('password_actual', 'La contraseña actual no es correcta.');
                    $errores = $v->errores();
                } elseif (strlen($nueva) < 8) {
                    $v = Validate::make();
                    $v->fallo('password_nueva', 'La nueva contraseña debe tener al menos 8 caracteres.');
                    $errores = $v->errores();
                } elseif ($nueva !== $rep) {
                    $v = Validate::make();
                    $v->fallo('password_rep', 'Las contraseñas no coinciden.');
                    $errores = $v->errores();
                } else {
                    Database::run('UPDATE cuenta_usuario SET password = ? WHERE id_usuario = ?', [Auth::hashPassword($nueva, $correo), $id]);
                    Flash::ok('Contraseña actualizada.');
                    $this->redirigir('perfil');
                }
            }
        }
        $this->vista('cliente/perfil.php', compact('usuario', 'cuenta', 'errores'));
    }

    public function compras(): void
    {
        $pagina = max(1, $this->entero('p', 1));
        $porPagina = 10;
        $id = Auth::id();
        $total = (int)Database::value('SELECT COUNT(*) FROM compra WHERE id_usuario = ?', [$id]);
        $compras = Database::all(
            'SELECT c.*, m.nombre_metodoPago,
                    (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items
             FROM compra c
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             WHERE c.id_usuario = ?
             ORDER BY c.fecha_compra DESC, c.id_compra DESC
             LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina),
            [$id]
        );
        $this->vista('cliente/compras.php', compact('compras', 'total', 'pagina', 'porPagina'));
    }

    public function detalleCompra(): void
    {
        $id = $this->entero('id');
        $compra = Database::one(
            'SELECT c.*, m.nombre_metodoPago, u.nombre, u.ap_paterno, u.ap_materno
             FROM compra c
             LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             WHERE c.id_compra = ?',
            [$id]
        );
        if ($compra === null || ($compra['id_usuario'] !== Auth::id() && !\App\Core\Auth::esPersonal())) {
            http_response_code(403);
            (new Errores())->denegado();
            return;
        }
        $items = Database::all(
            'SELECT d.*, p.nombre_producto, p.imagen_url, p.id_categoria
             FROM detalle_compra d
             JOIN producto p ON p.id_producto = d.id_producto
             WHERE d.id_compra = ?',
            [$id]
        );
        $this->vista('cliente/detalle_compra.php', compact('compra', 'items'));
    }
}
