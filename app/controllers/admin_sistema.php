<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Ids;
use App\Core\Validate;

final class AdminSistema extends Controller
{
    private const DIAS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

    
    public function sucursales(): void
    {
        $editar = null;
        $idEditar = $this->texto('editar');
        if ($idEditar !== '') {
            $editar = Database::one('SELECT * FROM sucursal WHERE id_sucursal = ?', [$idEditar]);
        }

        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'horario') {
                $this->guardarHorario();
            } else {
                $this->guardarSucursal();
            }
        }

        $sucursales = Database::all('SELECT * FROM sucursal ORDER BY nombre_sucursal');
        $horarios = [];
        foreach ($sucursales as $s) {
            $horarios[$s['id_sucursal']] = Database::all(
                'SELECT * FROM horario_sucursal WHERE id_sucursal = ? ORDER BY FIELD(dia_semana, "Lunes","Martes","Miercoles","Jueves","Viernes","Sabado","Domingo")',
                [$s['id_sucursal']]
            );
        }

        $this->vista('admin/sucursales.php', compact('sucursales', 'horarios', 'editar'));
    }

    private function guardarSucursal(): void
    {
        $idEditar = $this->texto('id_sucursal');
        $v = Validate::make()->texto('nombre_sucursal', 'El nombre', 2, 50);
        if ($this->texto('direccion') !== '' && mb_strlen($this->texto('direccion')) > 150) {
            $v->fallo('direccion', 'La dirección no puede superar 150 caracteres.');
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_sucursales');
        }

        try {
            $datos = [
                'nombre_sucursal' => trim((string)$_POST['nombre_sucursal']),
                'direccion'       => trim((string)($_POST['direccion'] ?? '')),
                'telefono'        => trim((string)($_POST['telefono'] ?? '')),
            ];
            if ($idEditar !== '') {
                $sets = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($datos)));
                Database::run("UPDATE sucursal SET $sets WHERE id_sucursal = ?", [...array_values($datos), $idEditar]);
                Flash::ok('Sucursal actualizada.');
            } else {
                $id = Ids::insertarConCodigo('sucursal', 'id_sucursal', 'SUC', 2, $datos);
                foreach (self::DIAS as $dia) {
                    Ids::insertarConEntero('horario_sucursal', 'id_horario', [
                        'dia_semana'    => $dia,
                        'hora_apertura' => null,
                        'hora_cierre'   => null,
                        'es_activo'     => 0,
                        'id_sucursal'   => $id,
                    ]);
                }
                Flash::ok('Sucursal creada: ' . $id . '. Ahora configura su horario.');
            }
        } catch (\Throwable $e) {
            error_log('[ADMIN-SUC] ' . $e->getMessage());
            Flash::error('No se pudo guardar la sucursal.');
        }
        $this->redirigir('admin_sucursales');
    }

    private function guardarHorario(): void
    {
        $idSucursal = $this->texto('id_sucursal');
        if (Database::one('SELECT id_sucursal FROM sucursal WHERE id_sucursal = ?', [$idSucursal]) === null) {
            Flash::error('Sucursal no válida.');
            $this->redirigir('admin_sucursales');
        }
        $dias = (array)($_POST['dia'] ?? []);
        $aperturas = (array)($_POST['apertura'] ?? []);
        $cierres = (array)($_POST['cierre'] ?? []);

        foreach (self::DIAS as $dia) {
            $activo = isset($dias[$dia]) ? 1 : 0;
            $ap = $aperturas[$dia] ?? '';
            $ci = $cierres[$dia] ?? '';
            if ($activo && ($ap === '' || $ci === '')) {
                continue;
            }
            $existente = Database::one('SELECT id_horario FROM horario_sucursal WHERE id_sucursal = ? AND dia_semana = ?', [$idSucursal, $dia]);
            if ($existente !== null) {
                Database::run(
                    'UPDATE horario_sucursal SET hora_apertura = ?, hora_cierre = ?, es_activo = ? WHERE id_horario = ?',
                    [$activo ? $ap . ':00' : null, $activo ? $ci . ':00' : null, $activo, $existente['id_horario']]
                );
            } elseif ($activo) {
                Ids::insertarConEntero('horario_sucursal', 'id_horario', [
                    'dia_semana'    => $dia,
                    'hora_apertura' => $ap . ':00',
                    'hora_cierre'   => $ci . ':00',
                    'es_activo'     => 1,
                    'id_sucursal'   => $idSucursal,
                ]);
            }
        }
        Flash::ok('Horario actualizado.');
        $this->redirigir('admin_sucursales');
    }

    public function usuarios(): void
    {
        if ($this->esPost()) {
            $this->guardarUsuario();
        }

        $usuarios = Database::all(
            'SELECT u.*, c.correo, r.nombre_rol, s.nombre_sucursal
             FROM usuario u
             JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
             JOIN rol r ON r.id_rol = u.id_rol
             LEFT JOIN sucursal s ON s.id_sucursal = u.id_sucursal
             WHERE u.id_rol IN ("ROL01","ROL02")
             ORDER BY u.nombre'
        );
        $sucursales = Database::all('SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal');

        $this->vista('admin/usuarios.php', compact('usuarios', 'sucursales'));
    }

    private function guardarUsuario(): void
    {
        $idEditar = $this->texto('id_usuario');
        $idRol = $this->texto('id_rol');
        $v = Validate::make()
            ->texto('nombre', 'El nombre', 2, 50)
            ->texto('ap_paterno', 'El apellido paterno', 2, 50)
            ->telefono('telefono');
        if (!in_array($idRol, ['ROL01', 'ROL02'], true)) {
            $v->fallo('id_rol', 'Selecciona un rol válido (administrador o personal).');
        }
        if ($idEditar === '') {
            $v->correo('correo')->unico('cuenta_usuario', 'correo', 'correo', 'correo electrónico');
            if (strlen((string)($_POST['password'] ?? '')) < 8) {
                $v->fallo('password', 'La contraseña debe tener al menos 8 caracteres.');
            }
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_usuarios');
        }

        try {
            Database::begin();
            $datos = [
                'nombre'     => trim((string)$_POST['nombre']),
                'ap_paterno' => trim((string)$_POST['ap_paterno']),
                'ap_materno' => trim((string)($_POST['ap_materno'] ?? '')),
                'telefono'   => trim((string)$_POST['telefono']),
                'id_rol'     => $idRol,
                'id_sucursal' => $this->texto('id_sucursal') !== '' ? $this->texto('id_sucursal') : null,
            ];
            if ($idEditar !== '') {
                $sets = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($datos)));
                Database::run("UPDATE usuario SET $sets WHERE id_usuario = ?", [...array_values($datos), $idEditar]);
                Database::run('UPDATE cuenta_usuario SET id_rol = ? WHERE id_usuario = ?', [$idRol, $idEditar]);
                Flash::ok('Usuario actualizado.');
            } else {
                $correo = mb_strtolower(trim((string)$_POST['correo']));
                $id = Ids::insertarConCodigo('usuario', 'id_usuario', 'EMP', 3, $datos);
                Ids::insertarConEntero('cuenta_usuario', 'id_cuentaUsuario', [
                    'correo'     => $correo,
                    'password'   => Auth::hashPassword((string)$_POST['password'], $correo),
                    'id_rol'     => $idRol,
                    'id_usuario' => $id,
                ]);
                Flash::ok('Usuario del personal registrado: ' . $id);
            }

            if ($this->texto('nueva_password') !== '') {
                $nueva = (string)$_POST['nueva_password'];
                if (strlen($nueva) >= 8) {
                    $correoCuenta = $idEditar !== ''
                        ? (string)Database::value('SELECT correo FROM cuenta_usuario WHERE id_usuario = ?', [$idEditar])
                        : $correo;
                    Database::run('UPDATE cuenta_usuario SET password = ? WHERE id_usuario = ?', [Auth::hashPassword($nueva, $correoCuenta), $idEditar !== '' ? $idEditar : $id]);
                    Flash::ok('Contraseña restablecida.');
                }
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[ADMIN-USU] ' . $e->getMessage());
            Flash::error('No se pudo guardar el usuario.');
        }
        $this->redirigir('admin_usuarios');
    }


    public function reportes(): void
    {
        $desde = $this->texto('desde', date('Y-m-01'));
        $hasta = $this->texto('hasta', date('Y-m-d'));

        if (($export = $this->texto('export')) !== '') {
            $this->exportarCSV($export, $desde, $hasta);
        }

        $rango = ' AND fecha_compra BETWEEN ? AND ?';
        $params = [$desde, $hasta];

        $resumen = Database::one(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto FROM compra WHERE 1' . $rango,
            $params
        );
        $reservas = Database::one(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'atendida') AS atendidas,
                    SUM(estado = 'cancelada') AS canceladas
             FROM reserva WHERE fecha BETWEEN ? AND ?",
            $params
        );

        $porProducto = Database::all(
            'SELECT p.nombre_producto, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS total
             FROM detalle_compra d
             JOIN producto p ON p.id_producto = d.id_producto
             JOIN compra c ON c.id_compra = d.id_compra
             WHERE c.fecha_compra BETWEEN ? AND ?
             GROUP BY d.id_producto
             ORDER BY total DESC LIMIT 10',
            $params
        );

        $porServicio = Database::all(
            'SELECT s.nombre, COUNT(*) AS citas, SUM(dr.subtotal) AS total
             FROM detalle_reserva dr
             JOIN servicio s ON s.id_servicio = dr.id_servicio
             JOIN reserva r ON r.id_reserva = dr.id_reserva
             WHERE r.fecha BETWEEN ? AND ? AND r.estado <> "cancelada"
             GROUP BY dr.id_servicio
             ORDER BY citas DESC LIMIT 10',
            $params
        );

        $topClientes = Database::all(
            'SELECT CONCAT(u.nombre, " ", u.ap_paterno) AS cliente,
                    COUNT(DISTINCT c.id_compra) AS compras,
                    COUNT(DISTINCT r.id_reserva) AS reservas,
                    COALESCE((SELECT SUM(total_pagar) FROM compra c2 WHERE c2.id_usuario = u.id_usuario), 0) AS gastado
             FROM usuario u
             LEFT JOIN compra c ON c.id_usuario = u.id_usuario AND c.fecha_compra BETWEEN ? AND ?
             LEFT JOIN reserva r ON r.id_usuario = u.id_usuario AND r.fecha BETWEEN ? AND ?
             WHERE u.id_rol = "ROL03"
             GROUP BY u.id_usuario
             HAVING compras > 0 OR reservas > 0
             ORDER BY gastado DESC, reservas DESC
             LIMIT 10',
            [$desde, $hasta, $desde, $hasta]
        );

        $this->vista('admin/reportes.php', compact(
            'desde', 'hasta', 'resumen', 'reservas', 'porProducto', 'porServicio', 'topClientes'
        ));
    }

    private function exportarCSV(string $tipo, string $desde, string $hasta): void
    {
        $filas = [];
        $nombre = 'reporte_' . $tipo;
        switch ($tipo) {
            case 'compras':
                $filas[] = ['N°', 'Fecha', 'Cliente', 'Método', 'Total'];
                foreach (Database::all(
                    'SELECT c.nro_compra, c.fecha_compra, CONCAT(u.nombre," ",u.ap_paterno) AS cliente, m.nombre_metodoPago, c.total_pagar
                     FROM compra c
                     LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
                     LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
                     WHERE c.fecha_compra BETWEEN ? AND ? ORDER BY c.fecha_compra',
                    [$desde, $hasta]
                ) as $f) {
                    $filas[] = [$f['nro_compra'], $f['fecha_compra'], $f['cliente'], $f['nombre_metodoPago'], $f['total_pagar']];
                }
                break;
            case 'productos':
                $filas[] = ['Producto', 'Unidades vendidas', 'Total Bs.'];
                foreach (Database::all(
                    'SELECT p.nombre_producto, SUM(d.cantidad) AS u, SUM(d.subtotal) AS t
                     FROM detalle_compra d JOIN producto p ON p.id_producto = d.id_producto
                     JOIN compra c ON c.id_compra = d.id_compra
                     WHERE c.fecha_compra BETWEEN ? AND ? GROUP BY d.id_producto ORDER BY t DESC',
                    [$desde, $hasta]
                ) as $f) {
                    $filas[] = [$f['nombre_producto'], $f['u'], $f['t']];
                }
                break;
            case 'servicios':
                $filas[] = ['Servicio', 'Citas', 'Total Bs.'];
                foreach (Database::all(
                    'SELECT s.nombre, COUNT(*) AS citas, SUM(dr.subtotal) AS t
                     FROM detalle_reserva dr JOIN servicio s ON s.id_servicio = dr.id_servicio
                     JOIN reserva r ON r.id_reserva = dr.id_reserva
                     WHERE r.fecha BETWEEN ? AND ? AND r.estado <> "cancelada"
                     GROUP BY dr.id_servicio ORDER BY citas DESC',
                    [$desde, $hasta]
                ) as $f) {
                    $filas[] = [$f['nombre'], $f['citas'], $f['t']];
                }
                break;
            default:
                Flash::error('Tipo de reporte no válido.');
                $this->redirigir('admin_reportes');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '_' . $desde . '_' . $hasta . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($filas as $fila) {
            fputcsv($out, $fila, ';');
        }
        fclose($out);
        exit;
    }

    public function notificaciones(): void
    {
        $pendientes = Database::all(
            "SELECT r.id_reserva, r.fecha, r.hora, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente, u.telefono,
                    s.nombre_sucursal, m.nombre_metodoPago
             FROM reserva r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.estado = 'pendiente'
             ORDER BY r.fecha, r.hora"
        );
        $stockBajo = Database::all(
            'SELECT p.*, c.nombre_categoria FROM producto p
             LEFT JOIN categoria_producto c ON c.id_categoria = p.id_categoria
             WHERE p.cantidad <= 3 ORDER BY p.cantidad ASC'
        );
        $citasHoy = Database::all(
            "SELECT r.id_reserva, r.hora, r.estado, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente
             FROM reserva r JOIN usuario u ON u.id_usuario = r.id_usuario
             WHERE r.fecha = CURDATE() AND r.estado <> 'cancelada'
             ORDER BY r.hora"
        );

        $this->vista('admin/notificaciones.php', compact('pendientes', 'stockBajo', 'citasHoy'));
    }

    public function perfil(): void
    {
        $id = Auth::id();
        $errores = [];
        $usuario = Database::one('SELECT * FROM usuario WHERE id_usuario = ?', [$id]);
        $cuenta = Database::one('SELECT correo FROM cuenta_usuario WHERE id_usuario = ?', [$id]);

        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'datos') {
                $v = Validate::make()
                    ->texto('nombre', 'El nombre', 2, 50)
                    ->texto('ap_paterno', 'El apellido paterno', 2, 50)
                    ->telefono('telefono');
                if ($v->fallido()) {
                    $errores = $v->errores();
                } else {
                    Database::run(
                        'UPDATE usuario SET nombre = ?, ap_paterno = ?, ap_materno = ?, telefono = ? WHERE id_usuario = ?',
                        [
                            trim((string)$_POST['nombre']),
                            trim((string)$_POST['ap_paterno']),
                            trim((string)($_POST['ap_materno'] ?? '')),
                            trim((string)($_POST['telefono'] ?? '')),
                            $id,
                        ]
                    );
                    Flash::ok('Datos actualizados.');
                    $this->redirigir('admin_perfil');
                }
            } elseif ($accion === 'password') {
                $actual = (string)($_POST['password_actual'] ?? '');
                $nueva = (string)($_POST['password_nueva'] ?? '');
                $rep = (string)($_POST['password_rep'] ?? '');
                $correo = (string)($cuenta['correo'] ?? '');
                $hashActual = (string)Database::value('SELECT password FROM cuenta_usuario WHERE id_usuario = ?', [$id]);
                if (!Auth::verificarPassword($actual, $hashActual, $correo)) {
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
                    $this->redirigir('admin_perfil');
                }
            }
        }

        $this->vista('admin/perfil.php', compact('usuario', 'cuenta', 'errores'));
    }

    public function config(): void
    {
        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'crear') {
                $nombre = $this->texto('nombre_metodoPago');
                $tipo = $this->texto('tipo');
                if (mb_strlen($nombre) < 2) {
                    Flash::error('El nombre del método de pago debe tener al menos 2 caracteres.');
                } elseif (Database::one('SELECT id_metodoPago FROM metodo_pago WHERE nombre_metodoPago = ?', [$nombre]) !== null) {
                    Flash::error('Ya existe un método de pago con ese nombre.');
                } else {
                    Ids::insertarConCodigo('metodo_pago', 'id_metodoPago', 'MP', 2, [
                        'nombre_metodoPago' => $nombre,
                        'tipo'              => $tipo !== '' ? $tipo : 'general',
                        'estado'            => 'activo',
                    ]);
                    Flash::ok('Método de pago creado: ' . $nombre);
                }
            } elseif ($accion === 'estado') {
                $id = $this->texto('id_metodoPago');
                $nuevo = $this->texto('nuevo_estado') === 'inactivo' ? 'inactivo' : 'activo';
                Database::run('UPDATE metodo_pago SET estado = ? WHERE id_metodoPago = ?', [$nuevo, $id]);
                Flash::ok('Estado actualizado.');
            } elseif ($accion === 'eliminar') {
                $id = $this->texto('id_metodoPago');
                $uso = (int)Database::value('SELECT COUNT(*) FROM compra WHERE id_metodoPago = ?', [$id])
                     + (int)Database::value('SELECT COUNT(*) FROM reserva WHERE id_metodoPago = ?', [$id]);
                if ($uso > 0) {
                    Flash::error('No se puede eliminar: ' . $uso . ' registro(s) lo usan. Desactívalo en su lugar.');
                } else {
                    Database::run('DELETE FROM metodo_pago WHERE id_metodoPago = ?', [$id]);
                    Flash::ok('Método de pago eliminado.');
                }
            }
            $this->redirigir('admin_config');
        }

        $metodos = Database::all(
            'SELECT m.*,
                    (SELECT COUNT(*) FROM compra c WHERE c.id_metodoPago = m.id_metodoPago) AS compras,
                    (SELECT COUNT(*) FROM reserva r WHERE r.id_metodoPago = m.id_metodoPago) AS reservas
             FROM metodo_pago m ORDER BY m.nombre_metodoPago'
        );
        $this->vista('admin/config.php', compact('metodos'));
    }
}
