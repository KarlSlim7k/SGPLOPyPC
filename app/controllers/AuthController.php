<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/PasswordResetService.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

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

        if (!$result['ok']) {
            // Auditar login fallido sin exponer la razón al cliente (seguridad)
            auditLog(
                $result['attempted_user_id'],
                'usuario',
                $result['attempted_user_id'] ?? 0,
                'LOGIN_FALLIDO',
                null,
                [
                    'email_intentado' => $email,
                    'razon' => $result['reason'],
                ]
            );
            jsonResponse(false, 'Credenciales inválidas', null, null, 401);
        }

        // Auditar login exitoso
        auditLog(
            $result['user']['id_usuario'],
            'usuario',
            $result['user']['id_usuario'],
            'LOGIN_OK',
            null,
            [
                'rol' => $result['user']['rol'],
                'email' => $result['user']['email'],
            ]
        );

        jsonResponse(true, 'Inicio de sesión exitoso', [
            'token' => $result['token'],
            'usuario' => $result['user'],
        ], null, 200);
    }

    /**
     * Logout. Como los JWT son stateless, no invalidamos en backend (a menos que se implemente
     * blacklist). Se registra el evento para fines de auditoría/trazabilidad.
     */
    public function logout(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        auditLog(
            (int) $user['id_usuario'],
            'usuario',
            (int) $user['id_usuario'],
            'LOGOUT',
            null,
            ['email' => $user['email']]
        );
        jsonResponse(true, 'Sesión cerrada', null, null, 200);
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
