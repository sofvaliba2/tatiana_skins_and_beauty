<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Correo;
use App\Core\Flash;
use App\Core\Validate;
use App\Models\RecuperacionPassword;
use App\Models\SeguridadLogin;
use App\Models\Usuario;

final class AuthController extends Controller
{
    public function login(): void
    {
        $errores = [];
        $next = $this->texto('next');
        $correo = mb_strtolower(trim((string)$_POST['correo'] ?? ''));
        $ip = SeguridadLogin::ip();
        $estado = $correo !== '' ? SeguridadLogin::estado($correo, $ip) : ['bloqueado' => false];

        if ($this->esPost()) {
            $v = Validate::make()->correo('correo');
            if (($_POST['password'] ?? '') === '') {
                $v->fallo('password', 'La contraseña es obligatoria.');
            }
            if ($v->fallido()) {
                $errores = $v->errores();
            } elseif (!empty($estado['bloqueado'])) {
                // El bloqueo se comprueba en servidor antes de validar la contraseña.
                $errores['_general'][] = SeguridadLogin::mensajeBloqueo((int)$estado['segundos']);
            } else {
                $ok = Auth::intentar($correo, (string)$_POST['password']);
                if (!$ok) {
                    $estado = SeguridadLogin::registrarFallo($correo, $ip);
                    $errores['_general'][] = !empty($estado['bloqueado'])
                        ? SeguridadLogin::mensajeBloqueo((int)$estado['segundos'])
                        : 'Correo o contraseña incorrectos. Intento ' . (int)$estado['intentos']
                            . ' de ' . SeguridadLogin::MAX_INTENTOS . '.';
                } else {
                    // Inicio de sesión correcto: se borra por completo el registro.
                    SeguridadLogin::reiniciar($correo, $ip);
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
                ->nombrePersona('nombre', 'El nombre', 2, 50)
                ->nombrePersona('ap_paterno', 'El apellido paterno', 2, 50)
                ->nombrePersona('ap_materno', 'El apellido materno', 2, 50)
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
        $devLink = '';
        if ($this->esPost()) {
            $v = Validate::make()->correo('correo');
            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                $enviado = true;
                RecuperacionPassword::limpiar();
                $solicitud = RecuperacionPassword::crear((string)$_POST['correo']);
                if ($solicitud !== null) {
                    $enlace = Correo::enlaceRestablecer($solicitud['token']);
                    $cuenta = Usuario::cuentaPorCorreo($solicitud['correo']);
                    $nombre = (string)($cuenta['nombre'] ?? '');
                    $html = Correo::plantillaRecuperacion($nombre, $enlace);
                    if (!Correo::enviar($solicitud['correo'], 'Restablece tu contraseña', $html)) {
                        error_log('[RECUPERACION] No se pudo enviar el correo a ' . $solicitud['correo']);
                    }
                    // En desarrollo se muestra el enlace para poder probar sin SMTP.
                    if (MAIL_DEV_LINK) {
                        $devLink = $enlace;
                    }
                }
                // Mensaje genérico: nunca revela si el correo está registrado.
                Flash::ok('Si el correo está registrado, enviamos un enlace para restablecer tu contraseña. '
                    . 'Revisa tu bandeja de entrada y spam. El enlace vence en '
                    . RecuperacionPassword::MINUTOS_VIGENCIA . ' minutos.');
            }
        }
        $this->vista('auth/recuperar.php', [
            'errores' => $errores,
            'enviado' => $enviado,
            'devLink' => $devLink,
        ]);
    }

    /** Pantalla y proceso para establecer una nueva contraseña con el token del correo. */
    public function restablecer(): void
    {
        $token = $this->texto('token', (string)($_POST['token'] ?? ''));
        $registro = RecuperacionPassword::validar($token);
        $errores = [];

        if ($registro === null) {
            $this->vista('auth/restablecer.php', [
                'errores' => [],
                'invalido' => true,
                'token' => '',
            ]);
            return;
        }

        if ($this->esPost()) {
            $v = Validate::make()
                ->passwordSegura('password')
                ->coincide('password2', 'password', 'Las contraseñas no coinciden.');
            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                try {
                    Usuario::cambiarPassword((string)$registro['id_usuario'], (string)$_POST['password']);
                    RecuperacionPassword::consumir($token, (string)$registro['id_usuario']);
                    SeguridadLogin::reiniciar(
                        Usuario::correoDe((string)$registro['id_usuario']),
                        SeguridadLogin::ip()
                    );
                    Flash::ok('Tu contraseña fue actualizada. Ya puedes iniciar sesión con la nueva.');
                    $this->redirigir('login');
                } catch (\Throwable $e) {
                    error_log('[RESTABLECER] ' . $e->getMessage());
                    $errores['_general'][] = 'No se pudo actualizar la contraseña. Solicita un nuevo enlace.';
                }
            }
        }

        $this->vista('auth/restablecer.php', [
            'errores' => $errores,
            'invalido' => false,
            'token' => $token,
        ]);
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
