<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ProveedorService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class ProveedorController {
    private ProveedorService $service;

    public function __construct() {
        $this->service = new ProveedorService();
    }

    public function list(): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $data = $this->service->list();
        jsonResponse(true, 'Listado de proveedores', $data, null, 200);
    }

    public function get(int $id): never {
        AuthMiddleware::handle();
        $item = $this->service->get($id);
        if (!$item) {
            jsonResponse(false, 'Proveedor no encontrado', null, null, 404);
        }
        jsonResponse(true, 'Proveedor obtenido', $item, null, 200);
    }

    public function create(): never {
        AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->create($input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('El usuario ya tiene un perfil de proveedor registrado.', $result['errors'])) {
                jsonResponse(false, 'Conflicto de registro', null, $result['errors'], 409);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Proveedor registrado exitosamente', ['id_proveedor' => $result['id']], null, 201);
    }

    public function update(int $id): never {
        AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $item = $this->service->get($id);
        if (!$item) {
            jsonResponse(false, 'Proveedor no encontrado', null, null, 404);
        }
        if ($item['id_usuario'] != $user['id_usuario'] && $user['rol'] !== 'ADMINISTRADOR') {
            jsonResponse(false, 'No tienes permisos para editar este proveedor.', null, null, 403);
        }
        $result = $this->service->update($id, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Proveedor actualizado exitosamente', null, null, 200);
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
            if (in_array('Proveedor no encontrado.', $result['errors'])) {
                jsonResponse(false, 'Proveedor no encontrado', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Estatus actualizado exitosamente', null, null, 200);
    }
}
