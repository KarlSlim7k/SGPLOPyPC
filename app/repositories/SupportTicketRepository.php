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

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM soporte_ticket WHERE id_soporte_ticket = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findForAdmin(int $page, int $limit, ?string $estado = null, ?string $search = null): array {
        $offset = ($page - 1) * $limit;
        $where = ['1=1'];
        $params = [];

        if ($estado !== null && $estado !== '') {
            $where[] = 'estado = :estado';
            $params['estado'] = $estado;
        }
        if ($search !== null && trim($search) !== '') {
            $where[] = '(folio LIKE :search_folio OR nombre LIKE :search_nombre OR email LIKE :search_email OR asunto LIKE :search_asunto)';
            $like = '%' . trim($search) . '%';
            $params['search_folio'] = $like;
            $params['search_nombre'] = $like;
            $params['search_email'] = $like;
            $params['search_asunto'] = $like;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT id_soporte_ticket, folio, nombre, email, telefono, asunto, mensaje, estado, created_at, updated_at '
             . 'FROM soporte_ticket '
             . $whereSql
             . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM soporte_ticket ' . $whereSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function getResumen(): array {
        $row = $this->db->query(
            "SELECT "
            . "COUNT(*) AS total, "
            . "SUM(CASE WHEN estado = 'NUEVO' THEN 1 ELSE 0 END) AS nuevo, "
            . "SUM(CASE WHEN estado = 'EN_PROCESO' THEN 1 ELSE 0 END) AS en_proceso, "
            . "SUM(CASE WHEN estado = 'CERRADO' THEN 1 ELSE 0 END) AS cerrado "
            . 'FROM soporte_ticket'
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($row['total'] ?? 0),
            'nuevo' => (int) ($row['nuevo'] ?? 0),
            'en_proceso' => (int) ($row['en_proceso'] ?? 0),
            'cerrado' => (int) ($row['cerrado'] ?? 0),
        ];
    }

    public function updateEstado(int $id, string $estado): void {
        $stmt = $this->db->prepare(
            'UPDATE soporte_ticket SET estado = :estado, updated_at = NOW() WHERE id_soporte_ticket = :id'
        );
        $stmt->execute([
            'id' => $id,
            'estado' => $estado,
        ]);
    }
}
