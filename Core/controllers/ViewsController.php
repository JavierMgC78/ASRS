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
     * @return array ['error' => string|null, 'success' => string|null]
     */
    public static function processCreate(): array
    {
        // 1. Sanitizar campos del formulario
        $menuTitle   = trim($_POST['menu_title'] ?? '');
        $menuGroup   = trim($_POST['menu_group'] ?? 'General');
        $uri         = trim($_POST['uri'] ?? '');
        $filePath    = trim($_POST['file_path'] ?? '');
        $layoutType  = in_array($_POST['layout_type'] ?? '', ['public', 'private'])
                       ? $_POST['layout_type']
                       : 'private';
        $showInMenu  = isset($_POST['show_in_menu']) ? 1 : 0;
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        // 2. Validaciones básicas
        if (empty($menuTitle)) {
            return ['error' => 'El campo "Título del Menú" es obligatorio.', 'success' => null];
        }
        if (empty($uri)) {
            return ['error' => 'La URI generada no puede estar vacía.', 'success' => null];
        }
        if (!preg_match('#^/[a-z0-9/_-]+$#', $uri)) {
            return ['error' => 'La URI contiene caracteres inválidos. Usa solo minúsculas, números, guiones y barras.', 'success' => null];
        }
        if (empty($filePath)) {
            return ['error' => 'La ruta física del archivo no puede estar vacía.', 'success' => null];
        }

        $db = Database::getInstance();

        // 3. Verificar que la URI no esté duplicada
        $stmtCheck = $db->prepare('SELECT id FROM views WHERE uri = :uri LIMIT 1');
        $stmtCheck->bindValue(':uri', $uri, PDO::PARAM_STR);
        $stmtCheck->execute();

        if ($stmtCheck->fetch()) {
            return ['error' => "La URI <strong>{$uri}</strong> ya existe en el sistema. Elige un título diferente.", 'success' => null];
        }

        // 4. Insertar en la tabla views
        $sql = "INSERT INTO views (uri, file_path, layout_type, menu_group, menu_title, show_in_menu, is_active)
                VALUES (:uri, :file_path, :layout_type, :menu_group, :menu_title, :show_in_menu, :is_active)";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uri',         $uri,        PDO::PARAM_STR);
        $stmt->bindValue(':file_path',   $filePath,   PDO::PARAM_STR);
        $stmt->bindValue(':layout_type', $layoutType, PDO::PARAM_STR);
        $stmt->bindValue(':menu_group',  $menuGroup,  PDO::PARAM_STR);
        $stmt->bindValue(':menu_title',  $menuTitle,  PDO::PARAM_STR);
        $stmt->bindValue(':show_in_menu', $showInMenu, PDO::PARAM_INT);
        $stmt->bindValue(':is_active',   $isActive,   PDO::PARAM_INT);
        $stmt->execute();

        $newViewId = (int)$db->lastInsertId();

        // 5. Registrar permiso en role_views para super_admin (role_id = 1)
        $stmtRv = $db->prepare(
            'INSERT IGNORE INTO role_views (role_id, view_id) VALUES (:role_id, :view_id)'
        );
        $stmtRv->bindValue(':role_id', self::SUPER_ADMIN_ROLE_ID, PDO::PARAM_INT);
        $stmtRv->bindValue(':view_id', $newViewId, PDO::PARAM_INT);
        $stmtRv->execute();

        // 6. Invalidar caché de vistas para que el Router detecte la nueva ruta
        ViewCache::refresh();

        return [
            'error'   => null,
            'success' => "Vista <strong>{$menuTitle}</strong> registrada exitosamente con la URI <strong>{$uri}</strong>."
                       . " El caché de vistas ha sido actualizado.",
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
