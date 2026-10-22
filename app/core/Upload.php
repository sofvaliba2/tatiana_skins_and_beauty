<?php
declare(strict_types=1);

namespace App\Core;

final class Upload
{
    
    public static function imagen(array $file, string $destinoRelativo, ?string &$error = null): ?string
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
        if ($file['size'] > 2 * 1024 * 1024) {
            $error = 'La imagen supera el máximo de 2 MB.';
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($permitidos[$mime])) {
            $error = 'Formato no permitido. Usa JPG, PNG o WEBP.';
            return null;
        }
        if (pathinfo($destinoRelativo, PATHINFO_EXTENSION) === '') {
            $destinoRelativo .= '.' . $permitidos[$mime];
        }
        $destino = BASE_PATH . '/public/' . $destinoRelativo;
        $dir = dirname($destino);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            $error = 'No se pudo crear la carpeta de destino.';
            return null;
        }
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $error = 'No se pudo guardar la imagen.';
            return null;
        }
        return $destinoRelativo;
    }
}
