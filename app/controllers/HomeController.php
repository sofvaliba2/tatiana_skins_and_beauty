<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Usuario;

final class HomeController extends Controller
{
    public function index(): void
    {
        $servicios = Servicio::primeros(6);
        $productos = Producto::destacados(4);
        $sucursales = Sucursal::todas();
        $stats = [
            'clientes'   => Usuario::contarClientes(),
            'sucursales' => count($sucursales),
            'servicios'  => Servicio::total(),
        ];
        $this->vista('home/index.php', compact('servicios', 'productos', 'sucursales', 'stats'));
    }

    public function servicios(): void
    {
        $cat = $this->texto('cat');
        $q = $this->texto('q');
        $categorias = Servicio::categorias();
        $servicios = Servicio::filtrar($cat, $q);

        $this->vista('home/servicios.php', compact('servicios', 'categorias', 'cat', 'q'));
    }

    public function ayuda(): void
    {
        $this->vista('home/ayuda.php', ['sucursales' => Sucursal::conHorariosActivos()]);
    }
}
