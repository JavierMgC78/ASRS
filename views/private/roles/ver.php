<?php
/**
 * Vista: Gestión de Roles - Listado
 * Grupo:     Roles
 * Archivo:   ver.php
 * Ruta:      views/private/roles/ver.php
 * Assets:    assets/css/roles/ver.css
 *            assets/js/roles/ver.js
 */

use Core\Database;
use Core\Router;

// ── Mensajes de feedback ───────────────────────────────────────────────────
$rawUpdated = $_GET['updated'] ?? null;
$updatedId  = filter_var($rawUpdated, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$roles      = [];
$dbError    = null;

try {
    $db   = Database::getInstance();
    $stmt = $db->query(
        "SELECT id, name, type, description, created_at
         FROM roles
         ORDER BY id ASC"
    );
    $roles = $stmt->fetchAll();
} catch (\Throwable $e) {
    $dbError = 'No se pudo conectar a la base de datos para obtener los roles.';
}
?>

<div class="roles-container">

    <!-- Encabezado de sección -->
    <div class="roles-page-header">
        <div class="roles-page-header__text">
            <h2 class="roles-page-title">Gestión de Roles</h2>
            <p class="roles-page-subtitle">Listado completo de los roles registrados en la plataforma ASRS.</p>
        </div>
        <div class="roles-page-header__actions">
            <span class="roles-badge roles-badge--total" id="roles-total-count">
                <?= count($roles) ?> rol<?= count($roles) !== 1 ? 'es' : '' ?>
            </span>
        </div>
    </div>

    <?php if ($updatedId): ?>
        <!-- Alerta de actualización exitosa -->
        <div class="roles-alert roles-alert--success" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>El rol #<?= (int)$updatedId ?> ha sido modificado y guardado exitosamente.</span>
        </div>
    <?php endif; ?>

    <?php if ($dbError): ?>
        <!-- Estado de error de BD -->
        <div class="roles-alert roles-alert--error" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span><?= htmlspecialchars($dbError) ?></span>
        </div>

    <?php elseif (empty($roles)): ?>
        <!-- Estado vacío -->
        <div class="roles-empty-state" id="roles-empty-state">
            <div class="roles-empty-state__icon">🛡️</div>
            <p class="roles-empty-state__title">Sin roles registrados</p>
            <p class="roles-empty-state__desc">No se encontraron roles en la base de datos del sistema.</p>
        </div>

    <?php else: ?>
        <!-- Tabla de roles -->
        <div class="roles-table-wrapper">
            <table class="roles-table" id="roles-table" aria-label="Tabla de roles del sistema">
                <thead>
                    <tr>
                        <th scope="col" class="col-id">#</th>
                        <th scope="col" class="col-name">Nombre del Rol</th>
                        <th scope="col" class="col-type">Tipo</th>
                        <th scope="col" class="col-desc">Descripción</th>
                        <th scope="col" class="col-date">Creado</th>
                        <th scope="col" class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr class="roles-table__row" id="role-row-<?= (int)$role['id'] ?>">
                            <td class="col-id">
                                <span class="roles-id-chip"><?= (int)$role['id'] ?></span>
                            </td>
                            <td class="col-name">
                                <div class="roles-name-cell">
                                    <span class="roles-avatar" aria-hidden="true">
                                        <?= mb_strtoupper(mb_substr($role['name'], 0, 1)) ?>
                                    </span>
                                    <span class="roles-name-text"><?= htmlspecialchars($role['name']) ?></span>
                                </div>
                            </td>
                            <td class="col-type">
                                <span class="roles-type-pill roles-type-pill--<?= htmlspecialchars($role['type'] ?? 'standard') ?>">
                                    <?= htmlspecialchars(ucfirst($role['type'] ?? 'standard')) ?>
                                </span>
                            </td>
                            <td class="col-desc">
                                <?php if (!empty($role['description'])): ?>
                                    <span class="roles-desc-text"><?= htmlspecialchars($role['description']) ?></span>
                                <?php else: ?>
                                    <span class="roles-desc-empty">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-date">
                                <time datetime="<?= htmlspecialchars($role['created_at'] ?? '') ?>"
                                      title="<?= htmlspecialchars($role['created_at'] ?? '') ?>">
                                    <?php
                                    if (!empty($role['created_at'])) {
                                        echo date('d/m/Y', strtotime($role['created_at']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </time>
                            </td>
                            <td class="col-actions">
                                <a href="<?= Router::url('roles/editar') . '?id=' . (int)$role['id'] ?>"
                                   class="roles-btn-edit"
                                   id="btn-edit-role-<?= (int)$role['id'] ?>"
                                   title="Modificar Rol">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Editar</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pie de tabla: resumen de conteo -->
        <div class="roles-table-footer" id="roles-table-footer">
            <p>Mostrando <strong><?= count($roles) ?></strong> rol<?= count($roles) !== 1 ? 'es' : '' ?> en total.</p>
        </div>

    <?php endif; ?>

</div><!-- /.roles-container -->
