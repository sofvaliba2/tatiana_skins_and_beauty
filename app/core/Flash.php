<?php
declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function ok(string $msg): void
    {
        $_SESSION['flash_ok'][] = $msg;
    }

    public static function error(string $msg): void
    {
        $_SESSION['flash_err'][] = $msg;
    }

    public static function pull(): array
    {
        $ok = $_SESSION['flash_ok'] ?? [];
        $err = $_SESSION['flash_err'] ?? [];
        unset($_SESSION['flash_ok'], $_SESSION['flash_err']);
        return ['ok' => $ok, 'err' => $err];
    }
}
