<?php
declare(strict_types=1);

/**
 * SimpleCache — cache file-based con TTL.
 *
 * Diseñado para queries pesadas de métricas. Cada entrada se guarda como
 * un archivo JSON en storage/cache/{namespace}/{hash}.json con timestamp
 * de expiración. Si el archivo expiró o no existe, devuelve null.
 *
 * No es seguro para concurrencia alta (no usa locks); para el volumen
 * actual del sistema es suficiente.
 */
class SimpleCache {
    private string $namespace;
    private string $rootDir;

    public function __construct(string $namespace = 'default') {
        $this->namespace = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $namespace) ?: 'default';
        $this->rootDir = realpath(__DIR__ . '/../../storage') ?: (__DIR__ . '/../../storage');
        $this->rootDir = rtrim($this->rootDir, '/') . '/cache/' . $this->namespace;
        if (!is_dir($this->rootDir) && !@mkdir($this->rootDir, 0755, true) && !is_dir($this->rootDir)) {
            // Ignora errores de creación concurrente; las operaciones siguientes fallarán suavemente
        }
    }

    /**
     * Obtiene un valor cacheado o ejecuta el callable y lo guarda.
     *
     * @template T
     * @param callable(): T $producer
     * @return T
     */
    public function remember(string $key, int $ttlSeconds, callable $producer): mixed {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }
        $value = $producer();
        $this->set($key, $value, $ttlSeconds);
        return $value;
    }

    public function get(string $key): mixed {
        $path = $this->path($key);
        if (!is_file($path)) return null;
        $raw = @file_get_contents($path);
        if ($raw === false) return null;
        $entry = json_decode($raw, true);
        if (!is_array($entry) || !isset($entry['expires_at'], $entry['value'])) {
            return null;
        }
        if ($entry['expires_at'] < time()) {
            @unlink($path);
            return null;
        }
        return $entry['value'];
    }

    public function set(string $key, mixed $value, int $ttlSeconds): void {
        $path = $this->path($key);
        $entry = [
            'expires_at' => time() + max(1, $ttlSeconds),
            'value' => $value,
        ];
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE);
        if ($json === false) return;
        @file_put_contents($path, $json, LOCK_EX);
    }

    public function forget(string $key): void {
        $path = $this->path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /** Borra TODA la cache del namespace (uso operativo). */
    public function flush(): int {
        $count = 0;
        if (!is_dir($this->rootDir)) return 0;
        $files = glob($this->rootDir . '/*.json') ?: [];
        foreach ($files as $f) {
            if (@unlink($f)) $count++;
        }
        return $count;
    }

    private function path(string $key): string {
        $hash = hash('sha256', $key);
        return $this->rootDir . '/' . $hash . '.json';
    }
}
