<?php
namespace App\Core\Controllers;

use RuntimeException;

/**
 * Controlador Base - ASRS (Axe Secure Router System)
 * Provee el motor de renderizado virtual mediante Output Buffering (ob_start / ob_get_clean)
 * y utilidades comunes para controladores públicos y privados.
 */
abstract class BaseController {

    /**
     * Resuelve la ruta física del archivo de contenido de la vista.
     *
     * @param string $content Nombre del archivo de contenido sin extensión (ej. 'vw_home')
     * @return string Ruta física absoluta al archivo
     */
    protected function resolveViewPath(string $content): string {
        $viewPath = __DIR__ . '/../Views/views_content/' . $content . '.php';

        if (!file_exists($viewPath)) {
            // Soporte alternativo modular por si se pasa una ruta con subdirectorios
            $altPath = __DIR__ . '/../../' . ltrim($content, '/') . '.php';
            if (file_exists($altPath)) {
                return $altPath;
            }
        }

        return $viewPath;
    }

    /**
     * Resuelve la ruta física del cascarón visual (layout).
     *
     * @param string $layout Nombre del layout (ej. 'lyt_public')
     * @return string Ruta física absoluta al archivo
     */
    protected function resolveLayoutPath(string $layout): string {
        $layoutPath = __DIR__ . '/../Views/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            // Soporte fallback para variaciones con puntos (ej. lyt.public.php)
            $dotPath = __DIR__ . '/../Views/layouts/' . str_replace('_', '.', $layout) . '.php';
            if (file_exists($dotPath)) {
                return $dotPath;
            }
        }

        return $layoutPath;
    }

    /**
     * Motor de Renderizado Virtual con Inyección de Vistas (Output Buffering).
     * Captura el contenido de views_content/{content}.php en la variable $viewContent
     * e incluye el cascarón layouts/{layout}.php.
     *
     * @param string $content Nombre de la vista de contenido
     * @param string $layout  Nombre del cascarón visual
     * @param array  $data    Variables a inyectar al ámbito de las vistas
     * @param array  $assets  Array asociativo de assets ('css' => '...', 'js' => '...')
     * 
     * @throws RuntimeException Si el archivo de contenido o el cascarón no existen
     */
    public function render(string $content, string $layout, array $data = [], array $assets = []): void {
        $contentFile = $this->resolveViewPath($content);
        if (!file_exists($contentFile)) {
            throw new RuntimeException("Error de Renderizado ASRS: La vista de contenido '{$content}.php' no existe en [app/Core/Views/views_content/].");
        }

        $layoutFile = $this->resolveLayoutPath($layout);
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Error de Renderizado ASRS: El cascarón visual (layout) '{$layout}.php' no existe en [app/Core/Views/layouts/].");
        }

        // 1. Extraer variables al ámbito local para el contenido
        extract($data, EXTR_SKIP);

        // 2. Capturar mediante Output Buffering el archivo de contenido
        ob_start();
        include $contentFile;
        $viewContent = ob_get_clean();

        // 3. Preparar variables auxiliares y assets para el cascarón desde el bloque 'assets'
        $css_asset = $assets['css'] ?? '';
        $js_asset  = $assets['js'] ?? '';

        // 4. Inyectar y renderizar el cascarón visual que contiene $viewContent
        include $layoutFile;
    }

    /**
     * Redirecciona de forma segura a una URL especificada.
     *
     * @param string $url
     * @param int $statusCode
     */
    protected function redirect(string $url, int $statusCode = 302): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header("Location: {$url}");
            exit;
        }

        echo "<script>window.location.href = " . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
        exit;
    }

    /**
     * Emite una respuesta en formato JSON.
     *
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json(mixed $data, int $statusCode = 200): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
