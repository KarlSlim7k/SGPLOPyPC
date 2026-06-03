<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/env_loader.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../config/database.php';

$migrationDir = __DIR__ . '/../database/migrations';
$schemaMigrations = [
    '003_fase5_indices.sql',
    '004_fase6_publico_completo.sql',
    '005_aclaraciones.sql',
    '006_contrato_firma.sql',
    '008_fase2_separacion_datos_test.sql',
    '009_fase4_integridad_indices.sql',
    '010_cleanup_e2e_data.sql',
    '011_fix_demo_presentacion.sql',
    '018_soporte_tickets_proveedor.sql',
    '019_propuesta_estatus_retirada.sql',
];

if (getenv('RUN_SEED_MIGRATIONS') === '1') {
    $schemaMigrations = array_merge(
        ['001_fase1_seed_usuarios.sql', '002_fase2_seed_dependencias.sql'],
        $schemaMigrations,
        ['007_seed_muestra.sql']
    );
}

function splitSqlStatements(string $sql): array {
    $statements = [];
    $buffer = '';
    $quote = null;
    $escaped = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i += 1) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote === null && $char === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i += 1;
            }
            continue;
        }

        if ($quote === null && $char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i += 1;
            }
            continue;
        }

        if ($quote === null && $char === '/' && $next === '*') {
            $i += 2;
            while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i += 1;
            }
            $i += 1;
            continue;
        }

        if ($quote !== null) {
            $buffer .= $char;
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            if ($char === $quote) {
                $quote = null;
            }
            continue;
        }

        if ($char === '\'' || $char === '"') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

echo "Ejecutando migraciones de esquema...\n";
$db = getDbConnection();

foreach ($schemaMigrations as $migration) {
    $path = $migrationDir . '/' . $migration;
    if (!is_file($path)) {
        fwrite(STDERR, "Migracion no encontrada: {$migration}\n");
        exit(1);
    }

    echo "Aplicando {$migration}\n";
    $sql = (string) file_get_contents($path);
    foreach (splitSqlStatements($sql) as $statement) {
        $result = $db->query($statement);
        if ($result instanceof PDOStatement) {
            $result->closeCursor();
        }
    }
}

echo "Migraciones completadas.\n";
