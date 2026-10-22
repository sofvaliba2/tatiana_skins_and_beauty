<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

final class Errores extends Controller
{
    public function noEncontrado(): void
    {
        http_response_code(404);
        $this->vista('errores/404.php');
    }

    public function denegado(): void
    {
        http_response_code(403);
        $this->vista('errores/403.php');
    }

    public function error(?\Throwable $e = null): void
    {
        http_response_code(500);
        $detalle = null;
        if ($e !== null && ini_get('display_errors')) {
            $detalle = $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine();
        }
        $this->vista('errores/500.php', ['detalle' => $detalle]);
    }
}
