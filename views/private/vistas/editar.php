<?php
/**
 * Vista: Gestión de Vistas - Panel Tabular con Edición Inline
 * Grupo:     Vistas
 * Archivo:   editar.php
 * Ruta:      views/private/vistas/editar.php
 * Assets:    assets/css/vistas/editar.css
 *            assets/js/vistas/editar.js
 *
 * Desarrollado para el Framework ASRS.
 * Permite listar todas las vistas registradas en el sistema, modificarlas directamente
 * mediante edición inline en la tabla y regenerar el caché de rutas automáticamente.
 */

use Core\Database;
use Core\Router;
use Core\ViewCache;
use Core\controllers\ViewsController;

$errorMsg   = null;
$successMsg = null;
$updatedId  = null;
$db         = null;

// Control de acceso estricto por rol de sesión (solo 'super_admin' gestiona roles de vista)
$isSuperAdmin = ViewsController::isSuperAdmin();
$allRoles     = [];
$viewRolesMap = [];

// Conexión PDO
try {
    $db = Database::getInstance();
} catch (\Throwable $e) {
    $errorMsg = 'Error crítico al conectar con la base de datos: ' . $e->getMessage();
}

// ── 1. Procesamiento Backend de Actualización (Inline / AJAX / Form POST) ─────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    // Detección de petición AJAX
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || isset($_POST['ajax'])
              || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    // Seguridad backend: cualquier intento de modificar roles exige super_admin (HTTP 403)
    $rolesSubmitted = isset($_POST['roles_submitted']) || isset($_POST['role_ids']);
    if ($rolesSubmitted) {
        ViewsController::denyUnlessSuperAdmin($isAjax);
    }

    $viewId     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $menuTitle  = trim($_POST['menu_title'] ?? '');
    $menuGroup  = trim($_POST['menu_group'] ?? 'General');
    $uri        = trim($_POST['uri'] ?? '');
    $nameFile   = trim($_POST['name_file'] ?? '');
    $filePath   = trim($_POST['file_path'] ?? '');
    $layoutType = in_array($_POST['layout_type'] ?? '', ['public', 'private'], true)
                    ? $_POST['layout_type']
                    : 'private';
    $showInMenu = !empty($_POST['show_in_menu']) ? 1 : 0;
    $isActive   = !empty($_POST['is_active']) ? 1 : 0;

    // Normalización de URI según contrato ASRS: sin prefijo /admin/, con barra inicial
    $adminPrefix = ViewCache::PRIVATE_PREFIX;
    if (str_starts_with($uri, $adminPrefix . '/') || $uri === $adminPrefix) {
        $uri = '/' . ltrim(substr($uri, strlen($adminPrefix)), '/');
    }
    if ($uri !== '' && $uri[0] !== '/') {
        $uri = '/' . $uri;
    }

    // Validaciones de Servidor
    $valError = null;
    if (!$viewId) {
        $valError = 'Identificador numérico de la vista no válido.';
    } elseif (empty($menuTitle)) {
        $valError = 'El título de la vista es obligatorio.';
    } elseif (mb_strlen($menuTitle) > 100) {
        $valError = 'El título no puede superar los 100 caracteres.';
    } elseif (empty($uri)) {
        $valError = 'La URI amigable no puede estar vacía.';
    } elseif ($uri !== '/' && !preg_match('#^/[a-z0-9/_-]+$#i', $uri)) {
        $valError = 'La URI contiene caracteres inválidos. Usa solo minúsculas, números, guiones y barras.';
    } elseif (empty($menuGroup)) {
        $valError = 'El grupo de menú es obligatorio.';
    } elseif (empty($nameFile)) {
        $valError = 'El identificador técnico (name_file) es obligatorio.';
    } elseif (empty($filePath)) {
        $valError = 'La ruta física del archivo no puede estar vacía.';
    }

    if ($valError) {
        if ($isAjax) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'error' => $valError]);
            exit;
        }
        $errorMsg = $valError;
    } else {
        try {
            // Verificar unicidad de la URI excluyendo el registro actual
            $checkStmt = $db->prepare('SELECT id, menu_title FROM views WHERE uri = :uri AND id != :id LIMIT 1');
            $checkStmt->bindValue(':uri', $uri, PDO::PARAM_STR);
            $checkStmt->bindValue(':id',  $viewId, PDO::PARAM_INT);
            $checkStmt->execute();
            $duplicate = $checkStmt->fetch();

            if ($duplicate) {
                $dupMsg = "La URI '{$uri}' ya está asignada a la vista #{$duplicate['id']} ({$duplicate['menu_title']}).";
                if ($isAjax) {
                    if (!headers_sent()) {
                        header('Content-Type: application/json; charset=utf-8');
                    }
                    echo json_encode(['success' => false, 'error' => $dupMsg]);
                    exit;
                }
                $errorMsg = $dupMsg;
            } else {
                // Sentencia preparada PDO para UPDATE seguro
                $updateSql = "UPDATE views 
                              SET uri          = :uri,
                                  file_path    = :file_path,
                                  layout_type  = :layout_type,
                                  menu_group   = :menu_group,
                                  menu_title   = :menu_title,
                                  name_file    = :name_file,
                                  show_in_menu = :show_in_menu,
                                  is_active    = :is_active
                              WHERE id = :id";

                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindValue(':uri',          $uri,        PDO::PARAM_STR);
                $updateStmt->bindValue(':file_path',    $filePath,   PDO::PARAM_STR);
                $updateStmt->bindValue(':layout_type',  $layoutType, PDO::PARAM_STR);
                $updateStmt->bindValue(':menu_group',   $menuGroup,  PDO::PARAM_STR);
                $updateStmt->bindValue(':menu_title',   $menuTitle,  PDO::PARAM_STR);
                $updateStmt->bindValue(':name_file',    $nameFile,   PDO::PARAM_STR);
                $updateStmt->bindValue(':show_in_menu', $showInMenu, PDO::PARAM_INT);
                $updateStmt->bindValue(':is_active',    $isActive,   PDO::PARAM_INT);
                $updateStmt->bindValue(':id',           $viewId,     PDO::PARAM_INT);
                $updateStmt->execute();

                // Sincronizar roles en la tabla pivote role_view_permissions (solo super_admin)
                if ($rolesSubmitted) {
                    $rawRoleIds = $_POST['role_ids'] ?? [];
                    ViewsController::syncViewRoles($viewId, is_array($rawRoleIds) ? $rawRoleIds : []);
                }

                // Regenerar automáticamente el archivo de caché de enrutamiento
                ViewCache::refresh();

                $publicUriActive = ViewCache::buildPublicUri($uri, $layoutType);

                if ($isAjax) {
                    if (!headers_sent()) {
                        header('Content-Type: application/json; charset=utf-8');
                    }
                    echo json_encode([
                        'success'    => true,
                        'message'    => "Vista #{$viewId} ({$menuTitle}) actualizada exitosamente.",
                        'public_uri' => $publicUriActive,
                        'data'       => [
                            'id'           => $viewId,
                            'menu_title'   => $menuTitle,
                            'menu_group'   => $menuGroup,
                            'uri'          => $uri,
                            'name_file'    => $nameFile,
                            'file_path'    => $filePath,
                            'layout_type'  => $layoutType,
                            'show_in_menu' => $showInMenu,
                            'is_active'    => $isActive,
                            'roles'        => $rolesSubmitted ? ViewsController::getRoleIdsByView()[$viewId] ?? [] : null,
                        ]
                    ]);
                    exit;
                }

                $updatedId  = $viewId;
                $successMsg = "La vista <strong>" . htmlspecialchars($menuTitle) . "</strong> (#{$viewId}) ha sido actualizada exitosamente.<br>"
                            . "URL pública actual: <code>" . htmlspecialchars($publicUriActive) . "</code>. "
                            . "El caché de enrutamiento (<code>storage/views_cache.php</code>) ha sido regenerado.";
            }
        } catch (\Throwable $e) {
            $dbErr = 'Error al actualizar en la base de datos: ' . $e->getMessage();
            if ($isAjax) {
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode(['success' => false, 'error' => $dbErr]);
                exit;
            }
            $errorMsg = $dbErr;
        }
    }
}

