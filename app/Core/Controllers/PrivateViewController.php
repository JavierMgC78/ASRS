<?php
namespace App\Core\Controllers;

/**
 * Controlador de Vistas Privadas - ASRS (Axe Secure Router System)
 * Implementa de forma estricta la Seguridad de Nivel 1:
 *  - Verificación de sesión de usuario activa (autenticación perimetral).
 *  - Validación de roles globales definidos en la ruta contra el rol del usuario.
 *  - Redirección automática a /login si no está autenticado.
 *  - Bloqueo y pantalla institucional HTTP 403 si el rol no cumple los permisos.
 */
class PrivateViewController extends BaseController {

    /**
     * Inicia de forma segura la sesión si aún no está activa.
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Determina si existe una sesión de usuario autenticada.
     *
     * @return bool
     */
    public function isAuthenticated(): bool {
        return !empty($_SESSION['user']) 
            || !empty($_SESSION['user_id']) 
            || !empty($_SESSION['auth']) 
            || !empty($_SESSION['logged_in']) 
            || !empty($_SESSION['role']);
    }

    /**
     * Obtiene el rol del usuario en la sesión actual.
     *
     * @return string|null
     */
    public function getCurrentUserRole(): ?string {
        if (!empty($_SESSION['role'])) {
            return (string)$_SESSION['role'];
        }
        if (!empty($_SESSION['user']['role'])) {
            return (string)$_SESSION['user']['role'];
        }
        if (isset($_SESSION['user']) && is_object($_SESSION['user']) && isset($_SESSION['user']->role)) {
            return (string)$_SESSION['user']->role;
        }
        return null;
    }

    /**
     * Despacha una ruta privada previa validación de Seguridad de Nivel 1.
     *
     * @param array $route Datos de la ruta registrada en routes_cache.php
     * @param array $data  Datos opcionales para la vista
     */
    public function dispatch(array $route, array $data = []): void {
        // Ejecución obligatoria de Seguridad de Nivel 1
        $this->validateSecurityLevel1($route);

        $controllerClass = $route['controller'] ?? null;
        $method          = $route['method'] ?? null;

        // Si la ruta define un controlador concreto que no sea esta misma clase
        if (!empty($controllerClass) && class_exists($controllerClass) && $controllerClass !== static::class) {
            $controllerInstance = new $controllerClass();
            if (!empty($method) && method_exists($controllerInstance, $method)) {
                $controllerInstance->$method($route, $data);
                return;
            }
        }

        // Renderizado del contenido privado en el cascarón correspondiente
        $content = $route['content'] ?? 'vw_dashboard';
        $layout  = $route['layout'] ?? 'lytPr_admin';
        $assets  = $route['assets'] ?? [];

        $this->render($content, $layout, $data, $assets);
    }

    /**
     * Valida la Seguridad de Nivel 1 para la ruta solicitada.
     * Si no cumple la autenticación o los roles requeridos, detiene la ejecución.
     *
     * @param array $route
     */
    public function validateSecurityLevel1(array $route): void {
        // 1. Verificación de autenticación de sesión
        if (!$this->isAuthenticated()) {
            $this->handleUnauthenticated($route);
            exit;
        }

        // 2. Verificación de roles globales de Nivel 1
        $allowedRoles = $route['roles'] ?? [];
        if (!empty($allowedRoles)) {
            $currentRole = $this->getCurrentUserRole();

            if ($currentRole === null || !in_array($currentRole, $allowedRoles, true)) {
                $this->handleForbidden($route, $currentRole, $allowedRoles);
                exit;
            }
        }
    }

