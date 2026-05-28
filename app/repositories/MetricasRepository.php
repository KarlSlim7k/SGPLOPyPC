<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class MetricasRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Tiempo de ciclo (publicación → adjudicación) por tipo de procedimiento.
     * Devuelve: días promedio, mediana aproximada, conteo y monto total adjudicado.
     */
    public function tiempoCiclo(?string $from, ?string $to, ?int $idDependencia): array {
        [$where, $params] = $this->buildWhere($from, $to, $idDependencia, 'l');
        // Adjudicada = tiene contrato. Tomamos fecha_publicacion convocatoria → fecha_adjudicacion
        $sql = "
            SELECT
                l.tipo_procedimiento,
                COUNT(DISTINCT l.id_licitacion) AS total_adjudicadas,
                AVG(DATEDIFF(c.fecha_adjudicacion, fp.fecha_programada)) AS dias_promedio,
                MIN(DATEDIFF(c.fecha_adjudicacion, fp.fecha_programada)) AS dias_min,
                MAX(DATEDIFF(c.fecha_adjudicacion, fp.fecha_programada)) AS dias_max,
                SUM(c.monto_contrato) AS monto_total
            FROM licitacion l
            JOIN contrato c ON c.id_licitacion = l.id_licitacion
            JOIN fecha_proceso fp ON fp.id_licitacion = l.id_licitacion
                AND fp.tipo_fecha = 'PUBLICACION_CONVOCATORIA'
            WHERE l.estado_proceso = 'ADJUDICADA'
              AND fp.fecha_programada IS NOT NULL
              AND c.fecha_adjudicacion IS NOT NULL
              {$where}
            GROUP BY l.tipo_procedimiento
            ORDER BY l.tipo_procedimiento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Top N proveedores por número de contratos adjudicados y monto total.
     */
    public function proveedoresTop(?string $from, ?string $to, int $limit = 10): array {
        $where = ['1=1'];
        $params = [];
        if ($from) { $where[] = 'c.fecha_adjudicacion >= :from'; $params['from'] = $from; }
        if ($to)   { $where[] = 'c.fecha_adjudicacion <= :to';   $params['to']   = $to; }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT
                pr.id_proveedor,
                pr.nombre_empresa,
                pr.registro_fiscal,
                COUNT(c.id_contrato) AS total_contratos,
                SUM(c.monto_contrato) AS monto_total,
                AVG(c.monto_contrato) AS monto_promedio,
                MAX(c.fecha_adjudicacion) AS ultima_adjudicacion
            FROM contrato c
            JOIN proveedor pr ON pr.id_proveedor = c.id_proveedor
            {$whereSql}
            GROUP BY pr.id_proveedor, pr.nombre_empresa, pr.registro_fiscal
            ORDER BY monto_total DESC, total_contratos DESC
            LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Series mensuales: licitaciones publicadas y montos adjudicados por mes
     * dentro del rango. Devuelve 12 meses por defecto (último año).
     */
    public function montosMensuales(?string $from, ?string $to, ?int $idDependencia): array {
        // Default: últimos 12 meses si no hay rango
        if (!$from) $from = date('Y-m-01', strtotime('-11 months'));
        if (!$to)   $to   = date('Y-m-t');

        $params = [
            'from_cte' => $from,
            'to_cte' => $to,
            'from_lic' => $from,
            'to_lic' => $to,
            'from_adj' => $from,
            'to_adj' => $to,
        ];
        $depFilterLic = '';
        $depFilterAdj = '';
        if ($idDependencia !== null && $idDependencia > 0) {
            $depFilterLic = 'AND l.id_dependencia = :id_dep_lic';
            $depFilterAdj = 'AND l.id_dependencia = :id_dep_adj';
            $params['id_dep_lic'] = $idDependencia;
            $params['id_dep_adj'] = $idDependencia;
        }

        // Subconsulta de meses con CTE (MySQL 8)
        $sql = "
            WITH RECURSIVE meses (mes_inicio) AS (
                SELECT DATE_FORMAT(:from_cte, '%Y-%m-01')
                UNION ALL
                SELECT DATE_ADD(mes_inicio, INTERVAL 1 MONTH)
                FROM meses
                WHERE mes_inicio < DATE_FORMAT(:to_cte, '%Y-%m-01')
            )
            SELECT
                m.mes_inicio AS mes,
                COALESCE(lic_count.total, 0) AS licitaciones_creadas,
                COALESCE(adj.total, 0) AS contratos_adjudicados,
                COALESCE(adj.monto, 0) AS monto_adjudicado
            FROM meses m
            LEFT JOIN (
                SELECT DATE_FORMAT(l.fecha_creacion, '%Y-%m-01') AS mes,
                       COUNT(*) AS total
                FROM licitacion l
                WHERE l.fecha_creacion BETWEEN :from_lic AND DATE_ADD(:to_lic, INTERVAL 1 DAY)
                  {$depFilterLic}
                GROUP BY DATE_FORMAT(l.fecha_creacion, '%Y-%m-01')
            ) lic_count ON lic_count.mes = m.mes_inicio
            LEFT JOIN (
                SELECT DATE_FORMAT(c.fecha_adjudicacion, '%Y-%m-01') AS mes,
                       COUNT(*) AS total,
                       SUM(c.monto_contrato) AS monto
                FROM contrato c
                JOIN licitacion l ON l.id_licitacion = c.id_licitacion
                WHERE c.fecha_adjudicacion BETWEEN :from_adj AND :to_adj
                  {$depFilterAdj}
                GROUP BY DATE_FORMAT(c.fecha_adjudicacion, '%Y-%m-01')
            ) adj ON adj.mes = m.mes_inicio
            ORDER BY m.mes_inicio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cumplimiento de fechas programadas vs reales:
     * % de licitaciones cuyo fallo se emitió dentro de la fecha programada.
     */
    public function cumplimiento(?string $from, ?string $to, ?int $idDependencia): array {
        [$where, $params] = $this->buildWhere($from, $to, $idDependencia, 'l');

        // Licitaciones con fecha_fallo_adjudicacion programada y comparable a fecha_adjudicacion del contrato
        $sql = "
            SELECT
                COUNT(*) AS total_evaluables,
                SUM(CASE WHEN c.fecha_adjudicacion <= fp_fal.fecha_programada THEN 1 ELSE 0 END) AS a_tiempo,
                SUM(CASE WHEN c.fecha_adjudicacion > fp_fal.fecha_programada THEN 1 ELSE 0 END) AS con_atraso,
                AVG(DATEDIFF(c.fecha_adjudicacion, fp_fal.fecha_programada)) AS dias_desviacion_promedio
            FROM licitacion l
            JOIN contrato c ON c.id_licitacion = l.id_licitacion
            JOIN fecha_proceso fp_fal ON fp_fal.id_licitacion = l.id_licitacion
                AND fp_fal.tipo_fecha = 'FALLO_ADJUDICACION'
            WHERE c.fecha_adjudicacion IS NOT NULL
              AND fp_fal.fecha_programada IS NOT NULL
              {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Distribución por estado para el panel general
        $sqlDist = "
            SELECT estado_proceso, COUNT(*) AS total
            FROM licitacion l
            WHERE 1=1 {$where}
            GROUP BY estado_proceso";
        $stmtD = $this->db->prepare($sqlDist);
        $stmtD->execute($params);
        $distribucion = $stmtD->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ['resumen' => $row, 'distribucion_estado' => $distribucion];
    }

    /**
     * Lista de dependencias disponibles para usar como filtro en el frontend.
     */
    public function dependenciasParaFiltro(): array {
        $stmt = $this->db->query(
            'SELECT d.id_dependencia, d.nombre,
                    (SELECT COUNT(*) FROM licitacion l WHERE l.id_dependencia = d.id_dependencia) AS total_licitaciones
             FROM dependencia d
             ORDER BY d.nombre'
        );
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    private function buildWhere(?string $from, ?string $to, ?int $idDependencia, string $alias): array {
        $where = [];
        $params = [];
        if ($from) {
            $where[] = "AND {$alias}.fecha_creacion >= :from";
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to) {
            $where[] = "AND {$alias}.fecha_creacion <= :to";
            $params['to'] = $to . ' 23:59:59';
        }
        if ($idDependencia !== null && $idDependencia > 0) {
            $where[] = "AND {$alias}.id_dependencia = :id_dep";
            $params['id_dep'] = $idDependencia;
        }
        return [implode(' ', $where), $params];
    }
}
