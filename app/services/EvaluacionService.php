<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/EvaluacionRepository.php';
require_once __DIR__ . '/../repositories/ParticipacionRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class EvaluacionService {
    private EvaluacionRepository $repo;
    private PropuestaRepository $propRepo;

    public function __construct() {
        $this->repo = new EvaluacionRepository();
        $this->propRepo = new PropuestaRepository();
    }

    public function get(int $id): ?array {
        return $this->repo->findById($id);
    }

    public function list(?int $idLicitacion = null): array {
        return $this->repo->findAll($idLicitacion);
    }

    public function create(array $input, int $idEvaluador): array {
        $errors = $this->validateInput($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        $idPropuesta = (int) $input['id_propuesta'];
        $propuesta = $this->propRepo->findById($idPropuesta);
        if (!$propuesta) {
            return ['ok' => false, 'errors' => ['Propuesta no encontrada.']];
        }
        $existente = $this->repo->findByPropuesta($idPropuesta);
        if ($existente) {
            return ['ok' => false, 'errors' => ['La propuesta ya tiene una evaluación registrada.']];
        }
        $pt = isset($input['puntaje_tecnico']) ? (float) $input['puntaje_tecnico'] : null;
        $pe = isset($input['puntaje_economico']) ? (float) $input['puntaje_economico'] : null;
        $total = $this->calcularTotal($pt, $pe, $input['puntaje_total'] ?? null);
        $data = [
            'id_propuesta' => $idPropuesta,
            'id_evaluador' => $idEvaluador,
            'puntaje_tecnico' => $pt,
            'puntaje_economico' => $pe,
            'puntaje_total' => $total,
            'observaciones' => isset($input['observaciones']) ? trim($input['observaciones']) : null,
            'dictamen' => null,
        ];
        $id = $this->repo->create($data);
        if (($propuesta['estatus'] ?? null) === 'RECIBIDA') {
            $this->propRepo->updateEstatus($idPropuesta, 'EN_REVISION');
        }
        auditLog($idEvaluador, 'evaluacion', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input, int $idEvaluador): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Evaluación no encontrada.']];
        }
        $errors = $this->validateInput($input, true);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        $data = [];
        if (array_key_exists('puntaje_tecnico', $input)) {
            $data['puntaje_tecnico'] = $input['puntaje_tecnico'] !== null ? (float) $input['puntaje_tecnico'] : null;
        }
        if (array_key_exists('puntaje_economico', $input)) {
            $data['puntaje_economico'] = $input['puntaje_economico'] !== null ? (float) $input['puntaje_economico'] : null;
        }
        if (array_key_exists('observaciones', $input)) {
            $data['observaciones'] = trim($input['observaciones']);
        }
        if (isset($data['puntaje_tecnico']) || isset($data['puntaje_economico'])) {
            $pt = $data['puntaje_tecnico'] ?? $existing['puntaje_tecnico'];
            $pe = $data['puntaje_economico'] ?? $existing['puntaje_economico'];
            $data['puntaje_total'] = $this->calcularTotal(
                $pt !== null ? (float) $pt : null,
                $pe !== null ? (float) $pe : null,
                null
            );
        }
        if (empty($data)) {
            return ['ok' => true];
        }
        $this->repo->update($id, $data);
        auditLog($idEvaluador, 'evaluacion', $id, 'ACTUALIZAR', $existing, $data);
        return ['ok' => true];
    }

    public function dictamen(int $id, array $input, int $idEvaluador): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Evaluación no encontrada.']];
        }
        $dictamenes = ['SOLVENTE','NO_SOLVENTE','DESCALIFICADA'];
        if (!isset($input['dictamen']) || !in_array($input['dictamen'], $dictamenes, true)) {
            return ['ok' => false, 'errors' => ['Dictamen no válido. Use SOLVENTE, NO_SOLVENTE o DESCALIFICADA.']];
        }
        $data = ['dictamen' => $input['dictamen']];
        if (array_key_exists('observaciones', $input)) {
            $data['observaciones'] = trim($input['observaciones']);
        }
        $this->repo->update($id, $data);
        auditLog($idEvaluador, 'evaluacion', $id, 'ACTUALIZAR', $existing, $data);
        return ['ok' => true];
    }

    private function validateInput(array $input, bool $isUpdate = false): array {
        $errors = [];
        if (!$isUpdate || array_key_exists('id_propuesta', $input)) {
            if (!isset($input['id_propuesta']) || (int) $input['id_propuesta'] <= 0) {
                $errors[] = 'El campo id_propuesta es obligatorio.';
            }
        }
        if (array_key_exists('puntaje_tecnico', $input) && $input['puntaje_tecnico'] !== null) {
            $v = (float) $input['puntaje_tecnico'];
            if ($v < 0 || $v > 100) {
                $errors[] = 'El puntaje técnico debe estar entre 0 y 100.';
            }
        }
        if (array_key_exists('puntaje_economico', $input) && $input['puntaje_economico'] !== null) {
            $v = (float) $input['puntaje_economico'];
            if ($v < 0 || $v > 100) {
                $errors[] = 'El puntaje económico debe estar entre 0 y 100.';
            }
        }
        return $errors;
    }

    private function calcularTotal(?float $pt, ?float $pe, ?float $providedTotal): ?float {
        if ($pt === null && $pe === null) {
            return $providedTotal ?? null;
        }
        $sum = ($pt ?? 0) + ($pe ?? 0);
        if ($providedTotal !== null && abs($sum - (float) $providedTotal) > 0.001) {
            return (float) $providedTotal;
        }
        return $sum;
    }
}
