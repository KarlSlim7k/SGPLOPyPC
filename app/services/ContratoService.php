<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ContratoRepository.php';
require_once __DIR__ . '/../repositories/LicitacionRepository.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../repositories/PropuestaRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class ContratoService {
    private ContratoRepository $repo;
    private LicitacionRepository $licRepo;
    private ProveedorRepository $provRepo;
    private PropuestaRepository $propRepo;

    public function __construct() {
        $this->repo = new ContratoRepository();
        $this->licRepo = new LicitacionRepository();
        $this->provRepo = new ProveedorRepository();
        $this->propRepo = new PropuestaRepository();
    }

    public function get(int $id): ?array {
        return $this->repo->findById($id);
    }

    public function list(?string $estatus = null): array {
        return $this->repo->findAll($estatus);
    }

    public function create(array $input, int $idUsuario): array {
        $errors = $this->validateCreate($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        $idLicitacion = (int) $input['id_licitacion'];
        $licitacion = $this->licRepo->findById($idLicitacion);
        if (!$licitacion) {
            return ['ok' => false, 'errors' => ['Licitación no encontrada.']];
        }
        if ($licitacion['estado_proceso'] !== 'ADJUDICADA') {
            return ['ok' => false, 'errors' => ['La licitación debe estar en estado ADJUDICADA para generar un contrato.']];
        }
        $existente = $this->repo->findByLicitacion($idLicitacion);
        if ($existente) {
            return ['ok' => false, 'errors' => ['La licitación ya tiene un contrato registrado.']];
        }
        $idProveedor = (int) $input['id_proveedor'];
        $proveedor = $this->provRepo->findById($idProveedor);
        if (!$proveedor) {
            return ['ok' => false, 'errors' => ['Proveedor no encontrado.']];
        }
        $propuestas = $this->propRepo->findByLicitacion($idLicitacion);
        $propuestaAceptada = null;
        foreach ($propuestas as $propuesta) {
            if (($propuesta['estatus'] ?? null) === 'ACEPTADA') {
                $propuestaAceptada = $propuesta;
                break;
            }
        }
        if ($propuestaAceptada && (int) $propuestaAceptada['id_proveedor'] !== $idProveedor) {
            return ['ok' => false, 'errors' => ['El proveedor del contrato debe corresponder al proveedor adjudicado.']];
        }
        $numero = trim($input['numero_contrato']);
        $dup = $this->repo->findByNumero($numero);
        if ($dup) {
            return ['ok' => false, 'errors' => ['El número de contrato ya existe.']];
        }
        $monto = (float) $input['monto_contrato'];
        if ($monto <= 0) {
            return ['ok' => false, 'errors' => ['El monto del contrato debe ser mayor a 0.']];
        }
        $fechaInicio = isset($input['fecha_inicio']) && $input['fecha_inicio'] !== '' ? $input['fecha_inicio'] : null;
        $fechaFin = isset($input['fecha_fin']) && $input['fecha_fin'] !== '' ? $input['fecha_fin'] : null;
        if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
            return ['ok' => false, 'errors' => ['La fecha de fin no puede ser anterior a la fecha de inicio.']];
        }
        $data = [
            'id_licitacion' => $idLicitacion,
            'id_proveedor' => $idProveedor,
            'numero_contrato' => $numero,
            'monto_contrato' => $monto,
            'fecha_adjudicacion' => $input['fecha_adjudicacion'],
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estatus' => $input['estatus'] ?? 'EN_FORMALIZACION',
        ];
        $id = $this->repo->create($data);
        auditLog($idUsuario, 'contrato', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Contrato no encontrado.']];
        }
        $errors = $this->validateUpdate($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        if (isset($input['numero_contrato'])) {
            $dup = $this->repo->findByNumero(trim($input['numero_contrato']));
            if ($dup && (int) $dup['id_contrato'] !== $id) {
                return ['ok' => false, 'errors' => ['El número de contrato ya existe.']];
            }
        }
        $data = [];
        $fields = ['numero_contrato', 'monto_contrato', 'fecha_adjudicacion', 'fecha_inicio', 'fecha_fin'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $input)) {
                if ($f === 'numero_contrato') {
                    $data[$f] = trim($input[$f]);
                } elseif (in_array($f, ['fecha_inicio','fecha_fin'], true)) {
                    $data[$f] = $input[$f] !== '' ? $input[$f] : null;
                } else {
                    $data[$f] = $input[$f];
                }
            }
        }
        if (isset($data['monto_contrato']) && (float) $data['monto_contrato'] <= 0) {
            return ['ok' => false, 'errors' => ['El monto del contrato debe ser mayor a 0.']];
        }
        $fi = $data['fecha_inicio'] ?? $existing['fecha_inicio'];
        $ff = $data['fecha_fin'] ?? $existing['fecha_fin'];
        if ($fi && $ff && $ff < $fi) {
            return ['ok' => false, 'errors' => ['La fecha de fin no puede ser anterior a la fecha de inicio.']];
        }
        if (empty($data)) {
            return ['ok' => true];
        }
        $this->repo->update($id, $data);
        auditLog($idUsuario, 'contrato', $id, 'ACTUALIZAR', $existing, $data);
        return ['ok' => true];
    }

    public function cambiarEstatus(int $id, string $nuevoEstatus, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Contrato no encontrado.']];
        }
        $estadosPermitidos = ['EN_FORMALIZACION','VIGENTE','EN_EJECUCION','CONCLUIDO','RESCINDIDO'];
        if (!in_array($nuevoEstatus, $estadosPermitidos, true)) {
            return ['ok' => false, 'errors' => ['Estatus de contrato no válido.']];
        }
        $this->repo->update($id, ['estatus' => $nuevoEstatus]);
        auditLog($idUsuario, 'contrato', $id, 'ACTUALIZAR', ['estatus' => $existing['estatus']], ['estatus' => $nuevoEstatus]);
        return ['ok' => true];
    }

    private function validateCreate(array $input): array {
        $errors = [];
        if (!isset($input['id_licitacion']) || (int) $input['id_licitacion'] <= 0) {
            $errors[] = 'El campo id_licitacion es obligatorio.';
        }
        if (!isset($input['id_proveedor']) || (int) $input['id_proveedor'] <= 0) {
            $errors[] = 'El campo id_proveedor es obligatorio.';
        }
        if (trim($input['numero_contrato'] ?? '') === '') {
            $errors[] = 'El número de contrato es obligatorio.';
        }
        if (!isset($input['monto_contrato']) || (float) $input['monto_contrato'] <= 0) {
            $errors[] = 'El monto del contrato es obligatorio y debe ser mayor a 0.';
        }
        if (empty($input['fecha_adjudicacion']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['fecha_adjudicacion'])) {
            $errors[] = 'La fecha de adjudicación es obligatoria y debe tener formato YYYY-MM-DD.';
        }
        return $errors;
    }

    private function validateUpdate(array $input): array {
        $errors = [];
        if (array_key_exists('numero_contrato', $input) && trim($input['numero_contrato']) === '') {
            $errors[] = 'El número de contrato no puede estar vacío.';
        }
        if (array_key_exists('monto_contrato', $input) && (float) $input['monto_contrato'] <= 0) {
            $errors[] = 'El monto del contrato debe ser mayor a 0.';
        }
        if (array_key_exists('fecha_adjudicacion', $input) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['fecha_adjudicacion'])) {
            $errors[] = 'La fecha de adjudicación debe tener formato YYYY-MM-DD.';
        }
        return $errors;
    }
}
