<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, callable(array): array>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, callable $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function delete(string $path, callable $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        [$handler, $params] = $this->match($method, $path);

        if ($handler === null) {
            foreach ($this->routes as $routeMethod => $patterns) {
                if ($routeMethod === $method) {
                    continue;
                }

                [$otherHandler] = $this->match($routeMethod, $path);

                if ($otherHandler !== null) {
                    $this->respond(405, ['error' => 'method not allowed']);
                    return;
                }
            }

            $this->respond(404, ['error' => 'not found']);
            return;
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

            if (!Csrf::validate(is_string($token) ? $token : null)) {
                $this->respond(403, ['error' => 'invalid csrf token']);
                return;
            }
        }

        $result = $handler($params);
        $key = isset($result['error']) ? 'error' : 'data';
        $this->respond($result['status'], [$key => $result[$key]]);
    }

    /**
     * @return array{0: callable(array): array|null, 1: array<string, string>}
     */
    private function match(string $method, string $path): array
    {
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = $this->patternToRegex($pattern);

            if (preg_match($regex, $path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            return [$handler, $params];
        }

        return [null, []];
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            $pattern
        );

        return '#^' . $regex . '$#';
    }

    private function respond(int $status, array $body): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($body);
    }
}
