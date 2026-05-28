<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class UserService {
    private UserRepository $repo;
    private ProveedorRepository $proveedorRepo;

    public function __construct() {
        $this->repo = new UserRepository();
        $this->proveedorRepo = new ProveedorRepository();
    }

    public function getMe(int $idUsuario): ?array {
        $user = $this->repo->findById($idUsuario);
        if (!$user) {
            return null;
        }

        if (($user['rol'] ?? '') === 'PROVEEDOR') {
            $proveedor = $this->proveedorRepo->findByUsuario($idUsuario);
            $user['proveedor'] = $proveedor;
            $user['id_proveedor'] = $proveedor ? (int) $proveedor['id_proveedor'] : null;
        }

        return $user;
    }

    public function updateProfile(int $idUsuario, array $input): array {
        $nombre = trim((string) ($input['nombre'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $errors = [];

        if ($nombre === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico es inválido.';
        }

        $existing = $this->repo->findById($idUsuario);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Usuario no encontrado.']];
        }

        $dup = $this->repo->findByEmail($email);
        if ($dup && (int) $dup['id_usuario'] !== $idUsuario) {
            $errors[] = 'El correo electrónico ya está en uso.';
        }

        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $changes = [
            'nombre' => $nombre,
            'email' => $email,
        ];

        $this->repo->updateProfile($idUsuario, $changes);
        auditLog($idUsuario, 'usuario', $idUsuario, 'ACTUALIZAR', [
            'nombre' => $existing['nombre'],
            'email' => $existing['email'],
        ], $changes);

        return ['ok' => true, 'user' => $this->repo->findById($idUsuario)];
    }

    public function changePassword(int $idUsuario, array $input): array {
        $actual = (string) ($input['contrasena_actual'] ?? '');
        $nueva = (string) ($input['contrasena_nueva'] ?? '');
        $confirmacion = (string) ($input['contrasena_confirmacion'] ?? '');
        $errors = [];

        if ($actual === '' || $nueva === '' || $confirmacion === '') {
            $errors[] = 'Todos los campos de contraseña son obligatorios.';
        }
        if ($nueva !== $confirmacion) {
            $errors[] = 'La confirmación de la contraseña no coincide.';
        }
        if (!$this->isStrongPassword($nueva)) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.';
        }

        $user = $this->repo->findAuthById($idUsuario);
        if (!$user) {
            return ['ok' => false, 'errors' => ['Usuario no encontrado.']];
        }
        if (!password_verify($actual, $user['contrasena_hash'])) {
            $errors[] = 'La contraseña actual es incorrecta.';
        }

        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $this->repo->updatePassword($idUsuario, $hash);
        auditLog($idUsuario, 'usuario', $idUsuario, 'PASSWORD_CHANGE', null, ['contrasena_actualizada' => true]);

        return ['ok' => true];
    }

    private function isStrongPassword(string $password): bool {
        return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }
}
