<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class TicketSoporteRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO ticket_soporte (id_usuario, asunto, descripcion, prioridad, estado, fecha_creacion, fecha_actualizacion)
             VALUES (:id_usuario, :asunto, :descripcion, :prioridad, :estado, NOW(), NOW())'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT t.*, u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM ticket_soporte t
             JOIN usuario u ON u.id_usuario = t.id_usuario
             WHERE t.id_ticket = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByUsuario(int $idUsuario, int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT t.*, u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM ticket_soporte t
             JOIN usuario u ON u.id_usuario = t.id_usuario
             WHERE t.id_usuario = :id
             ORDER BY t.fecha_actualizacion DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM ticket_soporte WHERE id_usuario = :id');
        $countStmt->execute(['id' => $idUsuario]);
        $total = (int) $countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function findRespuestasByTicket(int $idTicket): array {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol
             FROM ticket_respuesta r
             JOIN usuario u ON u.id_usuario = r.id_usuario
             WHERE r.id_ticket = :id
             ORDER BY r.fecha ASC'
        );
        $stmt->execute(['id' => $idTicket]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addRespuesta(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO ticket_respuesta (id_ticket, id_usuario, mensaje, fecha)
             VALUES (:id_ticket, :id_usuario, :mensaje, NOW())'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function updateEstado(int $id, string $estado): void {
        $stmt = $this->db->prepare(
            'UPDATE ticket_soporte SET estado = :estado, fecha_actualizacion = NOW() WHERE id_ticket = :id'
        );
        $stmt->execute(['id' => $id, 'estado' => $estado]);
    }

    public function getResumenByUsuario(int $idUsuario): array {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = "ABIERTO" THEN 1 ELSE 0 END) AS abiertos,
                SUM(CASE WHEN estado = "EN_PROCESO" THEN 1 ELSE 0 END) AS en_proceso,
                SUM(CASE WHEN estado = "RESUELTO" THEN 1 ELSE 0 END) AS resueltos,
                SUM(CASE WHEN estado = "CERRADO" THEN 1 ELSE 0 END) AS cerrados
             FROM ticket_soporte WHERE id_usuario = :id'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int) ($row['total'] ?? 0),
            'abiertos' => (int) ($row['abiertos'] ?? 0),
            'en_proceso' => (int) ($row['en_proceso'] ?? 0),
            'resueltos' => (int) ($row['resueltos'] ?? 0),
            'cerrados' => (int) ($row['cerrados'] ?? 0),
        ];
    }
}
