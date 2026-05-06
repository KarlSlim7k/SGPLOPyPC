<?php
declare(strict_types=1);

class HealthController {
    public function index(): never {
        jsonResponse(true, 'API activa', [
            'status' => 'ok',
            'timestamp' => date('c'),
        ], null, 200);
    }
}
