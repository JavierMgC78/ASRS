<?php
/**
 * Vista: Gestión de Roles - Creación (Alta)
 * Grupo:     Roles
 * Archivo:   crear.php
 * Ruta:      views/private/roles/crear.php
 * Assets:    assets/css/roles/crear.css
 *            assets/js/roles/crear.js
 */

use Core\Database;
use Core\Router;

$errorMsg   = null;
$successMsg = null;
$newRoleId  = null;
$db         = null;

// Repoblar campos en caso de envío previo
$post = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];

// ── 1. Procesamiento de formulario (POST) para INSERT seguro ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($post['name'] ?? '');
    $type        = trim($post['type'] ?? '');
    $description = trim($post['description'] ?? '');

    // Validaciones de negocio del backend
    if ($name === '') {
        $errorMsg = 'El nombre del rol es un campo obligatorio.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 60) {
        $errorMsg = 'El nombre del rol debe contener entre 3 y 60 caracteres.';
    } elseif ($type === '') {
        $errorMsg = 'Debes seleccionar un tipo de rol válido.';
    } elseif (!in_array($type, ['admin', 'standard', 'custom'], true)) {
        $errorMsg = 'El tipo de rol seleccionado no es válido.';
    } else {
        try {
            $db = Database::getInstance();

            // Verificar si ya existe un rol con el mismo nombre (unicidad)
            $checkStmt = $db->prepare("SELECT id FROM roles WHERE name = :name LIMIT 1");
            $checkStmt->execute([':name' => $name]);

            if ($checkStmt->fetch()) {
                $errorMsg = 'Ya existe un rol registrado con el nombre "' . htmlspecialchars($name) . '".';
            } else {
                // Inserción segura con sentencia preparada
                $insertStmt = $db->prepare(
                    "INSERT INTO roles (name, type, description, created_at) 
                     VALUES (:name, :type, :description, NOW())"
                );

                $insertStmt->execute([
                    ':name'        => $name,
                    ':type'        => $type,
                    ':description' => $description,
                ]);

                $newRoleId  = (int) $db->lastInsertId();
                $successMsg = 'El rol "' . htmlspecialchars($name) . '" ha sido registrado exitosamente con el ID #' . $newRoleId . '.';

                // Limpiar valores del formulario tras éxito
                $post = [];

                // Si se solicitó volver al listado tras crear
                if (!empty($_POST['redirect_to_list'])) {
                    header('Location: ' . Router::url('roles/ver') . '?created=' . $newRoleId);
                    exit;
                }
            }
        } catch (\Throwable $e) {
            $errorMsg = 'Error al registrar el rol en la base de datos: ' . $e->getMessage();
        }
    }
}

$listUrl = Router::url('roles/ver');
?>

<div class="roles-create-container">

    <!-- Encabezado de la página -->
    <div class="roles-page-header">
        <div class="roles-page-header__text">
            <div class="roles-breadcrumb">
                <a href="<?= htmlspecialchars($listUrl) ?>" class="roles-breadcrumb__link">Roles</a>
                <span class="roles-breadcrumb__separator">/</span>
                <span class="roles-breadcrumb__current">Crear Rol</span>
            </div>
            <h2 class="roles-page-title">Crear Nuevo Rol</h2>
            <p class="roles-page-subtitle">Define un nuevo perfil de permisos y nivel de acceso para los usuarios del sistema ASRS.</p>
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
                <strong>¡Registro exitoso!</strong> <?= $successMsg ?>
                <div style="margin-top: 8px;">
                    <a href="<?= htmlspecialchars($listUrl) ?>" style="color: #166534; font-weight: 600; text-decoration: underline;">
                        Ver en el listado de roles &rarr;
                    </a>
                </div>
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

    <!-- Formulario de creación del Rol -->
    <div class="roles-form-wrapper">
        <form action="" method="POST" class="roles-form" id="form-crear-rol" novalidate>

            <div class="form-grid">

                <!-- Nombre del Rol -->
                <div class="form-group">
                    <label for="name">Nombre del Rol <span class="required">*</span></label>
                    <input type="text" id="name" name="name"
                           class="form-control"
                           placeholder="Ej. Coordinador, Tutor, Estudiante"
                           value="<?= htmlspecialchars($post['name'] ?? '') ?>"
                           required minlength="3" maxlength="60"
                           autocomplete="off">
                    <small class="form-text">Nombre representativo para identificar este perfil en la asignación de usuarios.</small>
                    <div class="invalid-feedback" id="feedback-name"></div>
                </div>

                <!-- Tipo de Rol -->
                <div class="form-group">
                    <label for="type">Tipo de Rol <span class="required">*</span></label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">-- Selecciona un tipo --</option>
                        <option value="admin" <?= (($post['type'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin (Acceso y control total)</option>
                        <option value="standard" <?= (($post['type'] ?? 'standard') === 'standard') ? 'selected' : '' ?>>Standard (Operativo habitual)</option>
                        <option value="custom" <?= (($post['type'] ?? '') === 'custom') ? 'selected' : '' ?>>Custom (Personalizado / Restringido)</option>
                    </select>
                    <small class="form-text">Define la categoría base de privilegios dentro de la plataforma.</small>
                    <div class="invalid-feedback" id="feedback-type"></div>
                </div>

                <!-- Descripción del Rol -->
                <div class="form-group full-width">
                    <label for="description">Descripción Detallada</label>
                    <textarea id="description" name="description" class="form-control form-control--textarea"
                              rows="4" placeholder="Describe brevemente las funciones, permisos o responsabilidades que tendrá este rol..."
                              maxlength="255"><?= htmlspecialchars($post['description'] ?? '') ?></textarea>
                    <small class="form-text">Opcional. Proporciona contexto a otros administradores (máx. 255 caracteres).</small>
                    <div class="invalid-feedback" id="feedback-description"></div>
                </div>

            </div><!-- /.form-grid -->

            <!-- Acciones del Formulario -->
            <div class="form-actions">
                <button type="submit" class="btn-primary" id="btn-submit-rol">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span id="btn-submit-text">Registrar Rol</span>
                </button>

                <a href="<?= htmlspecialchars($listUrl) ?>" class="btn-secondary">
                    Cancelar
                </a>
            </div>

        </form>
    </div><!-- /.roles-form-wrapper -->

</div><!-- /.roles-create-container -->
