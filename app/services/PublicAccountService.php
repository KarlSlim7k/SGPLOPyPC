<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/Mailer.php';
require_once __DIR__ . '/../repositories/SupportTicketRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class PublicAccountService {
    private PDO $db;
    private JwtHelper $jwt;
    private UserRepository $userRepo;
    private SupportTicketRepository $supportRepo;
    private Mailer $mailer;

    public function __construct() {
        $this->db = getDbConnection();
        $this->jwt = new JwtHelper();
        $this->userRepo = new UserRepository();
        $this->supportRepo = new SupportTicketRepository();
        $this->mailer = new Mailer();
    }

    public function registerProveedorPublico(array $input, ?string $requestIp): array {
        $errors = $this->validateRegistro($input);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $email = strtolower(trim((string) $input['email']));
        if ($this->userRepo->findByEmail($email)) {
            return ['ok' => false, 'errors' => ['El correo electrónico ya está registrado.']];
        }

        $registroFiscal = strtoupper(trim((string) $input['registro_fiscal']));
        $stmtDup = $this->db->prepare('SELECT id_proveedor FROM proveedor WHERE registro_fiscal = :registro_fiscal LIMIT 1');
        $stmtDup->execute(['registro_fiscal' => $registroFiscal]);
        if ($stmtDup->fetch(PDO::FETCH_ASSOC)) {
            return ['ok' => false, 'errors' => ['El registro fiscal ya se encuentra registrado.']];
        }

        $representanteLegal = trim((string) ($input['representante_legal'] ?? $input['nombre_contacto'] ?? ''));
        $especialidades = isset($input['especialidades']) && is_array($input['especialidades'])
            ? implode('|', array_map(static fn($s) => trim((string) $s), $input['especialidades']))
            : null;

        $passwordHash = password_hash((string) $input['password'], PASSWORD_BCRYPT);

        try {
            $this->db->beginTransaction();

            $stmtUser = $this->db->prepare(
                'INSERT INTO usuario (nombre, email, contrasena_hash, rol, activo, fecha_registro, ultimo_acceso) '
                . 'VALUES (:nombre, :email, :contrasena_hash, :rol, :activo, NOW(), NULL)'
            );
            $stmtUser->execute([
                'nombre' => trim((string) $input['nombre_contacto']),
                'email' => $email,
                'contrasena_hash' => $passwordHash,
                'rol' => 'PROVEEDOR',
                'activo' => 1,
            ]);
            $idUsuario = (int) $this->db->lastInsertId();

            $stmtProv = $this->db->prepare(
                'INSERT INTO proveedor (id_usuario, nombre_empresa, representante_legal, registro_fiscal, regimen_fiscal, domicilio, telefono, contacto_cargo, contacto_email, especialidad, estatus, fecha_registro) '
                . 'VALUES (:id_usuario, :nombre_empresa, :representante_legal, :registro_fiscal, :regimen_fiscal, :domicilio, :telefono, :contacto_cargo, :contacto_email, :especialidad, :estatus, NOW())'
            );
            $stmtProv->execute([
                'id_usuario' => $idUsuario,
                'nombre_empresa' => trim((string) $input['nombre_empresa']),
                'representante_legal' => $representanteLegal,
                'registro_fiscal' => $registroFiscal,
                'regimen_fiscal' => trim((string) $input['regimen_fiscal']),
                'domicilio' => trim((string) $input['domicilio']),
                'telefono' => trim((string) ($input['telefono'] ?? '')),
                'contacto_cargo' => trim((string) ($input['cargo'] ?? '')),
                'contacto_email' => $email,
                'especialidad' => $especialidades,
                'estatus' => 'PENDIENTE',
            ]);
            $idProveedor = (int) $this->db->lastInsertId();

            $this->db->commit();

            $token = $this->jwt->encode([
                'sub' => $idUsuario,
                'rol' => 'PROVEEDOR',
            ]);

            auditLog($idUsuario, 'usuario', $idUsuario, 'CREAR', null, ['email' => $email, 'rol' => 'PROVEEDOR']);
            auditLog($idUsuario, 'proveedor', $idProveedor, 'CREAR', null, ['estatus' => 'PENDIENTE', 'request_ip' => $requestIp]);

            return [
                'ok' => true,
                'data' => [
                    'token' => $token,
                    'usuario' => [
                        'id_usuario' => $idUsuario,
                        'nombre' => trim((string) $input['nombre_contacto']),
                        'email' => $email,
                        'rol' => 'PROVEEDOR',
                    ],
                    'proveedor' => [
                        'id_proveedor' => $idProveedor,
                        'id_usuario' => $idUsuario,
                        'nombre_empresa' => trim((string) $input['nombre_empresa']),
                        'estatus' => 'PENDIENTE',
                    ],
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'errors' => ['No se pudo completar el registro. Intenta nuevamente.']];
        }
    }

    public function createSupportTicket(array $input): array {
        $errors = [];
        $nombre = trim((string) ($input['nombre'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $telefono = trim((string) ($input['telefono'] ?? ''));
        $asunto = trim((string) ($input['asunto'] ?? ''));
        $mensaje = trim((string) ($input['mensaje'] ?? ''));

        if ($nombre === '') $errors[] = 'El nombre es obligatorio.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo electrónico es inválido.';
        if ($asunto === '') $errors[] = 'El asunto es obligatorio.';
        if ($mensaje === '') $errors[] = 'El mensaje es obligatorio.';

        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $folio = 'SUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $id = $this->supportRepo->create([
            'folio' => $folio,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono !== '' ? $telefono : null,
            'asunto' => $asunto,
            'mensaje' => $mensaje,
            'estado' => 'NUEVO',
        ]);

        $supportTo = env('SUPPORT_EMAIL_TO', '');
        if ($supportTo !== '') {
            $body = "Nuevo ticket de soporte\n\nFolio: {$folio}\nNombre: {$nombre}\nEmail: {$email}\nTeléfono: {$telefono}\nAsunto: {$asunto}\n\nMensaje:\n{$mensaje}";
            $this->mailer->send($supportTo, 'Nuevo ticket de soporte ' . $folio, $body);
        }

        return ['ok' => true, 'data' => ['id_soporte_ticket' => $id, 'folio' => $folio]];
    }

    private function validateRegistro(array $input): array {
        $errors = [];

        $required = [
            'nombre_empresa',
            'representante_legal',
            'registro_fiscal',
            'regimen_fiscal',
            'domicilio',
            'nombre_contacto',
            'cargo',
            'email',
            'telefono',
            'password',
        ];

        foreach ($required as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                $errors[] = "El campo {$field} es obligatorio.";
            }
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico es inválido.';
        }

        $password = (string) ($input['password'] ?? '');
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        // Campo canónico: accepted_terms. Campo legacy: terms.
        // Se exige booleano true de forma estricta.
        if (array_key_exists('accepted_terms', $input)) {
            $acceptedTerms = ($input['accepted_terms'] === true);
        } elseif (array_key_exists('terms', $input)) {
            $acceptedTerms = ($input['terms'] === true);
        } else {
            $acceptedTerms = false;
        }
        if (!$acceptedTerms) {
            $errors[] = 'Debes aceptar los términos de uso y aviso de privacidad.';
        }

        return $errors;
    }
}
