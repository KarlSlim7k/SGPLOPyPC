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
require_once __DIR__ . '/../app/controllers/AclaracionController.php';
require_once __DIR__ . '/../app/controllers/AuditoriaController.php';
require_once __DIR__ . '/../app/controllers/PlantillaController.php';
require_once __DIR__ . '/../app/controllers/DatosAbiertosController.php';
require_once __DIR__ . '/../app/controllers/MetricasController.php';
require_once __DIR__ . '/../app/controllers/NotificacionStreamController.php';
require_once __DIR__ . '/../app/controllers/EfirmaController.php';
require_once __DIR__ . '/../app/controllers/ReputacionController.php';
require_once __DIR__ . '/../app/controllers/ProveedorMetricasController.php';
require_once __DIR__ . '/../app/controllers/TicketSoporteController.php';
require_once __DIR__ . '/../app/routes/PublicRouteTable.php';

// Middlewares
require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
require_once __DIR__ . '/../app/middlewares/RequestIdMiddleware.php';

// Inicializar request ID temprano para que esté disponible en logs y auditoría
RequestIdMiddleware::handle();

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

function enforcePublicReadRateLimit(Logger $logger, string $routeKey): void {
    $rl = new RateLimiter(
        (int) env('RATE_LIMIT_PUBLIC_READ_MAX', '20'),
        (int) env('RATE_LIMIT_PUBLIC_READ_WINDOW', '60')
    );
    $ip = getClientIp();
    $key = 'public-read:' . $routeKey . ':' . $ip;
    if (!$rl->isAllowed($key)) {
        $logger->security('Rate limit exceeded on public read endpoint', ['ip' => $ip, 'route' => $routeKey]);
        jsonResponse(false, 'Demasiadas solicitudes. Intente más tarde.', null, null, 429);
    }
}

/**
 * Rate limit de la API pública de datos abiertos (OCDS): 60 req/min por IP.
 * Configurable via RATE_LIMIT_OCDS_MAX y RATE_LIMIT_OCDS_WINDOW.
 */
function enforceOcdsRateLimit(Logger $logger, string $routeKey): void {
    $rl = new RateLimiter(
        (int) env('RATE_LIMIT_OCDS_MAX', '60'),
        (int) env('RATE_LIMIT_OCDS_WINDOW', '60')
    );
    $ip = getClientIp();
    $key = 'ocds:' . $routeKey . ':' . $ip;
    if (!$rl->isAllowed($key)) {
        $logger->security('Rate limit exceeded on OCDS endpoint', ['ip' => $ip, 'route' => $routeKey]);
        // Cabeceras CORS para que el cliente JS pueda leer el error
        header('Access-Control-Allow-Origin: *');
        jsonResponse(false, 'Demasiadas solicitudes. Intente más tarde.', null, null, 429);
    }
}

