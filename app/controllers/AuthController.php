<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';

class AuthController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function login(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $errors = Validator::validateInput([
            'email' => 'required|email|max:200',
            'password' => 'required|max:255',
        ], $input);

        if (!empty($errors)) {
            jsonResponse(false, 'Validación fallida', null, $errors, 400);
        }

        $email = trim($input['email']);
        $password = $input['password'];

        $result = $this->authService->authenticate($email, $password);

        if ($result === null) {
            jsonResponse(false, 'Credenciales inválidas', null, null, 401);
        }

        jsonResponse(true, 'Inicio de sesión exitoso', $result, null, 200);
    }
}
