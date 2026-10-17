<?php
namespace App\Core;

use App\Core\Models\Database;
use PDOException;
use RuntimeException;

/**
 * Generador y Compilador de Caché de Rutas - ASRS
 * Mapea las rutas estáticas desde la base de datos a un array nativo PHP de alta velocidad O(1),
 * resolviendo de manera automatizada la carga dinámica de assets por convención de nombres.
 */
class CacheRoutesBuilder {
    private static string $cacheFile = __DIR__ . '/../../config/routes_cache.php';

    /**
     * Verifica si la caché existe; si no, la genera automáticamente desde la BD.
     */
    public static function checkAndGenerate(): void {
        if (!file_exists(self::$cacheFile)) {
            self::generate();
        }
    }

    /**
     * Resuelve automáticamente las rutas de assets (CSS y JS) basándose en el parámetro 'content'
     * y el namespace modular del controlador.
     *
     * @param string      $content     Nombre de la vista de contenido (ej. 'vw_home')
     * @param string      $controller  Nombre de la clase controladora (ej. 'App\Core\Controllers\HomeController')
     * @param string|null $cssFromDb   Ruta registrada en base de datos si existe
     * @param string|null $jsFromDb    Ruta registrada en base de datos si existe
     * @return array ['css' => string, 'js' => string]
     */
    public static function resolveAssetPaths(string $content, string $controller, ?string $cssFromDb = null, ?string $jsFromDb = null): array {
        // Detectar módulo a partir del namespace (App\Core -> core, App\Segi -> segi)
        $module = 'core';
        if (preg_match('/App\\\\([^\\\\]+)\\\\/', $controller, $matches)) {
            $module = strtolower($matches[1]);
        }

        // Convención estándar de ASRS
        if ($module === 'core') {
            $conventionCss = "/AXE/public/assets/core/css/views_content/{$content}.css";
            $conventionJs  = "/AXE/public/assets/core/js/views_content/{$content}.js";
        } else {
            $conventionCss = "/AXE/public/assets/{$module}/css/{$content}.css";
            $conventionJs  = "/AXE/public/assets/{$module}/js/{$content}.js";
        }

        // Si la BD tiene una ruta no vacía que respeta el nombre del contenido, se mantiene; de lo contrario se aplica la convención
        $finalCss = (!empty($cssFromDb) && str_contains($cssFromDb, $content)) ? $cssFromDb : $conventionCss;
        $finalJs  = (!empty($jsFromDb) && str_contains($jsFromDb, $content)) ? $jsFromDb : $conventionJs;

        return [
            'css' => $finalCss,
            'js'  => $finalJs
        ];
    }

    /**
     * Consulta la base de datos (tabla views) y compila el archivo routes_cache.php
     * 
     * @throws RuntimeException Si la tabla 'views' no existe o hay un fallo estructural crítico.
     */
    public static function generate(): void {
        try {
            $db = Database::getInstance();

            $stmt = $db->query("SELECT uri, type, controller, method, layout, content, roles, css_path_view, js_path_view FROM views WHERE status = 1");
            $views = $stmt->fetchAll();

        } catch (PDOException $e) {
            // Código 42S02 en SQLSTATE indica que la tabla o vista no existe en MySQL
            if ($e->getCode() == '42S02' || str_contains($e->getMessage(), "doesn't exist")) {
                throw new RuntimeException(
                    "Error de ASRS [Boot-time]: La tabla maestra 'views' no existe en la base de datos. " .
                    "Por favor, asegúrese de migrar o crear la estructura de tablas antes de arrancar el sistema."
                );
            }
            
            // Si es otro error de PDO (credenciales, conexión caída, etc.), lo relanzamos intacto
            throw $e;
        }

        $cacheContent = "<?php\n/**\n * Archivo generado automáticamente por ASRS\n * Fecha de compilación: " . date('Y-m-d H:i:s') . "\n */\n\nreturn [\n";

        foreach ($views as $view) {
            $uri        = $view['uri'];
            $type       = $view['type'];
            $controller = $view['controller'];
            $method     = $view['method'];
            $layout     = $view['layout'];
            $content    = $view['content'];
            $roles      = !empty($view['roles']) ? var_export(json_decode($view['roles'], true), true) : '[]';

            // Resolución automatizada de assets por convención de nombres
            $assets = self::resolveAssetPaths($content, $controller, $view['css_path_view'] ?? null, $view['js_path_view'] ?? null);

            $cacheContent .= "    '{$uri}' => [\n";
            $cacheContent .= "        'type'       => '{$type}',\n";
            $cacheContent .= "        'controller' => '{$controller}',\n";
            $cacheContent .= "        'method'     => '{$method}',\n";
            $cacheContent .= "        'layout'     => '{$layout}',\n";
            $cacheContent .= "        'content'    => '{$content}',\n";
            $cacheContent .= "        'roles'      => {$roles},\n";
            $cacheContent .= "        'assets'     => [\n";
            $cacheContent .= "            'css' => '{$assets['css']}',\n";
            $cacheContent .= "            'js'  => '{$assets['js']}'\n";
            $cacheContent .= "        ]\n";
            $cacheContent .= "    ],\n";
        }

        $cacheContent .= "];\n";

        if (!is_dir(dirname(self::$cacheFile))) {
            mkdir(dirname(self::$cacheFile), 0755, true);
        }
        
        file_put_contents(self::$cacheFile, $cacheContent);
    }
}