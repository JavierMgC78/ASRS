<?php
namespace asrsCore\Core;

use Throwable;

class CacheRoutesBuilder {
    /** Ruta por defecto del caché: asrsCore/Cache/routes.php */
    public const DEFAULT_CACHE_FILE = __DIR__ . '/../Cache/routes.php';

    /** Clave reservada de la caché con el app_key de la app por defecto (asrs_apps.is_default = 1) */
    public const DEFAULT_APP_KEY = '@default_app';

    /**
     * Genera el archivo de caché de rutas (routes.php) si no existe.
     * Las rutas se indexan como "app|uri" para búsqueda O(1).
     */
    public static function buildIfNotExists(string $cacheFilePath = self::DEFAULT_CACHE_FILE): bool {
        if (is_file($cacheFilePath)) {
            return true;
        }

        try {
            $pdo = Conection::getInstance();
            $stmt = $pdo->query(
                "SELECT uri, app, type, controller, method, layout, content, roles, css_path_view, js_path_view
                 FROM asrs_views WHERE status = 1"
            );

            $routes = [];
            foreach ($stmt->fetchAll() as $row) {
                $row['roles'] = $row['roles'] !== null
                    ? (json_decode($row['roles'], true) ?? [])
                    : [];
                $routes[$row['app'] . '|' . $row['uri']] = $row;
            }

            // App por defecto; si la columna is_default aún no existe, se omite
            try {
                $default = $pdo->query(
                    "SELECT app_key FROM asrs_apps WHERE is_default = 1 AND status = 1 LIMIT 1"
                )->fetchColumn();
                if ($default !== false && $default !== null) {
                    $routes[self::DEFAULT_APP_KEY] = (string)$default;
                }
            } catch (Throwable $e) {
                // sin is_default: RoutingEngine usa su app por defecto interna
            }

            $dir = dirname($cacheFilePath);
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                return false;
            }

            $content = "<?php\nreturn " . var_export($routes, true) . ";\n";

            // Escritura atómica: archivo temporal + rename
            $tmp = $cacheFilePath . '.' . uniqid('', true) . '.tmp';
            if (file_put_contents($tmp, $content, LOCK_EX) === false) {
                return false;
            }
            if (!rename($tmp, $cacheFilePath)) {
                @unlink($tmp);
                return false;
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
