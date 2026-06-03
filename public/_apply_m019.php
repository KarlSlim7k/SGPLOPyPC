<?php
require_once __DIR__ . '/../config/database.php';
$db = getDbConnection();
$sql = file_get_contents(__DIR__ . '/../database/migrations/019_propuesta_estatus_retirada.sql');
$db->exec($sql);
echo "OK\n";
