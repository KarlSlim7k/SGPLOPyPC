<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class SupportTicketRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO soporte_ticket (folio, nombre, email, telefono, asunto, mensaje, estado, created_at, updated_at) '
            . 'VALUES (:folio, :nombre, :email, :telefono, :asunto, :mensaje, :estado, NOW(), NOW())'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }
}
