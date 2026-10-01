<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();
$_SESSION['user'] = [
    'id' => 5,
    'name' => 'Lucy (Cajero)',
    'role' => 'caja',
    'role_id' => 2,
    'role_type' => 'standard',
    'is_active' => 1
];

ob_start();
require __DIR__ . '/../views/private/caja/capturar.php';
$html = ob_get_clean();

echo "Longitud HTML renderizado: " . strlen($html) . " bytes\n";
if (strpos($html, 'Captura y Emisión de Pagos Escolares') !== false && strpos($html, 'panel-inteligente') !== false) {
    echo "✅ Vista capturar.php renderizada limpiamente con autoloader de Composer.\n";
} else {
    echo "❌ Error en el contenido de la vista.\n";
}
