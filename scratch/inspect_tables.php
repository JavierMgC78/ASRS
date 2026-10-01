<?php
require_once __DIR__ . '/../Core/Database.php';

$db = Core\Database::getInstance();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES IN DB:\n";
foreach ($tables as $t) {
    echo "- $t\n";
}

$roles = $db->query('SELECT * FROM roles')->fetchAll(PDO::FETCH_ASSOC);
echo "\nROLES IN DB:\n";
foreach ($roles as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Type: {$r['type']}\n";
}
