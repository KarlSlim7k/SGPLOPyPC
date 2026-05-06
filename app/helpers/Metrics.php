<?php
declare(strict_types=1);

class Metrics {
    private float $startTime;
    private Logger $logger;

    public function __construct() {
        $this->startTime = microtime(true);
        $this->logger = new Logger();
    }

    public function record(string $route, int $statusCode, ?string $error = null): void {
        $latencyMs = round((microtime(true) - $this->startTime) * 1000, 2);
        $context = [
            'route' => $route,
            'status_code' => $statusCode,
            'latency_ms' => $latencyMs,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ];
        if ($error !== null) {
            $context['error'] = $error;
        }

        $level = ($statusCode >= 500) ? 'ERROR' : (($statusCode >= 400) ? 'WARNING' : 'INFO');
        $this->logger->log($level, 'Request metrics', $context);

        // Alerta simple: latencia alta o errores 5xx frecuentes se loggean como WARNING/ERROR
        if ($latencyMs > 5000) {
            $this->logger->warning('Latencia alta detectada', $context);
        }
    }
}
