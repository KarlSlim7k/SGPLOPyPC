<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ReporteRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function resumenDashboard(): array {
        $totales = [];

        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM licitacion');
        $totales['total_licitaciones'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM licitacion WHERE estado_proceso = 'ADJUDICADA'");
        $totales['total_adjudicadas'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM licitacion WHERE estado_proceso = 'PUBLICADA'");
        $totales['total_publicadas'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM proveedor');
        $totales['total_proveedores'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM contrato');
        $totales['total_contratos'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM participacion');
        $totales['total_participaciones'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM propuesta WHERE estatus IN ('RECIBIDA','EN_REVISION','ACEPTADA','RECHAZADA')");
        $totales['total_propuestas'] = (int) $stmt->fetchColumn();

        return $totales;
    }

    public function licitacionesPorEstado(): array {
        $stmt = $this->db->query(
            'SELECT estado_proceso, COUNT(*) AS cantidad FROM licitacion GROUP BY estado_proceso ORDER BY cantidad DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function licitacionesPorTipo(): array {
        $stmt = $this->db->query(
            'SELECT l.tipo_procedimiento, COUNT(*) AS cantidad, COALESCE(SUM(c.monto_contrato), 0) AS monto_total_contratado '
            . 'FROM licitacion l '
            . 'LEFT JOIN contrato c ON c.id_licitacion = l.id_licitacion '
            . 'GROUP BY l.tipo_procedimiento '
            . 'ORDER BY cantidad DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function licitacionesPorMes(int $year): array {
        $stmt = $this->db->prepare(
            'SELECT MONTH(fecha_creacion) AS mes, COUNT(*) AS cantidad '
            . 'FROM licitacion WHERE YEAR(fecha_creacion) = :year '
            . 'GROUP BY MONTH(fecha_creacion) ORDER BY MONTH(fecha_creacion)'
        );
        $stmt->execute(['year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function participacionProveedores(): array {
        $stmt = $this->db->query(
            'SELECT COUNT(*) AS inscritos FROM participacion'
        );
        $inscritos = (int) $stmt->fetchColumn();

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS propuestas FROM participacion WHERE estatus = 'PROPUESTA_ENVIADA'"
        );
        $propuestas = (int) $stmt->fetchColumn();

        $tasa = $inscritos > 0 ? round(($propuestas / $inscritos) * 100, 2) : 0.0;

        return [
            'proveedores_inscritos' => $inscritos,
            'propuestas_enviadas' => $propuestas,
            'tasa_participacion_pct' => $tasa,
        ];
    }

    public function adjudicacionesPorPeriodo(string $from, string $to): array {
        $stmt = $this->db->prepare(
            "SELECT DATE(fecha_adjudicacion) AS fecha, COUNT(*) AS cantidad, SUM(monto_contrato) AS monto_total "
            . "FROM contrato WHERE fecha_adjudicacion BETWEEN :from AND :to "
            . "GROUP BY DATE(fecha_adjudicacion) ORDER BY fecha"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tiempoPromedioPublicacionAdjudicacion(): ?float {
        $stmt = $this->db->query(
            "SELECT AVG(DATEDIFF(c.fecha_adjudicacion, fp.fecha_real)) AS promedio_dias "
            . "FROM contrato c "
            . "JOIN licitacion l ON c.id_licitacion = l.id_licitacion "
            . "JOIN fecha_proceso fp ON l.id_licitacion = fp.id_licitacion AND fp.tipo_fecha = 'PUBLICACION_CONVOCATORIA' "
            . "WHERE fp.fecha_real IS NOT NULL"
        );
        $val = $stmt->fetchColumn();
        return $val !== null ? round((float) $val, 2) : null;
    }

    public function findLicitacionesForExport(?string $estado, ?int $dependencia, ?string $from, ?string $to): array {
        $where = [];
        $params = [];

        if ($estado !== null && $estado !== '') {
            $where[] = 'l.estado_proceso = :estado';
            $params['estado'] = $estado;
        }
        if ($dependencia !== null && $dependencia > 0) {
            $where[] = 'l.id_dependencia = :dependencia';
            $params['dependencia'] = $dependencia;
        }
        if ($from !== null && $from !== '') {
            $where[] = 'l.fecha_creacion >= :from';
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $where[] = 'l.fecha_creacion <= :to';
            $params['to'] = $to . ' 23:59:59';
        }

        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'l.tipo_procedimiento, l.descripcion_proyecto, l.presupuesto_estimado, '
             . 'l.ubicacion_proyecto, l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion, '
             . 'u.nombre AS responsable_nombre '
             . 'FROM licitacion l '
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

    public function findContratosForExport(?string $estatus, ?string $from, ?string $to): array {
        $where = [];
        $params = [];

        if ($estatus !== null && $estatus !== '') {
            $where[] = 'c.estatus = :estatus';
            $params['estatus'] = $estatus;
        }
        if ($from !== null && $from !== '') {
            $where[] = 'c.fecha_adjudicacion >= :from';
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $where[] = 'c.fecha_adjudicacion <= :to';
            $params['to'] = $to . ' 23:59:59';
        }

        $sql = 'SELECT c.id_contrato, c.numero_contrato, c.monto_contrato, c.fecha_adjudicacion, c.fecha_inicio, c.fecha_fin, c.estatus, '
             . 'l.numero_licitacion, l.tipo_procedimiento, d.nombre AS dependencia_nombre, '
             . 'p.nombre_empresa, p.registro_fiscal '
             . 'FROM contrato c '
             . 'JOIN licitacion l ON l.id_licitacion = c.id_licitacion '
             . 'JOIN dependencia d ON d.id_dependencia = l.id_dependencia '
             . 'JOIN proveedor p ON p.id_proveedor = c.id_proveedor';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.fecha_adjudicacion DESC, c.id_contrato DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findHistorialByLicitacion(int $idLicitacion): array {
        $items = [];

        // Cambios de estado en historial_cambio
        $stmt = $this->db->prepare(
            "SELECT h.id_historial, h.id_usuario, u.nombre AS usuario_nombre, h.accion, "
            . "h.valores_anteriores, h.valores_nuevos, h.fecha_accion, 'AUDITORIA' AS tipo_evento "
            . "FROM historial_cambio h "
            . "JOIN usuario u ON h.id_usuario = u.id_usuario "
            . "WHERE h.tabla_afectada = 'licitacion' AND h.id_registro_afectado = :id "
            . "ORDER BY h.fecha_accion DESC"
        );
        $stmt->execute(['id' => $idLicitacion]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = $row;
        }

        // Evaluaciones/dictamenes
        $stmt = $this->db->prepare(
            "SELECT e.id_evaluacion, e.id_evaluador AS id_usuario, u.nombre AS usuario_nombre, 'EVALUACION' AS accion, "
            . "NULL AS valores_anteriores, "
            . "JSON_OBJECT('puntaje_tecnico', e.puntaje_tecnico, 'puntaje_economico', e.puntaje_economico, 'puntaje_total', e.puntaje_total, 'dictamen', e.dictamen, 'observaciones', e.observaciones) AS valores_nuevos, "
            . "e.fecha_evaluacion AS fecha_accion, 'EVALUACION' AS tipo_evento "
            . "FROM evaluacion e "
            . "JOIN propuesta pr ON e.id_propuesta = pr.id_propuesta "
            . "JOIN participacion pa ON pr.id_participacion = pa.id_participacion "
            . "JOIN usuario u ON e.id_evaluador = u.id_usuario "
            . "WHERE pa.id_licitacion = :id "
            . "ORDER BY e.fecha_evaluacion DESC"
        );
        $stmt->execute(['id' => $idLicitacion]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = $row;
        }

        // Contrato adjudicado
        $stmt = $this->db->prepare(
            "SELECT c.id_contrato, l.id_usuario_responsable AS id_usuario, u.nombre AS usuario_nombre, 'CREAR' AS accion, "
            . "NULL AS valores_anteriores, "
            . "JSON_OBJECT('numero_contrato', c.numero_contrato, 'monto_contrato', c.monto_contrato, 'fecha_adjudicacion', c.fecha_adjudicacion, 'estatus', c.estatus) AS valores_nuevos, "
            . "c.fecha_creacion AS fecha_accion, 'CONTRATO' AS tipo_evento "
            . "FROM contrato c "
            . "JOIN licitacion l ON c.id_licitacion = l.id_licitacion "
            . "JOIN usuario u ON l.id_usuario_responsable = u.id_usuario "
            . "WHERE c.id_licitacion = :id "
            . "ORDER BY c.fecha_creacion DESC"
        );
        $stmt->execute(['id' => $idLicitacion]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = $row;
        }

        // Documentos públicos asociados a la licitación (bases, anexos, actas, aclaraciones)
        $stmt = $this->db->prepare(
            "SELECT d.id_documento, d.subido_por AS id_usuario, u.nombre AS usuario_nombre, 'CREAR' AS accion, "
            . "NULL AS valores_anteriores, "
            . "JSON_OBJECT('nombre_archivo', d.nombre_archivo, 'tipo_documento', d.tipo_documento, 'version', d.version, 'fecha_subida', d.fecha_subida) AS valores_nuevos, "
            . "d.fecha_subida AS fecha_accion, 'DOCUMENTO' AS tipo_evento "
            . "FROM documento d "
            . "JOIN usuario u ON d.subido_por = u.id_usuario "
            . "WHERE d.id_licitacion = :id AND d.tipo_documento IN ('BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACTA_PROCESO','ACLARACION') "
            . "ORDER BY d.fecha_subida DESC"
        );
        $stmt->execute(['id' => $idLicitacion]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = $row;
        }

        // Ordenar todo por fecha descendente
        usort($items, function (array $a, array $b): int {
            $fa = $a['fecha_accion'] ?? '0000-00-00 00:00:00';
            $fb = $b['fecha_accion'] ?? '0000-00-00 00:00:00';
            return $fb <=> $fa;
        });

        return $items;
    }
}
