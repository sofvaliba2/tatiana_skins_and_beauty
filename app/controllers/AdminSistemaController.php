<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validate;
use App\Core\Xlsx;
use App\Models\Compra;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Reporte;
use App\Models\Reserva;
use App\Models\Sucursal;
use App\Models\Usuario;

final class AdminSistemaController extends Controller
{
    public function sucursales(): void
    {
        if ($this->esPost()) {
            if ($this->texto('accion') === 'horario') {
                $this->guardarHorario();
            } else {
                $this->guardarSucursal();
            }
        }

        $idEditar = $this->texto('editar');
        $editar = $idEditar !== '' ? Sucursal::porId($idEditar) : null;
        $sucursales = Sucursal::todas();
        $horarios = [];
        foreach ($sucursales as $s) {
            $horarios[$s['id_sucursal']] = Sucursal::horarios((string)$s['id_sucursal']);
        }

        $this->vista('admin/sucursales.php', compact('sucursales', 'horarios', 'editar'));
    }

    private function guardarSucursal(): void
    {
        $idEditar = $this->texto('id_sucursal');
        $v = Validate::make()->texto('nombre_sucursal', 'El nombre', 2, 50);
        if ($this->texto('direccion') !== '' && mb_strlen($this->texto('direccion')) > 150) {
            $v->fallo('direccion', 'La dirección no puede superar 150 caracteres.');
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_sucursales');
        }

        try {
            [$id, $creado] = Sucursal::guardar($idEditar !== '' ? $idEditar : null, [
                'nombre_sucursal' => trim((string)$_POST['nombre_sucursal']),
                'direccion'       => trim((string)($_POST['direccion'] ?? '')),
                'telefono'        => trim((string)($_POST['telefono'] ?? '')),
            ]);
            Flash::ok($creado ? 'Sucursal creada: ' . $id . '. Ahora configura su horario.' : 'Sucursal actualizada.');
        } catch (\Throwable $e) {
            error_log('[ADMIN-SUC] ' . $e->getMessage());
            Flash::error('No se pudo guardar la sucursal.');
        }
        $this->redirigir('admin_sucursales');
    }

    private function guardarHorario(): void
    {
        $idSucursal = $this->texto('id_sucursal');
        if (Sucursal::porId($idSucursal) === null) {
            Flash::error('Sucursal no válida.');
            $this->redirigir('admin_sucursales');
        }
        Sucursal::guardarHorario(
            $idSucursal,
            (array)($_POST['dia'] ?? []),
            (array)($_POST['apertura'] ?? []),
            (array)($_POST['cierre'] ?? [])
        );
        Flash::ok('Horario actualizado.');
        $this->redirigir('admin_sucursales');
    }

    public function usuarios(): void
    {
        if ($this->esPost()) {
            $this->guardarUsuario();
        }

        $usuarios = Usuario::personal();
        $sucursales = Sucursal::lista();

        $this->vista('admin/usuarios.php', compact('usuarios', 'sucursales'));
    }

