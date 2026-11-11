<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;

final class Usuario extends Model
{
    public static function cuentaPorCorreo(string $correo): ?array
    {
        return self::one(
            'SELECT c.id_cuentaUsuario, c.correo, c.password, c.id_rol, c.id_usuario,
                    r.nombre_rol,
                    u.nombre, u.ap_paterno, u.ap_materno, u.telefono, u.id_sucursal,
                    s.nombre_sucursal
             FROM cuenta_usuario c
             JOIN rol r ON r.id_rol = c.id_rol
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
             LEFT JOIN sucursal s ON s.id_sucursal = u.id_sucursal
             WHERE c.correo = ?
             LIMIT 1',
            [mb_strtolower(trim($correo))]
        );
    }

    public static function correoDe(string $idUsuario): string
    {
        return (string)self::value('SELECT correo FROM cuenta_usuario WHERE id_usuario = ?', [$idUsuario]);
    }

    public static function passwordDe(string $idUsuario): string
    {
        return (string)self::value('SELECT password FROM cuenta_usuario WHERE id_usuario = ?', [$idUsuario]);
    }

    public static function cambiarPassword(string $idUsuario, string $password): void
    {
        $correo = self::correoDe($idUsuario);
        self::run('UPDATE cuenta_usuario SET password = ? WHERE id_usuario = ?', [Auth::hashPassword($password, $correo), $idUsuario]);
    }

    public static function porId(string $idUsuario): ?array
    {
        return self::one('SELECT * FROM usuario WHERE id_usuario = ?', [$idUsuario]);
    }

    public static function contarClientes(): int
    {
        return (int)self::value("SELECT COUNT(*) FROM usuario WHERE id_rol = 'ROL03'");
    }

    public static function resumenCliente(string $idUsuario): array
    {
        return self::one(
            'SELECT (SELECT COUNT(*) FROM reserva WHERE id_usuario = ?) AS reservas,
                    (SELECT COUNT(*) FROM compra WHERE id_usuario = ?) AS compras,
                    (SELECT COALESCE(SUM(total_pagar),0) FROM compra WHERE id_usuario = ?) AS gastado',
            [$idUsuario, $idUsuario, $idUsuario]
        ) ?? ['reservas' => 0, 'compras' => 0, 'gastado' => 0];
    }

    public static function listaClientes(): array
    {
        return self::all("SELECT id_usuario, nombre, ap_paterno FROM usuario WHERE id_rol = 'ROL03' ORDER BY nombre");
    }

    /** Clientas vinculadas a una sucursal (por asignación directa o por historial de citas). */
    public static function listaClientesDeSucursal(string $idSucursal): array
    {
        return self::all(
            "SELECT u.id_usuario, u.nombre, u.ap_paterno
             FROM usuario u
             WHERE u.id_rol = 'ROL03'
               AND (u.id_sucursal = ? OR EXISTS (
                   SELECT 1 FROM reserva r
                   WHERE r.id_usuario = u.id_usuario AND r.id_sucursal = ?
               ))
             ORDER BY u.nombre, u.ap_paterno",
            [$idSucursal, $idSucursal]
        );
    }

