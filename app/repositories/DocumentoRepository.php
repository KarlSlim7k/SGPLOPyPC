<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class DocumentoRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT d.*, u.nombre AS subido_por_nombre FROM documento d '
            . 'JOIN usuario u ON d.subido_por = u.id_usuario WHERE d.id_documento = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO documento (nombre_archivo, ruta_almacenamiento, tipo_documento, id_licitacion, id_propuesta, id_proveedor, id_contrato, id_evaluacion, version, subido_por, fecha_subida, tamano_bytes) '
            . 'VALUES (:nombre_archivo, :ruta_almacenamiento, :tipo_documento, :id_licitacion, :id_propuesta, :id_proveedor, :id_contrato, :id_evaluacion, :version, :subido_por, NOW(), :tamano_bytes)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }
}
