<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Router;
use Core\controllers\LoginController;

// Simular sesión de Super Admin
$_SESSION['user'] = [
    'id'        => 1,
    'name'      => 'Javier Administrador',
    'role_id'   => 1,
    'role_name' => 'super_admin',
    'role_type' => 'special'
];

echo "1. Simulando flujo de login con sesión previa activa...\n";
$target = Router::getAuthorizedLandingUrl(1, 1, 'special');
echo "   Landing URL resuelta: {$target}\n";
assert(str_contains($target, 'dashboard'), "Debe redirigir al dashboard");

echo "\n2. Simulando renderizado del Dashboard tras clic en 'Acceso Privado'...\n";
$_SERVER['REQUEST_URI'] = '/admin/dashboard';
$_SERVER['SCRIPT_NAME'] = '/index.php';

ob_start();
$viewData = [
    'menu_title' => 'Dashboard',
    'layout_type' => 'private'
];
require __DIR__ . '/../views/private/principal/dashboard.php';
$html = ob_get_clean();

echo "   Longitud HTML: " . strlen($html) . " bytes\n";
assert(str_contains($html, 'Bienvenido, Javier Administrador'), "Debe contener nombre");
assert(str_contains($html, 'dashboard-grid'), "Debe contener dashboard-grid");

echo "\n3. Verificando que userCanAccess devuelva booleanos estrictos:\n";
$res1 = Router::userCanAccess(14);
$res2 = Router::userCanAccess('alumnos/ver');
echo "   userCanAccess(14): " . var_export($res1, true) . " (" . gettype($res1) . ")\n";
echo "   userCanAccess('alumnos/ver'): " . var_export($res2, true) . " (" . gettype($res2) . ")\n";
assert(is_bool($res1) && is_bool($res2));

echo "\n=== TODAS LAS PRUEBAS DE INTEGRACIÓN DEL ACCESO PRIVADO PASARON CON ÉXITO ===\n";