    /** Búsqueda de clientas limitada a la sucursal del empleado. */
    public static function buscarClientesDeSucursal(string $q, int $pagina, int $porPagina, string $idSucursal): array
    {
        $sql = "SELECT u.*, c.correo,
                       (SELECT COUNT(*) FROM reserva r2
                        WHERE r2.id_usuario = u.id_usuario AND r2.id_sucursal = ?) AS reservas
                FROM usuario u
                LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
                WHERE u.id_rol = 'ROL03'
                  AND (u.id_sucursal = ? OR EXISTS (
                      SELECT 1 FROM reserva r
                      WHERE r.id_usuario = u.id_usuario AND r.id_sucursal = ?
                  ))";
        $params = [$idSucursal, $idSucursal, $idSucursal];
        if ($q !== '') {
            $sql .= ' AND (u.nombre LIKE ? OR u.ap_paterno LIKE ? OR u.ap_materno LIKE ? OR u.telefono LIKE ? OR c.correo LIKE ?)';
            for ($i = 0; $i < 5; $i++) {
                $params[] = '%' . $q . '%';
            }
        }
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY u.nombre, u.ap_paterno LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    public static function clienteDeSucursal(string $idUsuario, string $idSucursal): ?array
    {
        return self::one(
            'SELECT u.*, c.correo
             FROM usuario u
             LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
             WHERE u.id_usuario = ? AND u.id_rol = "ROL03"
               AND (u.id_sucursal = ? OR EXISTS (
                   SELECT 1 FROM reserva r
                   WHERE r.id_usuario = u.id_usuario AND r.id_sucursal = ?
               ))',
            [$idUsuario, $idSucursal, $idSucursal]
        );
    }

    public static function contarClientesDeSucursal(string $idSucursal): int
    {
        return (int)self::value(
            "SELECT COUNT(*) FROM usuario u
             WHERE u.id_rol = 'ROL03'
               AND (u.id_sucursal = ? OR EXISTS (
                   SELECT 1 FROM reserva r
                   WHERE r.id_usuario = u.id_usuario AND r.id_sucursal = ?
               ))",
            [$idSucursal, $idSucursal]
        );
    }

    public static function buscarClientes(string $q, int $pagina, int $porPagina): array
    {
        $sql = "SELECT u.*, c.correo,
                       (SELECT COUNT(*) FROM reserva r WHERE r.id_usuario = u.id_usuario) AS reservas
                FROM usuario u
                LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
                WHERE u.id_rol = 'ROL03'";
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (u.nombre LIKE ? OR u.ap_paterno LIKE ? OR u.ap_materno LIKE ? OR u.telefono LIKE ? OR c.correo LIKE ?)';
            for ($i = 0; $i < 5; $i++) {
                $params[] = '%' . $q . '%';
            }
        }
        $total = self::contar($sql, $params);
        $sql .= ' ORDER BY u.nombre, u.ap_paterno LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        return ['filas' => self::all($sql, $params), 'total' => $total];
    }

    public static function clienteConCorreo(string $idUsuario): ?array
    {
        return self::one(
            'SELECT u.*, c.correo FROM usuario u
             LEFT JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
             WHERE u.id_usuario = ? AND u.id_rol = "ROL03"',
            [$idUsuario]
        );
    }

    public static function personal(): array
    {
        return self::all(
            'SELECT u.*, c.correo, r.nombre_rol, s.nombre_sucursal
             FROM usuario u
             JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
             JOIN rol r ON r.id_rol = u.id_rol
             LEFT JOIN sucursal s ON s.id_sucursal = u.id_sucursal
             WHERE u.id_rol IN ("ROL01","ROL02")
             ORDER BY u.nombre'
        );
    }

    /** Empleados (ROL02) con su cuenta y sucursal. */
    public static function empleados(): array
    {
        return self::all(
            'SELECT u.*, c.correo, r.nombre_rol, s.nombre_sucursal
             FROM usuario u
             JOIN cuenta_usuario c ON c.id_usuario = u.id_usuario
             JOIN rol r ON r.id_rol = u.id_rol
             LEFT JOIN sucursal s ON s.id_sucursal = u.id_sucursal
             WHERE u.id_rol = "ROL02"
             ORDER BY u.nombre, u.ap_paterno'
        );
    }
    public static function actualizarPerfilCliente(string $idUsuario, array $d): void
    {
        self::run(
            'UPDATE usuario SET nombre = ?, ap_paterno = ?, ap_materno = ?, telefono = ?, fecha_nacimiento = ? WHERE id_usuario = ?',
            [$d['nombre'], $d['ap_paterno'], $d['ap_materno'], $d['telefono'], $d['fecha_nacimiento'], $idUsuario]
        );
    }

