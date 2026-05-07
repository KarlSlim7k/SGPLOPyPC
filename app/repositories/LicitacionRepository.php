<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class LicitacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findAll(?string $estado = null, ?string $tipo = null, ?int $dependencia = null, ?array $estadosPermitidos = null): array {
        $where = [];
        $params = [];
        if ($estadosPermitidos !== null && !empty($estadosPermitidos)) {
            $placeholders = [];
            foreach ($estadosPermitidos as $idx => $estadoPermitido) {
                $key = 'estado_permitido_' . $idx;
                $placeholders[] = ':' . $key;
                $params[$key] = $estadoPermitido;
            }
            $where[] = 'l.estado_proceso IN (' . implode(',', $placeholders) . ')';
        } elseif ($estado !== null && $estado !== '') {
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
        $sql = 'SELECT l.*, d.nombre AS dependencia_nombre, u.nombre AS responsable_nombre, '
             . 'fp_publicacion.fecha_programada AS fecha_publicacion_convocatoria, '
             . 'fp_junta.fecha_programada AS fecha_junta_aclaraciones, '
             . 'fp_recepcion.fecha_programada AS fecha_recepcion_propuestas, '
             . 'fp_apertura.fecha_programada AS fecha_apertura_propuestas, '
             . 'fp_fallo.fecha_programada AS fecha_fallo_adjudicacion, '
             . 'c.id_contrato AS id_contrato_relacionado '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . 'JOIN usuario u ON l.id_usuario_responsable = u.id_usuario '
             . "LEFT JOIN fecha_proceso fp_publicacion ON fp_publicacion.id_licitacion = l.id_licitacion AND fp_publicacion.tipo_fecha = 'PUBLICACION_CONVOCATORIA' "
             . "LEFT JOIN fecha_proceso fp_junta ON fp_junta.id_licitacion = l.id_licitacion AND fp_junta.tipo_fecha = 'JUNTA_ACLARACIONES' "
             . "LEFT JOIN fecha_proceso fp_recepcion ON fp_recepcion.id_licitacion = l.id_licitacion AND fp_recepcion.tipo_fecha = 'RECEPCION_PROPUESTAS' "
             . "LEFT JOIN fecha_proceso fp_apertura ON fp_apertura.id_licitacion = l.id_licitacion AND fp_apertura.tipo_fecha = 'APERTURA_PROPUESTAS' "
             . "LEFT JOIN fecha_proceso fp_fallo ON fp_fallo.id_licitacion = l.id_licitacion AND fp_fallo.tipo_fecha = 'FALLO_ADJUDICACION' "
             . 'LEFT JOIN contrato c ON c.id_licitacion = l.id_licitacion';
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
            'SELECT l.*, d.nombre AS dependencia_nombre, u.nombre AS responsable_nombre, '
            . 'fp_publicacion.fecha_programada AS fecha_publicacion_convocatoria, '
            . 'fp_junta.fecha_programada AS fecha_junta_aclaraciones, '
            . 'fp_recepcion.fecha_programada AS fecha_recepcion_propuestas, '
            . 'fp_apertura.fecha_programada AS fecha_apertura_propuestas, '
            . 'fp_fallo.fecha_programada AS fecha_fallo_adjudicacion, '
            . 'c.id_contrato AS id_contrato_relacionado '
            . 'FROM licitacion l '
            . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
            . 'JOIN usuario u ON l.id_usuario_responsable = u.id_usuario '
            . "LEFT JOIN fecha_proceso fp_publicacion ON fp_publicacion.id_licitacion = l.id_licitacion AND fp_publicacion.tipo_fecha = 'PUBLICACION_CONVOCATORIA' "
            . "LEFT JOIN fecha_proceso fp_junta ON fp_junta.id_licitacion = l.id_licitacion AND fp_junta.tipo_fecha = 'JUNTA_ACLARACIONES' "
            . "LEFT JOIN fecha_proceso fp_recepcion ON fp_recepcion.id_licitacion = l.id_licitacion AND fp_recepcion.tipo_fecha = 'RECEPCION_PROPUESTAS' "
            . "LEFT JOIN fecha_proceso fp_apertura ON fp_apertura.id_licitacion = l.id_licitacion AND fp_apertura.tipo_fecha = 'APERTURA_PROPUESTAS' "
            . "LEFT JOIN fecha_proceso fp_fallo ON fp_fallo.id_licitacion = l.id_licitacion AND fp_fallo.tipo_fecha = 'FALLO_ADJUDICACION' "
            . 'LEFT JOIN contrato c ON c.id_licitacion = l.id_licitacion '
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
