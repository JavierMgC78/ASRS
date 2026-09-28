<?php

namespace Core;

use PDO;

class Router {

    /**
     * Prefijo para zona privada (fuente única: ViewCache::PRIVATE_PREFIX).
     */
    private const ADMIN_PREFIX = '/admin';

    /**
     * Despacha la petición HTTP actual según la URI solicitada.
     *
     * Las claves del caché ya incluyen el prefijo /admin/ para rutas privadas
     * (generado por ViewCache::buildPublicUri), por lo que la comparación es O(1)
     * directamente sobre la URI normalizada del navegador.
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

        // 2. Rutas especiales del núcleo procesadas ANTES del output buffer

        // Cierre de sesión
        if ($uri === self::ADMIN_PREFIX . '/logout') {
            controllers\LogoutController::logout();
            return;
        }

        // Inicio de sesión: procesar lógica de autenticación
        if ($uri === self::ADMIN_PREFIX . '/login') {
            $loginController = \Core\controllers\LoginController::handle();
            $GLOBALS['__login_error'] = $loginController['errorMessage'] ?? null;
        }

        // Gestión de vistas: procesar POST antes de renderizar
        if ($uri === self::ADMIN_PREFIX . '/vistas/crear') {
            $GLOBALS['__views_data'] = \Core\controllers\ViewsController::handle();
        }

        // 3. Obtener el mapa de rutas desde el sistema de caché (O(1))
        //    Las claves ya incluyen el prefijo correcto (ej. '/admin/roles/ver')
        $routes = ViewCache::getRoutes();

        // 4. Verificar si la ruta existe en el caché
        if (!isset($routes[$uri])) {
            self::renderError404();
            return;
        }

        $viewData = $routes[$uri];

        // 5. Validar si la vista está activa
        if (!$viewData['is_active']) {
            self::renderError404();
            return;
        }

        // 6. Control de Acceso y Seguridad (Autenticación + RBAC)
        self::checkAccess($viewData);

        // 7. Determinar Base URL para resolución correcta de assets y rutas
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\');
        if ($baseUrl === '/' || $baseUrl === '\\') {
            $baseUrl = '';
        }

        // 8. Generar el Menú Dinámico para la zona actual (filtrado por layout_type)
        //    Las URIs del menú vienen directamente de las claves del caché (ya con prefijo)
        $dynamicMenu = self::generateDynamicMenu($routes, $viewData['layout_type'], $baseUrl);

        // 9. Resolver la ruta física del archivo de la vista.
        //    Estándar: views/{layout_type}/{strtolower(menu_group)}/{name_file}.php
        //    Ejemplo: views/public/general/inicio.php | views/private/roles/ver.php
        $viewFilepath = self::resolveViewFilepath($viewData);

        if (!file_exists($viewFilepath)) {
            echo "Error crítico: El archivo físico de la vista no existe en: {$viewFilepath}";
            return;
        }

        // 10. Resolver assets específicos de la vista con verificación física.
        //     Estándar: assets/css/{strtolower(menu_group)}/{name_file}.css
        //               assets/js/{strtolower(menu_group)}/{name_file}.js
        [$specificCss, $specificJs] = self::resolveViewAssets($viewData, $baseUrl);

        // 11. Renderizado Virtual de la Vista (Output Buffering)
        $viewContent = self::renderVirtualView($viewFilepath, $viewData);

        // 12. Determinar qué template maestro utilizar
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
        $currentUri   = $uri; // URI pública actual (con prefijo si es privada)

        // 13. Ensamblaje y Renderizado Final
        require_once $templateFile;
    }

    // =========================================================================
    // HELPERS PÚBLICOS DE URL
    // =========================================================================

    /**
     * Genera la URL pública completa (con baseUrl) a partir de una raw_uri y su layout_type.
     *
     * Uso desde vistas/templates:
     *   <?= Router::url('roles/ver') ?>        → /Proyectos/Axe/public/admin/roles/ver
     *   <?= Router::url('login', 'public') ?>  → /Proyectos/Axe/public/login
     *
     * @param  string $rawUri     URI limpia de BD (sin prefijo de zona).
     * @param  string $layoutType 'private' (por defecto) o 'public'.
     * @return string URL pública completa con baseUrl.
     */
    public static function url(string $rawUri, string $layoutType = 'private'): string {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\');
        if ($baseUrl === '/' || $baseUrl === '\\') {
            $baseUrl = '';
        }

        $publicUri = ViewCache::buildPublicUri($rawUri, $layoutType);
        return $baseUrl . $publicUri;
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

        // Fallback inteligente: extraer del file_path si faltan name_file o menu_group
        if (empty($nameFile) || empty($menuGroup)) {
            $filePath = $viewData['file_path'] ?? '';
            if (preg_match('#views/[^/]+/([^/]+)/([^/]+)\.php$#i', $filePath, $matches)) {
                $menuGroup = $menuGroup ?: $matches[1];
                $nameFile  = $nameFile  ?: $matches[2];
            }
        }

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
                header('Location: ' . self::ADMIN_PREFIX . '/dashboard');
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
     * Genera el menú dinámico agrupado por 'menu_group', filtrado por zona (layout_type).
     *
     * Las URIs del menú provienen directamente del caché, cuyas claves ya incluyen
     * el prefijo correcto (/admin/ para privadas). No se añade prefijo adicional
     * aquí: la clave $publicUri del caché ES la URL pública correcta.
     *
     * Retorna:
     *   [ 'NombreGrupo' => [ ['uri'=>..., 'raw_uri'=>..., 'menu_title'=>...], ... ], ... ]
     *
     * @param  array  $routes            Mapa del caché (indexado por URI pública).
     * @param  string $currentLayoutType Zona actual ('public' o 'private').
     * @param  string $baseUrl           Base URL del sistema.
     * @return array  Menú agrupado.
     */
    private static function generateDynamicMenu(array $routes, string $currentLayoutType, string $baseUrl = ''): array {
        $menu = [];
        foreach ($routes as $publicUri => $route) {
            if (
                $route['layout_type'] === $currentLayoutType
                && $route['show_in_menu'] == 1
                && $route['is_active']   == 1
            ) {
                // La URI pública ya es correcta (con o sin prefijo /admin/)
                $fullUri = ($baseUrl !== '' && $publicUri === '/')
                    ? $baseUrl . '/'
                    : $baseUrl . $publicUri;

                $group = !empty($route['menu_group']) ? $route['menu_group'] : 'General';

                $menu[$group][] = [
                    'uri'        => $fullUri,
                    'raw_uri'    => $route['raw_uri'] ?? $publicUri,
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