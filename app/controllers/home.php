<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

final class Home extends Controller
{
    public function index(): void
    {
        $servicios = Database::all(
            'SELECT s.*, cs.nombre_cservicio AS categoria
             FROM servicio s
             LEFT JOIN categoria_servicio cs ON cs.id_categoriaS = s.id_categoriaS
             ORDER BY s.nombre
             LIMIT 6'
        );
        $productos = Database::all(
            'SELECT p.*, cp.nombre_categoria AS categoria
             FROM producto p
             LEFT JOIN categoria_producto cp ON cp.id_categoria = p.id_categoria
             ORDER BY p.precio DESC
             LIMIT 4'
        );
        $sucursales = Database::all('SELECT * FROM sucursal ORDER BY nombre_sucursal');
        $stats = [
            'clientes'   => (int)Database::value("SELECT COUNT(*) FROM usuario WHERE id_rol = 'ROL03'"),
            'sucursales' => count($sucursales),
            'servicios'  => (int)Database::value('SELECT COUNT(*) FROM servicio'),
        ];
        $this->vista('home/index.php', compact('servicios', 'productos', 'sucursales', 'stats'));
    }

    public function servicios(): void
    {
        $categorias = Database::all('SELECT * FROM categoria_servicio ORDER BY nombre_cservicio');
        $cat = $this->texto('cat');
        $q = $this->texto('q');

        $sql = 'SELECT s.*, cs.nombre_cservicio AS categoria
                FROM servicio s
                LEFT JOIN categoria_servicio cs ON cs.id_categoriaS = s.id_categoriaS
                WHERE 1';
        $params = [];
        if ($cat !== '') {
            $sql .= ' AND s.id_categoriaS = ?';
            $params[] = $cat;
        }
        if ($q !== '') {
            $sql .= ' AND (s.nombre LIKE ? OR s.descripcion LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY s.nombre';
        $servicios = Database::all($sql, $params);

        $this->vista('home/servicios.php', compact('servicios', 'categorias', 'cat', 'q'));
    }

    public function ayuda(): void
    {
        $sucursales = Database::all(
            'SELECT s.*, h.dia_semana, h.hora_apertura, h.hora_cierre
             FROM sucursal s
             LEFT JOIN horario_sucursal h ON h.id_sucursal = s.id_sucursal AND h.es_activo = 1
             ORDER BY s.nombre_sucursal, FIELD(h.dia_semana, "Lunes","Martes","Miercoles","Jueves","Viernes","Sabado","Domingo")'
        );
        $this->vista('home/ayuda.php', ['sucursales' => $sucursales]);
    }
}
