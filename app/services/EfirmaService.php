<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/EfirmaValidator.php';
require_once __DIR__ . '/../repositories/ContratoRepository.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

/**
 * EfirmaService — orquesta el proceso de firma electrónica avanzada (e.firma/FIEL)
 * de contratos en SGPLOPyPC.
 *
 * Flujo:
 *   1. Verificar que el contrato existe y pertenece al proveedor autenticado.
 *   2. Parsear el certificado .cer y extraer RFC, titular, serial.
 *   3. Verificar vigencia del certificado.
 *   4. Cargar la clave privada .key con el password (en memoria, nunca persiste).
 *   5. Verificar que la clave corresponde al certificado.
 *   6. Calcular el hash SHA-256 del documento (número de contrato + monto + fechas).
 *   7. Firmar el hash con la clave privada.
 *   8. Persistir metadatos (RFC, titular, serial, fecha, hash, firma_b64) en BD.
 *   9. Descartar la clave privada de memoria.
 *
 * NUNCA se almacena el archivo .key ni el password.
 */
class EfirmaService {
    private ContratoRepository $contratoRepo;
    private ProveedorRepository $proveedorRepo;

    public function __construct() {
        $this->contratoRepo = new ContratoRepository();
        $this->proveedorRepo = new ProveedorRepository();
    }

    /**
     * Firma un contrato con e.firma.
     *
     * @param int    $idContrato
     * @param int    $idUsuario       Usuario autenticado (debe ser el proveedor del contrato)
     * @param string $cerContent      Contenido binario del archivo .cer
     * @param string $keyContent      Contenido binario del archivo .key
     * @param string $password        Contraseña de la clave privada
     * @return array{ok: bool, data?: array, errors?: array, status?: int}
     */
    public function firmarContrato(
        int $idContrato,
        int $idUsuario,
        string $cerContent,
        string $keyContent,
        string $password
    ): array {
        // 1. Verificar contrato
        $contrato = $this->contratoRepo->findById($idContrato);
        if (!$contrato) {
            return ['ok' => false, 'errors' => ['Contrato no encontrado.'], 'status' => 404];
        }

        // 2. Verificar que el usuario es el proveedor del contrato
        $proveedor = $this->proveedorRepo->findByUsuario($idUsuario);
        if (!$proveedor || (int) $proveedor['id_proveedor'] !== (int) $contrato['id_proveedor']) {
            return ['ok' => false, 'errors' => ['No tienes permiso para firmar este contrato.'], 'status' => 403];
        }

        // 3. Verificar que el contrato no está ya firmado con e.firma
        if (!empty($contrato['efirma_firma_b64'])) {
            return ['ok' => false, 'errors' => ['Este contrato ya fue firmado con e.firma.'], 'status' => 409];
        }

        // 4. Parsear certificado
        $certData = EfirmaValidator::parseCer($cerContent);
        if (!$certData['ok']) {
            return ['ok' => false, 'errors' => $certData['errors'], 'status' => 422];
        }

        // 5. Verificar vigencia
        if (!EfirmaValidator::isVigente($certData)) {
            return ['ok' => false, 'errors' => ['El certificado e.firma está vencido o aún no es válido.'], 'status' => 422];
        }

        // 6. Cargar clave privada (en memoria)
        $privateKey = EfirmaValidator::loadPrivateKey($keyContent, $password);
        if ($privateKey === null) {
            return ['ok' => false, 'errors' => ['No se pudo cargar la clave privada. Verifica el archivo .key y la contraseña.'], 'status' => 422];
        }

        // 7. Verificar que la clave corresponde al certificado
        if (!EfirmaValidator::keyMatchesCert($privateKey, $certData['cert_pem'])) {
            // Descartar clave antes de retornar
            unset($privateKey);
            return ['ok' => false, 'errors' => ['La clave privada no corresponde al certificado proporcionado.'], 'status' => 422];
        }

        // 8. Calcular hash del documento (datos canónicos del contrato)
        $documentoCanónico = $this->buildDocumentHash($contrato);
        $hashDocumento = hash('sha256', $documentoCanónico);

        // 9. Firmar
        $firmaB64 = EfirmaValidator::sign($hashDocumento, $privateKey);

        // 10. Descartar clave privada de memoria INMEDIATAMENTE
        unset($privateKey);

        if ($firmaB64 === null) {
            return ['ok' => false, 'errors' => ['Error al generar la firma digital.'], 'status' => 500];
        }

        // 11. Persistir metadatos (NUNCA el .key ni el password)
        $fechaFirma = date('Y-m-d H:i:s');
        $this->contratoRepo->updateEfirma($idContrato, [
            'rfc' => $certData['rfc'],
            'titular' => $certData['titular'],
            'serial' => $certData['serial'],
            'fecha' => $fechaFirma,
            'hash_documento' => $hashDocumento,
            'firma_b64' => $firmaB64,
            'firmado_por' => $idUsuario,
        ]);

        auditLog($idUsuario, 'contrato', $idContrato, 'FIRMAR', null, [
            'efirma_rfc' => $certData['rfc'],
            'efirma_serial' => $certData['serial'],
            'efirma_hash_documento' => $hashDocumento,
        ]);

        return [
            'ok' => true,
            'data' => [
                'id_contrato' => $idContrato,
                'efirma_rfc' => $certData['rfc'],
                'efirma_titular' => $certData['titular'],
                'efirma_serial' => $certData['serial'],
                'efirma_fecha' => $fechaFirma,
                'efirma_hash_documento' => $hashDocumento,
                'certificado_vigente_hasta' => $certData['valid_to'],
            ],
        ];
    }

    /**
     * Construye la cadena canónica del contrato para calcular el hash del documento.
     * Incluye los campos más relevantes del contrato para garantizar integridad.
     */
    private function buildDocumentHash(array $contrato): string {
        return implode('|', [
            'SGPLOPYPC',
            'CONTRATO',
            $contrato['id_contrato'],
            $contrato['numero_contrato'],
            $contrato['monto_contrato'],
            $contrato['fecha_adjudicacion'] ?? '',
            $contrato['fecha_inicio'] ?? '',
            $contrato['fecha_fin'] ?? '',
            $contrato['numero_licitacion'] ?? '',
            $contrato['nombre_empresa'] ?? '',
            $contrato['registro_fiscal'] ?? '',
        ]);
    }
}
