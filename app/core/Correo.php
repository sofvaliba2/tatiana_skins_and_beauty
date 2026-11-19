<?php
declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envío de correo del sistema mediante PHPMailer + SMTP.
 *
 * La configuración del servidor se centraliza en config/smtp.php y las
 * credenciales se leen de variables de entorno/.env. Este archivo no tiene
 * credenciales y nunca muestra el enlace de recuperación en pantalla.
 */
final class Correo
{
    private static ?array $smtp = null;

    private static function configuracion(): array
    {
        if (self::$smtp === null) {
            self::$smtp = require BASE_PATH . '/config/smtp.php';
        }
        return self::$smtp;
    }

    /** Carga PHPMailer. Usa vendor/autoload.php si existe; si no, los archivos vendorizados. */
    private static function cargarPhpMailer(): void
    {
        if (class_exists(PHPMailer::class)) {
            return;
        }

        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
            return;
        }

        $src = BASE_PATH . '/vendor/phpmailer/phpmailer/src/';
        foreach (['Exception.php', 'SMTP.php', 'PHPMailer.php'] as $archivo) {
            require_once $src . $archivo;
        }
    }

    /** Envía un correo HTML por SMTP. Devuelve true solo si el servidor lo aceptó. */
    public static function enviar(string $para, string $asunto, string $html, string $texto = ''): bool
    {
        $para = trim($para);
        if ($para === '' || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $cfg = self::configuracion();
        if ($cfg['host'] === '' || ($cfg['auth'] && ($cfg['user'] === '' || $cfg['pass'] === ''))) {
            error_log('[SMTP] Configuración incompleta: revisa SMTP_HOST, SMTP_USER y SMTP_PASS en .env');
            return false;
        }

        self::cargarPhpMailer();

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->Port = $cfg['port'];
            $mail->SMTPAuth = $cfg['auth'];
            if ($cfg['auth']) {
                $mail->Username = $cfg['user'];
                $mail->Password = $cfg['pass'];
            }

            switch ($cfg['encryption']) {
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                default:
                    $mail->SMTPSecure = '';
            }

            $mail->Timeout = $cfg['timeout'];
            // Nunca se escribe en la salida HTML: los mensajes van al log del servidor.
            $mail->SMTPDebug = $cfg['debug'];
            $mail->Debugoutput = 'error_log';

            $mail->setFrom($cfg['from_address'], $cfg['from_name']);
            $mail->addAddress($para);
            $mail->Subject = $asunto;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $texto !== '' ? $texto : self::textoPlano($html);

            return $mail->send();
        } catch (PhpMailerException $e) {
            error_log('[SMTP] Error al enviar correo: ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            error_log('[SMTP] Error inesperado: ' . $e->getMessage());
            return false;
        }
    }

    /** URL absoluta del enlace de restablecimiento para usar dentro del correo. */
    public static function enlaceRestablecer(string $token): string
    {
        $relativa = '/index.php?v=restablecer&token=' . urlencode($token);
        $cfg = self::configuracion();

        $base = $cfg['link_base'] !== ''
            ? $cfg['link_base']
            : rtrim(defined('APP_URL') ? (string)APP_URL : '', '/');
        if ($base !== '') {
            return $base . $relativa;
        }

        // Último recurso en entornos sin APP_URL: URL relativa a la raíz de la app.
        return rtrim(defined('BASE_URL') ? (string)BASE_URL : '', '/') . $relativa;
    }

    /** Plantilla HTML profesional del correo de recuperación. */
    public static function plantillaRecuperacion(string $nombre, string $enlace): string
    {
        $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $enlace = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');
        $minutos = \App\Models\RecuperacionPassword::MINUTOS_VIGENCIA;

        return '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Restablece tu contraseña · Tatiana Skin and Beauty</title></head>'
            . '<body style="margin:0;padding:0;background:#faf7fd;font-family:Jost,Arial,sans-serif;color:#3d3357">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">'
            . 'Usa este enlace para crear una nueva contraseña en Tatiana Skin and Beauty.</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf7fd;padding:28px 12px">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
            . 'style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(84,69,142,.10)">'
            . '<tr><td style="background:#54458e;padding:26px 32px;text-align:center">'
            . '<div style="font-family:Italiana,Georgia,serif;font-size:26px;color:#ffffff;letter-spacing:1px">'
            . 'Tatiana Skin and Beauty</div>'
            . '<div style="color:#e5dcf7;font-size:12px;margin-top:6px;letter-spacing:2px">CUIDADO · CONFIANZA · BELLEZA REAL</div>'
            . '</td></tr>'
            . '<tr><td style="padding:32px">'
            . '<h1 style="margin:0 0 18px;font-family:Italiana,Georgia,serif;font-size:24px;color:#54458e">'
            . 'Restablece tu contraseña</h1>'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.6">Hola ' . $nombre . ':</p>'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.6">'
            . 'Recibimos una solicitud para cambiar la contraseña de tu cuenta. Haz clic en el botón de abajo para crear una nueva.</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0">'
            . '<tr><td align="center" bgcolor="#7b5ea7" style="border-radius:10px">'
            . '<a href="' . $enlace . '" target="_blank" rel="noopener" '
            . 'style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px">'
            . 'Restablecer contraseña</a>'
            . '</td></tr></table>'
            . '<p style="margin:0 0 10px;font-size:13px;color:#6e648f;line-height:1.6">'
            . 'Este enlace vence en <strong>' . $minutos . ' minutos</strong> y solo puede usarse una vez.</p>'
            . '<p style="margin:0 0 10px;font-size:13px;color:#6e648f;line-height:1.6">'
            . 'Si no fuiste tú, ignora este correo. Tu contraseña seguirá siendo la misma.</p>'
            . '<p style="margin:18px 0 0;font-size:12px;color:#8d86a0;word-break:break-all">'
            . 'Si el botón no funciona, copia y pega esta dirección:<br><a href="' . $enlace . '" '
            . 'style="color:#7b5ea7">' . $enlace . '</a></p>'
            . '</td></tr>'
            . '<tr><td style="background:#f4f0fa;padding:18px 32px;text-align:center;color:#8d86a0;font-size:12px">'
            . 'Tatiana Skin and Beauty · Este correo es automático, no lo respondas.'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /** Versión en texto plano del correo de recuperación. */
    public static function plantillaTextoRecuperacion(string $nombre, string $enlace): string
    {
        $minutos = \App\Models\RecuperacionPassword::MINUTOS_VIGENCIA;
        return "Hola {$nombre}:\n\n"
            . "Recibimos una solicitud para restablecer tu contraseña en Tatiana Skin and Beauty.\n"
            . "Abre este enlace para crear una nueva:\n\n{$enlace}\n\n"
            . "El enlace vence en {$minutos} minutos y solo puede usarse una vez.\n"
            . "Si no fuiste tú, ignora este correo. Tu contraseña no cambiará.\n\n"
            . "Tatiana Skin and Beauty · Cuidado · Confianza · Belleza real";
    }

    private static function textoPlano(string $html): string
    {
        $texto = str_replace(['<br>', '<br/>', '</p>', '</div>', '</tr>'], "\n", $html);
        return trim(preg_replace('/\n{3,}/', "\n\n", strip_tags($texto)) ?? '');
    }
}