    /**
     * Maneja el caso de un usuario no autenticado (redirección al login).
     *
     * @param array $route
     */
    protected function handleUnauthenticated(array $route): void {
        $_SESSION['asrs_flash_message'] = "Debe iniciar sesión para acceder al recurso solicitado.";
        $_SESSION['asrs_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

        $this->redirect('/login');
    }

    /**
     * Detiene la ejecución y muestra la pantalla institucional de HTTP 403 (Acceso Denegado).
     *
     * @param array       $route
     * @param string|null $currentRole
     * @param array       $allowedRoles
     */
    protected function handleForbidden(array $route, ?string $currentRole, array $allowedRoles): void {
        http_response_code(403);

        $roleString = !empty($allowedRoles) ? implode(', ', $allowedRoles) : 'N/A';
        $userRoleDisplay = $currentRole ? htmlspecialchars($currentRole) : 'Sin rol asignado';

        echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>ASRS | 403 Acceso Denegado (Seguridad Nivel 1)</title>
    <link rel='stylesheet' href='/AXE/public/assets/core/css/layouts/lyt_public.css'>
    <style>
        .asrs-forbidden-card {
            background-color: var(--asrs-panel-bg);
            border: 1px solid rgba(217, 37, 37, 0.4);
            border-left: 5px solid var(--asrs-neon-red);
            border-radius: 8px;
            padding: 40px;
            max-width: 680px;
            margin: 40px auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6), 0 0 20px rgba(217, 37, 37, 0.2);
        }
        .asrs-forbidden-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .asrs-forbidden-icon {
            font-size: 2.5rem;
            color: var(--asrs-neon-red);
        }
        .asrs-forbidden-title {
            color: var(--asrs-white);
            font-size: 1.5rem;
            margin: 0;
        }
        .asrs-forbidden-badge {
            display: inline-block;
            background: rgba(217, 37, 37, 0.2);
            color: #ff8080;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-top: 5px;
            border: 1px solid rgba(217, 37, 37, 0.4);
        }
        .asrs-forbidden-info {
            background: #0f1724;
            border: 1px solid rgba(212, 217, 237, 0.15);
            border-radius: 6px;
            padding: 18px;
            margin: 20px 0;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .asrs-forbidden-info strong {
            color: var(--asrs-neon-gold);
        }
        .asrs-forbidden-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        .asrs-btn-back {
            background: transparent;
            color: var(--asrs-border-silver);
            border: 1px solid var(--asrs-border-silver);
            padding: 10px 22px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.2s ease;
        }
        .asrs-btn-back:hover {
            color: var(--asrs-white);
            border-color: var(--asrs-neon-gold);
            box-shadow: 0 0 10px rgba(227, 179, 49, 0.3);
        }
    </style>
</head>
<body class='asrs-layout-public'>
    <header class='asrs-navbar'>
        <div class='asrs-nav-brand'>
            <span class='asrs-brand-icon'>⚡</span>
            <span class='asrs-brand-text'>ASRS <small>Seguridad de Nivel 1</small></span>
        </div>
        <div class='asrs-nav-action'>
            <a href='/' class='asrs-nav-item'>Inicio</a>
        </div>
    </header>

    <main class='asrs-main-content'>
        <div class='asrs-forbidden-card'>
            <div class='asrs-forbidden-header'>
                <div class='asrs-forbidden-icon'>🛡️</div>
                <div>
                    <h2 class='asrs-forbidden-title'>Acceso Restringido (HTTP 403)</h2>
                    <span class='asrs-forbidden-badge'>SEGURIDAD DE NIVEL 1 ACTIVADA</span>
                </div>
            </div>

            <p style='color: var(--asrs-border-silver); font-size: 1rem; line-height: 1.5;'>
                El sistema de seguridad perimetral de ASRS ha bloqueado la solicitud porque su perfil actual no posee los privilegios requeridos para esta ruta.
            </p>

            <div class='asrs-forbidden-info'>
                <p>📍 <strong>Ruta protegida:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . "</p>
                <p>👤 <strong>Rol detectado:</strong> {$userRoleDisplay}</p>
                <p>🔑 <strong>Roles autorizados:</strong> <span style='color: #4ade80;'>{$roleString}</span></p>
            </div>

            <div class='asrs-forbidden-actions'>
                <a href='/' class='asrs-btn-back'>← Volver al Inicio</a>
                <a href='/login' class='asrs-btn-login'>Cambiar de Cuenta</a>
            </div>
        </div>
    </main>

    <footer class='asrs-footer'>
        <div class='asrs-footer-container'>
            <p class='asrs-footer-copy'>&copy; " . date('Y') . " ASRS (Axe Secure Router System). Protección perimetral activa.</p>
        </div>
    </footer>
</body>
</html>";
    }
}
