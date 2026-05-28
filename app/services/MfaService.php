<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../helpers/TotpHelper.php';
require_once __DIR__ . '/../helpers/audit.php';

/**
 * MfaService — gestiona el ciclo de vida del MFA TOTP para un usuario.
 *
 * Flujo de enrolamiento:
 *   1. enroll()   → genera secreto temporal, devuelve otpauth URL + QR URL
 *   2. confirm()  → verifica código TOTP, activa MFA, devuelve backup codes
 *
 * Flujo de login con MFA activo:
 *   - AuthService detecta mfa_enabled=1 y devuelve mfa_token (JWT corto)
 *   - El cliente llama a verify() con el mfa_token + código TOTP
 *   - Si OK, se emite el JWT de sesión completo
 */
class MfaService {
    private UserRepository $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    /**
     * Inicia el enrolamiento: genera un secreto y lo guarda temporalmente
     * (mfa_enabled sigue en 0 hasta que confirm() lo active).
     *
     * @return array{ok: bool, secret?: string, otpauth_url?: string, qr_url?: string, errors?: array}
     */
    public function enroll(int $idUsuario): array {
        $user = $this->repo->findMfaById($idUsuario);
        if (!$user) {
            return ['ok' => false, 'errors' => ['Usuario no encontrado.']];
        }
        if ((int) $user['mfa_enabled'] === 1) {
            return ['ok' => false, 'errors' => ['MFA ya está activo. Desactívalo primero para re-enrolar.']];
        }

        $secret = TotpHelper::generateSecret();
        // Guardar secreto temporal (mfa_enabled=0 hasta confirm)
        $this->repo->updateMfa($idUsuario, $secret, false, null);

        $otpauthUrl = TotpHelper::otpauthUrl($secret, (string) $user['email']);
        // URL de QR via Google Charts API (no envía datos sensibles, sólo la URL codificada)
        $qrUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . rawurlencode($otpauthUrl);

        auditLog($idUsuario, 'usuario', $idUsuario, 'ACTUALIZAR', null, ['mfa_enroll_iniciado' => true]);

        return [
            'ok' => true,
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
            'qr_url' => $qrUrl,
        ];
    }

    /**
     * Confirma el enrolamiento verificando el primer código TOTP.
     * Si es válido, activa MFA y genera los backup codes.
     *
     * @return array{ok: bool, backup_codes?: string[], errors?: array}
     */
    public function confirm(int $idUsuario, string $code): array {
        $user = $this->repo->findMfaById($idUsuario);
        if (!$user || empty($user['mfa_secret'])) {
            return ['ok' => false, 'errors' => ['Inicia el enrolamiento primero.']];
        }
        if ((int) $user['mfa_enabled'] === 1) {
            return ['ok' => false, 'errors' => ['MFA ya está activo.']];
        }

        if (!TotpHelper::verify((string) $user['mfa_secret'], $code)) {
            return ['ok' => false, 'errors' => ['Código incorrecto. Verifica la hora de tu dispositivo.']];
        }

        $backupCodes = TotpHelper::generateBackupCodes(8);
        $this->repo->updateMfa(
            $idUsuario,
            (string) $user['mfa_secret'],
            true,
            json_encode($backupCodes['hashed'])
        );

        auditLog($idUsuario, 'usuario', $idUsuario, 'ACTUALIZAR', null, ['mfa_activado' => true]);

        return ['ok' => true, 'backup_codes' => $backupCodes['plain']];
    }

    /**
     * Desactiva MFA. Requiere contraseña actual + código TOTP (o backup code).
     *
     * @return array{ok: bool, errors?: array}
     */
    public function disable(int $idUsuario, string $password, string $code): array {
        $user = $this->repo->findMfaById($idUsuario);
        if (!$user) {
            return ['ok' => false, 'errors' => ['Usuario no encontrado.']];
        }
        if ((int) $user['mfa_enabled'] !== 1) {
            return ['ok' => false, 'errors' => ['MFA no está activo.']];
        }

        // Verificar contraseña
        $authUser = $this->repo->findAuthById($idUsuario);
        if (!$authUser || !password_verify($password, $authUser['contrasena_hash'])) {
            return ['ok' => false, 'errors' => ['Contraseña incorrecta.']];
        }

        // Verificar código TOTP o backup code
        if (!$this->verifyCodeOrBackup($user, $code, $idUsuario)) {
            return ['ok' => false, 'errors' => ['Código incorrecto.']];
        }

        $this->repo->updateMfa($idUsuario, null, false, null);
        auditLog($idUsuario, 'usuario', $idUsuario, 'ACTUALIZAR', null, ['mfa_desactivado' => true]);

        return ['ok' => true];
    }

    /**
     * Verifica un código TOTP durante el flujo de login.
     * Acepta tanto código TOTP como backup code.
     *
     * @return array{ok: bool, errors?: array}
     */
    public function verifyLogin(int $idUsuario, string $code): array {
        $user = $this->repo->findMfaById($idUsuario);
        if (!$user || (int) $user['mfa_enabled'] !== 1) {
            return ['ok' => false, 'errors' => ['MFA no está activo para este usuario.']];
        }

        if (!$this->verifyCodeOrBackup($user, $code, $idUsuario)) {
            return ['ok' => false, 'errors' => ['Código MFA incorrecto.']];
        }

        return ['ok' => true];
    }

    // ----- internos -----

    /**
     * Verifica código TOTP o backup code. Si es backup code, lo consume (elimina).
     */
    private function verifyCodeOrBackup(array $user, string $code, int $idUsuario): bool {
        $secret = (string) ($user['mfa_secret'] ?? '');

        // Intentar TOTP primero
        if (TotpHelper::verify($secret, $code)) {
            return true;
        }

        // Intentar backup code
        $codesJson = $user['mfa_backup_codes'] ?? null;
        if (!$codesJson) return false;
        $hashed = json_decode($codesJson, true);
        if (!is_array($hashed)) return false;

        $idx = TotpHelper::verifyBackupCode($code, $hashed);
        if ($idx === -1) return false;

        // Consumir el backup code (eliminarlo del array)
        array_splice($hashed, $idx, 1);
        $this->repo->updateMfa(
            $idUsuario,
            $secret,
            true,
            json_encode(array_values($hashed))
        );
        auditLog($idUsuario, 'usuario', $idUsuario, 'ACTUALIZAR', null, ['backup_code_usado' => true, 'restantes' => count($hashed)]);

        return true;
    }
}
