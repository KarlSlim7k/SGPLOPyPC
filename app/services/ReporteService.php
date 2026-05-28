<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ReporteRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class ReporteService {
    private ReporteRepository $repo;

    public function __construct() {
        $this->repo = new ReporteRepository();
    }

    public function resumenDashboard(): array {
        $resumen = $this->repo->resumenDashboard();
        $resumen['tiempo_promedio_publicacion_adjudicacion_dias'] = $this->repo->tiempoPromedioPublicacionAdjudicacion();
        return $resumen;
    }

    public function licitacionesPorEstado(): array {
        return $this->repo->licitacionesPorEstado();
    }

    public function licitacionesPorTipo(): array {
        return $this->repo->licitacionesPorTipo();
    }

    public function licitacionesPorMes(?int $year = null): array {
        $targetYear = $year ?? (int) date('Y');
        $rows = $this->repo->licitacionesPorMes($targetYear);
        $labels = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        return array_map(function (array $row) use ($labels): array {
            $mes = (int) $row['mes'];
            return [
                'mes' => $mes,
                'mes_corto' => $labels[$mes] ?? (string) $mes,
                'cantidad' => (int) $row['cantidad'],
            ];
        }, $rows);
    }

    public function participacionProveedores(): array {
        return $this->repo->participacionProveedores();
    }

    public function adjudicacionesPorPeriodo(?string $from, ?string $to): array {
        $from = $this->sanitizeDate($from);
        $to = $this->sanitizeDate($to);
        if ($from === null || $to === null) {
            return ['ok' => false, 'errors' => ['Rango de fechas inválido.']];
        }
        if ($from > $to) {
            return ['ok' => false, 'errors' => ['La fecha inicial no puede ser mayor que la fecha final.']];
        }
        $data = $this->repo->adjudicacionesPorPeriodo($from, $to);
        return ['ok' => true, 'data' => $data];
    }

    public function exportarLicitacionesCsv(array $filters, int $idUsuario): array {
        $estado = isset($filters['estado']) && $filters['estado'] !== '' ? $filters['estado'] : null;
        $dependencia = isset($filters['dependencia']) && (int) $filters['dependencia'] > 0 ? (int) $filters['dependencia'] : null;
        $from = isset($filters['from']) && $filters['from'] !== '' ? $filters['from'] : null;
        $to = isset($filters['to']) && $filters['to'] !== '' ? $filters['to'] : null;

        $rows = $this->repo->findLicitacionesForExport($estado, $dependencia, $from, $to);

        if (empty($rows)) {
            return ['ok' => false, 'errors' => ['No hay datos para exportar con los filtros seleccionados.']];
        }

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return ['ok' => false, 'errors' => ['No se pudo crear el buffer de exportación.']];
        }

        // BOM UTF-8
        fprintf($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'ID',
            'Número Licitación',
            'Dependencia',
            'Tipo Procedimiento',
            'Descripción',
            'Presupuesto Estimado',
            'Ubicación',
            'Estado Proceso',
            'Fecha Creación',
            'Fecha Actualización',
            'Responsable',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id_licitacion'],
                $row['numero_licitacion'],
                $row['dependencia_nombre'],
                $row['tipo_procedimiento'],
                $row['descripcion_proyecto'],
                $row['presupuesto_estimado'],
                $row['ubicacion_proyecto'] ?? '',
                $row['estado_proceso'],
                $row['fecha_creacion'],
                $row['fecha_actualizacion'],
                $row['responsable_nombre'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        auditLog($idUsuario, 'reporte_exportacion', 0, 'EXPORT', null, [
            'tipo' => 'csv',
            'filtros' => $filters,
            'registros' => count($rows),
        ]);

        return ['ok' => true, 'csv' => $csv, 'filename' => 'licitaciones_' . date('Ymd_His') . '.csv'];
    }

    public function exportarContratosCsv(array $filters, int $idUsuario): array {
        $estatus = isset($filters['estatus']) && $filters['estatus'] !== '' ? $filters['estatus'] : null;
        $from = isset($filters['from']) && $filters['from'] !== '' ? $filters['from'] : null;
        $to = isset($filters['to']) && $filters['to'] !== '' ? $filters['to'] : null;

        $rows = $this->repo->findContratosForExport($estatus, $from, $to);
        if (empty($rows)) {
            return ['ok' => false, 'errors' => ['No hay contratos para exportar con los filtros seleccionados.']];
        }

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return ['ok' => false, 'errors' => ['No se pudo crear el buffer de exportación.']];
        }

        fprintf($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'ID Contrato',
            'Número Contrato',
            'Número Licitación',
            'Dependencia',
            'Tipo Procedimiento',
            'Proveedor',
            'RFC',
            'Monto Contrato',
            'Fecha Adjudicación',
            'Fecha Inicio',
            'Fecha Fin',
            'Estatus',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id_contrato'],
                $row['numero_contrato'],
                $row['numero_licitacion'],
                $row['dependencia_nombre'],
                $row['tipo_procedimiento'],
                $row['nombre_empresa'],
                $row['registro_fiscal'],
                $row['monto_contrato'],
                $row['fecha_adjudicacion'],
                $row['fecha_inicio'] ?? '',
                $row['fecha_fin'] ?? '',
                $row['estatus'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        auditLog($idUsuario, 'reporte_exportacion', 0, 'EXPORT', null, [
            'tipo' => 'csv_contratos',
            'filtros' => $filters,
            'registros' => count($rows),
        ]);

        return ['ok' => true, 'csv' => $csv, 'filename' => 'contratos_' . date('Ymd_His') . '.csv'];
    }

    public function historialLicitacion(int $idLicitacion): array {
        return $this->repo->findHistorialByLicitacion($idLicitacion);
    }

    private function sanitizeDate(?string $date): ?string {
        if ($date === null || $date === '') return null;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return $date;
        }
        return null;
    }
}
