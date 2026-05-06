<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthMiddleware {
    private static ?array $user = null;

    public static function handle(): void {
        $authHeader = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
        }
        if ($authHeader === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if ($authHeader === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            jsonResponse(false, 'No autenticado. Se requiere token Bearer.', null, null, 401);
        }

        $token = substr($authHeader, 7);
        $jwt = new JwtHelper();
        $payload = $jwt->decode($token);

        if (!$payload || !isset($payload['sub'])) {
            jsonResponse(false, 'Token inválido o expirado.', null, null, 401);
        }

        $repo = new UserRepository();
        $user = $repo->findById((int) $payload['sub']);

        if (!$user || !(bool) $user['activo']) {
            jsonResponse(false, 'Usuario no encontrado o inactivo.', null, null, 401);
        }

        self::$user = $user;
    }

    public static function getAuthenticatedUser(): array {
        if (self::$user === null) {
            self::handle();
        }
        return self::$user;
    }
}
