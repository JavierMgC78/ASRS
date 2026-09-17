<?php

/**
 * ASRS - Axe Secure Router System
 * Front Controller
 */

// 1. Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use Core\ViewCache;

// 2. Obtener el mapa de rutas gestionado por el núcleo
$routes = ViewCache::getRoutes();

// 3. Obtener y limpiar la URI solicitada por el usuario
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 4. Pruebas de enrutamiento
echo "<h3>ASRS Core en ejecución...</h3>";
echo "<p>Ruta solicitada: <strong>" . htmlspecialchars($requestUri) . "</strong></p>";
echo "<p style='color: green;'>✔ Caché gestionado de forma limpia por Core\ViewCache (" . count($routes) . " rutas).</p>";

if (isset($routes[$requestUri])) {
    echo "<p style='color: blue;'>✔ Ruta encontrada. Archivo físico: <strong>" . $routes[$requestUri]['file_path'] . "</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠ Ruta no registrada (404 Not Found).</p>";
}