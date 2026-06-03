<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/TicketSoporteService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class TicketSoporteController {
    private TicketSoporteService $service;

    public function __construct() {
        $this->service = new TicketSoporteService();
    }

    public function create(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $result = $this->service->crear($input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al crear ticket', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Ticket creado exitosamente', $result['data'], null, 201);
    }

    public function listMios(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $data = $this->service->listMios((int) $user['id_usuario'], $page, $perPage);
        jsonResponse(true, 'Mis tickets de soporte', $data, null, 200);
    }

    public function get(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $esAdmin = $user['rol'] === 'ADMINISTRADOR';
        $ticket = $this->service->getDetalle($id, (int) $user['id_usuario'], $esAdmin);
        if (!$ticket) {
            jsonResponse(false, 'Ticket no encontrado o sin permiso', null, null, 404);
        }
        jsonResponse(true, 'Detalle del ticket', $ticket, null, 200);
    }

    public function addRespuesta(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $esAdmin = $user['rol'] === 'ADMINISTRADOR';
        $result = $this->service->agregarRespuesta($id, $input, (int) $user['id_usuario'], $esAdmin);
        if (!$result['ok']) {
            $status = $result['status'] ?? 422;
            jsonResponse(false, 'Error al agregar respuesta', null, $result['errors'], $status);
        }
        jsonResponse(true, 'Respuesta agregada exitosamente', $result['data'], null, 201);
    }

    public function changeEstado(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        RoleMiddleware::handle('ADMINISTRADOR');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['estado'])) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, ['El campo estado es obligatorio.'], 400);
        }
        $result = $this->service->cambiarEstado($id, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            $status = $result['status'] ?? 422;
            jsonResponse(false, 'Error al actualizar estado', null, $result['errors'], $status);
        }
        jsonResponse(true, 'Estado del ticket actualizado', $result['data'], null, 200);
    }
}
