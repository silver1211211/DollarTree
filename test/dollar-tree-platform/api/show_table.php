<?php
require_once __DIR__ . '/../config.php';
global $pdo;

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$schema = [];
foreach ($tables as $table) {
    $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    $schema[$table] = array_column($cols, 'Field');
}
header('Content-Type: application/json');
echo json_encode($schema, JSON_PRETTY_PRINT);