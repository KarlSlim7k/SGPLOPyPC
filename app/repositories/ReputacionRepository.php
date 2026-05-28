<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ReputacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findByContrato(int $idContrato): ?array {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.nombre AS evaluador_nombre
             FROM proveedor_evaluacion_postcontrato e
             JOIN usuario u ON u.id_usuario = e.id_usuario_evaluador
             WHERE e.id_contrato = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idContrato]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByProveedor(int $idProveedor, int $limit = 20): array {
        $stmt = $this->db->prepare(
            'SELECT e.*, c.numero_contrato, l.descripcion_proyecto,
                    u.nombre AS evaluador_nombre
             FROM proveedor_evaluacion_postcontrato e
             JOIN contrato c ON c.id_contrato = e.id_contrato
             JOIN licitacion l ON l.id_licitacion = c.id_licitacion
             JOIN usuario u ON u.id_usuario = e.id_usuario_evaluador
             WHERE e.id_proveedor = :id
             ORDER BY e.fecha_evaluacion DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':id', $idProveedor, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO proveedor_evaluacion_postcontrato
                (id_contrato, id_proveedor, puntualidad, calidad, comunicacion,
                 cumplimiento_alcance, promedio, comentarios, id_usuario_evaluador)
             VALUES
                (:id_contrato, :id_proveedor, :puntualidad, :calidad, :comunicacion,
                 :cumplimiento_alcance, :promedio, :comentarios, :id_usuario_evaluador)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Recalcula y actualiza score_reputacion y total_evaluaciones del proveedor.
     * Usa AVG(promedio) de todas sus evaluaciones.
     */
    public function recalcularScore(int $idProveedor): void {
        $stmt = $this->db->prepare(
            'UPDATE proveedor
             SET score_reputacion = (
                 SELECT ROUND(AVG(promedio), 2)
                 FROM proveedor_evaluacion_postcontrato
                 WHERE id_proveedor = :id
             ),
             total_evaluaciones = (
                 SELECT COUNT(*)
                 FROM proveedor_evaluacion_postcontrato
                 WHERE id_proveedor = :id2
             )
             WHERE id_proveedor = :id3'
        );
        $stmt->execute(['id' => $idProveedor, 'id2' => $idProveedor, 'id3' => $idProveedor]);
    }

    public function findScoreProveedor(int $idProveedor): array {
        $stmt = $this->db->prepare(
            'SELECT score_reputacion, total_evaluaciones FROM proveedor WHERE id_proveedor = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idProveedor]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['score_reputacion' => null, 'total_evaluaciones' => 0];
    }
}
