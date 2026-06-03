<?php
require_once __DIR__ . '/../config/database.php';
$db = getDbConnection();
$sql = file_get_contents(__DIR__ . '/../database/migrations/018_soporte_tickets_proveedor.sql');
$db->exec($sql);
echo "OK\n";
