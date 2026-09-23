<?php
// Los datos del controlador son inyectados por Router::dispatch() vía $GLOBALS antes del ob_start()
$viewsData    = $GLOBALS['__views_data'] ?? ['error' => null, 'success' => null];
$errorMsg     = $viewsData['error']   ?? null;
$successMsg   = $viewsData['success'] ?? null;

// Grupos de menú disponibles en el sistema
$menuGroups = ['vistas', 'usuarios', 'sistema', 'reportes', 'configuracion', 'General'];
?>

<div class="crear-vista-container">

    <div class="section-header">
        <h2>Crear Nueva Vista</h2>
        <p>Registra una nueva ruta y vista en el sistema ASRS. Solo accesible para <strong>super_admin</strong>.</p>
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

    <div class="form-card">
        <form id="crearVistaForm" action="/admin/vistas/crear" method="POST" autocomplete="off">

            <!-- 1. TÍTULO DEL MENÚ -->
            <div class="form-group">
                <label for="menu_title">Título del Menú <span class="required">*</span></label>
                <input
                    type="text"
                    id="menu_title"
                    name="menu_title"
                    class="form-control"
                    placeholder="ej. Crear Usuario"
                    value="<?= htmlspecialchars($_POST['menu_title'] ?? 'Crear') ?>"
                    oninput="updatePreview()"
                    required
                >
                <small class="form-hint">Nombre que aparecerá en el menú de navegación.</small>
            </div>

            <!-- 2. GRUPO DE MENÚ -->
            <div class="form-group">
                <label for="menu_group">Grupo de Menú <span class="required">*</span></label>
                <select id="menu_group" name="menu_group" class="form-control" onchange="updatePreview()">
                    <?php foreach ($menuGroups as $group): ?>
                        <option value="<?= htmlspecialchars($group) ?>"
                            <?= (($_POST['menu_group'] ?? 'vistas') === $group) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">Agrupa rutas similares bajo un mismo segmento de la URL.</small>
            </div>

            <!-- 3. URI GENERADA AUTOMÁTICAMENTE -->
            <div class="form-group">
                <label for="uri">URI Generada <span class="required">*</span></label>
                <div class="preview-field">
                    <span class="preview-prefix">/admin/</span>
                    <input
                        type="text"
                        id="uri"
                        name="uri"
                        class="form-control preview-input"
                        readonly
                        value="<?= htmlspecialchars($_POST['uri'] ?? '/admin/vistas/crear') ?>"
                        title="Generada automáticamente"
                    >
                </div>
                <small class="form-hint">Generada automáticamente: <code>/admin/{grupo}/{titulo_normalizado}</code></small>
            </div>

            <!-- 4. TIPO DE LAYOUT -->
            <div class="form-group">
                <label for="layout_type">Tipo de Layout <span class="required">*</span></label>
                <select id="layout_type" name="layout_type" class="form-control" onchange="updatePreview()">
                    <option value="private" <?= (($_POST['layout_type'] ?? 'private') === 'private') ? 'selected' : '' ?>>
                        Private (Zona Protegida)
                    </option>
                    <option value="public" <?= (($_POST['layout_type'] ?? '') === 'public') ? 'selected' : '' ?>>
                        Public (Zona Pública)
                    </option>
                </select>
            </div>

            <!-- 5. RUTA FÍSICA GENERADA -->
            <div class="form-group">
                <label for="file_path">Ruta Física del Archivo</label>
                <input
                    type="text"
                    id="file_path"
                    name="file_path"
                    class="form-control preview-input"
                    readonly
                    value="<?= htmlspecialchars($_POST['file_path'] ?? 'views/private/crear.php') ?>"
                    title="Generada automáticamente"
                >
                <small class="form-hint">Generada automáticamente: <code>views/{layout_type}/{titulo_normalizado}.php</code></small>
            </div>

            <!-- 6. OPCIONES -->
            <div class="form-group form-row-checks">
                <label class="checkbox-label">
                    <input type="checkbox" id="show_in_menu" name="show_in_menu" value="1"
                        <?= isset($_POST['show_in_menu']) || !isset($_POST['menu_title']) ? 'checked' : '' ?>>
                    <span>Mostrar en el menú de navegación</span>
                </label>

                <label class="checkbox-label">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                        <?= isset($_POST['is_active']) || !isset($_POST['menu_title']) ? 'checked' : '' ?>>
                    <span>Vista activa (accesible)</span>
                </label>
            </div>

            <!-- BOTONES -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btn-submit-crear">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Registrar Vista
                </button>
                <a href="/admin/dashboard" class="btn btn-secondary">Cancelar</a>
            </div>

        </form>
    </div>
</div>

<?php /* El JS de esta vista (normalizeSlug, updatePreview) es inyectado automáticamente
         por Core\Router mediante la convención de assets: public/assets/js/crear_vista.js */ ?>
