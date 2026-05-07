<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/DependenciaRepository.php';

class DependenciaService {
    private DependenciaRepository $repo;

    public function __construct() {
        $this->repo = new DependenciaRepository();
    }

    public function listActivas(): array {
        return $this->repo->findAllActivas();
    }
}
