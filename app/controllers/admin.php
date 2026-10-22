<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Ids;
use App\Core\Upload;
use App\Core\Validate;

final class Admin extends Controller
{
    private const POR_PAGINA = 10;

    public function dashboard(): void
    {
        $hoy = date('Y-m-d');
        $kpi = [
            'clientes'    => (int)Database::value("SELECT COUNT(*) FROM usuario WHERE id_rol = 'ROL03'"),
            'citas'       => (int)Database::value("SELECT COUNT(*) FROM reserva WHERE fecha = ? AND estado <> 'cancelada'", [$hoy]),
            'ventas'      => (float)Database::value('SELECT COALESCE(SUM(total_pagar),0) FROM compra WHERE fecha_compra = ?', [$hoy]),
            'stock_bajo'  => (int)Database::value('SELECT COUNT(*) FROM producto WHERE cantidad <= 3'),
        ];

        $citasHoy = Database::all(
            "SELECT r.id_reserva, r.hora, r.estado,
                    CONCAT(u.nombre, ' ', u.ap_paterno) AS cliente,
                    COALESCE(GROUP_CONCAT(s.nombre SEPARATOR ', '), 'Sin servicio') AS servicio
             FROM reserva r
             LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
             LEFT JOIN detalle_reserva dr ON dr.id_reserva = r.id_reserva
             LEFT JOIN servicio s ON s.id_servicio = dr.id_servicio
             WHERE r.fecha = ?
             GROUP BY r.id_reserva
             ORDER BY r.hora",
            [$hoy]
        );

        $dias = [];
        $montos = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $dias[] = date('D', strtotime($fecha));
            $montos[] = (float)Database::value('SELECT COALESCE(SUM(total_pagar),0) FROM compra WHERE fecha_compra = ?', [$fecha]);
        }

