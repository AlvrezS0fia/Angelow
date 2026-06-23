<?php
namespace App\Libraries;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
            error_log("EmailService: Destinatario o nombre vacío");
            return false;
        }
        
         // Configuración SMTP desde variables de entorno
         $smtpConfig = [
             'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
             'username' => $_ENV['SMTP_USERNAME'] ?? 'angelow.contacto@gmail.com',
             'password' => $_ENV['SMTP_PASSWORD'] ?? 'vncn qkjn upop iuey',
             'port' => $_ENV['SMTP_PORT'] ?? 587,
             'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? 'angelow.contacto@gmail.com',
             'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'Angelow'
         ];
        
        $mail = new PHPMailer(true);
        
        try {
            // Configurar SMTP
            $mail->isSMTP();
            $mail->Host = $smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['username'];
            $mail->Password = $smtpConfig['password'];
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
                    error_log("✅ Logo incrustado desde: " . $ruta);
                    break;
                }
            }
            
            if (!$logoIncrustado) {
                error_log("⚠️ ADVERTENCIA: No se encontró el logo para incrustar. El correo se enviará sin logo.");
            }
            // ==============================================
            
            // Obtener el contenido HTML de la plantilla
            $htmlContent = self::getHtmlTemplate($tipo, $nombre, $destinatario, $datos_extra);
            $mail->Subject = self::getSubject($tipo);
            $mail->Body = $htmlContent;
            $mail->AltBody = self::getPlainTextBody($tipo, $nombre, $datos_extra);
            
            // Enviar el correo
            $mail->send();
            error_log("✅ Correo enviado exitosamente a: $destinatario - Tipo: $tipo");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error PHPMailer: " . $mail->ErrorInfo);
            return false;
        } catch (\Exception $e) {
            error_log("❌ Error General en EmailService: " . $e->getMessage());
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
                error_log("✅ Plantilla encontrada en: " . $ruta);
                break;
            }
        }
        
        // Si no hay plantilla, usar texto plano
        if (empty($html)) {
            error_log("⚠️ No se encontró plantilla para: $tipo");
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
        $html = str_replace('{{factura_id}}', $datos_extra['factura_id'] ?? '', $html);
        $html = str_replace('{{monto}}', $datos_extra['monto'] ?? '0', $html);
        $html = str_replace('{{mensaje}}', $datos_extra['mensaje'] ?? '', $html);
        
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
            
            case 'factura':
                $factura_id = $datos_extra['factura_id'] ?? '';
                $monto = $datos_extra['monto'] ?? '';
                return "Hola $nombre,\n\n" .
                       "Gracias por tu compra.\n\n" .
                       "Factura #$factura_id\n" .
                       "Monto: $$monto\n\n" .
                       "Saludos,\nEquipo Angelow";
            
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
            'notificacion' => 'Notificación - Angelow',
            'factura' => 'Factura - Angelow'
        ];
        return $subjects[$tipo] ?? 'Notificación - Angelow';
    }
}