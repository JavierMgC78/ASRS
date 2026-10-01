<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use Core\MenuGroupCache;
use Core\ViewCache;
use PDO;
use Throwable;

class MenuGroupsController
{
    private const REQUIRED_ROLE = 'super_admin';

    /**
     * Punto de entrada principal para peticiones del módulo de Grupos de Menú.
     *
     * @return array{error: string|null, success: string|null, groups: array, stats: array}
     */
    public static function handle(): array
    {
        // 1. Verificar autorización RBAC: solo super_admin puede gestionar grupos
        AuthMiddleware::requireRole(self::REQUIRED_ROLE);

        $result = [
            'error'   => null,
            'success' => null,
        ];

        // 2. Procesar peticiones POST (Crear, Editar, Alternar Estado, Eliminar)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || isset($_POST['ajax'])
                || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            $action = trim($_POST['action'] ?? 'create');

            $actionResult = match ($action) {
                'create' => self::processCreate(),
                'update', 'edit' => self::processUpdate(),
                'toggle' => self::processToggleActive(),
                'delete' => self::processDelete(),
                default  => ['error' => 'Acción no reconocida en el controlador.', 'success' => null],
            };

            // Respuesta para peticiones asíncronas AJAX
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => empty($actionResult['error']),
                    'message' => $actionResult['error'] ?? $actionResult['success'],
                    'data'    => $actionResult,
                ]);
                exit;
            }

            $result['error']   = $actionResult['error'] ?? null;
            $result['success'] = $actionResult['success'] ?? null;
        }

        // 3. Obtener listado enriquecido con vistas asociadas
        $groupsData = self::getAllGroupsWithStats();

        return array_merge($result, $groupsData);
    }

    /**
     * Obtiene todos los grupos de la base de datos junto con el conteo de vistas asociadas.
     *
     * @return array{groups: array, stats: array}
     */
    public static function getAllGroupsWithStats(): array
    {
        try {
            $db = Database::getInstance();

            // Consulta agregada para conocer cuántas vistas tiene cada grupo de menú
            $sql = "SELECT g.id, g.name, g.slug, g.display_order, g.icon, g.description, 
                           g.is_active, g.created_at, g.updated_at,
                           COUNT(v.id) AS views_count
                    FROM menu_groups g
                    LEFT JOIN views v ON v.menu_group = g.name
                    GROUP BY g.id
                    ORDER BY g.display_order ASC, g.name ASC";

            $stmt   = $db->query($sql);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = [
                'total'     => count($groups),
                'active'    => 0,
                'inactive'  => 0,
                'max_order' => 0,
            ];

            foreach ($groups as $g) {
                if ((int)$g['is_active'] === 1) {
                    $stats['active']++;
                } else {
                    $stats['inactive']++;
                }
                if ((int)$g['display_order'] > $stats['max_order']) {
                    $stats['max_order'] = (int)$g['display_order'];
                }
            }

            return ['groups' => $groups, 'stats' => $stats];

        } catch (Throwable $e) {
            return [
                'groups' => [],
                'stats'  => ['total' => 0, 'active' => 0, 'inactive' => 0, 'max_order' => 0],
                'error'  => 'Error al consultar la base de datos: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Procesa el alta de un nuevo grupo de menú y regenera el archivo en caché.
     *
     * @return array{error: string|null, success: string|null, id: int|null}
     */
    public static function processCreate(): array
    {
        $name         = trim($_POST['name'] ?? '');
        $rawSlug      = trim($_POST['slug'] ?? '');
        $displayOrder = filter_var($_POST['display_order'] ?? null, FILTER_VALIDATE_INT);
        $description  = trim($_POST['description'] ?? '');
        $isActive     = !empty($_POST['is_active']) ? 1 : 0;

        // Auto-generar slug si viene vacío
        $slug = $rawSlug !== '' ? self::slugify($rawSlug) : self::slugify($name);

        // Validaciones de negocio
        if ($name === '') {
            return ['error' => 'El nombre del grupo es obligatorio.', 'success' => null, 'id' => null];
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return ['error' => 'El nombre debe tener entre 2 y 100 caracteres.', 'success' => null, 'id' => null];
        }
        if ($slug === '') {
            return ['error' => 'El slug no es válido. Debe contener al menos caracteres alfanuméricos.', 'success' => null, 'id' => null];
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            return ['error' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.', 'success' => null, 'id' => null];
        }
        if ($displayOrder === false || $displayOrder < 0) {
            $displayOrder = MenuGroupCache::getNextDisplayOrder();
        }

        try {
            $db = Database::getInstance();

            // Verificar unicidad de nombre y slug
            $stmtCheck = $db->prepare('SELECT id, name, slug FROM menu_groups WHERE name = :name OR slug = :slug LIMIT 1');
            $stmtCheck->execute([':name' => $name, ':slug' => $slug]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if (strcasecmp($existing['name'], $name) === 0) {
                    return ['error' => "Ya existe un grupo con el nombre '{$name}'.", 'success' => null, 'id' => null];
                }
                return ['error' => "Ya existe un grupo con el slug '{$slug}'.", 'success' => null, 'id' => null];
            }

            // Inserción preparada
            $sql = "INSERT INTO menu_groups (name, slug, display_order, description, is_active, created_at, updated_at)
                    VALUES (:name, :slug, :display_order, :description, :is_active, NOW(), NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':name',          $name,         PDO::PARAM_STR);
            $stmt->bindValue(':slug',          $slug,         PDO::PARAM_STR);
            $stmt->bindValue(':display_order',  $displayOrder, PDO::PARAM_INT);
            $stmt->bindValue(':description',    $description !== '' ? $description : null, $description !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':is_active',      $isActive,     PDO::PARAM_INT);
            $stmt->execute();

            $newId = (int)$db->lastInsertId();

            // REQUERIMIENTO 4: Regenerar automáticamente la caché de grupos
            MenuGroupCache::refresh();

            return [
                'error'   => null,
                'success' => "Grupo '<strong>{$name}</strong>' creado exitosamente (ID #{$newId}). El caché estático ha sido sincronizado.",
                'id'      => $newId,
            ];

        } catch (Throwable $e) {
            return ['error' => 'Error al registrar el grupo en la base de datos: ' . $e->getMessage(), 'success' => null, 'id' => null];
        }
    }

    /**
     * Procesa la modificación de un grupo existente y sincroniza la caché.
     *
     * @return array{error: string|null, success: string|null}
     */
    public static function processUpdate(): array
    {
        $id           = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $name         = trim($_POST['name'] ?? '');
        $rawSlug      = trim($_POST['slug'] ?? '');
        $displayOrder = filter_var($_POST['display_order'] ?? null, FILTER_VALIDATE_INT);
        $description  = trim($_POST['description'] ?? '');
        $isActive     = isset($_POST['is_active']) ? ((int)$_POST['is_active'] === 1 ? 1 : 0) : 1;

        if (!$id) {
            return ['error' => 'ID de grupo inválido o no especificado.', 'success' => null];
        }
        if ($name === '') {
            return ['error' => 'El nombre del grupo no puede estar vacío.', 'success' => null];
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return ['error' => 'El nombre debe tener entre 2 y 100 caracteres.', 'success' => null];
        }

        $slug = $rawSlug !== '' ? self::slugify($rawSlug) : self::slugify($name);
        if ($slug === '' || !preg_match('/^[a-z0-9_-]+$/', $slug)) {
            return ['error' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.', 'success' => null];
        }
        if ($displayOrder === false || $displayOrder < 0) {
            $displayOrder = 0;
        }

        try {
            $db = Database::getInstance();

            // Obtener el registro actual
            $stmtCurrent = $db->prepare('SELECT id, name, slug FROM menu_groups WHERE id = :id LIMIT 1');
            $stmtCurrent->execute([':id' => $id]);
            $currentGroup = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

            if (!$currentGroup) {
                return ['error' => 'El grupo solicitado no existe en el sistema.', 'success' => null];
            }

            // Verificar si el nuevo nombre o slug ya pertenecen a OTRO grupo
            $stmtCol = $db->prepare('SELECT id, name, slug FROM menu_groups WHERE (name = :name OR slug = :slug) AND id != :id LIMIT 1');
            $stmtCol->execute([':name' => $name, ':slug' => $slug, ':id' => $id]);
            $collision = $stmtCol->fetch(PDO::FETCH_ASSOC);

            if ($collision) {
                if (strcasecmp($collision['name'], $name) === 0) {
                    return ['error' => "El nombre '{$name}' ya está en uso por otro grupo.", 'success' => null];
                }
                return ['error' => "El slug '{$slug}' ya está en uso por otro grupo.", 'success' => null];
            }

            // Actualizar tabla menu_groups
            $stmtUpdate = $db->prepare(
                "UPDATE menu_groups 
                 SET name = :name, 
                     slug = :slug, 
                     display_order = :display_order, 
                     description = :description, 
                     is_active = :is_active, 
                     updated_at = NOW() 
                 WHERE id = :id"
            );

            $stmtUpdate->bindValue(':name',          $name,         PDO::PARAM_STR);
            $stmtUpdate->bindValue(':slug',          $slug,         PDO::PARAM_STR);
            $stmtUpdate->bindValue(':display_order',  $displayOrder, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':description',    $description !== '' ? $description : null, $description !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':is_active',      $isActive,     PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id',             $id,           PDO::PARAM_INT);
            $stmtUpdate->execute();

            // Si cambió el nombre del grupo, actualizar las vistas asociadas para mantener consistencia
            $oldName = $currentGroup['name'];
            if ($oldName !== $name) {
                $stmtViews = $db->prepare('UPDATE views SET menu_group = :new_name WHERE menu_group = :old_name');
                $stmtViews->execute([':new_name' => $name, ':old_name' => $oldName]);
                ViewCache::refresh();
            }

            // REQUERIMIENTO 4: Regenerar automáticamente la caché de grupos
            MenuGroupCache::refresh();

            return [
                'error'   => null,
                'success' => "Grupo '<strong>{$name}</strong>' (ID #{$id}) actualizado correctamente. Caché sincronizada.",
            ];

        } catch (Throwable $e) {
            return ['error' => 'Error al actualizar el grupo: ' . $e->getMessage(), 'success' => null];
        }
    }

    /**
     * Alterna rápidamente el estado activo/inactivo de un grupo.
     *
     * @return array{error: string|null, success: string|null}
     */
    public static function processToggleActive(): array
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            return ['error' => 'ID de grupo inválido.', 'success' => null];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, name, is_active FROM menu_groups WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group) {
                return ['error' => 'Grupo no encontrado.', 'success' => null];
            }

            $newState = (int)$group['is_active'] === 1 ? 0 : 1;
            $stmtUpdate = $db->prepare('UPDATE menu_groups SET is_active = :state, updated_at = NOW() WHERE id = :id');
            $stmtUpdate->execute([':state' => $newState, ':id' => $id]);

            // Regenerar caché
            MenuGroupCache::refresh();

            $statusText = $newState === 1 ? 'activado' : 'desactivado';
            return [
                'error'   => null,
                'success' => "El grupo '<strong>{$group['name']}</strong>' ha sido {$statusText} correctamente.",
            ];

        } catch (Throwable $e) {
            return ['error' => 'Error al alternar estado: ' . $e->getMessage(), 'success' => null];
        }
    }

    /**
     * Elimina un grupo si no tiene vistas asociadas.
     *
     * @return array{error: string|null, success: string|null}
     */
    public static function processDelete(): array
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            return ['error' => 'ID de grupo inválido.', 'success' => null];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, name FROM menu_groups WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group) {
                return ['error' => 'Grupo no encontrado.', 'success' => null];
            }

            // Verificar si tiene vistas asociadas
            $stmtViews = $db->prepare('SELECT COUNT(*) FROM views WHERE menu_group = :name');
            $stmtViews->execute([':name' => $group['name']]);
            $viewsCount = (int)$stmtViews->fetchColumn();

            if ($viewsCount > 0) {
                return [
                    'error'   => "No se puede eliminar el grupo '{$group['name']}' porque tiene {$viewsCount} vista(s) vinculada(s). Reasigna o elimina las vistas primero.",
                    'success' => null,
                ];
            }

            // Proceder a eliminar
            $stmtDel = $db->prepare('DELETE FROM menu_groups WHERE id = :id');
            $stmtDel->execute([':id' => $id]);

            // Regenerar caché
            MenuGroupCache::refresh();

            return [
                'error'   => null,
                'success' => "El grupo '<strong>{$group['name']}</strong>' ha sido eliminado exitosamente. Caché actualizada.",
            ];

        } catch (Throwable $e) {
            return ['error' => 'Error al eliminar el grupo: ' . $e->getMessage(), 'success' => null];
        }
    }

    /**
     * Convierte una cadena a un slug limpio y amigable para URLs.
     */
    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Reemplazo de caracteres con acentos
        $trans = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
        $text = strtr($text, $trans);
        // Quitar caracteres no alfanuméricos
        $text = preg_replace('/[^a-z0-9_-]+/', '-', $text);
        return trim($text, '-_');
    }
}
