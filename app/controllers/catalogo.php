<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Ids;

final class Catalogo extends Controller
{
    private const POR_PAGINA = 8;

    public function index(): void
    {
        $q = $this->texto('q');
        $cat = $this->texto('cat');
        $pagina = max(1, $this->entero('p', 1));

        $categorias = Database::all('SELECT * FROM categoria_producto ORDER BY nombre_categoria');

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
        if ($cat !== '') {
            $sql .= ' AND p.id_categoria = ?';
            $params[] = $cat;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM (' . $sql . ') t', $params);
        $sql .= ' ORDER BY p.nombre_producto LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA);
        $productos = Database::all($sql, $params);

        $this->vista('catalogo/index.php', compact('productos', 'categorias', 'q', 'cat', 'pagina', 'total'));
    }

    public function detalle(): void
    {
        $id = $this->texto('id');
        $producto = Database::one(
            'SELECT p.*, c.nombre_categoria
             FROM producto p
             LEFT JOIN categoria_producto c ON c.id_categoria = p.id_categoria
             WHERE p.id_producto = ?',
            [$id]
        );
        if ($producto === null) {
            http_response_code(404);
            (new Errores())->noEncontrado();
            return;
        }
        $relacionados = Database::all(
            'SELECT p.*, c.nombre_categoria
             FROM producto p
             LEFT JOIN categoria_producto c ON c.id_categoria = p.id_categoria
             WHERE p.id_categoria = ? AND p.id_producto <> ?
             LIMIT 4',
            [$producto['id_categoria'], $id]
        );
        $this->vista('catalogo/detalle.php', compact('producto', 'relacionados'));
    }

    public function agregar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('catalogo');
        }
        $id = $this->texto('id_producto');
        $cantidad = max(1, $this->entero('cantidad', 1));
        $producto = Database::one('SELECT id_producto, nombre_producto, precio, cantidad AS stock FROM producto WHERE id_producto = ?', [$id]);

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
        $metodosPago = Database::all("SELECT * FROM metodo_pago WHERE estado = 'activo' OR estado IS NULL ORDER BY nombre_metodoPago");
        $sucursales = Database::all('SELECT * FROM sucursal ORDER BY nombre_sucursal');
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
            $stock = (int)Database::value('SELECT cantidad FROM producto WHERE id_producto = ?', [$item['id']]);
            if ($stock < (int)$item['cantidad']) {
                Flash::error('El stock de ' . $item['nombre'] . ' cambió (quedan ' . $stock . '). Revisa tu carrito.');
                $this->redirigir('carrito');
            }
        }

        try {
            Database::begin();
            $total = 0;
            foreach ($carrito as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            $nro = 'C-' . str_pad((string)((int)Database::value('SELECT COALESCE(MAX(id_compra),0) FROM compra') + 1), 6, '0', STR_PAD_LEFT);
            $idCompra = Ids::insertarConEntero('compra', 'id_compra', [
                'fecha_compra'  => date('Y-m-d'),
                'nro_compra'    => $nro,
                'total_pagar'   => $total,
                'id_usuario'    => Auth::id(),
                'id_metodoPago' => $idMetodo !== '' ? $idMetodo : null,
            ]);
            foreach ($carrito as $item) {
                Ids::insertarConEntero('detalle_compra', 'id_detalle', [
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal'        => $item['precio'] * $item['cantidad'],
                    'id_compra'       => $idCompra,
                    'id_producto'     => $item['id'],
                ]);
                Database::run('UPDATE producto SET cantidad = cantidad - ? WHERE id_producto = ?', [$item['cantidad'], $item['id']]);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[CHECKOUT] ' . $e->getMessage());
            Flash::error('No se pudo procesar la compra. Intenta nuevamente.');
            $this->redirigir('carrito');
        }

        unset($_SESSION['carrito']);
        Flash::ok('¡Compra registrada! N° ' . $nro . ' por ' . bs($total) . '.');
        $this->redirigir('detalle_compra', ['id' => $idCompra]);
    }
}
