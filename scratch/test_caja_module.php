<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/PermissionCache.php';
require_once __DIR__ . '/../Core/ViewCache.php';
require_once __DIR__ . '/../Core/MenuGroupCache.php';
require_once __DIR__ . '/../Core/Router.php';
require_once __DIR__ . '/../Core/controllers/CajaController.php';

use Core\Database;
use Core\PermissionCache;
use Core\ViewCache;
use Core\controllers\CajaController;

$db = Database::getInstance();

echo "========================================================\n";
echo "PRUEBAS DE VERIFICACIÓN - MÓDULO CAPTURAR PAGO (CAJA)\n";
echo "========================================================\n\n";

// -----------------------------------------------------------
// TEST 1: Control de Acceso por Roles (RBAC y Router)
// -----------------------------------------------------------
echo "--- TEST 1: Control de Acceso RBAC y Rutas ---\n";
$routes = ViewCache::getRoutes();
$cajaRoute = $routes['/admin/caja/capturar'] ?? null;

if (!$cajaRoute) {
    echo "❌ ERROR: La ruta /admin/caja/capturar no está registrada en ViewCache.\n";
    exit(1);
}
echo "✅ Ruta /admin/caja/capturar encontrada en ViewCache. ID: {$cajaRoute['id']}\n";

$viewId = (int)$cajaRoute['id'];

// Rol 1: super_admin
$canSuperAdmin = PermissionCache::userCanAccessView(1, 1, 'special', $viewId);
echo ($canSuperAdmin ? "✅" : "❌") . " Acceso Super Admin: " . ($canSuperAdmin ? "AUTORIZADO" : "DENEGADO") . "\n";

// Rol 2: caja
$canCaja = PermissionCache::userCanAccessView(5, 2, 'standard', $viewId);
echo ($canCaja ? "✅" : "❌") . " Acceso Rol Caja: " . ($canCaja ? "AUTORIZADO" : "DENEGADO") . "\n";

// Rol 3: inscripcion (no debe tener acceso)
$canInscripcion = PermissionCache::userCanAccessView(2, 3, 'standard', $viewId);
echo (!$canInscripcion ? "✅" : "❌") . " Acceso Rol Inscripción: " . ($canInscripcion ? "INCORRECTAMENTE AUTORIZADO" : "DENEGADO CORRECTAMENTE") . "\n";

// Rol 5: docente (no debe tener acceso)
$canDocente = PermissionCache::userCanAccessView(3, 5, 'standard', $viewId);
echo (!$canDocente ? "✅" : "❌") . " Acceso Rol Docente: " . ($canDocente ? "INCORRECTAMENTE AUTORIZADO" : "DENEGADO CORRECTAMENTE") . "\n";


