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
}
