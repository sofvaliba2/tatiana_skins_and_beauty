<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Upload;
use App\Core\Validate;
use App\Models\Historial;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\Sucursal;

final class ReservaController extends Controller
{
    private const TIPOS_PIEL = ['Normal', 'Seca', 'Grasa', 'Mixta', 'Sensible', 'No lo sé'];
    private const OBJETIVOS = ['Hidratación', 'Luminosidad', 'Antiedad', 'Control de acné', 'Manchas', 'Poros'];

    public function nueva(): void
    {
        $errores = [];
        $servicios = Servicio::todos();
        $sucursales = Sucursal::todas();
        $metodos = MetodoPago::activos();

        $idServicio = $this->texto('servicio');
        if ($idServicio === '' && $servicios !== []) {
            $idServicio = (string)$servicios[0]['id_servicio'];
        }
        $servicio = $idServicio !== '' ? Servicio::porId($idServicio) : null;

        $idSucursal = $this->texto('sucursal');
        if ($idSucursal === '') {
            $idSucursal = $this->sucursalPorDefecto($sucursales);
        }
        $fecha = $this->texto('fecha');
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = $this->fechaPorDefecto($idSucursal);
        }
        $hora = $this->texto('hora');
        $notas = $this->texto('notas');
        $accion = $this->texto('accion');
        $respuestas = [];

        if ($this->esPost()) {
            $v = Validate::make();
            if ($servicio === null) {
                $v->fallo('servicio', 'Selecciona un servicio válido.');
            }
            if ($idSucursal === '' || Sucursal::porId($idSucursal) === null) {
                $v->fallo('sucursal', 'Selecciona una sucursal válida.');
            }
            if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || strtotime($fecha) === false) {
                $v->fallo('fecha', 'Selecciona una fecha válida.');
            } elseif ($fecha < date('Y-m-d')) {
                $v->fallo('fecha', 'La fecha no puede ser en el pasado.');
            }
            if (mb_strlen($notas) > 500) {
                $v->fallo('notas', 'Las notas no pueden superar 500 caracteres.');
            }

            // El botón "Actualizar horarios" solo recalcula la agenda; nunca
            // crea una reserva a medias.
            if ($accion !== 'horarios' && !$v->fallido()) {
                $respuestas = $this->validarRespuestas($servicio, $v);

                $idMetodoPago = $this->texto('id_metodoPago');
                if ($idMetodoPago === '' && $metodos !== []) {
                    $idMetodoPago = (string)$metodos[0]['id_metodoPago'];
                }
                if ($idMetodoPago === '' || !MetodoPago::esActivo($idMetodoPago)) {
                    $v->fallo('id_metodoPago', 'Selecciona un método de pago válido.');
                }

                $this->validarHorario($idSucursal, $fecha, $hora, $v);
            }

