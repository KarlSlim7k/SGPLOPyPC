<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/MetricasService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class MetricasController {
    private MetricasService $service;

    public function __construct() {
        $this->service = new MetricasService();
    }

    public function tiempoCiclo(): never {
        $f = $this->extractFiltros();
        $data = $this->service->tiempoCiclo($f['from'], $f['to'], $f['id_dependencia']);
        jsonResponse(true, 'Tiempo de ciclo por tipo de procedimiento', $data, null, 200);
    }

    public function proveedoresTop(): never {
        $f = $this->extractFiltros();
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $data = $this->service->proveedoresTop($f['from'], $f['to'], $limit);
        jsonResponse(true, 'Top de proveedores adjudicados', $data, null, 200);
    }

    public function montosMensuales(): never {
        $f = $this->extractFiltros();
        $data = $this->service->montosMensuales($f['from'], $f['to'], $f['id_dependencia']);
        jsonResponse(true, 'Montos por mes', $data, null, 200);
    }

    public function cumplimiento(): never {
        $f = $this->extractFiltros();
        $data = $this->service->cumplimiento($f['from'], $f['to'], $f['id_dependencia']);
        jsonResponse(true, 'Cumplimiento de fechas', $data, null, 200);
    }

    public function dependencias(): never {
        $data = $this->service->dependenciasParaFiltro();
        jsonResponse(true, 'Dependencias para filtros', ['items' => $data], null, 200);
    }

    public function flushCache(): never {
        $count = $this->service->flushCache();
        jsonResponse(true, 'Cache de métricas vaciado', ['archivos_eliminados' => $count], null, 200);
    }

    private function extractFiltros(): array {
        $from = isset($_GET['from']) && $this->validDate((string) $_GET['from']) ? (string) $_GET['from'] : null;
        $to = isset($_GET['to']) && $this->validDate((string) $_GET['to']) ? (string) $_GET['to'] : null;
        $idDep = isset($_GET['id_dependencia']) && (int) $_GET['id_dependencia'] > 0
            ? (int) $_GET['id_dependencia']
            : null;
        return ['from' => $from, 'to' => $to, 'id_dependencia' => $idDep];
    }

    private function validDate(string $date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
