<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ProveedorMetricasRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function getMetricas(int $idProveedor): array {
        $sql = "
            SELECT
                COUNT(DISTINCT p.id_participacion) AS total_participaciones,
                COUNT(DISTINCT pr.id_propuesta) AS total_propuestas,
                COUNT(DISTINCT CASE WHEN p.estatus = 'GANADOR' THEN p.id_participacion END) AS total_ganadas,
                COALESCE(SUM(pr.monto_propuesta), 0) AS monto_total_propuesto,
                COALESCE(SUM(CASE WHEN p.estatus = 'GANADOR' THEN pr.monto_propuesta ELSE 0 END), 0) AS monto_total_adjudicado,
                (SELECT COUNT(*) FROM contrato c WHERE c.id_proveedor = :pid AND c.estatus IN ('VIGENTE', 'EN_EJECUCION')) AS contratos_vigentes
            FROM participacion p
            LEFT JOIN propuesta pr ON pr.id_participacion = p.id_participacion
            WHERE p.id_proveedor = :id_proveedor";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_proveedor' => $idProveedor, 'pid' => $idProveedor]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalParticipaciones = (int) ($row['total_participaciones'] ?? 0);
        $totalGanadas = (int) ($row['total_ganadas'] ?? 0);
        $tasaGanancia = $totalParticipaciones > 0
            ? round(($totalGanadas / $totalParticipaciones) * 100, 2)
            : 0;

        $sqlMes = "
            SELECT
                DATE_FORMAT(p.fecha_inscripcion, '%Y-%m') AS mes,
                COUNT(*) AS count
            FROM participacion p
            WHERE p.id_proveedor = :id_proveedor
              AND p.fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(p.fecha_inscripcion, '%Y-%m')
            ORDER BY mes";
        $stmtMes = $this->db->prepare($sqlMes);
        $stmtMes->execute(['id_proveedor' => $idProveedor]);
        $participacionesPorMes = $stmtMes->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlTipo = "
            SELECT
                l.tipo_procedimiento,
                COUNT(*) AS count
            FROM participacion p
            JOIN licitacion l ON l.id_licitacion = p.id_licitacion
            WHERE p.id_proveedor = :id_proveedor
            GROUP BY l.tipo_procedimiento
            ORDER BY l.tipo_procedimiento";
        $stmtTipo = $this->db->prepare($sqlTipo);
        $stmtTipo->execute(['id_proveedor' => $idProveedor]);
        $tipoRows = $stmtTipo->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $distribucionPorTipo = [];
        foreach ($tipoRows as $tr) {
            $distribucionPorTipo[$tr['tipo_procedimiento']] = (int) $tr['count'];
        }

        return [
            'total_participaciones' => $totalParticipaciones,
            'total_propuestas' => (int) ($row['total_propuestas'] ?? 0),
            'total_ganadas' => $totalGanadas,
            'tasa_ganancia' => $tasaGanancia,
            'monto_total_propuesto' => (float) ($row['monto_total_propuesto'] ?? 0),
            'monto_total_adjudicado' => (float) ($row['monto_total_adjudicado'] ?? 0),
            'contratos_vigentes' => (int) ($row['contratos_vigentes'] ?? 0),
            'participaciones_por_mes' => $participacionesPorMes,
            'distribucion_por_tipo' => $distribucionPorTipo,
        ];
    }

    public function getTendencia(int $idProveedor): array {
        $sql = "
            SELECT
                CONCAT(YEAR(p.fecha_inscripcion), '-Q', QUARTER(p.fecha_inscripcion)) AS trimestre,
                COUNT(DISTINCT p.id_participacion) AS participaciones,
                COALESCE(SUM(pr.monto_propuesta), 0) AS monto_propuesto,
                COUNT(DISTINCT CASE WHEN p.estatus = 'GANADOR' THEN p.id_participacion END) AS ganadas
            FROM participacion p
            LEFT JOIN propuesta pr ON pr.id_participacion = p.id_participacion
            WHERE p.id_proveedor = :id_proveedor
              AND p.fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 2 YEAR)
            GROUP BY YEAR(p.fecha_inscripcion), QUARTER(p.fecha_inscripcion)
            ORDER BY YEAR(p.fecha_inscripcion), QUARTER(p.fecha_inscripcion)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_proveedor' => $idProveedor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getUltimasParticipaciones(int $idProveedor, int $limit = 5): array {
        $sql = "
            SELECT
                p.id_participacion,
                p.estatus AS participacion_estatus,
                p.fecha_inscripcion,
                l.numero_licitacion,
                l.descripcion_proyecto,
                l.estado_proceso,
                pr.estatus AS propuesta_estatus,
                pr.monto_propuesta
            FROM participacion p
            JOIN licitacion l ON l.id_licitacion = p.id_licitacion
            LEFT JOIN propuesta pr ON pr.id_participacion = p.id_participacion
            WHERE p.id_proveedor = :id_proveedor
            ORDER BY p.fecha_inscripcion DESC
            LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
