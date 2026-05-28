<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class AuditoriaRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Construye y ejecuta una consulta paginada y filtrada de historial_cambio.
     *
     * @param array $filters {
     *   id_usuario?: int,
     *   accion?: string,
     *   tabla?: string,
     *   request_id?: string,
     *   from?: string (Y-m-d),
     *   to?: string (Y-m-d),
     * }
     * @param int $page  >= 1
     * @param int $limit 1..200
     * @return array{rows: array, total: int}
     */
    public function findPaginated(array $filters, int $page, int $limit): array {
        [$where, $params] = $this->buildWhere($filters);
        $offset = max(0, ($page - 1) * $limit);

        $sqlData = "SELECT
                        h.id_historial,
                        h.id_usuario,
                        u.nombre AS usuario_nombre,
                        u.email AS usuario_email,
                        u.rol AS usuario_rol,
                        h.tabla_afectada,
                        h.id_registro_afectado,
                        h.accion,
                        h.valores_anteriores,
                        h.valores_nuevos,
                        h.ip_origen,
                        h.user_agent,
                        h.request_id,
                        h.fecha_accion
                    FROM historial_cambio h
                    LEFT JOIN usuario u ON u.id_usuario = h.id_usuario
                    {$where}
                    ORDER BY h.fecha_accion DESC, h.id_historial DESC
                    LIMIT :limit OFFSET :offset";

        $sqlCount = "SELECT COUNT(*) AS total
                     FROM historial_cambio h
                     {$where}";

        $stmtData = $this->db->prepare($sqlData);
        foreach ($params as $k => $v) {
            $stmtData->bindValue($k, $v);
        }
        $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtData->execute();
        $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmtCount = $this->db->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $total = (int) ($stmtCount->fetchColumn() ?: 0);

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Versión sin paginación, usada para exportar CSV (límite duro de 50,000 filas).
     */
    public function findForExport(array $filters): array {
        [$where, $params] = $this->buildWhere($filters);

        $sql = "SELECT
                    h.id_historial,
                    h.id_usuario,
                    u.nombre AS usuario_nombre,
                    u.email AS usuario_email,
                    u.rol AS usuario_rol,
                    h.tabla_afectada,
                    h.id_registro_afectado,
                    h.accion,
                    h.ip_origen,
                    h.user_agent,
                    h.request_id,
                    h.fecha_accion
                FROM historial_cambio h
                LEFT JOIN usuario u ON u.id_usuario = h.id_usuario
                {$where}
                ORDER BY h.fecha_accion DESC, h.id_historial DESC
                LIMIT 50000";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lista de valores distintos disponibles para los filtros (para alimentar selects del frontend).
     */
    public function distinctValues(): array {
        $tablas = $this->db->query(
            'SELECT DISTINCT tabla_afectada FROM historial_cambio ORDER BY tabla_afectada'
        )->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $acciones = $this->db->query(
            'SELECT DISTINCT accion FROM historial_cambio ORDER BY accion'
        )->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return [
            'tablas' => array_values($tablas),
            'acciones' => array_values($acciones),
        ];
    }

    private function buildWhere(array $filters): array {
        $conditions = [];
        $params = [];

        if (!empty($filters['id_usuario'])) {
            $conditions[] = 'h.id_usuario = :id_usuario';
            $params[':id_usuario'] = (int) $filters['id_usuario'];
        }
        if (!empty($filters['accion'])) {
            $conditions[] = 'h.accion = :accion';
            $params[':accion'] = $filters['accion'];
        }
        if (!empty($filters['tabla'])) {
            $conditions[] = 'h.tabla_afectada = :tabla';
            $params[':tabla'] = $filters['tabla'];
        }
        if (!empty($filters['request_id'])) {
            $conditions[] = 'h.request_id = :request_id';
            $params[':request_id'] = $filters['request_id'];
        }
        if (!empty($filters['from'])) {
            $conditions[] = 'h.fecha_accion >= :from';
            $params[':from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $conditions[] = 'h.fecha_accion <= :to';
            $params[':to'] = $filters['to'] . ' 23:59:59';
        }

        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        return [$where, $params];
    }
}
