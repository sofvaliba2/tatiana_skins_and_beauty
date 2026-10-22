<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Ids;
use App\Core\Upload;
use App\Core\Validate;

final class Reserva extends Controller
{
    public function nueva(): void
    {
        $errores = [];
        $servicios = Database::all(
            'SELECT s.*, cs.nombre_cservicio AS categoria
             FROM servicio s
             LEFT JOIN categoria_servicio cs ON cs.id_categoriaS = s.id_categoriaS
             ORDER BY s.nombre'
        );
        $sucursales = Database::all('SELECT * FROM sucursal ORDER BY nombre_sucursal');

        if ($this->esPost()) {
            $idServicio = $this->texto('servicio');
            $idSucursal = $this->texto('sucursal');
            $fecha = $this->texto('fecha');
            $hora = $this->texto('hora');
            $notas = $this->texto('notas');

            $servicio = Database::one('SELECT * FROM servicio WHERE id_servicio = ?', [$idServicio]);
            $sucursal = Database::one('SELECT * FROM sucursal WHERE id_sucursal = ?', [$idSucursal]);

            $v = Validate::make();
            if ($servicio === null) {
                $v->fallo('servicio', 'Selecciona un servicio válido.');
            }
            if ($sucursal === null) {
                $v->fallo('sucursal', 'Selecciona una sucursal válida.');
            }
            if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || strtotime($fecha) === false) {
                $v->fallo('fecha', 'Selecciona una fecha válida.');
            } elseif ($fecha < date('Y-m-d')) {
                $v->fallo('fecha', 'La fecha no puede ser en el pasado.');
            }
            if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
                $v->fallo('hora', 'Selecciona una hora disponible.');
            }

            if (!$v->fallido()) {
                $ocupada = Database::value(
                    "SELECT COUNT(*) FROM reserva
                     WHERE fecha = ? AND hora = ? AND id_sucursal = ? AND estado <> 'cancelada'",
                    [$fecha, $hora . ':00', $idSucursal]
                );
                if ((int)$ocupada > 0) {
                    $v->fallo('hora', 'Esa hora ya está ocupada. Elige otra.');
                }
            }

