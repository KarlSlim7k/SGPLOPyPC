<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ParticipacionRepository.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../repositories/LicitacionRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class ParticipacionService {
    private ParticipacionRepository $partRepo;
    private PropuestaRepository $propRepo;
    private ProveedorRepository $provRepo;
    private LicitacionRepository $licRepo;

    public function __construct() {
        $this->partRepo = new ParticipacionRepository();
        $this->propRepo = new PropuestaRepository();
        $this->provRepo = new ProveedorRepository();
        $this->licRepo = new LicitacionRepository();
    }

    public function listByLicitacion(int $idLicitacion): array {
        return $this->partRepo->findByLicitacion($idLicitacion);
    }

    public function listAll(
        int $page,
        int $limit,
        ?int $idLicitacion = null,
        ?string $estatus = null,
        ?string $search = null
    ): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $normalizedEstatus = ($estatus !== null && trim($estatus) !== '') ? strtoupper(trim($estatus)) : null;
        $normalizedSearch = ($search !== null && trim($search) !== '') ? trim($search) : null;

        return $this->partRepo->findAllForAdmin($page, $limit, $idLicitacion, $normalizedEstatus, $normalizedSearch);
    }

    public function listMias(int $idUsuario, int $page, int $limit, ?string $estatus = null, ?string $search = null): array {
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if (!$proveedor) {
            return ['ok' => false, 'errors' => ['El usuario no tiene un perfil de proveedor registrado.']];
        }

        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $normalizedEstatus = ($estatus !== null && trim($estatus) !== '') ? strtoupper(trim($estatus)) : null;
        $normalizedSearch = ($search !== null && trim($search) !== '') ? trim($search) : null;

        return [
            'ok' => true,
            'data' => $this->partRepo->findByProveedorForPortal(
                (int) $proveedor['id_proveedor'],
                $page,
                $limit,
                $normalizedEstatus,
                $normalizedSearch
            ),
        ];
    }

    public function inscribir(int $idLicitacion, int $idUsuario): array {
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if (!$proveedor) {
            return ['ok' => false, 'errors' => ['El usuario no tiene un perfil de proveedor registrado.']];
        }
        if ($proveedor['estatus'] !== 'VALIDADO') {
            return ['ok' => false, 'errors' => ['El proveedor debe estar validado para inscribirse.']];
        }
        $licitacion = $this->licRepo->findById($idLicitacion);
        if (!$licitacion) {
            return ['ok' => false, 'errors' => ['Licitación no encontrada.']];
        }
        $estadosPermitidos = ['PUBLICADA','EN_ACLARACIONES','RECEPCION_PROPUESTAS'];
        if (!in_array($licitacion['estado_proceso'], $estadosPermitidos, true)) {
            return ['ok' => false, 'errors' => ['La licitación no permite inscripciones en su estado actual.']];
        }
        $existente = $this->partRepo->findByProveedorAndLicitacion((int) $proveedor['id_proveedor'], $idLicitacion);
        if ($existente) {
            return ['ok' => false, 'errors' => ['El proveedor ya está inscrito en esta licitación.']];
        }
        $id = $this->partRepo->create([
            'id_proveedor' => (int) $proveedor['id_proveedor'],
            'id_licitacion' => $idLicitacion,
            'estatus' => 'INSCRITO',
        ]);
        auditLog($idUsuario, 'participacion', $id, 'CREAR', null, ['id_licitacion' => $idLicitacion, 'id_proveedor' => $proveedor['id_proveedor']]);
        return ['ok' => true, 'id' => $id];
    }

    public function enviarPropuesta(int $idParticipacion, array $input, int $idUsuario): array {
        $participacion = $this->partRepo->findById($idParticipacion);
        if (!$participacion) {
            return ['ok' => false, 'errors' => ['Participación no encontrada.']];
        }
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if (!$proveedor || (int) $proveedor['id_proveedor'] !== (int) $participacion['id_proveedor']) {
            return ['ok' => false, 'errors' => ['No tienes permiso para enviar propuesta en esta participación.']];
        }
        $licitacion = $this->licRepo->findById((int) $participacion['id_licitacion']);
        if (!$licitacion) {
            return ['ok' => false, 'errors' => ['Licitación no encontrada.']];
        }
        $estadosPermitidos = ['RECEPCION_PROPUESTAS'];
        if (!in_array($licitacion['estado_proceso'], $estadosPermitidos, true)) {
            return ['ok' => false, 'errors' => ['La licitación no está recibiendo propuestas.']];
        }
        $existente = $this->propRepo->findByParticipacion($idParticipacion);
        if ($existente) {
            return ['ok' => false, 'errors' => ['Ya existe una propuesta para esta participación.']];
        }
        $errors = [];
        if (!isset($input['monto_propuesta']) || (float) $input['monto_propuesta'] <= 0) {
            $errors[] = 'El monto de la propuesta debe ser mayor a 0.';
        }
        if (empty($errors) === false) {
            return ['ok' => false, 'errors' => $errors];
        }
        $data = [
            'id_participacion' => $idParticipacion,
            'monto_propuesta' => (float) $input['monto_propuesta'],
            'descripcion_tecnica' => isset($input['descripcion_tecnica']) ? trim($input['descripcion_tecnica']) : null,
            'cumple_requisitos' => null,
            'estatus' => 'RECIBIDA',
        ];
        $id = $this->propRepo->create($data);
        $this->partRepo->updateEstatus($idParticipacion, 'PROPUESTA_ENVIADA');
        auditLog($idUsuario, 'propuesta', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function getPropuesta(int $idPropuesta, int $idUsuario, string $rol): ?array {
        $propuesta = $this->propRepo->findById($idPropuesta);
        if (!$propuesta) return null;
        if ($rol === 'ADMINISTRADOR') return $propuesta;
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if ($proveedor && (int) $proveedor['id_proveedor'] === (int) $propuesta['id_proveedor']) {
            return $propuesta;
        }
        return null;
    }

    public function listPropuestasMias(int $idUsuario, int $page, int $limit, ?string $estatus = null, ?string $search = null): array {
        $proveedor = $this->provRepo->findByUsuario($idUsuario);
        if (!$proveedor) {
            return ['ok' => false, 'errors' => ['El usuario no tiene un perfil de proveedor registrado.']];
        }

        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $normalizedEstatus = ($estatus !== null && trim($estatus) !== '') ? strtoupper(trim($estatus)) : null;
        $normalizedSearch = ($search !== null && trim($search) !== '') ? trim($search) : null;

        return [
            'ok' => true,
            'data' => $this->propRepo->findByProveedorForPortal(
                (int) $proveedor['id_proveedor'],
                $page,
                $limit,
                $normalizedEstatus,
                $normalizedSearch
            ),
        ];
    }

    public function listPropuestas(?int $idLicitacion = null): array {
        return $this->propRepo->findAll($idLicitacion);
    }
}
