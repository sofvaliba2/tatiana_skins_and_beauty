<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\ErroresController;

final class Router
{
    /**
     * Roles autorizados para cada nivel de ruta.
     * null significa acceso público.
     */
    private const NIVELES = [
        'publico'          => null,
        'autenticado'      => ['ROL01', 'ROL02', 'ROL03'],
        'admin'            => ['ROL01'],
        'empleado'         => ['ROL02'],
        'cliente'          => ['ROL03'],
        'cliente_personal' => ['ROL01', 'ROL02', 'ROL03'],
        'personal'         => ['ROL01', 'ROL02'],
    ];

    public function __construct(private array $rutas)
    {
    }

    public function despachar(): void
    {
        $vista = (string)($_GET['v'] ?? 'home');

        if (!isset($this->rutas[$vista])) {
            (new ErroresController())->noEncontrado();
            return;
        }

        [$clase, $metodo, $nivel] = $this->rutas[$vista];
        $usuario = Auth::usuario();

        // Seguimiento de la ruta interna anterior para los botones "Regresar".
        // Solo se actualiza en GET (nunca en POST ni en descargas) para que un
        // formulario o un export no reemplace el destino de regreso.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
            && !isset($_GET['export'])
            && !in_array($vista, ['login', 'restablecer'], true)) {
            $actual = $_SESSION['ruta_actual'] ?? '';
            if (is_array($actual) && ($actual['v'] ?? '') !== '' && $actual['v'] !== $vista) {
                $_SESSION['ruta_anterior'] = $actual;
            }
            $params = $_GET;
            unset($params['v'], $params['export'], $params['token'], $params['_csrf']);
            $_SESSION['ruta_actual'] = ['v' => $vista, 'params' => $params];
        }

        if (!array_key_exists($nivel, self::NIVELES)) {
            error_log('[ROUTER] Nivel de acceso desconocido: ' . $nivel);
            (new ErroresController())->denegado();
            return;
        }

        if ($nivel !== 'publico' && $usuario === null) {
            Flash::error('Inicia sesión para continuar.');
            $this->ir('login', ['next' => $vista]);
        }

        $rol = (string)($usuario['id_rol'] ?? '');
        $permitidos = self::NIVELES[$nivel];
        if ($permitidos !== null && !in_array($rol, $permitidos, true)) {
            (new ErroresController())->denegado();
            return;
        }

        if (in_array($vista, ['login', 'registro'], true) && $usuario !== null) {
            $this->ir($this->destinoPorRol($rol));
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !Csrf::validar()) {
            http_response_code(403);
            exit('Sesión expirada o envío no válido. <a href="' . BASE_URL . '/index.php?v=home">Volver al inicio</a>');
        }

        try {
            (new $clase())->$metodo();
        } catch (\Throwable $e) {
            error_log('[APP] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            (new ErroresController())->error($e);
        }
    }

    private function destinoPorRol(string $rol): string
    {
        return match ($rol) {
            'ROL01' => 'admin_dashboard',
            'ROL02' => 'empleado_dashboard',
            'ROL03' => 'panel',
            default => 'home',
        };
    }

    private function ir(string $vista, array $params = []): never
    {
        $url = BASE_URL . '/index.php?v=' . urlencode($vista);
        foreach ($params as $k => $val) {
            $url .= '&' . urlencode($k) . '=' . urlencode((string)$val);
        }
        header('Location: ' . $url);
        exit;
    }
}
