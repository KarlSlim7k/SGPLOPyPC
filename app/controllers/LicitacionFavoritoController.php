<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/LicitacionFavoritoService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class LicitacionFavoritoController {
    private LicitacionFavoritoService $service;

    public function __construct() {
        $this->service = new LicitacionFavoritoService();
    }

    public function agregar(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['id_licitacion'])) {
            jsonResponse(false, 'Se requiere el campo id_licitacion.', null, ['id_licitacion es obligatorio.'], 400);
        }
        $idLicitacion = (int) $input['id_licitacion'];
        $result = $this->service->agregar((int) $user['id_usuario'], $idLicitacion);
        if (!$result['ok']) {
            jsonResponse(false, 'No se pudo agregar a favoritos.', null, $result['errors'], 422);
        }
        jsonResponse(true, 'Licitación agregada a favoritos.', $result['data'], null, 201);
    }

    public function quitar(int $idLicitacion): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $result = $this->service->quitar((int) $user['id_usuario'], $idLicitacion);
        if (!$result['ok']) {
            $status = $result['status'] ?? 422;
            jsonResponse(false, 'No se pudo quitar de favoritos.', null, $result['errors'], $status);
        }
        jsonResponse(true, 'Licitación quitada de favoritos.', null, null, 200);
    }

    public function listar(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $data = $this->service->listar((int) $user['id_usuario'], $page, $perPage);
        jsonResponse(true, 'Favoritos del usuario.', $data, null, 200);
    }

    public function contar(): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $count = $this->service->contar((int) $user['id_usuario']);
        jsonResponse(true, 'Conteo de favoritos.', ['total' => $count], null, 200);
    }

    public function esFavorito(int $idLicitacion): never {
        $user = AuthMiddleware::getAuthenticatedUser();
        $esFav = $this->service->esFavorito((int) $user['id_usuario'], $idLicitacion);
        jsonResponse(true, 'Estado de favorito.', ['es_favorito' => $esFav], null, 200);
    }
}
