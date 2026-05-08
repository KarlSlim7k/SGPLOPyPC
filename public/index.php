<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/env_loader.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/jwt.php';
require_once __DIR__ . '/../app/helpers/security.php';
require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/RateLimiter.php';
require_once __DIR__ . '/../app/helpers/Validator.php';
require_once __DIR__ . '/../app/helpers/Logger.php';
require_once __DIR__ . '/../app/helpers/Metrics.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// If not an API route, serve landing page for root or let Apache handle existing files
if (strpos($requestUri, '/api/v1/') !== 0) {
    if ($requestUri === '/' || $requestUri === '/index.php') {
        require_once __DIR__ . '/landing.php';
        exit;
    }
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    exit;
}

// API routing
$route = substr($requestUri, strlen('/api/v1'));
$route = rtrim($route, '/');

setSecurityHeaders();

// Controllers
require_once __DIR__ . '/../app/controllers/HealthController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/DependenciaController.php';
require_once __DIR__ . '/../app/controllers/LicitacionController.php';
require_once __DIR__ . '/../app/controllers/ProveedorController.php';
require_once __DIR__ . '/../app/controllers/ParticipacionController.php';
require_once __DIR__ . '/../app/controllers/DocumentoController.php';
require_once __DIR__ . '/../app/controllers/EvaluacionController.php';
require_once __DIR__ . '/../app/controllers/ContratoController.php';
require_once __DIR__ . '/../app/controllers/ReporteController.php';
require_once __DIR__ . '/../app/controllers/PublicController.php';
require_once __DIR__ . '/../app/controllers/NotificacionController.php';
require_once __DIR__ . '/../app/controllers/SupportTicketController.php';

// Middlewares
require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';

$logger = new Logger();
$metrics = new Metrics();
$currentRouteForMetrics = $route;
register_shutdown_function(function () use ($metrics, $currentRouteForMetrics) {
    $status = http_response_code() ?: 200;
    $metrics->record($currentRouteForMetrics, $status);
});

