<?php
declare(strict_types=1);

namespace App\Models;

final class Reserva extends Model
{
    public const ESTADOS = ['pendiente', 'confirmada', 'atendida', 'cancelada'];

    /** Estados del pago de una reserva. */
    public const ESTADOS_PAGO = [
        'sin_pago', 'pendiente_efectivo', 'por_verificar',
        'anticipo_confirmado', 'parcial', 'pagado', 'rechazado',
    ];

    public static function contarPendientes(): int
    {
        return (int)self::value("SELECT COUNT(*) FROM reserva WHERE estado = 'pendiente'");
    }

    public static function contarPendientesDeUsuario(string $idUsuario): int
    {
        return (int)self::value("SELECT COUNT(*) FROM reserva WHERE id_usuario = ? AND estado = 'pendiente'", [$idUsuario]);
    }

    public static function contarDelDia(string $fecha): int
    {
        return (int)self::value("SELECT COUNT(*) FROM reserva WHERE fecha = ? AND estado <> 'cancelada'", [$fecha]);
    }

    public static function estaOcupada(string $fecha, string $hora, string $idSucursal): bool
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM reserva
             WHERE fecha = ? AND hora = ? AND id_sucursal = ? AND estado <> 'cancelada'",
            [$fecha, $hora, $idSucursal]
        ) > 0;
    }

    public static function horasOcupadas(string $fecha, string $idSucursal): array
    {
        return array_column(
            self::all("SELECT hora FROM reserva WHERE fecha = ? AND id_sucursal = ? AND estado <> 'cancelada'", [$fecha, $idSucursal]),
            'hora'
        );
    }

    public static function porId(int $id): ?array
    {
        return self::one('SELECT * FROM reserva WHERE id_reserva = ?', [$id]);
    }

    public static function items(int $idReserva): array
    {
        return self::all(
            'SELECT dr.*, s.nombre, s.duracion_minutos
             FROM detalle_reserva dr
             JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE dr.id_reserva = ?',
            [$idReserva]
        );
    }

    public static function deUsuario(string $idUsuario): array
    {
        return self::all(
            'SELECT r.*, s.nombre_sucursal, m.nombre_metodoPago,
                    (SELECT GROUP_CONCAT(sv.nombre SEPARATOR " · ")
                     FROM detalle_reserva dr JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                     WHERE dr.id_reserva = r.id_reserva) AS servicios,
                    COALESCE(NULLIF(r.precio_total, 0),
                        (SELECT COALESCE(SUM(dr2.subtotal),0) FROM detalle_reserva dr2 WHERE dr2.id_reserva = r.id_reserva)) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.id_usuario = ?
             ORDER BY r.fecha DESC, r.hora DESC',
            [$idUsuario]
        );
    }

    public static function proximasDeUsuario(string $idUsuario, string $desde, int $limite = 3): array
    {
        return self::all(
            'SELECT r.*, s.nombre_sucursal,
                    GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                    COALESCE(NULLIF(r.precio_total, 0), COALESCE(SUM(dr.subtotal), 0)) AS total
             FROM reserva r
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
             WHERE r.id_usuario = ? AND r.fecha >= ? AND r.estado <> "cancelada"
             GROUP BY r.id_reserva
             ORDER BY r.fecha, r.hora
             LIMIT ' . $limite,
            [$idUsuario, $desde]
        );
    }

    public static function citasDelDia(string $fecha): array
    {
        return self::all(
            "SELECT r.id_reserva, r.hora, r.estado, r.estado_pago,
                    CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente,
                    COALESCE(GROUP_CONCAT(s.nombre SEPARATOR ', '), 'Sin servicio') AS servicio
             FROM reserva r
             LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE r.fecha = ?
             GROUP BY r.id_reserva
             ORDER BY r.hora",
            [$fecha]
        );
    }

    public static function resumenDelDia(string $fecha): array
    {
        return self::all(
            "SELECT r.id_reserva, r.hora, r.estado, r.estado_pago, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente
             FROM reserva r JOIN usuario u ON u.id_usuario = r.id_usuario
             WHERE r.fecha = ? AND r.estado <> 'cancelada'
             ORDER BY r.hora",
            [$fecha]
        );
    }

    public static function pendientesDetalle(): array
    {
        return self::all(
            "SELECT r.id_reserva, r.fecha, r.hora, CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente, u.telefono,
                    s.nombre_sucursal, m.nombre_metodoPago
             FROM reserva r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.estado = 'pendiente'
             ORDER BY r.fecha, r.hora"
        );
    }

    /** Pagos con comprobante enviado que el personal todavía debe verificar. */
    public static function pendientesPago(): array
    {
        return self::all(
            "SELECT r.id_reserva, r.fecha, r.hora, r.precio_total, r.anticipo, r.comprobante, r.codigo_pago,
                    CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente, u.telefono,
                    s.nombre_sucursal,
                    COALESCE(
                        (SELECT p.comprobante FROM pago_reserva p
                         WHERE p.id_reserva = r.id_reserva AND p.comprobante IS NOT NULL
                         ORDER BY p.id_pago DESC LIMIT 1),
                        r.comprobante
                    ) AS comprobante_actual,
                    COALESCE(
                        (SELECT p.codigo FROM pago_reserva p
                         WHERE p.id_reserva = r.id_reserva AND p.codigo IS NOT NULL
                         ORDER BY p.id_pago DESC LIMIT 1),
                        r.codigo_pago
                    ) AS codigo_actual
             FROM reserva r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             WHERE r.estado_pago = 'por_verificar'
             ORDER BY r.fecha, r.hora"
        );
    }

    public static function filtrar(string $fecha, string $estado, string $idSucursal, int $limite = 100): array
    {
        $sql = 'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, s.nombre_sucursal, m.nombre_metodoPago,
                       GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                       COALESCE(NULLIF(r.precio_total, 0), COALESCE(SUM(dr.subtotal), 0)) AS total,
                       (SELECT p.comprobante FROM pago_reserva p
                        WHERE p.id_reserva = r.id_reserva AND p.comprobante IS NOT NULL
                        ORDER BY p.id_pago DESC LIMIT 1) AS comprobante_pago,
                       (SELECT p.codigo FROM pago_reserva p
                        WHERE p.id_reserva = r.id_reserva AND p.codigo IS NOT NULL
                        ORDER BY p.id_pago DESC LIMIT 1) AS codigo_pago_actual
                FROM reserva r
                LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
                LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
                LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
                LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
                LEFT JOIN servicio sv ON sv.id_servicio = dr.id_servicio
                WHERE 1';
        $params = [];
        if ($fecha !== '') {
            $sql .= ' AND r.fecha = ?';
            $params[] = $fecha;
        }
        if ($estado !== '') {
            $sql .= ' AND r.estado = ?';
            $params[] = $estado;
        }
        if ($idSucursal !== '') {
            $sql .= ' AND r.id_sucursal = ?';
            $params[] = $idSucursal;
        }
        $sql .= ' GROUP BY r.id_reserva ORDER BY r.fecha DESC, r.hora DESC LIMIT ' . $limite;
        return self::all($sql, $params);
    }

    /** Reserva con datos del cliente, sucursal y método de pago. */
    public static function conCliente(int $idReserva): ?array
    {
        return self::one(
            'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, u.fecha_nacimiento,
                    s.nombre_sucursal, m.nombre_metodoPago,
                    COALESCE(NULLIF(r.precio_total, 0),
                        (SELECT COALESCE(SUM(dr.subtotal),0) FROM detalle_reserva dr WHERE dr.id_reserva = r.id_reserva)) AS total
             FROM reserva r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN sucursal s ON s.id_sucursal = r.id_sucursal
             LEFT JOIN metodo_pago m ON m.id_metodoPago = r.id_metodoPago
             WHERE r.id_reserva = ?',
            [$idReserva]
        );
    }

    /**
     * Crea la reserva en una sola transacción. Bloquea el horario para evitar
     * que dos clientes reserven el mismo cupo. Los importes salen del servicio
     * guardado en MySQL, nunca de valores enviados por el navegador.
     */
    public static function crear(string $idUsuario, string $idSucursal, string $fecha, string $hora, array $servicio, array $opciones = []): int
    {
        $total = round((float)($servicio['precio'] ?? 0), 2);
        if ($total < 0) {
            throw new \DomainException('El precio del servicio no es válido.');
        }

        $tipo = (string)($opciones['tipo_tratamiento'] ?? Servicio::tipoTratamiento($servicio));
        $zona = $opciones['zona_tratamiento'] ?? null;
        $respuestas = $opciones['respuestas'] ?? [];
        $idMetodo = $opciones['id_metodoPago'] ?? null;
        $esEfectivo = $idMetodo === 'MP01';
        $anticipo = $esEfectivo ? 0.0 : min((float)ANTICIPO_MONTO, $total);
        $anticipo = max(0.0, round($anticipo, 2));
        $estadoPago = $esEfectivo ? 'pendiente_efectivo' : 'sin_pago';
        $saldo = round($total - $anticipo, 2);
        $codigo = self::generarCodigoPago();
        $horaNormalizada = substr(trim($hora), 0, 5) . ':00';
        $claveBloqueo = 'tatiana.reserva.' . md5($idSucursal . '|' . $fecha . '|' . $horaNormalizada);

        return (int)self::transaccion(function () use (
            $idUsuario, $idSucursal, $fecha, $horaNormalizada, $servicio,
            $tipo, $zona, $respuestas, $idMetodo, $esEfectivo, $total, $anticipo,
            $estadoPago, $saldo, $codigo, $claveBloqueo
        ) {
            $lock = self::value('SELECT GET_LOCK(?, 10)', [$claveBloqueo]);
            if ((string)$lock !== '1') {
                throw new \DomainException('No se pudo apartar el horario. Intenta nuevamente.');
            }
            try {
                if (self::estaOcupada($fecha, $horaNormalizada, $idSucursal)) {
                    throw new \DomainException('Ese horario acaba de ocuparse. Elige otra hora, por favor.');
                }

                $idReserva = Ids::insertarConEntero('reserva', 'id_reserva', [
                    'fecha'            => $fecha,
                    'hora'             => $horaNormalizada,
                    'tipo_tratamiento' => $tipo,
                    'zona_tratamiento' => $zona,
                    'respuestas'       => json_encode($respuestas, JSON_UNESCAPED_UNICODE),
                    'precio_total'     => $total,
                    'anticipo'         => $anticipo,
                    'monto_pagado'     => 0.00,
                    'saldo_pendiente'  => $saldo,
                    'estado_pago'      => $estadoPago,
                    'codigo_pago'      => $codigo,
                    'estado'           => 'pendiente',
                    'id_sucursal'      => $idSucursal,
                    'id_metodoPago'    => $idMetodo,
                    'id_usuario'       => $idUsuario,
                ]);

                Ids::insertarConEntero('detalle_reserva', 'id_detalle', [
                    'cantidad'        => 1,
                    'precio_unitario' => $total,
                    'subtotal'        => $total,
                    'id_servicio'     => (string)$servicio['id_servicio'],
                    'id_reserva'      => $idReserva,
                ]);

                if ($esEfectivo) {
                    // Pago completo en sucursal: queda pendiente hasta que el
                    // personal registre el dinero recibido.
                    Ids::insertarConEntero('pago_reserva', 'id_pago', [
                        'id_reserva'      => $idReserva,
                        'tipo'            => 'saldo',
                        'monto'           => $total,
                        'id_metodoPago'   => 'MP01',
                        'estado'          => 'pendiente_efectivo',
                        'codigo'          => $codigo,
                        'fecha_registro'  => date('Y-m-d H:i:s'),
                    ]);
                }

                return $idReserva;
            } finally {
                self::value('SELECT RELEASE_LOCK(?)', [$claveBloqueo]);
            }
        });
    }

    public static function asignarMetodoPago(int $idReserva, ?string $idMetodo): void
    {
        self::run('UPDATE reserva SET id_metodoPago = ? WHERE id_reserva = ?', [$idMetodo, $idReserva]);
    }

    /**
     * Registra el comprobante enviado por el cliente. El monto del pago se toma
     * del anticipo guardado en la reserva; no se acepta un monto del formulario.
     */
    public static function registrarComprobante(int $idReserva, string $rutaComprobante, string $idMetodo): string
    {
        $reserva = self::porId($idReserva);
        if ($reserva === null) {
            throw new \DomainException('La reserva no existe.');
        }
        if (($reserva['estado_pago'] ?? '') === 'pagado' || ($reserva['estado'] ?? '') === 'cancelada') {
            throw new \DomainException('Esta reserva ya no admite un nuevo comprobante.');
        }

        $total = (float)$reserva['precio_total'];
        $anticipo = (float)$reserva['anticipo'];
        if ($anticipo <= 0) {
            $anticipo = min((float)ANTICIPO_MONTO, $total);
        }
        $codigo = (string)($reserva['codigo_pago'] ?? '') !== ''
            ? (string)$reserva['codigo_pago']
            : self::generarCodigoPago();

        return (string)self::transaccion(function () use ($idReserva, $rutaComprobante, $idMetodo, $anticipo, $codigo) {
            self::run(
                "UPDATE reserva
                 SET id_metodoPago = ?, comprobante = ?, estado_pago = 'por_verificar', codigo_pago = ?
                 WHERE id_reserva = ?",
                [$idMetodo, $rutaComprobante, $codigo, $idReserva]
            );

            $pendiente = self::one(
                "SELECT id_pago FROM pago_reserva
                 WHERE id_reserva = ? AND estado = 'por_verificar'
                 ORDER BY id_pago DESC LIMIT 1",
                [$idReserva]
            );
            if ($pendiente !== null) {
                self::run(
                    'UPDATE pago_reserva SET monto = ?, id_metodoPago = ?, comprobante = ?, codigo = ?, fecha_registro = ? WHERE id_pago = ?',
                    [$anticipo, $idMetodo, $rutaComprobante, $codigo, date('Y-m-d H:i:s'), $pendiente['id_pago']]
                );
            } else {
                Ids::insertarConEntero('pago_reserva', 'id_pago', [
                    'id_reserva'     => $idReserva,
                    'tipo'           => 'anticipo',
                    'monto'          => $anticipo,
                    'id_metodoPago'  => $idMetodo,
                    'estado'         => 'por_verificar',
                    'comprobante'    => $rutaComprobante,
                    'codigo'         => $codigo,
                    'fecha_registro' => date('Y-m-d H:i:s'),
                ]);
            }

            return $codigo;
        });
    }

    /**
     * El personal confirma el anticipo. No suma dos veces el anticipo: usa el
     * mayor monto ya registrado y recalcula el saldo desde el total real.
     */
    public static function confirmarPago(int $idReserva, string $verificadoPor = ''): bool
    {
        $reserva = self::porId($idReserva);
        if ($reserva === null) {
            return false;
        }
        // Solo se confirma un comprobante QR/transferencia pendiente de revisión.
        // El efectivo se registra con registrarPagoEfectivo().
        if (($reserva['estado_pago'] ?? '') !== 'por_verificar') {
            return false;
        }

        $total = self::totalDe($reserva);
        $anticipo = (float)$reserva['anticipo'];
        if ($anticipo <= 0) {
            $anticipo = min((float)ANTICIPO_MONTO, $total);
        }
        $pagado = max((float)$reserva['monto_pagado'], $anticipo);
        $pagado = min($pagado, $total);
        $saldo = max(0.0, round($total - $pagado, 2));
        $estadoPago = $saldo <= 0.0001 ? 'pagado' : 'anticipo_confirmado';
        $nuevoEstado = ($reserva['estado'] ?? '') === 'pendiente' ? 'confirmada' : (string)$reserva['estado'];

        return (bool)self::transaccion(function () use ($idReserva, $pagado, $saldo, $estadoPago, $nuevoEstado, $verificadoPor, $anticipo) {
            self::run(
                'UPDATE reserva
                 SET monto_pagado = ?, saldo_pendiente = ?, estado_pago = ?, fecha_pago = ?, estado = ?
                 WHERE id_reserva = ?',
                [$pagado, $saldo, $estadoPago, date('Y-m-d H:i:s'), $nuevoEstado, $idReserva]
            );

            $pendientes = self::all(
                "SELECT id_pago FROM pago_reserva WHERE id_reserva = ? AND estado = 'por_verificar'",
                [$idReserva]
            );
            foreach ($pendientes as $p) {
                self::run(
                    "UPDATE pago_reserva
                     SET estado = 'confirmado', monto = ?, fecha_verificacion = ?, verificado_por = ?
                     WHERE id_pago = ?",
                    [$pagado > 0 ? $anticipo : 0, date('Y-m-d H:i:s'), $verificadoPor, $p['id_pago']]
                );
            }
            if ($pendientes === []) {
                Ids::insertarConEntero('pago_reserva', 'id_pago', [
                    'id_reserva'          => $idReserva,
                    'tipo'                => 'anticipo',
                    'monto'               => $anticipo,
                    'estado'              => 'confirmado',
                    'fecha_registro'      => date('Y-m-d H:i:s'),
                    'fecha_verificacion'  => date('Y-m-d H:i:s'),
                    'verificado_por'      => $verificadoPor,
                ]);
            }
            return true;
        });
    }

    /** El personal rechaza el comprobante; el cliente puede volver a enviarlo. */
    public static function rechazarPago(int $idReserva, string $motivo, string $verificadoPor = ''): bool
    {
        $reserva = self::porId($idReserva);
        if ($reserva === null || ($reserva['estado_pago'] ?? '') !== 'por_verificar') {
            return false;
        }
        self::run("UPDATE reserva SET estado_pago = 'rechazado' WHERE id_reserva = ?", [$idReserva]);
        self::run(
            "UPDATE pago_reserva
             SET estado = 'rechazado', observacion = ?, fecha_verificacion = ?, verificado_por = ?
             WHERE id_reserva = ? AND estado = 'por_verificar'",
            [mb_substr($motivo, 0, 255), date('Y-m-d H:i:s'), $verificadoPor, $idReserva]
        );
        return true;
    }

    /** Registra el pago completo en efectivo recibido en sucursal. */
    public static function registrarPagoEfectivo(int $idReserva, string $verificadoPor = ''): bool
    {
        $reserva = self::porId($idReserva);
        if ($reserva === null || ($reserva['estado_pago'] ?? '') === 'pagado') {
            return false;
        }
        $total = self::totalDe($reserva);
        $pagadoPrevio = (float)($reserva['monto_pagado'] ?? 0);
        $restante = max(0.0, round($total - $pagadoPrevio, 2));
        $nuevoEstado = ($reserva['estado'] ?? '') === 'pendiente' ? 'confirmada' : (string)$reserva['estado'];

        return (bool)self::transaccion(function () use ($idReserva, $total, $restante, $nuevoEstado, $verificadoPor) {
            self::run(
                "UPDATE reserva
                 SET monto_pagado = ?, saldo_pendiente = 0, estado_pago = 'pagado',
                     fecha_pago = ?, estado = ?, id_metodoPago = COALESCE(id_metodoPago, 'MP01')
                 WHERE id_reserva = ?",
                [$total, date('Y-m-d H:i:s'), $nuevoEstado, $idReserva]
            );

            $pendiente = self::one(
                "SELECT id_pago FROM pago_reserva
                 WHERE id_reserva = ? AND estado IN ('pendiente_efectivo', 'sin_pago')
                 ORDER BY id_pago DESC LIMIT 1",
                [$idReserva]
            );
            if ($pendiente !== null) {
                // Cobro en efectivo de una reserva que aún no tenía pagos.
                self::run(
                    "UPDATE pago_reserva
                     SET monto = ?, id_metodoPago = 'MP01', estado = 'confirmado',
                         fecha_verificacion = ?, verificado_por = ?
                     WHERE id_pago = ?",
                    [$total, date('Y-m-d H:i:s'), $verificadoPor, $pendiente['id_pago']]
                );
            } elseif ($restante > 0) {
                // Saldo cobrado después de un anticipo ya confirmado.
                Ids::insertarConEntero('pago_reserva', 'id_pago', [
                    'id_reserva'         => $idReserva,
                    'tipo'               => 'saldo',
                    'monto'              => $restante,
                    'id_metodoPago'      => 'MP01',
                    'estado'             => 'confirmado',
                    'fecha_registro'     => date('Y-m-d H:i:s'),
                    'fecha_verificacion' => date('Y-m-d H:i:s'),
                    'verificado_por'     => $verificadoPor,
                ]);
            }
            return true;
        });
    }

    public static function cambiarEstado(int $idReserva, string $estado): bool
    {
        if (!in_array($estado, self::ESTADOS, true) || self::porId($idReserva) === null) {
            return false;
        }
        self::run('UPDATE reserva SET estado = ? WHERE id_reserva = ?', [$estado, $idReserva]);
        return true;
    }

    public static function cancelarDeUsuario(int $idReserva, string $idUsuario): bool
    {
        $res = self::one(
            'SELECT id_usuario, estado, estado_pago, monto_pagado FROM reserva WHERE id_reserva = ?',
            [$idReserva]
        );
        if ($res === null || $res['id_usuario'] !== $idUsuario) {
            return false;
        }
        if (!in_array($res['estado'], ['pendiente', 'confirmada'], true)) {
            return false;
        }
        if ((float)($res['monto_pagado'] ?? 0) > 0
            || in_array((string)($res['estado_pago'] ?? ''), ['por_verificar', 'anticipo_confirmado', 'parcial', 'pagado'], true)) {
            // Con dinero ya registrado, la cancelación la gestiona el personal.
            return false;
        }
        self::run("UPDATE reserva SET estado = 'cancelada' WHERE id_reserva = ?", [$idReserva]);
        return true;
    }

    /** Importe total: columna nueva o, en reservas antiguas, suma del detalle. */
    public static function totalDe(array $reserva): float
    {
        $total = (float)($reserva['precio_total'] ?? 0);
        if ($total > 0) {
            return round($total, 2);
        }
        $id = (int)($reserva['id_reserva'] ?? 0);
        if ($id > 0) {
            $total = (float)self::value(
                'SELECT COALESCE(SUM(subtotal),0) FROM detalle_reserva WHERE id_reserva = ?',
                [$id]
            );
        }
        return round($total, 2);
    }

    /** Resumen de pago listo para las vistas. */
    public static function resumenPago(array $reserva): array
    {
        $total = self::totalDe($reserva);
        $anticipo = (float)($reserva['anticipo'] ?? 0);
        if ($anticipo <= 0 && ($reserva['estado_pago'] ?? '') !== 'pendiente_efectivo') {
            $anticipo = min((float)ANTICIPO_MONTO, $total);
        }
        $pagado = (float)($reserva['monto_pagado'] ?? 0);
        $saldo = array_key_exists('saldo_pendiente', $reserva)
            ? (float)$reserva['saldo_pendiente']
            : max(0.0, $total - $pagado);

        return [
            'total'    => round($total, 2),
            'anticipo' => round($anticipo, 2),
            'pagado'   => round($pagado, 2),
            'saldo'    => round($saldo, 2),
            'estado'   => (string)($reserva['estado_pago'] ?? 'sin_pago'),
        ];
    }

    private static function generarCodigoPago(): string
    {
        return 'PAG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
    }
}
