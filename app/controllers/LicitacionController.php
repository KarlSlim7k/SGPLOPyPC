<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/LicitacionService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class LicitacionController {
    private LicitacionService $service;

    public function __construct() {
        $this->service = new LicitacionService();
    }

    public function list(): never {
        $estado = $_GET['estado'] ?? null;
        $tipo = $_GET['tipo'] ?? null;
        $dependencia = isset($_GET['dependencia']) ? (int) $_GET['dependencia'] : null;
        $data = $this->service->list($estado, $tipo, $dependencia);
        jsonResponse(true, 'Listado de licitaciones', $data, null, 200);
    }

    public function get(int $id): never {
        $item = $this->service->get($id);
        if (!$item) {
            jsonResponse(false, 'Licitación no encontrada', null, null, 404);
        }
        jsonResponse(true, 'Licitación obtenida', $item, null, 200);
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
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Licitación creada exitosamente', ['id_licitacion' => $result['id']], null, 201);
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
            if (in_array('Licitación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Licitación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Licitación actualizada exitosamente', null, null, 200);
    }

    public function cambiarEstado(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['estado_proceso'])) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, ['El campo estado_proceso es obligatorio.'], 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->cambiarEstado($id, $input['estado_proceso'], (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Licitación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Licitación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 409);
        }
        jsonResponse(true, 'Estado actualizado exitosamente', null, null, 200);
    }
}