    private function guardarUsuario(): void
    {
        $idEditar = $this->texto('id_usuario');
        $idRol = $this->texto('id_rol');
        $idSucursal = $this->texto('id_sucursal');
        $v = Validate::make()
            ->texto('nombre', 'El nombre', 2, 50)
            ->texto('ap_paterno', 'El apellido paterno', 2, 50)
            ->telefono('telefono');
        if (!in_array($idRol, ['ROL01', 'ROL02'], true)) {
            $v->fallo('id_rol', 'Selecciona un rol válido (administrador o personal).');
        }
        if ($idSucursal !== '' && Sucursal::porId($idSucursal) === null) {
            $v->fallo('id_sucursal', 'La sucursal seleccionada no existe.');
        }
        if ($idRol === 'ROL02' && $idSucursal === '') {
            $v->fallo('id_sucursal', 'Asigna una sucursal al empleado. Es obligatoria para su panel de trabajo.');
        }
        if ($idEditar === '') {
            $v->correo('correo')->unico('cuenta_usuario', 'correo', 'correo', 'correo electrónico');
            if (strlen((string)($_POST['password'] ?? '')) < 8) {
                $v->fallo('password', 'La contraseña debe tener al menos 8 caracteres.');
            }
        }
        if ($this->texto('nueva_password') !== '' && strlen((string)$_POST['nueva_password']) < 8) {
            $v->fallo('nueva_password', 'La nueva contraseña debe tener al menos 8 caracteres.');
        }
        if ($v->fallido()) {
            Flash::error(implode(' ', $v->mensajes()));
            $this->redirigir('admin_usuarios');
        }

        try {
            $r = Usuario::guardarPersonal(
                $idEditar !== '' ? $idEditar : null,
                [
                    'nombre'      => trim((string)$_POST['nombre']),
                    'ap_paterno'  => trim((string)$_POST['ap_paterno']),
                    'ap_materno'  => trim((string)($_POST['ap_materno'] ?? '')),
                    'telefono'    => trim((string)$_POST['telefono']),
                    'id_rol'      => $idRol,
                    'id_sucursal' => $idSucursal !== '' ? $idSucursal : null,
                ],
                $idEditar === '' ? (string)$_POST['correo'] : null,
                $idEditar === '' ? (string)$_POST['password'] : null,
                $this->texto('nueva_password') !== '' ? (string)$_POST['nueva_password'] : null
            );
            if ($r['creado']) {
                Flash::ok(($idRol === 'ROL02' ? 'Empleado registrado: ' : 'Usuario administrador registrado: ') . $r['id']);
            } else {
                Flash::ok('Usuario actualizado.');
            }
            if ($r['passwordCambiada']) {
                Flash::ok('Contraseña restablecida.');
            }
        } catch (\Throwable $e) {
            error_log('[ADMIN-USU] ' . $e->getMessage());
            Flash::error('No se pudo guardar el usuario.');
        }
        $this->redirigir('admin_usuarios');
    }

    public function reportes(): void
    {
        $desde = $this->texto('desde', date('Y-m-01'));
        $hasta = $this->texto('hasta', date('Y-m-d'));
        $mes = $this->texto('mes');
        $anio = $this->texto('anio');

        // Atajos combinables de mes/año: si se elige un año (y mes) se calcula
        // el rango real sobre las fechas guardadas en la base de datos.
        if (ctype_digit($anio) && (int)$anio > 0) {
            if (ctype_digit($mes) && (int)$mes >= 1 && (int)$mes <= 12) {
                $desde = sprintf('%04d-%02d-01', (int)$anio, (int)$mes);
                $hasta = date('Y-m-t', strtotime($desde));
            } else {
                $mes = '';
                $desde = sprintf('%04d-01-01', (int)$anio);
                $hasta = sprintf('%04d-12-31', (int)$anio);
            }
        } else {
            $anio = '';
            $mes = '';
        }
        if (!self::fechaValida($desde)) {
            $desde = date('Y-m-01');
        }
        if (!self::fechaValida($hasta)) {
            $hasta = date('Y-m-d');
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $sucursales = Sucursal::lista();
        $sucursal = $this->texto('sucursal');
        $idsSucursal = array_column($sucursales, 'id_sucursal');
        if ($sucursal !== '' && $sucursal !== 'NULL' && !in_array($sucursal, $idsSucursal, true)) {
            $sucursal = '';
        }
        $filtro = $sucursal === '' ? null : $sucursal;

        if (($export = $this->texto('export')) !== '') {
            if ($export === 'excel') {
                $this->exportarExcel($desde, $hasta, $sucursal, $sucursales, Promocion::activas());
            }
            $this->exportarCSV($export, $desde, $hasta, $filtro);
        }

        $resumen = Reporte::resumenCompras($desde, $hasta, $filtro);
        $reservas = Reporte::resumenReservas($desde, $hasta, $filtro);
        $porProducto = Reporte::porProducto($desde, $hasta, 10, $filtro);
        $porServicio = Reporte::porServicio($desde, $hasta, 10, $filtro);
        $topClientes = Reporte::topClientes($desde, $hasta, 10, $filtro);
        $serie = Reporte::serie($desde, $hasta, $filtro);
        $porMetodo = Reporte::porMetodoPago($desde, $hasta, $filtro);
        $porSucursal = Reporte::porSucursal($desde, $hasta);
        $promociones = Promocion::activas();
        $anios = $this->aniosReportes();

        $this->vista('admin/reportes.php', compact(
            'desde', 'hasta', 'mes', 'anio', 'anios', 'sucursal', 'sucursales',
            'resumen', 'reservas', 'porProducto', 'porServicio', 'topClientes',
            'serie', 'porMetodo', 'porSucursal', 'promociones'
        ));
    }

    private static function fechaLarga(string $fecha): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d === false ? $fecha : $d->format('d/m/Y');
    }

