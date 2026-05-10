<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/AclaracionRepository.php';
require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../repositories/LicitacionRepository.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RoleMiddleware.php';

class AclaracionController {
    private AclaracionRepository $repo;
    private ProveedorRepository $provRepo;
    private LicitacionRepository $licRepo;

    public function __construct() {
        $this->repo    = new AclaracionRepository();
        $this->provRepo = new ProveedorRepository();
        $this->licRepo  = new LicitacionRepository();
    }

    /** GET /licitaciones/{id}/aclaraciones — proveedor ve las suyas; admin ve todas */
    public function list(int $idLicitacion): never {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if ($user['rol'] === 'ADMINISTRADOR') {
            $items = $this->repo->findByLicitacion($idLicitacion);
        } else {
            $proveedor = $this->provRepo->findByUsuario((int) $user['id_usuario']);
            if (!$proveedor) {
                jsonResponse(false, 'Perfil de proveedor no encontrado.', null, null, 404);
            }
            $items = $this->repo->findByLicitacionAndProveedor($idLicitacion, (int) $proveedor['id_proveedor']);
        }

        jsonResponse(true, 'Aclaraciones', $items, null, 200);
    }

    /** POST /licitaciones/{id}/aclaraciones — solo PROVEEDOR */
    public function create(int $idLicitacion): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('PROVEEDOR');
        $user = AuthMiddleware::getAuthenticatedUser();

        $proveedor = $this->provRepo->findByUsuario((int) $user['id_usuario']);
        if (!$proveedor) {
            jsonResponse(false, 'Perfil de proveedor no encontrado.', null, null, 404);
        }

        $licitacion = $this->licRepo->findById($idLicitacion);
        if (!$licitacion) {
            jsonResponse(false, 'Licitación no encontrada.', null, null, 404);
        }
        if ($licitacion['estado_proceso'] !== 'EN_ACLARACIONES') {
            jsonResponse(false, 'La licitación no está en fase de aclaraciones.', null, ['estado_actual' => $licitacion['estado_proceso']], 422);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $pregunta = trim((string) ($input['pregunta'] ?? ''));
        if ($pregunta === '') {
            jsonResponse(false, 'La pregunta es obligatoria.', null, ['El campo pregunta no puede estar vacío.'], 422);
        }

        $id = $this->repo->create($idLicitacion, (int) $proveedor['id_proveedor'], $pregunta);
        jsonResponse(true, 'Aclaración enviada exitosamente.', ['id_aclaracion' => $id], null, 201);
    }

    /** PATCH /aclaraciones/{id}/respuesta — solo ADMINISTRADOR */
    public function responder(int $id): never {
        AuthMiddleware::handle();
        RoleMiddleware::handle('ADMINISTRADOR');
        $user = AuthMiddleware::getAuthenticatedUser();

        $aclaracion = $this->repo->findById($id);
        if (!$aclaracion) {
            jsonResponse(false, 'Aclaración no encontrada.', null, null, 404);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $respuesta = trim((string) ($input['respuesta'] ?? ''));
        if ($respuesta === '') {
            jsonResponse(false, 'La respuesta es obligatoria.', null, ['El campo respuesta no puede estar vacío.'], 422);
        }

        $this->repo->responder($id, $respuesta, (int) $user['id_usuario']);
        jsonResponse(true, 'Respuesta registrada exitosamente.', null, null, 200);
    }
}
