<?php
// Inicialización del Kernel ASRS v1.0
require_once __DIR__ . '/../asrsCore/Helpers/global_functions.php';

use asrsCore\Core\Conection;

// Capturar la URI solicitada desde el parámetro de Apache (.htaccess)
$uri = $_GET['uri'] ?? '/';

// Interfaz básica de prueba del Kernel y Conexión
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ASRS v1 - Kernel Active</title>
    <style>
        body { background-color: #101926; color: #D4D9ED; font-family: monospace; padding: 40px; }
        .box { border: 1px solid #D4D9ED; padding: 20px; border-radius: 6px; max-width: 600px; margin: auto; }
        h1 { color: #E3B331; font-size: 20px; }
        .success { color: #2ecc71; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="box">
        <h1>ASRS v1 // Kernel Central Activo</h1>
        <p><strong>URI Solicitada:</strong> <?= htmlspecialchars($uri) ?></p>
        
        <hr style="border-color: #2c3e50;">

        <h3>Estado de Conexión a Base de Datos:</h3>
        <?php
        try {
            // Probando la instancia Singleton de PDO
            $pdo = Conection::getInstance();
            echo "<p class='success'>[OK] ¡Conexión exitosa a la base de datos a través de Conection (Singleton)!</p>";
            
            // Opcional: Consulta rápida para verificar que responde
            $stmt = $pdo->query("SELECT VERSION() as version");
            $row = $stmt->fetch();
            echo "<p>Versión de MySQL/MariaDB: <strong>" . htmlspecialchars($row['version']) . "</strong></p>";

        } catch (\Exception $e) {
            echo "<p class='error'>[ERROR CRÍTICO] No se pudo conectar a la base de datos:<br>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>