<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/jwt.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// If not an API route, serve landing page for root or let Apache handle existing files
if (strpos($requestUri, '/api/v1/') !== 0) {
    if ($requestUri === '/' || $requestUri === '/index.php') {
        require_once __DIR__ . '/landing.php';
        exit;
    }
    // For any other non-API path, let Apache handle (404 or existing files)
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    exit;
}

// API routing
$route = substr($requestUri, strlen('/api/v1'));
$route = rtrim($route, '/');

// Controllers
require_once __DIR__ . '/../app/controllers/HealthController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

// Middlewares
require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';

try {
    switch (true) {
        case $route === '/health' && $requestMethod === 'GET':
            (new HealthController())->index();
            break;

        case $route === '/auth/login' && $requestMethod === 'POST':
            (new AuthController())->login();
            break;

        case $route === '/me' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new UserController())->me();
            break;

        case $route === '/admin/dashboard' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new AdminController())->dashboard();
            break;

        default:
            jsonResponse(false, 'Ruta no encontrada', null, null, 404);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Error interno del servidor', null, [$e->getMessage()], 500);
}
