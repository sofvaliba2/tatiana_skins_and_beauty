<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Envío de correo del sistema usando mail() (sin dependencias externas).
 * Para producción basta configurar sendmail_path o SMTP en php.ini y
 * las variables MAIL_FROM / MAIL_FROM_NAME en .env.
 */
final class Correo
{
    public static function enviar(string $para, string $asunto, string $html, string $texto = ''): bool
    {
        $para = trim($para);
        if ($para === '' || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $from = trim(MAIL_FROM);
        $fromName = trim(MAIL_FROM_NAME);
        $nombreCodificado = $fromName !== '' && preg_match('/[^\x20-\x7E]/', $fromName)
            ? '=?UTF-8?B?' . base64_encode($fromName) . '?='
            : $fromName;

        $salto = "\r\n";
        $cabeceras = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . ($nombreCodificado !== '' ? $nombreCodificado . ' <' . $from . '>' : $from),
            'Reply-To: ' . $from,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $cuerpo = $html;
        if ($texto === '') {
            $texto = strip_tags(str_replace(['<br>', '</p>'], "\n", $html));
        }

        $destino = filter_var($para, FILTER_SANITIZE_EMAIL) ?: $para;
        return @mail($destino, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo, implode($salto, $cabeceras));
    }

    /** URL absoluta (si MAIL_LINK_BASE está configurada) para el enlace de recuperación. */
    public static function enlaceRestablecer(string $token): string
    {
        $relativa = BASE_URL . '/index.php?v=restablecer&token=' . urlencode($token);
        $base = rtrim(MAIL_LINK_BASE, '/');
        return $base !== '' ? $base . $relativa : $relativa;
    }

    public static function plantillaRecuperacion(string $nombre, string $enlace): string
    {
        $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $enlace = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');
        return '<div style="font-family:Jost,Arial,sans-serif;color:#3d3357;max-width:560px;margin:auto">'
            . '<h1 style="color:#54458e;font-family:Italiana,serif">Tatiana Skin and Beauty</h1>'
            . '<p>Hola ' . $nombre . ':</p>'
            . '<p>Recibimos una solicitud para restablecer tu contraseña. '
            . 'Haz clic en el siguiente botón para crear una nueva:</p>'
            . '<p style="margin:24px 0"><a href="' . $enlace . '" '
            . 'style="background:#7b5ea7;color:#fff;padding:12px 22px;border-radius:10px;'
            . 'text-decoration:none;font-weight:600">Restablecer contraseña</a></p>'
            . '<p>Este enlace vence en ' . \App\Models\RecuperacionPassword::MINUTOS_VIGENCIA
            . ' minutos y solo puede usarse una vez.</p>'
            . '<p>Si no fuiste tú, ignora este correo. Tu contraseña seguirá siendo la misma.</p>'
            . '<hr style="border:none;border-top:1px solid #ece7f5;margin-top:24px">'
            . '<small style="color:#8d86a0">Tatiana Skin and Beauty · Cuidado · Confianza · Belleza real</small>'
            . '</div>';
    }
}
