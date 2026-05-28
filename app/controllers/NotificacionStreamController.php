<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/NotificacionRepository.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

/**
 * NotificacionStreamController — endpoint SSE para notificaciones en tiempo real.
 *
 * Implementación: SSE con long-polling interno.
 * - El cliente conecta con EventSource a GET /notificaciones/stream?since=<timestamp>
 * - El servidor hace polling a la BD cada 2s durante máx 25s
 * - Si hay notificaciones nuevas, las emite y cierra la conexión
 * - Si no hay datos en 25s, emite un heartbeat y cierra (el cliente reconecta)
 * - El cliente JS actualiza `since` con el timestamp del último evento recibido
 *
 * Compatible con Apache/PHP sin configuración especial de streaming.
 * El fallback a polling simple (sin SSE) se maneja en notif-stream.js.
 */
class NotificacionStreamController {
    private NotificacionRepository $repo;

    private const POLL_INTERVAL_S = 2;   // segundos entre polls a la BD
    private const MAX_WAIT_S = 25;        // tiempo máximo de espera antes de heartbeat
    private const MAX_EVENTS = 20;        // máximo de eventos por respuesta

    public function __construct() {
        $this->repo = new NotificacionRepository();
    }

    /**
     * GET /api/v1/notificaciones/stream
     * Query params:
     *   - since: timestamp ISO 8601 o Unix (default: ahora - 60s)
     *   - token: JWT (alternativa al header Authorization para EventSource)
     */
    public function stream(): never {
        // EventSource no puede enviar headers personalizados, así que aceptamos
        // el token como query param además del header Authorization.
        if (!empty($_GET['token'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_GET['token'];
        }
        $user = AuthMiddleware::getAuthenticatedUser();
        $idUsuario = (int) $user['id_usuario'];

        // Parsear `since`
        $sinceRaw = $_GET['since'] ?? '';
        $since = $this->parseSince($sinceRaw);

        // Cabeceras SSE
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
        header('X-Accel-Buffering: no'); // Nginx: deshabilitar buffering
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');

        // Deshabilitar output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', 'off');

        $startTime = time();
        $emitted = false;

        while (true) {
            // Verificar si el cliente desconectó
            if (connection_aborted()) {
                exit;
            }

            // Consultar notificaciones nuevas
            $notifs = $this->repo->findRecientes($idUsuario, $since, self::MAX_EVENTS);

            if (!empty($notifs)) {
                foreach ($notifs as $n) {
                    $this->emitEvent('notificacion', [
                        'id_notificacion' => (int) $n['id_notificacion'],
                        'tipo' => $n['tipo_notificacion'],
                        'titulo' => $n['titulo'],
                        'mensaje' => $n['mensaje'],
                        'leida' => (bool) $n['leida'],
                        'fecha_envio' => $n['fecha_envio'],
                        'id_licitacion' => $n['id_licitacion'] ? (int) $n['id_licitacion'] : null,
                    ]);
                }
                // Actualizar since al más reciente
                $since = $notifs[0]['fecha_envio'];
                $emitted = true;
            }

            // Emitir badge count siempre que haya datos o en el primer ciclo
            if ($emitted || (time() - $startTime) < 1) {
                $count = $this->repo->findNoLeidasCount($idUsuario);
                $this->emitEvent('badge', ['count' => $count]);
            }

            if ($emitted) {
                // Cerrar conexión; el cliente reconectará con el nuevo `since`
                $this->emitEvent('sync', ['since' => $since]);
                exit;
            }

            // Heartbeat si se agotó el tiempo
            if ((time() - $startTime) >= self::MAX_WAIT_S) {
                $this->emitEvent('heartbeat', ['ts' => time()]);
                exit;
            }

            // Esperar antes del siguiente poll
            sleep(self::POLL_INTERVAL_S);
        }
    }

    /**
     * GET /api/v1/notificaciones/count
     * Devuelve el conteo de no leídas (para polling simple sin SSE).
     */
    public function count(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $count = $this->repo->findNoLeidasCount((int) $user['id_usuario']);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache');
        echo json_encode(['success' => true, 'data' => ['count' => $count]]);
        exit;
    }

    private function emitEvent(string $event, array $data): void {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    private function parseSince(string $raw): string {
        if ($raw === '') {
            // Default: últimos 60 segundos
            return date('Y-m-d H:i:s', time() - 60);
        }
        // Intentar como Unix timestamp
        if (ctype_digit($raw)) {
            return date('Y-m-d H:i:s', (int) $raw);
        }
        // Intentar como ISO 8601 o datetime
        $ts = strtotime($raw);
        if ($ts !== false) {
            return date('Y-m-d H:i:s', $ts);
        }
        return date('Y-m-d H:i:s', time() - 60);
    }
}
