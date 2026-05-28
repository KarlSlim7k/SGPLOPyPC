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
     *   - 'ok'         => bool
     *   - 'reason'     => 'USER_NOT_FOUND' | 'USER_INACTIVE' | 'BAD_PASSWORD' (solo si ok=false)
     *   - 'user'       => array del usuario (solo si ok=true)
     *   - 'token'      => JWT (solo si ok=true)
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

        // Update last access
        $this->userRepository->updateLastAccess((int) $user['id_usuario']);

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
