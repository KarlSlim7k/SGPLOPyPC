<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../helpers/jwt.php';

class AuthService {
    private UserRepository $userRepository;
    private JwtHelper $jwt;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->jwt = new JwtHelper();
    }

    /**
     * Autentica un usuario por email + password.
     *
     * Retorna un arreglo con:
     *   - 'ok'                => bool
     *   - 'reason'            => 'USER_NOT_FOUND' | 'USER_INACTIVE' | 'BAD_PASSWORD' (solo si ok=false)
     *   - 'requires_mfa'      => bool (true si el usuario tiene MFA activo)
     *   - 'mfa_token'         => string JWT de corta duración para el challenge MFA (solo si requires_mfa=true)
     *   - 'user'              => array del usuario (solo si ok=true y requires_mfa=false)
     *   - 'token'             => JWT de sesión completo (solo si ok=true y requires_mfa=false)
     *   - 'attempted_user_id' => int|null (id si el usuario existe aunque haya fallado)
     */
    public function authenticate(string $email, string $password): array {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return [
                'ok' => false,
                'reason' => 'USER_NOT_FOUND',
                'attempted_user_id' => null,
            ];
        }

        if (!$user['activo']) {
            return [
                'ok' => false,
                'reason' => 'USER_INACTIVE',
                'attempted_user_id' => (int) $user['id_usuario'],
            ];
        }

        if (!password_verify($password, $user['contrasena_hash'])) {
            return [
                'ok' => false,
                'reason' => 'BAD_PASSWORD',
                'attempted_user_id' => (int) $user['id_usuario'],
            ];
        }

        // Si MFA está activo, emitir un token de challenge de corta duración (5 min)
        // en lugar del JWT de sesión completo.
        if (!empty($user['mfa_enabled']) && (int) $user['mfa_enabled'] === 1) {
            $mfaToken = $this->jwt->encodeWithTtl([
                'sub' => $user['id_usuario'],
                'mfa_challenge' => true,
            ], 300); // 5 minutos

            return [
                'ok' => true,
                'requires_mfa' => true,
                'mfa_token' => $mfaToken,
            ];
        }

        // Sin MFA: flujo normal
        $this->userRepository->updateLastAccess((int) $user['id_usuario']);

        $token = $this->jwt->encode([
            'sub' => $user['id_usuario'],
            'rol' => $user['rol'],
        ]);

        return [
            'ok' => true,
            'requires_mfa' => false,
            'token' => $token,
            'user' => [
                'id_usuario' => (int) $user['id_usuario'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol'],
            ],
        ];
    }

    /**
     * Completa el login MFA: verifica el mfa_token + código TOTP y emite el JWT de sesión.
     *
     * @return array{ok: bool, token?: string, user?: array, errors?: array}
     */
    public function completeMfaLogin(string $mfaToken, string $code): array {
        $payload = $this->jwt->decode($mfaToken);
        if (!$payload || empty($payload['mfa_challenge']) || empty($payload['sub'])) {
            return ['ok' => false, 'errors' => ['Token MFA inválido o expirado.']];
        }

        $idUsuario = (int) $payload['sub'];

        // Verificar código TOTP (importar MfaService aquí para evitar dependencia circular)
        require_once __DIR__ . '/MfaService.php';
        $mfaService = new MfaService();
        $result = $mfaService->verifyLogin($idUsuario, $code);
        if (!$result['ok']) {
            return ['ok' => false, 'errors' => $result['errors']];
        }

        $user = $this->userRepository->findById($idUsuario);
        if (!$user) {
            return ['ok' => false, 'errors' => ['Usuario no encontrado.']];
        }

        $this->userRepository->updateLastAccess($idUsuario);

        $token = $this->jwt->encode([
            'sub' => $user['id_usuario'],
            'rol' => $user['rol'],
        ]);

        return [
            'ok' => true,
            'token' => $token,
            'user' => [
                'id_usuario' => (int) $user['id_usuario'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol'],
            ],
        ];
    }
}
