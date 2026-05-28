<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PlantillaRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class PlantillaService {
    private PlantillaRepository $repo;

    private const TIPOS_VALIDOS = [
        'ACTA_APERTURA', 'ACTA_ACLARACIONES', 'ACTA_FALLO',
        'DICTAMEN', 'CONTRATO', 'RESUMEN_LICITACION', 'PERSONALIZADA',
    ];

    private const TIPOS_ASSET_VALIDOS = ['LOGO', 'FIRMA', 'SELLO', 'OTRO'];

    public function __construct() {
        $this->repo = new PlantillaRepository();
    }

    public function list(array $filters): array {
        $sanitized = [];
        if (!empty($filters['tipo']) && in_array($filters['tipo'], self::TIPOS_VALIDOS, true)) {
            $sanitized['tipo'] = $filters['tipo'];
        }
        if (isset($filters['activa']) && $filters['activa'] !== '') {
            $sanitized['activa'] = (int) (bool) $filters['activa'];
        }
        if (!empty($filters['solo_predefinidas'])) {
            $sanitized['soloPredefinidas'] = true;
        }

        $rows = $this->repo->findAll($sanitized);
        return array_map([$this, 'transformRow'], $rows);
    }

    public function get(int $id, bool $withContent = false): ?array {
        $row = $this->repo->findById($id, $withContent);
        if (!$row) return null;
        $transformed = $this->transformRow($row);
        if ($withContent) {
            $transformed['contenido_html'] = $row['contenido_html'] ?? '';
            $transformed['contenido_json'] = $row['contenido_json'] ?? null;
        }
        $transformed['assets'] = $this->repo->findAssets($id);
        return $transformed;
    }

    public function create(array $input, int $idUsuario): array {
        $errors = $this->validate($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $id = $this->repo->create([
            'nombre' => trim((string) $input['nombre']),
            'descripcion' => $this->safeString($input['descripcion'] ?? null, 500),
            'tipo' => $input['tipo'],
            'contenido_html' => (string) $input['contenido_html'],
            'contenido_json' => isset($input['contenido_json']) && $input['contenido_json'] !== ''
                ? (is_string($input['contenido_json']) ? $input['contenido_json'] : json_encode($input['contenido_json']))
                : null,
            'variables_esperadas' => $this->safeString($input['variables_esperadas'] ?? null, 5000),
            'id_usuario_creador' => $idUsuario,
            'activa' => isset($input['activa']) ? (int) (bool) $input['activa'] : 1,
            'es_predefinida' => 0, // Las predefinidas sólo entran por seed
        ]);

        auditLog($idUsuario, 'plantilla_reporte', $id, 'CREAR', null, [
            'nombre' => $input['nombre'],
            'tipo' => $input['tipo'],
        ]);

        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input, int $idUsuario): array {
        $existing = $this->repo->findById($id, false);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Plantilla no encontrada.'], 'status' => 404];
        }
        if ((int) $existing['es_predefinida'] === 1) {
            return ['ok' => false, 'errors' => ['Las plantillas predefinidas no se pueden modificar.'], 'status' => 409];
        }

        $errors = $this->validate($input, false);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors, 'status' => 422];
        }

        $update = [];
        if (isset($input['nombre'])) $update['nombre'] = trim((string) $input['nombre']);
        if (array_key_exists('descripcion', $input)) $update['descripcion'] = $this->safeString($input['descripcion'], 500);
        if (isset($input['tipo'])) $update['tipo'] = $input['tipo'];
        if (isset($input['contenido_html'])) $update['contenido_html'] = (string) $input['contenido_html'];
        if (array_key_exists('contenido_json', $input)) {
            $update['contenido_json'] = $input['contenido_json'] === '' || $input['contenido_json'] === null
                ? null
                : (is_string($input['contenido_json']) ? $input['contenido_json'] : json_encode($input['contenido_json']));
        }
        if (array_key_exists('variables_esperadas', $input)) {
            $update['variables_esperadas'] = $this->safeString($input['variables_esperadas'], 5000);
        }
        if (isset($input['activa'])) $update['activa'] = (int) (bool) $input['activa'];

        if (!empty($update)) {
            $this->repo->update($id, $update);
            auditLog($idUsuario, 'plantilla_reporte', $id, 'ACTUALIZAR', $existing, $update);
        }

        return ['ok' => true];
    }

    public function delete(int $id, int $idUsuario): array {
        $existing = $this->repo->findById($id, false);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Plantilla no encontrada.'], 'status' => 404];
        }
        if ((int) $existing['es_predefinida'] === 1) {
            return ['ok' => false, 'errors' => ['Las plantillas predefinidas no se pueden eliminar.'], 'status' => 409];
        }
        $this->repo->delete($id);
        auditLog($idUsuario, 'plantilla_reporte', $id, 'ELIMINAR', $existing, null);
        return ['ok' => true];
    }

    /**
     * Subir un asset (logo, firma, sello) y registrarlo en la plantilla.
     *
     * @param array $file  Estructura $_FILES['archivo']
     * @return array{ok: bool, id?: int, ruta?: string, errors?: array, status?: int}
     */
    public function uploadAsset(int $idPlantilla, string $tipo, string $nombre, array $file, int $idUsuario): array {
        if (!in_array($tipo, self::TIPOS_ASSET_VALIDOS, true)) {
            return ['ok' => false, 'errors' => ['Tipo de asset inválido.'], 'status' => 422];
        }
        $plantilla = $this->repo->findById($idPlantilla, false);
        if (!$plantilla) {
            return ['ok' => false, 'errors' => ['Plantilla no encontrada.'], 'status' => 404];
        }

        if (empty($file) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'errors' => ['Archivo no recibido o inválido.'], 'status' => 400];
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) { // 5 MB max
            return ['ok' => false, 'errors' => ['El archivo debe pesar entre 1 byte y 5 MB.'], 'status' => 422];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = function_exists('mime_content_type') ? @mime_content_type($tmp) : null;
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/svg+xml' => 'svg'];
        if (!$mime || !isset($allowed[$mime])) {
            return ['ok' => false, 'errors' => ['Sólo se aceptan imágenes PNG, JPG o SVG.'], 'status' => 422];
        }
        $ext = $allowed[$mime];

        // storage/templates/{id_plantilla}/{tipo}-{uniqid}.ext
        $relDir = 'templates/' . $idPlantilla;
        $storageRoot = realpath(__DIR__ . '/../../storage') ?: (__DIR__ . '/../../storage');
        $absDir = rtrim($storageRoot, '/') . '/' . $relDir;
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            return ['ok' => false, 'errors' => ['No se pudo crear el directorio de almacenamiento.'], 'status' => 500];
        }
        $filename = strtolower($tipo) . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $absPath = $absDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $absPath) && !rename($tmp, $absPath)) {
            return ['ok' => false, 'errors' => ['No se pudo guardar el archivo.'], 'status' => 500];
        }

        $rutaRel = $relDir . '/' . $filename;
        $idAsset = $this->repo->createAsset([
            'id_plantilla' => $idPlantilla,
            'tipo' => $tipo,
            'nombre' => $nombre !== '' ? $nombre : $filename,
            'ruta_relativa' => $rutaRel,
        ]);

        auditLog($idUsuario, 'plantilla_asset', $idAsset, 'CREAR', null, [
            'id_plantilla' => $idPlantilla,
            'tipo' => $tipo,
            'ruta_relativa' => $rutaRel,
        ]);

        return ['ok' => true, 'id' => $idAsset, 'ruta' => $rutaRel];
    }

    public function deleteAsset(int $idAsset, int $idUsuario): array {
        $asset = $this->repo->findAssetById($idAsset);
        if (!$asset) {
            return ['ok' => false, 'errors' => ['Asset no encontrado.'], 'status' => 404];
        }
        // Borrar archivo físico (best effort)
        $storageRoot = realpath(__DIR__ . '/../../storage') ?: (__DIR__ . '/../../storage');
        $abs = rtrim($storageRoot, '/') . '/' . $asset['ruta_relativa'];
        if (is_file($abs)) {
            @unlink($abs);
        }
        $this->repo->deleteAsset($idAsset);
        auditLog($idUsuario, 'plantilla_asset', $idAsset, 'ELIMINAR', $asset, null);
        return ['ok' => true];
    }

    // ----- internos -----

    private function validate(array $input, bool $strictRequired = true): array {
        $errors = [];
        $requiredFields = ['nombre', 'tipo', 'contenido_html'];
        foreach ($requiredFields as $f) {
            if ($strictRequired && (empty($input[$f]) || (is_string($input[$f]) && trim($input[$f]) === ''))) {
                $errors[] = "El campo '{$f}' es obligatorio.";
            }
        }
        if (isset($input['nombre']) && mb_strlen((string) $input['nombre']) > 150) {
            $errors[] = "El nombre no puede exceder 150 caracteres.";
        }
        if (isset($input['tipo']) && !in_array($input['tipo'], self::TIPOS_VALIDOS, true)) {
            $errors[] = "Tipo inválido. Permitidos: " . implode(', ', self::TIPOS_VALIDOS);
        }
        if (isset($input['contenido_html']) && mb_strlen((string) $input['contenido_html']) > 1000000) {
            $errors[] = "El contenido HTML excede el tamaño máximo (1 MB).";
        }
        return $errors;
    }

    private function safeString(mixed $value, int $maxLen): ?string {
        if ($value === null || $value === '') return null;
        $s = (string) $value;
        if (mb_strlen($s) > $maxLen) {
            $s = mb_substr($s, 0, $maxLen);
        }
        return $s;
    }

    private function transformRow(array $row): array {
        return [
            'id_plantilla' => (int) $row['id_plantilla'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'tipo' => $row['tipo'],
            'variables_esperadas' => $row['variables_esperadas'],
            'id_usuario_creador' => (int) $row['id_usuario_creador'],
            'activa' => (int) $row['activa'] === 1,
            'es_predefinida' => (int) $row['es_predefinida'] === 1,
            'fecha_creacion' => $row['fecha_creacion'],
            'fecha_actualizacion' => $row['fecha_actualizacion'],
        ];
    }
}
