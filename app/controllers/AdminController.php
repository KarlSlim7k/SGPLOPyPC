<?php
declare(strict_types=1);

class AdminController {
    public function dashboard(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        jsonResponse(true, 'Panel de administrador', [
            'mensaje' => 'Bienvenido al área de administración',
            'admin' => [
                'id' => $user['id_usuario'],
                'nombre' => $user['nombre'],
            ],
        ], null, 200);
    }
}
