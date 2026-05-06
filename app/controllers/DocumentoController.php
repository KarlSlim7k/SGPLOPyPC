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
        $result = $this->service->upload($_FILES['archivo'], $_POST, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al subir documento', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Documento subido exitosamente', ['id_documento' => $result['id']], null, 201);
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
}
