<?php
/**
 * Punto de entrada único - ASRS (Axe Secure Router System)
 * Ubicación: public/index.php
 */

// 1. Cargar el autoloader nativo PSR-4 y funciones globales
require_once __DIR__ . '/../app/global_functions.php';

// 2. Importar clases del Core
use App\Core\Router;

// 3. Inicialización del Sistema y Manejo de Excepciones del Boot-time
try {
    // Instanciar el Router disparará la verificación de caché y la autocompilación
    $router = new Router();

    // Despachar la petición actual
    $router->dispatch();

} catch (\RuntimeException $e) {
    // Captura errores controlados del framework (ej. la falta de la tabla views)
    echo "<div style='font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #fff3f3; border-left: 5px solid #D92525; border-radius: 4px; color: #101926;'>";
    echo "<h3 style='color: #D92525; margin-top: 0;'>⚠️ Alerta de Infraestructura ASRS</h3>";
    echo "<p style='font-size: 15px; line-height: 1.5;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr style='border: none; border-top: 1px solid #ffcdcd; margin: 15px 0;'>";
    echo "<p style='font-size: 13px; color: #555;'>El framework detuvo el ciclo de vida de forma segura para evitar fallos en cascada.</p>";
    echo "</div>";

} catch (\Exception $e) {
    // Captura cualquier otra excepción general (conexión caída, errores de sintaxis, etc.)
    echo "<div style='font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #fff; border-left: 5px solid #101926; border-radius: 4px; color: #101926; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>";
    echo "<h2 style='color: #101926; margin-top: 0;'>Error Crítico del Sistema</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='font-size: 12px; color: #666;'>Archivo: " . htmlspecialchars($e->getFile()) . " en la línea " . $e->getLine() . "</p>";
    echo "</div>";
}