<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Models\Compra;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sucursal;

final class CatalogoController extends Controller
{
    private const POR_PAGINA = 8;

    public function index(): void
    {
        $q = $this->texto('q');
        $cat = $this->texto('cat');
        $pagina = max(1, $this->entero('p', 1));

        $categorias = Producto::categorias();
        ['filas' => $productos, 'total' => $total] = Producto::buscar($q, $cat, $pagina, self::POR_PAGINA);

        $this->vista('catalogo/index.php', compact('productos', 'categorias', 'q', 'cat', 'pagina', 'total'));
    }

    public function detalle(): void
    {
        $id = $this->texto('id');
        $producto = Producto::porId($id);
        if ($producto === null) {
            (new ErroresController())->noEncontrado();
            return;
        }
        $relacionados = Producto::relacionados((string)$producto['id_categoria'], $id);
        $this->vista('catalogo/detalle.php', compact('producto', 'relacionados'));
    }

    public function agregar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('catalogo');
        }
        $id = $this->texto('id_producto');
        $cantidad = max(1, $this->entero('cantidad', 1));
        $producto = Producto::paraCarrito($id);

        if ($producto === null) {
            Flash::error('El producto ya no está disponible.');
            $this->redirigir('catalogo');
        }
        if ((int)$producto['stock'] < $cantidad) {
            Flash::error('Solo hay ' . (int)$producto['stock'] . ' unidades disponibles de ' . $producto['nombre_producto'] . '.');
            $this->redirigir('producto', ['id' => $id]);
        }

        $carrito = $_SESSION['carrito'] ?? [];
        $actual = (int)($carrito[$id]['cantidad'] ?? 0);
        $nueva = $actual + $cantidad;
        if ($nueva > (int)$producto['stock']) {
            $nueva = (int)$producto['stock'];
        }
        $carrito[$id] = [
            'id'       => $id,
            'nombre'   => $producto['nombre_producto'],
            'precio'   => (float)$producto['precio'],
            'cantidad' => $nueva,
            'stock'    => (int)$producto['stock'],
        ];
        $_SESSION['carrito'] = $carrito;
        Flash::ok('Añadido al carrito: ' . $producto['nombre_producto'] . '.');
        $this->redirigir('carrito');
    }

    public function actualizar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('carrito');
        }
        $carrito = $_SESSION['carrito'] ?? [];
        foreach (($_POST['cantidad'] ?? []) as $id => $cant) {
            $id = (string)$id;
            if (!isset($carrito[$id])) {
                continue;
            }
            $cant = (int)$cant;
            $stock = (int)$carrito[$id]['stock'];
            if ($cant <= 0) {
                unset($carrito[$id]);
            } else {
                $carrito[$id]['cantidad'] = min($cant, $stock);
            }
        }
        $_SESSION['carrito'] = $carrito;
        Flash::ok('Carrito actualizado.');
        $this->redirigir('carrito');
    }

    public function quitar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('carrito');
        }
        $id = $this->texto('id_producto');
        $carrito = $_SESSION['carrito'] ?? [];
        unset($carrito[$id]);
        $_SESSION['carrito'] = $carrito;
        Flash::ok('Producto retirado del carrito.');
        $this->redirigir('carrito');
    }

    public function carrito(): void
    {
        $carrito = array_values($_SESSION['carrito'] ?? []);
        $metodosPago = MetodoPago::activos();
        $sucursales = Sucursal::todas();
        $errores = $_SESSION['carrito_errores'] ?? [];
        unset($_SESSION['carrito_errores']);
        $this->vista('catalogo/carrito.php', compact('carrito', 'metodosPago', 'sucursales', 'errores'));
    }

    public function checkout(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('carrito');
        }
        $carrito = array_values($_SESSION['carrito'] ?? []);
        if ($carrito === []) {
            Flash::error('Tu carrito está vacío.');
            $this->redirigir('catalogo');
        }
        $idMetodo = $this->texto('id_metodoPago');

        foreach ($carrito as $item) {
            $stock = Producto::stock((string)$item['id']);
            if ($stock < (int)$item['cantidad']) {
                Flash::error('El stock de ' . $item['nombre'] . ' cambió (quedan ' . $stock . '). Revisa tu carrito.');
                $this->redirigir('carrito');
            }
        }

        try {
            $compra = Compra::crear(Auth::id(), $idMetodo !== '' ? $idMetodo : null, $carrito);
        } catch (\Throwable $e) {
            error_log('[CHECKOUT] ' . $e->getMessage());
            Flash::error('No se pudo procesar la compra. Intenta nuevamente.');
            $this->redirigir('carrito');
        }

        unset($_SESSION['carrito']);
        Flash::ok('¡Compra registrada! N° ' . $compra['nro'] . ' por ' . bs($compra['total']) . '.');
        $this->redirigir('detalle_compra', ['id' => $compra['id']]);
    }
}
