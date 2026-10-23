<?php
namespace asrsCore\Core;

class RoutingEngine {
    private const DEFAULT_APP = 'ceaWeb';
    private const SAFE_NAME   = '#^[A-Za-z0-9_]+(?:/[A-Za-z0-9_]+)*$#';

    /**
     * Identificador de la app con is_default = 1 y status = 1.
     * asrs_views.app guarda el nombre de la app (asrs_apps.name), por lo que se
     * prioriza `name` y se usa `app_key` como alternativa. Si no hay, la app interna.
     */
    private static function defaultApp(array $apps): string {
        foreach ($apps as $a) {
            if ((int)($a['is_default'] ?? 0) !== 1 || (int)($a['status'] ?? 1) !== 1) {
                continue;
            }
            foreach (['name', 'app_key'] as $field) {
                $v = (string)($a[$field] ?? '');
                if (preg_match('#^[A-Za-z0-9_]+$#', $v)) {
                    return $v;
                }
            }
        }
        return self::DEFAULT_APP;
    }

    /**
     * Resuelve una URI contra la caché de rutas (búsqueda O(1) por "app|uri").
     * Retorna null si no existe, no es pública o los archivos físicos no existen.
     *
     * @param array $apps Contenido de apps.php (filas de asrs_apps)
     * @return array|null Ruta con claves extra: layoutFile, contentFile, cssFiles, jsFiles
     */
    public static function resolve(?string $uri, array $routes, array $apps = [], ?string $app = null): ?array {
        // Normaliza: null, '', '/' (y variantes como '//') => URI raíz ''
        $uri = trim((string)$uri, " \t\n\r\0\x0B/");

        // Raíz del sitio: usar la app con is_default = 1 definida en apps.php
        $explicitApp = $app !== null;
        if ($app === null) {
            $app = self::defaultApp($apps);
        }

        // Clave compuesta "app_key|uri" (para la raíz: "app_key|")
        $key   = $app . '|' . $uri;
        $route = $routes[$key] ?? null;

        // Tolerancia a mayúsculas/minúsculas entre asrs_apps.app_key y asrs_views.app
        if ($route === null) {
            foreach ($routes as $k => $r) {
                if (is_array($r) && strcasecmp((string)$k, $key) === 0) {
                    $route = $r;
                    break;
                }
            }
        }

        // URIs no raíz (p. ej. "webcea/nosotros"): la uri identifica a su app aunque no sea la predeterminada
        if ($route === null && $uri !== '' && !$explicitApp) {
            foreach ($routes as $r) {
                if (is_array($r) && ($r['uri'] ?? null) === $uri) {
                    $route = $r;
                    break;
                }
            }
        }

        if ($route === null || ($route['type'] ?? 'public') !== 'public') {
            return null;
        }

        // Usar el nombre de app registrado en la ruta (define directorios de Views/assets)
        $app = (string)($route['app'] ?? $app);
        if (!preg_match('#^[A-Za-z0-9_]+$#', $app)) {
            return null;
        }

        $layout  = (string)($route['layout'] ?? '');
        $content = (string)($route['content'] ?? '');

        // Evita path traversal con datos provenientes de la BD/caché
        if (!preg_match(self::SAFE_NAME, $layout) || !preg_match(self::SAFE_NAME, $content)) {
            return null;
        }

        $viewsDir    = dirname(__DIR__) . "/apps/{$app}/Views";
        $layoutFile  = "{$viewsDir}/layouts/{$layout}.php";
        $contentFile = "{$viewsDir}/contents/{$content}.php";

        if (!is_file($layoutFile) || !is_file($contentFile)) {
            return null;
        }

        // Assets opcionales (relativos a public/, se resuelven con <base href>)
        $publicDir = dirname(__DIR__, 2) . '/public';
        $assets = ['css' => [], 'js' => []];
        foreach (['css', 'js'] as $type) {
            foreach (["layouts/{$layout}", "contents/{$content}"] as $name) {
                $rel = "assets/{$app}/{$type}/{$name}.{$type}";
                if (is_file("{$publicDir}/{$rel}")) {
                    $assets[$type][] = $rel;
                }
            }
        }

        return $route + [
            'layoutFile'  => $layoutFile,
            'contentFile' => $contentFile,
            'cssFiles'    => $assets['css'],
            'jsFiles'     => $assets['js'],
        ];
    }
}
