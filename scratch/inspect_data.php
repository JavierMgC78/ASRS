<?php
require_once __DIR__ . '/../Core/Database.php';

$db = Core\Database::getInstance();
$alumnos = $db->query('SELECT id, curp, nombre, primer_apellido, segundo_apellido, grado, grupo, nivel_educativo FROM alumnos_cea LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
echo "ALUMNOS:\n";
print_r($alumnos);

$tutores = $db->query('SELECT * FROM tutores_cea LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
echo "TUTORES:\n";
print_r($tutores);

$fact = $db->query('SELECT * FROM facturacion_cea LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
echo "FACTURACION:\n";
print_r($fact);
