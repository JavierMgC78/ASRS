<?php

namespace Core;

use Exception;
use PDO;

class PermissionCache
{
    private static string $cacheFile = __DIR__ . '/../storage/permissions_cache.php';

    /**
     * Obtiene el mapa de permisos RBAC desde la caché estática.
     * Si el archivo no existe, lo genera automáticamente desde la base de datos.
     *
     * @return array{special_roles: array<int, string>, role_permissions: array<int, int[]>, user_overrides: array<int, array{grant: int[], revoke: int[]}>}
     */
    public static function getPermissions(): array
    {
        if (!file_exists(self::$cacheFile)) {
            self::generate();
        }

        return require self::$cacheFile;
    }

    /**
     * Genera o regenera el archivo estático storage/permissions_cache.php consultando PDO.
     *
     * @throws Exception
     */
    public static function generate(): void
    {
        try {
            $db = Database::getInstance();

            // 1. Obtener roles y clasificar roles especiales (acceso total sin restricciones)
            $stmtRoles = $db->query("SELECT id, name, type FROM roles");
            $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

            $specialRoles = [];
            $rolePermissions = [];

            foreach ($roles as $r) {
                $roleId = (int)$r['id'];
                if ($r['type'] === 'special') {
                    $specialRoles[$roleId] = $r['name'];
                }
                $rolePermissions[$roleId] = [];
            }

            // 2. Obtener matriz de permisos por rol desde role_view_permissions
            $stmtPerms = $db->query("SELECT role_id, view_id FROM role_view_permissions");
            $perms = $stmtPerms->fetchAll(PDO::FETCH_ASSOC);

            foreach ($perms as $p) {
                $rId = (int)$p['role_id'];
                $vId = (int)$p['view_id'];
                if (!isset($rolePermissions[$rId])) {
                    $rolePermissions[$rId] = [];
                }
                $rolePermissions[$rId][] = $vId;
            }

            // Asegurar unicidad de IDs de vista
            foreach ($rolePermissions as $rId => &$vList) {
                $vList = array_values(array_unique($vList));
            }
            unset($vList);

            // 3. Obtener excepciones por usuario desde user_view_overrides si la tabla existe
            $userOverrides = [];
            $tables = $db->query("SHOW TABLES LIKE 'user_view_overrides'")->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($tables)) {
                $stmtOverrides = $db->query("SELECT user_id, view_id, action FROM user_view_overrides");
                $overrides = $stmtOverrides->fetchAll(PDO::FETCH_ASSOC);

                foreach ($overrides as $o) {
                    $uId = (int)$o['user_id'];
                    $vId = (int)$o['view_id'];
                    $act = strtolower(trim($o['action']));

                    if (!isset($userOverrides[$uId])) {
                        $userOverrides[$uId] = ['grant' => [], 'revoke' => []];
                    }

                    if ($act === 'grant' || $act === 'revoke') {
                        $userOverrides[$uId][$act][] = $vId;
                    }
                }
            }

            $cacheData = [
                'special_roles'    => $specialRoles,
                'role_permissions' => $rolePermissions,
                'user_overrides'   => $userOverrides,
            ];

            $cacheContent = "<?php\n/**\n * Archivo de caché estático de Permisos RBAC - ASRS Framework\n * Generado automáticamente. No editar manualmente.\n */\nreturn " . var_export($cacheData, true) . ";\n";

            $storageDir = dirname(self::$cacheFile);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            file_put_contents(self::$cacheFile, $cacheContent, LOCK_EX);

        } catch (Exception $e) {
            http_response_code(500);
            exit("Error crítico al generar el caché de permisos RBAC: " . $e->getMessage());
        }
    }

    /**
     * Invalida el archivo físico y regenera la caché estática.
     */
    public static function refresh(): void
    {
        if (file_exists(self::$cacheFile)) {
            @unlink(self::$cacheFile);
        }
        self::generate();
    }

    /**
     * Comprueba en tiempo O(1) si el usuario en sesión tiene autorización para acceder a una vista.
     * Cero consultas SQL a la base de datos gracias a la caché estática.
     *
     * @param int    $userId   ID del usuario autenticado.
     * @param int    $roleId   ID del rol activo del usuario.
     * @param string $roleType Tipo de rol ('special' o 'standard').
     * @param int    $viewId   ID de la vista a evaluar.
     * @return bool True si tiene permiso, False si no lo tiene.
     */
    public static function userCanAccessView(int $userId, int $roleId, string $roleType, int $viewId): bool
    {
        // 1. Roles de tipo especial (super_admin, etc.) poseen autorización global
        if ($roleType === 'special') {
            return true;
        }

        $cache = self::getPermissions();

        // Comprobar si el ID del rol figura como especial en la caché
        if (isset($cache['special_roles'][$roleId])) {
            return true;
        }

        // 2. Evaluar excepciones directas del usuario (user_view_overrides)
        if (isset($cache['user_overrides'][$userId])) {
            $userRules = $cache['user_overrides'][$userId];

            // Si tiene revocación explícita sobre esta vista
            if (!empty($userRules['revoke']) && in_array($viewId, $userRules['revoke'], true)) {
                return false;
            }

            // Si tiene concesión explícita sobre esta vista
            if (!empty($userRules['grant']) && in_array($viewId, $userRules['grant'], true)) {
                return true;
            }
        }

        // 3. Evaluar permisos asignados al rol (role_view_permissions)
        $allowedViews = $cache['role_permissions'][$roleId] ?? [];

        return in_array($viewId, $allowedViews, true);
    }
}
