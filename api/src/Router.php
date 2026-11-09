<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, callable(): array>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            foreach ($this->routes as $routeMethod => $paths) {
                if ($routeMethod !== $method && isset($paths[$path])) {
                    $this->respond(405, ['error' => 'method not allowed']);
                    return;
                }
            }

            $this->respond(404, ['error' => 'not found']);
            return;
        }

        $result = $handler();
        $key = isset($result['error']) ? 'error' : 'data';
        $this->respond($result['status'], [$key => $result[$key]]);
    }

    private function respond(int $status, array $body): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($body);
    }
}