    private static function nombreSucursal(string $id, array $sucursales): string
    {
        if ($id === '') {
            return 'Todas las sucursales';
        }
        if ($id === 'NULL') {
            return 'Sin sucursal asignada';
        }
        foreach ($sucursales as $s) {
            if (($s['id_sucursal'] ?? '') === $id) {
                return (string)$s['nombre_sucursal'];
            }
        }
        return $id;
    }

    /** Exporta un XLSX con una hoja resumen y una hoja por sucursal, todas con el mismo diseño. */
    private function exportarExcel(string $desde, string $hasta, string $sucursal, array $sucursales, array $promociones = []): void
    {
        $x = new Xlsx();
        $porSucursal = Reporte::porSucursal($desde, $hasta);
        $rango = 'Desde ' . self::fechaLarga($desde) . ' hasta ' . self::fechaLarga($hasta)
            . '   ·   Sucursal: ' . self::nombreSucursal($sucursal, $sucursales);

        $head = static fn (string $t) => Xlsx::celda($t, Xlsx::S_HEAD);
        $h = $x->addSheet('Resumen', [30, 13, 17, 14, 14, 14, 3, 17, 17, 17], 5);
        $x->fila($h, 1, [Xlsx::celda('TATIANA SKIN AND BEAUTY  |  REPORTES', Xlsx::S_TITLE)], 32);
        $x->fila($h, 2, [Xlsx::celda($rango, Xlsx::S_SUBTITLE)], 20);
        $x->fila($h, 3, [Xlsx::celda('Generado el ' . date('d/m/Y H:i'), Xlsx::S_SUBTITLE)], 18);

        $filtro = $sucursal === '' ? null : $sucursal;
        $resumen = Reporte::resumenCompras($desde, $hasta, $filtro);
        $reservas = Reporte::resumenReservas($desde, $hasta, $filtro);
        $x->fila($h, 5, [$head('Compras'), $head('Ingresos tienda'), $head('Reservas'), $head('Atendidas')], 24);
        $x->fila($h, 6, [
            Xlsx::celda((int)($resumen['compras'] ?? 0), Xlsx::S_VALUE),
            Xlsx::celda((float)($resumen['monto'] ?? 0), Xlsx::S_MONEY),
            Xlsx::celda((int)($reservas['total'] ?? 0), Xlsx::S_VALUE),
            Xlsx::celda((int)($reservas['atendidas'] ?? 0), Xlsx::S_VALUE),
        ], 32);

        $x->fila($h, 8, [Xlsx::celda('RESUMEN POR SUCURSAL', Xlsx::S_SECTION)], 22);
        $x->fila($h, 9, [$head('Sucursal'), $head('Compras'), $head('Ingresos'), $head('Reservas'), $head('Atendidas'), $head('Canceladas')], 22);
        $f = 10;
        $catsIngresos = $valsIngresos = $catsReservas = $valsReservas = [];
        $valsCompras = $valsAtendidas = $valsCanceladas = $valsPendientes = [];
        foreach ($porSucursal as $s) {
            $x->fila($h, $f, [
                Xlsx::celda((string)$s['nombre_sucursal'], Xlsx::S_TEXT),
                Xlsx::celda((int)$s['compras'], Xlsx::S_INT),
                Xlsx::celda((float)$s['monto'], Xlsx::S_MONEY),
                Xlsx::celda((int)$s['reservas'], Xlsx::S_INT),
                Xlsx::celda((int)$s['atendidas'], Xlsx::S_INT),
                Xlsx::celda((int)$s['canceladas'], Xlsx::S_INT),
            ]);
            $catsIngresos[] = (string)$s['nombre_sucursal'];
            $valsIngresos[] = (float)$s['monto'];
            $catsReservas[] = (string)$s['nombre_sucursal'];
            $valsReservas[] = (int)$s['reservas'];
            $valsCompras[] = (int)$s['compras'];
            $valsAtendidas[] = (int)$s['atendidas'];
            $valsCanceladas[] = (int)$s['canceladas'];
            $valsPendientes[] = max(0, (int)$s['reservas'] - (int)$s['atendidas'] - (int)$s['canceladas']);
            $f++;
        }
        $x->fila($h, $f + 1, [Xlsx::celda('Nota: "Sin sucursal asignada" agrupa registros cargados antes de asignar una sucursal.', Xlsx::S_NOTE)]);
        $x->grafico($h, 'bar', 'Ingresos por sucursal', $catsIngresos, [
            ['name' => 'Ingresos (Bs.)', 'values' => $valsIngresos],
        ], ['c1' => 7, 'f1' => 2, 'c2' => 15, 'f2' => 17]);
        $x->grafico($h, 'bar', 'Reservas por sucursal', $catsReservas, [
            ['name' => 'Reservas', 'values' => $valsReservas],
        ], ['c1' => 7, 'f1' => 18, 'c2' => 15, 'f2' => 33], 'bar');
        $x->grafico($h, 'bar', 'Compras vs. reservas por sucursal', $catsReservas, [
            ['name' => 'Compras', 'values' => $valsCompras],
            ['name' => 'Reservas', 'values' => $valsReservas],
        ], ['c1' => 7, 'f1' => 34, 'c2' => 15, 'f2' => 49]);
        if (array_sum($valsReservas) > 0) {
            $x->grafico($h, 'pie', 'Estado de las reservas', ['Atendidas', 'Canceladas', 'Pendientes'], [
                ['name' => 'Reservas', 'values' => [
                    array_sum($valsAtendidas),
                    array_sum($valsCanceladas),
                    array_sum($valsPendientes),
                ]],
            ], ['c1' => 7, 'f1' => 50, 'c2' => 15, 'f2' => 65]);
        }

        $destinos = [];
        foreach ($sucursales as $s) {
            if ($sucursal === '' || $sucursal === $s['id_sucursal']) {
                $destinos[] = [(string)$s['nombre_sucursal'], (string)$s['id_sucursal']];
            }
        }
        foreach ($porSucursal as $s) {
            if ($s['id_sucursal'] === 'NULL' && ($sucursal === '' || $sucursal === 'NULL')) {
                $destinos[] = ['Sin sucursal asignada', 'NULL'];
            }
        }
        foreach ($destinos as [$nombre, $id]) {
            $this->hojaExcelSucursal($x, $nombre, $desde, $hasta, $id, $rango);
        }

        // Promociones vigentes leídas desde la BD y vinculadas a productos/servicios.
        $p = $x->addSheet('Promociones', [28, 26, 16, 16, 12, 15, 15, 12]);
        $x->fila($p, 1, [Xlsx::celda('TATIANA SKIN AND BEAUTY  |  PROMOCIONES VIGENTES', Xlsx::S_TITLE)], 32);
        $x->fila($p, 2, [Xlsx::celda('Generado el ' . date('d/m/Y H:i'), Xlsx::S_SUBTITLE)], 20);
        $x->fila($p, 4, [
            $head('Promoción'), $head('Artículo vinculado'), $head('Tipo'),
            $head('Precio original'), $head('Precio promo'), $head('Descuento'),
            $head('Inicio'), $head('Fin'),
        ], 24);
        $f = 5;
        $catsPromo = $valsPromo = [];
        foreach ($promociones as $promo) {
            $x->fila($p, $f, [
                Xlsx::celda((string)($promo['nombre'] ?? ''), Xlsx::S_TEXT),
                Xlsx::celda((string)($promo['articulo'] ?? ''), Xlsx::S_TEXT),
                Xlsx::celda((string)($promo['tipo_articulo'] ?? ''), Xlsx::S_TEXT),
                Xlsx::celda((float)($promo['precio_original'] ?? 0), Xlsx::S_MONEY),
                Xlsx::celda((float)($promo['precio_promocional'] ?? 0), Xlsx::S_MONEY),
                Xlsx::celda((float)($promo['descuento'] ?? 0) / 100, Xlsx::S_PCT),
                Xlsx::celda(self::fechaLarga((string)($promo['fecha_inicio'] ?? '')), Xlsx::S_TEXT),
                Xlsx::celda(self::fechaLarga((string)($promo['fecha_fin'] ?? '')), Xlsx::S_TEXT),
            ]);
            $catsPromo[] = (string)($promo['nombre'] ?? '');
            $valsPromo[] = (float)($promo['descuento'] ?? 0);
            $f++;
        }
        if ($catsPromo !== []) {
            $x->grafico($p, 'bar', 'Descuento de las promociones (%)', $catsPromo, [
                ['name' => 'Descuento (%)', 'values' => $valsPromo],
            ], ['c1' => 1, 'f1' => $f + 2, 'c2' => 9, 'f2' => $f + 17], 'bar');
        } else {
            $x->fila($p, $f + 1, [Xlsx::celda('No hay promociones vigentes en este momento.', Xlsx::S_NOTE)]);
        }

        $x->descargar('reporte_tatiana_' . $desde . '_' . $hasta . '.xlsx');
    }