try {
    $handledByRouteTable = dispatchPublicRouteTable($route, $requestMethod, $logger);
    if (!$handledByRouteTable) {
        switch (true) {
        case $route === '/health' && $requestMethod === 'GET':
            (new HealthController())->index();
            break;

        // ===== Datos abiertos (OCDS 1.1) — endpoints públicos sin autenticación =====
        case $route === '/datos-abiertos/releases' && $requestMethod === 'OPTIONS':
        case $route === '/datos-abiertos/release-package' && $requestMethod === 'OPTIONS':
        case (preg_match('#^/datos-abiertos/releases/[^/]+$#', $route) && $requestMethod === 'OPTIONS'):
            // Pre-flight CORS
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            exit;

        case $route === '/datos-abiertos/releases' && $requestMethod === 'GET':
            enforceOcdsRateLimit($logger, 'releases');
            (new DatosAbiertosController())->listReleases();
            break;

        case preg_match('#^/datos-abiertos/releases/([^/]+)$#', $route, $m) && $requestMethod === 'GET':
            enforceOcdsRateLimit($logger, 'release-by-ocid');
            (new DatosAbiertosController())->getReleaseByOcid($m[1]);
            break;

        case $route === '/datos-abiertos/release-package' && $requestMethod === 'GET':
            enforceOcdsRateLimit($logger, 'release-package');
            (new DatosAbiertosController())->getReleasePackage();
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

        case $route === '/auth/logout' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new AuthController())->logout();
            break;

        case $route === '/auth/login/mfa' && $requestMethod === 'POST':
            $rl = new RateLimiter(10, 60);
            $ip = getClientIp();
            if (!$rl->isAllowed('mfa-login:' . $ip)) {
                jsonResponse(false, 'Demasiados intentos. Intente más tarde.', null, null, 429);
            }
            (new AuthController())->loginMfa();
            break;

        case $route === '/me/mfa/enroll' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new AuthController())->mfaEnroll();
            break;

        case $route === '/me/mfa/confirm' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new AuthController())->mfaConfirm();
            break;

        case $route === '/me/mfa/disable' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new AuthController())->mfaDisable();
            break;

        case $route === '/admin/auditoria' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new AuditoriaController())->list();
            break;

        case $route === '/admin/auditoria/export.csv' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new AuditoriaController())->exportCsv();
            break;

        // Plantillas de reportes (ADMINISTRADOR)
        case $route === '/admin/plantillas' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->list();
            break;

        case $route === '/admin/plantillas' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->create();
            break;

        case preg_match('#^/admin/plantillas/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->get((int) $m[1]);
            break;

        case preg_match('#^/admin/plantillas/(\d+)$#', $route, $m) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->update((int) $m[1]);
            break;

        case preg_match('#^/admin/plantillas/(\d+)$#', $route, $m) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->delete((int) $m[1]);
            break;

        case preg_match('#^/admin/plantillas/(\d+)/assets$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->uploadAsset((int) $m[1]);
            break;

        case preg_match('#^/admin/plantillas/assets/(\d+)$#', $route, $m) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->deleteAsset((int) $m[1]);
            break;

        case $route === '/reportes/generar' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new PlantillaController())->generar();
            break;

        // Métricas del dashboard analítico (ADMINISTRADOR)
        case $route === '/admin/metricas/tiempo-ciclo' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new MetricasController())->tiempoCiclo();
            break;

        case $route === '/admin/metricas/proveedores-top' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new MetricasController())->proveedoresTop();
            break;

        case $route === '/admin/metricas/montos-mensuales' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new MetricasController())->montosMensuales();
            break;

        case $route === '/admin/metricas/cumplimiento' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new MetricasController())->cumplimiento();
            break;

        case $route === '/admin/metricas/dependencias' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new MetricasController())->dependencias();
            break;

        case $route === '/admin/metricas/flush-cache' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new MetricasController())->flushCache();
            break;

        // Notificaciones en tiempo real (SSE)
        case $route === '/notificaciones/stream' && $requestMethod === 'GET':
            // Token puede venir como query param (EventSource no soporta headers)
            if (!empty($_GET['token'])) {
                $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_GET['token'];
            }
            AuthMiddleware::handle();
            set_time_limit(30); // Permitir hasta 30s de ejecución para SSE
            (new NotificacionStreamController())->stream();
            break;

        case $route === '/notificaciones/count' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new NotificacionStreamController())->count();
            break;

        // Firma electrónica avanzada (e.firma/FIEL)
        case preg_match('#^/contratos/(\d+)/firma-efirma$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new EfirmaController())->firmar((int) $m[1]);
            break;

        // Reputación de proveedores
        case preg_match('#^/contratos/(\d+)/evaluacion-postcontrato$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ReputacionController())->crearEvaluacion((int) $m[1]);
            break;

        case preg_match('#^/proveedores/(\d+)/reputacion$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new ReputacionController())->getReputacion((int) $m[1]);
            break;

        case preg_match('#^/proveedores/(\d+)/metricas$#', $route, $m) && $requestMethod === 'GET':
            (new ProveedorMetricasController())->metricas((int) $m[1]);
            break;

        case preg_match('#^/proveedores/(\d+)/metricas/tendencia$#', $route, $m) && $requestMethod === 'GET':
            (new ProveedorMetricasController())->tendencia((int) $m[1]);
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
            $user = AuthMiddleware::getAuthenticatedUser();
            if (($user['rol'] ?? '') === 'PUBLICO') {
                jsonResponse(false, 'No tienes permisos para acceder a este recurso.', null, null, 403);
            }
            (new LicitacionController())->list();
            break;

        case preg_match('#^/licitaciones/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            $user = AuthMiddleware::getAuthenticatedUser();
            if (($user['rol'] ?? '') === 'PUBLICO') {
                jsonResponse(false, 'No tienes permisos para acceder a este recurso.', null, null, 403);
            }
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
        case $route === '/participaciones/mias' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->listMias();
            break;

        case $route === '/participaciones' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ParticipacionController())->list();
            break;

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

        case preg_match('#^/participaciones/(\d+)/propuesta$#', $route, $m) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->editarPropuesta((int) $m[1]);
            break;

        case preg_match('#^/participaciones/(\d+)$#', $route, $m) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->retirar((int) $m[1]);
            break;

        case preg_match('#^/propuestas/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new ParticipacionController())->getPropuesta((int) $m[1]);
            break;
        case $route === '/propuestas/mias' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->listPropuestasMias();
            break;
        case $route === '/propuestas' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new ParticipacionController())->listPropuestas();
            break;

        case $route === '/participaciones/mias/export.csv' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->exportParticipacionesCsv();
            break;

        case $route === '/propuestas/mias/export.csv' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ParticipacionController())->exportPropuestasCsv();
            break;

        // Documentos
        case $route === '/documentos/mios' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new DocumentoController())->listMios();
            break;

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

        case preg_match('#^/documentos/(\d+)/download$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new DocumentoController())->download((int) $m[1]);
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
        case $route === '/contratos/mios' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ContratoController())->listMios();
            break;

        case $route === '/contratos/mios/export.csv' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ContratoController())->exportContratosCsv();
            break;

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

        case preg_match('#^/contratos/(\d+)/firma$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            RoleMiddleware::handle('PROVEEDOR');
            (new ContratoController())->firmar((int) $m[1]);
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

        // Tickets de soporte autenticados (proveedor)
        case $route === '/tickets' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new TicketSoporteController())->create();
            break;

        case $route === '/tickets/mios' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new TicketSoporteController())->listMios();
            break;

        case preg_match('#^/tickets/(\d+)$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new TicketSoporteController())->get((int) $m[1]);
            break;

        case preg_match('#^/tickets/(\d+)/respuestas$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new TicketSoporteController())->addRespuesta((int) $m[1]);
            break;

        case preg_match('#^/tickets/(\d+)/estado$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            RoleMiddleware::handle('ADMINISTRADOR');
            (new TicketSoporteController())->changeEstado((int) $m[1]);
            break;

        // Aclaraciones
        case preg_match('#^/licitaciones/(\d+)/aclaraciones$#', $route, $m) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            (new AclaracionController())->list((int) $m[1]);
            break;

        case preg_match('#^/licitaciones/(\d+)/aclaraciones$#', $route, $m) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            (new AclaracionController())->create((int) $m[1]);
            break;

        case preg_match('#^/aclaraciones/(\d+)/respuesta$#', $route, $m) && $requestMethod === 'PATCH':
            AuthMiddleware::handle();
            (new AclaracionController())->responder((int) $m[1]);
            break;

            default:
                jsonResponse(false, 'Ruta no encontrada', null, null, 404);
        }
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
