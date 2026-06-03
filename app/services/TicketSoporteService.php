<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/TicketSoporteRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class TicketSoporteService {
    private const ESTADOS_VALIDOS = ['ABIERTO', 'EN_PROCESO', 'RESUELTO', 'CERRADO'];
    private const PRIORIDADES_VALIDAS = ['BAJA', 'MEDIA', 'ALTA', 'URGENTE'];

    private TicketSoporteRepository $repo;

    public function __construct() {
        $this->repo = new TicketSoporteRepository();
    }

    public function crear(array $input, int $idUsuario): array {
        $errors = [];
        $asunto = isset($input['asunto']) ? trim((string) $input['asunto']) : '';
        $descripcion = isset($input['descripcion']) ? trim((string) $input['descripcion']) : '';
        $prioridad = isset($input['prioridad']) ? strtoupper(trim((string) $input['prioridad'])) : 'MEDIA';

        if ($asunto === '' || strlen($asunto) > 200) {
            $errors[] = 'El asunto es obligatorio y no debe superar 200 caracteres.';
        }
        if ($descripcion === '') {
            $errors[] = 'La descripción es obligatoria.';
        }
        if (!in_array($prioridad, self::PRIORIDADES_VALIDAS, true)) {
            $errors[] = 'Prioridad inválida. Valores permitidos: BAJA, MEDIA, ALTA, URGENTE.';
        }
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $id = $this->repo->create([
            'id_usuario' => $idUsuario,
            'asunto' => $asunto,
            'descripcion' => $descripcion,
            'prioridad' => $prioridad,
            'estado' => 'ABIERTO',
        ]);

        auditLog($idUsuario, 'ticket_soporte', $id, 'CREAR', null, ['asunto' => $asunto, 'prioridad' => $prioridad]);

        $ticket = $this->repo->findById($id);
        return ['ok' => true, 'data' => $ticket];
    }

    public function listMios(int $idUsuario, int $page = 1, int $perPage = 20): array {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $data = $this->repo->findByUsuario($idUsuario, $page, $perPage);
        $data['resumen'] = $this->repo->getResumenByUsuario($idUsuario);
        return $data;
    }

    public function getDetalle(int $idTicket, int $idUsuario, bool $esAdmin = false): ?array {
        $ticket = $this->repo->findById($idTicket);
        if (!$ticket) {
            return null;
        }
        if (!$esAdmin && (int) $ticket['id_usuario'] !== $idUsuario) {
            return null;
        }
        $ticket['respuestas'] = $this->repo->findRespuestasByTicket($idTicket);
        return $ticket;
    }

    public function agregarRespuesta(int $idTicket, array $input, int $idUsuario, bool $esAdmin = false): array {
        $ticket = $this->repo->findById($idTicket);
        if (!$ticket) {
            return ['ok' => false, 'errors' => ['Ticket no encontrado.'], 'status' => 404];
        }
        if (!$esAdmin && (int) $ticket['id_usuario'] !== $idUsuario) {
            return ['ok' => false, 'errors' => ['No tienes permiso para responder este ticket.'], 'status' => 403];
        }

        $mensaje = isset($input['mensaje']) ? trim((string) $input['mensaje']) : '';
        if ($mensaje === '') {
            return ['ok' => false, 'errors' => ['El mensaje es obligatorio.']];
        }

        $idRespuesta = $this->repo->addRespuesta([
            'id_ticket' => $idTicket,
            'id_usuario' => $idUsuario,
            'mensaje' => $mensaje,
        ]);

        auditLog($idUsuario, 'ticket_respuesta', $idRespuesta, 'CREAR', null, ['id_ticket' => $idTicket]);

        return ['ok' => true, 'data' => ['id_respuesta' => $idRespuesta]];
    }

    public function cambiarEstado(int $idTicket, array $input, int $idUsuario): array {
        $ticket = $this->repo->findById($idTicket);
        if (!$ticket) {
            return ['ok' => false, 'errors' => ['Ticket no encontrado.'], 'status' => 404];
        }

        $estado = isset($input['estado']) ? strtoupper(trim((string) $input['estado'])) : '';
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return ['ok' => false, 'errors' => ['Estado inválido. Valores permitidos: ABIERTO, EN_PROCESO, RESUELTO, CERRADO.']];
        }

        $this->repo->updateEstado($idTicket, $estado);
        $updated = $this->repo->findById($idTicket);

        auditLog($idUsuario, 'ticket_soporte', $idTicket, 'ACTUALIZAR', ['estado' => $ticket['estado']], ['estado' => $estado]);

        return ['ok' => true, 'data' => $updated];
    }
}
