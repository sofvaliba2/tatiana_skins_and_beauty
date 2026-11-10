<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\ErroresController;

final class Router
{
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

        if ($nivel !== 'publico' && $usuario === null) {
            Flash::error('Inicia sesión para continuar.');
            $this->ir('login', ['next' => $vista]);
        }
        $rol = $usuario['id_rol'] ?? '';
        if (($nivel === 'admin' && $rol !== 'ROL01')
            || ($nivel === 'personal' && !in_array($rol, ['ROL01', 'ROL02'], true))) {
            (new ErroresController())->denegado();
            return;
        }
        if (in_array($vista, ['login', 'registro'], true) && $usuario !== null) {
            $this->ir($rol === 'ROL03' ? 'panel' : 'admin_dashboard');
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
