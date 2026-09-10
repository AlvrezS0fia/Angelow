<?php
namespace App\Core;

/**
 * ============================================================
 * ARCHIVO: Router.php — MÓDULO: Enrutador de peticiones HTTP
 * ============================================================
 * QUÉ HACE: Registra rutas (método + path + controlador + acción) y las
 *           despacha haciendo match de método HTTP + regex compilada.
 *           Soporta parámetros de URL nombrados ({id}, {pedidoId}).
 *           NO filtra roles; eso lo hace cada controlador.
 * QUIÉN LO USA: public/index.php (bootstrap de la aplicación)
 */
class Router {
    // ENCAPSULAMIENTO: el estado interno (rutas registradas) es privado y
    // solo se manipula desde add()/dispatch(), nunca desde fuera de la clase.
    // Colección de rutas registradas en tiempo de ejecución (llenada por add()).
    /** @var array{method: string, path: string, regex: string, controller: string, action: string}[] */
    private array $routes = [];

    // --- REGISTRAR UNA RUTA ---
    // Entrada: método HTTP, path (con soporte {param}), controlador y acción.
    // Procesamiento: convierte {param} en grupo regex nombrado, guarda la ruta
    //   ya compilada para que dispatch() no tenga que re-compilar en cada request.
    // Salida: push a $this->routes.
    public function add(string $method, string $path, string $controller, string $action) {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'regex' => $regex,
            'controller' => $controller,
            'action' => $action
        ];
    }

    // --- DESPACHAR LA PETICIÓN ---
    // Entrada: $_SERVER['REQUEST_METHOD'] y $_SERVER['REQUEST_URI'].
    // Procesamiento:
    //   1. Limpiar la URI: quita el prefijo base '/Angelow' y '/public' para
    //      normalizar a '/admin', '/api/pedidos', etc. (soporta entornos de
    //      subdirectorio en XAMPP).
    //   2. Recorrer $this->routes buscando coincidencia de MÉTODO + REGEX.
    //   3. Si coincide, ensamblar 'App\Controllers\' . $controller, instanciar,
    //      verificar que exista la acción y llamarla pasando los params de URL.
    //   4. Si nada coincide → respuesta 404 plana.
    // Salida: HTML renderizado (view) o JSON (api) ... o 404.
    // ROLES: aquí NO se filtran roles; solo se enruta. El controlador decide.
    public function dispatch(string $requestMethod, string $requestUri) {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $basePath = '/Angelow';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        $publicPath = '/public';
        if (strpos($path, $publicPath) === 0) {
            $path = substr($path, strlen($publicPath));
        }
        $path = $path ?: '/';
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) continue;

            if (preg_match($route['regex'], $path, $matches)) {
                $controllerName = 'App\\Controllers\\' . $route['controller'];
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $route['action'])) {
                        // POLIMORFISMO - despacho dinámico: el Router llama a la
                        // acción sobre cualquier controlador sin conocer su clase
                        // concreta; basta con que el método exista (mismo contrato).
                        // Solo se pasan los grupos nombrados de la regex ({id}, {pedidoId}, ...).
                        $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                        call_user_func_array([$controller, $route['action']], $params);
                        return;
                    }
                }
                break;
            }
        }
        http_response_code(404);
        echo "404 - Página no encontrada";
    }
}