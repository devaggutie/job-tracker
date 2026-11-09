<?php

declare(strict_types=1);

use App\HealthHandler;
use App\Router;

require __DIR__ . '/../vendor/autoload.php';

$router = new Router();
$router->get('/health', new HealthHandler());

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    $router->dispatch($method, $path);
} catch (Throwable $e) {
    error_log('unhandled error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'internal server error']);
}