    /** Hoja con el mismo formato para cada sucursal. */
    private function hojaExcelSucursal(Xlsx $x, string $nombre, string $desde, string $hasta, string $sucursal, string $rango): void
    {
        $filtro = $sucursal === '' ? null : $sucursal;
        $resumen = Reporte::resumenCompras($desde, $hasta, $filtro);
        $reservas = Reporte::resumenReservas($desde, $hasta, $filtro);
        $serie = Reporte::serie($desde, $hasta, $filtro);
        $metodos = Reporte::porMetodoPago($desde, $hasta, $filtro);
        $servicios = Reporte::porServicio($desde, $hasta, 10, $filtro);
        $productos = Reporte::porProducto($desde, $hasta, 10, $filtro);
        $clientes = Reporte::topClientes($desde, $hasta, 10, $filtro);

        $head = static fn (string $t) => Xlsx::celda($t, Xlsx::S_HEAD);
        $h = $x->addSheet($nombre, [30, 17, 13, 14, 14, 3, 17, 17, 17], 5);
        $x->fila($h, 1, [Xlsx::celda('REPORTE · ' . mb_strtoupper($nombre), Xlsx::S_TITLE)], 32);
        $x->fila($h, 2, [Xlsx::celda($rango, Xlsx::S_SUBTITLE)], 20);
        $x->fila($h, 3, [Xlsx::celda('Generado el ' . date('d/m/Y H:i'), Xlsx::S_SUBTITLE)], 18);
        $x->fila($h, 5, [$head('Compras'), $head('Ingresos tienda'), $head('Reservas'), $head('Atendidas')], 24);
        $x->fila($h, 6, [
            Xlsx::celda((int)($resumen['compras'] ?? 0), Xlsx::S_VALUE),
            Xlsx::celda((float)($resumen['monto'] ?? 0), Xlsx::S_MONEY),
            Xlsx::celda((int)($reservas['total'] ?? 0), Xlsx::S_VALUE),
            Xlsx::celda((int)($reservas['atendidas'] ?? 0), Xlsx::S_VALUE),
        ], 32);

        $f = 8;
        $x->fila($h, $f, [Xlsx::celda('INGRESOS Y RESERVAS POR PERÍODO', Xlsx::S_SECTION)], 22);
        $f += 2;
        $x->fila($h, $f, [$head('Período'), $head('Ingresos (Bs.)'), $head('Compras'), $head('Reservas')], 22);
        $f++;
        foreach ($serie['etiquetas'] as $i => $etq) {
            $x->fila($h, $f, [
                Xlsx::celda((string)$etq, Xlsx::S_TEXT),
                Xlsx::celda((float)$serie['montos'][$i], Xlsx::S_MONEY),
                Xlsx::celda((int)$serie['compras'][$i], Xlsx::S_INT),
                Xlsx::celda((int)$serie['reservas'][$i], Xlsx::S_INT),
            ]);
            $f++;
        }
        $x->grafico($h, 'bar', 'Ingresos por período', $serie['etiquetas'], [
            ['name' => 'Ingresos (Bs.)', 'values' => $serie['montos']],
        ], ['c1' => 6, 'f1' => 2, 'c2' => 15, 'f2' => 17]);
        $x->grafico($h, 'bar', 'Reservas por período', $serie['etiquetas'], [
            ['name' => 'Reservas', 'values' => $serie['reservas']],
        ], ['c1' => 6, 'f1' => 18, 'c2' => 15, 'f2' => 33]);

        $f++;
        $x->fila($h, $f, [Xlsx::celda('MÉTODOS DE PAGO', Xlsx::S_SECTION)], 22);
        $f += 2;
        $x->fila($h, $f, [$head('Método'), $head('Compras'), $head('Total')], 22);
        $f++;
        $catsMetodos = $valsMetodos = [];
        foreach ($metodos as $m) {
            $x->fila($h, $f, [
                Xlsx::celda((string)$m['nombre'], Xlsx::S_TEXT),
                Xlsx::celda((int)$m['compras'], Xlsx::S_INT),
                Xlsx::celda((float)$m['total'], Xlsx::S_MONEY),
            ]);
            $catsMetodos[] = (string)$m['nombre'];
            $valsMetodos[] = (float)$m['total'];
            $f++;
        }
        if ($catsMetodos !== []) {
            $x->grafico($h, 'pie', 'Ventas por método de pago', $catsMetodos, [
                ['name' => 'Total (Bs.)', 'values' => $valsMetodos],
            ], ['c1' => 6, 'f1' => 34, 'c2' => 15, 'f2' => 49]);
        }

        $f++;
        $x->fila($h, $f, [Xlsx::celda('SERVICIOS MÁS RESERVADOS', Xlsx::S_SECTION)], 22);
        $f += 2;
        $x->fila($h, $f, [$head('Servicio'), $head('Citas'), $head('Ingreso')], 22);
        $f++;
        $catsServ = $valsServ = [];
        foreach ($servicios as $s) {
            $x->fila($h, $f, [
                Xlsx::celda((string)$s['nombre'], Xlsx::S_TEXT),
                Xlsx::celda((int)$s['citas'], Xlsx::S_INT),
                Xlsx::celda((float)$s['total'], Xlsx::S_MONEY),
            ]);
            $catsServ[] = (string)$s['nombre'];
            $valsServ[] = (int)$s['citas'];
            $f++;
        }
        if ($catsServ !== []) {
            $x->grafico($h, 'bar', 'Servicios más reservados', $catsServ, [
                ['name' => 'Citas', 'values' => $valsServ],
            ], ['c1' => 6, 'f1' => 50, 'c2' => 15, 'f2' => 65], 'bar');
        }

        $f++;
        $x->fila($h, $f, [Xlsx::celda('PRODUCTOS MÁS VENDIDOS', Xlsx::S_SECTION)], 22);
        $f += 2;
        $x->fila($h, $f, [$head('Producto'), $head('Unidades'), $head('Total')], 22);
        $f++;
        $catsProd = $valsProd = [];
        foreach ($productos as $p) {
            $x->fila($h, $f, [
                Xlsx::celda((string)$p['nombre_producto'], Xlsx::S_TEXT),
                Xlsx::celda((int)$p['unidades'], Xlsx::S_INT),
                Xlsx::celda((float)$p['total'], Xlsx::S_MONEY),
            ]);
            $catsProd[] = (string)$p['nombre_producto'];
            $valsProd[] = (float)$p['total'];
            $f++;
        }
        if ($catsProd !== []) {
            $x->grafico($h, 'bar', 'Productos más vendidos', $catsProd, [
                ['name' => 'Total (Bs.)', 'values' => $valsProd],
            ], ['c1' => 6, 'f1' => 66, 'c2' => 15, 'f2' => 81], 'bar');
        }

        $f++;
        $x->fila($h, $f, [Xlsx::celda('CLIENTES DESTACADOS', Xlsx::S_SECTION)], 22);
        $f += 2;
        $x->fila($h, $f, [$head('Cliente'), $head('Compras'), $head('Reservas'), $head('Gastado')], 22);
        $f++;
        $catsClientes = $valsClientes = [];
        foreach ($clientes as $c) {
            $x->fila($h, $f, [
                Xlsx::celda((string)$c['cliente'], Xlsx::S_TEXT),
                Xlsx::celda((int)$c['compras'], Xlsx::S_INT),
                Xlsx::celda((int)$c['reservas'], Xlsx::S_INT),
                Xlsx::celda((float)$c['gastado'], Xlsx::S_MONEY),
            ]);
            $catsClientes[] = (string)$c['cliente'];
            $valsClientes[] = (float)$c['gastado'];
            $f++;
        }
        if ($catsClientes !== []) {
            $x->grafico($h, 'bar', 'Clientes con mayor gasto', $catsClientes, [
                ['name' => 'Gastado (Bs.)', 'values' => $valsClientes],
            ], ['c1' => 6, 'f1' => 82, 'c2' => 15, 'f2' => 97], 'bar');
        }
        if ((int)($reservas['total'] ?? 0) > 0) {
            $x->grafico($h, 'pie', 'Estado de las reservas', ['Atendidas', 'Canceladas', 'Pendientes'], [
                ['name' => 'Reservas', 'values' => [
                    (int)($reservas['atendidas'] ?? 0),
                    (int)($reservas['canceladas'] ?? 0),
                    max(0, (int)($reservas['total'] ?? 0) - (int)($reservas['atendidas'] ?? 0) - (int)($reservas['canceladas'] ?? 0)),
                ]],
            ], ['c1' => 6, 'f1' => 98, 'c2' => 15, 'f2' => 113]);
        }
    }

