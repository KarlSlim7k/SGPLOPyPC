<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../helpers/response.php';

class UserController {
    private UserService $service;

    public function __construct() {
        $this->service = new UserService();
    }

    public function me(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $data = $this->service->getMe((int) $user['id_usuario']);
        jsonResponse(true, 'Usuario autenticado', $data, null, 200);
    }

    public function updateProfile(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $result = $this->service->updateProfile((int) $user['id_usuario'], $input);
        if (!$result['ok']) {
            if (in_array('Usuario no encontrado.', $result['errors'], true)) {
                jsonResponse(false, 'Usuario no encontrado', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Perfil actualizado exitosamente', $result['user'], null, 200);
    }

    public function changePassword(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $result = $this->service->changePassword((int) $user['id_usuario'], $input);
        if (!$result['ok']) {
            if (in_array('Usuario no encontrado.', $result['errors'], true)) {
                jsonResponse(false, 'Usuario no encontrado', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Contraseña actualizada exitosamente', null, null, 200);
    }
}
