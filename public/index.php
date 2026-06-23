<?php
session_start();

// Load environment variables
require_once __DIR__ . '/../app/Core/Env.php';

// Cargar configuraciones
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Autoload simple
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Cargar helpers
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $helperFile) {
    require_once $helperFile;
}

// Generar session_id para invitados (carrito)
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['cart_session'])) {
    $session_id = bin2hex(random_bytes(16));
    setcookie('cart_session', $session_id, time() + (30 * 24 * 3600), '/');
}

// Inicializar router
$router = new App\Core\Router();

// Cargar rutas desde config/routes.php
$routes = require __DIR__ . '/../config/routes.php';
foreach ($routes as $route) {
    $router->add($route['method'], $route['path'], $route['controller'], $route['action']);
}

// Despachar
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);