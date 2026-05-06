<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/NotificacionService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class NotificacionController {
    private NotificacionService $service;

    public function __construct() {
        $this->service = new NotificacionService();
    }

    public function create(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->crear($input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Notificación creada exitosamente', ['id_notificacion' => $result['id']], null, 201);
    }

    public function listMias(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $data = $this->service->listarMias((int) $user['id_usuario']);
        jsonResponse(true, 'Mis notificaciones', $data, null, 200);
    }

    public function marcarLeida(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->marcarLeida($id, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Notificación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Notificación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'No autorizado', null, $result['errors'], 403);
        }
        jsonResponse(true, 'Notificación marcada como leída', null, null, 200);
    }
}
