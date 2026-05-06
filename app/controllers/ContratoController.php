<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ContratoService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class ContratoController {
    private ContratoService $service;

    public function __construct() {
        $this->service = new ContratoService();
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
            if (in_array('Licitación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Licitación no encontrada', null, $result['errors'], 404);
            }
            if (in_array('La licitación ya tiene un contrato registrado.', $result['errors'])) {
                jsonResponse(false, 'Conflicto de contrato', null, $result['errors'], 409);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Contrato creado exitosamente', ['id_contrato' => $result['id']], null, 201);
    }

    public function get(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $item = $this->service->get($id);
        if (!$item) {
            jsonResponse(false, 'Contrato no encontrado', null, null, 404);
        }
        jsonResponse(true, 'Contrato obtenido', $item, null, 200);
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
            if (in_array('Contrato no encontrado.', $result['errors'])) {
                jsonResponse(false, 'Contrato no encontrado', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Contrato actualizado exitosamente', null, null, 200);
    }

    public function cambiarEstatus(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['estatus'])) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, ['El campo estatus es obligatorio.'], 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->cambiarEstatus($id, $input['estatus'], (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Contrato no encontrado.', $result['errors'])) {
                jsonResponse(false, 'Contrato no encontrado', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Estatus actualizado exitosamente', null, null, 200);
    }
}
