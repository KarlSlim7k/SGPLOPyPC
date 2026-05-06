<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

class RoleMiddleware {
    public static function handle(string $requiredRole): void {
        $user = AuthMiddleware::getAuthenticatedUser();
        if ($user['rol'] !== $requiredRole) {
            jsonResponse(false, 'No tienes permisos para acceder a este recurso.', null, null, 403);
        }
    }
}
