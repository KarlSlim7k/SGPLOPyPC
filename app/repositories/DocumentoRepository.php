<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class DocumentoRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT d.*, u.nombre AS subido_por_nombre FROM documento d '
            . 'JOIN usuario u ON d.subido_por = u.id_usuario WHERE d.id_documento = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO documento (nombre_archivo, ruta_almacenamiento, tipo_documento, id_licitacion, id_propuesta, id_proveedor, id_contrato, id_evaluacion, version, subido_por, fecha_subida, tamano_bytes) '
            . 'VALUES (:nombre_archivo, :ruta_almacenamiento, :tipo_documento, :id_licitacion, :id_propuesta, :id_proveedor, :id_contrato, :id_evaluacion, :version, :subido_por, NOW(), :tamano_bytes)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function findByProveedorForPortal(
        int $idProveedor,
        int $page,
        int $limit,
        ?string $context = null,
        ?int $idPropuesta = null,
        ?string $tipoDocumento = null
    ): array {
        $offset = ($page - 1) * $limit;
        $where = ['(d.id_proveedor = :id_proveedor_documento OR pa.id_proveedor = :id_proveedor_propuesta)'];
        $params = [
            'id_proveedor_documento' => $idProveedor,
            'id_proveedor_propuesta' => $idProveedor,
        ];

        if ($context === 'proveedor') {
            $where[] = 'd.id_proveedor = :id_proveedor_context';
            $params['id_proveedor_context'] = $idProveedor;
        } elseif ($context === 'propuesta') {
            $where[] = 'd.id_propuesta IS NOT NULL';
        }

        if ($idPropuesta !== null && $idPropuesta > 0) {
            $where[] = 'd.id_propuesta = :id_propuesta';
            $params['id_propuesta'] = $idPropuesta;
        }

        if ($tipoDocumento !== null && trim($tipoDocumento) !== '') {
            $where[] = 'd.tipo_documento = :tipo_documento';
            $params['tipo_documento'] = trim($tipoDocumento);
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $sql = 'SELECT d.id_documento, d.nombre_archivo, d.tipo_documento, d.id_propuesta, d.id_proveedor, '
             . 'd.version, d.fecha_subida, d.tamano_bytes, u.nombre AS subido_por_nombre, '
             . 'pr.id_participacion, li.numero_licitacion '
             . 'FROM documento d '
             . 'JOIN usuario u ON d.subido_por = u.id_usuario '
             . 'LEFT JOIN propuesta pr ON d.id_propuesta = pr.id_propuesta '
             . 'LEFT JOIN participacion pa ON pr.id_participacion = pa.id_participacion '
             . 'LEFT JOIN licitacion li ON pa.id_licitacion = li.id_licitacion '
             . $whereSql
             . ' ORDER BY d.fecha_subida DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = 'SELECT COUNT(*) FROM documento d '
                  . 'LEFT JOIN propuesta pr ON d.id_propuesta = pr.id_propuesta '
                  . 'LEFT JOIN participacion pa ON pr.id_participacion = pa.id_participacion '
                  . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }
}
