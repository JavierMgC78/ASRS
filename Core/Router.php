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

        // 4. Verificar si la ruta existe en el caché.
        //    Si no existe, intentar resolverla como alias basado en el slug actual
        //    del grupo de menú (ej. /admin/cobros/capturar -> /admin/caja/capturar).
        $resolvedUri = $uri;
        if (!isset($routes[$resolvedUri])) {
            $aliasMap = self::buildSlugAliasMap($routes);
            if (isset($aliasMap[$uri])) {
                $resolvedUri = $aliasMap[$uri];
            }
        }

        if (!isset($routes[$resolvedUri])) {
            self::renderError404();
            return;
        }

        $viewData = $routes[$resolvedUri];

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

    /**
     * Determina la URL de destino (landing page) autorizada para el usuario
     * según su rol y permisos asignados en el sistema ASRS.
     *
     * Evita accesos denegados inmediatos dirigiendo al usuario a su área operativa principal:
     *   - Super Admin: /admin/dashboard
     *   - Caja: /admin/caja/capturar
     *   - Inscripción: /admin/alumnos/crear
     *   - Otros roles: Su primera vista autorizada en role_view_permissions
     *
     * @param  int|null    $userId    ID del usuario (opcional, se obtiene de sesión si es null).
     * @param  int|null    $roleId    ID del rol del usuario (opcional, se obtiene de sesión si es null).
     * @param  string|null $roleType  Tipo de rol ('special', 'standard', etc.).
     * @return string URL pública completa de redirección autorizada.
     */
    public static function getAuthorizedLandingUrl(?int $userId = null, ?int $roleId = null, ?string $roleType = null): string {
        // 1. Resolver datos de sesión activa si no se pasaron explícitamente
        if ($userId === null && isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
        }
        if ($roleId === null && isset($_SESSION['user']['role_id'])) {
            $roleId = (int)$_SESSION['user']['role_id'];
        }
        if ($roleType === null && isset($_SESSION['user'])) {
            $roleType = $_SESSION['user']['role_type'] ?? $_SESSION['user']['type'] ?? null;
        }

        // 2. Obtener el nombre del rol si no está determinado en sesión
        $roleName = $_SESSION['user']['role_name'] ?? $_SESSION['user']['role'] ?? '';
        if (empty($roleName) && $roleId !== null && $roleId > 0) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT name, type FROM roles WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $roleId]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($r) {
                    $roleName = $r['name'] ?? '';
                    if ($roleType === null) {
                        $roleType = $r['type'] ?? 'standard';
                    }
                }
            } catch (\Throwable $e) {
                // Silencioso, continuar con el fallback
            }
        }

        // Normalizar nombre de rol en minúsculas
        $roleNameLower = strtolower(trim($roleName));

        // 3. Roles Especiales / Super Admin -> Panel Principal Dashboard
        if ($roleType === 'special' || $roleNameLower === 'super_admin' || $roleId === 1) {
            return self::url('dashboard', 'private');
        }

        // 4. Rol de Caja -> Captura de Pagos
        if ($roleNameLower === 'caja' || $roleId === 2) {
            return self::url('caja/capturar', 'private');
        }

        // 5. Rol de Inscripción -> Alta de Alumno o Ver Alumnos
        if ($roleNameLower === 'inscripcion' || $roleId === 3) {
            return self::url('alumnos/crear', 'private');
        }

        // 6. Búsqueda dinámica en role_view_permissions para cualquier otro rol operativo
        if ($roleId !== null && $roleId > 0) {
            try {
                $db = Database::getInstance();
                $sql = "SELECT v.uri, v.layout_type
                        FROM role_view_permissions rvp
                        INNER JOIN views v ON rvp.view_id = v.id
                        WHERE rvp.role_id = :role_id
                          AND v.is_active = 1
                          AND v.layout_type = 'private'
                        ORDER BY 
                          CASE 
                            WHEN v.uri LIKE '%dashboard%' THEN 1 
                            WHEN v.show_in_menu = 1 THEN 2 
                            ELSE 3 
                          END,
                          v.id ASC
                        LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([':role_id' => $roleId]);
                $vista = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($vista && !empty($vista['uri'])) {
                    return self::url($vista['uri'], $vista['layout_type'] ?? 'private');
                }
            } catch (\Throwable $e) {
                // Fallback seguro en caso de error
            }
        }

        // 7. Fallback por defecto seguro
        return self::url('dashboard', 'private');
    }

    /**
     * Muestra la página o mensaje de error 403 (Acceso Prohibido).
     *
     * @param  string $message Mensaje explicativo del motivo del bloqueo.
     */
    public static function renderError403(string $message = 'No cuentas con el rol requerido para operar este recurso.'): void {
        http_response_code(403);
        $dashboardUrl = self::url('dashboard', 'private');
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><title>403 - Acceso Denegado</title>";
        echo "<style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}";
        echo ".card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:32px;max-width:480px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.3);}";
        echo "h1{color:#ef4444;margin:0 0 12px 0;font-size:24px;}p{color:#94a3b8;font-size:14px;line-height:1.5;margin:0 0 20px 0;}";
        echo "a{display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:10px 20px;border-radius:6px;font-weight:600;font-size:13px;}</style></head><body>";
        echo "<div class='card'><h1>403 - Acceso Prohibido</h1><p>" . htmlspecialchars($message) . "</p>";
        echo "<a href='" . htmlspecialchars($dashboardUrl) . "'>Regresar al Inicio</a></div></body></html>";
    }

    /**
     * Comprueba si el usuario autenticado tiene autorización para acceder a una vista o recurso.
     *
     * Permite evaluar el acceso mediante el ID numérico de la vista (ej. 14, 15) o su URI (ej. 'alumnos/ver').
     * Consulta la caché de permisos en tiempo O(1) vía PermissionCache y otorga acceso total inmediato
     * a roles especiales (super_admin).
     *
     * @param  int|string  $target   ID de la vista (int) o URI relativa/pública de la vista (string).
     * @param  int|null    $userId   ID del usuario (opcional, se obtiene de la sesión si es null).
     * @param  int|null    $roleId   ID del rol activo (opcional, se obtiene de la sesión si es null).
     * @param  string|null $roleType Tipo de rol ('special' o 'standard', opcional).
     * @return bool True si el usuario tiene acceso permitido, False en caso contrario.
     */
    public static function userCanAccess(int|string $target, ?int $userId = null, ?int $roleId = null, ?string $roleType = null): bool {
        // 1. Resolver datos de la sesión activa si no se proporcionaron explícitamente
        if ($userId === null && isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
        }
        if ($roleId === null && isset($_SESSION['user']['role_id'])) {
            $roleId = (int)$_SESSION['user']['role_id'];
        }
        if ($roleType === null && isset($_SESSION['user'])) {
            $roleType = $_SESSION['user']['role_type'] ?? $_SESSION['user']['type'] ?? 'standard';
        }

        // Si no hay usuario ni rol válido en sesión, denegar acceso
        if ($userId === null || $roleId === null) {
            return false;
        }

        // 2. Roles Especiales / Super Admin poseen autorización global a todos los módulos
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? $_SESSION['user']['role'] ?? '')));
        if ($roleType === 'special' || $roleId === 1 || $roleName === 'super_admin') {
            return true;
        }

        // 3. Resolver el viewId a partir del target recibido (int o string URI)
        $viewId = null;

        if (is_int($target) || ctype_digit((string)$target)) {
            $viewId = (int)$target;
        } else {
            // Es un string de URI: normalizar y buscar en ViewCache
            $routes = ViewCache::getRoutes();
            $cleanUri = '/' . ltrim((string)$target, '/');

            foreach ($routes as $publicUri => $route) {
                if ($publicUri === $cleanUri 
                    || ($route['raw_uri'] ?? '') === $cleanUri 
                    || ('/' . ltrim($route['raw_uri'] ?? '', '/')) === $cleanUri
                    || ltrim($publicUri, '/') === ltrim($cleanUri, '/')) {
                    $viewId = (int)($route['id'] ?? 0);
                    break;
                }
            }

            // Fallback: consultar en base de datos si no se resolvió por ViewCache
            if (!$viewId) {
                try {
                    $db = Database::getInstance();
                    $stmt = $db->prepare("SELECT id FROM views WHERE uri = :uri OR uri = :uri_slash LIMIT 1");
                    $stmt->execute([
                        ':uri'       => ltrim($cleanUri, '/'),
                        ':uri_slash' => $cleanUri
                    ]);
                    $foundId = $stmt->fetchColumn();
                    if ($foundId) {
                        $viewId = (int)$foundId;
                    }
                } catch (\Throwable $e) {
                    // Continuar al control de permiso
                }
            }
        }

        if (!$viewId || $viewId <= 0) {
            return false;
        }

        // 4. Delegar la verificación en PermissionCache (O(1))
        return PermissionCache::userCanAccessView($userId, $roleId, $roleType ?? 'standard', $viewId);
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
        $aliasByTarget = ($currentLayoutType === 'private')
            ? array_flip(self::buildSlugAliasMap($routes))
            : [];
        foreach ($routes as $publicUri => $route) {
            if (
                $route['layout_type'] === $currentLayoutType
                && $route['show_in_menu'] == 1
                && $route['is_active']   == 1
            ) {
                // La URI pública ya es correcta (con o sin prefijo /admin/).
                // Para zona privada se usa el slug vigente del grupo como segmento de ruta.
                $menuUri = $aliasByTarget[$publicUri] ?? $publicUri;
                $fullUri = ($baseUrl !== '' && $menuUri === '/')
                    ? $baseUrl . '/'
                    : $baseUrl . $menuUri;

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
     * Construye el mapa de alias de rutas privadas basado en el slug vigente
     * del grupo de menú (tabla menu_groups, vía MenuGroupCache).
     *
     * Para cada vista privada con raw_uri del tipo '{segmento}/{resto}', el alias
     * reemplaza el primer segmento por el slug del grupo: '/admin/{slug}/{resto}'.
     * Solo se registran alias que difieren de la ruta real y que no colisionan con
     * rutas existentes ni con otros alias.
     *
     * @param  array $routes Mapa del caché de vistas.
     * @return array<string,string> [aliasPublicUri => publicUriReal]
     */
    private static function buildSlugAliasMap(array $routes): array {
        static $cache = [];
        $key = md5(serialize(array_keys($routes)));
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $map = [];
        try {
            $groups = MenuGroupCache::getGroups();
        } catch (\Throwable $e) {
            return $cache[$key] = [];
        }

        foreach ($routes as $publicUri => $route) {
            if (($route['layout_type'] ?? '') !== 'private') continue;

            $groupName = $route['menu_group'] ?? '';
            $slug      = $groups[$groupName]['slug'] ?? '';
            $rawUri    = trim((string)($route['raw_uri'] ?? ''), '/');
            if ($slug === '' || strpos($rawUri, '/') === false) continue;

            $rest  = substr($rawUri, strpos($rawUri, '/') + 1);
            $alias = ViewCache::buildPublicUri($slug . '/' . $rest, 'private');

            if ($alias === $publicUri || isset($routes[$alias]) || isset($map[$alias])) continue;
            $map[$alias] = $publicUri;
        }

        return $cache[$key] = $map;
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