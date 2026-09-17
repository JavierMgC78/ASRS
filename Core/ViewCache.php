<?php

namespace Core;

use Exception;

class ViewCache {
    private static string $cacheFile = __DIR__ . '/../storage/views_cache.php';

    /**
     * Obtiene el mapa de rutas. Si no existe la caché, la genera automáticamente.
     */
    public static function getRoutes(): array {
        if (!file_exists(self::$cacheFile)) {
            self::generate();
        }
        return require self::$cacheFile;
    }

    /**
     * Genera o regenera el archivo views_cache.php consultando la base de datos.
     */
    public static function generate(): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM views WHERE is_active = 1");
            $views = $stmt->fetchAll();

            // Reorganizar indexando por 'uri' para búsquedas O(1)
            $cachedRoutes = [];
            foreach ($views as $view) {
                $cachedRoutes[$view['uri']] = $view;
            }

            $cacheContent = "<?php\nreturn " . var_export($cachedRoutes, true) . ";\n";

            $storageDir = dirname(self::$cacheFile);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            file_put_contents(self::$cacheFile, $cacheContent);

        } catch (Exception $e) {
            http_response_code(500);
            exit("Error crítico al generar el caché de vistas: " . $e->getMessage());
        }
    }

    /**
     * Invalida y regenera el caché (ideal para cuando se creen/editen vistas desde la app).
     */
    public static function refresh(): void {
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
        self::generate();
    }
}