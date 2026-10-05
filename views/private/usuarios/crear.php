<?php
/**
 * Vista: Gestión de Usuarios - Crear Nuevo Usuario
 * Grupo:     Usuarios
 * Archivo:   crear.php
 * Ruta:      views/private/usuarios/crear.php
 * Assets:    assets/css/usuarios/crear.css
 *            assets/js/usuarios/crear.js
 */

use Core\controllers\UsersController;
use Core\Router;

// Ejecutar lógica del controlador PDO para procesar peticiones y obtener roles
$moduleData = UsersController::handleCreate();

$errorMsg   = $moduleData['error']     ?? null;
$successMsg = $moduleData['success']   ?? null;
$roles      = $moduleData['roles']     ?? [];
$newUserId  = $moduleData['newUserId'] ?? null;

$editUrl    = Router::url('usuarios/editar');
?>

<div class="user-create-container">

    <!-- 1. Encabezado de la Página -->
    <header class="user-create-header">
        <div class="user-create-header__text">
            <nav class="user-breadcrumb" aria-label="Migas de pan">
                <span class="user-breadcrumb__item">Administración</span>
                <span class="user-breadcrumb__separator">/</span>
                <a href="<?= htmlspecialchars($editUrl) ?>" class="user-breadcrumb__link">Usuarios</a>
                <span class="user-breadcrumb__separator">/</span>
                <span class="user-breadcrumb__active">Crear Usuario</span>
            </nav>
            <h1 class="user-create-title">Registrar Nuevo Usuario</h1>
            <p class="user-create-subtitle">
                Da de alta una nueva cuenta de acceso al sistema, asigna su rol institucional y configura sus credenciales de seguridad.
            </p>
        </div>

        <div class="user-create-header__actions">
            <a href="<?= htmlspecialchars($editUrl) ?>" class="btn-secondary" title="Ir a la administración de usuarios">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Volver a Usuarios</span>
            </a>
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
                    <?php if ($newUserId): ?>
                        <div class="user-alert__actions">
                            <a href="<?= htmlspecialchars($editUrl) ?>?id=<?= (int)$newUserId ?>" class="alert-link-btn">
                                Ver y Editar Cuenta #<?= (int)$newUserId ?> &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
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

    <!-- 3. Formulario de Alta con Layout Vertical de Ancho Completo (100%) -->
    <form id="form-create-user" action="" method="POST" class="user-create-form" novalidate>

        <div class="user-layout-grid">

            <!-- Bloque de Secciones del Formulario Principal (100% de Ancho) -->
            <div class="user-main-col">

                <!-- Tarjeta 1: Información Personal -->
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
                            <p class="user-card__subtitle">Datos de identificación y correo principal del usuario.</p>
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
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                           placeholder="ej. Ana Patricia Morales"
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
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                           placeholder="ej. ana.morales@axe.test"
                                           required maxlength="150" autocomplete="email">
                                </div>
                                <small class="form-feedback" id="feedback-email"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Credenciales de Seguridad -->
                <div class="user-card">
                    <div class="user-card__header">
                        <div class="user-card__header-icon user-card__header-icon--lock">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="user-card__title">Credenciales de Acceso</h2>
                            <p class="user-card__subtitle">Contraseña inicial requerida para el primer inicio de sesión.</p>
                        </div>
                    </div>

                    <div class="user-card__body">
                        <div class="form-grid-2">
                            <!-- Contraseña -->
                            <div class="form-group">
                                <label for="input-password" class="form-label">
                                    Contraseña Inicial <span class="required-mark">*</span>
                                </label>
                                <div class="input-password-wrap">
                                    <input type="password" id="input-password" name="password" class="form-control"
                                           placeholder="Mínimo 6 caracteres" required minlength="6" autocomplete="new-password">
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
                                <label for="input-password-confirm" class="form-label">
                                    Confirmar Contraseña <span class="required-mark">*</span>
                                </label>
                                <div class="input-password-wrap">
                                    <input type="password" id="input-password-confirm" name="password_confirm" class="form-control"
                                           placeholder="Repite la contraseña" required minlength="6" autocomplete="new-password">
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

                <!-- Tarjeta 3: Rol y Estado -->
                <div class="user-card">
                    <div class="user-card__header">
                        <div class="user-card__header-icon user-card__header-icon--shield">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="user-card__title">Perfil de Permisos y Estado</h2>
                            <p class="user-card__subtitle">Asignación de rol para control de acceso RBAC y activación inmediata.</p>
                        </div>
                    </div>

                    <div class="user-card__body">
                        <div class="form-grid-2">
                            <!-- Rol -->
                            <div class="form-group">
                                <label for="select-role" class="form-label">
                                    Rol Asignado <span class="required-mark">*</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                    <select id="select-role" name="role_id" class="form-control" required>
                                        <option value="" disabled selected>Selecciona un rol...</option>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= (int)$r['id'] ?>" 
                                                    data-desc="<?= htmlspecialchars($r['description'] ?? '') ?>"
                                                    data-type="<?= htmlspecialchars($r['type']) ?>"
                                                    <?= ((int)($_POST['role_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($r['name']) ?> (<?= $r['type'] === 'special' ? 'Especial' : 'Estándar' ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small class="form-hint" id="role-description-hint"></small>
                            </div>

                            <!-- Estado Activo -->
                            <div class="form-group">
                                <label class="form-label">Estado Inicial</label>
                                <div class="toggle-card">
                                    <label class="switch-control">
                                        <input type="checkbox" id="check-is-active" name="is_active" value="1" 
                                               <?= (!isset($_POST['is_active']) || (int)$_POST['is_active'] === 1) ? 'checked' : '' ?>>
                                        <span class="switch-slider"></span>
                                    </label>
                                    <div class="switch-text-group">
                                        <span class="switch-title" id="switch-status-label">Cuenta Activa</span>
                                        <span class="switch-desc">Permite iniciar sesión inmediatamente tras el alta.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Ficha de Vista Previa del Nuevo Usuario (Reubicada debajo verticalmente) -->
            <div class="user-card user-card--profile">
                <div class="user-card__header">
                    <div class="user-card__header-icon user-card__header-icon--preview">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2 class="user-card__title">Vista Previa de la Ficha de Usuario</h2>
                        <p class="user-card__subtitle">Previsualización dinámica del perfil conforme completas la información del formulario.</p>
                    </div>
                </div>

                <div class="user-card__body profile-card-horizontal">
                    <div class="profile-card-horizontal__main">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar">
                                <span id="profile-initials">NU</span>
                            </div>
                            <span class="profile-status-indicator is-online" id="profile-status-dot"></span>
                        </div>

                        <div class="profile-meta">
                            <h3 class="profile-name" id="profile-name-preview">Nuevo Usuario</h3>
                            <p class="profile-email" id="profile-email-preview">correo@dominio.com</p>

                            <div class="profile-badges">
                                <span class="badge-role" id="profile-role-badge">Sin Rol</span>
                                <span class="badge-status badge-status--active" id="profile-status-badge">Activo</span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card-horizontal__details">
                        <div class="profile-details-list">
                            <div class="profile-detail-row">
                                <span class="detail-label">Tipo de Cuenta</span>
                                <span class="detail-val" id="profile-role-type">Por Definir</span>
                            </div>
                            <div class="profile-detail-row">
                                <span class="detail-label">Fecha de Alta</span>
                                <span class="detail-val"><?= date('d/m/Y') ?> (Hoy)</span>
                            </div>
                        </div>

                        <div class="profile-security-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <span>Cifrado Bcrypt Seguro (PDO)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Acciones de Registro -->
            <div class="user-card user-card--actions">
                <div class="actions-card-content">
                    <div class="actions-card-info">
                        <h4 class="actions-card-title">Acciones de Registro</h4>
                        <p class="actions-card-desc">El usuario será insertado de forma segura en la base de datos.</p>
                    </div>

                    <div class="actions-card-buttons">
                        <button type="button" class="btn-reset-user" id="btn-clear-form">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            <span>Limpiar Campos</span>
                        </button>

                        <button type="submit" class="btn-create-user" id="btn-submit-user">
                            <span class="btn-spinner" style="display: none;" id="create-spinner"></span>
                            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                            <span id="btn-submit-text">Registrar Usuario</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </form>

</div>

<!-- Contenedor Toast Flotante -->
<div class="user-toast-container" id="user-toast-container"></div>
