<?php
declare(strict_types=1);

/**
 * EfirmaValidator — valida certificados e.firma/FIEL del SAT (México).
 *
 * Funciones:
 *   - Parsear certificado X.509 (.cer) y extraer RFC, titular, serial, vigencia.
 *   - Verificar que la clave privada (.key) corresponde al certificado.
 *   - Firmar un hash de documento con la clave privada (PKCS#1 SHA-256).
 *   - Verificar una firma existente contra el certificado.
 *
 * Seguridad:
 *   - La clave privada (.key) y el password NUNCA se persisten.
 *   - Se usan en memoria y se descartan inmediatamente.
 *   - Requiere ext-openssl (disponible en PHP 8.x).
 *
 * Formato de archivos e.firma SAT:
 *   - .cer: certificado X.509 en formato DER (binario) o PEM.
 *   - .key: clave privada PKCS#8 cifrada con DES3 en formato DER.
 */
class EfirmaValidator {

    /**
     * Parsea un archivo .cer (DER o PEM) y extrae los metadatos del certificado.
     *
     * @param string $cerContent  Contenido binario del archivo .cer
     * @return array{
     *   ok: bool,
     *   rfc?: string,
     *   titular?: string,
     *   serial?: string,
     *   valid_from?: string,
     *   valid_to?: string,
     *   cert_pem?: string,
     *   errors?: array
     * }
     */
    public static function parseCer(string $cerContent): array {
        if (empty($cerContent)) {
            return ['ok' => false, 'errors' => ['El archivo .cer está vacío.']];
        }

        // Convertir DER → PEM si es necesario
        $pem = self::derToPem($cerContent);

        $cert = @openssl_x509_read($pem);
        if ($cert === false) {
            return ['ok' => false, 'errors' => ['No se pudo leer el certificado. Verifica que sea un archivo .cer válido.']];
        }

        $info = openssl_x509_parse($cert);
        if (!is_array($info)) {
            return ['ok' => false, 'errors' => ['No se pudo parsear el certificado.']];
        }

        // Extraer RFC del Subject (campo OU o serialNumber según versión del SAT)
        $rfc = self::extractRfc($info);
        $titular = self::extractTitular($info);
        $serial = self::formatSerial($info['serialNumber'] ?? '');

        // Validar vigencia
        $validFrom = isset($info['validFrom_time_t']) ? date('Y-m-d H:i:s', (int) $info['validFrom_time_t']) : null;
        $validTo = isset($info['validTo_time_t']) ? date('Y-m-d H:i:s', (int) $info['validTo_time_t']) : null;

        return [
            'ok' => true,
            'rfc' => $rfc,
            'titular' => $titular,
            'serial' => $serial,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'cert_pem' => $pem,
        ];
    }

    /**
     * Verifica que el certificado esté vigente al momento actual.
     */
    public static function isVigente(array $certData, int $at = 0): bool {
        if (!$certData['ok']) return false;
        $now = $at > 0 ? $at : time();
        $cert = @openssl_x509_read($certData['cert_pem']);
        if (!$cert) return false;
        $info = openssl_x509_parse($cert);
        if (!is_array($info)) return false;
        $from = (int) ($info['validFrom_time_t'] ?? 0);
        $to = (int) ($info['validTo_time_t'] ?? 0);
        return $now >= $from && $now <= $to;
    }

    /**
     * Carga la clave privada desde un archivo .key del SAT (PKCS#8 DER cifrado).
     * Devuelve el recurso de clave privada o null si falla.
     *
     * IMPORTANTE: el llamador debe descartar la clave inmediatamente después de usarla.
     *
     * @param string $keyContent  Contenido binario del archivo .key
     * @param string $password    Contraseña de la clave privada
     * @return \OpenSSLAsymmetricKey|null
     */
    public static function loadPrivateKey(string $keyContent, string $password): mixed {
        if (empty($keyContent) || $password === '') return null;

        // El .key del SAT es PKCS#8 DER cifrado. Convertir a PEM.
        $pem = self::derKeyToPem($keyContent);

        $key = @openssl_pkey_get_private($pem, $password);
        return $key !== false ? $key : null;
    }

