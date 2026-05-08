<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/PublicService.php';
require_once __DIR__ . '/../services/PublicAccountService.php';
require_once __DIR__ . '/../helpers/response.php';

class PublicController {
    private PublicService $service;
    private PublicAccountService $publicAccountService;

    public function __construct() {
        $this->service = new PublicService();
        $this->publicAccountService = new PublicAccountService();
    }

    public function listConvocatorias(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $sort = $_GET['sort'] ?? 'fecha_creacion';
        $order = $_GET['order'] ?? 'DESC';
        $search = $_GET['q'] ?? null;
        $estado = $_GET['estado'] ?? null;
        $tipo = $_GET['tipo'] ?? null;
        $dependencia = isset($_GET['dependencia']) ? (int) $_GET['dependencia'] : null;
        $year = isset($_GET['year']) ? (int) $_GET['year'] : null;

        $data = $this->service->listConvocatorias($page, $limit, $sort, $order, $search, $estado, $tipo, $dependencia, $year);
        jsonResponse(true, 'Listado público de convocatorias', $data, null, 200);
    }

    public function getConvocatoria(int $id): never {
        $item = $this->service->getConvocatoria($id);
        if (!$item) {
            jsonResponse(false, 'Convocatoria no encontrada o no disponible públicamente', null, null, 404);
        }
        jsonResponse(true, 'Convocatoria pública', $item, null, 200);
    }

    public function listResultados(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $search = $_GET['q'] ?? null;
        $data = $this->service->listResultados($page, $limit, $search);
        jsonResponse(true, 'Resultados de adjudicación', $data, null, 200);
    }

    public function listContratos(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $estatus = $_GET['estatus'] ?? null;
        $year = isset($_GET['year']) ? (int) $_GET['year'] : null;
        $data = $this->service->listContratos($page, $limit, $estatus, $year);
        jsonResponse(true, 'Contratos públicos', $data, null, 200);
    }

    public function listEvaluaciones(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $data = $this->service->listEvaluaciones($page, $limit);
        jsonResponse(true, 'Procesos públicos en evaluación', $data, null, 200);
    }

    public function listHistorial(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $year = isset($_GET['year']) ? (int) $_GET['year'] : null;
        $tipo = $_GET['tipo'] ?? null;
        $search = $_GET['q'] ?? null;
        $data = $this->service->listHistorial($page, $limit, $year, $tipo, $search);
        jsonResponse(true, 'Historial público de licitaciones', $data, null, 200);
    }

    public function estadisticas(): never {
        $data = $this->service->estadisticas();
        jsonResponse(true, 'Estadísticas públicas', $data, null, 200);
    }

    public function listConvocatoriaDocumentos(int $id): never {
        $convocatoria = $this->service->getConvocatoria($id);
        if (!$convocatoria) {
            jsonResponse(false, 'Convocatoria no encontrada o no disponible públicamente', null, null, 404);
        }

        $items = $this->service->listConvocatoriaDocumentos($id);
        jsonResponse(true, 'Documentos públicos de la convocatoria', ['items' => $items], null, 200);
    }

    public function downloadDocumentoPublico(int $id): never {
        $doc = $this->service->getDocumentoPublico($id);
        if (!$doc) {
            jsonResponse(false, 'Documento no encontrado o no disponible públicamente', null, null, 404);
        }

        $absolutePath = realpath(__DIR__ . '/../../' . $doc['ruta_almacenamiento']);
        $storageBase = realpath(__DIR__ . '/../../storage');

        if (!$absolutePath || !$storageBase || !str_starts_with($absolutePath, $storageBase) || !is_file($absolutePath)) {
            jsonResponse(false, 'El archivo solicitado no está disponible.', null, null, 404);
        }

        $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Content-Disposition: attachment; filename="' . basename((string) $doc['nombre_archivo']) . '"');
        readfile($absolutePath);
        exit;
    }

    public function registerProveedor(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $requestIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $result = $this->publicAccountService->registerProveedorPublico($input, $requestIp);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudo completar el registro', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Registro de proveedor completado', $result['data'], null, 201);
    }

    public function supportTicket(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $result = $this->publicAccountService->createSupportTicket($input);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudo registrar el ticket de soporte', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Solicitud de soporte registrada', $result['data'], null, 202);
    }
}
