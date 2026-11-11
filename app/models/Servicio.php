<?php
declare(strict_types=1);

namespace App\Models;

final class Servicio extends Model
{
    private const SELECT = 'SELECT s.*, cs.nombre_cservicio AS categoria
                FROM servicio s
                LEFT JOIN categoria_servicio cs ON cs.id_categoriaS = s.id_categoriaS';

    public static function categorias(): array
    {
        return self::all('SELECT * FROM categoria_servicio ORDER BY nombre_cservicio');
    }

    public static function total(): int
    {
        return (int)self::value('SELECT COUNT(*) FROM servicio');
    }

    public static function porId(string $id): ?array
    {
        return self::one('SELECT * FROM servicio WHERE id_servicio = ?', [$id]);
    }

    public static function todos(): array
    {
        return self::all(self::SELECT . ' ORDER BY s.nombre');
    }

    public static function primeros(int $limite = 6): array
    {
        return self::all(self::SELECT . ' ORDER BY s.nombre LIMIT ' . $limite);
    }

    public static function filtrar(string $categoria, string $q): array
    {
        [$sql, $params] = self::filtro($categoria, $q);
        return self::all($sql . ' ORDER BY s.nombre', $params);
    }

    public static function buscar(string $q, int $pagina, int $porPagina): array
    {
        [$sql, $params] = self::filtro('', $q);
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY s.nombre LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    private static function filtro(string $categoria, string $q): array
    {
        $sql = self::SELECT . ' WHERE 1';
        $params = [];
        if ($categoria !== '') {
            $sql .= ' AND s.id_categoriaS = ?';
            $params[] = $categoria;
        }
        if ($q !== '') {
            $sql .= ' AND (s.nombre LIKE ? OR s.descripcion LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        return [$sql, $params];
    }

    public static function guardar(?string $idEditar, array $datos): array
    {
        return self::transaccion(function () use ($idEditar, $datos) {
            if ($idEditar !== null) {
                self::run('UPDATE servicio SET ' . self::sets($datos) . ' WHERE id_servicio = ?', [...array_values($datos), $idEditar]);
                return [$idEditar, false];
            }
            return [Ids::insertarConCodigo('servicio', 'id_servicio', 'SERV', 2, $datos), true];
        });
    }

    public static function existeCategoria(string $nombre): bool
    {
        return self::one('SELECT id_categoriaS FROM categoria_servicio WHERE nombre_cservicio = ?', [$nombre]) !== null;
    }

    public static function crearCategoria(string $nombre): string
    {
        return Ids::insertarConCodigo('categoria_servicio', 'id_categoriaS', 'CS', 2, ['nombre_cservicio' => $nombre]);
    }

    /**
     * Clasifica un servicio del catálogo real como facial o depilación.
     * No crea registros: solo usa la categoría/nombre existentes.
     */
    public static function tipoTratamiento(array $servicio): string
    {
        $categoria = (string)($servicio['id_categoriaS'] ?? $servicio['categoria'] ?? '');
        $nombre = mb_strtolower((string)($servicio['nombre'] ?? ''));
        if ($categoria === 'CS01' || str_contains($nombre, 'depil')) {
            return 'depilacion';
        }
        return 'facial';
    }

    /** Zonas de depilación admitidas por el formulario. */
    public static function zonasDepilacion(): array
    {
        return [
            'axilas'  => 'Axilas',
            'piernas' => 'Piernas',
            'brazos'  => 'Brazos',
            'rostro'  => 'Rostro',
            'bozo'    => 'Bozo',
            'cejas'   => 'Cejas',
            'pies'    => 'Pies',
            'otras'   => 'Otras',
        ];
    }

    /** Productos del catálogo existente relacionados con un servicio. */
    public static function productosRelacionados(string $idServicio, int $limite = 6): array
    {
        return self::all(
            'SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio, p.cantidad, p.imagen_url
             FROM servicio_producto sp
             JOIN producto p ON p.id_producto = sp.id_producto
             WHERE sp.id_servicio = ?
             ORDER BY sp.recomendado DESC, p.nombre_producto
             LIMIT ' . $limite,
            [$idServicio]
        );
    }
}