    public static function actualizarPerfilPersonal(string $idUsuario, array $d): void
    {
        self::run(
            'UPDATE usuario SET nombre = ?, ap_paterno = ?, ap_materno = ?, telefono = ? WHERE id_usuario = ?',
            [$d['nombre'], $d['ap_paterno'], $d['ap_materno'], $d['telefono'], $idUsuario]
        );
    }

    public static function crearCliente(array $datos, string $correo, string $password): string
    {
        return (string)self::transaccion(fn () => self::crearConCuenta('CLI', 4, 'ROL03', $datos, $correo, $password));
    }

    public static function actualizarCliente(string $idUsuario, array $d): void
    {
        self::run(
            'UPDATE usuario SET nombre = ?, ap_paterno = ?, ap_materno = ?, telefono = ?, fecha_nacimiento = ? WHERE id_usuario = ? AND id_rol = "ROL03"',
            [$d['nombre'], $d['ap_paterno'], $d['ap_materno'], $d['telefono'], $d['fecha_nacimiento'], $idUsuario]
        );
    }

    public static function guardarPersonal(?string $idEditar, array $datos, ?string $correo, ?string $password, ?string $nuevaPassword): array
    {
        return self::transaccion(function () use ($idEditar, $datos, $correo, $password, $nuevaPassword) {
            $creado = ($idEditar === null);
            if ($creado) {
                $id = self::crearConCuenta('EMP', 3, (string)$datos['id_rol'], $datos, (string)$correo, (string)$password);
            } else {
                $id = $idEditar;
                self::run('UPDATE usuario SET ' . self::sets($datos) . ' WHERE id_usuario = ?', [...array_values($datos), $id]);
                self::run('UPDATE cuenta_usuario SET id_rol = ? WHERE id_usuario = ?', [$datos['id_rol'], $id]);
            }
            $cambiada = false;
            if ($nuevaPassword !== null && strlen($nuevaPassword) >= 8) {
                self::cambiarPassword($id, $nuevaPassword);
                $cambiada = true;
            }
            return ['id' => $id, 'creado' => $creado, 'passwordCambiada' => $cambiada];
        });
    }
    public static function crearOReiniciarAdmin(string $correo, string $nombre, string $apPaterno, string $password): array
    {
        $existe = self::one('SELECT id_usuario FROM cuenta_usuario WHERE correo = ?', [$correo]);
        if ($existe !== null) {
            self::run('UPDATE cuenta_usuario SET password = ?, id_rol = "ROL01" WHERE correo = ?', [Auth::hashPassword($password, $correo), $correo]);
            self::run('UPDATE usuario SET id_rol = "ROL01" WHERE id_usuario = ?', [$existe['id_usuario']]);
            return ['id' => (string)$existe['id_usuario'], 'creado' => false];
        }
        $id = self::crearConCuenta('ADM', 3, 'ROL01', [
            'nombre'           => $nombre,
            'ap_paterno'       => $apPaterno,
            'ap_materno'       => '',
            'telefono'         => '',
            'fecha_nacimiento' => null,
            'id_sucursal'      => null,
        ], $correo, $password);
        return ['id' => $id, 'creado' => true];
    }

    private static function crearConCuenta(string $prefijo, int $ancho, string $idRol, array $datos, string $correo, string $password): string
    {
        $correo = mb_strtolower(trim($correo));
        $datos['id_rol'] = $idRol;
        $datos += ['id_sucursal' => null];
        $id = Ids::insertarConCodigo('usuario', 'id_usuario', $prefijo, $ancho, $datos);
        Ids::insertarConEntero('cuenta_usuario', 'id_cuentaUsuario', [
            'correo'     => $correo,
            'password'   => Auth::hashPassword($password, $correo),
            'id_rol'     => $idRol,
            'id_usuario' => $id,
        ]);
        return $id;
    }
}
