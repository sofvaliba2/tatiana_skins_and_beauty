<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validate;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Reporte;
use App\Models\Reserva;
use App\Models\Sucursal;
use App\Models\Usuario;

final class AdminSistemaController extends Controller
{
    public function sucursales(): void
    {
        if ($this->esPost()) {
            if ($this->texto('accion') === 'horario') {
                $this->guardarHorario();
            } else {
                $this->guardarSucursal();
            }
        }

        $idEditar = $this->texto('editar');
        $editar = $idEditar !== '' ? Sucursal::porId($idEditar) : null;
        $sucursales = Sucursal::todas();
        $horarios = [];
        foreach ($sucursales as $s) {
            $horarios[$s['id_sucursal']] = Sucursal::horarios((string)$s['id_sucursal']);
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
            [$id, $creado] = Sucursal::guardar($idEditar !== '' ? $idEditar : null, [
                'nombre_sucursal' => trim((string)$_POST['nombre_sucursal']),
                'direccion'       => trim((string)($_POST['direccion'] ?? '')),
                'telefono'        => trim((string)($_POST['telefono'] ?? '')),
            ]);
            Flash::ok($creado ? 'Sucursal creada: ' . $id . '. Ahora configura su horario.' : 'Sucursal actualizada.');
        } catch (\Throwable $e) {
            error_log('[ADMIN-SUC] ' . $e->getMessage());
            Flash::error('No se pudo guardar la sucursal.');
        }
        $this->redirigir('admin_sucursales');
    }

    private function guardarHorario(): void
    {
        $idSucursal = $this->texto('id_sucursal');
        if (Sucursal::porId($idSucursal) === null) {
            Flash::error('Sucursal no válida.');
            $this->redirigir('admin_sucursales');
        }
        Sucursal::guardarHorario(
            $idSucursal,
            (array)($_POST['dia'] ?? []),
            (array)($_POST['apertura'] ?? []),
            (array)($_POST['cierre'] ?? [])
        );
        Flash::ok('Horario actualizado.');
        $this->redirigir('admin_sucursales');
    }

    public function usuarios(): void
    {
        if ($this->esPost()) {
            $this->guardarUsuario();
        }

        $usuarios = Usuario::personal();
        $sucursales = Sucursal::lista();

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
            $r = Usuario::guardarPersonal(
                $idEditar !== '' ? $idEditar : null,
                [
                    'nombre'      => trim((string)$_POST['nombre']),
                    'ap_paterno'  => trim((string)$_POST['ap_paterno']),
                    'ap_materno'  => trim((string)($_POST['ap_materno'] ?? '')),
                    'telefono'    => trim((string)$_POST['telefono']),
                    'id_rol'      => $idRol,
                    'id_sucursal' => $this->texto('id_sucursal') !== '' ? $this->texto('id_sucursal') : null,
                ],
                $idEditar === '' ? (string)$_POST['correo'] : null,
                $idEditar === '' ? (string)$_POST['password'] : null,
                $this->texto('nueva_password') !== '' ? (string)$_POST['nueva_password'] : null
            );
            Flash::ok($r['creado'] ? 'Usuario del personal registrado: ' . $r['id'] : 'Usuario actualizado.');
            if ($r['passwordCambiada']) {
                Flash::ok('Contraseña restablecida.');
            }
        } catch (\Throwable $e) {
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

        $resumen = Reporte::resumenCompras($desde, $hasta);
        $reservas = Reporte::resumenReservas($desde, $hasta);
        $porProducto = Reporte::porProducto($desde, $hasta);
        $porServicio = Reporte::porServicio($desde, $hasta);
        $topClientes = Reporte::topClientes($desde, $hasta);

        $this->vista('admin/reportes.php', compact(
            'desde', 'hasta', 'resumen', 'reservas', 'porProducto', 'porServicio', 'topClientes'
        ));
    }

