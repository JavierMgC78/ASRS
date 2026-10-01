<?php
require_once __DIR__ . '/../Core/Database.php';

$db = Core\Database::getInstance();
print_r($db->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC));
print_r($db->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC));
