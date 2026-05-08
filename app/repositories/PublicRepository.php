<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class PublicRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findConvocatorias(
        int $page,
        int $limit,
        string $sortField,
        string $sortOrder,
        ?string $search = null,
        ?string $estado = null,
        ?string $tipo = null,
        ?int $dependencia = null,
        ?int $year = null
    ): array {
        $offset = ($page - 1) * $limit;
        $allowedFields = ['fecha_creacion', 'numero_licitacion', 'tipo_procedimiento', 'presupuesto_estimado'];
        $allowedOrders = ['ASC', 'DESC'];
        if (!in_array($sortField, $allowedFields, true)) {
            $sortField = 'fecha_creacion';
        }
        $sortOrder = in_array(strtoupper($sortOrder), $allowedOrders, true) ? strtoupper($sortOrder) : 'DESC';

        $where = ["l.estado_proceso NOT IN ('BORRADOR','CANCELADA')"];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(l.numero_licitacion LIKE :search OR l.descripcion_proyecto LIKE :search OR d.nombre LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }
        if ($estado !== null && $estado !== '') {
            $where[] = 'l.estado_proceso = :estado';
            $params['estado'] = $estado;
        }
        if ($tipo !== null && $tipo !== '') {
            $where[] = 'l.tipo_procedimiento = :tipo';
            $params['tipo'] = $tipo;
        }
        if ($dependencia !== null && $dependencia > 0) {
            $where[] = 'l.id_dependencia = :dependencia';
            $params['dependencia'] = $dependencia;
        }
        if ($year !== null && $year > 2000) {
            $where[] = 'YEAR(l.fecha_creacion) = :year';
            $params['year'] = $year;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'l.tipo_procedimiento, l.descripcion_proyecto, l.presupuesto_estimado, '
             . 'l.ubicacion_proyecto, l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion, '
             . 'fp_recepcion.fecha_programada AS fecha_cierre_recepcion, '
             . 'fp_fallo.fecha_programada AS fecha_fallo_adjudicacion '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . "LEFT JOIN fecha_proceso fp_recepcion ON fp_recepcion.id_licitacion = l.id_licitacion AND fp_recepcion.tipo_fecha = 'RECEPCION_PROPUESTAS' "
             . "LEFT JOIN fecha_proceso fp_fallo ON fp_fallo.id_licitacion = l.id_licitacion AND fp_fallo.tipo_fecha = 'FALLO_ADJUDICACION' "
             . $whereSql
             . " ORDER BY l.{$sortField} {$sortOrder} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM licitacion l JOIN dependencia d ON l.id_dependencia = d.id_dependencia ' . $whereSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findConvocatoriaById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
            . 'l.tipo_procedimiento, l.descripcion_proyecto, l.presupuesto_estimado, '
            . 'l.ubicacion_proyecto, l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion, '
            . 'fp_recepcion.fecha_programada AS fecha_cierre_recepcion, '
            . 'fp_fallo.fecha_programada AS fecha_fallo_adjudicacion '
            . 'FROM licitacion l '
            . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
            . "LEFT JOIN fecha_proceso fp_recepcion ON fp_recepcion.id_licitacion = l.id_licitacion AND fp_recepcion.tipo_fecha = 'RECEPCION_PROPUESTAS' "
            . "LEFT JOIN fecha_proceso fp_fallo ON fp_fallo.id_licitacion = l.id_licitacion AND fp_fallo.tipo_fecha = 'FALLO_ADJUDICACION' "
            . 'WHERE l.id_licitacion = :id AND l.estado_proceso NOT IN (\'BORRADOR\',\'CANCELADA\') LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findResultados(int $page, int $limit, ?string $search = null): array {
        $offset = ($page - 1) * $limit;

        $where = ["l.estado_proceso = 'ADJUDICADA'"];
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $where[] = '(l.numero_licitacion LIKE :search OR l.descripcion_proyecto LIKE :search OR p.nombre_empresa LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'l.descripcion_proyecto, l.presupuesto_estimado, l.estado_proceso, '
             . 'c.numero_contrato, c.monto_contrato, c.fecha_adjudicacion, '
             . 'p.nombre_empresa AS adjudicatario_nombre_empresa '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . 'LEFT JOIN contrato c ON l.id_licitacion = c.id_licitacion '
             . 'LEFT JOIN proveedor p ON c.id_proveedor = p.id_proveedor '
             . $whereSql
             . ' ORDER BY c.fecha_adjudicacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM licitacion l LEFT JOIN contrato c ON l.id_licitacion = c.id_licitacion LEFT JOIN proveedor p ON c.id_proveedor = p.id_proveedor ' . $whereSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findContratosPublicos(int $page, int $limit, ?string $estatus = null, ?int $year = null): array {
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        if ($estatus !== null && $estatus !== '') {
            $where[] = 'c.estatus = :estatus';
            $params['estatus'] = $estatus;
        }
        if ($year !== null && $year > 2000) {
            $where[] = 'YEAR(c.fecha_adjudicacion) = :year';
            $params['year'] = $year;
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT c.id_contrato, c.numero_contrato, l.numero_licitacion, d.nombre AS dependencia_nombre, '
             . 'c.monto_contrato, c.fecha_adjudicacion, c.fecha_inicio, c.fecha_fin, c.estatus, '
             . 'p.nombre_empresa AS adjudicatario_nombre_empresa '
             . 'FROM contrato c '
             . 'JOIN licitacion l ON c.id_licitacion = l.id_licitacion '
             . 'JOIN dependencia d ON l.id_dependencia = d.id_dependencia '
             . 'JOIN proveedor p ON c.id_proveedor = p.id_proveedor '
             . $whereSql
             . ' ORDER BY c.fecha_adjudicacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM contrato c ' . $whereSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findEvaluacionesPublicas(int $page, int $limit): array {
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, l.descripcion_proyecto, l.presupuesto_estimado, '
             . 'l.ubicacion_proyecto, l.estado_proceso, d.nombre AS dependencia_nombre, '
             . 'COUNT(DISTINCT pa.id_participacion) AS propuestas_recibidas, '
             . 'fp_recepcion.fecha_programada AS fecha_cierre_recepcion, '
             . 'fp_fallo.fecha_programada AS fecha_fallo_adjudicacion '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON d.id_dependencia = l.id_dependencia '
             . 'LEFT JOIN participacion pa ON pa.id_licitacion = l.id_licitacion '
             . "LEFT JOIN fecha_proceso fp_recepcion ON fp_recepcion.id_licitacion = l.id_licitacion AND fp_recepcion.tipo_fecha = 'RECEPCION_PROPUESTAS' "
             . "LEFT JOIN fecha_proceso fp_fallo ON fp_fallo.id_licitacion = l.id_licitacion AND fp_fallo.tipo_fecha = 'FALLO_ADJUDICACION' "
             . "WHERE l.estado_proceso IN ('RECEPCION_PROPUESTAS','EN_EVALUACION') "
             . 'GROUP BY l.id_licitacion, l.numero_licitacion, l.descripcion_proyecto, l.presupuesto_estimado, '
             . 'l.ubicacion_proyecto, l.estado_proceso, d.nombre, fp_recepcion.fecha_programada, fp_fallo.fecha_programada '
             . 'ORDER BY l.fecha_actualizacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->query("SELECT COUNT(*) FROM licitacion WHERE estado_proceso IN ('RECEPCION_PROPUESTAS','EN_EVALUACION')");
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findHistorialPublico(int $page, int $limit, ?int $year = null, ?string $tipo = null, ?string $search = null): array {
        $offset = ($page - 1) * $limit;

        $where = ["l.estado_proceso IN ('ADJUDICADA','DESIERTA','CANCELADA')"];
        $params = [];

        if ($year !== null && $year > 2000) {
            $where[] = 'YEAR(l.fecha_actualizacion) = :year';
            $params['year'] = $year;
        }
        if ($tipo !== null && $tipo !== '') {
            $where[] = 'l.tipo_procedimiento = :tipo';
            $params['tipo'] = $tipo;
        }
        if ($search !== null && trim($search) !== '') {
            $where[] = '(l.numero_licitacion LIKE :search OR l.descripcion_proyecto LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT l.id_licitacion, l.numero_licitacion, l.descripcion_proyecto, l.presupuesto_estimado, '
             . 'l.tipo_procedimiento, l.estado_proceso, l.fecha_actualizacion, d.nombre AS dependencia_nombre, '
             . 'c.fecha_adjudicacion, c.monto_contrato '
             . 'FROM licitacion l '
             . 'JOIN dependencia d ON d.id_dependencia = l.id_dependencia '
             . 'LEFT JOIN contrato c ON c.id_licitacion = l.id_licitacion '
             . $whereSql
             . ' ORDER BY l.fecha_actualizacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM licitacion l ' . $whereSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function getEstadisticasPublicas(): array {
        $totales = $this->db->query(
            "SELECT "
            . "SUM(CASE WHEN estado_proceso IN ('PUBLICADA','EN_ACLARACIONES','RECEPCION_PROPUESTAS','EN_EVALUACION') THEN 1 ELSE 0 END) AS licitaciones_activas, "
            . "SUM(CASE WHEN estado_proceso = 'ADJUDICADA' THEN 1 ELSE 0 END) AS licitaciones_adjudicadas, "
            . "SUM(CASE WHEN estado_proceso = 'EN_EVALUACION' THEN 1 ELSE 0 END) AS licitaciones_en_evaluacion "
            . 'FROM licitacion'
        )->fetch(PDO::FETCH_ASSOC);

        $proveedores = $this->db->query(
            "SELECT "
            . 'COUNT(*) AS total, '
            . "SUM(CASE WHEN estatus IN ('PENDIENTE','VALIDADO') THEN 1 ELSE 0 END) AS activos "
            . 'FROM proveedor'
        )->fetch(PDO::FETCH_ASSOC);
        $contratos = $this->db->query('SELECT COUNT(*) AS total, COALESCE(SUM(monto_contrato),0) AS monto_total FROM contrato')->fetch(PDO::FETCH_ASSOC);

        $proveedoresTotal = (int) ($proveedores['total'] ?? 0);
        $proveedoresActivos = (int) ($proveedores['activos'] ?? 0);

        return [
            'licitaciones_activas' => (int) ($totales['licitaciones_activas'] ?? 0),
            'licitaciones_adjudicadas' => (int) ($totales['licitaciones_adjudicadas'] ?? 0),
            'licitaciones_en_evaluacion' => (int) ($totales['licitaciones_en_evaluacion'] ?? 0),
            // Compatibilidad hacia atrás: se conserva la clave histórica.
            'proveedores_registrados' => $proveedoresTotal,
            'proveedores_registrados_total' => $proveedoresTotal,
            'proveedores_activos' => $proveedoresActivos,
            'contratos_adjudicados' => (int) ($contratos['total'] ?? 0),
            'monto_total_contratado' => (float) ($contratos['monto_total'] ?? 0),
        ];
    }

    public function findDocumentosPublicosByLicitacion(int $idLicitacion): array {
        $stmt = $this->db->prepare(
            'SELECT d.id_documento, d.nombre_archivo, d.tipo_documento, d.tamano_bytes, d.fecha_subida '
            . 'FROM documento d '
            . 'JOIN licitacion l ON l.id_licitacion = d.id_licitacion '
            . "WHERE d.id_licitacion = :id_licitacion "
            . "AND d.tipo_documento IN ('BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACLARACION') "
            . "AND l.estado_proceso NOT IN ('BORRADOR','CANCELADA') "
            . 'ORDER BY d.fecha_subida DESC'
        );
        $stmt->execute(['id_licitacion' => $idLicitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDocumentoPublicoById(int $idDocumento): ?array {
        $stmt = $this->db->prepare(
            'SELECT d.* '
            . 'FROM documento d '
            . 'JOIN licitacion l ON l.id_licitacion = d.id_licitacion '
            . 'WHERE d.id_documento = :id_documento '
            . "AND d.tipo_documento IN ('BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACLARACION') "
            . "AND l.estado_proceso NOT IN ('BORRADOR','CANCELADA') "
            . 'LIMIT 1'
        );
        $stmt->execute(['id_documento' => $idDocumento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
