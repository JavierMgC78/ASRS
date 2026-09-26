<?php

namespace Core;

use PDO;

class Router {
    
    /**
     * Despacha la petición HTTP actual según la URI solicitada.
     */
    public static function dispatch() {
        // 1. Limpiar y normalizar la URI solicitada
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Detectar si el script corre en un subdirectorio (ej: /Proyectos/Axe/public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(dirname($scriptName), '/\\');
        if ($baseDir !== '' && $baseDir !== '/' && $baseDir !== '\\' && strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }

        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        // Manejo especial de la ruta de cierre de sesión (/admin/logout)
        if ($uri === '/admin/logout') {
            controllers\LogoutController::logout();
            return;
        }

        // Manejo especial de la ruta de inicio de sesión (/admin/login)
        // Se procesa ANTES del output buffer para que los headers de redirección funcionen correctamente
        if ($uri === '/admin/login') {
            $loginController = \Core\controllers\LoginController::handle();
            // Si handle() no redirigió (GET sin sesión activa o POST con error),
            // se continúa con el renderizado normal pasando el error a la vista
            $GLOBALS['__login_error'] = $loginController['errorMessage'] ?? null;
        }

        // Manejo especial de la vista de creación de vistas (/admin/vistas/crear)
        // Se procesa ANTES del output buffer: el controlador puede necesitar redirigir tras el POST
        if ($uri === '/admin/vistas/crear') {
            $GLOBALS['__views_data'] = \Core\controllers\ViewsController::handle();
        }

        // 2. Obtener el mapa de rutas desde el sistema de caché (O(1))
        $routes = ViewCache::getRoutes();

        // 3. Verificar si la ruta existe en el caché
        if (!isset($routes[$uri])) {
            self::renderError404();
            return;
        }

        $viewData = $routes[$uri];

        // 4. Validar si la vista está activa
        if (!$viewData['is_active']) {
            self::renderError404();
            return;
        }

        // 5. Control de Acceso y Seguridad (Autenticación + RBAC)
        self::checkAccess($viewData);

        // 6. Determinar Base URL para resolución correcta de assets y rutas
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\');
        if ($baseUrl === '/' || $baseUrl === '\\') {
            $baseUrl = '';
        }

        // 7. Generar el Menú Dinámico para la zona actual (Filtrado en memoria)
        $dynamicMenu = self::generateDynamicMenu($routes, $viewData['layout_type'], $baseUrl);

        // 8. Resolver la ruta física del archivo de la vista.
        //    Estándar unificado: views/{layout_type}/{strtolower(menu_group)}/{name_file}.php
        //    Ejemplo: views/public/general/inicio.php | views/private/roles/roles_crear.php
        $viewFilepath = self::resolveViewFilepath($viewData);

        if (!file_exists($viewFilepath)) {
            echo "Error crítico: El archivo físico de la vista no existe en: {$viewFilepath}";
            return;
        }

        // 9. Resolver assets específicos de la vista con verificación física.
        //    Ruta estándar: assets/css/{strtolower(menu_group)}/{name_file}.css
        //                   assets/js/{strtolower(menu_group)}/{name_file}.js
        [$specificCss, $specificJs] = self::resolveViewAssets($viewData, $baseUrl);

        // 10. Renderizado Virtual de la Vista (Output Buffering)
        $viewContent = self::renderVirtualView($viewFilepath, $viewData);

        // 11. Determinar qué template maestro utilizar
        $layoutType   = $viewData['layout_type']; // 'public' o 'private'
        $templateFile = ($layoutType === 'public')
            ? __DIR__ . '/../views/template_public.php'
            : __DIR__ . '/../views/template_private.php';

        if (!file_exists($templateFile)) {
            echo "Error crítico: El template maestro [{$layoutType}] no existe.";
            return;
        }

        // Variables disponibles para el Template maestro
        $pageTitle    = $viewData['menu_title'] . ' - Coffee Flavored Software';
        $brandName    = 'Coffee Flavored Software';
        $brandLogoUrl = $baseUrl . '/assets/img/logo.png';
        $currentUri   = $uri;

        // 12. Ensamblaje y Renderizado Final
        require_once $templateFile;
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Resuelve la ruta física absoluta del archivo PHP de la vista.
     *
     * Estándar unificado para ambas zonas:
     *   views/{layout_type}/{strtolower(menu_group)}/{name_file}.php
     *
     * Ejemplo: views/public/general/inicio.php
     *          views/private/roles/roles_crear.php
     *
     * Si name_file o menu_group no están disponibles, usa file_path de BD como
     * fallback de compatibilidad.
     *
     * @param  array  $viewData  Datos de la vista obtenidos del caché.
     * @return string Ruta absoluta al archivo PHP de la vista.
     */
    private static function resolveViewFilepath(array $viewData): string {
        $layoutType = $viewData['layout_type'] ?? 'public';
        $nameFile   = $viewData['name_file']   ?? null;
        $menuGroup  = $viewData['menu_group']  ?? null;
        $basePath   = __DIR__ . '/../';

        if (!empty($nameFile) && !empty($menuGroup)) {
            $groupFolder = self::normalizeGroupToFolder($menuGroup);
            return $basePath . 'views/' . $layoutType . '/' . $groupFolder . '/' . $nameFile . '.php';
        }

        // Fallback: usar file_path almacenado en BD (vistas sin name_file/menu_group)
        return $basePath . ($viewData['file_path'] ?? '');
    }

    /**
     * Resuelve las URLs públicas de los assets CSS y JS de una vista.
     *
     * Estándar: assets/css/{strtolower(menu_group)}/{name_file}.css
     *           assets/js/{strtolower(menu_group)}/{name_file}.js
     *
     * Verifica la existencia física del archivo antes de generar la URL.
     * Si el archivo no existe, devuelve null para esa entrada (sin etiqueta HTML).
     *
     * @param  array  $viewData  Datos de la vista del caché.
     * @param  string $baseUrl   Base URL del sistema (ej. '/Proyectos/Axe/public').
     * @return array{0: string|null, 1: string|null}  [$specificCss, $specificJs]
     */
    private static function resolveViewAssets(array $viewData, string $baseUrl): array {
        $nameFile      = $viewData['name_file']  ?? null;
        $menuGroup     = $viewData['menu_group'] ?? null;
        $assetBasePath = __DIR__ . '/../public/assets/';

        if (empty($nameFile) || empty($menuGroup)) {
            return [null, null];
        }

        $groupFolder = self::normalizeGroupToFolder($menuGroup);
        $cssRelPath  = 'css/' . $groupFolder . '/' . $nameFile . '.css';
        $jsRelPath   = 'js/'  . $groupFolder . '/' . $nameFile . '.js';

        $specificCss = file_exists($assetBasePath . $cssRelPath)
            ? $baseUrl . '/assets/' . $cssRelPath
            : null;

        $specificJs = file_exists($assetBasePath . $jsRelPath)
            ? $baseUrl . '/assets/' . $jsRelPath
            : null;

        return [$specificCss, $specificJs];
    }

    /**
     * Normaliza un menu_group al nombre de carpeta física estándar.
     * Aplica strtolower() y convierte espacios y guiones a guiones bajos.
     *
     * Ejemplos:
     *   'General'          -> 'general'
     *   'Gestión Roles'    -> 'gestión_roles'
     *   'Mi-Módulo'        -> 'mi_módulo'
     *
     * @param  string $menuGroup  Valor del campo menu_group de la BD.
     * @return string Nombre de carpeta normalizado.
     */
    private static function normalizeGroupToFolder(string $menuGroup): string {
        $folder = strtolower(trim($menuGroup));
        return str_replace([' ', '-'], '_', $folder);
    }

    /**
     * Valida los permisos de acceso a la vista (Autenticación + RBAC).
     *
     * Para zonas privadas:
     *   1. Verifica autenticación válida via AuthMiddleware (Split Token).
     *   2. Valida RBAC: el rol activo del usuario debe tener autorización
     *      sobre la vista solicitada en la tabla `role_view_permissions`.
     *      Los roles de tipo 'special' (ej. super_admin) tienen acceso total.
     *
     * @param  array  $viewData  Datos de la vista del caché.
     */
    private static function checkAccess(array $viewData): void {
        if (($viewData['layout_type'] ?? '') !== 'private') {
            return; // Vistas públicas no requieren validación
        }

        // 1. Validar sesión activa (Split Token)
        AuthMiddleware::handle();

        // 2. Validar RBAC
        $viewId   = (int)($viewData['id'] ?? 0);
        $roleType = $_SESSION['user']['role_type'] ?? $_SESSION['user']['type'] ?? '';

        // Los roles especiales (super_admin, etc.) tienen acceso total
        if ($roleType === 'special') {
            return;
        }

        // Verificar permiso explícito en role_view_permissions
        if ($viewId > 0 && !self::roleHasPermission($viewId)) {
            http_response_code(403);
            if (!headers_sent()) {
                header('Location: /admin/dashboard');
            }
            exit;
        }
    }

    /**
     * Consulta si el rol activo del usuario en sesión tiene permiso
     * sobre una vista específica en la tabla `role_view_permissions`.
     *
     * @param  int   $viewId  ID de la vista a verificar.
     * @return bool  True si existe el permiso, false en caso contrario.
     */
    private static function roleHasPermission(int $viewId): bool {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if ($roleId === 0) {
            return false;
        }

        $db  = Database::getInstance();
        $sql = "SELECT 1
                FROM role_view_permissions
                WHERE role_id = :role_id
                  AND view_id = :view_id
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':role_id', $roleId,  PDO::PARAM_INT);
        $stmt->bindValue(':view_id', $viewId,   PDO::PARAM_INT);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Filtra el menú dinámicamente según la zona y si debe mostrarse.
     * Retorna un array asociativo agrupado por 'menu_group':
     *   [ 'NombreGrupo' => [ ['uri'=>..., 'raw_uri'=>..., 'menu_title'=>...], ... ], ... ]
     */
    private static function generateDynamicMenu(array $routes, string $currentLayoutType, string $baseUrl = ''): array {
        $menu = [];
        foreach ($routes as $route) {
            if ($route['layout_type'] === $currentLayoutType && $route['show_in_menu'] == 1 && $route['is_active'] == 1) {
                $routeUri = ($route['uri'] === '/') ? '/' : $route['uri'];
                $fullUri  = ($baseUrl !== '' && $routeUri === '/') ? $baseUrl . '/' : $baseUrl . $routeUri;
                $group    = !empty($route['menu_group']) ? $route['menu_group'] : 'General';

                $menu[$group][] = [
                    'uri'        => $fullUri,
                    'raw_uri'    => $route['uri'],
                    'menu_title' => $route['menu_title'],
                ];
            }
        }
        return $menu;
    }

    /**
     * Normaliza un título a nombre válido de archivo de asset.
     * Ej: "Reporte Cobros" -> "reporte_cobros".
     * Se usa como fallback cuando name_file no está disponible.
     */
    private static function normalizeNameToAsset(string $menuTitle): string {
        $slug = mb_strtolower(trim($menuTitle));
        $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
        return preg_replace('/[^a-z0-9_]/', '_', str_replace(' ', '_', $slug));
    }

    /**
     * Ejecuta el búfer de salida para capturar la vista virtualmente.
     */
    private static function renderVirtualView(string $filepath, array $viewData): string {
        ob_start();
        // Se pueden inyectar datos adicionales a la vista si es necesario
        include $filepath;
        return ob_get_clean();
    }

    /**
     * Manejo básico de página no encontrada.
     */
    private static function renderError404(): void {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>La ruta solicitada no existe en el sistema ASRS.</p>";
    }
}