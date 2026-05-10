<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ContratoRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT c.*, l.numero_licitacion, p.nombre_empresa, p.registro_fiscal '
            . 'FROM contrato c '
            . 'JOIN licitacion l ON c.id_licitacion = l.id_licitacion '
            . 'JOIN proveedor p ON c.id_proveedor = p.id_proveedor '
            . 'WHERE c.id_contrato = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAll(?string $estatus = null): array {
        $sql = 'SELECT c.*, l.numero_licitacion, p.nombre_empresa, p.registro_fiscal '
             . 'FROM contrato c '
             . 'JOIN licitacion l ON c.id_licitacion = l.id_licitacion '
             . 'JOIN proveedor p ON c.id_proveedor = p.id_proveedor';
        $params = [];
        if ($estatus !== null && $estatus !== '') {
            $sql .= ' WHERE c.estatus = :estatus';
            $params['estatus'] = $estatus;
        }
        $sql .= ' ORDER BY c.fecha_creacion DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByProveedorForPortal(
        int $idProveedor,
        int $page,
        int $limit,
        ?string $estatus = null,
        ?string $search = null,
        ?int $idContrato = null
    ): array {
        $offset = ($page - 1) * $limit;
        $where = ['c.id_proveedor = :id_proveedor'];
        $params = ['id_proveedor' => $idProveedor];

        if ($idContrato !== null && $idContrato > 0) {
            $where[] = 'c.id_contrato = :id_contrato';
            $params['id_contrato'] = $idContrato;
        }

        if ($estatus !== null && trim($estatus) !== '') {
            $where[] = 'c.estatus = :estatus';
            $params['estatus'] = trim($estatus);
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = '(c.numero_contrato LIKE :search_contrato OR li.numero_licitacion LIKE :search_licitacion OR li.descripcion_proyecto LIKE :search_descripcion OR d.nombre LIKE :search_dependencia)';
            $searchLike = '%' . trim($search) . '%';
            $params['search_contrato'] = $searchLike;
            $params['search_licitacion'] = $searchLike;
            $params['search_descripcion'] = $searchLike;
            $params['search_dependencia'] = $searchLike;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT c.id_contrato, c.id_licitacion, c.numero_contrato, c.monto_contrato, '
             . 'c.fecha_adjudicacion, c.fecha_inicio, c.fecha_fin, c.estatus, c.fecha_creacion, c.fecha_firma_proveedor, '
             . 'li.numero_licitacion, li.descripcion_proyecto, li.estado_proceso, d.nombre AS dependencia_nombre '
             . 'FROM contrato c '
             . 'JOIN licitacion li ON c.id_licitacion = li.id_licitacion '
             . 'JOIN dependencia d ON li.id_dependencia = d.id_dependencia '
             . $whereSql
             . ' ORDER BY c.fecha_adjudicacion DESC, c.fecha_creacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = 'SELECT COUNT(*) FROM contrato c '
                  . 'JOIN licitacion li ON c.id_licitacion = li.id_licitacion '
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

    public function findDocumentosByContratoForProveedor(int $idContrato, int $idProveedor): array {
        $stmt = $this->db->prepare(
            'SELECT d.id_documento, d.nombre_archivo, d.tipo_documento, d.version, d.fecha_subida, d.tamano_bytes, u.nombre AS subido_por_nombre '
            . 'FROM documento d '
            . 'JOIN contrato c ON d.id_contrato = c.id_contrato '
            . 'JOIN usuario u ON d.subido_por = u.id_usuario '
            . 'WHERE d.id_contrato = :id_contrato AND c.id_proveedor = :id_proveedor '
            . 'ORDER BY d.fecha_subida DESC'
        );
        $stmt->execute([
            'id_contrato' => $idContrato,
            'id_proveedor' => $idProveedor,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByLicitacion(int $idLicitacion): ?array {
        $stmt = $this->db->prepare(
            'SELECT c.*, l.numero_licitacion, p.nombre_empresa, p.registro_fiscal '
            . 'FROM contrato c '
            . 'JOIN licitacion l ON c.id_licitacion = l.id_licitacion '
            . 'JOIN proveedor p ON c.id_proveedor = p.id_proveedor '
            . 'WHERE c.id_licitacion = :id_licitacion LIMIT 1'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByNumero(string $numero): ?array {
        $stmt = $this->db->prepare('SELECT * FROM contrato WHERE numero_contrato = :numero LIMIT 1');
        $stmt->execute(['numero' => $numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO contrato (id_licitacion, id_proveedor, numero_contrato, monto_contrato, fecha_adjudicacion, fecha_inicio, fecha_fin, estatus, fecha_creacion) '
            . 'VALUES (:id_licitacion, :id_proveedor, :numero_contrato, :monto_contrato, :fecha_adjudicacion, :fecha_inicio, :fecha_fin, :estatus, NOW())'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $k => $v) {
            $fields[] = "$k = :$k";
            $params[$k] = $v;
        }
        $sql = 'UPDATE contrato SET ' . implode(', ', $fields) . ' WHERE id_contrato = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function firmar(int $id, int $idUsuario): void {
        $stmt = $this->db->prepare(
            'UPDATE contrato SET fecha_firma_proveedor = NOW(), firmado_por = :firmado_por WHERE id_contrato = :id'
        );
        $stmt->execute(['id' => $id, 'firmado_por' => $idUsuario]);
    }
}
