<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class LicitacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findAll(?string $estado = null, ?string $tipo = null, ?int $dependencia = null): array {
        $where = [];
        $params = [];
        if ($estado !== null && $estado !== '') {
            $where[] = 'l.estado_proceso = :estado';
            $params['estado'] = $estado;
        }
        if ($tipo !== null && $tipo !== '') {
            $where[] = 'l.tipo_procedimiento = :tipo';
            $params['tipo'] = $tipo;
        }
        if ($dependencia !== null) {
            $where[] = 'l.id_dependencia = :dependencia';
            $params['dependencia'] = $dependencia;
        }
        $sql = 'SELECT l.*, d.nombre AS dependencia_nombre, u.nombre AS responsable_nombre FROM licitacion l '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . 'JOIN usuario u ON l.id_usuario_responsable = u.id_usuario';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY l.fecha_creacion DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT l.*, d.nombre AS dependencia_nombre, u.nombre AS responsable_nombre FROM licitacion l '
            . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
            . 'JOIN usuario u ON l.id_usuario_responsable = u.id_usuario '
            . 'WHERE l.id_licitacion = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByNumero(string $numero): ?array {
        $stmt = $this->db->prepare('SELECT * FROM licitacion WHERE numero_licitacion = :numero LIMIT 1');
        $stmt->execute(['numero' => $numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO licitacion (numero_licitacion, id_dependencia, id_usuario_responsable, tipo_procedimiento, descripcion_proyecto, presupuesto_estimado, ubicacion_proyecto, estado_proceso, fecha_creacion, fecha_actualizacion) '
            . 'VALUES (:numero_licitacion, :id_dependencia, :id_usuario_responsable, :tipo_procedimiento, :descripcion_proyecto, :presupuesto_estimado, :ubicacion_proyecto, :estado_proceso, NOW(), NOW())'
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
        $sql = 'UPDATE licitacion SET ' . implode(', ', $fields) . ', fecha_actualizacion = NOW() WHERE id_licitacion = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM licitacion WHERE id_licitacion = :id');
        $stmt->execute(['id' => $id]);
    }
}
