<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ParticipacionService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class ParticipacionController {
    private ParticipacionService $service;

    public function __construct() {
        $this->service = new ParticipacionService();
    }

    public function listByLicitacion(int $idLicitacion): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $data = $this->service->listByLicitacion($idLicitacion);
        jsonResponse(true, 'Listado de participaciones', $data, null, 200);
    }

    public function inscribir(int $idLicitacion): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden inscribirse.', null, null, 403);
        }
        $result = $this->service->inscribir($idLicitacion, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Licitación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Licitación no encontrada', null, $result['errors'], 404);
            }
            if (in_array('El proveedor ya está inscrito en esta licitación.', $result['errors'])) {
                jsonResponse(false, 'Conflicto de inscripción', null, $result['errors'], 409);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Inscripción exitosa', ['id_participacion' => $result['id']], null, 201);
    }

    public function enviarPropuesta(int $idParticipacion): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden enviar propuestas.', null, null, 403);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $result = $this->service->enviarPropuesta($idParticipacion, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Participación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Participación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Propuesta enviada exitosamente', ['id_propuesta' => $result['id']], null, 201);
    }

    public function getPropuesta(int $idPropuesta): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        $item = $this->service->getPropuesta($idPropuesta, (int) $user['id_usuario'], $user['rol']);
        if (!$item) {
            jsonResponse(false, 'Propuesta no encontrada', null, null, 404);
        }
        jsonResponse(true, 'Propuesta obtenida', $item, null, 200);
    }

    public function listPropuestas(): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $idLicitacion = isset($_GET['licitacion']) ? (int) $_GET['licitacion'] : null;
        $data = $this->service->listPropuestas($idLicitacion && $idLicitacion > 0 ? $idLicitacion : null);
        jsonResponse(true, 'Listado de propuestas', $data, null, 200);
    }
}
