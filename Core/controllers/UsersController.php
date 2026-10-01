<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use PDO;
use Throwable;

class UsersController
{
    private const REQUIRED_ROLE = 'super_admin';

    /**
     * Punto de entrada principal para el módulo de edición de usuarios.
     *
     * @return array{error: string|null, success: string|null, user: array|null, roles: array, allUsers: array}
     */
    public static function handle(): array
    {
        // 1. Control de acceso: solo super_admin puede editar usuarios
        AuthMiddleware::requireRole(self::REQUIRED_ROLE);

        $errorMsg   = null;
        $successMsg = null;

        // 2. Determinar el ID del usuario objetivo a editar
        $currentSessionUserId = (int)($_SESSION['user']['id'] ?? 1);
        $rawId = $_POST['id'] ?? $_GET['id'] ?? null;
        $targetUserId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        // 3. Procesar formulario POST para UPDATE
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || isset($_POST['ajax'])
                || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            $updateResult = self::processUpdate($targetUserId, $_POST, $currentSessionUserId);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => empty($updateResult['error']),
                    'message' => $updateResult['error'] ?? $updateResult['success'],
                    'data'    => $updateResult['user'] ?? null,
                ]);
                exit;
            }

            $errorMsg   = $updateResult['error'] ?? null;
            $successMsg = $updateResult['success'] ?? null;
            if (!empty($updateResult['targetId'])) {
                $targetUserId = (int)$updateResult['targetId'];
            }
        }

        // Si aún no se especificó un ID por GET, tomar el de la sesión o el primer usuario registrado
        if (!$targetUserId) {
            $targetUserId = $currentSessionUserId;
        }

        // 4. Recuperar datos seguros con PDO
        $roles    = self::getRoles();
        $allUsers = self::getAllUsers();
        $user     = self::getUser($targetUserId);

        if (!$user && !$errorMsg) {
            $errorMsg = "El usuario con ID #{$targetUserId} no fue encontrado o ha sido dado de baja.";
            // Si no se encuentra, intentar cargar el primer usuario de la lista como fallback
            if (!empty($allUsers[0])) {
                $user = self::getUser((int)$allUsers[0]['id']);
            }
        }

        return [
            'error'    => $errorMsg,
            'success'  => $successMsg,
            'user'     => $user,
            'roles'    => $roles,
            'allUsers' => $allUsers,
        ];
    }

    /**
     * Recupera la información completa de un usuario y los datos de su rol asociado.
     *
     * @param int $userId Identificador único del usuario.
     * @return array|null Datos del usuario o null si no existe.
     */
    public static function getUser(int $userId): ?array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT u.id, u.name, u.email, u.role_id, u.is_active, u.created_at,
                           r.name AS role_name, r.type AS role_type, r.description AS role_description
                    FROM users u
                    JOIN roles r ON r.id = u.role_id
                    WHERE u.id = :id
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;

        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Obtiene el catálogo de roles registrados en el sistema.
     *
     * @return array
     */
    public static function getRoles(): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT id, name, type, description FROM roles ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene el listado sintetizado de todos los usuarios para el selector rápido.
     *
     * @return array
     */
    public static function getAllUsers(): array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT u.id, u.name, u.email, u.role_id, u.is_active, u.created_at,
                           r.name AS role_name
                    FROM users u
                    JOIN roles r ON r.id = u.role_id
                    ORDER BY u.name ASC";

            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Procesa la actualización segura de un usuario mediante PDO y sentencias preparadas.
     *
     * @param int|null $targetUserId ID del usuario a modificar.
     * @param array    $data         Datos provenientes del formulario POST.
     * @param int      $sessionUserId ID del administrador autenticado.
     * @return array{error: string|null, success: string|null, user: array|null, targetId: int|null}
     */
    public static function processUpdate(?int $targetUserId, array $data, int $sessionUserId): array
    {
        $id = filter_var($data['id'] ?? $targetUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$id) {
            return ['error' => 'Identificador de usuario no válido o no proporcionado.', 'success' => null, 'user' => null, 'targetId' => null];
        }

        $name            = trim($data['name'] ?? '');
        $email           = trim($data['email'] ?? '');
        $roleId          = filter_var($data['role_id'] ?? null, FILTER_VALIDATE_INT);
        $isActive        = isset($data['is_active']) ? ((int)$data['is_active'] === 1 ? 1 : 0) : 0;
        $password        = trim($data['password'] ?? '');
        $passwordConfirm = trim($data['password_confirm'] ?? '');

        // 1. Validaciones de Negocio y Sanitización
        if ($name === '') {
            return ['error' => 'El nombre completo del usuario es obligatorio.', 'success' => null, 'user' => null, 'targetId' => $id];
        }
        if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
            return ['error' => 'El nombre debe contener entre 3 y 150 caracteres.', 'success' => null, 'user' => null, 'targetId' => $id];
        }

        if ($email === '') {
            return ['error' => 'El correo electrónico es obligatorio.', 'success' => null, 'user' => null, 'targetId' => $id];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'El formato del correo electrónico ingresado no es válido.', 'success' => null, 'user' => null, 'targetId' => $id];
        }
        if (mb_strlen($email) > 150) {
            return ['error' => 'El correo electrónico no puede superar los 150 caracteres.', 'success' => null, 'user' => null, 'targetId' => $id];
        }

        if (!$roleId || $roleId <= 0) {
            return ['error' => 'Debes seleccionar un rol válido para el usuario.', 'success' => null, 'user' => null, 'targetId' => $id];
        }

        // Validación de Contraseña (si se desea cambiar)
        $updatePassword = false;
        $hashedPassword = null;
        if ($password !== '') {
            if (mb_strlen($password) < 6) {
                return ['error' => 'La nueva contraseña debe tener al menos 6 caracteres.', 'success' => null, 'user' => null, 'targetId' => $id];
            }
            if ($passwordConfirm !== '' && $password !== $passwordConfirm) {
                return ['error' => 'Las contraseñas ingresadas no coinciden. Por favor verifícalas.', 'success' => null, 'user' => null, 'targetId' => $id];
            }
            $updatePassword = true;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            $db = Database::getInstance();

            // 2. Verificar existencia del usuario actual
            $currentUser = self::getUser($id);
            if (!$currentUser) {
                return ['error' => 'El usuario que intentas modificar no existe en el sistema.', 'success' => null, 'user' => null, 'targetId' => $id];
            }

            // 3. Regla de Seguridad Crítica: El super_admin no puede auto-desactivarse
            if ($id === $sessionUserId && $isActive === 0) {
                return ['error' => 'Acción denegada: No puedes desactivar tu propia cuenta de administrador en sesión.', 'success' => null, 'user' => $currentUser, 'targetId' => $id];
            }

            // 4. Verificar existencia del rol en la base de datos
            $stmtRole = $db->prepare("SELECT id, name FROM roles WHERE id = :role_id LIMIT 1");
            $stmtRole->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmtRole->execute();
            $roleExists = $stmtRole->fetch(PDO::FETCH_ASSOC);

            if (!$roleExists) {
                return ['error' => 'El rol seleccionado no existe en el sistema.', 'success' => null, 'user' => $currentUser, 'targetId' => $id];
            }

            // 5. Verificar unicidad de correo excluyendo al usuario actual
            $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmtCheckEmail->bindValue(':email', $email, PDO::PARAM_STR);
            $stmtCheckEmail->bindValue(':id',    $id,    PDO::PARAM_INT);
            $stmtCheckEmail->execute();

            if ($stmtCheckEmail->fetch()) {
                return ['error' => "El correo electrónico '<strong>{$email}</strong>' ya está asignado a otro usuario.", 'success' => null, 'user' => $currentUser, 'targetId' => $id];
            }

            // 6. Ejecutar UPDATE seguro con PDO
            if ($updatePassword) {
                $sql = "UPDATE users 
                        SET name = :name, 
                            email = :email, 
                            role_id = :role_id, 
                            is_active = :is_active, 
                            password_hash = :password_hash 
                        WHERE id = :id";
            } else {
                $sql = "UPDATE users 
                        SET name = :name, 
                            email = :email, 
                            role_id = :role_id, 
                            is_active = :is_active 
                        WHERE id = :id";
            }

            $stmtUpdate = $db->prepare($sql);
            $stmtUpdate->bindValue(':name',      $name,     PDO::PARAM_STR);
            $stmtUpdate->bindValue(':email',     $email,    PDO::PARAM_STR);
            $stmtUpdate->bindValue(':role_id',   $roleId,   PDO::PARAM_INT);
            $stmtUpdate->bindValue(':is_active', $isActive, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id',        $id,       PDO::PARAM_INT);

            if ($updatePassword) {
                $stmtUpdate->bindValue(':password_hash', $hashedPassword, PDO::PARAM_STR);
            }

            $stmtUpdate->execute();

            // 7. Si se actualizó el usuario actual en sesión, actualizar sesión activa
            if ($id === $sessionUserId) {
                $_SESSION['user']['name']      = $name;
                $_SESSION['user']['email']     = $email;
                $_SESSION['user']['role_id']   = $roleId;
                $_SESSION['user']['role_name'] = $roleExists['name'];
            }

            // Recuperar datos actualizados
            $updatedUser = self::getUser($id);

            $msg = "Usuario '<strong>" . htmlspecialchars($name) . "</strong>' (ID #{$id}) actualizado con éxito.";
            if ($updatePassword) {
                $msg .= " La contraseña también fue renovada.";
            }

            return [
                'error'    => null,
                'success'  => $msg,
                'user'     => $updatedUser,
                'targetId' => $id,
            ];

        } catch (Throwable $e) {
            return [
                'error'    => 'Error de base de datos al procesar la actualización: ' . $e->getMessage(),
                'success'  => null,
                'user'     => self::getUser($id),
                'targetId' => $id,
            ];
        }
    }

    /**
     * Punto de entrada para el módulo de alta (creación) de usuarios.
     *
     * @return array{error: string|null, success: string|null, roles: array, newUserId: int|null}
     */
    public static function handleCreate(): array
    {
        AuthMiddleware::requireRole(self::REQUIRED_ROLE);

        $errorMsg   = null;
        $successMsg = null;
        $newUserId  = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || isset($_POST['ajax'])
                || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            $createResult = self::processCreate($_POST);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => empty($createResult['error']),
                    'message' => $createResult['error'] ?? $createResult['success'],
                    'id'      => $createResult['id'] ?? null,
                ]);
                exit;
            }

            $errorMsg   = $createResult['error'] ?? null;
            $successMsg = $createResult['success'] ?? null;
            $newUserId  = $createResult['id'] ?? null;
        }

        $roles = self::getRoles();

        return [
            'error'     => $errorMsg,
            'success'   => $successMsg,
            'roles'     => $roles,
            'newUserId' => $newUserId,
        ];
    }

    /**
     * Procesa la inserción de un nuevo usuario en la base de datos mediante PDO y sentencias preparadas.
     *
     * @param array $data Datos del formulario POST.
     * @return array{error: string|null, success: string|null, id: int|null}
     */
    public static function processCreate(array $data): array
    {
        $name            = trim($data['name'] ?? '');
        $email           = trim($data['email'] ?? '');
        $roleId          = filter_var($data['role_id'] ?? null, FILTER_VALIDATE_INT);
        $isActive        = isset($data['is_active']) ? ((int)$data['is_active'] === 1 ? 1 : 0) : 1;
        $password        = trim($data['password'] ?? '');
        $passwordConfirm = trim($data['password_confirm'] ?? '');

        // 1. Validaciones del Servidor
        if ($name === '') {
            return ['error' => 'El nombre completo es obligatorio.', 'success' => null, 'id' => null];
        }
        if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
            return ['error' => 'El nombre debe contener entre 3 y 150 caracteres.', 'success' => null, 'id' => null];
        }

        if ($email === '') {
            return ['error' => 'El correo electrónico es obligatorio.', 'success' => null, 'id' => null];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'El formato del correo electrónico ingresado no es válido.', 'success' => null, 'id' => null];
        }
        if (mb_strlen($email) > 150) {
            return ['error' => 'El correo electrónico no puede superar los 150 caracteres.', 'success' => null, 'id' => null];
        }

        if (!$roleId || $roleId <= 0) {
            return ['error' => 'Debes seleccionar un rol válido para el usuario.', 'success' => null, 'id' => null];
        }

        if ($password === '') {
            return ['error' => 'La contraseña es obligatoria para nuevos usuarios.', 'success' => null, 'id' => null];
        }
        if (mb_strlen($password) < 6) {
            return ['error' => 'La contraseña debe contener al menos 6 caracteres.', 'success' => null, 'id' => null];
        }
        if ($passwordConfirm !== '' && $password !== $passwordConfirm) {
            return ['error' => 'Las contraseñas no coinciden. Por favor verifícalas.', 'success' => null, 'id' => null];
        }

        try {
            $db = Database::getInstance();

            // 2. Verificar existencia del rol
            $stmtRole = $db->prepare("SELECT id, name FROM roles WHERE id = :role_id LIMIT 1");
            $stmtRole->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmtRole->execute();
            $roleExists = $stmtRole->fetch(PDO::FETCH_ASSOC);

            if (!$roleExists) {
                return ['error' => 'El rol seleccionado no existe en el sistema.', 'success' => null, 'id' => null];
            }

            // 3. Verificar si el correo ya está en uso
            $stmtEmail = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmtEmail->bindValue(':email', $email, PDO::PARAM_STR);
            $stmtEmail->execute();

            if ($stmtEmail->fetch()) {
                return ['error' => "Ya existe una cuenta registrada con el correo '<strong>{$email}</strong>'. Elige uno diferente.", 'success' => null, 'id' => null];
            }

            // 4. Cifrado seguro de contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 5. Inserción preparada con PDO
            $sql = "INSERT INTO users (name, email, password_hash, role_id, is_active, created_at)
                    VALUES (:name, :email, :password_hash, :role_id, :is_active, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':name',          $name,          PDO::PARAM_STR);
            $stmt->bindValue(':email',         $email,         PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $passwordHash,  PDO::PARAM_STR);
            $stmt->bindValue(':role_id',       $roleId,        PDO::PARAM_INT);
            $stmt->bindValue(':is_active',     $isActive,      PDO::PARAM_INT);
            $stmt->execute();

            $newId = (int)$db->lastInsertId();

            return [
                'error'   => null,
                'success' => "Usuario '<strong>{$name}</strong>' (ID #{$newId}) registrado exitosamente con rol '{$roleExists['name']}'.",
                'id'      => $newId,
            ];

        } catch (Throwable $e) {
            return [
                'error'   => 'Error en la base de datos al registrar el usuario: ' . $e->getMessage(),
                'success' => null,
                'id'      => null,
            ];
        }
    }
}

