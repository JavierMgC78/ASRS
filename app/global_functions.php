<?php
/**
 * Archivo de funciones globales y autoloader nativo PSR-4
 * Framework ASRS (Axe Secure Router System)
 * Ubicación: app/global_functions.php
 */





/**
 * Autoloader nativo optimizado bajo el estándar PSR-4.
 * Carga las clases bajo demanda (lazy loading) traduciendo los Namespaces 
 * directamente a la estructura de directorios física dentro de /app.
 */
spl_autoload_register(function ($class) {
    // Prefijo base del namespace del proyecto
    $prefix = 'App\\';

    // Directorio base correspondiente a la carpeta 'app/'
    $base_dir = __DIR__ . '/';

    // Verificar si la clase solicitada usa el prefijo base
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Si no pertenece a App\, ignorar
    }

    // Obtener la ruta relativa de la clase
    $relative_class = substr($class, $len);

    // Reemplazar separadores de namespace por barras de directorio y agregar .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Cargar el archivo si existe físicamente
    if (file_exists($file)) {
        require $file;
    }
});