function getClientIp(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

try {
    switch (true) {
        case $route === '/health' && $requestMethod === 'GET':
            (new HealthController())->index();
            break;

        case $route === '/auth/login' && $requestMethod === 'POST':
            $rl = new RateLimiter(5, 60);
            $ip = getClientIp();
            if (!$rl->isAllowed('login:' . $ip)) {
                $logger->security('Rate limit exceeded on login', ['ip' => $ip]);
                jsonResponse(false, 'Demasiados intentos de inicio de sesión. Intente más tarde.', null, null, 429);
            }
            (new AuthController())->login();
            break;

        case $route === '/auth/password/forgot' && $requestMethod === 'POST':
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_FORGOT_MAX', '5'),
                (int) env('RATE_LIMIT_FORGOT_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('auth-forgot:' . $ip)) {
                $logger->security('Rate limit exceeded on forgot password', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas solicitudes de recuperación. Intente más tarde.', null, null, 429);
            }
            (new AuthController())->forgotPassword();
            break;

        case $route === '/auth/password/reset' && $requestMethod === 'POST':
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_RESET_MAX', '10'),
                (int) env('RATE_LIMIT_RESET_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('auth-reset:' . $ip)) {
                $logger->security('Rate limit exceeded on reset password', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas solicitudes de restablecimiento. Intente más tarde.', null, null, 429);
            }
            (new AuthController())->resetPassword();
            break;

        case $route === '/me' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new UserController())->me();
            break;

        case $route === '/me/profile' && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            (new UserController())->updateProfile();
            break;

        case $route === '/me/password' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new UserController())->changePassword();
            break;

        case $route === '/admin/dashboard' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new AdminController())->dashboard();
            break;

        case $route === '/dependencias' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new DependenciaController())->list();
            break;

        // Licitaciones
        case $route === '/licitaciones' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new LicitacionController())->list();
            break;

        case preg_match('#^/licitaciones/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new LicitacionController())->get((int) $m[1]);
            break;

        case $route === '/licitaciones' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new LicitacionController())->create();
            break;

        case preg_match('#^/licitaciones/(\d+)$#', $route, $m) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new LicitacionController())->update((int) $m[1]);
            break;

        case preg_match('#^/licitaciones/(\d+)/estado$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new LicitacionController())->cambiarEstado((int) $m[1]);
            break;

        // Proveedores
        case $route === '/proveedores' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ProveedorController())->list();
            break;

        case preg_match('#^/proveedores/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new ProveedorController())->get((int) $m[1]);
            break;

        case $route === '/proveedores' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new ProveedorController())->create();
            break;

        case preg_match('#^/proveedores/(\d+)$#', $route, $m) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            (new ProveedorController())->update((int) $m[1]);
            break;

        case preg_match('#^/proveedores/(\d+)/estatus$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ProveedorController())->cambiarEstatus((int) $m[1]);
            break;

        // Participaciones y propuestas
        case preg_match('#^/licitaciones/(\d+)/participaciones$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ParticipacionController())->listByLicitacion((int) $m[1]);
            break;

        case preg_match('#^/licitaciones/(\d+)/participaciones$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->inscribir((int) $m[1]);
            break;

        case preg_match('#^/participaciones/(\d+)/propuesta$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->enviarPropuesta((int) $m[1]);
            break;

        case preg_match('#^/propuestas/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new ParticipacionController())->getPropuesta((int) $m[1]);
            break;
        case $route === '/propuestas' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ParticipacionController())->listPropuestas();
            break;

        // Documentos
        case $route === '/documentos/upload' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            $rl = new RateLimiter(10, 60);
            $ip = getClientIp();
            if (!$rl->isAllowed('upload:' . $ip)) {
                $logger->security('Rate limit exceeded on upload', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas cargas de documentos. Intente más tarde.', null, null, 429);
            }
            (new DocumentoController())->upload();
            break;

        case preg_match('#^/documentos/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new DocumentoController())->get((int) $m[1]);
            break;

        // Evaluaciones
        case $route === '/evaluaciones' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new EvaluacionController())->create();
            break;
        case $route === '/evaluaciones' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new EvaluacionController())->list();
            break;

        case preg_match('#^/evaluaciones/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new EvaluacionController())->get((int) $m[1]);
            break;

        case preg_match('#^/evaluaciones/(\d+)$#', $route, $m) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new EvaluacionController())->update((int) $m[1]);
            break;

        case preg_match('#^/evaluaciones/(\d+)/dictamen$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new EvaluacionController())->dictamen((int) $m[1]);
            break;

        // Contratos
        case $route === '/contratos' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ContratoController())->create();
            break;
        case $route === '/contratos' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ContratoController())->list();
            break;

        case preg_match('#^/contratos/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ContratoController())->get((int) $m[1]);
            break;

        case preg_match('#^/contratos/(\d+)$#', $route, $m) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ContratoController())->update((int) $m[1]);
            break;

        case preg_match('#^/contratos/(\d+)/estatus$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ContratoController())->cambiarEstatus((int) $m[1]);
            break;

        // Adjudicación
        case preg_match('#^/licitaciones/(\d+)/adjudicar$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new LicitacionController())->adjudicar((int) $m[1]);
            break;

        // Reportes / Dashboard
        case $route === '/reportes/dashboard/resumen' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->dashboardResumen();
            break;

        case $route === '/reportes/dashboard/licitaciones-por-estado' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->dashboardLicitacionesPorEstado();
            break;

        case $route === '/reportes/dashboard/licitaciones-por-tipo' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->dashboardLicitacionesPorTipo();
            break;

        case $route === '/reportes/dashboard/licitaciones-por-mes' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->dashboardLicitacionesPorMes();
            break;

        case $route === '/reportes/dashboard/participacion-proveedores' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->dashboardParticipacionProveedores();
            break;

        case $route === '/reportes/dashboard/adjudicaciones-por-periodo' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->dashboardAdjudicacionesPorPeriodo();
            break;

        // Exportaciones
        case $route === '/reportes/export/licitaciones.csv' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            $rl = new RateLimiter(5, 60);
            $ip = getClientIp();
            if (!$rl->isAllowed('export:' . $ip)) {
                $logger->security('Rate limit exceeded on export', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas exportaciones. Intente más tarde.', null, null, 429);
            }
            (new ReporteController())->exportarLicitacionesCsv();
            break;

        case $route === '/reportes/export/contratos.csv' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            $rl = new RateLimiter(5, 60);
            $ip = getClientIp();
            if (!$rl->isAllowed('export:' . $ip)) {
                $logger->security('Rate limit exceeded on export', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas exportaciones. Intente más tarde.', null, null, 429);
            }
            (new ReporteController())->exportarContratosCsv();
            break;

        // Transparencia pública
        case $route === '/public/convocatorias' && $requestMethod === 'GET':
            (new PublicController())->listConvocatorias();
            break;

        case preg_match('#^/public/convocatorias/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            (new PublicController())->getConvocatoria((int) $m[1]);
            break;

        case $route === '/public/resultados' && $requestMethod === 'GET':
            (new PublicController())->listResultados();
            break;

        case $route === '/public/contratos' && $requestMethod === 'GET':
            (new PublicController())->listContratos();
            break;

        case $route === '/public/evaluaciones' && $requestMethod === 'GET':
            (new PublicController())->listEvaluaciones();
            break;

        case $route === '/public/historial' && $requestMethod === 'GET':
            (new PublicController())->listHistorial();
            break;

        case $route === '/public/estadisticas' && $requestMethod === 'GET':
            (new PublicController())->estadisticas();
            break;

        case preg_match('#^/public/convocatorias/(\d+)/documentos$#', $route, $m) && $requestMethod === 'GET':
            (new PublicController())->listConvocatoriaDocumentos((int) $m[1]);
            break;

        case preg_match('#^/public/documentos/(\d+)/download$#', $route, $m) && $requestMethod === 'GET':
            (new PublicController())->downloadDocumentoPublico((int) $m[1]);
            break;

        case $route === '/public/proveedores/registro' && $requestMethod === 'POST':
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_PUBLIC_REGISTER_MAX', '5'),
                (int) env('RATE_LIMIT_PUBLIC_REGISTER_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('public-register:' . $ip)) {
                $logger->security('Rate limit exceeded on public register', ['ip' => $ip]);
                jsonResponse(false, 'Demasiados intentos de registro. Intente más tarde.', null, null, 429);
            }
            (new PublicController())->registerProveedor();
            break;

        case $route === '/public/soporte' && $requestMethod === 'POST':
            $rl = new RateLimiter(
                (int) env('RATE_LIMIT_PUBLIC_SUPPORT_MAX', '5'),
                (int) env('RATE_LIMIT_PUBLIC_SUPPORT_WINDOW', '300')
            );
            $ip = getClientIp();
            if (!$rl->isAllowed('public-support:' . $ip)) {
                $logger->security('Rate limit exceeded on support', ['ip' => $ip]);
                jsonResponse(false, 'Demasiadas solicitudes de soporte. Intente más tarde.', null, null, 429);
            }
            (new PublicController())->supportTicket();
            break;

        // Historial de licitación
        case preg_match('#^/licitaciones/(\d+)/historial$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReporteController())->historialLicitacion((int) $m[1]);
            break;

        // Notificaciones
        case $route === '/notificaciones' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new NotificacionController())->create();
            break;

        case $route === '/notificaciones/mias' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new NotificacionController())->listMias();
            break;

        case preg_match('#^/notificaciones/(\d+)/leida$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            (new NotificacionController())->marcarLeida((int) $m[1]);
            break;

        // Soporte (bandeja administrativa)
        case $route === '/soporte/tickets' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new SupportTicketController())->list();
            break;

        case preg_match('#^/soporte/tickets/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new SupportTicketController())->get((int) $m[1]);
            break;

        case preg_match('#^/soporte/tickets/(\d+)/estado$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new SupportTicketController())->changeEstado((int) $m[1]);
            break;

        default:
            jsonResponse(false, 'Ruta no encontrada', null, null, 404);
    }
} catch (Throwable $e) {
    $logger->error('Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    $isDev = (env('APP_ENV', 'production') === 'development');
    $errors = $isDev ? [$e->getMessage()] : null;
    jsonResponse(false, 'Error interno del servidor', null, $errors, 500);
}
