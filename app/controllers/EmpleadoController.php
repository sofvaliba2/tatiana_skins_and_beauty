<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validate;
use App\Models\Compra;
use App\Models\Historial;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Usuario;

/**
 * Panel de trabajo para empleados (ROL02).
 *
 * Todas las operaciones de agenda, clientes e historial se limitan a la
 * sucursal asignada al usuario. Las ventas validan precios y stock en el
 * servidor y generan un comprobante interno.
 */
final class EmpleadoController extends Controller
{
    private const POR_PAGINA = 10;

    public function __construct()
    {
        if (!Auth::esEmpleado()) {
            (new ErroresController())->denegado();
            exit;
        }
    }

    public function dashboard(): void
    {
        $ctx = $this->exigirSucursal();
        $hoy = date('Y-m-d');

        $citasHoy = Reserva::citasDelDiaSucursal($hoy, $ctx['id']);
        $proximas = Reserva::proximasDeSucursal($ctx['id'], $hoy, 6);
        $pendientes = Reserva::contarPendientesSucursal($ctx['id']);
        $totalCitasHoy = Reserva::contarDelDiaSucursal($hoy, $ctx['id']);
        $totalProximas = Reserva::contarProximasSucursal($ctx['id'], $hoy);
        $totalClientes = Usuario::contarClientesDeSucursal($ctx['id']);

        $this->vistaEmpleado('empleado/dashboard.php', $ctx, compact(
            'citasHoy',
            'proximas',
            'pendientes',
            'totalCitasHoy',
            'totalProximas',
            'totalClientes',
            'hoy'
        ));
    }

    public function clientes(): void
    {
        $ctx = $this->exigirSucursal();
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        ['filas' => $clientes, 'total' => $total] = Usuario::buscarClientesDeSucursal(
            $q,
            $pagina,
            self::POR_PAGINA,
            $ctx['id']
        );

        $ficha = null;
        $historial = [];
        $reservas = [];
        $idFicha = $this->texto('ficha');
        if ($idFicha !== '') {
            $ficha = Usuario::clienteDeSucursal($idFicha, $ctx['id']);
            if ($ficha === null) {
                Flash::error('La clienta seleccionada no pertenece a tu sucursal.');
            } else {
                $historial = Historial::deUsuarioDeSucursal($idFicha, $ctx['id']);
                $reservas = Reserva::deClienteDeSucursal($idFicha, $ctx['id']);
            }
        }

        $this->vistaEmpleado('empleado/clientes.php', $ctx, compact(
            'clientes',
            'q',
            'pagina',
            'total',
            'ficha',
            'historial',
            'reservas'
        ));
    }

    public function citas(): void
    {
        $ctx = $this->exigirSucursal();

        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'crear') {
                $this->crearCita($ctx);
            } elseif ($accion === 'reprogramar') {
                $this->reprogramarCita($ctx);
            } elseif ($accion === 'estado') {
                $this->cambiarEstadoCita($ctx);
            } elseif ($accion === 'observacion') {
                $this->guardarObservacionCita($ctx);
            }

