<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PublicRepository.php';

class PublicService {
    private PublicRepository $repo;

    public function __construct() {
        $this->repo = new PublicRepository();
    }

    public function listConvocatorias(
        int $page,
        int $limit,
        string $sortField,
        string $sortOrder,
        ?string $search = null,
        ?string $estado = null,
        ?string $tipo = null,
        ?int $dependencia = null,
        ?int $year = null
    ): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findConvocatorias($page, $limit, $sortField, $sortOrder, $search, $estado, $tipo, $dependencia, $year);
    }

    public function getConvocatoria(int $id): ?array {
        return $this->repo->findConvocatoriaById($id);
    }

    public function listResultados(int $page, int $limit, ?string $search = null): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findResultados($page, $limit, $search);
    }

    public function listContratos(int $page, int $limit, ?string $estatus = null, ?int $year = null): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findContratosPublicos($page, $limit, $estatus, $year);
    }

    public function listEvaluaciones(int $page, int $limit): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findEvaluacionesPublicas($page, $limit);
    }

    public function listHistorial(int $page, int $limit, ?int $year = null, ?string $tipo = null, ?string $search = null): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findHistorialPublico($page, $limit, $year, $tipo, $search);
    }

    public function estadisticas(): array {
        return $this->repo->getEstadisticasPublicas();
    }

    public function listConvocatoriaDocumentos(int $idLicitacion): array {
        return $this->repo->findDocumentosPublicosByLicitacion($idLicitacion);
    }

    public function getDocumentoPublico(int $idDocumento): ?array {
        return $this->repo->findDocumentoPublicoById($idDocumento);
    }
}
