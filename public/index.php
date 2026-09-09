<?php

// ============================================
// ARCHIVO: public/index.php
// PROPÓSITO: Front controller / bootstrap de toda la aplicación web.
//            Es el ÚNICO punto de entrada (todas las peticiones pasan por aquí
//            vía .htaccess). Prepara el entorno: sesión, variables de entorno,
//            configuración, autoload, helpers y finalmente despacha la ruta.
// FLUJO: navegador → .htaccess (Apache) → index.php
//        → [1] sesión PHP → [2] .env → [3] config → [4] autoload
//        → [5] cookie de invitado → [6] Router::dispatch() → controlador → vista/JSON.
// ROLES AFECTADOS: todos. Aquí SOLO se inicia la sesión; la asignación de rol
//        ocurre después en AuthController::login()/register() y el chequeo de
//        permisos ocurre dentro de cada controlador (ver app/Core/Auth.php).
// ============================================
//
// --- SECCIÓN: Flujo de autenticación desde este archivo ---
// 1. Se arranca la sesión PHP (Session Layer).
// 2. Si no hay `$_SESSION['user']`, se crea una cookie `cart_session` para
//    poder tener carrito de INVITADO (rol implícito "invitado").
// 3. Se carga config/routes.php y se entrega la petición al Router.
// 4. El Router llama al controlador; el controlador lee $_SESSION['user']['rol']
//    (Auth::rol()) y decide si la acción está permitida.
// ============================================

// CAPA 5 ISO-OSI (Sesión): sesiones PHP con directorio propio en storage/sessions.
// Configurar directorio de sesiones dentro de la app (evita errores de
// permisos intermitentes en C:\xampp\tmp y asegura escritura para el usuario web)
$sessionDir = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0777, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}

// Endurecimiento de la sesión ANTES de session_start():
//  - use_strict_mode: rechaza ids de sesión no emitidos por el servidor (anti fijación).
//  - use_only_cookies: no acepta session_id por URL (anti robo por referrer).
//  - Parámetros de la cookie: HttpOnly (el JS nunca lee PHPSESSID), SameSite=Lax
//    (bloquea CSRF por cookies entre sitios), Secure solo si hay HTTPS.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// ---- EXPIRACIÓN POR INACTIVIDAD (12 h) ----
// Si el usuario logueado supera el idle, se destruye la sesión silenciosamente;
// los controladores detectan la falta de sesión y redirigen/403. No interfiere
// con el carrito de invitado (usa la cookie cart_session, no la sesión PHP).
if (!empty($_SESSION['user'])) {
    $ahora = time();
    $ultimo = (int) ($_SESSION['last_activity'] ?? $ahora);
    if (($ahora - $ultimo) > 12 * 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['last_activity'] = $ahora;
    }
}

// ---- CABECERAS DE SEGURIDAD ----
// Se emiten antes de cualquier salida para todas las respuestas (páginas y APIs).
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
}

// --- CARGAR VARIABLES DE ENTORNO ---
// app/Core/Env.php lee el archivo .env (raíz) y lo vuelca a $_ENV.
// De aquí salen: APP_URL, DB_HOST/DB_NAME/DB_USER/DB_PASS, JWT_SECRET, etc.
require_once __DIR__ . '/../app/Core/Env.php';

// --- CARGAR CONFIGURACIÓN ---
// config/app.php → APP_NAME, APP_URL, TIMEZONE (America/Bogota).
// config/database.php → constantes DB_* para la conexión PDO.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// --- AUTOLOAD PSR-4 SIMPLE ---
// Mapea el prefijo 'App\' a la carpeta app/. Así `new App\Controllers\HomeController()`
// carga automáticamente app/Controllers/HomeController.php sin require manuales.
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// --- CARGAR HELPERS ---
// Funciones globales (p.ej. sanitize()) para limpiar entradas de usuario.
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $helperFile) {
    require_once $helperFile;
}

// --- GENERAR SESSION_ID PARA INVITADOS (CARRITO) ---
// Si el usuario NO está logueado, le asignamos una cookie de 30 días.
// Esta cookie (`cart_session`) permite que el carrito se guarde anónimamente
// en la tabla `carrito` (columna session_id). Es el equivalente al rol
// "invitado": puede navegar y agregar al carrito, pero NO comprar
// (para comprar debe iniciar sesión; ver CompraController::index).
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['cart_session'])) {
    $session_id = bin2hex(random_bytes(16));
    setcookie('cart_session', $session_id, [
        'expires' => time() + (30 * 24 * 3600),
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
}

// --- INICIALIZAR ROUTER ---
$router = new App\Core\Router();

// --- CARGAR RUTAS DESDE config/routes.php ---
// Este es el "mapa" central de la aplicación: método + path → controlador@acción.
// Cada entrada se registra en el Router; la verificación de ROLES NO está aquí,
// vive dentro de cada acción (ver comentarios en config/routes.php).
$routes = require __DIR__ . '/../config/routes.php';
foreach ($routes as $route) {
    $router->add($route['method'], $route['path'], $route['controller'], $route['action']);
}

// --- CHEQUEO CSRF MÍNIMO (Origin/Referer) ---
// Para peticiones que mutan estado (POST/PUT/DELETE/PATCH): si el navegador
// manda un Origin (o Referer) cuyo host no es el propio, se rechaza con 403.
// Clientes sin Origin/Referer (curl, scripts, la app de repartidor) pasan,
// porque el navegador SIEMPRE envía Origin en peticiones entre sitios.
// Complementa SameSite=Lax para las cookies.
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    if ($origin !== null || $referer !== null) {
        $hostsPermitidos = ['localhost', '127.0.0.1', '[::1]', '::1'];
        $hostApp = parse_url(APP_URL, PHP_URL_HOST);
        if ($hostApp !== null && $hostApp !== false) {
            $hostsPermitidos[] = $hostApp;
        }
        $origenOk = function ($url) use ($hostsPermitidos) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === null || $host === false || $host === '') {
                return false;
            }
            $host = strtolower(rtrim($host, '.'));
            foreach ($hostsPermitidos as $permitido) {
                if (strtolower($permitido) === $host) {
                    return true;
                }
            }
            return false;
        };
        if ($origin !== null && !$origenOk($origin)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo '{"error":"Origen no permitido"}';
            exit;
        }
        if ($origin === null && $referer !== null && !$origenOk($referer)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo '{"error":"Origen no permitido"}';
            exit;
        }
    }
}

// --- DESPACHAR ---
// Entrega la petición al Router: método HTTP + URI. El Router resuelve
// controlador@acción y el controlador devuelve la vista/JSON.
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);