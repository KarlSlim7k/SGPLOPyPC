<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/LicitacionRepository.php';
require_once __DIR__ . '/../repositories/EvaluacionRepository.php';
require_once __DIR__ . '/../repositories/ParticipacionRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class LicitacionService {
    private LicitacionRepository $repo;
    private EvaluacionRepository $evalRepo;
    private ParticipacionRepository $partRepo;
    private PropuestaRepository $propRepo;

    public function __construct() {
        $this->repo = new LicitacionRepository();
        $this->evalRepo = new EvaluacionRepository();
        $this->partRepo = new ParticipacionRepository();
        $this->propRepo = new PropuestaRepository();
    }

    public function list(?string $estado, ?string $tipo, ?int $dependencia, ?array $estadosPermitidos = null): array {
        return $this->repo->findAll($estado, $tipo, $dependencia, $estadosPermitidos);
    }

    public function get(int $id): ?array {
        return $this->repo->findById($id);
    }

    public function create(array $input, int $idUsuario): array {
        $errors = $this->validate($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        if ($this->repo->findByNumero($input['numero_licitacion'])) {
            return ['ok' => false, 'errors' => ['El número de licitación ya existe.']];
        }
        $data = [
            'numero_licitacion' => trim($input['numero_licitacion']),
            'id_dependencia' => (int) $input['id_dependencia'],
            'id_usuario_responsable' => $idUsuario,
            'tipo_procedimiento' => $input['tipo_procedimiento'],
            'descripcion_proyecto' => trim($input['descripcion_proyecto']),
            'presupuesto_estimado' => (float) $input['presupuesto_estimado'],
            'ubicacion_proyecto' => isset($input['ubicacion_proyecto']) ? trim($input['ubicacion_proyecto']) : null,
            'estado_proceso' => $input['estado_proceso'] ?? 'BORRADOR',
        ];
        $id = $this->repo->create($data);
        auditLog($idUsuario, 'licitacion', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Licitación no encontrada.']];
        }
        $errors = $this->validate($input, true);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        if (isset($input['numero_licitacion'])) {
            $dup = $this->repo->findByNumero(trim($input['numero_licitacion']));
            if ($dup && (int) $dup['id_licitacion'] !== $id) {
                return ['ok' => false, 'errors' => ['El número de licitación ya existe.']];
            }
        }
        $data = [];
        $fields = ['numero_licitacion', 'id_dependencia', 'tipo_procedimiento', 'descripcion_proyecto', 'presupuesto_estimado', 'ubicacion_proyecto'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $input)) {
                $data[$f] = is_string($input[$f]) ? trim($input[$f]) : $input[$f];
            }
        }
        $this->repo->update($id, $data);
        auditLog($idUsuario, 'licitacion', $id, 'ACTUALIZAR', $existing, $data);
        return ['ok' => true];
    }

    public function cambiarEstado(int $id, string $nuevoEstado, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Licitación no encontrada.']];
        }
        $estadosPermitidos = ['BORRADOR','PUBLICADA','EN_ACLARACIONES','RECEPCION_PROPUESTAS','EN_EVALUACION','ADJUDICADA','DESIERTA','CANCELADA'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            return ['ok' => false, 'errors' => ['Estado no válido.']];
        }
        $transiciones = [
            'BORRADOR' => ['PUBLICADA','CANCELADA'],
            'PUBLICADA' => ['EN_ACLARACIONES','RECEPCION_PROPUESTAS','CANCELADA'],
            'EN_ACLARACIONES' => ['RECEPCION_PROPUESTAS','CANCELADA'],
            'RECEPCION_PROPUESTAS' => ['EN_EVALUACION','DESIERTA','CANCELADA'],
            'EN_EVALUACION' => ['ADJUDICADA','DESIERTA','CANCELADA'],
            'ADJUDICADA' => [],
            'DESIERTA' => [],
            'CANCELADA' => [],
        ];
        $actual = $existing['estado_proceso'];
        if ($nuevoEstado !== $actual && !in_array($nuevoEstado, $transiciones[$actual] ?? [], true)) {
            return ['ok' => false, 'errors' => ["Transición de estado no permitida: {$actual} → {$nuevoEstado}"]];
        }
        $this->repo->update($id, ['estado_proceso' => $nuevoEstado]);
        auditLog($idUsuario, 'licitacion', $id, 'ACTUALIZAR', ['estado_proceso' => $actual], ['estado_proceso' => $nuevoEstado]);
        return ['ok' => true];
    }

    public function adjudicar(int $id, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Licitación no encontrada.']];
        }
        if ($existing['estado_proceso'] !== 'EN_EVALUACION') {
            return ['ok' => false, 'errors' => ['La licitación debe estar en estado EN_EVALUACION para adjudicarse.']];
        }
        $ganadora = $this->evalRepo->findGanadoraByLicitacion($id);
        if (!$ganadora) {
            return ['ok' => false, 'errors' => ['No existe una propuesta solvente para adjudicar.']];
        }
        $idPropuestaGanadora = (int) $ganadora['id_propuesta'];
        $idParticipacionGanadora = (int) $ganadora['id_participacion'];

        $this->repo->update($id, ['estado_proceso' => 'ADJUDICADA']);
        $this->partRepo->updateEstatus($idParticipacionGanadora, 'GANADOR');
        $this->partRepo->updateEstatusByLicitacion($id, 'NO_GANADOR', $idParticipacionGanadora);
        $this->propRepo->updateEstatus($idPropuestaGanadora, 'ACEPTADA');
        $this->propRepo->updateEstatusByLicitacion($id, 'RECHAZADA', $idPropuestaGanadora);

        auditLog($idUsuario, 'licitacion', $id, 'ACTUALIZAR', ['estado_proceso' => 'EN_EVALUACION'], ['estado_proceso' => 'ADJUDICADA']);
        auditLog($idUsuario, 'participacion', $idParticipacionGanadora, 'ACTUALIZAR', ['estatus' => 'PROPUESTA_ENVIADA'], ['estatus' => 'GANADOR']);
        return ['ok' => true, 'id_propuesta_ganadora' => $idPropuestaGanadora];
    }

    private function validate(array $input, bool $isUpdate = false): array {
        $errors = [];
        if (!$isUpdate || array_key_exists('numero_licitacion', $input)) {
            if (trim($input['numero_licitacion'] ?? '') === '') $errors[] = 'El número de licitación es obligatorio.';
        }
        if (!$isUpdate || array_key_exists('id_dependencia', $input)) {
            if (!isset($input['id_dependencia']) || (int) $input['id_dependencia'] <= 0) $errors[] = 'La dependencia es obligatoria.';
        }
        if (!$isUpdate || array_key_exists('tipo_procedimiento', $input)) {
            $tipos = ['LICITACION_PUBLICA','INVITACION_RESTRINGIDA','ADJUDICACION_DIRECTA'];
            if (!isset($input['tipo_procedimiento']) || !in_array($input['tipo_procedimiento'], $tipos, true)) $errors[] = 'Tipo de procedimiento no válido.';
        }
        if (!$isUpdate || array_key_exists('descripcion_proyecto', $input)) {
            if (trim($input['descripcion_proyecto'] ?? '') === '') $errors[] = 'La descripción del proyecto es obligatoria.';
        }
        if (!$isUpdate || array_key_exists('presupuesto_estimado', $input)) {
            $presupuesto = (float) ($input['presupuesto_estimado'] ?? 0);
            if ($presupuesto <= 0) $errors[] = 'El presupuesto estimado debe ser mayor a 0.';
        }
        if (array_key_exists('estado_proceso', $input) && $input['estado_proceso'] !== null) {
            $estados = ['BORRADOR','PUBLICADA','EN_ACLARACIONES','RECEPCION_PROPUESTAS','EN_EVALUACION','ADJUDICADA','DESIERTA','CANCELADA'];
            if (!in_array($input['estado_proceso'], $estados, true)) $errors[] = 'Estado de proceso no válido.';
        }
        return $errors;
    }
}
