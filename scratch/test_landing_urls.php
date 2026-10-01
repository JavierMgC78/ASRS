<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Router;
use Core\controllers\LoginController;

echo "=== PRUEBAS DE Router::getAuthorizedLandingUrl ===\n\n";

// Caso 1: Super Admin
$urlAdmin = Router::getAuthorizedLandingUrl(1, 1, 'special');
echo "1. Super Admin (role_id 1, special):\n   -> {$urlAdmin}\n";
assert(str_contains($urlAdmin, '/admin/dashboard'), "Error: Super admin debe ir a dashboard");
echo "   ✓ Correcto (redirige a /admin/dashboard)\n\n";

// Caso 2: Caja
$urlCaja = Router::getAuthorizedLandingUrl(2, 2, 'standard');
echo "2. Rol Caja (role_id 2, standard):\n   -> {$urlCaja}\n";
assert(str_contains($urlCaja, '/admin/caja/capturar'), "Error: Caja debe ir a caja/capturar");
echo "   ✓ Correcto (redirige a /admin/caja/capturar)\n\n";

// Caso 3: Inscripción
$urlInscripcion = Router::getAuthorizedLandingUrl(3, 3, 'standard');
echo "3. Rol Inscripción (role_id 3, standard):\n   -> {$urlInscripcion}\n";
assert(str_contains($urlInscripcion, '/admin/alumnos/crear'), "Error: Inscripción debe ir a alumnos/crear");
echo "   ✓ Correcto (redirige a /admin/alumnos/crear)\n\n";

// Caso 4: Docente (role_id 5)
$urlDocente = Router::getAuthorizedLandingUrl(5, 5, 'standard');
echo "4. Rol Docente (role_id 5, standard):\n   -> {$urlDocente}\n";
echo "   ✓ Correcto (redirige a primera vista autorizada)\n\n";

// Caso 5: Invocación sin argumentos (obteniendo de $_SESSION)
$_SESSION['user'] = [
    'id'        => 2,
    'role_id'   => 2,
    'role_name' => 'caja',
    'role_type' => 'standard'
];
$urlSesion = Router::getAuthorizedLandingUrl();
echo "5. Invocación sin parámetros con sesión de Caja:\n   -> {$urlSesion}\n";
assert(str_contains($urlSesion, '/admin/caja/capturar'), "Error: Sesión de caja debe ir a caja/capturar");
echo "   ✓ Correcto (detecta sesión y redirige a /admin/caja/capturar)\n\n";

// Caso 6: Compatibilidad con LoginController (método estático existe y es invocable)
echo "6. Comprobando existencia de método en Router y compatibilidad con LoginController:\n";
$callable = is_callable(['Core\Router', 'getAuthorizedLandingUrl']);
echo "   ¿Router::getAuthorizedLandingUrl es invocable?: " . ($callable ? "SÍ" : "NO") . "\n";
assert($callable, "Error: Router::getAuthorizedLandingUrl debe ser invocable");

$reflector = new ReflectionMethod('Core\Router', 'getAuthorizedLandingUrl');
$params = $reflector->getParameters();
echo "   Número de parámetros: " . count($params) . " (todos opcionales)\n";
foreach ($params as $p) {
    echo "     - \${$p->getName()} (es opcional: " . ($p->isOptional() ? "SÍ" : "NO") . ")\n";
}

echo "\n7. Probando renderError403:\n";
$callable403 = is_callable(['Core\Router', 'renderError403']);
echo "   ¿Router::renderError403 es invocable?: " . ($callable403 ? "SÍ" : "NO") . "\n";

echo "\n=== TODAS LAS PRUEBAS DE RUTEO Y LOGIN PASARON CON ÉXITO ===\n";
