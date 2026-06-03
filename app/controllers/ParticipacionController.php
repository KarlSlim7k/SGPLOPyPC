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

    public function list(): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $idLicitacion = isset($_GET['licitacion']) ? (int) $_GET['licitacion'] : null;
        $estatus = $_GET['estatus'] ?? null;
        $search = $_GET['q'] ?? null;

        $data = $this->service->listAll(
            $page,
            $limit,
            ($idLicitacion !== null && $idLicitacion > 0) ? $idLicitacion : null,
            $estatus,
            $search
        );
        jsonResponse(true, 'Listado general de participaciones', $data, null, 200);
    }

    public function listMias(): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden consultar sus participaciones.', null, null, 403);
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $estatus = $_GET['estatus'] ?? null;
        $search = $_GET['q'] ?? null;

        $result = $this->service->listMias((int) $user['id_usuario'], $page, $limit, $estatus, $search);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudieron cargar las participaciones', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Mis participaciones', $result['data'], null, 200);
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

    public function retirar(int $idParticipacion): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden retirar inscripciones.', null, null, 403);
        }
        $result = $this->service->retirarInscripcion($idParticipacion, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Participación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Participación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'No se pudo retirar la inscripción', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Inscripción retirada exitosamente', null, null, 200);
    }

    public function editarPropuesta(int $idParticipacion): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden editar propuestas.', null, null, 403);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $result = $this->service->editarPropuesta($idParticipacion, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            if (in_array('Participación no encontrada.', $result['errors'])) {
                jsonResponse(false, 'Participación no encontrada', null, $result['errors'], 404);
            }
            jsonResponse(false, 'No se pudo editar la propuesta', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Propuesta actualizada exitosamente', ['id_propuesta' => $result['id']], null, 200);
    }

    public function listPropuestasMias(): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden consultar sus propuestas.', null, null, 403);
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $estatus = $_GET['estatus'] ?? null;
        $search = $_GET['q'] ?? null;

        $result = $this->service->listPropuestasMias((int) $user['id_usuario'], $page, $limit, $estatus, $search);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudieron cargar las propuestas', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Mis propuestas', $result['data'], null, 200);
    }

    public function exportParticipacionesCsv(): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden exportar sus participaciones.', null, null, 403);
        }

        $result = $this->service->listMias((int) $user['id_usuario'], 1, 10000, null, null);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudieron cargar las participaciones', null, $result['errors'], 422);
        }

        $items = $result['data']['items'] ?? [];
        $this->outputCsv('participaciones', $items);
    }

    public function exportPropuestasCsv(): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden exportar sus propuestas.', null, null, 403);
        }

        $result = $this->service->listPropuestasMias((int) $user['id_usuario'], 1, 10000, null, null);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudieron cargar las propuestas', null, $result['errors'], 422);
        }

        $items = $result['data']['items'] ?? [];
        $this->outputCsv('propuestas', $items);
    }

    private function outputCsv(string $tipo, array $items): never {
        $filename = $tipo . '_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($tipo === 'participaciones') {
            fputcsv($output, ['Licitación', 'Descripción', 'Dependencia', 'Estado Proceso', 'Estatus', 'Monto Propuesta', 'Fecha Inscripción']);
            foreach ($items as $item) {
                fputcsv($output, [
                    $item['numero_licitacion'] ?? '',
                    $item['descripcion_proyecto'] ?? '',
                    $item['dependencia_nombre'] ?? '',
                    $item['estado_proceso'] ?? '',
                    $item['estatus'] ?? '',
                    $item['monto_propuesta'] ?? '0',
                    $item['fecha_inscripcion'] ?? '',
                ]);
            }
        } else {
            fputcsv($output, ['Licitación', 'Descripción', 'Dependencia', 'Estatus Propuesta', 'Monto Propuesta', 'Fecha Envío']);
            foreach ($items as $item) {
                fputcsv($output, [
                    $item['numero_licitacion'] ?? '',
                    $item['descripcion_proyecto'] ?? '',
                    $item['dependencia_nombre'] ?? '',
                    $item['estatus_propuesta'] ?? '',
                    $item['monto_propuesta'] ?? '0',
                    $item['fecha_envio'] ?? '',
                ]);
            }
        }

        fclose($output);
        exit;
    }
}
