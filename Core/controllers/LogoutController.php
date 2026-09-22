<?php

namespace Core\controllers;

use Core\SessionManager;

class LogoutController
{
    /**
     * Cierra la sesión activa del usuario:
     * 1. Lee la cookie de sesión actual.
     * 2. Elimina el registro en la tabla user_sessions mediante el selector.
     * 3. Expira y borra la cookie del navegador (setcookie con tiempo en el pasado).
     * 4. Destruye la sesión activa de PHP (session_destroy()).
     * 5. Redirige al usuario hacia la ruta pública principal (/).
     *
     * @return void
     */
    public static function logout(): void
    {
        // 1, 2 y 3. Borrar sesión en BD por selector y expirar cookie de navegador
        SessionManager::destroySession();

        // 4. Destruir la sesión activa de PHP
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        // 5. Redirigir limpiamente hacia la ruta pública principal (/)
        if (!headers_sent()) {
            header('Location: /');
        }
        exit;
    }
}
