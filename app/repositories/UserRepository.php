<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class UserRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare('SELECT * FROM usuario WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT id_usuario, nombre, email, rol, activo, fecha_registro, ultimo_acceso FROM usuario WHERE id_usuario = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAuthById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT id_usuario, nombre, email, contrasena_hash, rol, activo FROM usuario WHERE id_usuario = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateLastAccess(int $id): void {
        $stmt = $this->db->prepare('UPDATE usuario SET ultimo_acceso = NOW() WHERE id_usuario = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateProfile(int $id, array $data): void {
        $stmt = $this->db->prepare(
            'UPDATE usuario SET nombre = :nombre, email = :email WHERE id_usuario = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
        ]);
    }

    public function updatePassword(int $id, string $hash): void {
        $stmt = $this->db->prepare('UPDATE usuario SET contrasena_hash = :hash WHERE id_usuario = :id');
        $stmt->execute([
            'id' => $id,
            'hash' => $hash,
        ]);
    }

    public function findMfaById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, email, rol, mfa_secret, mfa_enabled, mfa_backup_codes
             FROM usuario WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateMfa(int $id, ?string $secret, bool $enabled, ?string $backupCodesJson): void {
        $stmt = $this->db->prepare(
            'UPDATE usuario SET mfa_secret = :secret, mfa_enabled = :enabled, mfa_backup_codes = :codes
             WHERE id_usuario = :id'
        );
        $stmt->execute([
            'id' => $id,
            'secret' => $secret,
            'enabled' => $enabled ? 1 : 0,
            'codes' => $backupCodesJson,
        ]);
    }
}
