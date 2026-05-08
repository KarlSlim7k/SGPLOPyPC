<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/SupportTicketService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class SupportTicketController {
    private SupportTicketService $service;

    public function __construct() {
        $this->service = new SupportTicketService();
    }

    public function list(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $estado = $_GET['estado'] ?? null;
        $search = $_GET['q'] ?? null;

        $data = $this->service->listAdmin($page, $limit, $estado, $search);
        jsonResponse(true, 'Tickets de soporte', $data, null, 200);
    }

    public function get(int $id): never {
        $ticket = $this->service->getById($id);
        if (!$ticket) {
            jsonResponse(false, 'Ticket de soporte no encontrado', null, null, 404);
        }

        jsonResponse(true, 'Ticket de soporte', $ticket, null, 200);
    }

    public function changeEstado(int $id): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['estado'])) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, ['El campo estado es obligatorio.'], 400);
        }

        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->changeEstado($id, (string) $input['estado'], (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Ticket de soporte no encontrado.', $result['errors'], true)) {
                jsonResponse(false, 'Ticket de soporte no encontrado', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Estado del ticket actualizado', $result['data'], null, 200);
    }
}
