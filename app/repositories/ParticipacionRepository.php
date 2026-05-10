<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ParticipacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findByLicitacion(int $idLicitacion): array {
        $stmt = $this->db->prepare(
            'SELECT pa.*, p.nombre_empresa, p.registro_fiscal, u.nombre AS usuario_nombre '
            . 'FROM participacion pa '
            . 'JOIN proveedor p ON pa.id_proveedor = p.id_proveedor '
            . 'JOIN usuario u ON p.id_usuario = u.id_usuario '
            . 'WHERE pa.id_licitacion = :id ORDER BY pa.fecha_inscripcion DESC'
        );
        $stmt->execute(['id' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllForAdmin(
        int $page,
        int $limit,
        ?int $idLicitacion = null,
        ?string $estatus = null,
        ?string $search = null
    ): array {
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];

        if ($idLicitacion !== null && $idLicitacion > 0) {
            $where[] = 'pa.id_licitacion = :id_licitacion';
            $params['id_licitacion'] = $idLicitacion;
        }
        if ($estatus !== null && trim($estatus) !== '') {
            $where[] = 'pa.estatus = :estatus';
            $params['estatus'] = trim($estatus);
        }
        if ($search !== null && trim($search) !== '') {
            $where[] = '(p.nombre_empresa LIKE :search_empresa OR p.registro_fiscal LIKE :search_registro_fiscal OR li.numero_licitacion LIKE :search_numero_licitacion)';
            $searchLike = '%' . trim($search) . '%';
            $params['search_empresa'] = $searchLike;
            $params['search_registro_fiscal'] = $searchLike;
            $params['search_numero_licitacion'] = $searchLike;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT pa.id_participacion, pa.id_licitacion, li.numero_licitacion, pa.id_proveedor, '
             . 'p.nombre_empresa, p.registro_fiscal, pa.estatus, pa.fecha_inscripcion '
             . 'FROM participacion pa '
             . 'JOIN proveedor p ON pa.id_proveedor = p.id_proveedor '
             . 'JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
             . $whereSql
             . ' ORDER BY pa.fecha_inscripcion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = 'SELECT COUNT(*) FROM participacion pa '
                  . 'JOIN proveedor p ON pa.id_proveedor = p.id_proveedor '
                  . 'JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
                  . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findByProveedorForPortal(
        int $idProveedor,
        int $page,
        int $limit,
        ?string $estatus = null,
        ?string $search = null
    ): array {
        $offset = ($page - 1) * $limit;

        $where = ['pa.id_proveedor = :id_proveedor'];
        $params = ['id_proveedor' => $idProveedor];

        if ($estatus !== null && trim($estatus) !== '') {
            $where[] = 'pa.estatus = :estatus';
            $params['estatus'] = trim($estatus);
        }
        if ($search !== null && trim($search) !== '') {
            $where[] = '(li.numero_licitacion LIKE :search_numero OR li.descripcion_proyecto LIKE :search_descripcion OR d.nombre LIKE :search_dependencia)';
            $searchLike = '%' . trim($search) . '%';
            $params['search_numero'] = $searchLike;
            $params['search_descripcion'] = $searchLike;
            $params['search_dependencia'] = $searchLike;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT pa.id_participacion, pa.id_licitacion, li.numero_licitacion, li.descripcion_proyecto, '
             . 'li.estado_proceso, li.presupuesto_estimado, d.nombre AS dependencia_nombre, pa.estatus, '
             . 'pa.fecha_inscripcion, pr.id_propuesta, pr.monto_propuesta, pr.estatus AS estatus_propuesta, '
             . 'pr.fecha_envio '
             . 'FROM participacion pa '
             . 'JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
             . 'JOIN dependencia d ON li.id_dependencia = d.id_dependencia '
             . 'LEFT JOIN propuesta pr ON pr.id_participacion = pa.id_participacion '
             . $whereSql
             . ' ORDER BY pa.fecha_inscripcion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = 'SELECT COUNT(*) FROM participacion pa '
                  . 'JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
                  . 'JOIN dependencia d ON li.id_dependencia = d.id_dependencia '
                  . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findByProveedorAndLicitacion(int $idProveedor, int $idLicitacion): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM participacion WHERE id_proveedor = :id_proveedor AND id_licitacion = :id_licitacion LIMIT 1'
        );
        $stmt->execute(['id_proveedor' => $idProveedor, 'id_licitacion' => $idLicitacion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT pa.*, p.nombre_empresa, p.registro_fiscal FROM participacion pa '
            . 'JOIN proveedor p ON pa.id_proveedor = p.id_proveedor WHERE pa.id_participacion = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO participacion (id_proveedor, id_licitacion, fecha_inscripcion, estatus) '
            . 'VALUES (:id_proveedor, :id_licitacion, NOW(), :estatus)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function updateEstatus(int $id, string $estatus): void {
        $stmt = $this->db->prepare('UPDATE participacion SET estatus = :estatus WHERE id_participacion = :id');
        $stmt->execute(['id' => $id, 'estatus' => $estatus]);
    }

    public function updateEstatusByLicitacion(int $idLicitacion, string $estatus, ?int $excludeParticipacionId = null): void {
        $sql = 'UPDATE participacion SET estatus = :estatus WHERE id_licitacion = :id_licitacion';
        $params = ['estatus' => $estatus, 'id_licitacion' => $idLicitacion];
        if ($excludeParticipacionId !== null) {
            $sql .= ' AND id_participacion != :exclude';
            $params['exclude'] = $excludeParticipacionId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}

class PropuestaRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT pr.*, pa.id_licitacion, pa.id_proveedor FROM propuesta pr '
            . 'JOIN participacion pa ON pr.id_participacion = pa.id_participacion '
            . 'WHERE pr.id_propuesta = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByParticipacion(int $idParticipacion): ?array {
        $stmt = $this->db->prepare('SELECT * FROM propuesta WHERE id_participacion = :id LIMIT 1');
        $stmt->execute(['id' => $idParticipacion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO propuesta (id_participacion, monto_propuesta, descripcion_tecnica, fecha_envio, cumple_requisitos, estatus) '
            . 'VALUES (:id_participacion, :monto_propuesta, :descripcion_tecnica, NOW(), :cumple_requisitos, :estatus)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function findByLicitacion(int $idLicitacion): array {
        $stmt = $this->db->prepare(
            'SELECT pr.*, pa.id_licitacion, pa.id_proveedor '
            . 'FROM propuesta pr '
            . 'JOIN participacion pa ON pr.id_participacion = pa.id_participacion '
            . 'WHERE pa.id_licitacion = :id_licitacion'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(?int $idLicitacion = null): array {
        $sql = 'SELECT pr.*, pa.id_licitacion, pa.id_proveedor, pa.estatus AS estatus_participacion, '
             . 'li.numero_licitacion, pv.nombre_empresa, pv.registro_fiscal '
             . 'FROM propuesta pr '
             . 'JOIN participacion pa ON pr.id_participacion = pa.id_participacion '
             . 'JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
             . 'JOIN proveedor pv ON pa.id_proveedor = pv.id_proveedor';
        $params = [];
        if ($idLicitacion !== null) {
            $sql .= ' WHERE pa.id_licitacion = :id_licitacion';
            $params['id_licitacion'] = $idLicitacion;
        }
        $sql .= ' ORDER BY pr.fecha_envio DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateEstatus(int $id, string $estatus): void {
        $stmt = $this->db->prepare('UPDATE propuesta SET estatus = :estatus WHERE id_propuesta = :id');
        $stmt->execute(['id' => $id, 'estatus' => $estatus]);
    }

    public function updateEstatusByLicitacion(int $idLicitacion, string $estatus, ?int $excludePropuestaId = null): void {
        $sql = 'UPDATE propuesta SET estatus = :estatus WHERE id_participacion IN (SELECT id_participacion FROM participacion WHERE id_licitacion = :id_licitacion)';
        $params = ['estatus' => $estatus, 'id_licitacion' => $idLicitacion];
        if ($excludePropuestaId !== null) {
            $sql .= ' AND id_propuesta != :exclude';
            $params['exclude'] = $excludePropuestaId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}
