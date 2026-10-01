<?php

namespace Core\controllers;

use Core\AuthModel;
use Core\Router;
use Core\SessionManager;

class LoginController
{
    /**
     * Maneja las peticiones GET y POST para la vista de inicio de sesión.
     *
     * @return array Arreglo con datos para la vista (ej. errorMessage)
     */
    public static function handle(): array
    {
        $errorMessage = null;

        // Redirigir al panel autorizado si ya existe una sesión válida por Split Token
        if (isset($_COOKIE[SessionManager::COOKIE_NAME])) {
            $userId = SessionManager::validateSession();
            if ($userId !== null) {
                $user = AuthModel::findById($userId);
                if ($user) {
                    $landingUrl = Router::getAuthorizedLandingUrl(
                        (int)$user['id'],
                        (int)$user['role_id'],
                        $user['role_type'] ?? $user['type'] ?? 'standard'
                    );
                    $target = $landingUrl ?: Router::url('dashboard', 'private');
                    if (!headers_sent()) {
                        header('Location: ' . $target);
                    }
                    exit;
                }
            }
        }

        // Procesar credenciales enviadas mediante método POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errorMessage = self::processLogin();
        }

        return [
            'errorMessage' => $errorMessage
        ];
    }

    /**
     * Autentica las credenciales con AuthModel y genera la sesión por Split Token con SessionManager.
     *
     * @return string|null Retorna mensaje de error si falla la autenticación, o redirige al panel autorizado en éxito.
     */
    public static function processLogin(): ?string
    {
        $email = trim($_POST['email'] ?? $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return "Por favor ingresa tu correo electrónico y contraseña.";
        }

        // 1. Verificar credenciales con AuthModel (valida email, password_verify e is_active = 1)
        $user = AuthModel::authenticate($email, $password);

        if (!$user) {
            return "Credenciales inválidas o la cuenta se encuentra inactiva.";
        }

        // 2. Crear sesión mediante el patrón Split Token en SessionManager
        SessionManager::createSession((int)$user['id']);

        // 3. Redirigir fluidamente al panel principal autorizado según sus permisos
        $landingUrl = Router::getAuthorizedLandingUrl(
            (int)$user['id'],
            (int)$user['role_id'],
            $user['role_type'] ?? $user['type'] ?? 'standard'
        );

        $target = $landingUrl ?: Router::url('dashboard', 'private');
        if (!headers_sent()) {
            header('Location: ' . $target);
        }
        exit;
    }
}
