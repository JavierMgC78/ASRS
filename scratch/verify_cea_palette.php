<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/ViewCache.php';
require_once __DIR__ . '/../Core/PermissionCache.php';
require_once __DIR__ . '/../Core/Router.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';
require_once __DIR__ . '/../Core/controllers/AlumnosController.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user'] = [
    'id' => 1,
    'role' => 'super_admin',
    'role_name' => 'super_admin',
    'role_type' => 'special'
];

$controller = new \Core\controllers\AlumnosController();
$filters = ['search' => '', 'nivel' => '', 'grado' => '', 'grupo' => '', 'estado' => ''];
$data = $controller->handleList($filters);

echo "Total alumnos cargados: " . count($data['alumnos']) . "\n";
$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/alumnos/ver.css');

$colors = [
    'Preescolar (#FFB300)' => strpos($cssContent, '#FFB300') !== false,
    'Primaria (#2962FF)'   => strpos($cssContent, '#2962FF') !== false,
    'Secundaria (#B71C1C)' => strpos($cssContent, '#B71C1C') !== false,
];

foreach ($colors as $label => $found) {
    echo "Color {$label}: " . ($found ? "VERIFICADO EN CSS" : "FALTA") . "\n";
}

$classes = [
    '--preescolar-cea',
    '--primaria-cea',
    '--secundaria-cea',
    'legend-badge--preescolar',
    'legend-badge--primaria',
    'legend-badge--secundaria',
    'group-header-chip--preescolar',
    'group-header-chip--primaria',
    'group-header-chip--secundaria',
    'alumno-avatar--preescolar',
    'alumno-avatar--primaria',
    'alumno-avatar--secundaria',
    'badge-academico--preescolar',
    'badge-academico--primaria',
    'badge-academico--secundaria',
    'modal-avatar--preescolar',
    'modal-avatar--primaria',
    'modal-avatar--secundaria'
];

echo "\nComprobando clases institucionales CEA en ver.css:\n";
$allFound = true;
foreach ($classes as $cls) {
    $exists = strpos($cssContent, $cls) !== false;
    echo " - {$cls}: " . ($exists ? "OK" : "NO ENCONTRADA") . "\n";
    if (!$exists) $allFound = false;
}

echo "\nResultado global: " . ($allFound ? "ÉXITO TOTAL" : "REVISAR DETALLES") . "\n";
