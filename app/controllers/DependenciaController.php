<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/DependenciaService.php';
require_once __DIR__ . '/../helpers/response.php';

class DependenciaController {
    private DependenciaService $service;

    public function __construct() {
        $this->service = new DependenciaService();
    }

    public function list(): never {
        jsonResponse(true, 'Listado de dependencias', $this->service->listActivas(), null, 200);
    }
}
