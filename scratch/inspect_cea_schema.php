<?php
require_once __DIR__ . '/../Core/Database.php';

$db = Core\Database::getInstance();
$tables = ['alumnos_cea', 'alumnos_plataforma_cea', 'facturacion_cea', 'tutores_cea', 'personas_autorizadas_cea'];

foreach ($tables as $table) {
    echo "=== TABLE: $table ===\n";
    $cols = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "{$c['Field']} - {$c['Type']} - Null: {$c['Null']} - Key: {$c['Key']} - Default: {$c['Default']}\n";
    }
    echo "\n";
}
