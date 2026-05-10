<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class AclaracionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findByLicitacion(int $idLicitacion): array {
        $stmt = $this->db->prepare(
            'SELECT a.*, p.nombre_empresa, u.nombre AS respondida_por_nombre '
            . 'FROM aclaracion a '
            . 'JOIN proveedor p ON a.id_proveedor = p.id_proveedor '
            . 'LEFT JOIN usuario u ON a.respondida_por = u.id_usuario '
            . 'WHERE a.id_licitacion = :id ORDER BY a.fecha_pregunta ASC'
        );
        $stmt->execute(['id' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByLicitacionAndProveedor(int $idLicitacion, int $idProveedor): array {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.nombre AS respondida_por_nombre '
            . 'FROM aclaracion a '
            . 'LEFT JOIN usuario u ON a.respondida_por = u.id_usuario '
            . 'WHERE a.id_licitacion = :id_licitacion AND a.id_proveedor = :id_proveedor '
            . 'ORDER BY a.fecha_pregunta ASC'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion, 'id_proveedor' => $idProveedor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $idLicitacion, int $idProveedor, string $pregunta): int {
        $stmt = $this->db->prepare(
            'INSERT INTO aclaracion (id_licitacion, id_proveedor, pregunta) VALUES (:id_licitacion, :id_proveedor, :pregunta)'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion, 'id_proveedor' => $idProveedor, 'pregunta' => $pregunta]);
        return (int) $this->db->lastInsertId();
    }

    public function responder(int $id, string $respuesta, int $idUsuario): void {
        $stmt = $this->db->prepare(
            'UPDATE aclaracion SET respuesta = :respuesta, respondida_por = :respondida_por, fecha_respuesta = NOW() WHERE id_aclaracion = :id'
        );
        $stmt->execute(['respuesta' => $respuesta, 'respondida_por' => $idUsuario, 'id' => $id]);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM aclaracion WHERE id_aclaracion = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
