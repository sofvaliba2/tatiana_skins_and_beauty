<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validate;
use App\Models\Usuario;

final class AuthController extends Controller
{
    public function login(): void
    {
        $errores = [];
        $next = $this->texto('next');

        if ($this->esPost()) {
            $v = Validate::make()->correo('correo');
            if (($_POST['password'] ?? '') === '') {
                $v->fallo('password', 'La contraseña es obligatoria.');
            }
            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                $ok = Auth::intentar((string)$_POST['correo'], (string)$_POST['password']);
                if (!$ok) {
                    $errores['_general'][] = 'Correo o contraseña incorrectos.';
                } else {
                    $u = Auth::usuario();
                    Flash::ok('Bienvenido(a), ' . $u['nombre'] . '.');
                    $porDefecto = match ($u['id_rol']) {
                        'ROL01' => 'admin_dashboard',
                        'ROL02' => 'empleado_dashboard',
                        'ROL03' => 'panel',
                        default => 'home',
                    };
                    $permitidas = $this->destinosPermitidos((string)$u['id_rol']);
                    $destino = ($next !== '' && in_array($next, $permitidas, true)) ? $next : $porDefecto;
                    $this->redirigir($destino);
                }
            }
        }
        $this->vista('auth/login.php', ['errores' => $errores, 'next' => $next]);
    }

    public function registro(): void
    {
        $errores = [];
        if ($this->esPost()) {
            $v = Validate::make()
                ->texto('nombre', 'El nombre', 2, 50)
                ->texto('ap_paterno', 'El apellido paterno', 2, 50)
                ->telefono('telefono')
                ->correo('correo')
                ->fecha('fecha_nacimiento', 'de nacimiento')
                ->unico('cuenta_usuario', 'correo', 'correo', 'correo electrónico');

            if (empty($_POST['telefono']) || trim((string)$_POST['telefono']) === '') {
                $v->fallo('telefono', 'El teléfono es obligatorio.');
            }
            $pass = (string)($_POST['password'] ?? '');
            $pass2 = (string)($_POST['password2'] ?? '');
            if (strlen($pass) < 8) {
                $v->fallo('password', 'La contraseña debe tener al menos 8 caracteres.');
            } elseif ($pass !== $pass2) {
                $v->fallo('password2', 'Las contraseñas no coinciden.');
            }

            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                try {
                    $correo = mb_strtolower(trim((string)$_POST['correo']));
                    Usuario::crearCliente([
                        'nombre'           => trim((string)$_POST['nombre']),
                        'ap_paterno'       => trim((string)$_POST['ap_paterno']),
                        'ap_materno'       => trim((string)($_POST['ap_materno'] ?? '')),
                        'telefono'         => trim((string)$_POST['telefono']),
                        'fecha_nacimiento' => ($_POST['fecha_nacimiento'] ?? '') !== '' ? $_POST['fecha_nacimiento'] : null,
                    ], $correo, $pass);
                } catch (\Throwable $e) {
                    error_log('[REGISTRO] ' . $e->getMessage());
                    $errores['_general'][] = 'No se pudo crear la cuenta. Intenta nuevamente.';
                }

                if (!$errores) {
                    Auth::intentar((string)$_POST['correo'], $pass);
                    Flash::ok('Tu cuenta fue creada. ¡Bienvenida!');
                    $this->redirigir('panel');
                }
            }
        }
        $this->vista('auth/registro.php', ['errores' => $errores]);
    }

    public function recuperar(): void
    {
        $errores = [];
        $enviado = false;
        if ($this->esPost()) {
            $v = Validate::make()->correo('correo');
            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                $enviado = true;
                Flash::ok('Si el correo existe, el personal del centro te ayudará a restablecer tu contraseña.');
            }
        }
        $this->vista('auth/recuperar.php', ['errores' => $errores, 'enviado' => $enviado]);
    }

    /** Destinos internos válidos para cada rol tras iniciar sesión. */
    private function destinosPermitidos(string $rol): array
    {
        $publicas = ['home', 'servicios', 'catalogo', 'producto', 'ayuda'];
        return match ($rol) {
            'ROL01' => [...$publicas, 'detalle_compra',
                'admin_dashboard', 'admin_clientes', 'admin_citas', 'admin_inventario', 'admin_ventas',
                'admin_servicios', 'admin_sucursales', 'admin_usuarios', 'admin_reportes',
                'admin_notificaciones', 'admin_perfil', 'admin_config'],
            'ROL02' => [...$publicas, 'detalle_compra',
                'empleado_dashboard', 'empleado_clientes', 'empleado_citas', 'empleado_historial',
                'empleado_ventas', 'empleado_comprobante', 'empleado_perfil'],
            'ROL03' => [...$publicas, 'panel', 'perfil', 'mis_compras', 'detalle_compra',
                'reserva_nueva', 'reserva_pago', 'reserva_formulario', 'reserva_seguimiento',
                'reserva_recibo', 'mis_reservas', 'recomendaciones', 'carrito', 'checkout'],
            default => ['home'],
        };
    }

    public function logout(): void
    {
        Auth::salir();
        session_start();
        Flash::ok('Sesión cerrada correctamente.');
        $this->redirigir('home');
    }
}
