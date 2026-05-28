<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ReputacionService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class ReputacionController {
    private ReputacionService $service;

    public function __construct() {
        $this->service = new ReputacionService();
    }

    /**
     * POST /api/v1/contratos/{id}/evaluacion-postcontrato
     * Body: { puntualidad, calidad, comunicacion, cumplimiento_alcance, comentarios? }
     * Rol: ADMINISTRADOR
     */
    public function crearEvaluacion(int $idContrato): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $result = $this->service->crearEvaluacion($idContrato, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al crear evaluación', null, $result['errors'], $result['status'] ?? 422);
        }
        jsonResponse(true, 'Evaluación registrada exitosamente', $result['data'], null, 201);
    }

    /**
     * GET /api/v1/proveedores/{id}/reputacion
     * Público para ADMINISTRADOR; el propio proveedor también puede ver su reputación.
     */
    public function getReputacion(int $idProveedor): never {
        $data = $this->service->getReputacion($idProveedor);
        jsonResponse(true, 'Reputación del proveedor', $data, null, 200);
    }
}
