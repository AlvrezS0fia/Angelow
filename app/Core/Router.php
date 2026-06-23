<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch($requestMethod, $requestUri) {
        $path = parse_url($requestUri, PHP_URL_PATH);
        // Eliminar el prefijo '/Angelow' (o el que corresponda)
        $basePath = '/Angelow';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        // Si aún queda '/public', lo quitamos también
        $publicPath = '/public';
        if (strpos($path, $publicPath) === 0) {
            $path = substr($path, strlen($publicPath));
        }
        $path = $path ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['path'] === $path) {
                $controllerName = 'App\\Controllers\\' . $route['controller'];
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $route['action'])) {
                        call_user_func([$controller, $route['action']]);
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