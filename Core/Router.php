<?php

namespace Core;

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

        // 5. Control de Acceso y Seguridad (Zonas y Permisos)
        self::checkAccess($viewData);

        // 6. Determinar Base URL para resolución correcta de assets y rutas
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\');
        if ($baseUrl === '/' || $baseUrl === '\\') {
            $baseUrl = '';
        }

        // 7. Generar el Menú Dinámico para la zona actual (Filtrado en memoria)
        $dynamicMenu = self::generateDynamicMenu($routes, $viewData['layout_type'], $baseUrl);

        // 8. Preparar assets específicos de la vista por convención (basado en menu_title)
        $viewAssetName = self::normalizeNameToAsset($viewData['menu_title']);

        // 9. Renderizado Virtual de la Vista (Output Buffering)
        $viewFilepath = __DIR__ . '/../' . $viewData['file_path'];
        
        if (!file_exists($viewFilepath)) {
            echo "Error crítico: El archivo físico de la vista no existe en: {$viewFilepath}";
            return;
        }

        $viewContent = self::renderVirtualView($viewFilepath, $viewData);

        // 10. Determinar qué template maestro utilizar
        $layoutType = $viewData['layout_type']; // 'public' o 'private'
        $templateFile = ($layoutType === 'public') 
            ? __DIR__ . '/../views/template_public.php' 
            : __DIR__ . '/../views/template_private.php';

        if (!file_exists($templateFile)) {
            echo "Error crítico: El template maestro [{$layoutType}] no existe.";
            return;
        }

        // Variables disponibles para el Template maestro
        $pageTitle = $viewData['menu_title'] . ' - Coffee Flavored Software';
        $brandName = 'Coffee Flavored Software';
        $brandLogoUrl = $baseUrl . '/assets/img/logo.png';
        $currentUri = $uri;

        // 11. Ensamblaje y Renderizado Final
        require_once $templateFile;
    }

    /**
     * Valida los permisos de acceso a la vista.
     */
    private static function checkAccess($viewData) {
        // Ejemplo de validación de zonas privadas
        if ($viewData['layout_type'] === 'private') {
            // Aquí verificarías si existe sesión de usuario activo
            // Si no está logueado, redirigir al login:
            // header('Location: /admin/login'); exit;
        }
    }

    /**
     * Filtra el menú dinámicamente según la zona y si debe mostrarse.
     */
    private static function generateDynamicMenu($routes, $currentLayoutType, $baseUrl = '') {
        $menu = [];
        foreach ($routes as $route) {
            if ($route['layout_type'] === $currentLayoutType && $route['show_in_menu'] == 1 && $route['is_active'] == 1) {
                $routeUri = ($route['uri'] === '/') ? '/' : $route['uri'];
                $fullUri = ($baseUrl !== '' && $routeUri === '/') ? $baseUrl . '/' : $baseUrl . $routeUri;
                $menu[] = [
                    'uri' => $fullUri,
                    'raw_uri' => $route['uri'],
                    'menu_title' => $route['menu_title']
                ];
            }
        }
        return $menu;
    }

    /**
     * Normaliza el menu_title a un nombre válido de archivo de asset (ej: "Reporte Cobros" -> "reporte_cobros")
     */
    private static function normalizeNameToAsset($menuTitle) {
        $slug = mb_strtolower(trim($menuTitle));
        $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
        return preg_replace('/[^a-z0-9_]/', '_', str_replace(' ', '_', $slug));
    }

    /**
     * Ejecuta el búfer de salida para capturar la vista virtualmente.
     */
    private static function renderVirtualView($filepath, $viewData) {
        ob_start();
        // Se pueden inyectar datos adicionales a la vista si es necesario
        include $filepath;
        return ob_get_clean();
    }

    /**
     * Manejo básico de página no encontrada.
     */
    private static function renderError404() {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>La ruta solicitada no existe en el sistema ASRS.</p>";
    }
}