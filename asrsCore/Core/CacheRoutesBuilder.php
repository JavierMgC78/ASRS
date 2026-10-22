<?php
namespace asrsCore\Core;

use Throwable;

class CacheRoutesBuilder {
    /**
     * Genera el archivo de caché de rutas si no existe.
     * Las rutas se indexan como "app|uri" para búsqueda O(1).
     */
    public static function buildIfNotExists(string $cacheFilePath): bool {
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
