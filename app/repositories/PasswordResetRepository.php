<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class PasswordResetRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function create(string $email, string $tokenHash, string $expiresAt, ?string $requestIp): int {
        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_token (email, token_hash, expires_at, used_at, request_ip, created_at) '
            . 'VALUES (:email, :token_hash, :expires_at, NULL, :request_ip, NOW())'
        );
        $stmt->execute([
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'request_ip' => $requestIp,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findActiveByRawToken(string $rawToken): ?array {
        $hash = hash('sha256', $rawToken);
        $stmt = $this->db->prepare(
            'SELECT * FROM password_reset_token '
            . 'WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at >= NOW() '
            . 'ORDER BY id_password_reset_token DESC LIMIT 1'
        );
        $stmt->execute(['token_hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markUsed(int $idToken): void {
        $stmt = $this->db->prepare('UPDATE password_reset_token SET used_at = NOW() WHERE id_password_reset_token = :id');
        $stmt->execute(['id' => $idToken]);
    }
}