        $this->vista('admin/dashboard.php', [
            'kpi'           => $kpi,
            'citas_hoy'     => $citasHoy,
            'ventas_semana' => $montos,
            'dias'          => $dias,
        ]);
    }

    /* ============================ CLIENTES ============================= */
    public function clientes(): void
    {
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $this->guardarCliente();
        }

        $sql = "SELECT u.*, c.correo,
                       (SELECT COUNT(*) FROM reserva r WHERE r.id_usuario = u.id_usuario) AS reservas
                FROM usuario u
                LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
                WHERE u.id_rol = 'ROL03'";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (u.nombre LIKE ? OR u.ap_paterno LIKE ? OR u.ap_materno LIKE ? OR u.telefono LIKE ? OR c.correo LIKE ?)";
            for ($i = 0; $i < 5; $i++) {
                $params[] = '%' . $q . '%';
            }
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM (' . $sql . ') t', $params);
        $sql .= ' ORDER BY u.nombre, u.ap_paterno LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA);
        $clientes = Database::all($sql, $params);

        $editar = null;
        if ($this->texto('editar') !== '') {
            $editar = Database::one(
                'SELECT u.*, c.correo FROM usuario u
                 LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
                 WHERE u.id_usuario = ? AND u.id_rol = "ROL03"',
                [$this->texto('editar')]
            );
        }

        $this->vista('admin/clientes.php', compact('clientes', 'q', 'pagina', 'total', 'editar'));
    }

    private function guardarCliente(): void
    {
        $idEditar = $this->texto('id_usuario');
        $v = Validate::make()
            ->texto('nombre', 'El nombre', 2, 50)
            ->texto('ap_paterno', 'El apellido paterno', 2, 50)
            ->telefono('telefono');
        if ($this->texto('telefono') === '') {
            $v->fallo('telefono', 'El teléfono es obligatorio.');
        }
        if ($idEditar === '') {
            $v->correo('correo')
              ->unico('cuenta_usuario', 'correo', 'correo', 'correo electrónico');
            if (strlen((string)($_POST['password'] ?? '')) < 8) {
                $v->fallo('password', 'La contraseña debe tener al menos 8 caracteres.');
            }
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_clientes', ['q' => $this->texto('q')]);
        }

        try {
            Database::begin();
            $datos = [
                'nombre'     => trim((string)$_POST['nombre']),
                'ap_paterno' => trim((string)$_POST['ap_paterno']),
                'ap_materno' => trim((string)($_POST['ap_materno'] ?? '')),
                'telefono'   => trim((string)$_POST['telefono']),
                'fecha_nacimiento' => ($_POST['fecha_nacimiento'] ?? '') !== '' ? $_POST['fecha_nacimiento'] : null,
            ];
            if ($idEditar !== '') {
                Database::run(
                    'UPDATE usuario SET nombre = ?, ap_paterno = ?, ap_materno = ?, telefono = ?, fecha_nacimiento = ? WHERE id_usuario = ? AND id_rol = "ROL03"',
                    [...array_values($datos), $idEditar]
                );
                Flash::ok('Cliente actualizado.');
            } else {
                $correo = mb_strtolower(trim((string)$_POST['correo']));
                $id = Ids::insertarConCodigo('usuario', 'id_usuario', 'CLI', 4, $datos + [
                    'id_rol'      => 'ROL03',
                    'id_sucursal' => null,
                ]);
                Ids::insertarConEntero('cuenta_usuario', 'id_cuentaUsuario', [
                    'correo'     => $correo,
                    'password'   => Auth::hashPassword((string)$_POST['password'], $correo),
                    'id_rol'     => 'ROL03',
                    'id_usuario' => $id,
                ]);
                Flash::ok('Cliente registrado: ' . $id);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[ADMIN-CLIENTES] ' . $e->getMessage());
            Flash::error('No se pudo guardar el cliente.');
        }
        $this->redirigir('admin_clientes', ['q' => $this->texto('q')]);
    }

    /* ============================= CITAS =============================== */
    public function citas(): void
    {
        $fecha = $this->texto('fecha');
        $estado = $this->texto('estado');
        $idSucursal = $this->texto('sucursal');
        $ficha = $this->entero('ficha');

        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'estado') {
                $id = $this->entero('id_reserva');
                $nuevo = $this->texto('nuevo_estado');
                $permitidos = ['pendiente', 'confirmada', 'atendida', 'cancelada'];
                if (in_array($nuevo, $permitidos, true) && Database::one('SELECT id_reserva FROM reserva WHERE id_reserva = ?', [$id]) !== null) {
                    Database::run('UPDATE reserva SET estado = ? WHERE id_reserva = ?', [$nuevo, $id]);
                    Flash::ok('Reserva #' . $id . ' marcada como ' . $nuevo . '.');
                } else {
                    Flash::error('No se pudo actualizar la reserva.');
                }
            } elseif ($accion === 'observacion') {
                $idReserva = $this->entero('id_reserva');
                $obs = trim((string)($_POST['observaciones'] ?? ''));
                $reserva = Database::one('SELECT id_usuario FROM reserva WHERE id_reserva = ?', [$idReserva]);
                if ($reserva !== null) {
                    $hist = Database::one('SELECT id_historial FROM historial_cliente WHERE id_reserva = ?', [$idReserva]);
                    if ($hist !== null) {
                        Database::run('UPDATE historial_cliente SET observaciones_sesion = ? WHERE id_reserva = ?', [$obs, $idReserva]);
                    } else {
                        Ids::insertarConEntero('historial_cliente', 'id_historial', [
                            'alergias'               => null,
                            'objetivo_piel'          => null,
                            'abierto_recomendaciones' => 0,
                            'rutina_actual'          => null,
                            'observaciones_sesion'   => $obs,
                            'fecha_registro'         => date('Y-m-d H:i:s'),
                            'id_reserva'             => $idReserva,
                        ]);
                    }
                    Flash::ok('Observación guardada en el historial.');
                }
            }
            $this->redirigir('admin_citas', array_filter([
                'fecha' => $fecha, 'estado' => $estado, 'sucursal' => $idSucursal, 'ficha' => $ficha ?: null,
            ]));
        }

        $sql = 'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, s.nombre_sucursal, m.nombre_metodoPago,
                       GROUP_CONCAT(sv.nombre SEPARATOR ", ") AS servicios,
                       COALESCE(SUM(dr.subtotal), 0) AS total
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
        $sql .= ' GROUP BY r.id_reserva ORDER BY r.fecha DESC, r.hora DESC LIMIT 100';
        $reservas = Database::all($sql, $params);

        $sucursales = Database::all('SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal');

        $fichaCliente = null;
        if ($ficha > 0) {
            $reservaFicha = Database::one(
                'SELECT r.*, u.nombre, u.ap_paterno, u.ap_materno, u.telefono, u.fecha_nacimiento
                 FROM reserva r JOIN usuario u ON u.id_usuario = r.id_usuario
                 WHERE r.id_reserva = ?',
                [$ficha]
            );
            if ($reservaFicha !== null) {
                $historiales = Database::all(
                    'SELECT h.*, r.fecha FROM historial_cliente h
                     JOIN reserva r ON r.id_reserva = h.id_reserva
                     WHERE r.id_usuario = ? ORDER BY h.fecha_registro DESC',
                    [$reservaFicha['id_usuario']]
                );
                $compras = Database::all(
                    'SELECT * FROM compra WHERE id_usuario = ? ORDER BY fecha_compra DESC LIMIT 5',
                    [$reservaFicha['id_usuario']]
                );
                $fichaCliente = [
                    'cliente'    => $reservaFicha,
                    'historial'  => $historiales,
                    'compras'    => $compras,
                ];
            }
        }

        $this->vista('admin/citas.php', compact('reservas', 'sucursales', 'fecha', 'estado', 'idSucursal', 'fichaCliente'));
    }

    /* =========================== INVENTARIO ============================ */
    public function inventario(): void
    {
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $this->guardarProducto();
        }

        $sql = 'SELECT p.*, c.nombre_categoria
                FROM producto p
                LEFT JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                WHERE 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (p.nombre_producto LIKE ? OR p.descripcion LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM (' . $sql . ') t', $params);
        $sql .= ' ORDER BY p.nombre_producto LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA);
        $productos = Database::all($sql, $params);

        $categorias = Database::all('SELECT * FROM categoria_producto ORDER BY nombre_categoria');

        $this->vista('admin/inventario.php', compact('productos', 'categorias', 'q', 'pagina', 'total'));
    }

    private function guardarProducto(): void
    {
        $idEditar = $this->texto('id_producto');
        $v = Validate::make()
            ->texto('nombre_producto', 'El nombre', 2, 80)
            ->numero('precio', 'El precio');
        if ($this->texto('descripcion') !== '' && mb_strlen($this->texto('descripcion')) > 200) {
            $v->fallo('descripcion', 'La descripción no puede superar 200 caracteres.');
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_inventario', ['q' => $this->texto('q')]);
        }

        $imagen = null;
        if (!empty($_FILES['imagen']['name'] ?? '')) {
            $errorUp = null;
            $prefijo = 'uploads/productos/prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $imagen = Upload::imagen($_FILES['imagen'], $prefijo, $errorUp);
            if ($imagen === null) {
                Flash::error($errorUp ?? 'No se pudo subir la imagen.');
                $this->redirigir('admin_inventario');
            }
        }

        try {
            Database::begin();
            $datos = [
                'nombre_producto' => trim((string)$_POST['nombre_producto']),
                'descripcion'     => trim((string)($_POST['descripcion'] ?? '')),
                'precio'          => (float)$_POST['precio'],
                'cantidad'        => max(0, $this->entero('cantidad')),
                'id_categoria'    => $this->texto('id_categoria') !== '' ? $this->texto('id_categoria') : null,
            ];
            if ($imagen !== null) {
                $datos['imagen_url'] = $imagen;
            }
            if ($idEditar !== '') {
                $campos = $imagen !== null ? $datos : array_diff_key($datos, ['imagen_url' => 1]);
                $sets = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($campos)));
                Database::run("UPDATE producto SET $sets WHERE id_producto = ?", [...array_values($campos), $idEditar]);
                Flash::ok('Producto actualizado.');
            } else {
                $id = Ids::insertarConCodigo('producto', 'id_producto', 'PROD', 2, $datos);
                Flash::ok('Producto registrado: ' . $id);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[ADMIN-INV] ' . $e->getMessage());
            Flash::error('No se pudo guardar el producto.');
        }
        $this->redirigir('admin_inventario', ['q' => $this->texto('q')]);
    }

    /* ============================= VENTAS ============================== */
    public function ventas(): void
    {
        $fecha = $this->texto('fecha');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $this->registrarVenta();
        }

        $sql = 'SELECT c.*, CONCAT(u.nombre, " ", u.ap_paterno) AS cliente, m.nombre_metodoPago,
                       (SELECT COUNT(*) FROM detalle_compra d WHERE d.id_compra = c.id_compra) AS items
                FROM compra c
                LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
                LEFT JOIN metodo_pago m ON m.id_metodoPago = c.id_metodoPago
                WHERE 1';
        $params = [];
        if ($fecha !== '') {
            $sql .= ' AND c.fecha_compra = ?';
            $params[] = $fecha;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM (' . $sql . ') t', $params);
        $sql .= ' ORDER BY c.fecha_compra DESC, c.id_compra DESC LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA);
        $ventas = Database::all($sql, $params);

        $totales = Database::one(
            'SELECT COUNT(*) AS compras, COALESCE(SUM(total_pagar),0) AS monto FROM compra' . ($fecha !== '' ? ' WHERE fecha_compra = ?' : ''),
            $fecha !== '' ? [$fecha] : []
        );

        $productos = Database::all('SELECT id_producto, nombre_producto, precio, cantidad FROM producto ORDER BY nombre_producto');
        $clientes = Database::all("SELECT id_usuario, nombre, ap_paterno FROM usuario WHERE id_rol = 'ROL03' ORDER BY nombre");
        $metodos = Database::all("SELECT * FROM metodo_pago WHERE estado = 'activo' OR estado IS NULL ORDER BY nombre_metodoPago");

        $this->vista('admin/ventas.php', compact('ventas', 'productos', 'clientes', 'metodos', 'fecha', 'pagina', 'total', 'totales'));
    }

    private function registrarVenta(): void
    {
        $idCliente = $this->texto('id_usuario');
        $idProducto = $this->texto('id_producto');
        $cantidad = max(1, $this->entero('cantidad', 1));
        $idMetodo = $this->texto('id_metodoPago');

        $producto = Database::one('SELECT precio, cantidad AS stock, nombre_producto FROM producto WHERE id_producto = ?', [$idProducto]);
        if ($producto === null) {
            Flash::error('Producto no válido.');
            $this->redirigir('admin_ventas');
        }
        if ((int)$producto['stock'] < $cantidad) {
            Flash::error('Solo hay ' . (int)$producto['stock'] . ' unidades de ' . $producto['nombre_producto'] . '.');
            $this->redirigir('admin_ventas');
        }
        if ($idCliente === '') {
            Flash::error('Selecciona el cliente (regístralo primero en Clientes si no existe).');
            $this->redirigir('admin_ventas');
        }

        try {
            Database::begin();
            $subtotal = (float)$producto['precio'] * $cantidad;
            $nro = 'C-' . str_pad((string)((int)Database::value('SELECT COALESCE(MAX(id_compra),0) FROM compra') + 1), 6, '0', STR_PAD_LEFT);
            $idCompra = Ids::insertarConEntero('compra', 'id_compra', [
                'fecha_compra'  => date('Y-m-d'),
                'nro_compra'    => $nro,
                'total_pagar'   => $subtotal,
                'id_usuario'    => $idCliente,
                'id_metodoPago' => $idMetodo !== '' ? $idMetodo : null,
            ]);
            Ids::insertarConEntero('detalle_compra', 'id_detalle', [
                'cantidad'        => $cantidad,
                'precio_unitario' => $producto['precio'],
                'subtotal'        => $subtotal,
                'id_compra'       => $idCompra,
                'id_producto'     => $idProducto,
            ]);
            Database::run('UPDATE producto SET cantidad = cantidad - ? WHERE id_producto = ?', [$cantidad, $idProducto]);
            Database::commit();
            Flash::ok('Venta registrada: ' . $nro . ' por ' . bs($subtotal) . '.');
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[ADMIN-VENTAS] ' . $e->getMessage());
            Flash::error('No se pudo registrar la venta.');
        }
        $this->redirigir('admin_ventas', ['fecha' => date('Y-m-d')]);
    }

    /* ============================ SERVICIOS ============================ */
    public function servicios(): void
    {
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'categoria') {
                $this->guardarCategoriaServicio();
            } else {
                $this->guardarServicio();
            }
        }

        $sql = 'SELECT s.*, cs.nombre_cservicio AS categoria
                FROM servicio s
                LEFT JOIN categoria_servicio cs ON cs.id_categoriaS = s.id_categoriaS
                WHERE 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (s.nombre LIKE ? OR s.descripcion LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM (' . $sql . ') t', $params);
        $sql .= ' ORDER BY s.nombre LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA);
        $servicios = Database::all($sql, $params);

        $categorias = Database::all('SELECT * FROM categoria_servicio ORDER BY nombre_cservicio');

        $this->vista('admin/servicios.php', compact('servicios', 'categorias', 'q', 'pagina', 'total'));
    }

    private function guardarServicio(): void
    {
        $idEditar = $this->texto('id_servicio');
        $v = Validate::make()
            ->texto('nombre', 'El nombre', 2, 80)
            ->numero('precio', 'El precio')
            ->entero('duracion_minutos', 'La duración');
        if ($this->entero('duracion_minutos', 0) <= 0) {
            $v->fallo('duracion_minutos', 'La duración debe ser mayor a 0 minutos.');
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_servicios', ['q' => $this->texto('q')]);
        }

        try {
            Database::begin();
            $datos = [
                'nombre'            => trim((string)$_POST['nombre']),
                'descripcion'       => trim((string)($_POST['descripcion'] ?? '')),
                'precio'            => (float)$_POST['precio'],
                'duracion_minutos'  => $this->entero('duracion_minutos'),
                'id_categoriaS'     => $this->texto('id_categoriaS') !== '' ? $this->texto('id_categoriaS') : null,
            ];
            if ($idEditar !== '') {
                $sets = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($datos)));
                Database::run("UPDATE servicio SET $sets WHERE id_servicio = ?", [...array_values($datos), $idEditar]);
                Flash::ok('Servicio actualizado.');
            } else {
                $id = Ids::insertarConCodigo('servicio', 'id_servicio', 'SERV', 2, $datos);
                Flash::ok('Servicio registrado: ' . $id);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[ADMIN-SERV] ' . $e->getMessage());
            Flash::error('No se pudo guardar el servicio.');
        }
        $this->redirigir('admin_servicios', ['q' => $this->texto('q')]);
    }

    private function guardarCategoriaServicio(): void
    {
        $nombre = $this->texto('nombre_cservicio');
        if (mb_strlen($nombre) < 2) {
            Flash::error('El nombre de la categoría debe tener al menos 2 caracteres.');
        } elseif (Database::one('SELECT id_categoriaS FROM categoria_servicio WHERE nombre_cservicio = ?', [$nombre]) !== null) {
            Flash::error('Ya existe una categoría con ese nombre.');
        } else {
            try {
                Ids::insertarConCodigo('categoria_servicio', 'id_categoriaS', 'CS', 2, ['nombre_cservicio' => $nombre]);
                Flash::ok('Categoría creada: ' . $nombre);
            } catch (\Throwable $e) {
                error_log('[ADMIN-CATS] ' . $e->getMessage());
                Flash::error('No se pudo crear la categoría.');
            }
        }
        $this->redirigir('admin_servicios');
    }
}
