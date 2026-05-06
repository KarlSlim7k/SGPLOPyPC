<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class PublicRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findConvocatorias(int $page, int $limit, string $sortField, string $sortOrder): array {
        $offset = ($page - 1) * $limit;
        $allowedFields = ['fecha_creacion', 'numero_licitacion', 'tipo_procedimiento'];
        $allowedOrders = ['ASC', 'DESC'];
        if (!in_array($sortField, $allowedFields, true)) {
            $sortField = 'fecha_creacion';
        }
        $sortOrder = in_array(strtoupper($sortOrder), $allowedOrders, true) ? strtoupper($sortOrder) : 'DESC';

        // Estados visibles públicamente
        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'l.tipo_procedimiento, l.descripcion_proyecto, l.presupuesto_estimado, '
             . 'l.ubicacion_proyecto, l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . "WHERE l.estado_proceso NOT IN ('BORRADOR','CANCELADA') "
             . "ORDER BY l.{$sortField} {$sortOrder} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->query(
            "SELECT COUNT(*) FROM licitacion WHERE estado_proceso NOT IN ('BORRADOR','CANCELADA')"
        );
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findConvocatoriaById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
            . 'l.tipo_procedimiento, l.descripcion_proyecto, l.presupuesto_estimado, '
            . 'l.ubicacion_proyecto, l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion '
            . 'FROM licitacion l '
            . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
            . 'WHERE l.id_licitacion = :id AND l.estado_proceso NOT IN (\'BORRADOR\',\'CANCELADA\') LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findResultados(int $page, int $limit): array {
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'l.descripcion_proyecto, l.presupuesto_estimado, l.estado_proceso, '
             . 'c.numero_contrato, c.monto_contrato, c.fecha_adjudicacion, '
             . 'p.nombre_empresa AS adjudicatario_nombre_empresa '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . 'LEFT JOIN contrato c ON l.id_licitacion = c.id_licitacion '
             . 'LEFT JOIN proveedor p ON c.id_proveedor = p.id_proveedor '
             . "WHERE l.estado_proceso = 'ADJUDICADA' "
             . 'ORDER BY c.fecha_adjudicacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->query("SELECT COUNT(*) FROM licitacion WHERE estado_proceso = 'ADJUDICADA'");
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findContratosPublicos(int $page, int $limit): array {
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT c.id_contrato, c.numero_contrato, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'c.monto_contrato, c.fecha_adjudicacion, c.fecha_inicio, c.fecha_fin, c.estatus, '
             . 'p.nombre_empresa AS adjudicatario_nombre_empresa '
             . 'FROM contrato c '
             . 'JOIN licitacion l ON c.id_licitacion = l.id_licitacion '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . 'JOIN proveedor p ON c.id_proveedor = p.id_proveedor '
             . 'ORDER BY c.fecha_adjudicacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->query('SELECT COUNT(*) FROM contrato');
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }
}
