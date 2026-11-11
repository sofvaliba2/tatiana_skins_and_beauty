<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Upload;
use App\Core\Validate;
use App\Models\Compra;
use App\Models\Historial;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Usuario;

final class AdminController extends Controller
{
    private const POR_PAGINA = 10;

    public function dashboard(): void
    {
        $hoy = date('Y-m-d');
        $kpi = [
            'clientes'   => Usuario::contarClientes(),
            'citas'      => Reserva::contarDelDia($hoy),
            'ventas'     => Compra::totalDelDia($hoy),
            'stock_bajo' => Producto::contarStockBajo(),
        ];
        $semana = Compra::ultimosDias(7);

        $this->vista('admin/dashboard.php', [
            'kpi'           => $kpi,
            'citas_hoy'     => Reserva::citasDelDia($hoy),
            'ventas_semana' => $semana['montos'],
            'dias'          => $semana['dias'],
        ]);
    }

    public function clientes(): void
    {
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $this->guardarCliente();
        }

        ['filas' => $clientes, 'total' => $total] = Usuario::buscarClientes($q, $pagina, self::POR_PAGINA);
        $editar = $this->texto('editar') !== '' ? Usuario::clienteConCorreo($this->texto('editar')) : null;

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
            $datos = [
                'nombre'           => trim((string)$_POST['nombre']),
                'ap_paterno'       => trim((string)$_POST['ap_paterno']),
                'ap_materno'       => trim((string)($_POST['ap_materno'] ?? '')),
                'telefono'         => trim((string)$_POST['telefono']),
                'fecha_nacimiento' => ($_POST['fecha_nacimiento'] ?? '') !== '' ? $_POST['fecha_nacimiento'] : null,
            ];
            if ($idEditar !== '') {
                Usuario::actualizarCliente($idEditar, $datos);
                Flash::ok('Cliente actualizado.');
            } else {
                $id = Usuario::crearCliente($datos, (string)$_POST['correo'], (string)$_POST['password']);
                Flash::ok('Cliente registrado: ' . $id);
            }
        } catch (\Throwable $e) {
            error_log('[ADMIN-CLIENTES] ' . $e->getMessage());
            Flash::error('No se pudo guardar el cliente.');
        }
        $this->redirigir('admin_clientes', ['q' => $this->texto('q')]);
    }

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
                if (Reserva::cambiarEstado($id, $nuevo)) {
                    Flash::ok('Reserva #' . $id . ' marcada como ' . $nuevo . '.');
                } else {
                    Flash::error('No se pudo actualizar la reserva.');
                }
            } elseif ($accion === 'observacion') {
                $idReserva = $this->entero('id_reserva');
                if (Reserva::porId($idReserva) !== null) {
                    Historial::guardarObservacion($idReserva, trim((string)($_POST['observaciones'] ?? '')));
                    Flash::ok('Observación guardada en el historial.');
                }
            } elseif ($accion === 'pago_confirmar') {
                $id = $this->entero('id_reserva');
                if (Reserva::confirmarPago($id, Auth::id())) {
                    Flash::ok('Anticipo de la reserva #' . $id . ' confirmado. Se actualizó el saldo.');
                } else {
                    Flash::error('No se pudo confirmar el pago. La reserva debe tener un comprobante por verificar.');
                }
            } elseif ($accion === 'pago_rechazar') {
                $id = $this->entero('id_reserva');
                $motivo = trim((string)($_POST['motivo'] ?? ''));
                if ($motivo === '') {
                    $motivo = 'Comprobante rechazado por el personal.';
                }
                if (Reserva::rechazarPago($id, $motivo, Auth::id())) {
                    Flash::ok('Comprobante de la reserva #' . $id . ' rechazado. El cliente puede enviar uno nuevo.');
                } else {
                    Flash::error('No se pudo rechazar el comprobante.');
                }
            } elseif ($accion === 'pago_efectivo') {
                $id = $this->entero('id_reserva');
                if (Reserva::registrarPagoEfectivo($id, Auth::id())) {
                    Flash::ok('Pago en efectivo de la reserva #' . $id . ' registrado. Saldo en cero.');
                } else {
                    Flash::error('No se pudo registrar el pago en efectivo.');
                }
            }
            $this->redirigir('admin_citas', array_filter([
                'fecha' => $fecha, 'estado' => $estado, 'sucursal' => $idSucursal, 'ficha' => $ficha ?: null,
            ]));
        }

        $reservas = Reserva::filtrar($fecha, $estado, $idSucursal);
        $sucursales = Sucursal::lista();

        $fichaCliente = null;
        if ($ficha > 0) {
            $reservaFicha = Reserva::conCliente($ficha);
            if ($reservaFicha !== null) {
                $fichaCliente = [
                    'cliente'   => $reservaFicha,
                    'historial' => Historial::deUsuario((string)$reservaFicha['id_usuario']),
                    'compras'   => Compra::recientesDeUsuario((string)$reservaFicha['id_usuario'], 5),
                ];
            }
        }

        $this->vista('admin/citas.php', compact('reservas', 'sucursales', 'fecha', 'estado', 'idSucursal', 'fichaCliente'));
    }

    public function inventario(): void
    {
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $this->guardarProducto();
        }

        ['filas' => $productos, 'total' => $total] = Producto::buscar($q, '', $pagina, self::POR_PAGINA);
        $categorias = Producto::categorias();

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
            [$id, $creado] = Producto::guardar($idEditar !== '' ? $idEditar : null, $datos);
            Flash::ok($creado ? 'Producto registrado: ' . $id : 'Producto actualizado.');
        } catch (\Throwable $e) {
            error_log('[ADMIN-INV] ' . $e->getMessage());
            Flash::error('No se pudo guardar el producto.');
        }
        $this->redirigir('admin_inventario', ['q' => $this->texto('q')]);
    }

    public function ventas(): void
    {
        $fecha = $this->texto('fecha');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            $this->registrarVenta();
        }

        ['filas' => $ventas, 'total' => $total] = Compra::ventas($fecha, $pagina, self::POR_PAGINA);
        $totales = Compra::totales($fecha);
        $productos = Producto::lista();
        $clientes = Usuario::listaClientes();
        $metodos = MetodoPago::activos();

        $this->vista('admin/ventas.php', compact('ventas', 'productos', 'clientes', 'metodos', 'fecha', 'pagina', 'total', 'totales'));
    }

    private function registrarVenta(): void
    {
        $idCliente = $this->texto('id_usuario');
        $idProducto = $this->texto('id_producto');
        $cantidad = max(1, $this->entero('cantidad', 1));
        $idMetodo = $this->texto('id_metodoPago');

        $producto = Producto::paraCarrito($idProducto);
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
            $compra = Compra::crear($idCliente, $idMetodo !== '' ? $idMetodo : null, [
                ['id' => $idProducto, 'precio' => (float)$producto['precio'], 'cantidad' => $cantidad],
            ]);
            Flash::ok('Venta registrada: ' . $compra['nro'] . ' por ' . bs($compra['total']) . '.');
        } catch (\Throwable $e) {
            error_log('[ADMIN-VENTAS] ' . $e->getMessage());
            Flash::error('No se pudo registrar la venta.');
        }
        $this->redirigir('admin_ventas', ['fecha' => date('Y-m-d')]);
    }

    public function servicios(): void
    {
        $q = $this->texto('q');
        $pagina = max(1, $this->entero('p', 1));

        if ($this->esPost()) {
            if ($this->texto('accion') === 'categoria') {
                $this->guardarCategoriaServicio();
            } else {
                $this->guardarServicio();
            }
        }

        ['filas' => $servicios, 'total' => $total] = Servicio::buscar($q, $pagina, self::POR_PAGINA);
        $categorias = Servicio::categorias();

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
            [$id, $creado] = Servicio::guardar($idEditar !== '' ? $idEditar : null, [
                'nombre'           => trim((string)$_POST['nombre']),
                'descripcion'      => trim((string)($_POST['descripcion'] ?? '')),
                'precio'           => (float)$_POST['precio'],
                'duracion_minutos' => $this->entero('duracion_minutos'),
                'id_categoriaS'    => $this->texto('id_categoriaS') !== '' ? $this->texto('id_categoriaS') : null,
            ]);
            Flash::ok($creado ? 'Servicio registrado: ' . $id : 'Servicio actualizado.');
        } catch (\Throwable $e) {
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
        } elseif (Servicio::existeCategoria($nombre)) {
            Flash::error('Ya existe una categoría con ese nombre.');
        } else {
            try {
                Servicio::crearCategoria($nombre);
                Flash::ok('Categoría creada: ' . $nombre);
            } catch (\Throwable $e) {
                error_log('[ADMIN-CATS] ' . $e->getMessage());
                Flash::error('No se pudo crear la categoría.');
            }
        }
        $this->redirigir('admin_servicios');
    }
}