            if ($v->fallido()) {
                $errores = $v->errores();
            } elseif ($accion !== 'horarios') {
                $idMetodoPago = $this->texto('id_metodoPago');
                if ($idMetodoPago === '' && $metodos !== []) {
                    $idMetodoPago = (string)$metodos[0]['id_metodoPago'];
                }
                try {
                    $idReserva = Reserva::crear(Auth::id(), $idSucursal, $fecha, $hora, $servicio, [
                        'tipo_tratamiento' => (string)($respuestas['tipo_tratamiento'] ?? Servicio::tipoTratamiento($servicio)),
                        'zona_tratamiento' => $respuestas['zona'] ?? null,
                        'respuestas'       => $respuestas,
                        'id_metodoPago'    => $idMetodoPago,
                    ]);
                    $_SESSION['reserva_en_curso'] = $idReserva;
                    $this->guardarHistorialReserva($idReserva, $servicio, $respuestas);

                    if ($idMetodoPago === 'MP01') {
                        Flash::ok('Reserva registrada. El pago completo se realiza en sucursal el día de tu cita.');
                        $this->redirigir('reserva_recibo', ['id' => $idReserva]);
                    }
                    Flash::ok('Reserva creada. Envía el comprobante del anticipo para confirmarla.');
                    $this->redirigir('reserva_pago', ['id' => $idReserva]);
                } catch (\DomainException $e) {
                    $v->fallo('hora', $e->getMessage());
                    $errores = $v->errores();
                } catch (\Throwable $e) {
                    error_log('[RESERVA] ' . $e->getMessage());
                    $errores['_general'][] = 'No se pudo crear la reserva. Intenta nuevamente.';
                }
            }
        }

        $horas = $this->horariosDisponibles($idSucursal, $fecha);

        $servicioSel = $servicio ?? ($servicios[0] ?? null);
        $tipoSeleccionado = $servicioSel !== null ? Servicio::tipoTratamiento($servicioSel) : 'facial';
        $zonaSeleccionada = strtolower((string)($_POST['zona'] ?? ''));

        $metodoSeleccionado = $this->texto('id_metodoPago');
        if ($metodoSeleccionado === '') {
            foreach ($metodos as $m) {
                if ($m['id_metodoPago'] === 'MP02') {
                    $metodoSeleccionado = 'MP02';
                    break;
                }
            }
            if ($metodoSeleccionado === '' && $metodos !== []) {
                $metodoSeleccionado = (string)$metodos[0]['id_metodoPago'];
            }
        }

        $productosPorServicio = [];
        foreach ($servicios as $s) {
            $productosPorServicio[(string)$s['id_servicio']] = array_map(
                static fn (array $p): array => [
                    'id'     => (string)$p['id_producto'],
                    'nombre' => (string)$p['nombre_producto'],
                    'precio' => (float)$p['precio'],
                ],
                Servicio::productosRelacionados((string)$s['id_servicio'], 3)
            );
        }

        $this->vista('reserva/nueva.php', [
            'servicios'             => $servicios,
            'sucursales'            => $sucursales,
            'metodos'               => $metodos,
            'errores'               => $errores,
            'horas'                 => $horas,
            'idServicio'            => $idServicio,
            'idSucursal'            => $idSucursal,
            'fecha'                 => $fecha,
            'hora'                  => $hora,
            'notas'                 => $notas,
            'respuestas'            => $respuestas,
            'tipoSeleccionado'      => $tipoSeleccionado,
            'zonaSeleccionada'      => $zonaSeleccionada,
            'metodoSeleccionado'    => $metodoSeleccionado,
            'productosPorServicio'  => $productosPorServicio,
            'anticipoMonto'         => (float)ANTICIPO_MONTO,
        ]);
    }

    public function pago(): void
    {
        $idReserva = $this->reservaActual();
        $reserva = $this->reservaPropia($idReserva);
        if ($reserva === null) {
            return;
        }

        $estadoPago = (string)($reserva['estado_pago'] ?? 'sin_pago');
        if (in_array($estadoPago, ['pendiente_efectivo', 'por_verificar', 'anticipo_confirmado', 'parcial', 'pagado'], true)) {
            Flash::error('Esta reserva ya no requiere un nuevo comprobante de anticipo.');
            $this->redirigir('reserva_recibo', ['id' => $idReserva]);
        }

        $errores = [];
        $metodos = MetodoPago::activos();
        $items = Reserva::items($idReserva);
        $sucursal = Sucursal::porId((string)$reserva['id_sucursal']);
        $resumen = Reserva::resumenPago($reserva);
        $total = $resumen['total'];
        $anticipo = $resumen['anticipo'];
        $saldo = $resumen['saldo'];

        if ($this->esPost()) {
            $idMetodo = $this->texto('id_metodoPago');
            if ($idMetodo === '') {
                $idMetodo = 'MP02';
            }
            if (!MetodoPago::esActivo($idMetodo) || $idMetodo === 'MP01') {
                $errores['id_metodoPago'][] = 'Selecciona un método de pago digital válido.';
            }
            if (empty($_FILES['comprobante']['name'] ?? '')) {
                $errores['comprobante'][] = 'Adjunta la captura de tu pago.';
            }

            if (!$errores) {
                $errorUp = null;
                $ruta = Upload::imagen(
                    $_FILES['comprobante'],
                    'uploads/comprobantes/res_' . $idReserva . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)),
                    $errorUp
                );
                if ($ruta === null) {
                    $errores['comprobante'][] = $errorUp ?? 'No se pudo guardar el comprobante.';
                } else {
                    try {
                        Reserva::registrarComprobante($idReserva, $ruta, $idMetodo);
                        Flash::ok('Comprobante enviado. El personal verificará tu anticipo y confirmará la cita.');
                        $this->redirigir('reserva_recibo', ['id' => $idReserva]);
                    } catch (\DomainException $e) {
                        $errores['_general'][] = $e->getMessage();
                    } catch (\Throwable $e) {
                        error_log('[RESERVA-PAGO] ' . $e->getMessage());
                        $errores['_general'][] = 'No se pudo registrar el comprobante. Intenta nuevamente.';
                    }
                }
            }
        }

        $this->vista('reserva/pago.php', [
            'reserva'  => $reserva,
            'errores'  => $errores,
            'metodos'  => $metodos,
            'items'    => $items,
            'sucursal' => $sucursal,
            'total'    => $total,
            'anticipo' => $anticipo,
            'saldo'    => $saldo,
        ]);
    }

    /** Formulario de piel antiguo (se mantiene por compatibilidad de rutas). */
    public function formulario(): void
    {
        $idReserva = $this->reservaActual();
        $reserva = $this->reservaPropia($idReserva);
        if ($reserva === null) {
            return;
        }

        $previo = Historial::ultimoDeUsuario(Auth::id());
        $esSeguimiento = $previo !== null;

        if ($this->esPost()) {
            if ($esSeguimiento) {
                $this->guardarSeguimiento($idReserva, $previo);
            } else {
                $this->guardarInicial($idReserva);
            }
        }

        $ultimo = [];
        if ($previo !== null) {
            $ultimo = [
                'tipo_piel' => $this->extraerTipoPiel((string)$previo['objetivo_piel']),
                'objetivo'  => (string)$previo['objetivo_piel'],
                'alergias'  => (string)($previo['alergias'] ?? 'ninguna'),
            ];
        }
        $this->vista($esSeguimiento ? 'reserva/formulario_seguimiento.php' : 'reserva/formulario_inicial.php', [
            'reserva' => $reserva,
            'ultimo'  => $ultimo,
            'errores' => [],
        ]);
    }

    public function seguimiento(): void
    {
        $this->formulario();
    }

    private function guardarInicial(int $idReserva): void
    {
        $v = Validate::make();
        if (($_POST['tipo_piel'] ?? '') === '') {
            $v->fallo('tipo_piel', 'Selecciona tu tipo de piel.');
        }
        if ($v->fallido()) {
            Flash::error($v->mensajes()[0]);
            $this->redirigir('reserva_formulario', ['id' => $idReserva]);
        }

        $objetivos = array_filter((array)($_POST['objetivo'] ?? []), 'is_string');
        $objetivoPiel = 'Piel: ' . (string)$_POST['tipo_piel'];
        if ($objetivos !== []) {
            $objetivoPiel .= ' · Objetivos: ' . implode(', ', array_map('strval', $objetivos));
        }

        Historial::guardarInicial($idReserva, [
            'alergias'                => trim((string)($_POST['alergias'] ?? '')) !== '' ? trim((string)$_POST['alergias']) : 'ninguna',
            'objetivo_piel'           => $objetivoPiel,
            'abierto_recomendaciones' => isset($_POST['abierto_recomendaciones']) ? 1 : 0,
            'rutina_actual'           => trim((string)($_POST['rutina_actual'] ?? '')),
            'observaciones_sesion'    => trim((string)($_POST['observaciones'] ?? '')),
        ]);
        Flash::ok('Tu información fue guardada.');
        $this->redirigir('reserva_recibo', ['id' => $idReserva]);
    }

    private function guardarSeguimiento(int $idReserva, array $previo): void
    {
        if (($_POST['resultado'] ?? '') === '') {
            Flash::error('Indica cómo te fue con el tratamiento anterior.');
            $this->redirigir('reserva_seguimiento', ['id' => $idReserva]);
        }

        $reacciones = array_filter((array)($_POST['reacciones'] ?? []), 'is_string');
        $obs = 'Resultado: ' . (string)$_POST['resultado'];
        $obs .= ' · Producto: ' . (trim((string)($_POST['crema_utilizada'] ?? '')) ?: 'no indicado');
        $obs .= ' · Tiempo de uso: ' . (string)($_POST['tiempo_uso'] ?? '');
        if ($reacciones !== []) {
            $obs .= ' · Reacciones: ' . implode(', ', array_map('strval', $reacciones));
        }
        if (trim((string)($_POST['cambios'] ?? '')) !== '') {
            $obs .= ' · Cambios: ' . trim((string)$_POST['cambios']);
        }
        $alergiasNuevas = trim((string)($_POST['alergias_nuevas'] ?? ''));

        Historial::agregarSeguimiento($idReserva, $previo, $obs, $alergiasNuevas);
        Flash::ok('Seguimiento registrado.');
        $this->redirigir('reserva_recibo', ['id' => $idReserva]);
    }

    public function recibo(): void
    {
        $idReserva = $this->entero('id', (int)($_SESSION['reserva_en_curso'] ?? 0));
        $reserva = Reserva::conCliente($idReserva);
        if ($reserva === null || !$this->puedeVer($reserva)) {
            if ($reserva === null && $idReserva <= 0) {
                Flash::error('Primero crea una reserva.');
                $this->redirigir('reserva_nueva');
            }
            (new ErroresController())->denegado();
            return;
        }

        $items = Reserva::items($idReserva);
        $resumen = Reserva::resumenPago($reserva);
        $recomendados = [];
        if ($items !== []) {
            $recomendados = Servicio::productosRelacionados((string)$items[0]['id_servicio'], 4);
        }
        $respuestas = [];
        if (!empty($reserva['respuestas'])) {
            $decodificado = json_decode((string)$reserva['respuestas'], true);
            if (is_array($decodificado)) {
                $respuestas = $decodificado;
            }
        }

        $this->vista('reserva/recibo.php', [
            'reserva'      => $reserva,
            'items'        => $items,
            'metodo'       => (string)($reserva['nombre_metodoPago'] ?? 'Por definir'),
            'total'        => $resumen['total'],
            'resumen'      => $resumen,
            'recomendados' => $recomendados,
            'respuestas'   => $respuestas,
        ]);
    }

    public function misReservas(): void
    {
        if ($this->esPost()) {
            if ($this->texto('accion') === 'cancelar') {
                $id = $this->entero('id_reserva');
                if (Reserva::cancelarDeUsuario($id, Auth::id())) {
                    Flash::ok('Reserva cancelada.');
                } else {
                    Flash::error('No se pudo cancelar. Si ya registraste un pago, contacta al centro para gestionarlo.');
                }
            }
            $this->redirigir('mis_reservas');
        }

        $reservas = Reserva::deUsuario(Auth::id());
        $this->vista('reserva/mis_reservas.php', compact('reservas'));
    }

    public function recomendaciones(): void
    {
        $historial = Historial::ultimoDeUsuario(Auth::id());
        if ($historial === null) {
            Flash::error('Aún no tienes un historial registrado. Completa el formulario de tu primera reserva.');
            $this->redirigir('mis_reservas');
        }

        $objetivo = (string)$historial['objetivo_piel'];
        $alergias = mb_strtolower((string)($historial['alergias'] ?? ''));
        $recomendados = Producto::conStock(6);
        foreach ($recomendados as &$p) {
            $texto = mb_strtolower($p['nombre_producto'] . ' ' . $p['descripcion'] . ' ' . ($p['nombre_categoria'] ?? ''));
            $coincide = false;
            foreach (['luminosidad', 'hidrata', 'antiedad', 'manchas', 'poros', 'acné'] as $kw) {
                if (str_contains($objetivo, $kw) && str_contains($texto, $kw)) {
                    $coincide = true;
                    break;
                }
            }
            $p['afinidad'] = $coincide ? 95 : 80;
            $p['motivo'] = $coincide
                ? 'Coincide con tu objetivo registrado: "[' . $objetivo . ']".'
                : 'Complementa tu rutina registrada sin ingredientes de tus alergias.';
            if ($alergias !== '' && $alergias !== 'ninguna' && str_contains($alergias, mb_strtolower($p['nombre_producto']))) {
                $p['afinidad'] = 40;
                $p['motivo'] = 'Precaución: revisa tus alergias registradas antes de usar este producto.';
            }
        }
        unset($p);
        $this->vista('reserva/recomendaciones.php', ['recomendados' => $recomendados, 'historial' => $historial, 'objetivo' => $objetivo]);
    }

    private function reservaActual(): int
    {
        $id = $this->entero('id', (int)($_SESSION['reserva_en_curso'] ?? 0));
        if ($id > 0) {
            $_SESSION['reserva_en_curso'] = $id;
        }
        return $id;
    }

    private function reservaPropia(int $id): ?array
    {
        if ($id <= 0) {
            Flash::error('Primero crea una reserva.');
            $this->redirigir('reserva_nueva');
        }
        $reserva = Reserva::porId($id);
        if ($reserva === null || !$this->puedeVer($reserva)) {
            (new ErroresController())->denegado();
            return null;
        }
        return $reserva;
    }

    private function puedeVer(array $reserva): bool
    {
        return ($reserva['id_usuario'] ?? '') === Auth::id() || Auth::esPersonal();
    }

    private function validarHorario(string $idSucursal, string $fecha, string $hora, Validate $v): void
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
            $v->fallo('hora', 'Selecciona una hora disponible.');
            return;
        }
        $encontrada = false;
        foreach ($this->horariosDisponibles($idSucursal, $fecha) as $h) {
            if ($h['hora'] === $hora) {
                $encontrada = true;
                if (!$h['libre']) {
                    $v->fallo('hora', 'Esa hora ya está ocupada. Elige otra.');
                }
                break;
            }
        }
        if (!$encontrada) {
            $v->fallo('hora', 'La hora elegida no está dentro del horario de atención de la sucursal.');
        }
    }

    /** Valida y normaliza únicamente las preguntas que aplican al servicio. */
    private function validarRespuestas(array $servicio, Validate $v): array
    {
        $tipo = Servicio::tipoTratamiento($servicio);
        $r = ['tipo_tratamiento' => $tipo];
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        if (mb_strlen($observaciones) > 600) {
            $v->fallo('observaciones', 'Las observaciones no pueden superar 600 caracteres.');
        }
        $r['observaciones'] = $observaciones;

        if ($tipo === 'depilacion') {
            $zonas = Servicio::zonasDepilacion();
            $zona = strtolower(trim((string)($_POST['zona'] ?? '')));
            if (!isset($zonas[$zona])) {
                $v->fallo('zona', 'Selecciona la zona que deseas depilar.');
            }
            $r['zona'] = $zona;

            if ($zona === 'otras') {
                $otra = trim((string)($_POST['zona_otra'] ?? ''));
                if ($otra === '') {
                    $v->fallo('zona_otra', 'Describe la zona que deseas depilar.');
                } elseif (mb_strlen($otra) > 60) {
                    $v->fallo('zona_otra', 'La descripción no puede superar 60 caracteres.');
                }
                $r['zona_otra'] = $otra;
            }

            $this->validarSiNo($v, 'depil_previa', 'Indica si te has depilado antes.', $r);
            $this->validarSiNo($v, 'sensibilidad', 'Indica si tienes la piel sensible.', $r);
            $this->validarAlergias($v, $r);

            foreach ($this->preguntasZona($zona) as $campo => $etiqueta) {
                $this->validarSiNo($v, $campo, $etiqueta, $r);
            }
        } else {
            $tipoPiel = trim((string)($_POST['tipo_piel'] ?? ''));
            if (!in_array($tipoPiel, self::TIPOS_PIEL, true)) {
                $v->fallo('tipo_piel', 'Selecciona tu tipo de piel.');
            }
            $r['tipo_piel'] = $tipoPiel;

            $objetivos = array_values(array_filter((array)($_POST['objetivo'] ?? []), 'is_string'));
            $objetivos = array_values(array_intersect($objetivos, self::OBJETIVOS));
            if ($objetivos === []) {
                $v->fallo('objetivo', 'Selecciona al menos un objetivo del tratamiento.');
            }
            $r['objetivos'] = $objetivos;

            $this->validarSiNo($v, 'tratamiento_previo', 'Indica si te has realizado un tratamiento facial antes.', $r);
            if (($r['tratamiento_previo'] ?? 'no') === 'si') {
                $detalle = trim((string)($_POST['tratamiento_previo_detalle'] ?? ''));
                if ($detalle === '') {
                    $v->fallo('tratamiento_previo_detalle', 'Indica qué tratamiento facial te realizaste.');
                } elseif (mb_strlen($detalle) > 150) {
                    $v->fallo('tratamiento_previo_detalle', 'El detalle no puede superar 150 caracteres.');
                }
                $r['tratamiento_previo_detalle'] = $detalle;
            }
            $this->validarAlergias($v, $r);
        }

        return $r;
    }

    private function validarSiNo(Validate $v, string $campo, string $etiqueta, array &$r): void
    {
        $valor = strtolower(trim((string)($_POST[$campo] ?? '')));
        if ($valor === 'sí') {
            $valor = 'si';
        }
        if (!in_array($valor, ['si', 'no'], true)) {
            $v->fallo($campo, $etiqueta);
            return;
        }
        $r[$campo] = $valor;
    }

    private function validarAlergias(Validate $v, array &$r): void
    {
        $this->validarSiNo($v, 'alergias', 'Indica si tienes alergias o sensibilidades.', $r);
        if (($r['alergias'] ?? 'no') === 'si') {
            $detalle = trim((string)($_POST['alergias_detalle'] ?? ''));
            if ($detalle === '') {
                $v->fallo('alergias_detalle', 'Describe tus alergias o sensibilidades.');
            } elseif (mb_strlen($detalle) > 300) {
                $v->fallo('alergias_detalle', 'La descripción no puede superar 300 caracteres.');
            }
            $r['alergias_detalle'] = $detalle;
        }
    }

    /** Preguntas específicas por zona de depilación. */
    private function preguntasZona(string $zona): array
    {
        return match ($zona) {
            'axilas'  => ['axilas_desodorante' => '¿Usas desodorante o antitranspirante?', 'axilas_irritacion' => '¿Tienes irritación en las axilas?'],
            'piernas' => ['piernas_exfoliacion' => '¿Exfolias la zona regularmente?', 'piernas_reseca' => '¿Sientes la piel reseca?'],
            'brazos'  => ['brazos_exfoliacion' => '¿Exfolias la zona regularmente?', 'brazos_reseca' => '¿Sientes la piel reseca?'],
            'rostro'  => ['rostro_acidos' => '¿Usaste retinoides o ácidos en los últimos 7 días?', 'rostro_sensible' => '¿Tu rostro es sensible?'],
            'bozo'    => ['bozo_acidos' => '¿Usaste retinoides o ácidos en los últimos 7 días?', 'bozo_sensible' => '¿La zona es sensible?'],
            'cejas'   => ['cejas_acidos' => '¿Usaste retinoides o ácidos en los últimos 7 días?', 'cejas_sensible' => '¿La zona es sensible?'],
            'pies'    => ['pies_hongos' => '¿Tienes hongos en los pies?', 'pies_heridas' => '¿Tienes heridas o grietas?'],
            'otras'   => ['otras_sensibilidad' => '¿La zona es sensible?'],
            default   => [],
        };
    }

    /** Guarda también las respuestas en el historial de piel del cliente. */
    private function guardarHistorialReserva(int $idReserva, array $servicio, array $respuestas): void
    {
        try {
            if (Servicio::tipoTratamiento($servicio) === 'depilacion') {
                $zona = (string)($respuestas['zona_otra'] ?? $respuestas['zona'] ?? '');
                Historial::guardarInicial($idReserva, [
                    'alergias'                => ($respuestas['alergias'] ?? 'no') === 'si'
                        ? (string)($respuestas['alergias_detalle'] ?? '')
                        : 'ninguna',
                    'objetivo_piel'           => 'Depilación: ' . $zona,
                    'abierto_recomendaciones' => 0,
                    'rutina_actual'           => '',
                    'observaciones_sesion'    => (string)($respuestas['observaciones'] ?? ''),
                ]);
                return;
            }

            $objetivos = is_array($respuestas['objetivos'] ?? null) ? implode(', ', $respuestas['objetivos']) : '';
            Historial::guardarInicial($idReserva, [
                'alergias'                => ($respuestas['alergias'] ?? 'no') === 'si'
                    ? (string)($respuestas['alergias_detalle'] ?? '')
                    : 'ninguna',
                'objetivo_piel'           => 'Piel: ' . (string)($respuestas['tipo_piel'] ?? '—')
                    . ($objetivos !== '' ? ' · Objetivos: ' . $objetivos : ''),
                'abierto_recomendaciones' => 1,
                'rutina_actual'           => '',
                'observaciones_sesion'    => (string)($respuestas['observaciones'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            // La reserva ya está creada; no se pierde por un fallo del historial.
            error_log('[RESERVA-HISTORIAL] ' . $e->getMessage());
        }
    }

    private function horariosDisponibles(string $idSucursal, string $fecha): array
    {
        $diaIngles = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miercoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado'];
        $dia = $diaIngles[date('l', strtotime($fecha))] ?? '';
        $horario = Sucursal::horarioDelDia($idSucursal, $dia);
        $ocupadas = [];
        if ($idSucursal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $ocupadas = Reserva::horasOcupadas($fecha, $idSucursal);
        }
        $horas = [];
        if ($horario === null) {
            return $horas;
        }
        $ini = (int)substr((string)$horario['hora_apertura'], 0, 2) * 60 + (int)substr((string)$horario['hora_apertura'], 3, 2);
        $fin = (int)substr((string)$horario['hora_cierre'], 0, 2) * 60 + (int)substr((string)$horario['hora_cierre'], 3, 2);
        for ($m = $ini; $m + 30 <= $fin; $m += 30) {
            $h = str_pad((string)intdiv($m, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT);
            $horas[] = [
                'hora'  => $h,
                'libre' => !in_array($h . ':00', $ocupadas, true) && strtotime($fecha . ' ' . $h) > time(),
            ];
        }
        return $horas;
    }

    private function sucursalPorDefecto(array $sucursales): string
    {
        foreach ($sucursales as $s) {
            $id = (string)$s['id_sucursal'];
            foreach (Sucursal::DIAS as $dia) {
                if (Sucursal::horarioDelDia($id, $dia) !== null) {
                    return $id;
                }
            }
        }
        return isset($sucursales[0]) ? (string)$sucursales[0]['id_sucursal'] : '';
    }

    private function fechaPorDefecto(string $idSucursal): string
    {
        for ($i = 1; $i <= 14; $i++) {
            $fecha = date('Y-m-d', strtotime('+' . $i . ' day'));
            $diaIngles = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miercoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado'];
            $dia = $diaIngles[date('l', strtotime($fecha))] ?? '';
            if ($idSucursal !== '' && Sucursal::horarioDelDia($idSucursal, $dia) !== null) {
                return $fecha;
            }
        }
        return date('Y-m-d', strtotime('+1 day'));
    }

    private function extraerTipoPiel(string $objetivo): string
    {
        if (preg_match('/Piel:\s*([^·]+)/', $objetivo, $m)) {
            return trim($m[1]);
        }
        return '—';
    }
}
