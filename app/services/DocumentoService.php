<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/DocumentoRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class DocumentoService {
    private DocumentoRepository $repo;

    public function __construct() {
        $this->repo = new DocumentoRepository();
    }

    public function upload(array $file, array $input, int $idUsuario): array {
        $errors = [];
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No se recibió un archivo válido.';
        }
        $tipoDoc = $input['tipo_documento'] ?? '';
        $tiposValidos = ['BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACTA_PROCESO','PROPUESTA_TECNICA','PROPUESTA_ECONOMICA','DOC_COMPLEMENTARIA','DOC_LEGAL_PROVEEDOR','DOC_CONTRATO','ACLARACION','DICTAMEN'];
        if (!in_array($tipoDoc, $tiposValidos, true)) {
            $errors[] = 'Tipo de documento no válido.';
        }
        $contextoCount = 0;
        $contextos = ['id_licitacion','id_propuesta','id_proveedor','id_contrato','id_evaluacion'];
        foreach ($contextos as $c) {
            if (!empty($input[$c])) $contextoCount++;
        }
        if ($contextoCount === 0) {
            $errors[] = 'El documento debe estar asociado al menos a un contexto (licitación, propuesta, proveedor, contrato o evaluación).';
        }
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $allowedMime = [
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowedMime[$mime])) {
            return ['ok' => false, 'errors' => ['Tipo MIME no permitido: ' . $mime]];
        }
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return ['ok' => false, 'errors' => ['El archivo excede el tamaño máximo permitido de 10MB.']];
        }

        $ext = $allowedMime[$mime];
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $timestamp = date('Ymd_His');
        $fileName = "{$safeName}_{$timestamp}.{$ext}";
        $year = date('Y');
        $month = strtolower(date('F'));
        $uploadDir = __DIR__ . "/../../storage/documents/uploads/{$year}/{$month}";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $destPath = $uploadDir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'errors' => ['Error al guardar el archivo.']];
        }

        $relativePath = "storage/documents/uploads/{$year}/{$month}/{$fileName}";
        $data = [
            'nombre_archivo' => $file['name'],
            'ruta_almacenamiento' => $relativePath,
            'tipo_documento' => $tipoDoc,
            'id_licitacion' => !empty($input['id_licitacion']) ? (int) $input['id_licitacion'] : null,
            'id_propuesta' => !empty($input['id_propuesta']) ? (int) $input['id_propuesta'] : null,
            'id_proveedor' => !empty($input['id_proveedor']) ? (int) $input['id_proveedor'] : null,
            'id_contrato' => !empty($input['id_contrato']) ? (int) $input['id_contrato'] : null,
            'id_evaluacion' => !empty($input['id_evaluacion']) ? (int) $input['id_evaluacion'] : null,
            'version' => 1,
            'subido_por' => $idUsuario,
            'tamano_bytes' => $file['size'],
        ];
        $id = $this->repo->create($data);
        auditLog($idUsuario, 'documento', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function get(int $id, int $idUsuario, string $rol): ?array {
        $doc = $this->repo->findById($id);
        if (!$doc) return null;
        // Admin puede ver todo
        if ($rol === 'ADMINISTRADOR') return $doc;
        // Proveedor solo puede ver sus documentos asociados
        if ($rol === 'PROVEEDOR') {
            // Si está asociado a su proveedor
            if (!empty($doc['id_proveedor'])) {
                require_once __DIR__ . '/../repositories/ProveedorRepository.php';
                $provRepo = new ProveedorRepository();
                $prov = $provRepo->findByUsuario($idUsuario);
                if ($prov && (int) $prov['id_proveedor'] === (int) $doc['id_proveedor']) {
                    return $doc;
                }
            }
            // Si está asociado a su propuesta
            if (!empty($doc['id_propuesta'])) {
                require_once __DIR__ . '/../repositories/ParticipacionRepository.php';
                $partRepo = new ParticipacionRepository();
                $propRepo = new PropuestaRepository();
                $prop = $propRepo->findById((int) $doc['id_propuesta']);
                if ($prop) {
                    $part = $partRepo->findById((int) $prop['id_participacion']);
                    $provRepo = new ProveedorRepository();
                    $prov = $provRepo->findByUsuario($idUsuario);
                    if ($part && $prov && (int) $part['id_proveedor'] === (int) $prov['id_proveedor']) {
                        return $doc;
                    }
                }
            }
            // Documentos de licitación públicos (bases, anexos técnicos, planos, formatos, aclaraciones)
            $publicTypes = ['BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACLARACION'];
            if (!empty($doc['id_licitacion']) && in_array($doc['tipo_documento'], $publicTypes, true)) {
                return $doc;
            }
        }
        // Público solo puede ver documentos públicos de licitación
        if ($rol === 'PUBLICO') {
            $publicTypes = ['BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACLARACION'];
            if (!empty($doc['id_licitacion']) && in_array($doc['tipo_documento'], $publicTypes, true)) {
                return $doc;
            }
        }
        return null;
    }
}
