<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PublicRepository.php';

class PublicService {
    private PublicRepository $repo;

    public function __construct() {
        $this->repo = new PublicRepository();
    }

    public function listConvocatorias(int $page, int $limit, string $sortField, string $sortOrder): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findConvocatorias($page, $limit, $sortField, $sortOrder);
    }

    public function getConvocatoria(int $id): ?array {
        return $this->repo->findConvocatoriaById($id);
    }

    public function listResultados(int $page, int $limit): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findResultados($page, $limit);
    }

    public function listContratos(int $page, int $limit): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        return $this->repo->findContratosPublicos($page, $limit);
    }
}
