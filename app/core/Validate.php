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

    /**
     * Nombres y apellidos: solo letras (incluye acentos y ñ), espacios,
     * apóstrofes o guiones. Acepta nombres compuestos.
     */
    public function nombrePersona(string $campo, string $etiqueta, int $min = 2, int $max = 60, bool $obligatorio = true): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
        if ($v === '') {
            if ($obligatorio) {
                $this->fallo($campo, "El campo {$etiqueta} es obligatorio.");
            }
            return $this;
        }
        if (mb_strlen($v) < $min) {
            $this->fallo($campo, "{$etiqueta} debe tener al menos {$min} caracteres.");
        } elseif (mb_strlen($v) > $max) {
            $this->fallo($campo, "{$etiqueta} no puede superar {$max} caracteres.");
        } elseif (!preg_match("/^[\p{L}\s'’\-]+$/u", $v)) {
            $this->fallo($campo, "{$etiqueta} solo puede contener letras, espacios, apóstrofes o guiones (sin números ni símbolos).");
        }
        return $this;
    }

    /** Deja solo los 8 dígitos del celular boliviano (quita +591, espacios y guiones). */
    public static function normalizarTelefono(string $valor): string
    {
        $v = preg_replace('/[\s\-\(\)\.]/', '', trim($valor)) ?? '';
        if (str_starts_with($v, '+591')) {
            $v = substr($v, 4);
        } elseif (str_starts_with($v, '591') && strlen($v) === 11) {
            $v = substr($v, 3);
        }
        return preg_replace('/\D/', '', $v) ?? '';
    }

    public function telefonoBolivia(string $campo = 'telefono', bool $obligatorio = true): self
    {
        $v = self::normalizarTelefono((string)($_POST[$campo] ?? ''));
        if ($v === '') {
            if ($obligatorio) {
                $this->fallo($campo, 'El teléfono es obligatorio.');
            }
            return $this;
        }
        if (!preg_match('/^\d{8}$/', $v)) {
            $this->fallo($campo, 'El teléfono debe tener exactamente 8 dígitos, sin el prefijo +591. Ejemplo: 71234567.');
        }
        return $this;
    }

    /** Fecha de nacimiento real, pasada y con mayoría de edad. */
    public function fechaNacimiento(string $campo = 'fecha_nacimiento', bool $obligatoria = true, int $edadMinima = 18): self
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        if ($v === '') {
            if ($obligatoria) {
                $this->fallo($campo, 'La fecha de nacimiento es obligatoria.');
            }
            return $this;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            $this->fallo($campo, 'La fecha de nacimiento no es válida.');
            return $this;
        }
        $hoy = new \DateTime('today');
        if ($d > $hoy) {
            $this->fallo($campo, 'La fecha de nacimiento no puede ser futura.');
            return $this;
        }
        if ($d->diff($hoy)->y < $edadMinima) {
            $this->fallo($campo, "Debes tener al menos {$edadMinima} años para registrarte.");
        }
        return $this;
    }

    public function passwordSegura(string $campo = 'password', int $min = 8): self
    {
        $v = (string)($_POST[$campo] ?? '');
        if ($v === '') {
            $this->fallo($campo, 'La contraseña es obligatoria.');
            return $this;
        }
        if (strlen($v) < $min) {
            $this->fallo($campo, "La contraseña debe tener al menos {$min} caracteres.");
        } elseif (!preg_match('/[A-Z]/', $v)) {
            $this->fallo($campo, 'La contraseña debe incluir al menos una letra mayúscula.');
        } elseif (!preg_match('/[a-z]/', $v)) {
            $this->fallo($campo, 'La contraseña debe incluir al menos una letra minúscula.');
        } elseif (!preg_match('/\d/', $v)) {
            $this->fallo($campo, 'La contraseña debe incluir al menos un número.');
        }
        return $this;
    }

    public function coincide(string $campo, string $otroCampo, string $mensaje): self
    {
        if ((string)($_POST[$campo] ?? '') !== (string)($_POST[$otroCampo] ?? '')) {
            $this->fallo($campo, $mensaje);
        }
        return $this;
    }

    /** Valida una lista de checkboxes contra valores permitidos. */
    public function opcionesRequeridas(string $campo, string $etiqueta, array $permitidas, bool $obligatorio = true): self
    {
        $valores = array_values(array_filter((array)($_POST[$campo] ?? []), 'is_string'));
        if ($valores === []) {
            if ($obligatorio) {
                $this->fallo($campo, "Selecciona al menos una opción en {$etiqueta}.");
            }
            return $this;
        }
        foreach ($valores as $v) {
            if (!in_array($v, $permitidas, true)) {
                $this->fallo($campo, "Valor no permitido en {$etiqueta}.");
                return $this;
            }
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
        if (\App\Models\Model::existeValor($tabla, $columna, $v, $excluirColumna, $excluirValor)) {
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