            $this->redirigir('empleado_citas', array_filter([
                'fecha'   => $this->texto('fecha'),
                'estado'  => $this->texto('estado'),
                'cliente' => $this->texto('cliente'),
                'ficha'   => $this->entero('ficha') ?: null,
            ]));
        }

        $fecha = $this->texto('fecha');
        $estado = $this->texto('estado');
        $idCliente = $this->texto('cliente');
        $idFicha = $this->entero('ficha');

        $reservas = Reserva::filtrar($fecha, $estado, $ctx['id'], 100, $idCliente);
        $clientes = Usuario::listaClientesDeSucursal($ctx['id']);
        $servicios = Servicio::todos();

        $fichaCliente = null;
        if ($idFicha > 0) {
            $reservaFicha = Reserva::porIdDeSucursal($idFicha, $ctx['id']);
            if ($reservaFicha !== null) {
                $fichaCliente = [
                    'reserva'   => $reservaFicha,
                    'usuario'   => Usuario::clienteConCorreo((string)$reservaFicha['id_usuario']),
                    'historial' => Historial::deUsuarioDeSucursal((string)$reservaFicha['id_usuario'], $ctx['id']),
                ];
            }
        }

        $idEditar = $this->entero('editar');
        $editarReserva = $idEditar > 0 ? Reserva::porIdDeSucursal($idEditar, $ctx['id']) : null;
        $editarItems = $editarReserva !== null ? Reserva::items($idEditar) : [];
        $editarServicio = (string)($editarItems[0]['id_servicio'] ?? '');

        $this->vistaEmpleado('empleado/citas.php', $ctx, compact(
            'reservas',
            'clientes',
            'servicios',
            'fecha',
            'estado',
            'idCliente',
            'fichaCliente',
            'editarReserva',
            'editarServicio'
        ));
    }

    public function disponibilidad(): void
    {
        $ctx = $this->sucursalJson();
        $fecha = $this->texto('fecha');
        $idServicio = $this->texto('servicio');
        $excluir = $this->entero('excluir');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            $this->json(['ok' => false, 'horas' => [], 'mensaje' => 'Selecciona una fecha válida.'], 422);
        }
        $servicio = Servicio::porId($idServicio);
        if ($servicio === null) {
            $this->json(['ok' => false, 'horas' => [], 'mensaje' => 'Selecciona un servicio válido.'], 422);
        }

        $horas = Reserva::horasDisponibles(
            $fecha,
            $ctx['id'],
            (int)$servicio['duracion_minutos'],
            $excluir > 0 ? $excluir : null
        );

        $mensaje = '';
        if (Sucursal::horarioParaFecha($ctx['id'], $fecha) === null) {
            $mensaje = 'La sucursal está cerrada ese día.';
            $horas = [];
        } elseif ($horas === []) {
            $mensaje = 'No quedan horarios disponibles para ese día.';
        }

        $this->json(['ok' => true, 'horas' => $horas, 'mensaje' => $mensaje]);
    }

    public function historial(): void
    {
        $ctx = $this->exigirSucursal();

        if ($this->esPost() && $this->texto('accion') === 'seguimiento') {
            $idUsuario = $this->texto('id_usuario');
            $this->registrarSeguimiento($ctx);
            $this->redirigir('empleado_historial', array_filter(['cliente' => $idUsuario]));
        }

        $q = $this->texto('q');
        $idCliente = $this->texto('cliente');
        $clientes = Usuario::buscarClientesDeSucursal($q, 1, 30, $ctx['id'])['filas'];

        $ficha = null;
        $historial = [];
        $reservas = [];
        if ($idCliente !== '') {
            $ficha = Usuario::clienteDeSucursal($idCliente, $ctx['id']);
            if ($ficha === null) {
                Flash::error('La clienta seleccionada no pertenece a tu sucursal.');
            } else {
                $historial = Historial::deUsuarioDeSucursal($idCliente, $ctx['id']);
                $reservas = Reserva::deClienteDeSucursal($idCliente, $ctx['id']);
            }
        }

        $this->vistaEmpleado('empleado/historial.php', $ctx, compact(
            'clientes',
            'q',
            'idCliente',
            'ficha',
            'historial',
            'reservas'
        ));
    }

    public function ventas(): void
    {
        $ctx = $this->exigirSucursal();

        if ($this->esPost()) {
            $this->registrarVenta($ctx);
        }

        $clientes = Usuario::listaClientesDeSucursal($ctx['id']);
        $productos = Producto::lista();
        $servicios = Servicio::todos();
        $metodos = MetodoPago::activos();
        $ventas = Compra::recientesDeSucursal($ctx['id'], 10);

        $this->vistaEmpleado('empleado/ventas.php', $ctx, compact(
            'clientes',
            'productos',
            'servicios',
            'metodos',
            'ventas'
        ));
    }

    public function comprobante(): void
    {
        $ctx = $this->exigirSucursal();
        $compra = Compra::porId($this->entero('id'));

        $idCliente = (string)($compra['id_usuario'] ?? '');
        if (
            $compra === null ||
            $idCliente === '' ||
            Usuario::clienteDeSucursal($idCliente, $ctx['id']) === null
        ) {
            (new ErroresController())->denegado();
            return;
        }

        $items = Compra::items((int)$compra['id_compra']);
        $this->vista('cliente/detalle_compra.php', compact('compra', 'items'));
    }

    public function perfil(): void
    {
        $ctx = $this->actual();
        $id = Auth::id();
        $errores = [];

        if ($this->esPost()) {
            $accion = $this->texto('accion', 'datos');
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
                    Flash::ok('Tus datos fueron actualizados.');
                    $this->redirigir('empleado_perfil');
                }
            } elseif ($accion === 'password') {
                $errores = $this->cambiarPassword($id);
                if ($errores === []) {
                    Flash::ok('Contraseña actualizada.');
                    $this->redirigir('empleado_perfil');
                }
            }
        }

        $usuario = Usuario::porId($id);
        $cuenta = ['correo' => Usuario::correoDe($id)];
        $this->vistaEmpleado('empleado/perfil.php', $ctx, compact('usuario', 'cuenta', 'errores'));
    }

    private function crearCita(array $ctx): void
    {
        $idCliente = $this->texto('id_usuario');
        $idServicio = $this->texto('id_servicio');
        $fecha = $this->texto('fecha');
        $hora = $this->texto('hora');
        $notas = $this->texto('notas');

        if (Usuario::clienteDeSucursal($idCliente, $ctx['id']) === null) {
            Flash::error('Selecciona una clienta vinculada a tu sucursal.');
            return;
        }
        $servicio = Servicio::porId($idServicio);
        if ($servicio === null) {
            Flash::error('Selecciona un servicio válido.');
            return;
        }
        if ($mensaje = $this->validarFechaHora($fecha, $hora)) {
            Flash::error($mensaje);
            return;
        }

        $motivo = Reserva::motivoHorarioInvalido($fecha, $hora, $ctx['id'], (int)$servicio['duracion_minutos']);
        if ($motivo !== null) {
            Flash::error($motivo);
            return;
        }
        if (Reserva::citaDuplicadaCliente($idCliente, $fecha, $hora, $ctx['id'])) {
            Flash::error('La clienta ya tiene una cita a esa misma fecha y hora.');
            return;
        }

        try {
            $idReserva = Reserva::crear(
                $idCliente,
                $ctx['id'],
                $fecha,
                $hora,
                [
                    'id_servicio' => (string)$servicio['id_servicio'],
                    'precio'      => (float)$servicio['precio'],
                ],
                $notas,
                'confirmada'
            );
            Flash::ok('Cita #' . $idReserva . ' registrada para el ' . date('d/m/Y', strtotime($fecha)) . ' a las ' . substr($hora, 0, 5) . '.');
        } catch (\Throwable $e) {
            error_log('[EMPLEADO-CITAS] ' . $e->getMessage());
            Flash::error('No se pudo registrar la cita.');
        }
    }

    private function reprogramarCita(array $ctx): void
    {
        $idReserva = $this->entero('id_reserva');
        $reserva = Reserva::porIdDeSucursal($idReserva, $ctx['id']);
        if ($reserva === null) {
            Flash::error('La cita no pertenece a tu sucursal.');
            return;
        }

        $idServicio = $this->texto('id_servicio');
        $fecha = $this->texto('fecha');
        $hora = $this->texto('hora');
        $servicio = Servicio::porId($idServicio);
        if ($servicio === null) {
            Flash::error('Selecciona un servicio válido.');
            return;
        }
        if ($mensaje = $this->validarFechaHora($fecha, $hora)) {
            Flash::error($mensaje);
            return;
        }

        $motivo = Reserva::motivoHorarioInvalido(
            $fecha,
            $hora,
            $ctx['id'],
            (int)$servicio['duracion_minutos'],
            $idReserva
        );
        if ($motivo !== null) {
            Flash::error($motivo);
            return;
        }
        if (Reserva::citaDuplicadaCliente((string)$reserva['id_usuario'], $fecha, $hora, $ctx['id'], $idReserva)) {
            Flash::error('La clienta ya tiene otra cita a esa misma fecha y hora.');
            return;
        }

        try {
            Reserva::actualizar($idReserva, $ctx['id'], $fecha, $hora, [
                'id_servicio' => (string)$servicio['id_servicio'],
                'precio'      => (float)$servicio['precio'],
            ]);
            Flash::ok('Cita #' . $idReserva . ' reprogramada.');
        } catch (\Throwable $e) {
            error_log('[EMPLEADO-CITAS] ' . $e->getMessage());
            Flash::error('No se pudo reprogramar la cita.');
        }
    }

    private function cambiarEstadoCita(array $ctx): void
    {
        $idReserva = $this->entero('id_reserva');
        $estado = $this->texto('nuevo_estado');

        if (Reserva::porIdDeSucursal($idReserva, $ctx['id']) === null) {
            Flash::error('La cita no pertenece a tu sucursal.');
            return;
        }
        if (!in_array($estado, Reserva::ESTADOS, true)) {
            Flash::error('Estado de cita no válido.');
            return;
        }
        if (Reserva::cambiarEstado($idReserva, $estado)) {
            Flash::ok('Cita #' . $idReserva . ' marcada como ' . $estado . '.');
        } else {
            Flash::error('No se pudo actualizar la cita.');
        }
    }

    private function guardarObservacionCita(array $ctx): void
    {
        $idReserva = $this->entero('id_reserva');
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));

        if (Reserva::porIdDeSucursal($idReserva, $ctx['id']) === null) {
            Flash::error('La cita no pertenece a tu sucursal.');
            return;
        }
        if ($observaciones === '') {
            Flash::error('Escribe las observaciones de la sesión.');
            return;
        }

        Historial::registrarSeguimiento($idReserva, ['observaciones_sesion' => $observaciones]);
        Flash::ok('Observación guardada en el historial de piel.');
    }

    private function registrarSeguimiento(array $ctx): void
    {
        $idUsuario = $this->texto('id_usuario');
        $idReserva = $this->entero('id_reserva');
        $reserva = Reserva::porIdDeSucursal($idReserva, $ctx['id']);

        if ($reserva === null || (string)$reserva['id_usuario'] !== $idUsuario) {
            Flash::error('Selecciona una cita válida de la clienta en tu sucursal.');
            return;
        }
        if (Usuario::clienteDeSucursal($idUsuario, $ctx['id']) === null) {
            Flash::error('La clienta no pertenece a tu sucursal.');
            return;
        }

        $datos = [
            'alergias'                => $this->texto('alergias'),
            'objetivo_piel'           => $this->texto('objetivo_piel'),
            'rutina_actual'           => $this->texto('rutina_actual'),
            'observaciones_sesion'    => $this->texto('observaciones_sesion'),
            'abierto_recomendaciones' => $this->texto('abierto_recomendaciones') !== '',
        ];

        if (
            $datos['alergias'] === '' &&
            $datos['objetivo_piel'] === '' &&
            $datos['rutina_actual'] === '' &&
            $datos['observaciones_sesion'] === ''
        ) {
            Flash::error('Registra al menos un dato de la ficha o una observación de la sesión.');
            return;
        }

        try {
            Historial::registrarSeguimiento($idReserva, $datos);
            Flash::ok('Ficha de seguimiento guardada para la cita #' . $idReserva . '.');
        } catch (\Throwable $e) {
            error_log('[EMPLEADO-HISTORIAL] ' . $e->getMessage());
            Flash::error('No se pudo guardar la ficha de seguimiento.');
        }
    }

    private function registrarVenta(array $ctx): void
    {
        $idCliente = $this->texto('id_usuario');
        $idMetodo = $this->texto('id_metodoPago');

        if (Usuario::clienteDeSucursal($idCliente, $ctx['id']) === null) {
            Flash::error('Selecciona una clienta vinculada a tu sucursal.');
            $this->redirigir('empleado_ventas');
        }

        $metodos = MetodoPago::activos();
        if ($metodos !== []) {
            if ($idMetodo === '' || MetodoPago::activo($idMetodo) === null) {
                Flash::error('Selecciona un método de pago válido.');
                $this->redirigir('empleado_ventas');
            }
        } else {
            $idMetodo = '';
        }

        $items = $this->itemsDeVenta();
        if ($items === []) {
            Flash::error('Agrega al menos un producto o servicio a la venta.');
            $this->redirigir('empleado_ventas');
        }

        try {
            $compra = Compra::crear($idCliente, $idMetodo !== '' ? $idMetodo : null, $items);
            Flash::ok('Venta registrada: ' . $compra['nro'] . ' por ' . bs($compra['total']) . '.');
            $this->redirigir('empleado_comprobante', ['id' => $compra['id']]);
        } catch (\DomainException $e) {
            Flash::error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EMPLEADO-VENTAS] ' . $e->getMessage());
            Flash::error('No se pudo registrar la venta.');
        }
        $this->redirigir('empleado_ventas');
    }

    /** Convierte las líneas del formulario en ítems validados para Compra::crear(). */
    private function itemsDeVenta(): array
    {
        $selecciones = $_POST['item'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        if (!is_array($selecciones)) {
            $selecciones = [];
        }
        if (!is_array($cantidades)) {
            $cantidades = [];
        }

        $items = [];
        foreach ($selecciones as $i => $valor) {
            $valor = trim((string)$valor);
            if ($valor === '') {
                continue;
            }
            $partes = explode(':', $valor, 2);
            if (count($partes) !== 2) {
                continue;
            }
            $tipo = strtolower(trim($partes[0]));
            $id = trim($partes[1]);
            $cantidad = max(1, (int)($cantidades[$i] ?? 1));

            if (!in_array($tipo, ['producto', 'servicio'], true) || $id === '') {
                continue;
            }
            $items[] = ['tipo' => $tipo, 'id' => $id, 'cantidad' => $cantidad];
        }
        return $items;
    }

    private function validarFechaHora(string $fecha, string $hora): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return 'Selecciona una fecha válida.';
        }
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora) !== 1) {
            return 'Selecciona una hora válida.';
        }
        $marca = strtotime($fecha . ' ' . $hora);
        if ($marca === false || $marca <= time()) {
            return 'La cita debe programarse para una fecha y hora futuras.';
        }
        return null;
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

    private function actual(): array
    {
        $usuario = Usuario::porId(Auth::id()) ?? [];
        $idSucursal = (string)($usuario['id_sucursal'] ?? '');
        $sucursal = $idSucursal !== '' ? Sucursal::porId($idSucursal) : null;

        return ['id' => $idSucursal, 'sucursal' => $sucursal, 'usuario' => $usuario];
    }

    private function exigirSucursal(): array
    {
        $ctx = $this->actual();
        if ($ctx['sucursal'] === null) {
            http_response_code(403);
            $this->vista('empleado/sin_sucursal.php', $this->datosContexto($ctx));
            exit;
        }
        return $ctx;
    }

    private function sucursalJson(): array
    {
        $ctx = $this->actual();
        if ($ctx['sucursal'] === null) {
            $this->json([
                'ok'      => false,
                'horas'   => [],
                'mensaje' => 'Tu cuenta no tiene una sucursal activa asignada.',
            ], 403);
        }
        return $ctx;
    }

    private function datosContexto(array $ctx): array
    {
        return [
            'sucursal_actual' => $ctx['sucursal'],
            'usuario_actual'  => $ctx['usuario'],
        ];
    }

    private function vistaEmpleado(string $archivo, array $ctx, array $datos = []): void
    {
        $this->vista($archivo, $datos + $this->datosContexto($ctx));
    }
}
