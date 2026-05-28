<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/AuditoriaRepository.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class AuditoriaController {
    private AuditoriaRepository $repo;

    private const ACCIONES_VALIDAS = [
        'CREAR', 'ACTUALIZAR', 'ELIMINAR', 'FIRMAR',
        'LOGIN_OK', 'LOGIN_FALLIDO', 'LOGOUT',
        'PASSWORD_CHANGE', 'PASSWORD_RESET',
        'EXPORT', 'CONSULTA',
    ];

    public function __construct() {
        $this->repo = new AuditoriaRepository();
    }

    public function list(): never {
        $filters = $this->extractFilters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(200, max(1, (int) ($_GET['limit'] ?? 50)));

        $result = $this->repo->findPaginated($filters, $page, $limit);
        $distinct = $this->repo->distinctValues();

        $rows = array_map([$this, 'transformRow'], $result['rows']);

        jsonResponse(true, 'Bitácora de auditoría', [
            'items' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit),
            ],
            'filtros_aplicados' => $filters,
            'opciones' => $distinct,
        ], null, 200);
    }

    public function exportCsv(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $filters = $this->extractFilters();
        $rows = $this->repo->findForExport($filters);

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            jsonResponse(false, 'No se pudo crear el buffer de exportación.', null, null, 500);
        }

        // BOM UTF-8
        fprintf($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'ID', 'Fecha', 'Usuario ID', 'Usuario', 'Email', 'Rol',
            'Tabla', 'ID Registro', 'Acción', 'IP', 'User-Agent', 'Request-ID',
        ]);

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['id_historial'],
                $r['fecha_accion'],
                $r['id_usuario'] ?? '',
                $r['usuario_nombre'] ?? '',
                $r['usuario_email'] ?? '',
                $r['usuario_rol'] ?? '',
                $r['tabla_afectada'],
                $r['id_registro_afectado'],
                $r['accion'],
                $r['ip_origen'] ?? '',
                $r['user_agent'] ?? '',
                $r['request_id'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // Auditar la propia exportación de auditoría
        auditLog((int) $user['id_usuario'], 'auditoria', 0, 'EXPORT', null, [
            'filtros' => $filters,
            'registros' => count($rows),
        ]);

        $filename = 'auditoria_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit;
    }

    private function extractFilters(): array {
        $filters = [];

        if (isset($_GET['id_usuario']) && (int) $_GET['id_usuario'] > 0) {
            $filters['id_usuario'] = (int) $_GET['id_usuario'];
        }

        $accion = $_GET['accion'] ?? '';
        if (is_string($accion) && in_array($accion, self::ACCIONES_VALIDAS, true)) {
            $filters['accion'] = $accion;
        }

        $tabla = $_GET['tabla'] ?? '';
        if (is_string($tabla) && $tabla !== '' && preg_match('/^[a-zA-Z_]{1,50}$/', $tabla) === 1) {
            $filters['tabla'] = $tabla;
        }

        $requestId = $_GET['request_id'] ?? '';
        if (is_string($requestId) && $requestId !== '' && preg_match('/^[a-zA-Z0-9\-]{8,40}$/', $requestId) === 1) {
            $filters['request_id'] = $requestId;
        }

        $from = $_GET['from'] ?? '';
        if (is_string($from) && $this->isValidDate($from)) {
            $filters['from'] = $from;
        }

        $to = $_GET['to'] ?? '';
        if (is_string($to) && $this->isValidDate($to)) {
            $filters['to'] = $to;
        }

        return $filters;
    }

    private function isValidDate(string $date): bool {
        if ($date === '') return false;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function transformRow(array $row): array {
        return [
            'id_historial' => (int) $row['id_historial'],
            'fecha_accion' => $row['fecha_accion'],
            'usuario' => $row['id_usuario'] !== null ? [
                'id_usuario' => (int) $row['id_usuario'],
                'nombre' => $row['usuario_nombre'],
                'email' => $row['usuario_email'],
                'rol' => $row['usuario_rol'],
            ] : null,
            'tabla_afectada' => $row['tabla_afectada'],
            'id_registro_afectado' => (int) $row['id_registro_afectado'],
            'accion' => $row['accion'],
            'ip_origen' => $row['ip_origen'],
            'user_agent' => $row['user_agent'],
            'request_id' => $row['request_id'],
            'valores_anteriores' => $this->safeJsonDecode($row['valores_anteriores'] ?? null),
            'valores_nuevos' => $this->safeJsonDecode($row['valores_nuevos'] ?? null),
        ];
    }

    private function safeJsonDecode(?string $json): mixed {
        if ($json === null || $json === '') return null;
        $decoded = json_decode($json, true);
        return $decoded !== null ? $decoded : $json;
    }
}
