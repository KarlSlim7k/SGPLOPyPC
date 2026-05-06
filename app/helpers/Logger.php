<?php
declare(strict_types=1);

class Logger {
    private string $logDir;

    public function __construct() {
        $this->logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0750, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void {
        $env = env('APP_ENV', 'production');
        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'env' => $env,
        ];
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
        $file = $this->logDir . '/' . date('Y-m-d') . '.jsonl';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }

    public function security(string $message, array $context = []): void {
        $this->log('SECURITY', $message, $context);
    }
}
