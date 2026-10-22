<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;

abstract class Controller
{
    protected function vista(string $archivo, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require BASE_PATH . '/app/views/' . $archivo;
    }

    protected function redirigir(string $vista, array $params = []): never
    {
        $url = BASE_URL . '/index.php?v=' . urlencode($vista);
        foreach ($params as $k => $val) {
            $url .= '&' . urlencode($k) . '=' . urlencode((string)$val);
        }
        header('Location: ' . $url);
        exit;
    }

    protected function json(array $datos, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function metodo(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    protected function esPost(): bool
    {
        return $this->metodo() === 'POST';
    }

    protected function entero(string $clave, int $def = 0): int
    {
        return (int)($_GET[$clave] ?? $_POST[$clave] ?? $def);
    }

    protected function texto(string $clave, string $def = ''): string
    {
        return trim((string)($_GET[$clave] ?? $_POST[$clave] ?? $def));
    }

    protected function exito(string $msg): never
    {
        Flash::ok($msg);
        $this->redirigir($this->texto('v', 'home'), array_diff_key($_GET, ['v' => 1]));
    }
}
