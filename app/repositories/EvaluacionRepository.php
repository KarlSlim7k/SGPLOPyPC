<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class EvaluacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT e.*, p.id_participacion, pa.id_licitacion, pa.id_proveedor '
            . 'FROM evaluacion e '
            . 'JOIN propuesta p ON e.id_propuesta = p.id_propuesta '
            . 'JOIN participacion pa ON p.id_participacion = pa.id_participacion '
            . 'WHERE e.id_evaluacion = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByPropuesta(int $idPropuesta): ?array {
        $stmt = $this->db->prepare(
            'SELECT e.*, p.id_participacion, pa.id_licitacion, pa.id_proveedor '
            . 'FROM evaluacion e '
            . 'JOIN propuesta p ON e.id_propuesta = p.id_propuesta '
            . 'JOIN participacion pa ON p.id_participacion = pa.id_participacion '
            . 'WHERE e.id_propuesta = :id_propuesta LIMIT 1'
        );
        $stmt->execute(['id_propuesta' => $idPropuesta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByLicitacion(int $idLicitacion): array {
        $stmt = $this->db->prepare(
            'SELECT e.*, p.id_participacion, pa.id_licitacion, pa.id_proveedor, pr.nombre_empresa '
            . 'FROM evaluacion e '
            . 'JOIN propuesta p ON e.id_propuesta = p.id_propuesta '
            . 'JOIN participacion pa ON p.id_participacion = pa.id_participacion '
            . 'JOIN proveedor pr ON pa.id_proveedor = pr.id_proveedor '
            . 'WHERE pa.id_licitacion = :id_licitacion ORDER BY e.puntaje_total DESC'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(?int $idLicitacion = null): array {
        $sql = 'SELECT e.*, p.monto_propuesta, p.descripcion_tecnica, '
             . 'pa.id_licitacion, pa.id_proveedor, li.numero_licitacion, pr.nombre_empresa, pr.registro_fiscal '
             . 'FROM evaluacion e '
             . 'JOIN propuesta p ON e.id_propuesta = p.id_propuesta '
             . 'JOIN participacion pa ON p.id_participacion = pa.id_participacion '
             . 'JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
             . 'JOIN proveedor pr ON pa.id_proveedor = pr.id_proveedor';
        $params = [];
        if ($idLicitacion !== null) {
            $sql .= ' WHERE pa.id_licitacion = :id_licitacion';
            $params['id_licitacion'] = $idLicitacion;
        }
        $sql .= ' ORDER BY e.fecha_evaluacion DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO evaluacion (id_propuesta, id_evaluador, puntaje_tecnico, puntaje_economico, puntaje_total, observaciones, dictamen, fecha_evaluacion) '
            . 'VALUES (:id_propuesta, :id_evaluador, :puntaje_tecnico, :puntaje_economico, :puntaje_total, :observaciones, :dictamen, NOW())'
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
        $sql = 'UPDATE evaluacion SET ' . implode(', ', $fields) . ' WHERE id_evaluacion = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function findGanadoraByLicitacion(int $idLicitacion): ?array {
        $stmt = $this->db->prepare(
            'SELECT e.*, pr.id_propuesta, pr.id_participacion, pa.id_proveedor, pa.id_licitacion '
            . 'FROM evaluacion e '
            . 'JOIN propuesta pr ON e.id_propuesta = pr.id_propuesta '
            . 'JOIN participacion pa ON pr.id_participacion = pa.id_participacion '
            . 'WHERE pa.id_licitacion = :id_licitacion AND e.dictamen = :dictamen '
            . 'ORDER BY e.puntaje_total DESC, pr.id_propuesta ASC LIMIT 1'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion, 'dictamen' => 'SOLVENTE']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
