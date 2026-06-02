<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ProveedorMetricasRepository.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../helpers/SimpleCache.php';

class ProveedorMetricasService {
    private ProveedorMetricasRepository $repo;
    private ProveedorRepository $proveedorRepo;
    private SimpleCache $cache;

    public function __construct() {
        $this->repo = new ProveedorMetricasRepository();
        $this->proveedorRepo = new ProveedorRepository();
        $this->cache = new SimpleCache('proveedor_metricas');
    }

    public function getMetricas(int $idProveedor): ?array {
        $proveedor = $this->proveedorRepo->findById($idProveedor);
        if (!$proveedor) {
            return null;
        }
        return $this->cache->remember(
            'metricas_' . $idProveedor,
            300,
            function () use ($idProveedor): array {
                $metricas = $this->repo->getMetricas($idProveedor);
                $metricas['ultimas_participaciones'] = $this->repo->getUltimasParticipaciones($idProveedor);
                return $metricas;
            }
        );
    }

    public function getTendencia(int $idProveedor): ?array {
        $proveedor = $this->proveedorRepo->findById($idProveedor);
        if (!$proveedor) {
            return null;
        }
        return $this->cache->remember(
            'tendencia_' . $idProveedor,
            300,
            function () use ($idProveedor): array {
                return $this->repo->getTendencia($idProveedor);
            }
        );
    }

    public function findByIdProveedor(int $idProveedor): ?array {
        return $this->proveedorRepo->findById($idProveedor);
    }
}
