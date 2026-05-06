<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/response.php';

function getDbConnection(): PDO {
    $host = env('DB_HOST');
    $port = env('DB_PORT');
    $db   = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS');
    $charset = 'utf8mb4';

    $missing = [];
    if ($host === '') $missing[] = 'DB_HOST';
    if ($port === '') $missing[] = 'DB_PORT';
    if ($db === '')   $missing[] = 'DB_NAME';
    if ($user === '') $missing[] = 'DB_USER';
    if ($pass === '') $missing[] = 'DB_PASS';

    if (!empty($missing)) {
        jsonResponse(false, 'Configuración de base de datos incompleta', null, ['Faltan variables: ' . implode(', ', $missing)], 500);
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log('DB connection error: ' . $e->getMessage());
        jsonResponse(false, 'Error de conexión a base de datos', null, null, 500);
    }
}