    /**
     * Verifica que la clave privada corresponde al certificado.
     *
     * @param \OpenSSLAsymmetricKey $privateKey
     * @param string $certPem
     */
    public static function keyMatchesCert(mixed $privateKey, string $certPem): bool {
        $cert = @openssl_x509_read($certPem);
        if (!$cert) return false;
        return (bool) @openssl_x509_check_private_key($cert, $privateKey);
    }

    /**
     * Firma un hash SHA-256 con la clave privada (PKCS#1 v1.5).
     * Devuelve la firma en base64 o null si falla.
     *
     * @param string $documentHash  Hash SHA-256 hex del documento (64 chars)
     * @param \OpenSSLAsymmetricKey $privateKey
     */
    public static function sign(string $documentHash, mixed $privateKey): ?string {
        // Firmar el hash como datos (no re-hashear)
        $signature = '';
        $result = @openssl_sign($documentHash, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$result) return null;
        return base64_encode($signature);
    }

    /**
     * Verifica una firma base64 contra el certificado y el hash del documento.
     *
     * @param string $documentHash  Hash SHA-256 hex del documento
     * @param string $signatureB64  Firma en base64
     * @param string $certPem       Certificado PEM
     */
    public static function verify(string $documentHash, string $signatureB64, string $certPem): bool {
        $cert = @openssl_x509_read($certPem);
        if (!$cert) return false;
        $pubKey = @openssl_pkey_get_public($cert);
        if (!$pubKey) return false;
        $signature = base64_decode($signatureB64, true);
        if ($signature === false) return false;
        $result = @openssl_verify($documentHash, $signature, $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    // ----- internos -----

    private static function derToPem(string $der): string {
        // Si ya es PEM, devolverlo tal cual
        if (str_contains($der, '-----BEGIN CERTIFICATE-----')) {
            return $der;
        }
        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END CERTIFICATE-----\n";
    }

    private static function derKeyToPem(string $der): string {
        // Si ya es PEM, devolverlo tal cual
        if (str_contains($der, '-----BEGIN')) {
            return $der;
        }
        // PKCS#8 cifrado
        return "-----BEGIN ENCRYPTED PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END ENCRYPTED PRIVATE KEY-----\n";
    }

    private static function extractRfc(array $info): string {
        // El SAT incluye el RFC en el campo x500UniqueIdentifier o en OU
        $subject = $info['subject'] ?? [];

        // Versión moderna: x500UniqueIdentifier contiene RFC / CURP
        if (!empty($subject['x500UniqueIdentifier'])) {
            $uid = (string) $subject['x500UniqueIdentifier'];
            // Formato: "RFC / CURP" o sólo RFC
            $parts = explode(' / ', $uid);
            $rfc = trim($parts[0]);
            if (preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc)) {
                return $rfc;
            }
        }

        // Versión anterior: OU contiene el RFC
        if (!empty($subject['OU'])) {
            $ou = is_array($subject['OU']) ? $subject['OU'][0] : $subject['OU'];
            if (preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', trim($ou))) {
                return trim($ou);
            }
        }

        // Fallback: buscar en serialNumber
        if (!empty($subject['serialNumber'])) {
            $sn = (string) $subject['serialNumber'];
            if (preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $sn)) {
                return $sn;
            }
        }

        return '';
    }

    private static function extractTitular(array $info): string {
        $subject = $info['subject'] ?? [];
        // CN contiene el nombre completo
        if (!empty($subject['CN'])) {
            return (string) $subject['CN'];
        }
        if (!empty($subject['commonName'])) {
            return (string) $subject['commonName'];
        }
        return '';
    }

    private static function formatSerial(string $serial): string {
        // Convertir a hexadecimal si viene como decimal
        if (ctype_digit($serial)) {
            return strtoupper(base_convert($serial, 10, 16));
        }
        return strtoupper($serial);
    }
}
