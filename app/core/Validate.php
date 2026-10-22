<?php
declare(strict_types=1);

namespace App\Core;

final class Validate
{
    private array $errores = [];

    public static function make(): self
    {
        return new self();
    }

    public function texto(string $campo, string $etiqueta, int $min = 1, int $max = 255): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        if ($v === '' && $min > 0) {
            $this->fallo($campo, "El campo {$etiqueta} es obligatorio.");
        } elseif (mb_strlen($v) < $min) {
            $this->fallo($campo, "{$etiqueta} debe tener al menos {$min} caracteres.");
        } elseif (mb_strlen($v) > $max) {
            $this->fallo($campo, "{$etiqueta} no puede superar {$max} caracteres.");
        }
        return $this;
    }

    public function correo(string $campo = 'correo'): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        if ($v === '') {
            $this->fallo($campo, 'El correo electrónico es obligatorio.');
        } elseif (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->fallo($campo, 'Ingresa un correo electrónico válido.');
        }
        return $this;
    }

    public function telefono(string $campo = 'telefono'): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        if ($v !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $v)) {
            $this->fallo($campo, 'El teléfono solo puede contener números, espacios, + o -.');
        }
        return $this;
    }

    public function fecha(string $campo, string $etiqueta, bool $obligatoria = false): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        if ($v === '') {
            if ($obligatoria) {
                $this->fallo($campo, "La fecha {$etiqueta} es obligatoria.");
            }
            return $this;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            $this->fallo($campo, "La fecha {$etiqueta} no es válida.");
        }
        return $this;
    }

    public function numero(string $campo, string $etiqueta, float $min = 0, ?float $max = null, bool $obligatorio = true): self
    {
        $raw = $_POST[$campo] ?? '';
        if ($raw === '' || $raw === null) {
            if ($obligatorio) {
                $this->fallo($campo, "El campo {$etiqueta} es obligatorio.");
            }
            return $this;
        }
        if (!is_numeric($raw)) {
            $this->fallo($campo, "{$etiqueta} debe ser un número.");
            return $this;
        }
        $n = (float)$raw;
        if ($n < $min) {
            $this->fallo($campo, "{$etiqueta} no puede ser menor a {$min}.");
        } elseif ($max !== null && $n > $max) {
            $this->fallo($campo, "{$etiqueta} no puede superar {$max}.");
        }
        return $this;
    }

    public function entero(string $campo, string $etiqueta, int $min = 0, ?int $max = null, bool $obligatorio = true): self
    {
        $raw = $_POST[$campo] ?? '';
        if ($raw === '' || $raw === null) {
            if ($obligatorio) {
                $this->fallo($campo, "El campo {$etiqueta} es obligatorio.");
            }
            return $this;
        }
        if (!preg_match('/^-?\d+$/', (string)$raw)) {
            $this->fallo($campo, "{$etiqueta} debe ser un número entero.");
            return $this;
        }
        $n = (int)$raw;
        if ($n < $min) {
            $this->fallo($campo, "{$etiqueta} no puede ser menor a {$min}.");
        } elseif ($max !== null && $n > $max) {
            $this->fallo($campo, "{$etiqueta} no puede superar {$max}.");
        }
        return $this;
    }

    public function opcion(string $campo, string $etiqueta, array $permitidas): self
    {
        $v = (string)($_POST[$campo] ?? '');
        if ($v === '') {
            $this->fallo($campo, "Selecciona {$etiqueta}.");
        } elseif (!in_array($v, $permitidas, true)) {
            $this->fallo($campo, "Valor no permitido en {$etiqueta}.");
        }
        return $this;
    }

    public function unico(string $tabla, string $columna, string $campo, string $etiqueta, ?string $excluirValor = null, ?string $excluirColumna = null): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        if ($v === '') {
            return $this;
        }
        $sql = "SELECT COUNT(*) FROM `{$tabla}` WHERE `{$columna}` = ?";
        $params = [$v];
        if ($excluirValor !== null && $excluirColumna !== null) {
            $sql .= " AND `{$excluirColumna}` <> ?";
            $params[] = $excluirValor;
        }
        if ((int)Database::value($sql, $params) > 0) {
            $this->fallo($campo, "Ya existe un registro con ese valor de {$etiqueta}.");
        }
        return $this;
    }

    public function fallo(string $campo, string $msg): void
    {
        $this->errores[$campo][] = $msg;
    }

    public function falloGeneral(string $msg): void
    {
        $this->errores['_general'][] = $msg;
    }

    public function fallido(): bool
    {
        return $this->errores !== [];
    }

    public function errores(): array
    {
        return $this->errores;
    }

    public function mensajes(): array
    {
        $todos = [];
        foreach ($this->errores as $lista) {
            foreach ($lista as $m) {
                $todos[] = $m;
            }
        }
        return $todos;
    }

    public function errorDe(string $campo): string
    {
        return $this->errores[$campo][0] ?? '';
    }

    public static function viejo(string $campo, string $def = ''): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return trim((string)($_POST[$campo] ?? $def));
        }
        return $def;
    }
}
