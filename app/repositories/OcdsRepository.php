<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class OcdsRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Estados de licitación que se publican como datos abiertos.
     * BORRADOR no se incluye (proceso interno aún no publicado).
     */
    public const ESTADOS_PUBLICOS = [
        'PUBLICADA', 'EN_ACLARACIONES', 'RECEPCION_PROPUESTAS',
        'EN_EVALUACION', 'ADJUDICADA', 'DESIERTA', 'CANCELADA',
    ];

    /**
     * Listado paginado de licitaciones con datos completos para construir releases.
     *
     * @param array{from?: string, to?: string, estado?: string} $filters
     */
    public function findReleasesData(array $filters, int $page, int $limit): array {
        $where = ['l.estado_proceso IN (' . self::placeholders('e', count(self::ESTADOS_PUBLICOS)) . ')'];
        $params = [];
        foreach (self::ESTADOS_PUBLICOS as $i => $s) {
            $params['e' . $i] = $s;
        }

        if (!empty($filters['from'])) {
            $where[] = 'l.fecha_actualizacion >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'l.fecha_actualizacion <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['estado']) && in_array($filters['estado'], self::ESTADOS_PUBLICOS, true)) {
            // Reemplazar el filtro genérico por uno único
            $where = ['l.estado_proceso = :estado_unico'];
            $params = ['estado_unico' => $filters['estado']];
            if (!empty($filters['from'])) {
                $where[] = 'l.fecha_actualizacion >= :from';
                $params['from'] = $filters['from'] . ' 00:00:00';
            }
            if (!empty($filters['to'])) {
                $where[] = 'l.fecha_actualizacion <= :to';
                $params['to'] = $filters['to'] . ' 23:59:59';
            }
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'l.tipo_procedimiento = :tipo';
            $params['tipo'] = $filters['tipo'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $offset = max(0, ($page - 1) * $limit);

        $sqlData = "
            SELECT
                l.id_licitacion, l.numero_licitacion, l.tipo_procedimiento,
                l.descripcion_proyecto, l.presupuesto_estimado, l.ubicacion_proyecto,
                l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion,
                l.id_dependencia,
                d.nombre AS dependencia_nombre,
                fp_pub.fecha_programada  AS fp_publicacion,
                fp_jun.fecha_programada  AS fp_junta,
                fp_rec.fecha_programada  AS fp_recepcion,
                fp_ape.fecha_programada  AS fp_apertura,
                fp_fal.fecha_programada  AS fp_fallo
            FROM licitacion l
            JOIN dependencia d ON d.id_dependencia = l.id_dependencia
            LEFT JOIN fecha_proceso fp_pub ON fp_pub.id_licitacion = l.id_licitacion AND fp_pub.tipo_fecha = 'PUBLICACION_CONVOCATORIA'
            LEFT JOIN fecha_proceso fp_jun ON fp_jun.id_licitacion = l.id_licitacion AND fp_jun.tipo_fecha = 'JUNTA_ACLARACIONES'
            LEFT JOIN fecha_proceso fp_rec ON fp_rec.id_licitacion = l.id_licitacion AND fp_rec.tipo_fecha = 'RECEPCION_PROPUESTAS'
            LEFT JOIN fecha_proceso fp_ape ON fp_ape.id_licitacion = l.id_licitacion AND fp_ape.tipo_fecha = 'APERTURA_PROPUESTAS'
            LEFT JOIN fecha_proceso fp_fal ON fp_fal.id_licitacion = l.id_licitacion AND fp_fal.tipo_fecha = 'FALLO_ADJUDICACION'
            {$whereSql}
            ORDER BY l.fecha_actualizacion DESC, l.id_licitacion DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sqlData);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $licitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlCount = "SELECT COUNT(*) FROM licitacion l {$whereSql}";
        $stmtC = $this->db->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtC->bindValue(':' . $k, $v);
        }
        $stmtC->execute();
        $total = (int) $stmtC->fetchColumn();

        return ['licitaciones' => $licitaciones, 'total' => $total];
    }

    /**
     * Datos completos para una sola licitación por número.
     */
    public function findByNumeroLicitacion(string $numero): ?array {
        $sql = "
            SELECT
                l.id_licitacion, l.numero_licitacion, l.tipo_procedimiento,
                l.descripcion_proyecto, l.presupuesto_estimado, l.ubicacion_proyecto,
                l.estado_proceso, l.fecha_creacion, l.fecha_actualizacion,
                l.id_dependencia,
                d.nombre AS dependencia_nombre,
                fp_pub.fecha_programada AS fp_publicacion,
                fp_jun.fecha_programada AS fp_junta,
                fp_rec.fecha_programada AS fp_recepcion,
                fp_ape.fecha_programada AS fp_apertura,
                fp_fal.fecha_programada AS fp_fallo
            FROM licitacion l
            JOIN dependencia d ON d.id_dependencia = l.id_dependencia
            LEFT JOIN fecha_proceso fp_pub ON fp_pub.id_licitacion = l.id_licitacion AND fp_pub.tipo_fecha = 'PUBLICACION_CONVOCATORIA'
            LEFT JOIN fecha_proceso fp_jun ON fp_jun.id_licitacion = l.id_licitacion AND fp_jun.tipo_fecha = 'JUNTA_ACLARACIONES'
            LEFT JOIN fecha_proceso fp_rec ON fp_rec.id_licitacion = l.id_licitacion AND fp_rec.tipo_fecha = 'RECEPCION_PROPUESTAS'
            LEFT JOIN fecha_proceso fp_ape ON fp_ape.id_licitacion = l.id_licitacion AND fp_ape.tipo_fecha = 'APERTURA_PROPUESTAS'
            LEFT JOIN fecha_proceso fp_fal ON fp_fal.id_licitacion = l.id_licitacion AND fp_fal.tipo_fecha = 'FALLO_ADJUDICACION'
            WHERE l.numero_licitacion = :numero
              AND l.estado_proceso != 'BORRADOR'
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['numero' => $numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Devuelve participantes (proveedores) y contrato si existe.
     */
    public function findRelatedActors(int $idLicitacion): array {
        $sqlParticipantes = "
            SELECT
                pr.id_proveedor, pr.nombre_empresa, pr.representante_legal,
                pr.registro_fiscal, pr.domicilio, pr.telefono, pr.contacto_email,
                p.estatus AS participacion_estatus
            FROM participacion p
            JOIN proveedor pr ON pr.id_proveedor = p.id_proveedor
            WHERE p.id_licitacion = :id
            ORDER BY pr.nombre_empresa";
        $stmt = $this->db->prepare($sqlParticipantes);
        $stmt->execute(['id' => $idLicitacion]);
        $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlContrato = "
            SELECT
                c.id_contrato, c.numero_contrato, c.monto_contrato,
                c.fecha_adjudicacion, c.fecha_inicio, c.fecha_fin, c.estatus,
                c.fecha_firma_proveedor, c.fecha_creacion,
                c.id_proveedor,
                pr.nombre_empresa AS proveedor_nombre,
                pr.registro_fiscal AS proveedor_rfc,
                pr.representante_legal AS proveedor_representante,
                pr.domicilio AS proveedor_domicilio,
                pr.telefono AS proveedor_telefono,
                pr.contacto_email AS proveedor_email
            FROM contrato c
            JOIN proveedor pr ON pr.id_proveedor = c.id_proveedor
            WHERE c.id_licitacion = :id
            LIMIT 1";
        $stmt = $this->db->prepare($sqlContrato);
        $stmt->execute(['id' => $idLicitacion]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'participantes' => $participantes,
            'contrato' => $contrato ?: null,
        ];
    }

    private static function placeholders(string $prefix, int $count): string {
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = ':' . $prefix . $i;
        }
        return implode(',', $items);
    }
}
