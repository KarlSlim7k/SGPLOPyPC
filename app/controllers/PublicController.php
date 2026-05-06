<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/PublicService.php';
require_once __DIR__ . '/../helpers/response.php';

class PublicController {
    private PublicService $service;

    public function __construct() {
        $this->service = new PublicService();
    }

    public function listConvocatorias(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $sort = $_GET['sort'] ?? 'fecha_creacion';
        $order = $_GET['order'] ?? 'DESC';
        $data = $this->service->listConvocatorias($page, $limit, $sort, $order);
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
        $data = $this->service->listResultados($page, $limit);
        jsonResponse(true, 'Resultados de adjudicación', $data, null, 200);
    }

    public function listContratos(): never {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $data = $this->service->listContratos($page, $limit);
        jsonResponse(true, 'Contratos públicos', $data, null, 200);
    }
}
