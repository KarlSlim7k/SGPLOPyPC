<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/EvaluacionService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class EvaluacionController {
    private EvaluacionService $service;

    public function __construct() {
        $this->service = new EvaluacionService();
    }

    public function list(): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $idLicitacion = isset($_GET['licitacion']) ? (int) $_GET['licitacion'] : null;
        $data = $this->service->list($idLicitacion && $idLicitacion > 0 ? $idLicitacion : null);
        jsonResponse(true, 'Listado de evaluaciones', $data, null, 200);
    }

    public function create(): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->create($input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Propuesta no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Propuesta no encontrada', null, $result['errors'], 404);
            }
            if (in_array('La propuesta ya tiene una evaluación registrada.', $result['errors'])) {
                jsonResponse(false, 'Conflicto de evaluación', null, $result['errors'], 409);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Evaluación creada exitosamente', ['id_evaluacion' => $result['id']], null, 201);
    }

    public function get(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $item = $this->service->get($id);
        if (!$item) {
            jsonResponse(false, 'Evaluación no encontrada', null, null, 404);
        }
        jsonResponse(true, 'Evaluación obtenida', $item, null, 200);
    }

    public function update(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->update($id, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Evaluación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Evaluación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Evaluación actualizada exitosamente', null, null, 200);
    }

    public function dictamen(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->dictamen($id, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Evaluación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Evaluación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Dictamen registrado exitosamente', null, null, 200);
    }
}
