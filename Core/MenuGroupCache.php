<?php

namespace Core;

use Exception;
use PDO;

class MenuGroupCache
{
    private static string $cacheFile = __DIR__ . '/../storage/menu_groups_cache.php';

    /**
     * Obtiene el catálogo de grupos de menú desde la caché estática.
     * Si el archivo no existe, lo genera automáticamente desde la base de datos.
     *
     * @return array<string, array> Array asociativo de grupos ordenado por display_order ASC.
     */
    public static function getGroups(): array
    {
        if (!file_exists(self::$cacheFile)) {
            self::generate();
        }

        return require self::$cacheFile;
    }

    /**
     * Obtiene únicamente los grupos activos y listos para navegación.
     *
     * @return array<string, array>
     */
    public static function getActiveGroups(): array
    {
        $all = self::getGroups();
        return array_filter($all, fn($g) => !empty($g['is_active']));
    }

    /**
     * Genera o regenera el archivo estático storage/menu_groups_cache.php consultando PDO.
     *
     * @throws Exception En caso de fallo crítico en la consulta o escritura.
     */
    public static function generate(): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, name, slug, display_order, icon, description, is_active, created_at, updated_at
                 FROM menu_groups
                 ORDER BY display_order ASC, name ASC"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Indexar por nombre de grupo
            $cachedGroups = [];
            foreach ($rows as $row) {
                // Castear tipos de datos para mayor consistencia
                $row['id']            = (int) $row['id'];
                $row['display_order'] = (int) $row['display_order'];
                $row['is_active']     = (int) $row['is_active'];

                $cachedGroups[$row['name']] = $row;
            }

            // Ordenar alfabéticamente por clave (nombre del grupo de la A a la Z)
            ksort($cachedGroups);

            $cacheContent = "<?php\n/**\n * Archivo de caché estático de Grupos de Menú - ASRS Framework\n * Generado automáticamente. No editar manualmente.\n */\nreturn " . var_export($cachedGroups, true) . ";\n";

            $storageDir = dirname(self::$cacheFile);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            file_put_contents(self::$cacheFile, $cacheContent, LOCK_EX);

        } catch (Exception $e) {
            http_response_code(500);
            exit("Error crítico al generar el caché de grupos de menú: " . $e->getMessage());
        }
    }

    /**
     * Invalida físicamente el archivo de caché y lo vuelve a generar desde la BD.
     */
    public static function refresh(): void
    {
        if (file_exists(self::$cacheFile)) {
            @unlink(self::$cacheFile);
        }
        self::generate();
    }

    /**
     * Retorna el siguiente número entero sugerido para display_order.
     */
    public static function getNextDisplayOrder(): int
    {
        $groups = self::getGroups();
        if (empty($groups)) {
            return 1;
        }

        $max = 0;
        foreach ($groups as $g) {
            if ($g['display_order'] > $max) {
                $max = $g['display_order'];
            }
        }

        return $max + 1;
    }
}
