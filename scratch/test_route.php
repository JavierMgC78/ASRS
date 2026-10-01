<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\ViewCache;

$routes = ViewCache::getRoutes();
print_r($routes['/admin/precios'] ?? 'NO ENCONTRADA');
