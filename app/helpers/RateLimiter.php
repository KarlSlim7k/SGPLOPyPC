<?php
declare(strict_types=1);

class RateLimiter {
    private string $storageDir;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 10, int $windowSeconds = 60) {
        $this->storageDir = __DIR__ . '/../../storage/ratelimit';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0750, true);
        }
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function isAllowed(string $key): bool {
        $now = time();
        $file = $this->storageDir . '/' . $this->sanitizeKey($key) . '.json';
        $tmpFile = $file . '.tmp.' . uniqid();

        $entries = [];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $entries = @json_decode($raw, true) ?: [];
            }
        }

        // Filtrar entradas fuera de la ventana
        $entries = array_filter($entries, fn(int $t) => ($now - $t) < $this->windowSeconds);

        if (count($entries) >= $this->maxAttempts) {
            return false;
        }

        $entries[] = $now;
        @file_put_contents($tmpFile, json_encode(array_values($entries)));
        @rename($tmpFile, $file);
        return true;
    }

    private function sanitizeKey(string $key): string {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }

    public function getRetryAfter(string $key): int {
        $file = $this->storageDir . '/' . $this->sanitizeKey($key) . '.json';
        if (!file_exists($file)) return 0;
        $raw = @file_get_contents($file);
        if ($raw === false) return 0;
        $entries = @json_decode($raw, true) ?: [];
        $now = time();
        $entries = array_filter($entries, fn(int $t) => ($now - $t) < $this->windowSeconds);
        if (empty($entries)) return 0;
        $oldest = min($entries);
        return max(0, $this->windowSeconds - ($now - $oldest));
    }
}
