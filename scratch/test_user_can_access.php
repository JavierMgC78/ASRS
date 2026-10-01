<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Router;
use Core\Database;

echo "=== PRUEBAS DE Router::userCanAccess ===\n\n";

// 1. Prueba con Super Admin (role_id 1, special)
$_SESSION['user'] = [
    'id'        => 1,
    'name'      => 'Super Administrador',
    'role_id'   => 1,
    'role_name' => 'super_admin',
    'role_type' => 'special'
];

echo "1. Evaluando permisos de Super Admin (acceso global):\n";
$canAlumnos = Router::userCanAccess(14); // Alta de Alumnos
$canVer     = Router::userCanAccess(15); // Ver Alumnos
$canRoles   = Router::userCanAccess(6);  // Ver Roles
$canUsers   = Router::userCanAccess(4);  // Usuarios
$canCaja    = Router::userCanAccess(16); // Caja
$canPrecios = Router::userCanAccess(17); // Precios

echo "   - View 14 (Alta Alumno): " . ($canAlumnos ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 15 (Ver Alumnos): " . ($canVer ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 6  (Ver Roles):   " . ($canRoles ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 4  (Usuarios):    " . ($canUsers ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 16 (Caja):        " . ($canCaja ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 17 (Precios):     " . ($canPrecios ? 'PERMITIDO' : 'DENEGADO') . "\n";

assert($canAlumnos && $canVer && $canRoles && $canUsers && $canCaja && $canPrecios, "Error en permisos de Super Admin");
echo "   ✓ Todos permitidos para Super Admin.\n\n";

// 2. Prueba con rol Caja (role_id 2, standard)
$_SESSION['user'] = [
    'id'        => 5,
    'name'      => 'Cajero Turno',
    'role_id'   => 2,
    'role_name' => 'caja',
    'role_type' => 'standard'
];

echo "2. Evaluando permisos de Rol Caja (operativo restringido):\n";
$cajaCanCaja    = Router::userCanAccess(16); // Caja capturar
$cajaCanAlumnos = Router::userCanAccess(14); // Alta de alumnos (no permitido para caja)
$cajaCanVer     = Router::userCanAccess(15); // Ver alumnos (permitido para caja)
$cajaCanRoles   = Router::userCanAccess(6);  // Roles (no permitido para caja)

echo "   - View 16 (Caja capturar): " . ($cajaCanCaja ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 14 (Alta Alumno):   " . ($cajaCanAlumnos ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 15 (Ver Alumnos):   " . ($cajaCanVer ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - View 6  (Roles):         " . ($cajaCanRoles ? 'PERMITIDO' : 'DENEGADO') . "\n";

assert($cajaCanCaja === true, "Caja debe tener acceso a view 16");
assert($cajaCanAlumnos === false, "Caja NO debe tener acceso a view 14");
assert($cajaCanVer === true, "Caja debe tener acceso a view 15");
assert($cajaCanRoles === false, "Caja NO debe tener acceso a view 6");
echo "   ✓ Permisos de Caja validados con precisión según la matriz RBAC.\n\n";

// 3. Prueba por URI como string
echo "3. Evaluando acceso por URI (string):\n";
$uriCanCaja = Router::userCanAccess('caja/capturar');
$uriCanRoles = Router::userCanAccess('roles/ver');
echo "   - 'caja/capturar' para cajero: " . ($uriCanCaja ? 'PERMITIDO' : 'DENEGADO') . "\n";
echo "   - 'roles/ver' para cajero:     " . ($uriCanRoles ? 'PERMITIDO' : 'DENEGADO') . "\n";
assert($uriCanCaja === true && $uriCanRoles === false);
echo "   ✓ Acceso por string URI validado con éxito.\n\n";

// 4. Prueba de renderizado de views/private/principal/dashboard.php
echo "4. Probando renderizado del Dashboard para Super Admin...\n";
$_SESSION['user'] = [
    'id'        => 1,
    'name'      => 'Super Administrador',
    'role_id'   => 1,
    'role_name' => 'super_admin',
    'role_type' => 'special'
];

ob_start();
require __DIR__ . '/../views/private/principal/dashboard.php';
$dashboardHtml = ob_get_clean();

echo "   Longitud HTML Dashboard: " . strlen($dashboardHtml) . " bytes.\n";
assert(str_contains($dashboardHtml, 'dashboard-container'), "Error: Dashboard no contiene container");
assert(str_contains($dashboardHtml, 'Super Administrador'), "Error: Dashboard no contiene nombre de usuario");
echo "   ✓ Dashboard de Super Admin renderizado limpiamente sin Fatal Error.\n\n";

echo "5. Probando renderizado del Dashboard para Cajero...\n";
$_SESSION['user'] = [
    'id'        => 5,
    'name'      => 'Cajero Turno',
    'role_id'   => 2,
    'role_name' => 'caja',
    'role_type' => 'standard'
];

ob_start();
require __DIR__ . '/../views/private/principal/dashboard.php';
$dashboardCajeroHtml = ob_get_clean();

echo "   Longitud HTML Dashboard Cajero: " . strlen($dashboardCajeroHtml) . " bytes.\n";
assert(str_contains($dashboardCajeroHtml, 'dashboard-container'), "Error: Dashboard cajero");
echo "   ✓ Dashboard de Cajero renderizado limpiamente.\n\n";

echo "=== TODAS LAS PRUEBAS DE userCanAccess COMPLETADAS CON ÉXITO ===\n";
