<?php

namespace app\core;

class Router
{
    public Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    public array $routes = [];

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function resolve()
    {
        $path = $this->request->path();
        $method = $this->request->method();
        
        error_log("Router attempting to resolve path: '$path', method: '$method'");
        
        // Try with and without trailing slash to be more forgiving
        $callback = $this->routes[$method][$path] ?? $this->routes[$method][rtrim($path, '/')] ?? $this->routes[$method][$path . '/'] ?? false;

        if ($callback === false) {
            error_log("Router could not find a route for path: '$path'");
            error_log("Available routes: " . json_encode(array_keys($this->routes[$method])));
            http_response_code(404);
            include __DIR__ . '/../views/404.php';
            exit;
        }

        error_log("Router found callback for path: '$path'");
        
        if (is_array($callback)) {
            error_log("Callback is controller: " . $callback[0] . ", action: " . $callback[1]);
            $callback[0] = new $callback[0]();

            return call_user_func($callback);
        }
        
        return $callback();
    }
} 