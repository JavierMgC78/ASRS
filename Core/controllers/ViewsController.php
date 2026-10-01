<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use Core\ViewCache;
use PDO;

class ViewsController
{
    private const SUPER_ADMIN_ROLE = 'super_admin';
    private const SUPER_ADMIN_ROLE_ID = 1;

    /**
     * Punto de entrada principal. Verifica rol, detecta método HTTP y delega.
     *
     * @return array ['error' => string|null, 'success' => string|null]
     */
    public static function handle(): array
    {
        // Restricción exclusiva: solo super_admin puede acceder
        AuthMiddleware::requireRole(self::SUPER_ADMIN_ROLE);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return self::processCreate();
        }

        return ['error' => null, 'success' => null];
    }

    /**
     * Procesa el formulario POST para registrar una nueva vista.
     *
     * Contrato de URI:
     *   - La URI se almacena en BD SIN prefijo de zona (ej. 'roles/ver', 'dashboard', '/').
     *   - El Router (vía ViewCache::buildPublicUri) añade automáticamente el prefijo
     *     /admin/ para vistas con layout_type = 'private' al construir la URL pública.
     *   - Si el usuario ingresó el prefijo /admin/ por error, se elimina antes de guardar.
     *
     * @return array ['error' => string|null, 'success' => string|null]
     */
    public static function processCreate(): array
    {
        // 1. Sanitizar campos del formulario
        $menuTitle   = trim($_POST['menu_title'] ?? '');
        $menuGroup   = trim($_POST['menu_group'] ?? 'General');
        $uri         = trim($_POST['uri'] ?? '');
        $nameFile    = trim($_POST['name_file'] ?? '');
        $filePath    = trim($_POST['file_path'] ?? '');
        $layoutType  = in_array($_POST['layout_type'] ?? '', ['public', 'private'])
                       ? $_POST['layout_type']
                       : 'private';
        $showInMenu  = isset($_POST['show_in_menu']) ? 1 : 0;
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        // 2. Normalizar URI: eliminar prefijo /admin/ si fue incluido por error
        $adminPrefix = \Core\ViewCache::PRIVATE_PREFIX;
        if (str_starts_with($uri, $adminPrefix . '/') || $uri === $adminPrefix) {
            $uri = '/' . ltrim(substr($uri, strlen($adminPrefix)), '/');
        }
        // Asegurar que empiece con /
        if ($uri !== '' && $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        // 3. Validaciones básicas
        if (empty($menuTitle)) {
            return ['error' => 'El campo "Título del Menú" es obligatorio.', 'success' => null];
        }
        if (empty($uri)) {
            return ['error' => 'La URI generada no puede estar vacía.', 'success' => null];
        }
        // Permitir '/' o segmentos con letras, números, guiones y barras
        if ($uri !== '/' && !preg_match('#^/[a-z0-9/_-]+$#', $uri)) {
            return ['error' => 'La URI contiene caracteres inválidos. Usa solo minúsculas, números, guiones y barras.', 'success' => null];
        }
        if (empty($filePath)) {
            return ['error' => 'La ruta física del archivo no puede estar vacía.', 'success' => null];
        }

        $db = Database::getInstance();

        // 4. Verificar que la URI no esté duplicada (comparar en BD, sin prefijo)
        $stmtCheck = $db->prepare('SELECT id FROM views WHERE uri = :uri LIMIT 1');
        $stmtCheck->bindValue(':uri', $uri, PDO::PARAM_STR);
        $stmtCheck->execute();

        if ($stmtCheck->fetch()) {
            // Mostrar la URL pública real en el mensaje de error para mayor claridad
            $publicUri = \Core\ViewCache::buildPublicUri($uri, $layoutType);
            return ['error' => "La URL pública <strong>{$publicUri}</strong> ya existe en el sistema. Elige un título diferente.", 'success' => null];
        }

        // 5. Insertar en la tabla views (se guarda la raw_uri, sin prefijo de zona)
        $sql = "INSERT INTO views (uri, file_path, layout_type, menu_group, menu_title, name_file, show_in_menu, is_active)
                VALUES (:uri, :file_path, :layout_type, :menu_group, :menu_title, :name_file, :show_in_menu, :is_active)";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uri',         $uri,        PDO::PARAM_STR);
        $stmt->bindValue(':file_path',   $filePath,   PDO::PARAM_STR);
        $stmt->bindValue(':layout_type', $layoutType, PDO::PARAM_STR);
        $stmt->bindValue(':menu_group',  $menuGroup,  PDO::PARAM_STR);
        $stmt->bindValue(':menu_title',  $menuTitle,  PDO::PARAM_STR);
        $stmt->bindValue(':name_file',   $nameFile !== '' ? $nameFile : null, $nameFile !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':show_in_menu', $showInMenu, PDO::PARAM_INT);
        $stmt->bindValue(':is_active',   $isActive,   PDO::PARAM_INT);
        $stmt->execute();

        $newViewId = (int)$db->lastInsertId();

        // 6. Registrar permiso en role_view_permissions para super_admin (role_id = 1)
        $stmtRv = $db->prepare(
            'INSERT IGNORE INTO role_view_permissions (role_id, view_id) VALUES (:role_id, :view_id)'
        );
        $stmtRv->bindValue(':role_id', self::SUPER_ADMIN_ROLE_ID, PDO::PARAM_INT);
        $stmtRv->bindValue(':view_id', $newViewId, PDO::PARAM_INT);
        $stmtRv->execute();

        // 7. Invalidar cachés de vistas y permisos RBAC para sincronizar navegación
        \Core\ViewCache::refresh();
        \Core\PermissionCache::refresh();

        // Mostrar la URL pública real en el mensaje de éxito
        $publicUri = \Core\ViewCache::buildPublicUri($uri, $layoutType);
        return [
            'error'   => null,
            'success' => "Vista <strong>{$menuTitle}</strong> registrada exitosamente. URL pública: <strong>{$publicUri}</strong>."
                       . " El caché de vistas ha sido actualizado.",
        ];
    }

    /**
     * Procesa la actualización de una vista existente y regenera el caché de rutas.
     *
     * @param int $viewId Identificador de la vista.
     * @return array ['error' => string|null, 'success' => string|null]
     */
    public static function processEdit(int $viewId): array
    {
        AuthMiddleware::requireRole(self::SUPER_ADMIN_ROLE);

        $menuTitle   = trim($_POST['menu_title'] ?? '');
        $menuGroup   = trim($_POST['menu_group'] ?? 'General');
        $uri         = trim($_POST['uri'] ?? '');
        $nameFile    = trim($_POST['name_file'] ?? '');
        $filePath    = trim($_POST['file_path'] ?? '');
        $layoutType  = in_array($_POST['layout_type'] ?? '', ['public', 'private'], true)
                       ? $_POST['layout_type']
                       : 'private';
        $showInMenu  = isset($_POST['show_in_menu']) ? 1 : 0;
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        $adminPrefix = \Core\ViewCache::PRIVATE_PREFIX;
        if (str_starts_with($uri, $adminPrefix . '/') || $uri === $adminPrefix) {
            $uri = '/' . ltrim(substr($uri, strlen($adminPrefix)), '/');
        }
        if ($uri !== '' && $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        if (empty($menuTitle)) {
            return ['error' => 'El campo "Título en Menú" es obligatorio.', 'success' => null];
        }
        if (empty($uri)) {
            return ['error' => 'La URI no puede estar vacía.', 'success' => null];
        }
        if ($uri !== '/' && !preg_match('#^/[a-z0-9/_-]+$#i', $uri)) {
            return ['error' => 'La URI contiene caracteres inválidos. Usa solo minúsculas, números, guiones y barras.', 'success' => null];
        }
        if (empty($filePath)) {
            return ['error' => 'La ruta física del archivo no puede estar vacía.', 'success' => null];
        }

        $db = Database::getInstance();

        $stmtCheck = $db->prepare('SELECT id, menu_title FROM views WHERE uri = :uri AND id != :id LIMIT 1');
        $stmtCheck->bindValue(':uri', $uri, PDO::PARAM_STR);
        $stmtCheck->bindValue(':id',  $viewId, PDO::PARAM_INT);
        $stmtCheck->execute();

        if ($stmtCheck->fetch()) {
            $publicUri = \Core\ViewCache::buildPublicUri($uri, $layoutType);
            return ['error' => "La URL pública <strong>{$publicUri}</strong> ya existe en otra vista registrada.", 'success' => null];
        }

        $sql = "UPDATE views 
                SET uri = :uri, file_path = :file_path, layout_type = :layout_type,
                    menu_group = :menu_group, menu_title = :menu_title, name_file = :name_file,
                    show_in_menu = :show_in_menu, is_active = :is_active
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uri',         $uri,        PDO::PARAM_STR);
        $stmt->bindValue(':file_path',   $filePath,   PDO::PARAM_STR);
        $stmt->bindValue(':layout_type', $layoutType, PDO::PARAM_STR);
        $stmt->bindValue(':menu_group',  $menuGroup,  PDO::PARAM_STR);
        $stmt->bindValue(':menu_title',  $menuTitle,  PDO::PARAM_STR);
        $stmt->bindValue(':name_file',   $nameFile !== '' ? $nameFile : null, $nameFile !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':show_in_menu', $showInMenu, PDO::PARAM_INT);
        $stmt->bindValue(':is_active',   $isActive,   PDO::PARAM_INT);
        $stmt->bindValue(':id',          $viewId,     PDO::PARAM_INT);
        $stmt->execute();

        ViewCache::refresh();

        $publicUri = \Core\ViewCache::buildPublicUri($uri, $layoutType);
        return [
            'error'   => null,
            'success' => "Vista <strong>{$menuTitle}</strong> actualizada exitosamente. URL pública: <strong>{$publicUri}</strong>. El caché de vistas ha sido regenerado.",
        ];
    }

    /**
     * Normaliza un string al formato slug para URI y nombre de archivo.
     * (Usado internamente, la normalización principal la hace el JS del cliente.)
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $text);
        return preg_replace('/[^a-z0-9_]/', '_', str_replace(' ', '_', $text));
    }
}

