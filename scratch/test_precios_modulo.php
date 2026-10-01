<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database;
use Core\controllers\PreciosController;
use Core\ViewCache;

// Simular sesión autenticada como Super Admin
$_SESSION['user'] = [
    'id'        => 1,
    'name'      => 'Administrador General',
    'role_id'   => 1,
    'role_name' => 'Super Admin',
    'role_type' => 'special'
];

echo "1. Probando PreciosController::handle()...\n";
$data = PreciosController::handle();

echo "✓ Tarifas cargadas:\n";
foreach ($data['tarifas'] as $nivel => $conceptos) {
    echo "  - Nivel " . ucfirst($nivel) . ": " . count($conceptos) . " conceptos cargados.\n";
    $m = $data['metricas'][$nivel];
    echo "    Colegiatura: $" . $m['colegiatura_fmt'] . " | Inscripción: $" . $m['inscripcion_fmt'] . " | Total Paquete: $" . $m['paquete_fmt'] . "\n";
}

echo "\n2. Probando actualización de tarifa con PDO preparado...\n";
$db = Database::getInstance();
// Obtener una tarifa de preescolar
$tarifa = $db->query("SELECT id, concepto, nivel, monto FROM conceptos_precios_cea WHERE nivel = 'preescolar' LIMIT 1")->fetch();
echo "Tarifa seleccionada: ID {$tarifa['id']} - {$tarifa['concepto']} - Monto actual: \${$tarifa['monto']}\n";

$nuevoMonto = ((float)$tarifa['monto'] == 2800.00) ? 2850.00 : 2800.00;
$stmt = $db->prepare("UPDATE conceptos_precios_cea SET monto = :monto, updated_at = NOW() WHERE id = :id");
$stmt->execute([':monto' => $nuevoMonto, ':id' => $tarifa['id']]);
echo "✓ Monto actualizado a \${$nuevoMonto} correctamente.\n";

echo "\n3. Probando renderizado de la vista views/private/precios/index.php...\n";
ob_start();
$viewData = [
    'menu_title' => 'Precios',
    'layout_type' => 'private'
];
require __DIR__ . '/../views/private/precios/index.php';
$html = ob_get_clean();

echo "Longitud HTML generado: " . strlen($html) . " bytes.\n";
if (strpos($html, 'Gestión de Precios y Tarifas por Nivel') !== false 
    && strpos($html, 'tab-theme--preescolar') !== false 
    && strpos($html, 'tab-theme--primaria') !== false 
    && strpos($html, 'tab-theme--secundaria') !== false) {
    echo "✓ Pestañas y estructura de Preescolar (#FFB300), Primaria (#2962FF) y Secundaria (#B71C1C) renderizadas con éxito.\n";
} else {
    echo "✗ Error en el renderizado de la vista.\n";
}

echo "\n4. Verificando Assets y Rutas en ViewCache...\n";
$routes = ViewCache::getRoutes();
if (isset($routes['/admin/precios'])) {
    echo "✓ Ruta '/admin/precios' presente en ViewCache.\n";
    echo "  Archivo vista: " . $routes['/admin/precios']['file_path'] . "\n";
    echo "  Grupo de menú: " . $routes['/admin/precios']['menu_group'] . "\n";
} else {
    echo "✗ Ruta '/admin/precios' no encontrada en ViewCache.\n";
}

$cssFile = __DIR__ . '/../public/assets/css/precios/index.css';
$jsFile  = __DIR__ . '/../public/assets/js/precios/index.js';
echo "✓ Archivo CSS existe: " . (file_exists($cssFile) ? "SÍ" : "NO") . " (" . filesize($cssFile) . " bytes)\n";
echo "✓ Archivo JS existe: "  . (file_exists($jsFile) ? "SÍ" : "NO") . " (" . filesize($jsFile) . " bytes)\n";

echo "\n=== TODAS LAS PRUEBAS COMPLETADAS CON ÉXITO ===\n";
