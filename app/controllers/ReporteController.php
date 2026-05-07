<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/ReporteService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class ReporteController {
    private ReporteService $service;

    public function __construct() {
        $this->service = new ReporteService();
    }

    public function dashboardResumen(): never {
        $data = $this->service->resumenDashboard();
        jsonResponse(true, 'Resumen del dashboard', $data, null, 200);
    }

    public function dashboardLicitacionesPorEstado(): never {
        $data = $this->service->licitacionesPorEstado();
        jsonResponse(true, 'Licitaciones por estado', $data, null, 200);
    }

    public function dashboardLicitacionesPorTipo(): never {
        $data = $this->service->licitacionesPorTipo();
        jsonResponse(true, 'Licitaciones por tipo', $data, null, 200);
    }

    public function dashboardLicitacionesPorMes(): never {
        $year = isset($_GET['year']) ? (int) $_GET['year'] : null;
        $data = $this->service->licitacionesPorMes($year);
        jsonResponse(true, 'Licitaciones por mes', $data, null, 200);
    }

    public function dashboardParticipacionProveedores(): never {
        $data = $this->service->participacionProveedores();
        jsonResponse(true, 'Participación de proveedores', $data, null, 200);
    }

    public function dashboardAdjudicacionesPorPeriodo(): never {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $result = $this->service->adjudicacionesPorPeriodo($from, $to);
        if (!$result['ok']) {
            jsonResponse(false, 'Error de validación', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Adjudicaciones por periodo', $result['data'], null, 200);
    }

    public function exportarLicitacionesCsv(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $filters = [
            'estado' => $_GET['estado'] ?? null,
            'dependencia' => $_GET['dependencia'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];
        $result = $this->service->exportarLicitacionesCsv($filters, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error de exportación', null, $result['errors'], 422);
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        echo $result['csv'];
        exit;
    }

    public function exportarContratosCsv(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $filters = [
            'estatus' => $_GET['estatus'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];
        $result = $this->service->exportarContratosCsv($filters, (int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error de exportación', null, $result['errors'], 422);
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        echo $result['csv'];
        exit;
    }

    public function historialLicitacion(int $id): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $data = $this->service->historialLicitacion($id);
        auditLog((int) $user['id_usuario'], 'licitacion_historial', $id, 'CREAR', null, [
            'eventos_consultados' => count($data),
        ]);
        jsonResponse(true, 'Historial de la licitación', $data, null, 200);
    }
}
