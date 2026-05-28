<?php
declare(strict_types=1);

/**
 * RequestIdMiddleware
 *
 * Garantiza un identificador único por petición HTTP (X-Request-ID) para correlacionar
 * eventos en logs y auditoría. Si el cliente envía X-Request-ID válido, se reutiliza;
 * si no, se genera uno nuevo.
 *
 * Uso:
 *   RequestIdMiddleware::handle();  // al inicio del request
 *   RequestIdMiddleware::get();     // recupera el ID actual desde cualquier punto
 */
class RequestIdMiddleware {
    private static ?string $requestId = null;

    public static function handle(): void {
        $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        $incoming = is_string($incoming) ? trim($incoming) : '';

        // Aceptar solo formatos seguros (alfanumérico, guiones, hasta 40 chars)
        if ($incoming !== '' && preg_match('/^[a-zA-Z0-9\-]{8,40}$/', $incoming) === 1) {
            self::$requestId = $incoming;
        } else {
            self::$requestId = self::generate();
        }

        // Exponer en respuesta
        if (!headers_sent()) {
            header('X-Request-ID: ' . self::$requestId);
        }
    }

    public static function get(): string {
        if (self::$requestId === null) {
            self::handle();
        }
        return self::$requestId ?? self::generate();
    }

    private static function generate(): string {
        try {
            // UUID v4 simple (sin dependencia externa)
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } catch (Throwable $e) {
            // Fallback determinístico
            return bin2hex(uniqid('', true));
        }
    }
}
