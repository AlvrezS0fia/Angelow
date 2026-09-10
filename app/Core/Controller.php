<?php
namespace App\Core;

/**
 * ============================================================
 * ARCHIVO: Controller.php — MÓDULO: Controlador base
 * ============================================================
 * QUÉ HACE: Clase padre de todos los controladores. Provee helpers
 *           para renderizar vistas HTML, responder JSON (API REST)
 *           y redirigir a rutas internas.
 * QUIÉN LO USA: Todos los controladores del sistema (extienden Controller).
 */
class Controller {

    // --- RENDERIZAR UNA VISTA (HTML) ---
    // Entrada: nombre de vista con puntos como separador de carpeta
    //          ('auth.login' → app/Views/auth/login.php) y datos asociativos.
    // Procesamiento: extract() convierte las claves del array en variables
    //          ($data['user'] → $user) disponibles dentro del template PHP.
    // Salida: HTML renderizado al navegador.
    protected function view(string $view, array $data = []) {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("Vista no encontrada: $view");
        }
    }

    // --- RESPONDER COMO JSON (API REST) ---
    // Entrada: array a serializar + código de estado HTTP (200 por defecto).
    // Procesamiento: define Content-Type application/json y hace exit tras el echo.
    // Salida: JSON hacia el fetch() del frontend. Ejemplos de uso:
    //   $this->json(['success' => false, 'message' => 'Credenciales incorrectas']);
    //   $this->json(['error' => 'No autorizado'], 403);   // control de roles
    protected function json(array $data, int $statusCode = 200) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            http_response_code(500);
            $json = json_encode([
                'error' => 'Error al serializar la respuesta',
                'detalle' => json_last_error_msg()
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        echo $json;
        exit;
    }

    // --- REDIRIGIR A OTRA URL INTERNA ---
    // Entrada: ruta interna ('/auth/login', '/admin', ...).
    // Procesamiento: antepone APP_URL (definida en config/app.php).
    // Salida: header Location → el navegador carga la nueva página.
    // Uso común en verificación de roles: un no-admin que llegue a /admin
    // es redirigido a /auth/login (ver app/Controllers/Admin/UsuariosController.php:10).
    protected function redirect(string $url) {
        header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
        exit;
    }
}