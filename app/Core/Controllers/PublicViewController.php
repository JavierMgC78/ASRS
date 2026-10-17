<?php
namespace App\Core\Controllers;

/**
 * Controlador de Vistas Públicas - ASRS (Axe Secure Router System)
 * Especializado en el despacho de rutas públicas, preparación del entorno
 * y renderizado de contenido mediante Output Buffering en el cascarón público.
 */
class PublicViewController extends BaseController {

    /**
     * Despacha una ruta pública.
     * Prepara el entorno y delega en un controlador específico si existe,
     * o ejecuta directamente el renderizado virtual.
     *
     * @param array $route Datos de la ruta resuelta desde la caché
     * @param array $data  Datos opcionales para la vista
     */
    public function dispatch(array $route, array $data = []): void {
        $controllerClass = $route['controller'] ?? null;
        $method          = $route['method'] ?? null;

        // Si la ruta especifica un controlador concreto existente que no sea la clase actual
        if (!empty($controllerClass) && class_exists($controllerClass) && $controllerClass !== static::class) {
            $controllerInstance = new $controllerClass();
            if (!empty($method) && method_exists($controllerInstance, $method)) {
                $controllerInstance->$method($route, $data);
                return;
            }
        }

        // Preparar entorno y renderizar la vista pública
        $content = $route['content'] ?? 'vw_home';
        $layout  = $route['layout'] ?? 'lyt_public';
        $assets  = $route['assets'] ?? [];

        $this->render($content, $layout, $data, $assets);
    }
}