// ── 2. Consulta y Agrupación de TODAS las vistas registradas (PDO) ───────────
$viewsList    = [];
$groupedViews = [];
if ($db) {
    try {
        $stmtAll = $db->query(
            "SELECT id, uri, file_path, layout_type, menu_group, menu_title, name_file, show_in_menu, is_active, created_at 
             FROM views 
             ORDER BY CAST(layout_type AS CHAR) ASC, menu_group ASC, id ASC"
        );
        $viewsList = $stmtAll->fetchAll();

        // Agrupar vistas lógicamente por layout_type y menu_group
        foreach ($viewsList as $view) {
            $layout = $view['layout_type'] ?? 'private';
            $group  = !empty($view['menu_group']) ? trim($view['menu_group']) : 'General';
            $groupedViews[$layout][$group][] = $view;
        }

        // Asegurar ordenamiento ascendente estricto tanto por layout como por grupo
        ksort($groupedViews);
        foreach ($groupedViews as &$groups) {
            ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($groups);

        if ($isSuperAdmin) {
            $allRoles     = ViewsController::getAllRoles();
            $viewRolesMap = ViewsController::getRoleIdsByView();
        }
    } catch (\Throwable $e) {
        $errorMsg = 'Error al obtener la lista de vistas: ' . $e->getMessage();
    }
}
$tableCols = $isSuperAdmin ? 10 : 9;

$dashboardUrl   = Router::url('dashboard');
$createViewUrl  = Router::url('vistas/crear');
$currentEditUrl = Router::url('vistas/editar');
$totalViews     = count($viewsList);
?>

<div class="vistas-edit-container">

    <!-- Encabezado de la Página y Migas de Pan -->
    <div class="vistas-header-card">
        <div class="vistas-header-content">
            <nav class="vistas-breadcrumb" aria-label="Migas de pan">
                <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="vistas-breadcrumb__link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Dashboard
                </a>
                <span class="vistas-breadcrumb__separator">/</span>
                <span class="vistas-breadcrumb__current">Gestión de Vistas</span>
                <span class="vistas-breadcrumb__separator">/</span>
                <span class="vistas-breadcrumb__current">Listado y Edición Inline</span>
            </nav>

            <div class="vistas-header-title-row">
                <h1 class="vistas-title">
                    Administrador de Vistas
                    <span class="vistas-badge-count" id="vistas-total-badge"><?= $totalViews ?> vista<?= $totalViews !== 1 ? 's' : '' ?></span>
                </h1>
                <p class="vistas-subtitle">
                    Consulta el registro completo de vistas y edita directamente sus metadatos, enrutamiento y estado desde la tabla.
                </p>
            </div>
        </div>

        <div class="vistas-header-actions">
            <a href="<?= htmlspecialchars($createViewUrl) ?>" class="btn-primary" id="btn-nueva-vista">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nueva Vista</span>
            </a>
            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Volver</span>
            </a>
        </div>
    </div>

    <!-- Contenedor dinámico de alertas de feedback del cliente JS -->
    <div id="vistas-dynamic-alert-box"></div>

    <!-- Feedback Servidor: Alerta de Éxito -->
    <?php if (!empty($successMsg)): ?>
        <div class="vistas-alert vistas-alert--success" role="alert" id="server-alert-success">
            <div class="vistas-alert__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="vistas-alert__content">
                <strong>¡Actualización exitosa!</strong>
                <div><?= $successMsg ?></div>
            </div>
            <button type="button" class="vistas-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Feedback Servidor: Alerta de Error -->
    <?php if (!empty($errorMsg)): ?>
        <div class="vistas-alert vistas-alert--danger" role="alert" id="server-alert-error">
            <div class="vistas-alert__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="vistas-alert__content">
                <strong>Atención:</strong>
                <div><?= $errorMsg ?></div>
            </div>
            <button type="button" class="vistas-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Barra de Herramientas: Búsqueda y Filtros Rápidos -->
    <div class="vistas-toolbar-card">
        <div class="vistas-search-wrapper">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="vistas-search-input" class="vistas-search-input"
                   placeholder="Filtrar por título, grupo, URI o archivo..."
                   aria-label="Buscar en la tabla de vistas">
            <button type="button" id="vistas-search-clear" class="vistas-search-clear" title="Limpiar búsqueda" style="display: none;">&times;</button>
        </div>

        <div class="vistas-filters-wrapper">
            <div class="filter-group">
                <label for="filter-layout" class="filter-label">Layout:</label>
                <select id="filter-layout" class="filter-select">
                    <option value="all">Todos</option>
                    <option value="private">Privado (Admin)</option>
                    <option value="public">Público</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter-status" class="filter-label">Estado:</label>
                <select id="filter-status" class="filter-select">
                    <option value="all">Todos</option>
                    <option value="active">Activas</option>
                    <option value="inactive">Inactivas</option>
                </select>
            </div>
        </div>
    </div>

    <?php if (empty($viewsList)): ?>
        <!-- Estado Vacío -->
        <div class="vistas-empty-state">
            <div class="empty-state-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <h2 class="empty-state-title">No hay vistas registradas</h2>
            <p class="empty-state-text">Actualmente no existen registros en la tabla de vistas del sistema ASRS.</p>
            <div class="empty-state-actions">
                <a href="<?= htmlspecialchars($createViewUrl) ?>" class="btn-primary">Registrar Primera Vista</a>
            </div>
        </div>

    <?php else: ?>
        <!-- Tabla Estructurada con Soporte para Edición Inline -->
        <div class="vistas-table-wrapper">
            <table class="vistas-table" id="vistas-table" aria-label="Tabla de gestión y edición de vistas">
                <thead>
                    <tr>
                        <th scope="col" class="th-id"># ID</th>
                        <th scope="col" class="th-title">Título en Menú</th>
                        <th scope="col" class="th-group">Grupo</th>
                        <th scope="col" class="th-layout">Layout</th>
                        <th scope="col" class="th-uri">URI Amigable</th>
                        <th scope="col" class="th-file">Identificador / Archivo</th>
                        <th scope="col" class="th-menu text-center">En Menú</th>
                        <th scope="col" class="th-status text-center">Estado</th>
                        <?php if ($isSuperAdmin): ?>
                        <th scope="col" class="th-roles">Roles con Acceso</th>
                        <?php endif; ?>
                        <th scope="col" class="th-actions text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupedViews as $layoutKey => $groups): 
                        $isPrivate = ($layoutKey === 'private');
                        $layoutTitle = $isPrivate ? 'Zona Privada (Panel Administrativo)' : 'Zona Pública (Portal General)';
                        $layoutSub = $isPrivate ? 'Rutas bajo el prefijo /admin con validación de sesión y permisos RBAC' : 'Rutas de acceso público libre sin autenticación';
                        $layoutBadgeClass = $isPrivate ? 'layout-badge--private' : 'layout-badge--public';
                    ?>
                        <!-- Cabecera de Zona / Layout -->
                        <tr class="vistas-layout-header-row" data-layout="<?= htmlspecialchars($layoutKey) ?>">
                            <td colspan="<?= $tableCols ?>">
                                <div class="layout-header-banner <?= $isPrivate ? 'banner-private' : 'banner-public' ?>">
                                    <div class="layout-header-left">
                                        <span class="layout-header-icon">
                                            <?php if ($isPrivate): ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                            <?php else: ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <span class="layout-header-title"><?= htmlspecialchars($layoutTitle) ?></span>
                                        <span class="layout-header-badge <?= $layoutBadgeClass ?>"><?= $isPrivate ? '/admin/*' : '/*' ?></span>
                                    </div>
                                    <div class="layout-header-right">
                                        <span class="layout-header-subtext"><?= htmlspecialchars($layoutSub) ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <?php foreach ($groups as $groupName => $viewsInGroup): 
                            $groupKey = $layoutKey . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($groupName));
                            $viewsCountInGroup = count($viewsInGroup);
                        ?>
                            <!-- Cabecera de Separación de Módulo / Grupo -->
                            <tr class="vistas-group-header-row" data-group-key="<?= htmlspecialchars($groupKey) ?>" data-layout="<?= htmlspecialchars($layoutKey) ?>">
                                <td colspan="<?= $tableCols ?>">
                                    <div class="group-header-bar">
                                        <div class="group-header-left">
                                            <span class="group-folder-icon">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                                </svg>
                                            </span>
                                            <span class="group-header-name">Módulo / Grupo: <strong><?= htmlspecialchars($groupName) ?></strong></span>
                                        </div>
                                        <div class="group-header-right">
                                            <span class="group-count-pill"><?= $viewsCountInGroup ?> vista<?= $viewsCountInGroup !== 1 ? 's' : '' ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <?php foreach ($viewsInGroup as $view): 
                                $vId         = (int)$view['id'];
                                $vTitle      = $view['menu_title'];
                                $vGroup      = $view['menu_group'] ?? 'General';
                                $vLayout     = $view['layout_type'] ?? 'private';
                                $vUri        = $view['uri'];
                                $vNameFile   = $view['name_file'] ?? '';
                                $vFilePath   = $view['file_path'];
                                $vShowInMenu = !empty($view['show_in_menu']);
                                $vIsActive   = !empty($view['is_active']);
                                $vPublicUri  = ViewCache::buildPublicUri($vUri, $vLayout);
                                $isRowUpdated = ($updatedId === $vId);
                            ?>
                                <tr class="vistas-row <?= $isRowUpdated ? 'row-just-saved' : '' ?>" 
                                    id="view-row-<?= $vId ?>"
                                    data-id="<?= $vId ?>"
                                    data-group-key="<?= htmlspecialchars($groupKey) ?>"
                                    data-layout="<?= htmlspecialchars($vLayout) ?>"
                                    data-status="<?= $vIsActive ? 'active' : 'inactive' ?>">

                                    <!-- Columna 1: ID -->
                                    <td class="td-id">
                                        <span class="vistas-chip-id">#<?= $vId ?></span>
                                    </td>

                                    <!-- Columna 2: Título en Menú -->
                                    <td class="td-title">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-title-view">
                                            <strong class="title-text"><?= htmlspecialchars($vTitle) ?></strong>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-title-edit" style="display: none;">
                                            <input type="text" name="menu_title" class="inline-input input-title"
                                                   value="<?= htmlspecialchars($vTitle) ?>"
                                                   required maxlength="100" placeholder="Título en Menú"
                                                   form="form-row-<?= $vId ?>">
                                        </div>
                                    </td>

                                    <!-- Columna 3: Grupo -->
                                    <td class="td-group">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-group-view">
                                            <span class="group-badge"><?= htmlspecialchars($vGroup) ?></span>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-group-edit" style="display: none;">
                                            <input type="text" name="menu_group" class="inline-input input-group"
                                                   value="<?= htmlspecialchars($vGroup) ?>"
                                                   required maxlength="100" placeholder="Grupo"
                                                   form="form-row-<?= $vId ?>">
                                        </div>
                                    </td>

                                    <!-- Columna 4: Layout / Zona -->
                                    <td class="td-layout">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-layout-view">
                                            <span class="layout-badge <?= $vLayout === 'private' ? 'layout-badge--private' : 'layout-badge--public' ?>">
                                                <?= $vLayout === 'private' ? 'Privado (Admin)' : 'Público' ?>
                                            </span>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-layout-edit" style="display: none;">
                                            <select name="layout_type" class="inline-select select-layout" form="form-row-<?= $vId ?>">
                                                <option value="private" <?= $vLayout === 'private' ? 'selected' : '' ?>>Privado</option>
                                                <option value="public"  <?= $vLayout === 'public'  ? 'selected' : '' ?>>Público</option>
                                            </select>
                                        </div>
                                    </td>

                                    <!-- Columna 5: URI Amigable -->
                                    <td class="td-uri">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-uri-view">
                                            <code class="uri-code" title="URL Pública: <?= htmlspecialchars($vPublicUri) ?>"><?= htmlspecialchars($vUri) ?></code>
                                            <span class="public-preview-hint"><?= htmlspecialchars($vPublicUri) ?></span>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-uri-edit" style="display: none;">
                                            <input type="text" name="uri" class="inline-input input-uri"
                                                   value="<?= htmlspecialchars($vUri) ?>"
                                                   required maxlength="255" placeholder="/ruta"
                                                   form="form-row-<?= $vId ?>">
                                        </div>
                                    </td>

                                    <!-- Columna 6: Identificador Técnico y Ruta Física -->
                                    <td class="td-file">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-file-view">
                                            <span class="namefile-text font-mono"><?= htmlspecialchars($vNameFile ?: '—') ?></span>
                                            <span class="filepath-subtext" title="<?= htmlspecialchars($vFilePath) ?>"><?= htmlspecialchars($vFilePath) ?></span>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-file-edit" style="display: none;">
                                            <input type="text" name="name_file" class="inline-input input-namefile"
                                                   value="<?= htmlspecialchars($vNameFile) ?>"
                                                   required maxlength="100" placeholder="name_file"
                                                   title="Identificador técnico sin .php"
                                                   form="form-row-<?= $vId ?>">
                                            <input type="text" name="file_path" class="inline-input input-filepath font-mono"
                                                   value="<?= htmlspecialchars($vFilePath) ?>"
                                                   required maxlength="255" placeholder="views/..."
                                                   title="Ruta física del archivo PHP"
                                                   form="form-row-<?= $vId ?>">
                                        </div>
                                    </td>

                                    <!-- Columna 7: En Menú -->
                                    <td class="td-menu text-center">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-menu-view">
                                            <span class="status-indicator <?= $vShowInMenu ? 'is-yes' : 'is-no' ?>">
                                                <?= $vShowInMenu ? 'Sí' : 'No' ?>
                                            </span>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-menu-edit" style="display: none;">
                                            <label class="table-switch" title="Mostrar en menú de navegación">
                                                <input type="checkbox" name="show_in_menu" class="input-showinmenu" value="1"
                                                       <?= $vShowInMenu ? 'checked' : '' ?>
                                                       form="form-row-<?= $vId ?>">
                                                <span class="table-slider"></span>
                                            </label>
                                        </div>
                                    </td>

                                    <!-- Columna 8: Estado (Activa/Inactiva) -->
                                    <td class="td-status text-center">
                                        <!-- Modo Lectura -->
                                        <div class="cell-view cell-status-view">
                                            <span class="status-pill <?= $vIsActive ? 'status-pill--active' : 'status-pill--inactive' ?>">
                                                <?= $vIsActive ? 'Activa' : 'Inactiva' ?>
                                            </span>
                                        </div>
                                        <!-- Modo Edición Inline -->
                                        <div class="cell-edit cell-status-edit" style="display: none;">
                                            <label class="table-switch" title="Habilitar o deshabilitar ruta">
                                                <input type="checkbox" name="is_active" class="input-isactive" value="1"
                                                       <?= $vIsActive ? 'checked' : '' ?>
                                                       form="form-row-<?= $vId ?>">
                                                <span class="table-slider"></span>
                                            </label>
                                        </div>
                                    </td>

                                    <?php if ($isSuperAdmin):
                                        $assignedRoleIds = $viewRolesMap[$vId] ?? [];
                                    ?>
                                    <!-- Columna Roles: visible y editable únicamente para super_admin -->
                                    <td class="td-roles">
                                        <div class="cell-view cell-roles-view">
                                            <?php $hasRole = false; foreach ($allRoles as $role):
                                                if (!in_array((int)$role['id'], $assignedRoleIds, true)) continue; $hasRole = true; ?>
                                                <span class="group-badge" data-role-id="<?= (int)$role['id'] ?>"><?= htmlspecialchars($role['name']) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (!$hasRole): ?><span class="filepath-subtext">Sin roles</span><?php endif; ?>
                                        </div>
                                        <div class="cell-edit cell-roles-edit" style="display: none;">
                                            <input type="hidden" name="roles_submitted" value="1" form="form-row-<?= $vId ?>">
                                            <?php foreach ($allRoles as $role):
                                                $isSA = ((int)$role['id'] === 1); ?>
                                                <label class="role-check" style="display:block;font-size:12px;white-space:nowrap;">
                                                    <input type="checkbox" name="role_ids[]" class="input-role"
                                                           value="<?= (int)$role['id'] ?>"
                                                           <?= in_array((int)$role['id'], $assignedRoleIds, true) || $isSA ? 'checked' : '' ?>
                                                           <?= $isSA ? 'disabled title="super_admin siempre conserva acceso"' : '' ?>
                                                           form="form-row-<?= $vId ?>">
                                                    <?= htmlspecialchars($role['name']) ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>

                                    <!-- Columna Acciones Inline -->
                                    <td class="td-actions text-center">
                                        <!-- Formulario independiente asociado a la fila para soporte nativo sin JS -->
                                        <form id="form-row-<?= $vId ?>" action="<?= htmlspecialchars($currentEditUrl) ?>" method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $vId ?>">
                                        </form>

                                        <!-- Botones en Modo Lectura -->
                                        <div class="actions-view">
                                            <button type="button" class="btn-row-action btn-row-edit"
                                                    title="Editar fila directamente"
                                                    data-id="<?= $vId ?>">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                <span>Editar</span>
                                            </button>
                                        </div>

                                        <!-- Botones en Modo Edición Inline -->
                                        <div class="actions-edit" style="display: none;">
                                            <button type="submit" class="btn-row-action btn-row-save"
                                                    title="Guardar cambios de esta vista"
                                                    form="form-row-<?= $vId ?>"
                                                    data-id="<?= $vId ?>">
                                                <svg class="save-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                                    <polyline points="7 3 7 8 15 8"></polyline>
                                                </svg>
                                                <span class="btn-text">Guardar</span>
                                            </button>

                                            <button type="button" class="btn-row-action btn-row-cancel"
                                                    title="Cancelar edición"
                                                    data-id="<?= $vId ?>">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pie de Tabla: Resumen y Estado -->
        <div class="vistas-table-footer">
            <div class="footer-info">
                Mostrando <strong id="vistas-visible-count"><?= $totalViews ?></strong> de <strong><?= $totalViews ?></strong> vistas registradas.
            </div>
            <div class="footer-legend">
                <span class="legend-item"><span class="legend-dot dot-active"></span> Activa</span>
                <span class="legend-item"><span class="legend-dot dot-inactive"></span> Inactiva</span>
                <span class="legend-item"><span class="legend-dot dot-private"></span> Privada (/admin)</span>
                <span class="legend-item"><span class="legend-dot dot-public"></span> Pública</span>
            </div>
        </div>

    <?php endif; ?>

</div><!-- /.vistas-edit-container -->
