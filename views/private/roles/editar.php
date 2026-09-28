<?php
/**
 * Vista: Gestión de Roles - Modificación (Editar)
 * Grupo:     Roles
 * Archivo:   editar.php
 * Ruta:      views/private/roles/editar.php
 * Assets:    assets/css/roles/editar.css
 *            assets/js/roles/editar.js
 */

use Core\Database;
use Core\Router;

// ── 1. Captura y sanitización del parámetro GET `id` ─────────────────────────
$rawId  = $_GET['id'] ?? null;
$roleId = filter_var($rawId, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

$errorMsg   = null;
$successMsg = null;
$role       = null;
$db         = null;

try {
    $db = Database::getInstance();
} catch (\Throwable $e) {
    $errorMsg = 'Error al conectar con la base de datos: ' . $e->getMessage();
}

// Validar presencia y validez del ID
if (!$roleId || $roleId <= 0) {
    $errorMsg = 'Identificador de rol no válido o no proporcionado.';
}

// ── 2. Procesamiento de formulario (POST) para UPDATE seguro ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $roleId && $db) {
    $name        = trim($_POST['name'] ?? '');
    $type        = trim($_POST['type'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validaciones de servidor
    if ($name === '') {
        $errorMsg = 'El nombre del rol es un campo obligatorio.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 60) {
        $errorMsg = 'El nombre del rol debe contener entre 3 y 60 caracteres.';
    } elseif ($type === '') {
        $errorMsg = 'Debes seleccionar un tipo de rol válido.';
    } else {
        try {
            // Verificar unicidad del nombre excluyendo el rol actual
            $checkStmt = $db->prepare("SELECT id FROM roles WHERE name = :name AND id != :id LIMIT 1");
            $checkStmt->execute([
                ':name' => $name,
                ':id'   => $roleId,
            ]);

            if ($checkStmt->fetch()) {
                $errorMsg = 'Ya existe otro rol registrado con el nombre "' . htmlspecialchars($name) . '".';
            } else {
                // UPDATE seguro mediante sentencia preparada
                $updateStmt = $db->prepare(
                    "UPDATE roles 
                     SET name = :name, type = :type, description = :description 
                     WHERE id = :id"
                );

                $updateStmt->execute([
                    ':name'        => $name,
                    ':type'        => $type,
                    ':description' => $description,
                    ':id'          => $roleId,
                ]);

                $successMsg = 'El rol ha sido actualizado exitosamente.';

                // Si se solicitó volver al listado tras guardar
                if (!empty($_POST['redirect_to_list'])) {
                    header('Location: ' . Router::url('roles/ver') . '?updated=' . $roleId);
                    exit;
                }
            }
        } catch (\Throwable $e) {
            $errorMsg = 'Error al procesar la actualización en la base de datos: ' . $e->getMessage();
        }
    }
}

// ── 3. Consulta de datos del rol existente vía PDO ───────────────────────────
if ($roleId && $db) {
    try {
        $stmt = $db->prepare(
            "SELECT id, name, type, description, created_at 
             FROM roles 
             WHERE id = :id 
             LIMIT 1"
        );
        $stmt->execute([':id' => $roleId]);
        $role = $stmt->fetch();

        if (!$role && !$errorMsg) {
            $errorMsg = 'El rol solicitado (ID #' . htmlspecialchars((string)$roleId) . ') no existe o ha sido eliminado.';
        }
    } catch (\Throwable $e) {
        if (!$errorMsg) {
            $errorMsg = 'Error al consultar la información del rol: ' . $e->getMessage();
        }
    }
}

// Valores finales para precargar campos (retiene datos de POST fallido si existieran)
$valName = $_POST['name'] ?? ($role['name'] ?? '');
$valType = $_POST['type'] ?? ($role['type'] ?? 'standard');
$valDesc = $_POST['description'] ?? ($role['description'] ?? '');

$listUrl = Router::url('roles/ver');
?>

<div class="roles-edit-container">

    <!-- Encabezado de la página -->
    <div class="roles-page-header">
        <div class="roles-page-header__text">
            <div class="roles-breadcrumb">
                <a href="<?= htmlspecialchars($listUrl) ?>" class="roles-breadcrumb__link">Roles</a>
                <span class="roles-breadcrumb__separator">/</span>
                <span class="roles-breadcrumb__current">Modificar Rol</span>
            </div>
            <h2 class="roles-page-title">
                Modificar Rol <?= $role ? '<span class="roles-id-chip">#' . (int)$role['id'] . '</span>' : '' ?>
            </h2>
            <p class="roles-page-subtitle">Actualiza los permisos, tipo y descripción del rol en el sistema ASRS.</p>
        </div>
        <div class="roles-page-header__actions">
            <a href="<?= htmlspecialchars($listUrl) ?>" class="btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Volver al Listado</span>
            </a>
        </div>
    </div>

    <!-- Feedback: Mensaje de Éxito -->
    <?php if (!empty($successMsg)): ?>
        <div class="roles-alert roles-alert--success" role="alert" id="roles-alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <div>
                <strong>¡Actualizado!</strong> <?= htmlspecialchars($successMsg) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Feedback: Mensaje de Error -->
    <?php if (!empty($errorMsg)): ?>
        <div class="roles-alert roles-alert--error" role="alert" id="roles-alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <strong>Atención:</strong> <?= htmlspecialchars($errorMsg) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role): ?>
        <!-- Formulario de edición del Rol -->
        <div class="roles-form-wrapper">
            <form action="" method="POST" class="roles-form" id="form-editar-rol" novalidate>

                <div class="form-grid">

                    <!-- ID del Rol (Lectura / Precargado) -->
                    <div class="form-group">
                        <label for="role_id_display">Identificador (ID)</label>
                        <input type="text" id="role_id_display" class="form-control form-control--readonly"
                               value="#<?= (int)$role['id'] ?>" readonly tabindex="-1">
                        <small class="form-text">Identificador numérico único asignado por el sistema.</small>
                    </div>

                    <!-- Fecha de Creación (Informativo) -->
                    <div class="form-group">
                        <label for="created_at_display">Fecha de Registro</label>
                        <input type="text" id="created_at_display" class="form-control form-control--readonly"
                               value="<?= !empty($role['created_at']) ? date('d/m/Y H:i:s', strtotime($role['created_at'])) : '—' ?>"
                               readonly tabindex="-1">
                        <small class="form-text">Momento en que el rol fue registrado originalmente.</small>
                    </div>

                    <!-- Nombre del Rol -->
                    <div class="form-group">
                        <label for="name">Nombre del Rol <span class="required">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control"
                               placeholder="Ej. Administrador, Docente, Alumno"
                               value="<?= htmlspecialchars($valName) ?>"
                               required minlength="3" maxlength="60"
                               autocomplete="off">
                        <small class="form-text">Nombre representativo para visualización y asignación de usuarios.</small>
                        <div class="invalid-feedback" id="feedback-name"></div>
                    </div>

                    <!-- Tipo de Rol -->
                    <div class="form-group">
                        <label for="type">Tipo de Rol <span class="required">*</span></label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">-- Selecciona un tipo --</option>
                            <option value="admin" <?= ($valType === 'admin') ? 'selected' : '' ?>>Admin (Control Total)</option>
                            <option value="standard" <?= ($valType === 'standard') ? 'selected' : '' ?>>Standard (Operativo)</option>
                            <option value="custom" <?= ($valType === 'custom') ? 'selected' : '' ?>>Custom (Personalizado)</option>
                        </select>
                        <small class="form-text">Determina el nivel base de privilegios dentro de la plataforma.</small>
                        <div class="invalid-feedback" id="feedback-type"></div>
                    </div>

                    <!-- Descripción del Rol -->
                    <div class="form-group full-width">
                        <label for="description">Descripción Detallada</label>
                        <textarea id="description" name="description" class="form-control form-control--textarea"
                                  rows="4" placeholder="Describe brevemente las responsabilidades o alcance de este rol..."
                                  maxlength="255"><?= htmlspecialchars($valDesc) ?></textarea>
                        <small class="form-text">Opcional. Máximo 255 caracteres.</small>
                        <div class="invalid-feedback" id="feedback-description"></div>
                    </div>

                </div><!-- /.form-grid -->

                <!-- Barra de Acciones del Formulario -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="btn-submit-rol">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span id="btn-submit-text">Guardar Cambios</span>
                    </button>

                    <a href="<?= htmlspecialchars($listUrl) ?>" class="btn-secondary">
                        Cancelar
                    </a>
                </div>

            </form>
        </div><!-- /.roles-form-wrapper -->

    <?php else: ?>
        <!-- Estado de rol no encontrado -->
        <div class="roles-empty-state">
            <div class="roles-empty-state__icon">🔍</div>
            <p class="roles-empty-state__title">Rol no disponible</p>
            <p class="roles-empty-state__desc">No se puede cargar la información solicitada. Comprueba el identificador o regresa al listado general.</p>
            <div style="margin-top: 18px;">
                <a href="<?= htmlspecialchars($listUrl) ?>" class="btn-primary" style="display: inline-flex; text-decoration: none;">
                    Ir al Listado de Roles
                </a>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /.roles-edit-container -->
