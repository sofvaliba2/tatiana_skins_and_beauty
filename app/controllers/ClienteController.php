<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validate;
use App\Models\Compra;
use App\Models\Historial;
use App\Models\Reserva;
use App\Models\Usuario;

final class ClienteController extends Controller
{
    public function panel(): void
    {
        $id = Auth::id();
        $proximas = Reserva::proximasDeUsuario($id, date('Y-m-d'), 3);
        $compras = Compra::recientesDeUsuario($id, 3);
        $totales = Usuario::resumenCliente($id);
        $historial = ['visitas' => Historial::contarVisitas($id)];
        $this->vista('cliente/panel.php', compact('proximas', 'compras', 'totales', 'historial'));
    }

    public function perfil(): void
    {
        $id = Auth::id();
        $errores = [];

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
                    Usuario::actualizarPerfilCliente($id, [
                        'nombre'           => trim((string)$_POST['nombre']),
                        'ap_paterno'       => trim((string)$_POST['ap_paterno']),
                        'ap_materno'       => trim((string)($_POST['ap_materno'] ?? '')),
                        'telefono'         => trim((string)($_POST['telefono'] ?? '')),
                        'fecha_nacimiento' => ($_POST['fecha_nacimiento'] ?? '') !== '' ? $_POST['fecha_nacimiento'] : null,
                    ]);
                    Flash::ok('Tus datos fueron actualizados.');
                    $this->redirigir('perfil');
                }
            } elseif ($accion === 'password') {
                $errores = $this->cambiarPassword($id);
                if ($errores === []) {
                    Flash::ok('Contraseña actualizada.');
                    $this->redirigir('perfil');
                }
            }
        }

        $usuario = Usuario::porId($id);
        $cuenta = ['correo' => Usuario::correoDe($id)];
        $this->vista('cliente/perfil.php', compact('usuario', 'cuenta', 'errores'));
    }

    private function cambiarPassword(string $id): array
    {
        $actual = (string)($_POST['password_actual'] ?? '');
        $nueva = (string)($_POST['password_nueva'] ?? '');
        $rep = (string)($_POST['password_rep'] ?? '');
        $correo = Usuario::correoDe($id);

        $v = Validate::make();
        if (!Auth::verificarPassword($actual, Usuario::passwordDe($id), $correo)) {
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

    public function compras(): void
    {
        $pagina = max(1, $this->entero('p', 1));
        $porPagina = 10;
        ['filas' => $compras, 'total' => $total] = Compra::deUsuario(Auth::id(), $pagina, $porPagina);
        $this->vista('cliente/compras.php', compact('compras', 'total', 'pagina', 'porPagina'));
    }

    public function detalleCompra(): void
    {
        $compra = Compra::porId($this->entero('id'));
        if ($compra === null || ($compra['id_usuario'] !== Auth::id() && !Auth::esPersonal())) {
            (new ErroresController())->denegado();
            return;
        }
        $items = Compra::items((int)$compra['id_compra']);
        $this->vista('cliente/detalle_compra.php', compact('compra', 'items'));
    }
}
