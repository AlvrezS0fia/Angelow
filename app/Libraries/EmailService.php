<?php
namespace App\Libraries;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * ============================================================
 * ARCHIVO: EmailService.php — MÓDULO: Servicio de envío de correos
 * ============================================================
 * QUÉ HACE: Envía correos electrónicos usando PHPMailer con SMTP (STARTTLS).
 *           Soporta plantillas HTML (bienvenida, recuperación, notificación)
 *           con logo incrustado vía CID, y versión texto plano como fallback.
 *           Configuración SMTP viene de $_ENV (cargado desde .env).
 * QUIÉN LO USA: AuthController (bienvenida, recuperación, cambio de contraseña)
 */
class EmailService {
    
    /**
     * Enviar correo electrónico con imagen incrustada (CID)
     * 
     * @param string $destinatario Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param string $tipo Tipo de correo (bienvenida, recuperacion, notificacion, factura)
     * @param array $datos_extra Datos adicionales para la plantilla
     * @return bool True si se envió correctamente
     */
    public static function enviar($destinatario, $nombre, $tipo = 'bienvenida', $datos_extra = []) {
        
        if (empty($destinatario) || empty($nombre)) {
            error_log("[EmailService] Destinatario o nombre vacío");
            return false;
        }

        // Verificar que $_ENV tiene configuración SMTP
        if (empty($_ENV['SMTP_HOST']) || empty($_ENV['SMTP_USERNAME']) || empty($_ENV['SMTP_PASSWORD'])) {
            error_log("[EmailService] FALTA configuración SMTP en .env - Host: " . ($_ENV['SMTP_HOST'] ?? 'VACIO') . ", Username: " . ($_ENV['SMTP_USERNAME'] ?? 'VACIO'));
        }
        
         // Configuración SMTP desde variables de entorno
         $smtpConfig = [
             'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
             'username' => $_ENV['SMTP_USERNAME'] ?? 'angelow.contacto@gmail.com',
             'password' => $_ENV['SMTP_PASSWORD'] ?? 'bhsc nmnw iwah claj',
             'port' => $_ENV['SMTP_PORT'] ?? 587,
             'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? 'angelow.contacto@gmail.com',
             'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'Angelow'
         ];

        error_log("[EmailService] Iniciando envio SMTP - Host: {$smtpConfig['host']}:{$smtpConfig['port']}, Username: {$smtpConfig['username']}, Password length: " . strlen($smtpConfig['password']));
        
        $mail = new PHPMailer(true);
        
        try {
            // Configurar SMTP
            $mail->isSMTP();
            $mail->Host = $smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['username'];
            $mail->Password = $smtpConfig['password'];
            // CAPA 4 ISO-OSI (Transporte): cifrado SMTP con STARTTLS.
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpConfig['port'];
            $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $mail->addAddress($destinatario, $nombre);
            $mail->addReplyTo($smtpConfig['from_email'], $smtpConfig['from_name']);
            
            // Configurar formato HTML
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // ==============================================
            // INCRUSTAR LOGO (CID) - IMAGEN INCrustADA
            // ==============================================
            $basePath = realpath(__DIR__ . '/../../');
            
            // Buscar el logo en múltiples ubicaciones
            $rutasLogo = [
                $basePath . '/app/Views/emails/img/logos.png',
                $basePath . '/app/Views/emails/img/logo.png',
                $basePath . '/public/assets/imagenes/general/logo.png',
                $basePath . '/public/img/logo.png',
                $basePath . '/public/emails/img/logos.png',
                __DIR__ . '/../Views/emails/img/logos.png',
                __DIR__ . '/../public/emails/img/logos.png',
            ];
            
            $logoIncrustado = false;
            foreach ($rutasLogo as $ruta) {
                if (file_exists($ruta)) {
                    // Incrustar la imagen con CID 'logo_angelow'
                    $mail->addEmbeddedImage($ruta, 'logo_angelow', basename($ruta));
                    $logoIncrustado = true;
                    error_log("[EmailService] Logo incrustado desde: " . $ruta);
                    break;
                }
            }
            
            if (!$logoIncrustado) {
                error_log("[EmailService] ADVERTENCIA: No se encontro el logo para incrustar. El correo se enviara sin logo.");
            }
            // ==============================================
            
            // Obtener el contenido HTML de la plantilla
            $htmlContent = self::getHtmlTemplate($tipo, $nombre, $destinatario, $datos_extra);
            $mail->Subject = self::getSubject($tipo);
            $mail->Body = $htmlContent;
            $mail->AltBody = self::getPlainTextBody($tipo, $nombre, $datos_extra);
            
            // Enviar el correo
            $mail->send();
            error_log("[EmailService] OK - Correo enviado exitosamente a: $destinatario - Tipo: $tipo");
            return true;
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("[EmailService] ERROR PHPMailer: " . $errorMsg);
            error_log("[EmailService] ErrorInfo: " . $mail->ErrorInfo);
            
            if (strpos($errorMsg, 'Username and Password not accepted') !== false) {
                error_log("[EmailService] DIAGNOSTICO: Credenciales SMTP rechazadas. Verifica la App Password de Gmail.");
            } elseif (strpos($errorMsg, 'Connection refused') !== false) {
                error_log("[EmailService] DIAGNOSTICO: Conexion rechazada. Firewall o puerto bloqueado.");
            } elseif (strpos($errorMsg, 'Could not connect') !== false) {
                error_log("[EmailService] DIAGNOSTICO: No se pudo conectar. Verifica conexion a internet.");
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("[EmailService] ERROR GENERAL: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            return false;
        }
    }
    
    /**
     * Cargar plantilla HTML y reemplazar variables
     */
    private static function getHtmlTemplate($tipo, $nombre, $email, $datos_extra = []) {
        
        // Buscar la plantilla en diferentes ubicaciones
        $basePath = realpath(__DIR__ . '/../../');
        $rutasPlantilla = [
            $basePath . '/app/Views/emails/' . $tipo . '.html',
            $basePath . '/public/emails/' . $tipo . '.html',
            __DIR__ . '/../Views/emails/' . $tipo . '.html',
        ];
        
        $html = '';
        foreach ($rutasPlantilla as $ruta) {
            if (file_exists($ruta)) {
                $html = file_get_contents($ruta);
                error_log("[EmailService] Plantilla encontrada en: " . $ruta);
                break;
            }
        }
        
        // Si no hay plantilla, usar texto plano
        if (empty($html)) {
            error_log("[EmailService] No se encontro plantilla para: $tipo");
            return self::getPlainTextBody($tipo, $nombre, $datos_extra);
        }
        
        // Definir APP_URL si no está definida
        $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/Angelow';
        
        // Preparar enlace de recuperación
        $resetLink = '';
        if (!empty($datos_extra['token'])) {
            $resetLink = $app_url . "/auth/reset-password?token=" . $datos_extra['token'];
        }
        
        // ==============================================
        // REEMPLAZAR VARIABLES EN LA PLANTILLA
        // ==============================================
        
        // Reemplazar el nombre del usuario
        $html = str_replace('Hola, Usuario', 'Hola, ' . htmlspecialchars($nombre), $html);
        $html = str_replace('>Usuario<', '>' . htmlspecialchars($nombre) . '<', $html);
        $html = str_replace('{{nombre}}', htmlspecialchars($nombre), $html);
        $html = str_replace('{{email}}', htmlspecialchars($email), $html);
        
        // Reemplazar año y fechas
        $html = str_replace('{{year}}', date('Y'), $html);
        $html = str_replace('{{fecha_actual}}', date('d/m/Y H:i:s'), $html);
        
        // Reemplazar códigos y enlaces
        $html = str_replace('{{codigo_descuento}}', $datos_extra['codigo_descuento'] ?? 'ANGELOW10', $html);
        $html = str_replace('{{reset_link}}', $resetLink, $html);
        $html = str_replace('{{token}}', $datos_extra['token'] ?? '', $html);
        $html = str_replace('{{app_url}}', $app_url, $html);
        $html = str_replace('{{monto}}', $datos_extra['monto'] ?? '0', $html);
        $html = str_replace('{{mensaje}}', $datos_extra['mensaje'] ?? '', $html);
        
        $html = str_replace('{{nombre_cliente}}', $datos_extra['nombre_cliente'] ?? $nombre, $html);
        $html = str_replace('{{fecha_emision}}', $datos_extra['fecha_emision'] ?? date('d/m/Y'), $html);
        $html = str_replace('{{estado_label}}', $datos_extra['estado_label'] ?? 'Pendiente', $html);
        $html = str_replace('{{subtotal}}', $datos_extra['subtotal'] ?? '0', $html);
        $html = str_replace('{{total}}', $datos_extra['total'] ?? '0', $html);
        $html = str_replace('{{envio_text}}', $datos_extra['envio_text'] ?? 'Gratis', $html);
        
        // Fila de descuento condicional
        $descuento = floatval($datos_extra['descuento'] ?? 0);
        if ($descuento > 0) {
            $descuentoRow = '<tr><td style="padding:6px 0;font-size:13px;color:#10b981;">Descuento</td><td style="padding:6px 0;font-size:13px;color:#10b981;text-align:right;">- COP $' . number_format($descuento) . '</td></tr>';
        } else {
            $descuentoRow = '';
        }
        $html = str_replace('{{descuento_row}}', $descuentoRow, $html);
        
        // NOTA: La imagen ya tiene src="cid:logo_angelow" en tu HTML
        // No es necesario reemplazar nada más para el logo
        
        return $html;
    }
    
    /**
     * Versión texto plano (para clientes que no soportan HTML)
     */
    private static function getPlainTextBody($tipo, $nombre, $datos_extra = []) {
        switch($tipo) {
            case 'bienvenida':
                return "Hola $nombre,\n\n" .
                       "¡Bienvenido a Angelow!\n\n" .
                       "Tu cuenta ha sido creada exitosamente.\n\n" .
                       "Código de descuento: " . ($datos_extra['codigo_descuento'] ?? 'ANGELOW10') . "\n" .
                       "10% de descuento en tu primera compra\n\n" .
                       "Saludos,\nEquipo Angelow";
                       
            case 'recuperacion':
                $token = $datos_extra['token'] ?? '';
                $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/Angelow';
                $resetLink = $app_url . "/auth/reset-password?token=$token";
                return "Hola $nombre,\n\n" .
                       "Solicitaste restablecer tu contraseña.\n\n" .
                       "Haz clic en el siguiente enlace:\n" .
                       "$resetLink\n\n" .
                       "Este enlace expira en 1 hora.\n\n" .
                       "Si no solicitaste esto, ignora este mensaje.\n\n" .
                       "Saludos,\nEquipo Angelow";
                       
            case 'notificacion':
                $mensaje = $datos_extra['mensaje'] ?? 'Notificación del sistema';
                return "Hola $nombre,\n\n$mensaje\n\nSaludos,\nEquipo Angelow";
            
            default:
                return "Hola $nombre,\n\n" .
                       "Este es un mensaje de Angelow.\n\n" .
                       "Saludos,\nEquipo Angelow";
        }
    }
    
    /**
     * Obtener el asunto del correo según el tipo
     */
    private static function getSubject($tipo) {
        $subjects = [
            'bienvenida' => '¡Bienvenido a Angelow!',
            'recuperacion' => 'Recuperación de contraseña - Angelow',
            'notificacion' => 'Notificación - Angelow'
        ];
        return $subjects[$tipo] ?? 'Notificación - Angelow';
    }
}