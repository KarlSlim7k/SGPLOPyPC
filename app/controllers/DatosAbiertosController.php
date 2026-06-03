<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/OcdsService.php';
require_once __DIR__ . '/../helpers/response.php';

/**
 * Endpoints públicos (sin auth) que exponen datos del sistema en formato OCDS 1.1.
 *
 * Mapping y diseño en docs/fases/mejoras/FASE3_OCDS_MAPPING.md
 */
class DatosAbiertosController {
    private OcdsService $service;

    public function __construct() {
        $this->service = new OcdsService();
    }

    /**
     * GET /api/v1/datos-abiertos/releases
     *
     * Lista paginada en formato envoltura SGPLOPyPC (success/message/data/errors).
     * Query params: page, limit (max 50), from (Y-m-d), to (Y-m-d), estado.
     */
    public function listReleases(): never {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
        $filters = [];
        if (!empty($_GET['from']) && $this->validDate((string) $_GET['from'])) {
            $filters['from'] = (string) $_GET['from'];
        }
        if (!empty($_GET['to']) && $this->validDate((string) $_GET['to'])) {
            $filters['to'] = (string) $_GET['to'];
        }
        if (!empty($_GET['estado']) && in_array((string) $_GET['estado'], OcdsRepository::ESTADOS_PUBLICOS, true)) {
            $filters['estado'] = (string) $_GET['estado'];
        }
        if (!empty($_GET['tipo'])) {
            $filters['tipo'] = (string) $_GET['tipo'];
        }

        $result = $this->service->listReleases($filters, $page, $limit);
        $this->setOcdsHeaders();

        jsonResponse(true, 'Releases (OCDS 1.1)', [
            'releases' => $result['releases'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'standard' => 'OCDS 1.1',
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
        ], null, 200);
    }

    /**
     * GET /api/v1/datos-abiertos/releases/{ocid}
     *
     * Devuelve un release individual en formato OCDS puro (sin envoltura SGPLOPyPC).
     */
    public function getReleaseByOcid(string $ocid): never {
        $release = $this->service->getReleaseByOcid($ocid);
        if ($release === null) {
            $this->setOcdsHeaders();
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'not_found',
                'message' => 'Release no encontrado o estado no público',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->setOcdsHeaders();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($release, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * GET /api/v1/datos-abiertos/release-package
     *
     * Devuelve un Release Package OCDS 1.1 puro (descargable).
     */
    public function getReleasePackage(): never {
        $package = $this->service->buildReleasePackage(200);
        $this->setOcdsHeaders();
        header('Content-Type: application/json; charset=utf-8');
        // Atajo: si viene ?download=1, sugerir descarga
        if (!empty($_GET['download'])) {
            header('Content-Disposition: attachment; filename="sgplopypc-ocds-' . date('Ymd') . '.json"');
        }
        echo json_encode($package, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function setOcdsHeaders(): void {
        if (headers_sent()) return;
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        // X-Content-Type-Options: nosniff ya lo establece setSecurityHeaders() global
        header('Cache-Control: public, max-age=300'); // 5 min
    }

    private function validDate(string $date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