            if ($v->fallido()) {
                $errores = $v->errores();
            } else {
                try {
                    Database::begin();
                    $idReserva = Ids::insertarConEntero('reserva', 'id_reserva', [
                        'fecha'        => $fecha,
                        'hora'         => $hora . ':00',
                        'estado'       => 'pendiente',
                        'id_sucursal'  => $idSucursal,
                        'id_metodoPago' => null,
                        'id_usuario'   => Auth::id(),
                    ]);
                    Ids::insertarConEntero('detalle_reserva', 'id_detalle', [
                        'cantidad'        => 1,
                        'precio_unitario' => $servicio['precio'],
                        'subtotal'        => $servicio['precio'],
                        'id_servicio'     => $idServicio,
                        'id_reserva'      => $idReserva,
                    ]);
                    if ($notas !== '') {
                        Ids::insertarConEntero('historial_cliente', 'id_historial', [
                            'alergias'               => null,
                            'objetivo_piel'          => null,
                            'abierto_recomendaciones' => 0,
                            'rutina_actual'          => null,
                            'observaciones_sesion'   => 'Notas de reserva: ' . $notas,
                            'fecha_registro'         => date('Y-m-d H:i:s'),
                            'id_reserva'             => $idReserva,
                        ]);
                    }
                    Database::commit();
                    $_SESSION['reserva_en_curso'] = $idReserva;
                    Flash::ok('Reserva creada. Ahora paga el anticipo para confirmarla.');
                    $this->redirigir('reserva_pago', ['id_metodoPago' => $idReserva]);
                } catch (\Throwable $e) {
                    Database::rollback();
                    error_log('[RESERVA] ' . $e->getMessage());
                    $errores['_general'][] = 'No se pudo crear la reserva. Intenta nuevamente.';
                }
            }
        }

        $horas = $this->horariosDisponibles($this->texto('sucursal', ($sucursales[0]['id_sucursal'] ?? '')), $this->texto('fecha', date('Y-m-d', strtotime('+1 day'))));
        $this->vista('reserva/nueva.php', compact('servicios', 'sucursales', 'errores', 'horas'));
    }

    public function pago(): void
    {
        $idReserva = $this->reservaActual();
        $reserva = $this->reservaPropia($idReserva);
        if ($reserva === null) {
            return;
        }
        $errores = [];
        $metodos = Database::all("SELECT * FROM metodo_pago WHERE estado = 'activo' OR estado IS NULL ORDER BY nombre_metodoPago");
        $items = Database::all(
            'SELECT dr.*, s.nombre, s.duracion_minutos
             FROM detalle_reserva dr
             JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE dr.id_reserva = ?',
            [$idReserva]
        );
        $sucursal = Database::one('SELECT nombre_sucursal FROM sucursal WHERE id_sucursal = ?', [$reserva['id_sucursal']]);
        $total = array_sum(array_column($items, 'subtotal'));

        if ($this->esPost()) {
            $idMetodo = $this->texto('id_metodoPago');
            $errorUp = null;
            $rutaComprobante = null;
            if (!empty($_FILES['comprobante']['name'] ?? '')) {
                $rutaComprobante = Upload::imagen($_FILES['comprobante'], 'uploads/comprobantes/res_' . $idReserva . '.jpg', $errorUp);
            }
            if ($rutaComprobante === null && $errorUp !== null && ($errorUp !== 'Selecciona una imagen.' || !empty($_FILES['comprobante']['name']))) {
                $errores['comprobante'][] = $errorUp;
            }
            if ($idMetodo === '' && $metodos !== []) {
                $errores['id_metodoPago'][] = 'Selecciona un método de pago.';
            }
            if (!$errores) {
                Database::run('UPDATE reserva SET id_metodoPago = ? WHERE id_reserva = ?', [$idMetodo !== '' ? $idMetodo : null, $idReserva]);
                Flash::ok('Comprobante enviado. El personal verificará tu pago y confirmará la cita.');
                $this->redirigir('reserva_formulario', ['id' => $idReserva]);
            }
        }
        $this->vista('reserva/pago.php', compact('reserva', 'errores', 'metodos', 'items', 'sucursal', 'total'));
    }

    public function formulario(): void
    {
        $idReserva = $this->reservaActual();
        $reserva = $this->reservaPropia($idReserva);
        if ($reserva === null) {
            return;
        }

        $previo = Database::one(
            'SELECT h.* FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             WHERE r.id_usuario = ? AND h.objetivo_piel IS NOT NULL
             ORDER BY h.fecha_registro DESC LIMIT 1',
            [Auth::id()]
        );
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
                'tipo_piel'  => $this->extraerTipoPiel((string)$previo['objetivo_piel']),
                'objetivo'   => (string)$previo['objetivo_piel'],
                'alergias'   => (string)($previo['alergias'] ?? 'ninguna'),
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

        $existente = Database::one('SELECT id_historial FROM historial_cliente WHERE id_reserva = ?', [$idReserva]);
        $datos = [
            'alergias'               => trim((string)($_POST['alergias'] ?? '')) !== '' ? trim((string)$_POST['alergias']) : 'ninguna',
            'objetivo_piel'          => $objetivoPiel,
            'abierto_recomendaciones' => isset($_POST['abierto_recomendaciones']) ? 1 : 0,
            'rutina_actual'          => trim((string)($_POST['rutina_actual'] ?? '')),
            'observaciones_sesion'   => trim((string)($_POST['observaciones'] ?? '')),
        ];
        if ($existente) {
            Database::run(
                'UPDATE historial_cliente SET alergias = ?, objetivo_piel = ?, abierto_recomendaciones = ?, rutina_actual = ?, observaciones_sesion = ?, fecha_registro = ? WHERE id_reserva = ?',
                [...array_values($datos), date('Y-m-d H:i:s'), $idReserva]
            );
        } else {
            Ids::insertarConEntero('historial_cliente', 'id_historial', $datos + [
                'fecha_registro' => date('Y-m-d H:i:s'),
                'id_reserva'     => $idReserva,
            ]);
        }
        Flash::ok('Tu información fue guardada.');
        $this->redirigir('reserva_recibo', ['id' => $idReserva]);
    }

    private function guardarSeguimiento(int $idReserva, array $previo): void
    {
        $v = Validate::make();
        if (($_POST['resultado'] ?? '') === '') {
            $v->fallo('resultado', 'Indica cómo te fue con el tratamiento anterior.');
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

        $idHistorial = Ids::insertarConEntero('historial_cliente', 'id_historial', [
            'alergias'               => $alergiasNuevas !== '' ? $alergiasNuevas : $previo['alergias'],
            'objetivo_piel'          => $previo['objetivo_piel'],
            'abierto_recomendaciones' => $previo['abierto_recomendaciones'],
            'rutina_actual'          => $previo['rutina_actual'],
            'observaciones_sesion'   => $obs,
            'fecha_registro'         => date('Y-m-d H:i:s'),
            'id_reserva'             => $idReserva,
        ]);
        Flash::ok('Seguimiento registrado.');
        $this->redirigir('reserva_recibo', ['id' => $idReserva]);
    }

    public function recibo(): void
    {
        $idReserva = $this->entero('id', (int)($_SESSION['reserva_en_curso'] ?? 0));
        $reserva = $this->reservaPropia($idReserva);
        if ($reserva === null) {
            return;
        }
        $items = Database::all(
            'SELECT dr.*, s.nombre, s.duracion_minutos
             FROM detalle_reserva dr
             JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE dr.id_reserva = ?',
            [$idReserva]
        );
        $metodo = $reserva['id_metodoPago']
            ? (string)Database::value('SELECT nombre_metodoPago FROM metodo_pago WHERE id_metodoPago = ?', [$reserva['id_metodoPago']])
            : 'Por definir';
        $total = array_sum(array_column($items, 'subtotal'));

        $this->vista('reserva/recibo.php', [
            'reserva' => $reserva,
            'items'   => $items,
            'metodo'  => $metodo,
            'total'   => $total,
        ]);
    }

    public function misReservas(): void
    {
        if ($this->esPost()) {
            $id = $this->entero('id_reserva');
            $res = Database::one('SELECT id_usuario, estado FROM reserva WHERE id_reserva = ?', [$id]);
            if ($res !== null && $res['id_usuario'] === Auth::id() && in_array($res['estado'], ['pendiente', 'confirmada'], true)) {
                Database::run("UPDATE reserva SET estado = 'cancelada' WHERE id_reserva = ?", [$id]);
                Flash::ok('Reserva cancelada.');
            } else {
                Flash::error('No se pudo cancelar esa reserva.');
            }
            $this->redirigir('mis_reservas');
        }

        $reservas = Database::all(
            'SELECT r.*, s.nombre_sucursal, m.nombre_metodoPago,
                    (SELECT GROUP_CONCAT(sv.nombre SEPARATOR " · ")
                     FROM detalle_reserva dr JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                     WHERE dr.id_reserva = r.id_reserva) AS servicios,
                    (SELECT COALESCE(SUM(dr2.subtotal),0) FROM detalle_reserva dr2 WHERE dr2.id_reserva = r.id_reserva) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.id_usuario = ?
             ORDER BY r.fecha DESC, r.hora DESC',
            [Auth::id()]
        );
        $this->vista('reserva/mis_reservas.php', compact('reservas'));
    }

    public function recomendaciones(): void
    {
        $historial = Database::one(
            'SELECT h.* FROM historial_cliente h
             JOIN reserva r ON r.id_reserva = h.id_reserva
             WHERE r.id_usuario = ? AND h.objetivo_piel IS NOT NULL
             ORDER BY h.fecha_registro DESC LIMIT 1',
            [Auth::id()]
        );
        if ($historial === null) {
            Flash::error('Aún no tienes un historial registrado. Completa el formulario de tu primera reserva.');
            $this->redirigir('mis_reservas');
        }

        $objetivo = (string)$historial['objetivo_piel'];
        $alergias = mb_strtolower((string)($historial['alergias'] ?? ''));
        $recomendados = Database::all(
            'SELECT p.*, c.nombre_categoria
             FROM producto p
             LEFT JOIN categoria_producto c ON c.id_categoria = p.id_categoria
             WHERE p.cantidad > 0
             ORDER BY p.precio DESC
             LIMIT 6'
        );
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
        $reserva = Database::one('SELECT * FROM reserva WHERE id_reserva = ?', [$id]);
        if ($reserva === null || ($reserva['id_usuario'] !== Auth::id() && !Auth::esPersonal())) {
            http_response_code(403);
            (new Errores())->denegado();
            return null;
        }
        return $reserva;
    }

    private function horariosDisponibles(string $idSucursal, string $fecha): array
    {
        $diaIngles = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado'];
        $dia = $diaIngles[date('l', strtotime($fecha))] ?? '';
        $horario = Database::one(
            'SELECT hora_apertura, hora_cierre FROM horario_sucursal
             WHERE id_sucursal = ? AND dia_semana = ? AND es_activo = 1',
            [$idSucursal, $dia]
        );
        $ocupadas = [];
        if ($idSucursal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $ocupadas = array_column(
                Database::all('SELECT hora FROM reserva WHERE fecha = ? AND id_sucursal = ? AND estado <> "cancelada"', [$fecha, $idSucursal]),
                'hora'
            );
        }
        $horas = [];
        if ($horario === null) {
            return $horas;
        }
        $ini = (int)substr((string)$horario['hora_apertura'], 0, 2) * 60 + (int)substr((string)$horario['hora_apertura'], 3, 2);
        $fin = (int)substr((string)$horario['hora_cierre'], 0, 2) * 60 + (int)substr((string)$horario['hora_cierre'], 3, 2);
        for ($m = $ini; $m + 30 <= $fin; $m += 30) {
            $h = str_pad((string)intdiv($m, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT);
            $horas[] = ['hora' => $h, 'libre' => !in_array($h . ':00', $ocupadas, true) && strtotime($fecha . ' ' . $h) > time()];
        }
        return $horas;
    }

    private function extraerTipoPiel(string $objetivo): string
    {
        if (preg_match('/Piel:\s*([^·]+)/', $objetivo, $m)) {
            return trim($m[1]);
        }
        return '—';
    }
}
