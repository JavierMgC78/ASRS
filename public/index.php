<?php
// Kernel ASRS v1.0 - Front controller
require_once __DIR__ . '/../asrsCore/Helpers/global_functions.php';

use asrsCore\Core\CacheAppsBuilder;
use asrsCore\Core\CacheRoutesBuilder;
use asrsCore\Core\RoutingEngine;

// URI solicitada (Apache .htaccess -> index.php?uri=...)
$uri = $_GET['uri'] ?? '';

// 1. Caché de aplicaciones: se genera solo si no existe.
$appsCacheFile = __DIR__ . '/../asrsCore/Cache/apps.php';
$apps = CacheAppsBuilder::buildIfNotExists($appsCacheFile)
    ? (require $appsCacheFile)
    : [];

// 2. Caché de rutas: se genera solo si no existe; si existe no se toca.
$routesCacheFile = __DIR__ . '/../asrsCore/Cache/routes.php';
// 3. Arreglo en memoria para el RoutingEngine
$routes = CacheRoutesBuilder::buildIfNotExists($routesCacheFile)
    ? (require $routesCacheFile)
    : [];

$route = RoutingEngine::resolve($uri, $routes, $apps);

if ($route === null) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404</title></head>'
       . '<body style="font-family:sans-serif;text-align:center;padding:4rem">'
       . '<h1>404</h1><p>La página solicitada no existe.</p></body></html>';
    exit;
}

// <base href> para que los assets y enlaces relativos funcionen en cualquier profundidad de URI
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$baseUrl   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
           . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/';

// Variables disponibles en el layout: $route, $baseUrl, $content, $cssFiles, $jsFiles
$contentFile = $route['contentFile'];
$cssFiles    = $route['cssFiles'];
$jsFiles     = $route['jsFiles'];
$currentUri  = trim($uri, '/');

// Renderiza la vista de contenido con Output Buffering y la incrusta en el layout maestro
ob_start();
require $contentFile;
$content = ob_get_clean();

require $route['layoutFile'];