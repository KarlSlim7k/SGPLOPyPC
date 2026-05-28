<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../middlewares/RequestIdMiddleware.php';

/**
 * Registra una acción auditable en historial_cambio.
 *
 * @param int|null    $idUsuario          Usuario que realiza la acción (null para eventos pre-auth, ej: login fallido).
 * @param string      $tabla              Tabla/recurso afectado (ej: 'usuario', 'licitacion').
 * @param int         $idRegistro         ID del registro afectado (0 si no aplica, ej: login).
 * @param string      $accion             Acción: CREAR | ACTUALIZAR | ELIMINAR | LOGIN_OK | LOGIN_FALLIDO |
 *                                        LOGOUT | PASSWORD_CHANGE | PASSWORD_RESET | EXPORT | CONSULTA.
 * @param array|null  $valoresAnteriores  Estado previo del registro (opcional).
 * @param array|null  $valoresNuevos      Estado nuevo o metadatos del evento (opcional).
 */
function auditLog(
    ?int $idUsuario,
    string $tabla,
    int $idRegistro,
    string $accion,
    ?array $valoresAnteriores = null,
    ?array $valoresNuevos = null
): void {
    try {
        $db = getDbConnection();

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if (is_string($ip) && str_contains($ip, ',')) {
            // Si hay cadena de proxies, tomar la primera (cliente original)
            $ip = trim(explode(',', $ip)[0]);
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if (is_string($userAgent) && strlen($userAgent) > 500) {
            $userAgent = substr($userAgent, 0, 500);
        }

        $requestId = null;
        try {
            $requestId = RequestIdMiddleware::get();
        } catch (Throwable $e) {
            $requestId = null;
        }

        $stmt = $db->prepare(
            'INSERT INTO historial_cambio
                (id_usuario, tabla_afectada, id_registro_afectado, accion,
                 valores_anteriores, valores_nuevos, ip_origen, user_agent, request_id, fecha_accion)
             VALUES
                (:id_usuario, :tabla, :id_registro, :accion,
                 :valores_anteriores, :valores_nuevos, :ip_origen, :user_agent, :request_id, NOW())'
        );

        $stmt->execute([
            'id_usuario' => $idUsuario,
            'tabla' => $tabla,
            'id_registro' => $idRegistro,
            'accion' => $accion,
            'valores_anteriores' => $valoresAnteriores !== null
                ? json_encode($valoresAnteriores, JSON_UNESCAPED_UNICODE) : null,
            'valores_nuevos' => $valoresNuevos !== null
                ? json_encode($valoresNuevos, JSON_UNESCAPED_UNICODE) : null,
            'ip_origen' => $ip,
            'user_agent' => $userAgent,
            'request_id' => $requestId,
        ]);
    } catch (Throwable $e) {
        // Auditoría jamás debe romper la operación funcional, solo loguear el error.
        error_log('Audit log error: ' . $e->getMessage());
    }
}
