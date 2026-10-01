<?php
/**
 * Vista: Gestión de Usuarios - Modificar Usuario
 * Grupo:     Usuarios
 * Archivo:   editar.php
 * Ruta:      views/private/usuarios/editar.php
 * Assets:    assets/css/usuarios/editar.css
 *            assets/js/usuarios/editar.js
 */

use Core\controllers\UsersController;
use Core\Router;

// Ejecutar controlador seguro con PDO
$data = UsersController::handle();

$errorMsg   = $data['error']    ?? null;
$successMsg = $data['success']  ?? null;
$user       = $data['user']     ?? null;
$roles      = $data['roles']    ?? [];
$allUsers   = $data['allUsers'] ?? [];

$currentSessionUserId = (int)($_SESSION['user']['id'] ?? 1);
$isSelf = $user && ((int)$user['id'] === $currentSessionUserId);

// Generar iniciales para el avatar
$initials = 'U';
if ($user && !empty($user['name'])) {
    $parts = explode(' ', trim($user['name']));
    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= mb_strtoupper(mb_substr(end($parts), 0, 1));
    }
}
?>

<div class="user-edit-container">

    <!-- 1. Encabezado de la Página con Breadcrumb y Selector de Usuario -->
    <header class="user-edit-header">
        <div class="user-edit-header__text">
            <nav class="user-breadcrumb" aria-label="Migas de pan">
                <span class="user-breadcrumb__item">Administración</span>
                <span class="user-breadcrumb__separator">/</span>
                <span class="user-breadcrumb__item">Usuarios</span>
                <span class="user-breadcrumb__separator">/</span>
                <span class="user-breadcrumb__active">Editar Usuario</span>
            </nav>
            <h1 class="user-edit-title">
                Editar Cuenta de Usuario
                <?php if ($user): ?>
                    <span class="user-id-chip" title="ID único del usuario en el sistema">#<?= (int)$user['id'] ?></span>
                <?php endif; ?>
            </h1>
            <p class="user-edit-subtitle">
                Actualiza la información personal, credenciales de acceso y asignación de roles RBAC en la plataforma.
            </p>
        </div>

        <div class="user-edit-header__actions">
            <!-- Selector Rápido de Usuarios -->
            <div class="user-quick-selector">
                <label for="select-quick-user" class="quick-selector-label">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Cambiar usuario:</span>
                </label>
                <select id="select-quick-user" class="quick-selector-input" onchange="if(this.value) window.location.href='?id=' + this.value;">
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ($user && (int)$user['id'] === (int)$u['id']) ? 'selected' : '' ?>>
                            #<?= (int)$u['id'] ?> - <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </header>

    <!-- 2. Alertas del Sistema -->
    <div id="user-alert-container">
        <?php if (!empty($successMsg)): ?>
            <div class="user-alert user-alert--success" role="alert">
                <div class="user-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="user-alert__message">
                    <?= $successMsg ?>
                </div>
                <button type="button" class="user-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="user-alert user-alert--danger" role="alert">
                <div class="user-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="user-alert__message">
                    <?= $errorMsg ?>
                </div>
                <button type="button" class="user-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$user): ?>
        <div class="user-empty-card">
            <div class="user-empty-icon">👤</div>
            <h3>Usuario no encontrado</h3>
            <p>El identificador de usuario especificado no existe o no tiene permisos para ser gestionado.</p>
            <a href="?id=<?= $currentSessionUserId ?>" class="btn-primary">Ver mi perfil</a>
        </div>
    <?php else: ?>

        <!-- 3. Formulario Principal con Layout de 2 Columnas -->
        <form id="form-edit-user" action="" method="POST" class="user-edit-form" novalidate>
            <input type="hidden" name="id" id="user-id" value="<?= (int)$user['id'] ?>">

            <div class="user-layout-grid">

                <!-- Columna Izquierda: Formulario Principal -->
                <div class="user-main-col">

                    <!-- Tarjeta 1: Información de Identidad -->
                    <div class="user-card">
                        <div class="user-card__header">
                            <div class="user-card__header-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div>
                                <h2 class="user-card__title">Información Personal</h2>
                                <p class="user-card__subtitle">Datos principales y de contacto del titular de la cuenta.</p>
                            </div>
                        </div>

                        <div class="user-card__body">
                            <div class="form-grid-2">
                                <!-- Nombre Completo -->
                                <div class="form-group">
                                    <label for="input-name" class="form-label">
                                        Nombre Completo <span class="required-mark">*</span>
                                    </label>
                                    <div class="input-icon-wrap">
                                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input type="text" id="input-name" name="name" class="form-control"
                                               value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>"
                                               placeholder="ej. Juan Carlos Pérez"
                                               required minlength="3" maxlength="150" autocomplete="name">
                                    </div>
                                    <small class="form-feedback" id="feedback-name"></small>
                                </div>

                                <!-- Correo Electrónico -->
                                <div class="form-group">
                                    <label for="input-email" class="form-label">
                                        Correo Electrónico <span class="required-mark">*</span>
                                    </label>
                                    <div class="input-icon-wrap">
                                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        <input type="email" id="input-email" name="email" class="form-control"
                                               value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                               placeholder="usuario@dominio.com"
                                               required maxlength="150" autocomplete="email">
                                    </div>
                                    <small class="form-feedback" id="feedback-email"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Rol y Permisos de Acceso -->
                    <div class="user-card">
                        <div class="user-card__header">
                            <div class="user-card__header-icon user-card__header-icon--shield">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="user-card__title">Permisos y Rol del Sistema</h2>
                                <p class="user-card__subtitle">Define el perfil de autorización que rige el acceso a las vistas de ASRS.</p>
                            </div>
                        </div>

                        <div class="user-card__body">
                            <div class="form-grid-2">
                                <!-- Selector de Rol -->
                                <div class="form-group">
                                    <label for="select-role" class="form-label">
                                        Rol Asignado <span class="required-mark">*</span>
                                    </label>
                                    <div class="input-icon-wrap">
                                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                        <select id="select-role" name="role_id" class="form-control" required <?= ($isSelf && $user['role_name'] === 'super_admin') ? 'disabled' : '' ?>>
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?= (int)$r['id'] ?>" 
                                                        data-desc="<?= htmlspecialchars($r['description'] ?? '') ?>"
                                                        data-type="<?= htmlspecialchars($r['type']) ?>"
                                                        <?= ((int)($_POST['role_id'] ?? $user['role_id']) === (int)$r['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($r['name']) ?> (<?= $r['type'] === 'special' ? 'Especial' : 'Estándar' ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if ($isSelf && $user['role_name'] === 'super_admin'): ?>
                                        <!-- Campo oculto para enviar role_id cuando está disabled -->
                                        <input type="hidden" name="role_id" value="<?= (int)$user['role_id'] ?>">
                                        <small class="form-hint text-amber">Por seguridad, no puedes despojarte de tu propio rol de Super Administrador.</small>
                                    <?php else: ?>
                                        <small class="form-hint" id="role-description-hint"></small>
                                    <?php endif; ?>
                                </div>

                                <!-- Estado Activo / Inactivo -->
                                <div class="form-group">
                                    <label class="form-label">Estado de la Cuenta</label>
                                    <div class="toggle-card <?= $isSelf ? 'toggle-card--disabled' : '' ?>">
                                        <label class="switch-control">
                                            <input type="checkbox" id="check-is-active" name="is_active" value="1" 
                                                   <?= ((int)($_POST['is_active'] ?? $user['is_active']) === 1) ? 'checked' : '' ?>
                                                   <?= $isSelf ? 'disabled' : '' ?>>
                                            <span class="switch-slider"></span>
                                        </label>
                                        <div class="switch-text-group">
                                            <span class="switch-title" id="switch-status-label">
                                                <?= ((int)$user['is_active'] === 1) ? 'Cuenta Activa' : 'Cuenta Suspendida / Inactiva' ?>
                                            </span>
                                            <span class="switch-desc">
                                                <?= $isSelf ? 'No puedes auto-desactivar tu cuenta activa.' : 'Los usuarios inactivos no pueden iniciar sesión.' ?>
                                            </span>
                                        </div>
                                        <?php if ($isSelf): ?>
                                            <input type="hidden" name="is_active" value="1">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Seguridad y Cambio de Contraseña -->
                    <div class="user-card">
                        <div class="user-card__header">
                            <div class="user-card__header-icon user-card__header-icon--lock">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="user-card__title">Cambio de Contraseña</h2>
                                <p class="user-card__subtitle">Opcional. Deja estos campos en blanco si conservas la contraseña actual.</p>
                            </div>
                        </div>

                        <div class="user-card__body">
                            <div class="password-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>Solo completa estos campos si deseas restablecer o cambiar la contraseña del usuario.</span>
                            </div>

                            <div class="form-grid-2">
                                <!-- Nueva Contraseña -->
                                <div class="form-group">
                                    <label for="input-password" class="form-label">Nueva Contraseña</label>
                                    <div class="input-password-wrap">
                                        <input type="password" id="input-password" name="password" class="form-control"
                                               placeholder="Mínimo 6 caracteres" minlength="6" autocomplete="new-password">
                                        <button type="button" class="btn-toggle-eye" data-target="input-password" title="Ver / Ocultar contraseña">
                                            <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            <svg class="eye-closed" style="display:none;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        </button>
                                    </div>
                                    <div class="password-strength-bar" id="password-strength-bar">
                                        <div class="strength-meter" id="strength-meter"></div>
                                    </div>
                                    <small class="form-feedback" id="feedback-password"></small>
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div class="form-group">
                                    <label for="input-password-confirm" class="form-label">Confirmar Contraseña</label>
                                    <div class="input-password-wrap">
                                        <input type="password" id="input-password-confirm" name="password_confirm" class="form-control"
                                               placeholder="Repite la nueva contraseña" minlength="6" autocomplete="new-password">
                                        <button type="button" class="btn-toggle-eye" data-target="input-password-confirm" title="Ver / Ocultar contraseña">
                                            <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            <svg class="eye-closed" style="display:none;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        </button>
                                    </div>
                                    <small class="form-feedback" id="feedback-password-confirm"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Columna Derecha: Tarjeta de Perfil & Acciones de Guardado -->
                <div class="user-side-col">

                    <!-- Tarjeta de Perfil Resumen -->
                    <div class="user-card user-card--profile">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar">
                                <span id="profile-initials"><?= htmlspecialchars($initials) ?></span>
                            </div>
                            <span class="profile-status-indicator <?= (int)$user['is_active'] === 1 ? 'is-online' : 'is-offline' ?>" id="profile-status-dot"></span>
                        </div>

                        <div class="profile-meta">
                            <h3 class="profile-name" id="profile-name-preview"><?= htmlspecialchars($user['name']) ?></h3>
                            <p class="profile-email" id="profile-email-preview"><?= htmlspecialchars($user['email']) ?></p>
                            
                            <div class="profile-badges">
                                <span class="badge-role" id="profile-role-badge">
                                    <?= htmlspecialchars($user['role_name']) ?>
                                </span>
                                <span class="badge-status <?= (int)$user['is_active'] === 1 ? 'badge-status--active' : 'badge-status--inactive' ?>" id="profile-status-badge">
                                    <?= (int)$user['is_active'] === 1 ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                        </div>

                        <hr class="profile-divider">

                        <div class="profile-details-list">
                            <div class="profile-detail-row">
                                <span class="detail-label">ID de Usuario</span>
                                <span class="detail-val">#<?= (int)$user['id'] ?></span>
                            </div>
                            <div class="profile-detail-row">
                                <span class="detail-label">Tipo de Rol</span>
                                <span class="detail-val" id="profile-role-type"><?= ucfirst($user['role_type']) ?></span>
                            </div>
                            <div class="profile-detail-row">
                                <span class="detail-label">Fecha de Registro</span>
                                <span class="detail-val"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span>
                            </div>
                            <?php if ($isSelf): ?>
                                <div class="profile-detail-row profile-detail-row--self">
                                    <span class="detail-badge-self">⭐ Sesión Actual</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tarjeta de Acciones -->
                    <div class="user-card user-card--actions">
                        <h4 class="actions-card-title">Acciones</h4>
                        <p class="actions-card-desc">Los cambios surtirán efecto de inmediato en la base de datos.</p>

                        <button type="submit" class="btn-save-user" id="btn-save-user">
                            <span class="btn-spinner" style="display: none;" id="save-spinner"></span>
                            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span id="btn-save-text">Guardar Cambios</span>
                        </button>

                        <button type="button" class="btn-reset-user" id="btn-reset-user">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            <span>Descartar Cambios</span>
                        </button>
                    </div>

                </div>

            </div>
        </form>

    <?php endif; ?>

</div>

<!-- Contenedor Toast para Feedback Flotante AJAX -->
<div class="user-toast-container" id="user-toast-container"></div>
