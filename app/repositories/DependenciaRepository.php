<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class DependenciaRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findAllActivas(): array {
        $stmt = $this->db->query(
            'SELECT id_dependencia, nombre, siglas, email_contacto, activa '
            . 'FROM dependencia WHERE activa = 1 ORDER BY nombre ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
