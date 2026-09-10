<?php
/**
 * ============================================================
 * ARCHIVO: ContactoController.php — MÓDULO: Controlador de contacto
 * ============================================================
 * QUÉ HACE: Muestra la página de "Contáctenos" y procesa el formulario de
 *   contacto que llega por AJAX, validando cada campo y respondiendo en JSON.
 *   IMPORTANTE: el envío real de correo está en un bloque comentado; por ahora
 *   el método solo responde éxito sin persistir ni enviar nada.
 * MODELO(S) QUE USA: ninguno
 * ENDPOINTS/RUTAS: GET /contacto, POST /contacto/enviar
 * QUIÉN LO CONSUME: La página de contacto (vista paginas.contactenos) y su
 *   formulario con fetch() en JavaScript.
 */
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de la sección de contacto. Extiende la clase base Controller.
 * Patrón MVC: recibe la petición y devuelve una vista o una respuesta JSON.
 */
class ContactoController extends Controller
{
    /** Muestra la vista de contacto, pasando el usuario logueado (si existe). */
    public function index()
    {
        $user = $_SESSION['user'] ?? null;
        $this->view('paginas.contactenos', ['user' => $user]);
    }

    /** Procesa el formulario de contacto: valida los campos y responde JSON. */
    public function enviar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        // Lee el JSON enviado por el frontend y limpia espacios en los campos.
        $data = json_decode(file_get_contents('php://input'), true);
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $asunto = trim($data['asunto'] ?? '');
        $mensaje = trim($data['mensaje'] ?? '');

        // Validaciones
        if (empty($nombre) || strlen($nombre) < 3) {
            $this->json(['success' => false, 'message' => 'El nombre debe tener al menos 3 caracteres.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Correo electrónico inválido.']);
        }
        if (empty($telefono) || !preg_match('/^[\+\d\s\-]{7,}$/', $telefono)) {
            $this->json(['success' => false, 'message' => 'Teléfono inválido (mínimo 7 dígitos).']);
        }
        if (empty($asunto) || strlen($asunto) < 4) {
            $this->json(['success' => false, 'message' => 'El asunto debe tener al menos 4 caracteres.']);
        }
        if (empty($mensaje) || strlen($mensaje) < 10) {
            $this->json(['success' => false, 'message' => 'El mensaje debe tener al menos 10 caracteres.']);
        }

        // Aquí puedes enviar el correo o guardar en BD
        // Ejemplo (descomentar cuando tengas servidor de correo):
        /*
        $to = "info@angelow.com";
        $subject = "Contacto: $asunto";
        $body = "Nombre: $nombre\nEmail: $email\nTeléfono: $telefono\n\nMensaje:\n$mensaje";
        $headers = "From: $email\r\nReply-To: $email";
        mail($to, $subject, $body, $headers);
        */

        $this->json(['success' => true, 'message' => '¡Mensaje enviado correctamente! Nos pondremos en contacto pronto.']);
    }
}