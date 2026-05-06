<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ProveedorRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

class ProveedorService {
    private ProveedorRepository $repo;
    private UserRepository $userRepo;

    public function __construct() {
        $this->repo = new ProveedorRepository();
        $this->userRepo = new UserRepository();
    }

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): ?array {
        return $this->repo->findById($id);
    }

    public function create(array $input, int $idUsuario): array {
        $errors = $this->validate($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        if ($this->repo->findByUsuario($idUsuario)) {
            return ['ok' => false, 'errors' => ['El usuario ya tiene un perfil de proveedor registrado.']];
        }
        if ($this->repo->findByRegistroFiscal(trim($input['registro_fiscal']))) {
            return ['ok' => false, 'errors' => ['El registro fiscal ya está registrado.']];
        }
        $data = [
            'id_usuario' => $idUsuario,
            'nombre_empresa' => trim($input['nombre_empresa']),
            'representante_legal' => trim($input['representante_legal']),
            'registro_fiscal' => trim($input['registro_fiscal']),
            'domicilio' => trim($input['domicilio']),
            'telefono' => isset($input['telefono']) ? trim($input['telefono']) : null,
            'especialidad' => isset($input['especialidad']) ? trim($input['especialidad']) : null,
            'estatus' => 'PENDIENTE',
        ];
        $id = $this->repo->create($data);
        auditLog($idUsuario, 'proveedor', $id, 'CREAR', null, $data);
        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Proveedor no encontrado.']];
        }
        $errors = $this->validate($input, true);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }
        if (isset($input['registro_fiscal'])) {
            $dup = $this->repo->findByRegistroFiscal(trim($input['registro_fiscal']));
            if ($dup && (int) $dup['id_proveedor'] !== $id) {
                return ['ok' => false, 'errors' => ['El registro fiscal ya está registrado.']];
            }
        }
        $data = [];
        $fields = ['nombre_empresa', 'representante_legal', 'registro_fiscal', 'domicilio', 'telefono', 'especialidad'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $input)) {
                $data[$f] = is_string($input[$f]) ? trim($input[$f]) : $input[$f];
            }
        }
        $this->repo->update($id, $data);
        auditLog($idUsuario, 'proveedor', $id, 'ACTUALIZAR', $existing, $data);
        return ['ok' => true];
    }

    public function cambiarEstatus(int $id, string $nuevoEstatus, int $idUsuario): array {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['Proveedor no encontrado.']];
        }
        $estadosPermitidos = ['PENDIENTE','VALIDADO','RECHAZADO','SUSPENDIDO'];
        if (!in_array($nuevoEstatus, $estadosPermitidos, true)) {
            return ['ok' => false, 'errors' => ['Estatus no válido.']];
        }
        $this->repo->update($id, ['estatus' => $nuevoEstatus]);
        auditLog($idUsuario, 'proveedor', $id, 'ACTUALIZAR', ['estatus' => $existing['estatus']], ['estatus' => $nuevoEstatus]);
        return ['ok' => true];
    }

    private function validate(array $input, bool $isUpdate = false): array {
        $errors = [];
        if (!$isUpdate || array_key_exists('nombre_empresa', $input)) {
            if (trim($input['nombre_empresa'] ?? '') === '') $errors[] = 'El nombre de la empresa es obligatorio.';
        }
        if (!$isUpdate || array_key_exists('representante_legal', $input)) {
            if (trim($input['representante_legal'] ?? '') === '') $errors[] = 'El representante legal es obligatorio.';
        }
        if (!$isUpdate || array_key_exists('registro_fiscal', $input)) {
            if (trim($input['registro_fiscal'] ?? '') === '') $errors[] = 'El registro fiscal es obligatorio.';
        }
        if (!$isUpdate || array_key_exists('domicilio', $input)) {
            if (trim($input['domicilio'] ?? '') === '') $errors[] = 'El domicilio es obligatorio.';
        }
        return $errors;
    }
}
