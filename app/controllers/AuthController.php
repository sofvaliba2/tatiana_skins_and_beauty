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
                    $destino = $next !== '' ? $next : ($u['id_rol'] === 'ROL03' ? 'panel' : 'admin_dashboard');
                    $permitidas = ['panel', 'catalogo', 'servicios', 'mis_reservas', 'carrito', 'reserva_nueva',
                        'admin_dashboard', 'admin_clientes', 'admin_citas', 'admin_ventas', 'admin_inventario'];
                    $this->redirigir(in_array($destino, $permitidas, true) ? $destino : 'home');
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
                ->nombrePersona('nombre', 'El nombre', 2, 50)
                ->nombrePersona('ap_paterno', 'El apellido paterno', 2, 50)
                ->nombrePersona('ap_materno', 'El apellido materno', 2, 50, false)
                ->telefonoBolivia('telefono')
                ->correo('correo')
                ->fechaNacimiento('fecha_nacimiento', true, 18)
                ->passwordSegura('password')
                ->coincide('password2', 'password', 'Las contraseñas no coinciden.')
                ->unico('cuenta_usuario', 'correo', 'correo', 'correo electrónico');

            $pass = (string)($_POST['password'] ?? '');

            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                try {
                    $correo = mb_strtolower(trim((string)$_POST['correo']));
                    $limpiar = static fn (string $c): string => preg_replace('/\s+/u', ' ', trim((string)($_POST[$c] ?? ''))) ?? '';
                    Usuario::crearCliente([
                        'nombre'           => $limpiar('nombre'),
                        'ap_paterno'       => $limpiar('ap_paterno'),
                        'ap_materno'       => $limpiar('ap_materno'),
                        'telefono'         => Validate::normalizarTelefono((string)$_POST['telefono']),
                        'fecha_nacimiento' => (string)$_POST['fecha_nacimiento'],
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

    public function logout(): void
    {
        Auth::salir();
        session_start();
        Flash::ok('Sesión cerrada correctamente.');
        $this->redirigir('home');
    }
}
