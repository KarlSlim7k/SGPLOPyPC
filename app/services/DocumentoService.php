<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/DocumentoRepository.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../repositories/ParticipacionRepository.php';

class DocumentoService {
    private DocumentoRepository $repo;
    private ProveedorRepository $provRepo;
    private ParticipacionRepository $partRepo;
    private PropuestaRepository $propRepo;

    public function __construct() {
        $this->repo = new DocumentoRepository();
        $this->provRepo = new ProveedorRepository();
        $this->partRepo = new ParticipacionRepository();
        $this->propRepo = new PropuestaRepository();
    }

    public function upload(array $file, array $input, int $idUsuario, string $rol): array {
        $errors = [];
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No se recibió un archivo válido.';
        }
        $tipoDoc = $input['tipo_documento'] ?? '';
        $tiposValidos = ['BASES_LICITACION','ANEXO_TECNICO','PLANO','FORMATO_OFICIAL','ACTA_PROCESO','PROPUESTA_TECNICA','PROPUESTA_ECONOMICA','DOC_COMPLEMENTARIA','DOC_LEGAL_PROVEEDOR','DOC_CONTRATO','ACLARACION','DICTAMEN'];
        if (!in_array($tipoDoc, $tiposValidos, true)) {
            $errors[] = 'Tipo de documento no válido.';
        }

        if ($rol === 'PROVEEDOR') {
            $providerValidation = $this->normalizeProviderUploadContext($input, $idUsuario, $tipoDoc);
            if (!$providerValidation['ok']) {
                $errors = array_merge($errors, $providerValidation['errors']);
            } else {
                $input = $providerValidation['input'];
            }
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

    public function listMios(
        int $idUsuario,
        int $page,
        int $limit,
        ?string $context = null,
        ?int $idPropuesta = null,
        ?string $tipoDocumento = null
    ): array {
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if (!$proveedor) {
            return ['ok' => false, 'errors' => ['El usuario no tiene un perfil de proveedor registrado.']];
        }

        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $normalizedContext = ($context !== null && in_array($context, ['proveedor', 'propuesta'], true)) ? $context : null;
        $normalizedTipo = ($tipoDocumento !== null && trim($tipoDocumento) !== '') ? strtoupper(trim($tipoDocumento)) : null;

        return [
            'ok' => true,
            'data' => $this->repo->findByProveedorForPortal(
                (int) $proveedor['id_proveedor'],
                $page,
                $limit,
                $normalizedContext,
                ($idPropuesta !== null && $idPropuesta > 0) ? $idPropuesta : null,
                $normalizedTipo
            ),
        ];
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

    public function download(int $id, int $idUsuario, string $rol): array {
        $doc = $this->get($id, $idUsuario, $rol);
        if (!$doc) {
            return ['ok' => false, 'errors' => ['Documento no encontrado o sin acceso.']];
        }

        $absolutePath = realpath(__DIR__ . '/../../' . $doc['ruta_almacenamiento']);
        $storageBase = realpath(__DIR__ . '/../../storage');
        if (!$absolutePath || !$storageBase || !str_starts_with($absolutePath, $storageBase) || !is_file($absolutePath)) {
            return ['ok' => false, 'errors' => ['El archivo solicitado no está disponible.']];
        }

        return [
            'ok' => true,
            'data' => [
                'path' => $absolutePath,
                'nombre_archivo' => $doc['nombre_archivo'],
                'mime_type' => mime_content_type($absolutePath) ?: 'application/octet-stream',
            ],
        ];
    }

    private function normalizeProviderUploadContext(array $input, int $idUsuario, string $tipoDoc): array {
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if (!$proveedor) {
            return ['ok' => false, 'errors' => ['El usuario no tiene un perfil de proveedor registrado.']];
        }

        $idProveedor = (int) $proveedor['id_proveedor'];

        if (!empty($input['id_proveedor']) && (int) $input['id_proveedor'] !== $idProveedor) {
            return ['ok' => false, 'errors' => ['No puedes asociar documentos a otro proveedor.']];
        }

        if (!empty($input['id_propuesta'])) {
            $allowedProposalTypes = ['PROPUESTA_TECNICA', 'PROPUESTA_ECONOMICA', 'DOC_COMPLEMENTARIA'];
            if (!in_array($tipoDoc, $allowedProposalTypes, true)) {
                return ['ok' => false, 'errors' => ['Tipo de documento no válido para propuesta.']];
            }

            $prop = $this->propRepo->findById((int) $input['id_propuesta']);
            $part = $prop ? $this->partRepo->findById((int) $prop['id_participacion']) : null;
            if (!$part || (int) $part['id_proveedor'] !== $idProveedor) {
                return ['ok' => false, 'errors' => ['No puedes asociar documentos a una propuesta ajena.']];
            }
            unset($input['id_proveedor'], $input['id_licitacion'], $input['id_contrato'], $input['id_evaluacion']);
            return ['ok' => true, 'input' => $input];
        }

        if ($tipoDoc !== 'DOC_LEGAL_PROVEEDOR') {
            return ['ok' => false, 'errors' => ['Solo puedes subir documentos legales de proveedor o documentos asociados a tus propuestas.']];
        }

        $input['id_proveedor'] = $idProveedor;
        unset($input['id_propuesta'], $input['id_licitacion'], $input['id_contrato'], $input['id_evaluacion']);
        return ['ok' => true, 'input' => $input];
    }
}
