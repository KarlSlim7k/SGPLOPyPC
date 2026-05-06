<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ProveedorRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findAll(): array {
        $stmt = $this->db->query(
            'SELECT p.*, u.email, u.nombre AS usuario_nombre FROM proveedor p JOIN usuario u ON p.id_usuario = u.id_usuario ORDER BY p.fecha_registro DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.email, u.nombre AS usuario_nombre FROM proveedor p JOIN usuario u ON p.id_usuario = u.id_usuario WHERE p.id_proveedor = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByUsuario(int $idUsuario): ?array {
        $stmt = $this->db->prepare('SELECT * FROM proveedor WHERE id_usuario = :id_usuario LIMIT 1');
        $stmt->execute(['id_usuario' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByRegistroFiscal(string $rf): ?array {
        $stmt = $this->db->prepare('SELECT * FROM proveedor WHERE registro_fiscal = :rf LIMIT 1');
        $stmt->execute(['rf' => $rf]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO proveedor (id_usuario, nombre_empresa, representante_legal, registro_fiscal, domicilio, telefono, especialidad, estatus, fecha_registro) '
            . 'VALUES (:id_usuario, :nombre_empresa, :representante_legal, :registro_fiscal, :domicilio, :telefono, :especialidad, :estatus, NOW())'
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
        $sql = 'UPDATE proveedor SET ' . implode(', ', $fields) . ' WHERE id_proveedor = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}
