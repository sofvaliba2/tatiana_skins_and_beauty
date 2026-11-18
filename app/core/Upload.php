<?php
declare(strict_types=1);

namespace App\Core;

final class Upload
{
    /** Tipos de imagen aceptados y su extensión. */
    private const PERMITIDOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public static function imagen(array $file, string $destinoRelativo, ?string &$error = null): ?string
    {
        $info = self::validar($file, $error);
        if ($info === null) {
            return null;
        }
        $destinoRelativo = self::rutaConExtension($destinoRelativo, $info['extension']);
        if (!self::mover($file['tmp_name'], $destinoRelativo, $error)) {
            return null;
        }
        return $destinoRelativo;
    }

    /**
     * Valida la imagen, guarda la copia en disco y además devuelve sus datos
     * para almacenarlos en MySQL (comprobantes de pago).
     *
     * @return array{ruta:string,nombre:string,tipo:string,datos:string}|null
     */
    public static function imagenConDatos(array $file, string $destinoRelativo, ?string &$error = null): ?array
    {
        $info = self::validar($file, $error);
        if ($info === null) {
            return null;
        }

        $contenido = @file_get_contents($file['tmp_name']);
        if ($contenido === false || $contenido === '') {
            $error = 'No se pudo leer la imagen recibida.';
            return null;
        }

        $destinoRelativo = self::rutaConExtension($destinoRelativo, $info['extension']);
        if (!self::mover($file['tmp_name'], $destinoRelativo, $error)) {
            return null;
        }

        return [
            'ruta'   => $destinoRelativo,
            'nombre' => self::nombreOriginal((string)($file['name'] ?? '')),
            'tipo'   => $info['mime'],
            'datos'  => $contenido,
        ];
    }

    /**
     * @return array{mime:string,extension:string}|null
     */
    private static function validar(array $file, ?string &$error = null): ?array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            $error = 'No se recibió ningún archivo.';
            return null;
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Selecciona una imagen.';
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'No se pudo subir el archivo (código ' . $file['error'] . ').';
            return null;
        }
        if ((int)$file['size'] > 2 * 1024 * 1024) {
            $error = 'La imagen supera el máximo de 2 MB.';
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        if (!isset(self::PERMITIDOS[$mime])) {
            $error = 'Formato no permitido. Usa JPG, PNG o WEBP.';
            return null;
        }
        return ['mime' => $mime, 'extension' => self::PERMITIDOS[$mime]];
    }

    private static function rutaConExtension(string $destinoRelativo, string $extension): string
    {
        if (pathinfo($destinoRelativo, PATHINFO_EXTENSION) === '') {
            $destinoRelativo .= '.' . $extension;
        }
        return $destinoRelativo;
    }

    private static function mover(string $origen, string $destinoRelativo, ?string &$error = null): bool
    {
        $destino = BASE_PATH . '/public/' . $destinoRelativo;
        $dir = dirname($destino);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            $error = 'No se pudo crear la carpeta de destino.';
            return false;
        }
        if (!move_uploaded_file($origen, $destino)) {
            $error = 'No se pudo guardar la imagen.';
            return false;
        }
        return true;
    }

    /** Nombre original del archivo, saneado y limitado a 255 caracteres. */
    private static function nombreOriginal(string $nombre): string
    {
        $nombre = trim(str_replace(["\0", '/', '\\'], ' ', $nombre));
        if ($nombre === '') {
            return 'comprobante';
        }
        return mb_substr($nombre, 0, 255);
    }
}