    private static function fechaValida(string $fecha): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }

    /** Años con actividad (ventas o reservas) para el filtro de reportes. */
    private function aniosReportes(): array
    {
        $lista = array_merge([(int)date('Y')], Compra::anios(), Reserva::anios());
        $lista = array_values(array_unique(array_filter(array_map('intval', $lista), static fn ($a) => $a > 0)));
        rsort($lista);
        return $lista;
    }

    private function exportarCSV(string $tipo, string $desde, string $hasta, ?string $sucursal = null): void
    {
        $filas = [];
        switch ($tipo) {
            case 'compras':
                $filas[] = ['N°', 'Fecha', 'Cliente', 'Método', 'Sucursal', 'Total'];
                foreach (Reporte::comprasDetalle($desde, $hasta, $sucursal) as $f) {
                    $filas[] = [$f['nro_compra'], $f['fecha_compra'], $f['cliente'], $f['nombre_metodoPago'], $f['sucursal'], $f['total_pagar']];
                }
                break;
            case 'productos':
                $filas[] = ['Producto', 'Unidades vendidas', 'Total Bs.'];
                foreach (Reporte::porProducto($desde, $hasta, null, $sucursal) as $f) {
                    $filas[] = [$f['nombre_producto'], $f['unidades'], $f['total']];
                }
                break;
            case 'servicios':
                $filas[] = ['Servicio', 'Citas', 'Total Bs.'];
                foreach (Reporte::porServicio($desde, $hasta, null, $sucursal) as $f) {
                    $filas[] = [$f['nombre'], $f['citas'], $f['total']];
                }
                break;
            default:
                Flash::error('Tipo de reporte no válido.');
                $this->redirigir('admin_reportes');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . $desde . '_' . $hasta . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($filas as $fila) {
            fputcsv($out, $fila, ';');
        }
        fclose($out);
        exit;
    }

    public function notificaciones(): void
    {
        $this->vista('admin/notificaciones.php', [
            'pendientes' => Reserva::pendientesPago(),
            'stockBajo'  => Producto::stockBajo(),
            'citasHoy'   => Reserva::resumenDelDia(date('Y-m-d')),
        ]);
    }

    public function perfil(): void
    {
        $id = Auth::id();
        $errores = [];

        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'datos') {
                $v = Validate::make()
                    ->texto('nombre', 'El nombre', 2, 50)
                    ->texto('ap_paterno', 'El apellido paterno', 2, 50)
                    ->telefono('telefono');
                if ($v->fallido()) {
                    $errores = $v->errores();
                } else {
                    Usuario::actualizarPerfilPersonal($id, [
                        'nombre'     => trim((string)$_POST['nombre']),
                        'ap_paterno' => trim((string)$_POST['ap_paterno']),
                        'ap_materno' => trim((string)($_POST['ap_materno'] ?? '')),
                        'telefono'   => trim((string)($_POST['telefono'] ?? '')),
                    ]);
                    Flash::ok('Datos actualizados.');
                    $this->redirigir('admin_perfil');
                }
            } elseif ($accion === 'password') {
                $errores = $this->cambiarPassword($id);
                if ($errores === []) {
                    Flash::ok('Contraseña actualizada.');
                    $this->redirigir('admin_perfil');
                }
            }
        }

        $usuario = Usuario::porId($id);
        $cuenta = ['correo' => Usuario::correoDe($id)];
        $this->vista('admin/perfil.php', compact('usuario', 'cuenta', 'errores'));
    }

    private function cambiarPassword(string $id): array
    {
        $actual = (string)($_POST['password_actual'] ?? '');
        $nueva = (string)($_POST['password_nueva'] ?? '');
        $rep = (string)($_POST['password_rep'] ?? '');

        $v = Validate::make();
        if (!Auth::verificarPassword($actual, Usuario::passwordDe($id), Usuario::correoDe($id))) {
            $v->fallo('password_actual', 'La contraseña actual no es correcta.');
        } elseif (strlen($nueva) < 8) {
            $v->fallo('password_nueva', 'La nueva contraseña debe tener al menos 8 caracteres.');
        } elseif ($nueva !== $rep) {
            $v->fallo('password_rep', 'Las contraseñas no coinciden.');
        } else {
            Usuario::cambiarPassword($id, $nueva);
        }
        return $v->errores();
    }

    public function config(): void
    {
        if ($this->esPost()) {
            $accion = $this->texto('accion');
            if ($accion === 'crear') {
                $nombre = $this->texto('nombre_metodoPago');
                if (mb_strlen($nombre) < 2) {
                    Flash::error('El nombre del método de pago debe tener al menos 2 caracteres.');
                } elseif (MetodoPago::existeNombre($nombre)) {
                    Flash::error('Ya existe un método de pago con ese nombre.');
                } else {
                    MetodoPago::crear($nombre, $this->texto('tipo'));
                    Flash::ok('Método de pago creado: ' . $nombre);
                }
            } elseif ($accion === 'estado') {
                $nuevo = $this->texto('nuevo_estado') === 'inactivo' ? 'inactivo' : 'activo';
                MetodoPago::cambiarEstado($this->texto('id_metodoPago'), $nuevo);
                Flash::ok('Estado actualizado.');
            } elseif ($accion === 'eliminar') {
                $id = $this->texto('id_metodoPago');
                $uso = MetodoPago::usos($id);
                if ($uso > 0) {
                    Flash::error('No se puede eliminar: ' . $uso . ' registro(s) lo usan. Desactívalo en su lugar.');
                } else {
                    MetodoPago::eliminar($id);
                    Flash::ok('Método de pago eliminado.');
                }
            }
            $this->redirigir('admin_config');
        }

        $this->vista('admin/config.php', ['metodos' => MetodoPago::conUso()]);
    }
}
