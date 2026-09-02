<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action) {
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

    public function dispatch($requestMethod, $requestUri) {
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
                        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
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