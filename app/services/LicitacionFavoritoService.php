<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/LicitacionFavoritoRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class LicitacionFavoritoService {
    private LicitacionFavoritoRepository $repo;

    public function __construct() {
        $this->repo = new LicitacionFavoritoRepository();
    }

    public function agregar(int $idUsuario, int $idLicitacion): array {
        if ($this->repo->exists($idUsuario, $idLicitacion)) {
            return ['ok' => false, 'errors' => ['Esta licitación ya está en tus favoritos.']];
        }
        $id = $this->repo->add($idUsuario, $idLicitacion);
        auditLog($idUsuario, 'licitacion_favorito', $id, 'CREAR', null, ['id_licitacion' => $idLicitacion]);
        return ['ok' => true, 'data' => ['id_favorito' => $id]];
    }

    public function quitar(int $idUsuario, int $idLicitacion): array {
        $removed = $this->repo->remove($idUsuario, $idLicitacion);
        if (!$removed) {
            return ['ok' => false, 'errors' => ['Favorito no encontrado.'], 'status' => 404];
        }
        auditLog($idUsuario, 'licitacion_favorito', 0, 'ELIMINAR', null, ['id_licitacion' => $idLicitacion]);
        return ['ok' => true, 'data' => null];
    }

    public function listar(int $idUsuario, int $page = 1, int $perPage = 20): array {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        return $this->repo->findByUsuario($idUsuario, $page, $perPage);
    }

    public function contar(int $idUsuario): int {
        return $this->repo->countByUsuario($idUsuario);
    }

    public function recientes(int $idUsuario, int $limit = 3): array {
        return $this->repo->findRecentByUsuario($idUsuario, $limit);
    }

    public function esFavorito(int $idUsuario, int $idLicitacion): bool {
        return $this->repo->exists($idUsuario, $idLicitacion);
    }
}
