<?php
declare(strict_types=1);

class HealthController {
    public function index(): never {
        $checks = [
            'app' => ['status' => 'ok', 'timestamp' => date('c')],
            'db' => $this->checkDb(),
        ];
        $allOk = $checks['db']['status'] === 'ok';
        $httpCode = $allOk ? 200 : 503;
        jsonResponse($allOk, $allOk ? 'Servicio saludable' : 'Servicio degradado', $checks, null, $httpCode);
    }

    private function checkDb(): array {
        try {
            $db = getDbConnection();
            $stmt = $db->query('SELECT 1');
            $stmt->fetch();
            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'No se pudo conectar a la base de datos'];
        }
    }
}
