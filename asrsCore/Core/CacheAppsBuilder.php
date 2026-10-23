<?php
namespace asrsCore\Core;

use Throwable;

class CacheAppsBuilder {
    /**
     * Genera el archivo de caché de apps (asrsCore/Cache/apps.php) si no existe.
     * Si ya existe no hace nada y retorna true.
     */
    public static function buildIfNotExists(string $cacheFilePath): bool {
        if (is_file($cacheFilePath)) {
            return true;
        }

        try {
            $pdo = Conection::getInstance();
            $apps = $pdo->query("SELECT * FROM asrs_apps")->fetchAll();

            $dir = dirname($cacheFilePath);
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                return false;
            }

            $content = "<?php\nreturn " . var_export($apps, true) . ";\n";

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
