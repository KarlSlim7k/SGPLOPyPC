<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Mailer.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../repositories/PasswordResetRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class PasswordResetService {
    private UserRepository $userRepo;
    private PasswordResetRepository $tokenRepo;
    private Mailer $mailer;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->tokenRepo = new PasswordResetRepository();
        $this->mailer = new Mailer();
    }

    public function forgot(string $email, ?string $requestIp): array {
        $email = strtolower(trim($email));
        $genericMessage = 'Si el correo existe, recibirás instrucciones para restablecer tu contraseña.';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => true, 'message' => $genericMessage];
        }

        $user = $this->userRepo->findByEmail($email);
        if (!$user || !(bool) $user['activo']) {
            return ['ok' => true, 'message' => $genericMessage];
        }

        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', time() + ((int) env('PASSWORD_RESET_TTL_SECONDS', '3600')));
        $this->tokenRepo->create($email, hash('sha256', $token), $expiresAt, $requestIp);

        $baseUrl = rtrim(env('APP_BASE_URL', ''), '/');
        $resetUrl = $baseUrl !== ''
            ? $baseUrl . '/frontend/auth/login.html#reset=' . urlencode($token)
            : 'Token de recuperación: ' . $token;

        $body = "Se solicitó restablecer tu contraseña en SGPLOPyPC.\n\n" .
            "Usa este enlace o token:\n{$resetUrl}\n\n" .
            "Vigencia: 60 minutos.";
        $this->mailer->send($email, 'Restablecer contraseña - SGPLOPyPC', $body);

        return ['ok' => true, 'message' => $genericMessage];
    }

    public function reset(string $token, string $newPassword): array {
        if (trim($token) === '') {
            return ['ok' => false, 'errors' => ['El token es obligatorio.']];
        }

        if (!$this->isStrongPassword($newPassword)) {
            return ['ok' => false, 'errors' => ['La nueva contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.']];
        }

        $tokenRow = $this->tokenRepo->findActiveByRawToken($token);
        if (!$tokenRow) {
            return ['ok' => false, 'errors' => ['Token inválido o expirado.']];
        }

        $user = $this->userRepo->findByEmail((string) $tokenRow['email']);
        if (!$user) {
            return ['ok' => false, 'errors' => ['Token inválido o expirado.']];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->userRepo->updatePassword((int) $user['id_usuario'], $hash);
        $this->tokenRepo->markUsed((int) $tokenRow['id_password_reset_token']);

        auditLog((int) $user['id_usuario'], 'usuario', (int) $user['id_usuario'], 'PASSWORD_RESET', null, ['contrasena_reseteada' => true]);

        return ['ok' => true];
    }

    private function isStrongPassword(string $password): bool {
        return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }
}
