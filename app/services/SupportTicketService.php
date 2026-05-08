<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/SupportTicketRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class SupportTicketService {
    private const ESTADOS_VALIDOS = ['NUEVO', 'EN_PROCESO', 'CERRADO'];

    private SupportTicketRepository $repo;

    public function __construct() {
        $this->repo = new SupportTicketRepository();
    }

    public function listAdmin(int $page, int $limit, ?string $estado = null, ?string $search = null): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        $normalizedEstado = null;
        if ($estado !== null && trim($estado) !== '') {
            $candidate = strtoupper(trim($estado));
            if (in_array($candidate, self::ESTADOS_VALIDOS, true)) {
                $normalizedEstado = $candidate;
            }
        }

        $data = $this->repo->findForAdmin($page, $limit, $normalizedEstado, $search);
        $data['resumen'] = $this->repo->getResumen();
        return $data;
    }

    public function getById(int $id): ?array {
        return $this->repo->findById($id);
    }

    public function changeEstado(int $id, string $estado, int $idUsuario): array {
        $nextEstado = strtoupper(trim($estado));
        if (!in_array($nextEstado, self::ESTADOS_VALIDOS, true)) {
            return ['ok' => false, 'errors' => ['Estado invalido. Valores permitidos: NUEVO, EN_PROCESO, CERRADO.']];
        }

        $ticket = $this->repo->findById($id);
        if (!$ticket) {
            return ['ok' => false, 'errors' => ['Ticket de soporte no encontrado.']];
        }

        if ($ticket['estado'] !== $nextEstado) {
            $this->repo->updateEstado($id, $nextEstado);
            $updated = $this->repo->findById($id);
            if ($updated) {
                auditLog(
                    $idUsuario,
                    'soporte_ticket',
                    $id,
                    'ACTUALIZAR',
                    ['estado' => $ticket['estado']],
                    ['estado' => $updated['estado']]
                );
                return ['ok' => true, 'data' => $updated];
            }
            $ticket['estado'] = $nextEstado;
            return ['ok' => true, 'data' => $ticket];
        }

        return ['ok' => true, 'data' => $ticket];
    }
}