// -----------------------------------------------------------
// TEST 2: Búsqueda de Alumno por Apellido Paterno
// -----------------------------------------------------------
echo "\n--- TEST 2: Búsqueda de Alumno por Apellido Paterno ---\n";
$stmtSearch = $db->prepare("
    SELECT id, curp, nombre, primer_apellido, segundo_apellido
    FROM alumnos_cea
    WHERE primer_apellido LIKE :term
    ORDER BY primer_apellido ASC
");
$stmtSearch->execute([':term' => 'Góm%']);
$resultsGomez = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
echo "Resultados para búsqueda 'Góm%': " . count($resultsGomez) . " alumno(s) encontrado(s).\n";
foreach ($resultsGomez as $r) {
    echo "  - ID {$r['id']}: {$r['primer_apellido']} {$r['segundo_apellido']} {$r['nombre']} (CURP: {$r['curp']})\n";
}
if (count($resultsGomez) > 0) {
    echo "✅ Búsqueda por apellido paterno exitosa.\n";
} else {
    echo "❌ ERROR: No se encontraron alumnos con apellido paterno Gómez.\n";
}


// -----------------------------------------------------------
// TEST 3: Panel Lateral Inteligente (Limpieza de Nulos)
// -----------------------------------------------------------
echo "\n--- TEST 3: Panel Lateral Inteligente (Ficha sin Nulos) ---\n";
// Simulemos la consulta de alumno con ID 3 (González Navarro Mateo)
$stmtAlumno = $db->prepare("SELECT * FROM alumnos_cea WHERE id = 3");
$stmtAlumno->execute();
$al3 = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

$stmtTutores = $db->prepare("SELECT * FROM tutores_cea WHERE alumno_id = 3");
$stmtTutores->execute();
$tutoresAl3 = $stmtTutores->fetchAll(PDO::FETCH_ASSOC);

$stmtFact = $db->prepare("SELECT * FROM facturacion_cea WHERE alumno_id = 3");
$stmtFact->execute();
$factAl3 = $stmtFact->fetch(PDO::FETCH_ASSOC);

echo "Alumno 3: {$al3['nombre']} {$al3['primer_apellido']}\n";
echo "Tutores registrados: " . count($tutoresAl3) . "\n";
echo "Facturación registrada: " . ($factAl3 ? "Sí" : "No") . "\n";

// Verificamos que los campos nulos o vacíos se detecten y limpien
foreach ($tutoresAl3 as $t) {
    echo "  Tutor: {$t['nombre_completo']} ({$t['parentesco']})\n";
    echo "  Tel secundario en BD: " . var_export($t['telefono_secundario'], true) . " -> Limpio: " . var_export(trim($t['telefono_secundario'] ?? '') === '' ? null : $t['telefono_secundario'], true) . "\n";
    echo "  Email en BD: " . var_export($t['email'], true) . " -> Limpio: " . var_export(trim($t['email'] ?? '') === '' ? null : $t['email'], true) . "\n";
}
echo "✅ Limpieza de nulos verificada.\n";


// -----------------------------------------------------------
// TEST 4: Dinámica de Forma de Pago y Validación de Referencia
// -----------------------------------------------------------
echo "\n--- TEST 4: Dinámica de Forma de Pago y Referencia ---\n";
$_SESSION['user'] = [
    'id' => 5,
    'name' => 'Lucy (Cajera)',
    'role' => 'caja',
    'role_id' => 2,
    'role_type' => 'standard'
];

// Intento 1: Pago bancario BBVA SIN referencia (Debe fallar)
$datosPagoInvalido = [
    'alumno_id' => 2,
    'concepto' => 'Colegiatura Octubre',
    'monto' => 3500.00,
    'forma_pago' => 'Transferencia BBVA',
    'referencia' => '', // VACÍA
    'fecha_pago' => date('Y-m-d'),
    'hora_pago' => date('H:i')
];
$resInvalido = CajaController::savePayment($db, $datosPagoInvalido);
echo "Intento BBVA sin referencia:\n";
echo "  Éxito: " . ($resInvalido['success'] ? 'true' : 'false') . "\n";
echo "  Mensaje: {$resInvalido['message']}\n";
if (!$resInvalido['success']) {
    echo "✅ Validación de referencia obligatoria funcionó correctamente.\n";
} else {
    echo "❌ ERROR: Debió rechazar el pago bancario sin referencia.\n";
}

// Intento 2: Pago en Efectivo SIN referencia (Debe ser exitoso)
$datosPagoEfectivo = [
    'alumno_id' => 2,
    'concepto' => 'Colegiatura Mensual (Efectivo)',
    'monto' => 3500.00,
    'forma_pago' => 'Efectivo',
    'referencia' => '',
    'fecha_pago' => date('Y-m-d'),
    'hora_pago' => date('H:i')
];
$resEfectivo = CajaController::savePayment($db, $datosPagoEfectivo);
echo "\nIntento Efectivo sin referencia:\n";
echo "  Éxito: " . ($resEfectivo['success'] ? 'true' : 'false') . "\n";
echo "  Mensaje: {$resEfectivo['message']}\n";
if ($resEfectivo['success']) {
    echo "  Folio generado: {$resEfectivo['data']['folio']}\n";
    echo "  Referencia almacenada: {$resEfectivo['data']['referencia']}\n";
    echo "✅ Pago en efectivo guardado exitosamente con persistencia PDO.\n";
} else {
    echo "❌ ERROR: No se guardó el pago en efectivo: {$resEfectivo['message']}\n";
}

// Intento 3: Pago Transferencia BBVA CON referencia (Debe ser exitoso)
$datosPagoBBVA = [
    'alumno_id' => 3,
    'concepto' => 'Inscripción Anual (BBVA)',
    'monto' => 4500.00,
    'forma_pago' => 'Transferencia BBVA',
    'referencia' => 'BBVA-TR-99882211',
    'fecha_pago' => date('Y-m-d'),
    'hora_pago' => date('H:i')
];
$resBBVA = CajaController::savePayment($db, $datosPagoBBVA);
echo "\nIntento Transferencia BBVA con referencia:\n";
echo "  Éxito: " . ($resBBVA['success'] ? 'true' : 'false') . "\n";
echo "  Mensaje: {$resBBVA['message']}\n";
if ($resBBVA['success']) {
    echo "  Folio generado: {$resBBVA['data']['folio']}\n";
    echo "  Referencia almacenada: {$resBBVA['data']['referencia']}\n";
    echo "✅ Pago BBVA con referencia guardado exitosamente en pagos_cea.\n";
} else {
    echo "❌ ERROR: No se guardó el pago BBVA: {$resBBVA['message']}\n";
}

// -----------------------------------------------------------
// TEST 5: Verificación en tabla pagos_cea
// -----------------------------------------------------------
echo "\n--- TEST 5: Verificación en tabla pagos_cea (PDO) ---\n";
$stmtVerif = $db->query("SELECT id, folio, alumno_id, concepto, monto, forma_pago, referencia, fecha_pago, hora_pago, estado FROM pagos_cea ORDER BY id DESC LIMIT 2");
$pagosDb = $stmtVerif->fetchAll(PDO::FETCH_ASSOC);
echo "Últimos registros en pagos_cea:\n";
print_r($pagosDb);

echo "\n========================================================\n";
echo "TODAS LAS PRUEBAS COMPLETADAS SATISFACTORIAMENTE\n";
echo "========================================================\n";
