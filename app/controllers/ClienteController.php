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
        if ($compra === null) {
            (new ErroresController())->denegado();
            return;
        }

        $esPropia = (string)($compra['id_usuario'] ?? '') === Auth::id();
        if (!$esPropia && !Auth::esPersonal()) {
            (new ErroresController())->denegado();
            return;
        }

        // Un empleado solo puede ver comprobantes de clientas de su sucursal.
        if (Auth::esEmpleado()) {
            $idSucursal = (string)(Auth::usuario()['sucursal'] ?? '');
            $idCliente = (string)($compra['id_usuario'] ?? '');
            if (
                $idSucursal === '' ||
                $idCliente === '' ||
                Usuario::clienteDeSucursal($idCliente, $idSucursal) === null
            ) {
                (new ErroresController())->denegado();
                return;
            }
        }

        $items = Compra::items((int)$compra['id_compra']);
        $this->vista('cliente/detalle_compra.php', compact('compra', 'items'));
    }

    /**
     * Sirve el comprobante de pago de una compra desde MySQL, con respaldo en
     * el archivo de disco. Mismos permisos que el detalle de la compra.
     */
    public function comprobanteCompra(): void
    {
        $id = $this->entero('id');
        $compra = $id > 0 ? Compra::porId($id) : null;
        if ($compra === null) {
            (new ErroresController())->noEncontrado();
            return;
        }

        $u = Auth::usuario() ?? [];
        $rol = (string)($u['id_rol'] ?? '');
        if ($rol === 'ROL03') {
            if ((string)($compra['id_usuario'] ?? '') !== (string)($u['id'] ?? '')) {
                (new ErroresController())->denegado();
                return;
            }
        } elseif ($rol === 'ROL02') {
            $idSucursal = (string)($u['sucursal'] ?? '');
            $idCliente = (string)($compra['id_usuario'] ?? '');
            $deSucursal = $idSucursal !== '' && (
                (string)($compra['id_sucursal'] ?? '') === $idSucursal
                || ($idCliente !== '' && Usuario::clienteDeSucursal($idCliente, $idSucursal) !== null)
            );
            if (!$deSucursal) {
                (new ErroresController())->denegado();
                return;
            }
        } elseif ($rol !== 'ROL01') {
            (new ErroresController())->denegado();
            return;
        }

        $comprobante = Compra::comprobantePago($id);
        if ($comprobante === null) {
            (new ErroresController())->noEncontrado();
            return;
        }

        $datos = $comprobante['datos'] ?? null;
        if (is_string($datos) && $datos !== '') {
            $tipo = trim((string)($comprobante['tipo'] ?? ''));
            if ($tipo === '' || !str_starts_with($tipo, 'image/')) {
                $tipo = 'application/octet-stream';
            }
            $nombre = trim((string)($comprobante['nombre'] ?? ''));
            if ($nombre === '') {
                $nombre = 'comprobante-compra-' . $id;
            }
            header('Content-Type: ' . $tipo);
            header('Content-Length: ' . strlen($datos));
            header('Content-Disposition: inline; filename="' . rawurlencode($nombre) . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $datos;
            exit;
        }

        $ruta = trim((string)($comprobante['ruta'] ?? ''));
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');
        if (str_starts_with($ruta, 'public/')) {
            $ruta = substr($ruta, 7);
        }
        $absoluta = BASE_PATH . '/public/' . $ruta;
        if ($ruta === '' || !is_file($absoluta)) {
            (new ErroresController())->noEncontrado();
            return;
        }

        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($absoluta) ?: 'application/octet-stream';
        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . (string)filesize($absoluta));
        header('Content-Disposition: inline; filename="' . rawurlencode(basename($absoluta)) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($absoluta);
        exit;
    }
}
