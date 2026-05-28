<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class PlantillaRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Listar plantillas con filtros opcionales.
     * @param array{tipo?: string, activa?: int, soloPredefinidas?: bool} $filters
     */
    public function findAll(array $filters = []): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params['tipo'] = $filters['tipo'];
        }
        if (isset($filters['activa'])) {
            $where[] = 'activa = :activa';
            $params['activa'] = (int) $filters['activa'];
        }
        if (!empty($filters['soloPredefinidas'])) {
            $where[] = 'es_predefinida = 1';
        }

        $sql = 'SELECT id_plantilla, nombre, descripcion, tipo, variables_esperadas,
                       id_usuario_creador, activa, es_predefinida,
                       fecha_creacion, fecha_actualizacion
                FROM plantilla_reporte
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY es_predefinida DESC, nombre ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id, bool $withContent = false): ?array {
        $cols = $withContent
            ? 'id_plantilla, nombre, descripcion, tipo, contenido_html, contenido_json,
               variables_esperadas, id_usuario_creador, activa, es_predefinida,
               fecha_creacion, fecha_actualizacion'
            : 'id_plantilla, nombre, descripcion, tipo, variables_esperadas,
               id_usuario_creador, activa, es_predefinida,
               fecha_creacion, fecha_actualizacion';

        $stmt = $this->db->prepare("SELECT {$cols} FROM plantilla_reporte WHERE id_plantilla = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO plantilla_reporte
                (nombre, descripcion, tipo, contenido_html, contenido_json,
                 variables_esperadas, id_usuario_creador, activa, es_predefinida)
             VALUES
                (:nombre, :descripcion, :tipo, :contenido_html, :contenido_json,
                 :variables_esperadas, :id_usuario_creador, :activa, :es_predefinida)'
        );
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'tipo' => $data['tipo'],
            'contenido_html' => $data['contenido_html'],
            'contenido_json' => $data['contenido_json'] ?? null,
            'variables_esperadas' => $data['variables_esperadas'] ?? null,
            'id_usuario_creador' => $data['id_usuario_creador'],
            'activa' => $data['activa'] ?? 1,
            'es_predefinida' => $data['es_predefinida'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        foreach (['nombre', 'descripcion', 'tipo', 'contenido_html', 'contenido_json',
                  'variables_esperadas', 'activa'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = :{$col}";
                $params[$col] = $data[$col];
            }
        }
        if (empty($fields)) return;
        $sql = 'UPDATE plantilla_reporte SET ' . implode(', ', $fields) . ' WHERE id_plantilla = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM plantilla_reporte WHERE id_plantilla = :id');
        $stmt->execute(['id' => $id]);
    }

    // ----- assets -----

    public function findAssets(int $idPlantilla): array {
        $stmt = $this->db->prepare(
            'SELECT id_asset, id_plantilla, tipo, nombre, ruta_relativa,
                    pos_x, pos_y, ancho_mm, alto_mm, fecha_creacion
             FROM plantilla_asset
             WHERE id_plantilla = :id
             ORDER BY tipo, id_asset'
        );
        $stmt->execute(['id' => $idPlantilla]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAssetById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM plantilla_asset WHERE id_asset = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createAsset(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO plantilla_asset
                (id_plantilla, tipo, nombre, ruta_relativa, pos_x, pos_y, ancho_mm, alto_mm)
             VALUES
                (:id_plantilla, :tipo, :nombre, :ruta_relativa, :pos_x, :pos_y, :ancho_mm, :alto_mm)'
        );
        $stmt->execute([
            'id_plantilla' => $data['id_plantilla'],
            'tipo' => $data['tipo'],
            'nombre' => $data['nombre'],
            'ruta_relativa' => $data['ruta_relativa'],
            'pos_x' => $data['pos_x'] ?? null,
            'pos_y' => $data['pos_y'] ?? null,
            'ancho_mm' => $data['ancho_mm'] ?? null,
            'alto_mm' => $data['alto_mm'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteAsset(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM plantilla_asset WHERE id_asset = :id');
        $stmt->execute(['id' => $id]);
    }
}
