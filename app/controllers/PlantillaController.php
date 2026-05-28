<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/PlantillaService.php';
require_once __DIR__ . '/../services/ReporteRenderService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class PlantillaController {
    private PlantillaService $service;
    private ReporteRenderService $renderer;

    public function __construct() {
        $this->service = new PlantillaService();
        $this->renderer = new ReporteRenderService();
    }

    public function list(): never {
        $filters = [
            'tipo' => $_GET['tipo'] ?? null,
            'activa' => $_GET['activa'] ?? null,
            'solo_predefinidas' => !empty($_GET['solo_predefinidas']),
        ];
        $items = $this->service->list($filters);
        jsonResponse(true, 'Plantillas disponibles', ['items' => $items, 'total' => count($items)], null, 200);
    }

    public function get(int $id): never {
        $withContent = !empty($_GET['with_content']);
        $plantilla = $this->service->get($id, $withContent);
        if (!$plantilla) {
            jsonResponse(false, 'Plantilla no encontrada', null, null, 404);
        }
        jsonResponse(true, 'Plantilla', $plantilla, null, 200);
    }

    public function create(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $result = $this->service->create($input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Plantilla creada', ['id_plantilla' => $result['id']], null, 201);
    }

    public function update(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $result = $this->service->update($id, $input, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error', null, $result['errors'], $result['status'] ?? 422);
        }
        jsonResponse(true, 'Plantilla actualizada', null, null, 200);
    }

    public function delete(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->delete($id, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error', null, $result['errors'], $result['status'] ?? 400);
        }
        jsonResponse(true, 'Plantilla eliminada', null, null, 200);
    }

    public function uploadAsset(int $idPlantilla): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $tipo = strtoupper((string) ($_POST['tipo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $file = $_FILES['archivo'] ?? null;
        if ($file === null) {
            jsonResponse(false, 'No se recibió archivo (campo: archivo).', null, null, 400);
        }
        $result = $this->service->uploadAsset($idPlantilla, $tipo, $nombre, $file, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al subir asset', null, $result['errors'], $result['status'] ?? 400);
        }
        jsonResponse(true, 'Asset subido', [
            'id_asset' => $result['id'],
            'ruta_relativa' => $result['ruta'],
        ], null, 201);
    }

    public function deleteAsset(int $idAsset): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->deleteAsset($idAsset, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error', null, $result['errors'], $result['status'] ?? 400);
        }
        jsonResponse(true, 'Asset eliminado', null, null, 200);
    }

    /**
     * POST /api/v1/reportes/generar
     * Body JSON: {
     *   id_plantilla: int,
     *   entidad: 'licitacion'|'contrato',
     *   id_entidad: int,
     *   formato: 'pdf'|'docx'|'md',
     *   parametros?: {clave: valor, ...}
     * }
     * Devuelve el archivo como descarga (binario o texto).
     */
    public function generar(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }
        $idPlantilla = (int) ($input['id_plantilla'] ?? 0);
        $entidad = (string) ($input['entidad'] ?? '');
        $idEntidad = (int) ($input['id_entidad'] ?? 0);
        $formato = strtolower((string) ($input['formato'] ?? 'pdf'));
        $parametros = is_array($input['parametros'] ?? null) ? $input['parametros'] : [];

        if ($idPlantilla <= 0 || $idEntidad <= 0 || $entidad === '') {
            jsonResponse(false, 'Faltan parámetros requeridos: id_plantilla, entidad, id_entidad', null, null, 422);
        }

        $result = $this->renderer->render($idPlantilla, $entidad, $idEntidad, $formato, $parametros);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al generar reporte', null, $result['errors'], $result['status'] ?? 500);
        }

        auditLog((int) $user['id_usuario'], 'reporte_generado', $idPlantilla, 'EXPORT', null, [
            'entidad' => $entidad,
            'id_entidad' => $idEntidad,
            'formato' => $formato,
            'tamano_bytes' => strlen((string) $result['content']),
        ]);

        // Streaming directo del archivo
        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen((string) $result['content']));
        header('X-Content-Type-Options: nosniff');
        echo $result['content'];
        exit;
    }
}
