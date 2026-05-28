<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/PasswordResetService.php';
require_once __DIR__ . '/../services/MfaService.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class AuthController {
    private AuthService $authService;
    private PasswordResetService $passwordResetService;
    private MfaService $mfaService;

    public function __construct() {
        $this->authService = new AuthService();
        $this->passwordResetService = new PasswordResetService();
        $this->mfaService = new MfaService();
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
            auditLog(
                $result['attempted_user_id'],
                'usuario',
                $result['attempted_user_id'] ?? 0,
                'LOGIN_FALLIDO',
                null,
                ['email_intentado' => $email, 'razon' => $result['reason']]
            );
            jsonResponse(false, 'Credenciales inválidas', null, null, 401);
        }

        // MFA requerido: devolver mfa_token en lugar del JWT de sesión
        if (!empty($result['requires_mfa'])) {
            jsonResponse(true, 'Se requiere verificación MFA', [
                'requires_mfa' => true,
                'mfa_token' => $result['mfa_token'],
            ], null, 200);
        }

        auditLog(
            $result['user']['id_usuario'],
            'usuario',
            $result['user']['id_usuario'],
            'LOGIN_OK',
            null,
            ['rol' => $result['user']['rol'], 'email' => $result['user']['email']]
        );

        jsonResponse(true, 'Inicio de sesión exitoso', [
            'token' => $result['token'],
            'usuario' => $result['user'],
        ], null, 200);
    }

    /**
     * POST /api/v1/auth/login/mfa
     * Body: { mfa_token: string, code: string }
     * Completa el login cuando MFA está activo.
     */
    public function loginMfa(): never {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            jsonResponse(false, 'Cuerpo de solicitud inválido', null, null, 400);
        }

        $mfaToken = trim((string) ($input['mfa_token'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));

        if ($mfaToken === '' || $code === '') {
            jsonResponse(false, 'Se requieren mfa_token y code', null, null, 400);
        }

        $result = $this->authService->completeMfaLogin($mfaToken, $code);
        if (!$result['ok']) {
            jsonResponse(false, 'Verificación MFA fallida', null, $result['errors'], 401);
        }

        auditLog(
            $result['user']['id_usuario'],
            'usuario',
            $result['user']['id_usuario'],
            'LOGIN_OK',
            null,
            ['rol' => $result['user']['rol'], 'via' => 'mfa']
        );

        jsonResponse(true, 'Inicio de sesión exitoso', [
            'token' => $result['token'],
            'usuario' => $result['user'],
        ], null, 200);
    }

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

    public function mfaEnroll(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->mfaService->enroll((int) $user['id_usuario']);
        if (!$result['ok']) {
            jsonResponse(false, 'Error al iniciar enrolamiento MFA', null, $result['errors'], 409);
        }
        jsonResponse(true, 'Enrolamiento MFA iniciado', [
            'secret' => $result['secret'],
            'otpauth_url' => $result['otpauth_url'],
            'qr_url' => $result['qr_url'],
        ], null, 200);
    }

    public function mfaConfirm(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        $code = trim((string) ($input['code'] ?? ''));
        if ($code === '') {
            jsonResponse(false, 'El campo code es obligatorio', null, null, 400);
        }
        $result = $this->mfaService->confirm((int) $user['id_usuario'], $code);
        if (!$result['ok']) {
            jsonResponse(false, 'Confirmación MFA fallida', null, $result['errors'], 422);
        }
        jsonResponse(true, 'MFA activado correctamente', [
            'backup_codes' => $result['backup_codes'],
            'aviso' => 'Guarda estos códigos en un lugar seguro. No se mostrarán de nuevo.',
        ], null, 200);
    }

    public function mfaDisable(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        $password = (string) ($input['password'] ?? '');
        $code = trim((string) ($input['code'] ?? ''));
        if ($password === '' || $code === '') {
            jsonResponse(false, 'Se requieren password y code', null, null, 400);
        }
        $result = $this->mfaService->disable((int) $user['id_usuario'], $password, $code);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudo desactivar MFA', null, $result['errors'], 422);
        }
        jsonResponse(true, 'MFA desactivado', null, null, 200);
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
