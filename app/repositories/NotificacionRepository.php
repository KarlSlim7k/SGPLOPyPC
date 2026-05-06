<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class NotificacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO notificacion (id_usuario_destino, id_licitacion, tipo_notificacion, titulo, mensaje, leida, fecha_envio, fecha_lectura) '
            . 'VALUES (:id_usuario_destino, :id_licitacion, :tipo_notificacion, :titulo, :mensaje, 0, NOW(), NULL)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM notificacion WHERE id_notificacion = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByUsuario(int $idUsuario, bool $soloNoLeidas = false): array {
        $sql = 'SELECT n.*, l.numero_licitacion FROM notificacion n '
             . 'LEFT JOIN licitacion l ON n.id_licitacion = l.id_licitacion '
             . 'WHERE n.id_usuario_destino = :id_usuario';
        if ($soloNoLeidas) {
            $sql .= ' AND n.leida = 0';
        }
        $sql .= ' ORDER BY n.fecha_envio DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_usuario' => $idUsuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarLeida(int $id, ?string $fechaLectura = null): void {
        $stmt = $this->db->prepare(
            'UPDATE notificacion SET leida = 1, fecha_lectura = :fecha_lectura WHERE id_notificacion = :id'
        );
        $stmt->execute([
            'id' => $id,
            'fecha_lectura' => $fechaLectura ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function findDestinatariosByLicitacion(int $idLicitacion): array {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT p.id_usuario FROM participacion pa '
            . 'JOIN proveedor p ON pa.id_proveedor = p.id_proveedor '
            . 'WHERE pa.id_licitacion = :id'
        );
        $stmt->execute(['id' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findDestinatariosByLicitacionAll(int $idLicitacion): array {
        // Todos los usuarios que participaron (inscritos o con propuesta) + el responsable
        $stmt = $this->db->prepare(
            'SELECT DISTINCT p.id_usuario FROM participacion pa '
            . 'JOIN proveedor p ON pa.id_proveedor = p.id_proveedor '
            . 'WHERE pa.id_licitacion = :id '
            . 'UNION '
            . 'SELECT id_usuario_responsable FROM licitacion WHERE id_licitacion = :id'
        );
        $stmt->execute(['id' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
