<?php

namespace Core;

use PDO;

class SessionManager
{
    public const COOKIE_NAME = 'asrs_session';
    public const COOKIE_DELIMITER = ':';
    public const SESSION_LIFETIME_DAYS = 7;

    /**
     * Crea una nueva sesión por Split Token para el usuario especificado.
     * Genera un selector aleatorio de 32 caracteres y un token secreto de 64 caracteres.
     * Registra la sesión en la base de datos y envía la cookie segura al navegador.
     *
     * @param int $userId ID del usuario
     * @return void
     */
    public static function createSession(int $userId): void
    {
        // 1. Selector aleatorio único de 32 caracteres (16 bytes hex)
        $selector = bin2hex(random_bytes(16));

        // 2. Token secreto de alta entropía de 64 caracteres (32 bytes hex)
        $token = bin2hex(random_bytes(32));

        // 3. Hash seguro del token
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);

        // 4. Expiración a 7 días
        $expiresTimestamp = time() + (self::SESSION_LIFETIME_DAYS * 86400);
        $expiresAt = date('Y-m-d H:i:s', $expiresTimestamp);

        // 5. Almacenar la sesión en la tabla user_sessions mediante PDO Singleton
        $db = Database::getInstance();
        $sql = "INSERT INTO user_sessions (user_id, selector, token_hash, expires_at)
                VALUES (:user_id, :selector, :token_hash, :expires_at)";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
        $stmt->bindValue(':token_hash', $tokenHash, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
        $stmt->execute();

        // 6. Configurar la cookie en el navegador con formato selector:token
        $cookieValue = $selector . self::COOKIE_DELIMITER . $token;

        // Detectar HTTPS dinámicamente para compatibilidad local (HTTP) y producción (HTTPS)
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

        setcookie(
            self::COOKIE_NAME,
            $cookieValue,
            [
                'expires'  => $expiresTimestamp,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Valida la sesión actual del usuario mediante la cookie enviada por el cliente.
     *
     * @return int|null Retorna el user_id si el token y selector son válidos y vigentes, o null en caso contrario.
     */
    public static function validateSession(): ?int
    {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return null;
        }

        $parts = explode(self::COOKIE_DELIMITER, $_COOKIE[self::COOKIE_NAME], 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $token] = $parts;

        $db = Database::getInstance();
        $sql = "SELECT user_id, token_hash, expires_at 
                FROM user_sessions 
                WHERE selector = :selector AND expires_at > NOW() 
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
        $stmt->execute();

        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) {
            return null;
        }

        if (!password_verify($token, $session['token_hash'])) {
            return null;
        }

        return (int)$session['user_id'];
    }

    /**
     * Destruye la sesión activa eliminando el registro en la base de datos y borrando la cookie.
     *
     * @return void
     */
    public static function destroySession(): void
    {
        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $parts = explode(self::COOKIE_DELIMITER, $_COOKIE[self::COOKIE_NAME], 2);
            if (count($parts) === 2) {
                $selector = $parts[0];
                $db = Database::getInstance();
                $stmt = $db->prepare("DELETE FROM user_sessions WHERE selector = :selector");
                $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
                $stmt->execute();
            }

            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

            setcookie(
                self::COOKIE_NAME,
                '',
                [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
            unset($_COOKIE[self::COOKIE_NAME]);
        }
    }
}
