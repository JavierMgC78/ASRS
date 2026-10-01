<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database;

$db = Database::getInstance();
$sql = "SELECT r.id as role_id, r.name as role_name, v.id as view_id, v.uri, v.menu_title, v.menu_group
        FROM roles r
        LEFT JOIN role_view_permissions rvp ON r.id = rvp.role_id
        LEFT JOIN views v ON rvp.view_id = v.id
        ORDER BY r.id, v.id";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    echo sprintf("Rol %-15s (#%d) -> Vista: %-20s (%s)\n", 
        $row['role_name'], $row['role_id'], $row['menu_title'] ?? 'SIN VISTAS', $row['uri'] ?? 'N/A');
}
