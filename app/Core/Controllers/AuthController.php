<?php
namespace App\Core\Controllers;

/**
 * Controlador de Autenticación - ASRS
 * Gestiona el inicio de sesión y la preparación de credenciales de usuario.
 */
class AuthController extends PublicViewController {

    /**
     * Muestra el formulario de inicio de sesión.
     *
     * @param array $route
     * @param array $data
     */
    public function showLogin(array $route = [], array $data = []): void {
        $content = $route['content'] ?? 'vw_login';
        $layout  = $route['layout'] ?? 'lyt_public';
        $assets  = $route['assets'] ?? [];

        $data['title'] = 'ASRS | Iniciar Sesión';

        $this->render($content, $layout, $data, $assets);
    }
}
