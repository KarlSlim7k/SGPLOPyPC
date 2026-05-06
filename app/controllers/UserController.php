<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

class UserController {
    public function me(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        jsonResponse(true, 'Usuario autenticado', [
            'id_usuario' => $user['id_usuario'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol'],
        ], null, 200);
    }
}
