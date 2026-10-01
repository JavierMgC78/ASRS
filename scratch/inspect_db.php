<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database;

try {
    $db = Database::getInstance();
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas en la BD:\n";
    foreach ($tables as $t) {
        echo " - " . $t . "\n";
    }

    echo "\nEstructura de conceptos_pago_cea:\n";
    $cols = $db->query("DESCRIBE conceptos_pago_cea")->fetchAll();
    foreach ($cols as $c) {
        echo sprintf(" %-20s | %-15s\n", $c['Field'], $c['Type']);
    }

    echo "\nRegistros en conceptos_pago_cea:\n";
    $conceptos = $db->query("SELECT * FROM conceptos_pago_cea")->fetchAll();
    foreach ($conceptos as $c) {
        print_r($c);
    }

    echo "\nEstructura de role_views:\n";
    $cols = $db->query("DESCRIBE role_views")->fetchAll();
    foreach ($cols as $c) {
        echo sprintf(" %-20s | %-15s\n", $c['Field'], $c['Type']);
    }

    echo "\nEstructura de role_view_permissions:\n";
    $cols = $db->query("DESCRIBE role_view_permissions")->fetchAll();
    foreach ($cols as $c) {
        echo sprintf(" %-20s | %-15s\n", $c['Field'], $c['Type']);
    }

    echo "\nPermisos para ID 16 (caja/capturar):\n";
    $rv = $db->query("SELECT * FROM role_views WHERE view_id = 16")->fetchAll();
    print_r($rv);
    $rvp = $db->query("SELECT * FROM role_view_permissions WHERE view_id = 16")->fetchAll();
    print_r($rvp);

    echo "\nGrupos de Menú:\n";
    $groups = $db->query("SELECT * FROM menu_groups")->fetchAll();
    foreach ($groups as $g) {
        echo sprintf(" ID %d | %-15s | orden: %s\n", $g['id'], $g['name'], $g['display_order'] ?? 'N/A');
    }

    echo "\nRoles:\n";
    $roles = $db->query("SELECT * FROM roles")->fetchAll();
    foreach ($roles as $r) {
        echo sprintf(" ID %d | %-15s\n", $r['id'], $r['name']);
    }

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
