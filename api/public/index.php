<?php

declare(strict_types=1);

use App\AuthHandler;
use App\HealthHandler;
use App\Router;
use App\Session;

require __DIR__ . '/../vendor/autoload.php';

Session::start();

$router = new Router();
$router->get('/health', new HealthHandler());

$auth = new AuthHandler();
$router->get('/auth/csrf', [$auth, 'csrf']);
$router->post('/auth/register', [$auth, 'register']);
$router->post('/auth/login', [$auth, 'login']);
$router->post('/auth/logout', [$auth, 'logout']);
$router->get('/auth/me', [$auth, 'me']);

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
