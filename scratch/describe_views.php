<?php
require_once __DIR__ . '/../Core/Database.php';

$db = Core\Database::getInstance();
print_r($db->query('DESCRIBE views')->fetchAll(PDO::FETCH_ASSOC));
