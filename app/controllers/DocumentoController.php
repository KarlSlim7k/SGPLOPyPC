<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/DocumentoService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class DocumentoController {
    private DocumentoService $service;

    public function __construct() {
        $this->service = new DocumentoService();
    }

    public function upload(): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if (!isset($_FILES['archivo']) || !isset($_POST['tipo_documento'])) {
            jsonResponse(false, 'Solicitud inválida', null, ['Se requiere archivo y tipo_documento.'], 400);
        }
        $result = $this->service->upload($_FILES['archivo'], $_POST, (int) $user['id_usuario'], $user['rol']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al subir documento', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Documento subido exitosamente', ['id_documento' => $result['id']], null, 201);
    }

    public function listMios(): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== 'PROVEEDOR') {
            jsonResponse(false, 'Solo los proveedores pueden consultar sus documentos.', null, null, 403);
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $context = $_GET['context'] ?? null;
        $idPropuesta = isset($_GET['id_propuesta']) ? (int) $_GET['id_propuesta'] : null;
        $tipoDocumento = $_GET['tipo_documento'] ?? null;

        $result = $this->service->listMios((int) $user['id_usuario'], $page, $limit, $context, $idPropuesta, $tipoDocumento);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudieron cargar los documentos', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Mis documentos', $result['data'], null, 200);
    }

    public function get(int $id): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        $item = $this->service->get($id, (int) $user['id_usuario'], $user['rol']);
        if (!$item) {
            jsonResponse(false, 'Documento no encontrado o sin acceso.', null, null, 404);
        }
        jsonResponse(true, 'Documento obtenido', $item, null, 200);
    }

    public function download(int $id): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->download($id, (int) $user['id_usuario'], $user['rol']);
        if (!$result['ok']) {
            jsonResponse(false, 'Documento no encontrado o sin acceso.', null, $result['errors'] ?? null, 404);
        }

        $doc = $result['data'];
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Length: ' . (string) filesize($doc['path']));
        header('Content-Disposition: attachment; filename="' . basename((string) $doc['nombre_archivo']) . '"');
        readfile($doc['path']);
        exit;
    }
}
