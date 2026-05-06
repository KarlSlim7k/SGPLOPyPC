<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

function auditLog(int $idUsuario, string $tabla, int $idRegistro, string $accion, ?array $valoresAnteriores = null, ?array $valoresNuevos = null): void {
    try {
        $db = getDbConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null);
        $stmt = $db->prepare(
            'INSERT INTO historial_cambio (id_usuario, tabla_afectada, id_registro_afectado, accion, valores_anteriores, valores_nuevos, ip_origen, fecha_accion) '
            . 'VALUES (:id_usuario, :tabla, :id_registro, :accion, :valores_anteriores, :valores_nuevos, :ip_origen, NOW())'
        );
        $stmt->execute([
            'id_usuario' => $idUsuario,
            'tabla' => $tabla,
            'id_registro' => $idRegistro,
            'accion' => $accion,
            'valores_anteriores' => $valoresAnteriores !== null ? json_encode($valoresAnteriores, JSON_UNESCAPED_UNICODE) : null,
            'valores_nuevos' => $valoresNuevos !== null ? json_encode($valoresNuevos, JSON_UNESCAPED_UNICODE) : null,
            'ip_origen' => $ip,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}
