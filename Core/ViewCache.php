<?php

namespace Core;

use Exception;

class ViewCache {
    private static string $cacheFile = __DIR__ . '/../storage/views_cache.php';

    /**
     * Prefijo que se antepone automáticamente a las URIs de zona privada.
     */
    public const PRIVATE_PREFIX = '/admin';

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
     * Construye la URI pública expuesta en el navegador a partir de la URI
     * almacenada en BD y el layout_type de la vista.
     *
     * Regla:
     *   - layout_type = 'private'  →  /admin/{raw_uri}   (ej. /admin/roles/ver)
     *   - layout_type = 'public'   →  /{raw_uri}          (ej. /login, /)
     *
     * La raw_uri en BD debe ser limpia, sin el prefijo /admin/.
     * Ejemplos de raw_uri: 'dashboard', 'roles/ver', 'login', '/'
     *
     * @param  string $rawUri     URI almacenada en BD (sin prefijo de zona).
     * @param  string $layoutType Tipo de zona: 'public' o 'private'.
     * @return string URI pública resultante (siempre empieza con /).
     */
    public static function buildPublicUri(string $rawUri, string $layoutType): string {
        // Normalizar: asegurarse de que raw_uri empiece con /
        $normalized = '/' . ltrim($rawUri, '/');

        if ($layoutType === 'private') {
            // Evitar doble prefijo si la raw_uri ya lo trae (compatibilidad)
            if (str_starts_with($normalized, self::PRIVATE_PREFIX . '/') || $normalized === self::PRIVATE_PREFIX) {
                return $normalized;
            }
            return ($normalized === '/') ? self::PRIVATE_PREFIX : self::PRIVATE_PREFIX . $normalized;
        }

        // Zona pública: URI directa
        return $normalized;
    }

    /**
     * Genera o regenera el archivo views_cache.php consultando la base de datos.
     *
     * El mapa resultante se indexa por la URI pública expuesta en el navegador
     * (con prefijo /admin/ para rutas privadas), derivada automáticamente del
     * campo `uri` de la BD y del `layout_type`. Cada entrada conserva el campo
     * original `uri` de BD bajo la clave `raw_uri`.
     */
    public static function generate(): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM views WHERE is_active = 1");
            $views = $stmt->fetchAll();

            // Indexar por URI pública (con prefijo automático según layout_type)
            $cachedRoutes = [];
            foreach ($views as $view) {
                $publicUri = self::buildPublicUri($view['uri'], $view['layout_type']);

                $view['raw_uri']   = $view['uri'];  // Valor original de BD
                $view['uri']       = $publicUri;    // URI pública expuesta

                $cachedRoutes[$publicUri] = $view;
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
        PermissionCache::refresh();
    }
}