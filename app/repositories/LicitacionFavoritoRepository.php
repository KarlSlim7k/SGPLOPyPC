<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class LicitacionFavoritoRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function add(int $idUsuario, int $idLicitacion): int {
        $stmt = $this->db->prepare(
            'INSERT INTO licitacion_favorito (id_usuario, id_licitacion) VALUES (:id_usuario, :id_licitacion)'
        );
        $stmt->execute([
            'id_usuario' => $idUsuario,
            'id_licitacion' => $idLicitacion,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function remove(int $idUsuario, int $idLicitacion): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM licitacion_favorito WHERE id_usuario = :id_usuario AND id_licitacion = :id_licitacion'
        );
        $stmt->execute([
            'id_usuario' => $idUsuario,
            'id_licitacion' => $idLicitacion,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function exists(int $idUsuario, int $idLicitacion): bool {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM licitacion_favorito WHERE id_usuario = :id_usuario AND id_licitacion = :id_licitacion LIMIT 1'
        );
        $stmt->execute([
            'id_usuario' => $idUsuario,
            'id_licitacion' => $idLicitacion,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function findByUsuario(int $idUsuario, int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT lf.id_favorito, lf.fecha_creacion, '
             . 'l.id_licitacion, l.numero_licitacion, l.descripcion_proyecto, l.estado_proceso, l.tipo_procedimiento, '
             . 'd.nombre AS dependencia_nombre '
             . 'FROM licitacion_favorito lf '
             . 'JOIN licitacion l ON l.id_licitacion = lf.id_licitacion '
             . 'JOIN dependencia d ON d.id_dependencia = l.id_dependencia '
             . 'WHERE lf.id_usuario = :id_usuario '
             . 'ORDER BY lf.fecha_creacion DESC '
             . 'LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM licitacion_favorito WHERE id_usuario = :id_usuario'
        );
        $countStmt->execute(['id_usuario' => $idUsuario]);
        $total = (int) $countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function countByUsuario(int $idUsuario): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM licitacion_favorito WHERE id_usuario = :id_usuario'
        );
        $stmt->execute(['id_usuario' => $idUsuario]);
        return (int) $stmt->fetchColumn();
    }

    public function findRecentByUsuario(int $idUsuario, int $limit = 3): array {
        $stmt = $this->db->prepare(
            'SELECT lf.id_favorito, lf.fecha_creacion, '
            . 'l.id_licitacion, l.numero_licitacion, l.descripcion_proyecto, l.estado_proceso, '
            . 'd.nombre AS dependencia_nombre '
            . 'FROM licitacion_favorito lf '
            . 'JOIN licitacion l ON l.id_licitacion = lf.id_licitacion '
            . 'JOIN dependencia d ON d.id_dependencia = l.id_dependencia '
            . 'WHERE lf.id_usuario = :id_usuario '
            . 'ORDER BY lf.fecha_creacion DESC '
            . 'LIMIT :limit'
        );
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