    private function exportarCSV(string $tipo, string $desde, string $hasta): void
    {
        $filas = [];
        switch ($tipo) {
            case 'compras':
                $filas[] = ['N°', 'Fecha', 'Cliente', 'Método', 'Total'];
                foreach (Reporte::comprasDetalle($desde, $hasta) as $f) {
                    $filas[] = [$f['nro_compra'], $f['fecha_compra'], $f['cliente'], $f['nombre_metodoPago'], $f['total_pagar']];
                }
                break;
            case 'productos':
                $filas[] = ['Producto', 'Unidades vendidas', 'Total Bs.'];
                foreach (Reporte::porProducto($desde, $hasta, null) as $f) {
                    $filas[] = [$f['nombre_producto'], $f['unidades'], $f['total']];
                }
                break;
            case 'servicios':
                $filas[] = ['Servicio', 'Citas', 'Total Bs.'];
                foreach (Reporte::porServicio($desde, $hasta, null) as $f) {
                    $filas[] = [$f['nombre'], $f['citas'], $f['total']];
                }
                break;
            default:
                Flash::error('Tipo de reporte no válido.');
                $this->redirigir('admin_reportes');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . $desde . '_' . $hasta . '.csv"');
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
        $this->vista('admin/notificaciones.php', [
            'pendientes' => Reserva::pendientesDetalle(),
            'stockBajo'  => Producto::stockBajo(),
            'citasHoy'   => Reserva::resumenDelDia(date('Y-m-d')),
        ]);
    }

    public function perfil(): void
    {
        $id = Auth::id();
        $errores = [];

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
                    Usuario::actualizarPerfilPersonal($id, [
                        'nombre'     => trim((string)$_POST['nombre']),
                        'ap_paterno' => trim((string)$_POST['ap_paterno']),
                        'ap_materno' => trim((string)($_POST['ap_materno'] ?? '')),
                        'telefono'   => trim((string)($_POST['telefono'] ?? '')),
                    ]);
                    Flash::ok('Datos actualizados.');
                    $this->redirigir('admin_perfil');
                }
            } elseif ($accion === 'password') {
                $errores = $this->cambiarPassword($id);
                if ($errores === []) {
                    Flash::ok('Contraseña actualizada.');
                    $this->redirigir('admin_perfil');
                }
            }
        }

        $usuario = Usuario::porId($id);
        $cuenta = ['correo' => Usuario::correoDe($id)];
        $this->vista('admin/perfil.php', compact('usuario', 'cuenta', 'errores'));
    }

    private function cambiarPassword(string $id): array
    {
        $actual = (string)($_POST['password_actual'] ?? '');
        $nueva = (string)($_POST['password_nueva'] ?? '');
        $rep = (string)($_POST['password_rep'] ?? '');

        $v = Validate::make();
        if (!Auth::verificarPassword($actual, Usuario::passwordDe($id), Usuario::correoDe($id))) {
            $v->fallo('password_actual', 'La contraseña actual no es correcta.');
        } elseif (strlen($nueva) < 8) {
            $v->fallo('password_nueva', 'La nueva contraseña debe tener al menos 8 caracteres.');
        } elseif ($nueva !== $rep) {
            $v->fallo('password_rep', 'Las contraseñas no coinciden.');
        } else {
            Usuario::cambiarPassword($id, $nueva);
        }
        return $v->errores();
    }

    public function config(): void
    {
        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'crear') {
                $nombre = $this->texto('nombre_metodoPago');
                if (mb_strlen($nombre) < 2) {
                    Flash::error('El nombre del método de pago debe tener al menos 2 caracteres.');
                } elseif (MetodoPago::existeNombre($nombre)) {
                    Flash::error('Ya existe un método de pago con ese nombre.');
                } else {
                    MetodoPago::crear($nombre, $this->texto('tipo'));
                    Flash::ok('Método de pago creado: ' . $nombre);
                }
            } elseif ($accion === 'estado') {
                $nuevo = $this->texto('nuevo_estado') === 'inactivo' ? 'inactivo' : 'activo';
                MetodoPago::cambiarEstado($this->texto('id_metodoPago'), $nuevo);
                Flash::ok('Estado actualizado.');
            } elseif ($accion === 'eliminar') {
                $id = $this->texto('id_metodoPago');
                $uso = MetodoPago::usos($id);
                if ($uso > 0) {
                    Flash::error('No se puede eliminar: ' . $uso . ' registro(s) lo usan. Desactívalo en su lugar.');
                } else {
                    MetodoPago::eliminar($id);
                    Flash::ok('Método de pago eliminado.');
                }
            }
            $this->redirigir('admin_config');
        }

        $this->vista('admin/config.php', ['metodos' => MetodoPago::conUso()]);
    }
}
