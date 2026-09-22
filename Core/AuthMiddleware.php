<?php

namespace Core;

use PDO;

class AuthMiddleware
{
    /**
     * Valida la sesión activa por Split Token.
     * Lee la cookie, extrae el selector y token, consulta user_sessions por selector,
     * comprueba la caducidad (expires_at > NOW()), valida el token con password_verify
     * y carga la información completa del usuario y su rol en $_SESSION['user'].
     * Si falla cualquier validación, borra las cookies/sesión y redirige al login.
     *
     * @return bool Retorna true si la sesión es válida.
     */
    public static function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $cookieName = SessionManager::COOKIE_NAME;

        // 1. Leer la cookie de sesión del navegador
        if (empty($_COOKIE[$cookieName])) {
            self::rejectAndRedirect();
            return false;
        }

        // 2. Separar la cookie en selector y token
        $cookieValue = $_COOKIE[$cookieName];
        $parts = explode(SessionManager::COOKIE_DELIMITER, $cookieValue, 2);

        if (count($parts) !== 2) {
            self::rejectAndRedirect();
            return false;
        }

        [$selector, $token] = $parts;

        // 3. Consultar user_sessions usando únicamente el selector y verificando que expires_at > NOW()
        $db = Database::getInstance();
        $sql = "SELECT id, user_id, token_hash, expires_at 
                FROM user_sessions 
                WHERE selector = :selector AND expires_at > NOW() 
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
        $stmt->execute();

        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            self::rejectAndRedirect();
            return false;
        }

        // 4. Validar el token secreto contra el token_hash usando password_verify
        if (!password_verify($token, $session['token_hash'])) {
            self::rejectAndRedirect();
            return false;
        }

        // 5. Cargar los datos del usuario y su rol en $_SESSION['user']
        $user = AuthModel::findById((int)$session['user_id']);

        if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
            self::rejectAndRedirect();
            return false;
        }

        $_SESSION['user'] = $user;

        return true;
    }

    /**
     * Destruye las cookies de sesión y redirige al usuario a la página de login.
     */
    private static function rejectAndRedirect(): void
    {
        SessionManager::destroySession();

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['user']);
            session_destroy();
        }

        if (!headers_sent()) {
            header('Location: /admin/login');
        }
        exit;
    }
}
