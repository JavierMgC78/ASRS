<?php
namespace asrsCore\Core;

class RoutingEngine {
    private const DEFAULT_APP = 'ceaWeb';
    private const SAFE_NAME   = '#^[A-Za-z0-9_]+(?:/[A-Za-z0-9_]+)*$#';

    /**
     * Resuelve una URI contra la caché de rutas (búsqueda O(1) por "app|uri").
     * Retorna null si no existe, no es pública o los archivos físicos no existen.
     *
     * @return array|null Ruta con claves extra: layoutFile, contentFile, cssFiles, jsFiles
     */
    public static function resolve(string $uri, array $routes, string $app = self::DEFAULT_APP): ?array {
        $uri = trim($uri, '/');
        $route = $routes[$app . '|' . $uri] ?? null;

        if ($route === null || ($route['type'] ?? 'public') !== 'public') {
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
