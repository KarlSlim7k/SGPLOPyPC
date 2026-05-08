<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/SupportTicketRepository.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/Mailer.php';

class SupportTicketService {
    private const ESTADOS_VALIDOS = ['NUEVO', 'EN_PROCESO', 'CERRADO'];

    private SupportTicketRepository $repo;
    private Mailer $mailer;

    public function __construct() {
        $this->repo = new SupportTicketRepository();
        $this->mailer = new Mailer();
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
                $this->notifyStatusChange($updated, (string) $ticket['estado']);
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

    private function notifyStatusChange(array $ticket, string $previousEstado): void {
        if (env('SUPPORT_NOTIFY_STATUS_CHANGE', '0') !== '1') {
            return;
        }

        $to = trim((string) ($ticket['email'] ?? ''));
        $folio = (string) ($ticket['folio'] ?? '');
        if ($to === '' || $folio === '') {
            return;
        }

        $subject = 'Actualizacion de ticket de soporte ' . $folio;
        $body = "Tu ticket de soporte fue actualizado.\n\n"
              . "Folio: {$folio}\n"
              . 'Estado anterior: ' . $this->estadoLabel($previousEstado) . "\n"
              . 'Estado actual: ' . $this->estadoLabel((string) ($ticket['estado'] ?? '')) . "\n\n"
              . "Asunto: " . (string) ($ticket['asunto'] ?? '') . "\n"
              . "Mensaje original:\n" . (string) ($ticket['mensaje'] ?? '') . "\n\n"
              . "Este correo es informativo.";

        $this->mailer->send($to, $subject, $body);
    }

    private function estadoLabel(string $estado): string {
        return match ($estado) {
            'NUEVO' => 'Nuevo',
            'EN_PROCESO' => 'En proceso',
            'CERRADO' => 'Cerrado',
            default => $estado,
        };
    }
}
