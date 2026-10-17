<?php
namespace App\Core\Controllers;

/**
 * Controlador de Inicio - ASRS
 * Gestiona la página de aterrizaje y presentación del framework.
 */
class HomeController extends PublicViewController {

    /**
     * Renderiza la página principal de ASRS.
     *
     * @param array $route
     * @param array $data
     */
    public function index(array $route = [], array $data = []): void {
        $content = $route['content'] ?? 'vw_home';
        $layout  = $route['layout'] ?? 'lyt_public';
        $assets  = $route['assets'] ?? [];

        $data['title'] = 'ASRS | Enrutador Ultrarrápido O(1)';

        $this->render($content, $layout, $data, $assets);
    }
}
