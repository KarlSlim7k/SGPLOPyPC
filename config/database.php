<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/response.php';

function getDbConnection(): PDO {
    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
    $db   = env('DB_NAME', 'sgplopypc');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        jsonResponse(false, 'Error de conexión a base de datos', null, [$e->getMessage()], 500);
    }
}
