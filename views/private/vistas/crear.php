<?php
/**
 * Vista: Gestión de Vistas - Crear Nueva Vista
 * Grupo:     vistas
 * Archivo:   crear.php
 * Ruta:      views/private/vistas/crear.php
 *
 * Los datos del controlador son inyectados por Router::dispatch() vía $GLOBALS
 * antes del ob_start(), por lo que están disponibles en este scope.
 */

$viewsData  = $GLOBALS['__views_data'] ?? ['error' => null, 'success' => null];
$errorMsg   = $viewsData['error']   ?? null;
$successMsg = $viewsData['success'] ?? null;

// Repoblar campos tras un POST fallido
$post = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
?>

<div class="vistas-container">
    <div class="vistas-page-header">
        <h2 class="vistas-page-title">Crear Nueva Vista</h2>
        <p class="vistas-page-subtitle">Registra y enruta un nuevo componente visual dentro del framework ASRS.</p>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success">
            <?= $successMsg ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger">
            <?= $errorMsg ?>
        </div>
    <?php endif; ?>

    <div class="vistas-form-wrapper">
        <form action="" method="POST" class="vistas-form" id="form-crear-vista">

            <div class="form-grid">

                <!-- URI Amigable -->
                <div class="form-group">
                    <label for="uri">URI Amigable <span class="required">*</span></label>
                    <input type="text" id="uri" name="uri"
                           placeholder="ej. roles/crear"
                           value="<?= htmlspecialchars($post['uri'] ?? '') ?>"
                           required class="form-control">
                    <small class="form-text">Sin prefijo <code>/admin/</code> — el Router lo añade automáticamente si es privada.</small>
                </div>

                <!-- Tipo de Layout -->
                <div class="form-group">
                    <label for="layout_type">Tipo de Layout <span class="required">*</span></label>
                    <select id="layout_type" name="layout_type" class="form-control" required>
                        <option value="private" <?= (($post['layout_type'] ?? 'private') === 'private') ? 'selected' : '' ?>>Privado (Admin)</option>
                        <option value="public"  <?= (($post['layout_type'] ?? '') === 'public')  ? 'selected' : '' ?>>Público</option>
                    </select>
                </div>

                <!-- Grupo de Menú -->
                <div class="form-group">
                    <label for="menu_group">Grupo de Menú <span class="required">*</span></label>
                    <input type="text" id="menu_group" name="menu_group" list="datalist-menu-groups"
                           placeholder="ej. Roles"
                           value="<?= htmlspecialchars($post['menu_group'] ?? '') ?>"
                           required class="form-control">
                    <datalist id="datalist-menu-groups">
                        <?php foreach (\Core\MenuGroupCache::getActiveGroups() as $cachedG): ?>
                            <option value="<?= htmlspecialchars($cachedG['name']) ?>"><?= htmlspecialchars($cachedG['name']) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                    <small class="form-text">Define la subcarpeta física (se normaliza a minúsculas) y la agrupación en el sidebar.</small>
                </div>

                <!-- Identificador Técnico -->
                <div class="form-group">
                    <label for="name_file">Identificador Técnico <code>name_file</code> <span class="required">*</span></label>
                    <input type="text" id="name_file" name="name_file"
                           placeholder="ej. roles_crear"
                           value="<?= htmlspecialchars($post['name_file'] ?? '') ?>"
                           required class="form-control">
                    <small class="form-text">Nombre del archivo sin extensión <code>.php</code>.</small>
                </div>

                <!-- Ruta Física (auto-generada) -->
                <div class="form-group full-width">
                    <label for="file_path">Ruta del Archivo Físico <span class="required">*</span></label>
                    <input type="text" id="file_path" name="file_path"
                           placeholder="views/private/roles/roles_crear.php"
                           value="<?= htmlspecialchars($post['file_path'] ?? '') ?>"
                           required class="form-control"
                           title="Generada automáticamente al completar Grupo e Identificador">
                    <small class="form-text">Generada automáticamente: <code>views/{layout}/{grupo}/{name_file}.php</code></small>
                </div>

                <!-- Título en Menú -->
                <div class="form-group">
                    <label for="menu_title">Título en Menú <span class="required">*</span></label>
                    <input type="text" id="menu_title" name="menu_title"
                           placeholder="ej. Crear Rol"
                           value="<?= htmlspecialchars($post['menu_title'] ?? '') ?>"
                           required class="form-control">
                </div>

                <!-- Opciones -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_in_menu" value="1"
                               <?= (!isset($post['menu_title']) || isset($post['show_in_menu'])) ? 'checked' : '' ?>>
                        <span>Mostrar en el Menú de Navegación</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1"
                               <?= (!isset($post['menu_title']) || isset($post['is_active'])) ? 'checked' : '' ?>>
                        <span>Vista activa (accesible)</span>
                    </label>
                </div>

            </div><!-- /.form-grid -->

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="btn-guardar-vista">Guardar Vista</button>
                <a href="<?= \Core\Router::url('dashboard') ?>" class="btn-secondary">Cancelar</a>
            </div>

        </form>
    </div>
</div>