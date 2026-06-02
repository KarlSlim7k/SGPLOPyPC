<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ProveedorMetricasService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class ProveedorMetricasController {
    private ProveedorMetricasService $service;

    public function __construct() {
        $this->service = new ProveedorMetricasService();
    }

    public function metricas(int $idProveedor): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        $proveedor = $this->service->findByIdProveedor($idProveedor);
        if (!$proveedor) {
            jsonResponse(false, 'Proveedor no encontrado', null, null, 404);
        }
        if ($user['rol'] === 'PROVEEDOR' && (int) $proveedor['id_usuario'] !== (int) $user['id_usuario']) {
            jsonResponse(false, 'No tienes permisos para acceder a estas datos.', null, null, 403);
        }
        if ($user['rol'] !== 'ADMINISTRADOR' && $user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'No tienes permisos para acceder a estos datos.', null, null, 403);
        }
        $data = $this->service->getMetricas($idProveedor);
        jsonResponse(true, 'Métricas del proveedor', $data, null, 200);
    }

    public function tendencia(int $idProveedor): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        $proveedor = $this->service->findByIdProveedor($idProveedor);
        if (!$proveedor) {
            jsonResponse(false, 'Proveedor no encontrado', null, null, 404);
        }
        if ($user['rol'] === 'PROVEEDOR' && (int) $proveedor['id_usuario'] !== (int) $user['id_usuario']) {
            jsonResponse(false, 'No tienes permisos para acceder a estos datos.', null, null, 403);
        }
        if ($user['rol'] !== 'ADMINISTRADOR' && $user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'No tienes permisos para acceder a estos datos.', null, null, 403);
        }
        $data = $this->service->getTendencia($idProveedor);
        jsonResponse(true, 'Tendencia del proveedor', $data, null, 200);
    }
}
