<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/EfirmaService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

/**
 * EfirmaController — endpoint para firmar contratos con e.firma/FIEL.
 *
 * POST /api/v1/contratos/{id}/firma-efirma
 * Content-Type: multipart/form-data
 * Campos:
 *   - cer: archivo .cer (certificado X.509)
 *   - key: archivo .key (clave privada PKCS#8 cifrada)
 *   - password: contraseña de la clave privada
 *
 * Seguridad:
 *   - Solo el proveedor dueño del contrato puede firmarlo.
 *   - El .key y el password NUNCA se persisten ni se loguean.
 *   - Los archivos se leen en memoria y se descartan.
 *   - Límite de tamaño: 100 KB por archivo (los .cer/.key del SAT son ~2-4 KB).
 */
class EfirmaController {
    private EfirmaService $service;

    private const MAX_FILE_SIZE = 102400; // 100 KB

    public function __construct() {
        $this->service = new EfirmaService();
    }

    public function firmar(int $idContrato): never {
        $user = AuthMiddleware::getAuthenticatedUser();

        // Validar archivos subidos
        $cerFile = $_FILES['cer'] ?? null;
        $keyFile = $_FILES['key'] ?? null;
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if (!$cerFile || ($cerFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'El archivo .cer es obligatorio.';
        }
        if (!$keyFile || ($keyFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'El archivo .key es obligatorio.';
        }
        if ($password === '') {
            $errors[] = 'La contraseña de la clave privada es obligatoria.';
        }
        if (!empty($errors)) {
            jsonResponse(false, 'Faltan campos requeridos', null, $errors, 400);
        }

        // Validar tamaño
        if (($cerFile['size'] ?? 0) > self::MAX_FILE_SIZE) {
            jsonResponse(false, 'El archivo .cer excede el tamaño máximo (100 KB)', null, null, 422);
        }
        if (($keyFile['size'] ?? 0) > self::MAX_FILE_SIZE) {
            jsonResponse(false, 'El archivo .key excede el tamaño máximo (100 KB)', null, null, 422);
        }

        // Leer contenido en memoria
        $cerContent = @file_get_contents($cerFile['tmp_name']);
        $keyContent = @file_get_contents($keyFile['tmp_name']);

        if ($cerContent === false || $cerContent === '') {
            jsonResponse(false, 'No se pudo leer el archivo .cer', null, null, 400);
        }
        if ($keyContent === false || $keyContent === '') {
            jsonResponse(false, 'No se pudo leer el archivo .key', null, null, 400);
        }

        // Ejecutar firma
        $result = $this->service->firmarContrato(
            $idContrato,
            (int) $user['id_usuario'],
            $cerContent,
            $keyContent,
            $password
        );

        // Limpiar variables sensibles de memoria
        $keyContent = str_repeat('0', strlen($keyContent));
        $password = str_repeat('0', strlen($password));
        unset($keyContent, $password);

        if (!$result['ok']) {
            $status = $result['status'] ?? 422;
            jsonResponse(false, 'Error al firmar el contrato', null, $result['errors'], $status);
        }

        jsonResponse(true, 'Contrato firmado con e.firma exitosamente', $result['data'], null, 200);
    }
}
