<?php
/**
 * ASRS - Front Controller
 * Punto de entrada único para todas las peticiones HTTP del sistema.
 */

// 1. Cargar el autoloader de Composer para la gestión automática de clases (PSR-4)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Delegar la ejecución completa al núcleo del Enrutador
Core\Router::dispatch();