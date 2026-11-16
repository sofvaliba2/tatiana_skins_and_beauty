<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\Upload;
use App\Core\Validate;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Servicio;

final class PromocionController extends Controller
{
    private const POR_PAGINA = 10;

    /** Página pública de promociones. */
    public function index(): void
    {
        $promociones = Promocion::activas();
        $porVencer = Promocion::porVencer();
        $this->vista('home/promociones.php', compact('promociones', 'porVencer'));
    }

    /** Panel de administración: registro, consulta y edición. */
    public function admin(): void
    {
        if ($this->esPost()) {
            $accion = $this->texto('accion', 'guardar');
            if ($accion === 'estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'eliminar') {
                $this->eliminar();
            } else {
                $this->guardar();
            }
        }

        $q = $this->texto('q');
        $estadoFiltro = $this->texto('estado');
        $pagina = max(1, $this->entero('p', 1));

        ['filas' => $promociones, 'total' => $total] = Promocion::buscar($q, $estadoFiltro, $pagina, self::POR_PAGINA);
        $resumen = Promocion::resumen();
        $editar = $this->texto('editar') !== '' ? Promocion::porId($this->texto('editar')) : null;
        $productos = Producto::lista();
        $servicios = Servicio::todos();

        $this->vista('admin/promociones.php', compact(
            'promociones', 'total', 'pagina', 'q', 'estadoFiltro', 'resumen', 'editar', 'productos', 'servicios'
        ));
    }

    private function guardar(): void
    {
        $idEditar = $this->texto('id_promocion');
        $idProducto = $this->texto('id_producto');
        $idServicio = $this->texto('id_servicio');
        $precioOriginal = (float)str_replace(',', '.', $this->texto('precio_original'));
        $precioPromo = (float)str_replace(',', '.', $this->texto('precio_promocional'));
        $fechaInicio = $this->texto('fecha_inicio');
        $fechaFin = $this->texto('fecha_fin');
        $estado = $this->texto('estado', 'activa');

        $v = Validate::make()
            ->texto('nombre', 'El nombre', 3, 100);
        if ($this->texto('descripcion') !== '' && mb_strlen($this->texto('descripcion')) > 255) {
            $v->fallo('descripcion', 'La descripción no puede superar 255 caracteres.');
        }
        if ($idProducto === '' && $idServicio === '') {
            $v->fallo('id_producto', 'Asocia la promoción a un producto o a un servicio.');
        }
        if ($idProducto !== '' && Producto::porId($idProducto) === null) {
            $v->fallo('id_producto', 'El producto seleccionado no existe.');
        }
        if ($idServicio !== '' && Servicio::porId($idServicio) === null) {
            $v->fallo('id_servicio', 'El servicio seleccionado no existe.');
        }
        if ($precioOriginal <= 0) {
            $v->fallo('precio_original', 'El precio original debe ser mayor a 0.');
        }
        if ($precioPromo < 0) {
            $v->fallo('precio_promocional', 'El precio promocional no puede ser negativo.');
        } elseif ($precioOriginal > 0 && $precioPromo > $precioOriginal) {
            $v->fallo('precio_promocional', 'El precio promocional no puede ser mayor que el precio original.');
        }
        if (!self::fechaValida($fechaInicio)) {
            $v->fallo('fecha_inicio', 'Selecciona una fecha de inicio válida.');
        }
        if (!self::fechaValida($fechaFin)) {
            $v->fallo('fecha_fin', 'Selecciona una fecha de fin válida.');
        }
        if (self::fechaValida($fechaInicio) && self::fechaValida($fechaFin) && $fechaInicio > $fechaFin) {
            $v->fallo('fecha_fin', 'La fecha de fin debe ser posterior a la de inicio.');
        }
        if (!in_array($estado, Promocion::ESTADOS, true)) {
            $v->fallo('estado', 'Selecciona un estado válido.');
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_promociones');
        }

        $imagen = null;
        if (!empty($_FILES['imagen']['name'] ?? '')) {
            $errorUp = null;
            $prefijo = 'uploads/promociones/promo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $imagen = Upload::imagen($_FILES['imagen'], $prefijo, $errorUp);
            if ($imagen === null) {
                Flash::error($errorUp ?? 'No se pudo subir la imagen.');
                $this->redirigir('admin_promociones');
            }
        }

        try {
            $datos = [
                'nombre'             => $this->texto('nombre'),
                'descripcion'        => $this->texto('descripcion') !== '' ? $this->texto('descripcion') : null,
                'id_producto'        => $idProducto !== '' ? $idProducto : null,
                'id_servicio'        => $idServicio !== '' ? $idServicio : null,
                'precio_original'    => round($precioOriginal, 2),
                'precio_promocional' => round($precioPromo, 2),
                'fecha_inicio'       => $fechaInicio,
                'fecha_fin'          => $fechaFin,
                'estado'             => $estado,
            ];
            if ($imagen !== null) {
                $datos['imagen_url'] = $imagen;
            }
            [$id, $creado] = Promocion::guardar($idEditar !== '' ? $idEditar : null, $datos);
            Flash::ok($creado ? 'Promoción registrada: ' . $id : 'Promoción actualizada.');
        } catch (\Throwable $e) {
            error_log('[ADMIN-PROMO] ' . $e->getMessage());
            Flash::error('No se pudo guardar la promoción.');
        }
        $this->redirigir('admin_promociones');
    }

    private function cambiarEstado(): void
    {
        $id = $this->texto('id_promocion');
        $nuevo = $this->texto('nuevo_estado') === 'inactiva' ? 'inactiva' : 'activa';
        if (Promocion::porId($id) === null) {
            Flash::error('La promoción no existe.');
        } elseif (Promocion::cambiarEstado($id, $nuevo)) {
            Flash::ok('Promoción ' . ($nuevo === 'activa' ? 'activada' : 'desactivada') . '.');
        } else {
            Flash::error('No se pudo cambiar el estado de la promoción.');
        }
        $this->redirigir('admin_promociones');
    }

    private function eliminar(): void
    {
        $id = $this->texto('id_promocion');
        if (Promocion::porId($id) === null) {
            Flash::error('La promoción no existe.');
        } elseif (Promocion::eliminar($id)) {
            Flash::ok('Promoción eliminada.');
        } else {
            Flash::error('No se pudo eliminar la promoción.');
        }
        $this->redirigir('admin_promociones');
    }

    private static function fechaValida(string $fecha): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }
}
