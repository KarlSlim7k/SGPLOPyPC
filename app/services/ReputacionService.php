<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ReputacionRepository.php';
require_once __DIR__ . '/../repositories/ContratoRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class ReputacionService {
    private ReputacionRepository $repo;
    private ContratoRepository $contratoRepo;

    public function __construct() {
        $this->repo = new ReputacionRepository();
        $this->contratoRepo = new ContratoRepository();
    }

    /**
     * Crea una evaluación post-contrato. Solo ADMINISTRADOR puede evaluar.
     * El contrato debe estar en estado CONCLUIDO o EN_EJECUCION.
     */
    public function crearEvaluacion(int $idContrato, array $input, int $idEvaluador): array {
        $contrato = $this->contratoRepo->findById($idContrato);
        if (!$contrato) {
            return ['ok' => false, 'errors' => ['Contrato no encontrado.'], 'status' => 404];
        }

        $estatusPermitidos = ['CONCLUIDO', 'EN_EJECUCION', 'VIGENTE'];
        if (!in_array($contrato['estatus'], $estatusPermitidos, true)) {
            return ['ok' => false, 'errors' => ['El contrato debe estar en estado CONCLUIDO, EN_EJECUCION o VIGENTE para ser evaluado.'], 'status' => 409];
        }

        if ($this->repo->findByContrato($idContrato) !== null) {
            return ['ok' => false, 'errors' => ['Este contrato ya fue evaluado.'], 'status' => 409];
        }

        // Validar criterios
        $errors = [];
        foreach (['puntualidad', 'calidad', 'comunicacion', 'cumplimiento_alcance'] as $campo) {
            $val = isset($input[$campo]) ? (int) $input[$campo] : 0;
            if ($val < 1 || $val > 5) {
                $errors[] = "El campo '{$campo}' debe ser un número entre 1 y 5.";
            }
        }
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors, 'status' => 422];
        }

        $puntualidad = (int) $input['puntualidad'];
        $calidad = (int) $input['calidad'];
        $comunicacion = (int) $input['comunicacion'];
        $cumplimiento = (int) $input['cumplimiento_alcance'];
        $promedio = round(($puntualidad + $calidad + $comunicacion + $cumplimiento) / 4, 2);

        $id = $this->repo->create([
            'id_contrato' => $idContrato,
            'id_proveedor' => (int) $contrato['id_proveedor'],
            'puntualidad' => $puntualidad,
            'calidad' => $calidad,
            'comunicacion' => $comunicacion,
            'cumplimiento_alcance' => $cumplimiento,
            'promedio' => $promedio,
            'comentarios' => isset($input['comentarios']) ? trim((string) $input['comentarios']) : null,
            'id_usuario_evaluador' => $idEvaluador,
        ]);

        // Recalcular score del proveedor
        $this->repo->recalcularScore((int) $contrato['id_proveedor']);

        auditLog($idEvaluador, 'proveedor_evaluacion_postcontrato', $id, 'CREAR', null, [
            'id_contrato' => $idContrato,
            'id_proveedor' => $contrato['id_proveedor'],
            'promedio' => $promedio,
        ]);

        $score = $this->repo->findScoreProveedor((int) $contrato['id_proveedor']);

        return [
            'ok' => true,
            'data' => [
                'id_evaluacion' => $id,
                'promedio' => $promedio,
                'score_reputacion_actualizado' => $score['score_reputacion'],
                'total_evaluaciones' => (int) $score['total_evaluaciones'],
            ],
        ];
    }

    /**
     * Devuelve el perfil de reputación de un proveedor: score + historial de evaluaciones + desglose.
     */
    public function getReputacion(int $idProveedor): array {
        $score = $this->repo->findScoreProveedor($idProveedor);
        $historial = $this->repo->findByProveedor($idProveedor, 20);
        $desglose = $this->repo->findDesgloseByProveedor($idProveedor);

        $evaluaciones = array_map(function (array $e) {
            return [
                'id_eval' => (int) $e['id_evaluacion'],
                'id_contrato' => (int) $e['id_contrato'],
                'contrato_numero' => $e['numero_contrato'],
                'puntualidad' => (int) $e['puntualidad'],
                'calidad' => (int) $e['calidad'],
                'comunicacion' => (int) $e['comunicacion'],
                'cumplimiento_alcance' => (int) $e['cumplimiento_alcance'],
                'comentarios' => $e['comentarios'],
                'fecha_evaluacion' => $e['fecha_evaluacion'],
            ];
        }, $historial);

        return [
            'id_proveedor' => $idProveedor,
            'score_reputacion' => $score['score_reputacion'] !== null
                ? (float) $score['score_reputacion'] : null,
            'score' => $score['score_reputacion'] !== null
                ? (float) $score['score_reputacion'] : null,
            'total_evaluaciones' => (int) $score['total_evaluaciones'],
            'nivel' => $this->nivelReputacion($score['score_reputacion']),
            'historial' => array_map(function (array $e) {
                return [
                    'id_evaluacion' => (int) $e['id_evaluacion'],
                    'id_contrato' => (int) $e['id_contrato'],
                    'numero_contrato' => $e['numero_contrato'],
                    'descripcion_proyecto' => $e['descripcion_proyecto'],
                    'puntualidad' => (int) $e['puntualidad'],
                    'calidad' => (int) $e['calidad'],
                    'comunicacion' => (int) $e['comunicacion'],
                    'cumplimiento_alcance' => (int) $e['cumplimiento_alcance'],
                    'promedio' => (float) $e['promedio'],
                    'comentarios' => $e['comentarios'],
                    'evaluador' => $e['evaluador_nombre'],
                    'fecha_evaluacion' => $e['fecha_evaluacion'],
                ];
            }, $historial),
            'evaluaciones' => $evaluaciones,
            'desglose' => [
                'puntualidad_promedio' => $desglose['puntualidad_promedio'] !== null ? (float) $desglose['puntualidad_promedio'] : null,
                'calidad_promedio' => $desglose['calidad_promedio'] !== null ? (float) $desglose['calidad_promedio'] : null,
                'comunicacion_promedio' => $desglose['comunicacion_promedio'] !== null ? (float) $desglose['comunicacion_promedio'] : null,
                'cumplimiento_alcance_promedio' => $desglose['cumplimiento_alcance_promedio'] !== null ? (float) $desglose['cumplimiento_alcance_promedio'] : null,
            ],
        ];
    }

    private function nivelReputacion(mixed $score): string {
        if ($score === null) return 'sin_evaluaciones';
        $s = (float) $score;
        if ($s >= 4.5) return 'excelente';
        if ($s >= 3.5) return 'bueno';
        if ($s >= 2.5) return 'regular';
        return 'deficiente';
    }
}
