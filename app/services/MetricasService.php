<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/MetricasRepository.php';
require_once __DIR__ . '/../helpers/SimpleCache.php';

class MetricasService {
    private MetricasRepository $repo;
    private SimpleCache $cache;
    private int $ttlSeconds;

    public function __construct() {
        $this->repo = new MetricasRepository();
        $this->cache = new SimpleCache('metricas');
        $this->ttlSeconds = (int) (env('METRICS_CACHE_TTL', '300') ?: '300');
    }

    public function tiempoCiclo(?string $from, ?string $to, ?int $idDependencia): array {
        $key = $this->cacheKey('tiempo-ciclo', compact('from', 'to', 'idDependencia'));
        $rows = $this->cache->remember($key, $this->ttlSeconds, function () use ($from, $to, $idDependencia) {
            return $this->repo->tiempoCiclo($from, $to, $idDependencia);
        });

        return [
            'series' => array_map(function (array $r) {
                return [
                    'tipo_procedimiento' => $r['tipo_procedimiento'],
                    'tipo_label' => $this->humanProcedimiento((string) $r['tipo_procedimiento']),
                    'total_adjudicadas' => (int) $r['total_adjudicadas'],
                    'dias_promedio' => $r['dias_promedio'] !== null ? round((float) $r['dias_promedio'], 1) : null,
                    'dias_min' => $r['dias_min'] !== null ? (int) $r['dias_min'] : null,
                    'dias_max' => $r['dias_max'] !== null ? (int) $r['dias_max'] : null,
                    'monto_total' => round((float) ($r['monto_total'] ?? 0), 2),
                ];
            }, $rows),
            'meta' => [
                'cached_for_seconds' => $this->ttlSeconds,
                'filtros' => array_filter([
                    'from' => $from,
                    'to' => $to,
                    'id_dependencia' => $idDependencia,
                ], fn($v) => $v !== null && $v !== ''),
            ],
        ];
    }

    public function proveedoresTop(?string $from, ?string $to, int $limit): array {
        $limit = max(1, min(50, $limit));
        $key = $this->cacheKey('proveedores-top', compact('from', 'to', 'limit'));
        $rows = $this->cache->remember($key, $this->ttlSeconds, function () use ($from, $to, $limit) {
            return $this->repo->proveedoresTop($from, $to, $limit);
        });
        return [
            'items' => array_map(function (array $r) {
                return [
                    'id_proveedor' => (int) $r['id_proveedor'],
                    'nombre_empresa' => $r['nombre_empresa'],
                    'registro_fiscal' => $r['registro_fiscal'],
                    'total_contratos' => (int) $r['total_contratos'],
                    'monto_total' => round((float) $r['monto_total'], 2),
                    'monto_promedio' => round((float) $r['monto_promedio'], 2),
                    'ultima_adjudicacion' => $r['ultima_adjudicacion'],
                ];
            }, $rows),
            'total' => count($rows),
            'limit' => $limit,
        ];
    }

    public function montosMensuales(?string $from, ?string $to, ?int $idDependencia): array {
        $key = $this->cacheKey('montos-mensuales', compact('from', 'to', 'idDependencia'));
        $rows = $this->cache->remember($key, $this->ttlSeconds, function () use ($from, $to, $idDependencia) {
            return $this->repo->montosMensuales($from, $to, $idDependencia);
        });
        return [
            'series' => array_map(function (array $r) {
                $mes = (string) ($r['mes'] ?? '');
                return [
                    'mes' => $mes,
                    'mes_label' => $this->labelMes($mes),
                    'licitaciones_creadas' => (int) ($r['licitaciones_creadas'] ?? 0),
                    'contratos_adjudicados' => (int) ($r['contratos_adjudicados'] ?? 0),
                    'monto_adjudicado' => round((float) ($r['monto_adjudicado'] ?? 0), 2),
                ];
            }, $rows),
        ];
    }

    public function cumplimiento(?string $from, ?string $to, ?int $idDependencia): array {
        $key = $this->cacheKey('cumplimiento', compact('from', 'to', 'idDependencia'));
        $data = $this->cache->remember($key, $this->ttlSeconds, function () use ($from, $to, $idDependencia) {
            return $this->repo->cumplimiento($from, $to, $idDependencia);
        });
        $r = $data['resumen'] ?? [];
        $total = (int) ($r['total_evaluables'] ?? 0);
        $aTiempo = (int) ($r['a_tiempo'] ?? 0);
        $conAtraso = (int) ($r['con_atraso'] ?? 0);
        $pctATiempo = $total > 0 ? round(($aTiempo / $total) * 100, 1) : null;
        $desv = $r['dias_desviacion_promedio'] !== null ? round((float) $r['dias_desviacion_promedio'], 1) : null;

        return [
            'resumen' => [
                'total_evaluables' => $total,
                'a_tiempo' => $aTiempo,
                'con_atraso' => $conAtraso,
                'porcentaje_a_tiempo' => $pctATiempo,
                'dias_desviacion_promedio' => $desv,
            ],
            'distribucion_estado' => array_map(function (array $r) {
                return [
                    'estado_proceso' => $r['estado_proceso'],
                    'total' => (int) $r['total'],
                ];
            }, $data['distribucion_estado'] ?? []),
        ];
    }

    public function dependenciasParaFiltro(): array {
        return $this->cache->remember('dependencias-filtro', 600, function () {
            return array_map(function (array $r) {
                return [
                    'id_dependencia' => (int) $r['id_dependencia'],
                    'nombre' => $r['nombre'],
                    'total_licitaciones' => (int) $r['total_licitaciones'],
                ];
            }, $this->repo->dependenciasParaFiltro());
        });
    }

    public function flushCache(): int {
        return $this->cache->flush();
    }

    // ----- internos -----

    private function cacheKey(string $base, array $args): string {
        ksort($args);
        return $base . ':' . md5(json_encode($args));
    }

    private function humanProcedimiento(string $tipo): string {
        return match ($tipo) {
            'LICITACION_PUBLICA' => 'Licitación Pública',
            'INVITACION_RESTRINGIDA' => 'Invitación restringida',
            'ADJUDICACION_DIRECTA' => 'Adjudicación directa',
            default => $tipo,
        };
    }

    private function labelMes(string $mes): string {
        if ($mes === '') return '';
        $ts = strtotime($mes);
        if (!$ts) return $mes;
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return $meses[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }
}
