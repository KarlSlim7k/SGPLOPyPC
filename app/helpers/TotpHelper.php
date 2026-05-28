<?php
declare(strict_types=1);

/**
 * TotpHelper — implementación nativa de TOTP (RFC 6238) y HOTP (RFC 4226).
 *
 * Sin dependencias externas. Compatible con Google Authenticator, Authy,
 * Microsoft Authenticator y cualquier app TOTP estándar.
 *
 * Algoritmo: HMAC-SHA1, ventana de tiempo de 30s, 6 dígitos.
 */
class TotpHelper {
    private const DIGITS = 6;
    private const PERIOD = 30;   // segundos
    private const WINDOW = 1;    // ±1 período de tolerancia (total 3 períodos)
    private const ALGORITHM = 'sha1';

    /**
     * Genera un secreto base32 aleatorio de 160 bits (32 chars base32).
     */
    public static function generateSecret(): string {
        $bytes = random_bytes(20); // 160 bits
        return self::base32Encode($bytes);
    }

    /**
     * Verifica un código TOTP de 6 dígitos contra el secreto.
     * Acepta ventana de ±WINDOW períodos para tolerar desfase de reloj.
     *
     * @param string $secret  Secreto base32
     * @param string $code    Código de 6 dígitos ingresado por el usuario
     * @param int    $at      Timestamp Unix (default: now)
     */
    public static function verify(string $secret, string $code, int $at = 0): bool {
        if ($at === 0) $at = time();
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $counter = (int) floor($at / self::PERIOD);
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (self::hotp($secret, $counter + $i) === $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * Genera el código TOTP actual (útil para tests).
     */
    public static function currentCode(string $secret, int $at = 0): string {
        if ($at === 0) $at = time();
        $counter = (int) floor($at / self::PERIOD);
        return self::hotp($secret, $counter);
    }

    /**
     * Construye la URL otpauth:// para generar el QR code.
     *
     * @param string $secret  Secreto base32
     * @param string $email   Email del usuario (label)
     * @param string $issuer  Nombre de la aplicación
     */
    public static function otpauthUrl(string $secret, string $email, string $issuer = 'SGPLOPyPC'): string {
        $label = rawurlencode($issuer . ':' . $email);
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    /**
     * Genera N códigos de respaldo de 8 caracteres alfanuméricos.
     * Devuelve [plaintext[], hashed[]] — guardar sólo los hashes.
     *
     * @return array{plain: string[], hashed: string[]}
     */
    public static function generateBackupCodes(int $count = 8): array {
        $plain = [];
        $hashed = [];
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // sin 0/1/I/O para evitar confusión
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $plain[] = $code;
            $hashed[] = password_hash($code, PASSWORD_BCRYPT);
        }
        return ['plain' => $plain, 'hashed' => $hashed];
    }

    /**
     * Verifica un código de respaldo contra la lista de hashes.
     * Si coincide, devuelve el índice para que el caller lo elimine.
     * Devuelve -1 si no coincide.
     */
    public static function verifyBackupCode(string $code, array $hashedCodes): int {
        $code = strtoupper(preg_replace('/\s+/', '', $code));
        foreach ($hashedCodes as $i => $hash) {
            if (password_verify($code, $hash)) {
                return $i;
            }
        }
        return -1;
    }

    // ----- internos -----

    private static function hotp(string $secret, int $counter): string {
        $key = self::base32Decode($secret);
        // Counter como 8 bytes big-endian
        $msg = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac(self::ALGORITHM, $msg, $key, true);
        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $bytes): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        foreach (str_split($bytes) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $output .= $alphabet[($buffer >> $bitsLeft) & 0x1f];
            }
        }
        if ($bitsLeft > 0) {
            $output .= $alphabet[($buffer << (5 - $bitsLeft)) & 0x1f];
        }
        return $output;
    }

    private static function base32Decode(string $input): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(preg_replace('/[^A-Z2-7]/', '', $input));
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        foreach (str_split($input) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }
        return $output;
    }
}
