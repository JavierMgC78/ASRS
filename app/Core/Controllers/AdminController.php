<?php
namespace App\Core\Controllers;

/**
 * Controlador de Administración - ASRS
 * Zona privada protegida por Seguridad de Nivel 1 (Rol: admin).
 */
class AdminController extends PrivateViewController {

    /**
     * Panel principal de administración.
     *
     * @param array $route
     * @param array $data
     */
    public function index(array $route = [], array $data = []): void {
        $content = $route['content'] ?? 'vw_dashboard';
        $layout  = $route['layout'] ?? 'lytPr_admin';
        $assets  = $route['assets'] ?? [];

        $data['title']    = 'ASRS | Panel de Administración';
        $data['userRole'] = $this->getCurrentUserRole();

        $this->render($content, $layout, $data, $assets);
    }
}
