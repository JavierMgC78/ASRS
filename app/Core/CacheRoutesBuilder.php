<?php
namespace App\Core;

use App\Core\Models\Database;
use PDOException;
use RuntimeException;

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

            $cacheContent .= "    '{$uri}' => [\n";
            $cacheContent .= "        'type'       => '{$type}',\n";
            $cacheContent .= "        'controller' => '{$controller}',\n";
            $cacheContent .= "        'method'     => '{$method}',\n";
            $cacheContent .= "        'layout'     => '{$layout}',\n";
            $cacheContent .= "        'content'    => '{$content}',\n";
            $cacheContent .= "        'roles'      => {$roles},\n";
            $cacheContent .= "        'assets'     => [\n";
            $cacheContent .= "            'css' => '{$view['css_path_view']}',\n";
            $cacheContent .= "            'js'  => '{$view['js_path_view']}'\n";
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