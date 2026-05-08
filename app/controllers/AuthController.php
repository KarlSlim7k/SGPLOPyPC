<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/PasswordResetService.php';

class AuthController {
    private AuthService $authService;
    private PasswordResetService $passwordResetService;

    public function __construct() {
        $this->authService = new AuthService();
        $this->passwordResetService = new PasswordResetService();
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

    public function forgotPassword(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $email = (string) ($input['email'] ?? '');
        $requestIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $result = $this->passwordResetService->forgot($email, $requestIp);

        jsonResponse(true, $result['message'], null, null, 200);
    }

    public function resetPassword(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $token = (string) ($input['token'] ?? '');
        $password = (string) ($input['password'] ?? '');

        $result = $this->passwordResetService->reset($token, $password);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudo restablecer la contraseña', null, $result['errors'], 422);
        }

        jsonResponse(true, 'Contraseña restablecida exitosamente', null, null, 200);
    }
}
