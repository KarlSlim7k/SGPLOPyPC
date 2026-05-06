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

    public function authenticate(string $email, string $password): ?array {
        $user = $this->userRepository->findByEmail($email);
        if (!$user || !$user['activo']) {
            return null;
        }

        if (!password_verify($password, $user['contrasena_hash'])) {
            return null;
        }

        // Update last access
        $this->userRepository->updateLastAccess((int) $user['id_usuario']);

        $token = $this->jwt->encode([
            'sub' => $user['id_usuario'],
            'rol' => $user['rol'],
        ]);

        return [
            'token' => $token,
            'usuario' => [
                'id_usuario' => $user['id_usuario'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol'],
            ],
        ];
    }
}
