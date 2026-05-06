<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/NotificacionRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class NotificacionService {
    private NotificacionRepository $repo;

    public function __construct() {
        $this->repo = new NotificacionRepository();
    }

    public function crear(array $input, int $idUsuarioCreador): array {
        $errors = [];
        if (empty($input['id_usuario_destino']) || (int) $input['id_usuario_destino'] <= 0) {
            $errors[] = 'El destinatario es obligatorio.';
        }
        if (empty($input['titulo']) || trim($input['titulo']) === '') {
            $errors[] = 'El título es obligatorio.';
        }
        if (empty($input['mensaje']) || trim($input['mensaje']) === '') {
            $errors[] = 'El mensaje es obligatorio.';
        }
        $tipos = ['CONVOCATORIA_PUBLICADA','ACLARACION','RESULTADO_EVALUACION','ADJUDICACION','CAMBIO_ESTADO','GENERAL'];
        if (empty($input['tipo_notificacion']) || !in_array($input['tipo_notificacion'], $tipos, true)) {
            $errors[] = 'Tipo de notificación no válido.';
        }
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $data = [
            'id_usuario_destino' => (int) $input['id_usuario_destino'],
            'id_licitacion' => isset($input['id_licitacion']) && (int) $input['id_licitacion'] > 0 ? (int) $input['id_licitacion'] : null,
            'tipo_notificacion' => $input['tipo_notificacion'],
            'titulo' => trim($input['titulo']),
            'mensaje' => trim($input['mensaje']),
        ];
        $id = $this->repo->create($data);
        auditLog($idUsuarioCreador, 'notificacion', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function listarMias(int $idUsuario): array {
        return $this->repo->findByUsuario($idUsuario);
    }

    public function marcarLeida(int $idNotificacion, int $idUsuario): array {
        $notif = $this->repo->findById($idNotificacion);
        if (!$notif) {
            return ['ok' => false, 'errors' => ['Notificación no encontrada.']];
        }
        if ((int) $notif['id_usuario_destino'] !== $idUsuario) {
            return ['ok' => false, 'errors' => ['No tienes permisos para modificar esta notificación.']];
        }
        if ((bool) $notif['leida']) {
            return ['ok' => true];
        }
        $this->repo->marcarLeida($idNotificacion);
        auditLog($idUsuario, 'notificacion', $idNotificacion, 'ACTUALIZAR', ['leida' => false], ['leida' => true]);
        return ['ok' => true];
    }

    public function notificarEventoSistema(int $idLicitacion, string $tipo, string $titulo, string $mensaje): int {
        $destinatarios = $this->repo->findDestinatariosByLicitacion($idLicitacion);
        $creadas = 0;
        foreach ($destinatarios as $idUsuario) {
            $data = [
                'id_usuario_destino' => (int) $idUsuario,
                'id_licitacion' => $idLicitacion,
                'tipo_notificacion' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
            ];
            $this->repo->create($data);
            $creadas++;
        }
        return $creadas;
    }
}
