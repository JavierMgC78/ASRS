<?php

namespace Core;

use PDO;

class AuthModel
{
    /**
     * Busca un usuario activo por su correo electrónico, realizando un JOIN con la tabla roles
     * para incluir la información del rol y su tipo ('standard' o 'special').
     *
     * @param string $email Correo electrónico del usuario
     * @return array|null Retorna el arreglo con los datos del usuario o null si no se encuentra o está inactivo.
     */
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getInstance();

        $sql = "SELECT 
                    u.*, 
                    r.name AS role, 
                    r.name AS role_name, 
                    r.type AS role_type, 
                    r.type AS type
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.email = :email AND u.is_active = 1
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Busca un usuario activo por su ID, realizando un JOIN con la tabla roles.
     *
     * @param int $id ID del usuario
     * @return array|null Retorna los datos del usuario o null si no existe o está inactivo.
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();

        $sql = "SELECT 
                    u.*, 
                    r.name AS role, 
                    r.name AS role_name, 
                    r.type AS role_type, 
                    r.type AS type
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id AND u.is_active = 1
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Valida de manera segura una contraseña contra su hash usando password_verify.
     *
     * @param string $password Contraseña en texto plano a verificar
     * @param string $passwordHash Hash almacenado en la base de datos
     * @return bool True si la contraseña coincide, False en caso contrario.
     */
    public static function verifyPassword(string $password, string $passwordHash): bool
    {
        return password_verify($password, $passwordHash);
    }

    /**
     * Autentica las credenciales de un usuario.
     * Verifica que el usuario exista por su correo, esté activo (is_active = 1)
     * y que la contraseña enviada coincida con password_hash.
     *
     * @param string $email Correo electrónico
     * @param string $password Contraseña ingresada
     * @return array|null Arreglo con datos del usuario autenticado o null si falla la autenticación.
     */
    public static function authenticate(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);

        if (!$user) {
            return null;
        }

        // Validación de seguridad adicional para asegurar is_active = 1
        if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            return null;
        }

        if (empty($user['password_hash']) || !self::verifyPassword($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }
}
