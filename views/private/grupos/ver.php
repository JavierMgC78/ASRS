<?php
/**
 * Vista: Gestión de Grupos de Menú - Catálogo y Ordenamiento
 * Grupo:     Grupos
 * Archivo:   ver.php
 * Ruta:      views/private/grupos/ver.php
 * Assets:    assets/css/grupos/ver.css
 *            assets/js/grupos/ver.js
 */

use Core\controllers\MenuGroupsController;
use Core\Router;

// Ejecutar lógica del controlador PDO para procesar peticiones y obtener datos
$moduleData = MenuGroupsController::handle();

$errorMsg   = $moduleData['error']   ?? null;
$successMsg = $moduleData['success'] ?? null;
$groups     = $moduleData['groups']  ?? [];
$stats      = $moduleData['stats']   ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'max_order' => 0];

$currentUrl = Router::url('grupos/ver');
?>

<div class="groups-container">

    <!-- 1. Encabezado de la Página -->
    <header class="groups-page-header">
        <div class="groups-page-header__text">
            <div class="groups-breadcrumb">
                <span class="groups-breadcrumb__current">Navegación</span>
                <span class="groups-breadcrumb__separator">/</span>
                <span class="groups-breadcrumb__active">Grupos de Menú</span>
            </div>
            <h2 class="groups-page-title">Gestión de Grupos de Menú</h2>
            <p class="groups-page-subtitle">Configura los módulos del menú lateral, administra su orden secuencial y sincroniza la caché estática.</p>
        </div>
        <div class="groups-page-header__actions">
            <button type="button" class="groups-btn-secondary" id="btn-sync-cache" title="Forzar reconstrucción del archivo de caché en storage/">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                <span>Sincronizar Caché</span>
            </button>
            <button type="button" class="groups-btn-primary" id="btn-open-modal-create">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nuevo Grupo</span>
            </button>
        </div>
    </header>

    <!-- 2. Alertas del Sistema -->
    <div id="groups-alert-container">
        <?php if (!empty($successMsg)): ?>
            <div class="groups-alert groups-alert--success" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div class="groups-alert__content"><?= $successMsg ?></div>
                <button type="button" class="groups-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="groups-alert groups-alert--danger" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div class="groups-alert__content"><?= $errorMsg ?></div>
                <button type="button" class="groups-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Tarjetas de Resumen (Stats Cards) -->
    <div class="groups-stats-grid">
        <div class="groups-stat-card">
            <div class="groups-stat-card__icon groups-stat-card__icon--blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div class="groups-stat-card__info">
                <span class="groups-stat-card__label">Total Grupos</span>
                <span class="groups-stat-card__value" id="stat-total"><?= (int)$stats['total'] ?></span>
            </div>
        </div>

        <div class="groups-stat-card">
            <div class="groups-stat-card__icon groups-stat-card__icon--green">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="groups-stat-card__info">
                <span class="groups-stat-card__label">Activos en Menú</span>
                <span class="groups-stat-card__value" id="stat-active"><?= (int)$stats['active'] ?></span>
            </div>
        </div>

        <div class="groups-stat-card">
            <div class="groups-stat-card__icon groups-stat-card__icon--amber">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                </svg>
            </div>
            <div class="groups-stat-card__info">
                <span class="groups-stat-card__label">Inactivos / Ocultos</span>
                <span class="groups-stat-card__value" id="stat-inactive"><?= (int)$stats['inactive'] ?></span>
            </div>
        </div>

        <div class="groups-stat-card">
            <div class="groups-stat-card__icon groups-stat-card__icon--purple">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div class="groups-stat-card__info">
                <span class="groups-stat-card__label">Caché Sincronizada</span>
                <span class="groups-stat-card__value groups-stat-card__value--badge">storage/menu_groups_cache.php</span>
            </div>
        </div>
    </div>

    <!-- 4. Contenedor de la Tabla Principal -->
    <div class="groups-card">
        <div class="groups-card__header">
            <div class="groups-card__search">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="groups-search-input" placeholder="Buscar grupo por nombre o slug..." autocomplete="off">
            </div>
            <div class="groups-card__meta">
                <span class="groups-pill-count">
                    Mostrando <strong id="groups-visible-count"><?= count($groups) ?></strong> grupos
                </span>
            </div>
        </div>

        <div class="groups-table-responsive">
            <table class="groups-table" id="groups-data-table">
                <thead>
                    <tr>
                        <th class="th-order" title="Secuencia en la que se despliegan en el menú">Orden</th>
                        <th class="th-name">Nombre del Grupo</th>
                        <th class="th-slug">Slug / Identificador</th>
                        <th class="th-views">Vistas Vinculadas</th>
                        <th class="th-status">Estado</th>
                        <th class="th-updated">Última Modificación</th>
                        <th class="th-actions text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="groups-table-body">
                    <?php if (empty($groups)): ?>
                        <tr class="tr-empty">
                            <td colspan="7">
                                <div class="groups-empty-state">
                                    <div class="groups-empty-state__icon">📂</div>
                                    <h4 class="groups-empty-state__title">No hay grupos de menú registrados</h4>
                                    <p class="groups-empty-state__desc">Crea el primer grupo para comenzar a ordenar la navegación de ASRS.</p>
                                    <button type="button" class="groups-btn-primary btn-trigger-create">Crear Grupo</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                            <tr class="group-row" 
                                data-id="<?= (int)$group['id'] ?>"
                                data-name="<?= htmlspecialchars($group['name']) ?>"
                                data-slug="<?= htmlspecialchars($group['slug']) ?>"
                                data-order="<?= (int)$group['display_order'] ?>"
                                data-description="<?= htmlspecialchars($group['description'] ?? '') ?>"
                                data-active="<?= (int)$group['is_active'] ?>"
                                data-views="<?= (int)$group['views_count'] ?>">
                                
                                <!-- Orden -->
                                <td class="td-order">
                                    <span class="order-badge" title="Orden secuencial #<?= (int)$group['display_order'] ?>">
                                        <?= (int)$group['display_order'] ?>
                                    </span>
                                </td>

                                <!-- Nombre y descripción -->
                                <td class="td-name">
                                    <div class="group-name-cell">
                                        <span class="group-name-title"><?= htmlspecialchars($group['name']) ?></span>
                                        <?php if (!empty($group['description'])): ?>
                                            <span class="group-name-desc"><?= htmlspecialchars($group['description']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Slug -->
                                <td class="td-slug">
                                    <code class="slug-tag"><?= htmlspecialchars($group['slug']) ?></code>
                                </td>

                                <!-- Vistas asignadas -->
                                <td class="td-views">
                                    <span class="views-badge <?= (int)$group['views_count'] > 0 ? 'views-badge--has' : 'views-badge--empty' ?>"
                                          title="<?= (int)$group['views_count'] ?> vistas asociadas en la tabla views">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span><?= (int)$group['views_count'] ?> vista<?= (int)$group['views_count'] !== 1 ? 's' : '' ?></span>
                                    </span>
                                </td>

                                <!-- Estado Toggle -->
                                <td class="td-status">
                                    <button type="button" 
                                            class="btn-toggle-status status-badge <?= (int)$group['is_active'] === 1 ? 'status-badge--active' : 'status-badge--inactive' ?>"
                                            data-id="<?= (int)$group['id'] ?>"
                                            title="Haz clic para <?= (int)$group['is_active'] === 1 ? 'desactivar' : 'activar' ?> este grupo">
                                        <span class="status-dot"></span>
                                        <span class="status-text"><?= (int)$group['is_active'] === 1 ? 'Activo' : 'Inactivo' ?></span>
                                    </button>
                                </td>

                                <!-- Fecha -->
                                <td class="td-updated">
                                    <span class="date-text" title="<?= htmlspecialchars($group['updated_at'] ?? $group['created_at']) ?>">
                                        <?= date('d/m/Y H:i', strtotime($group['updated_at'] ?? $group['created_at'])) ?>
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="td-actions text-right">
                                    <div class="row-actions">
                                        <button type="button" class="btn-action btn-action--edit btn-edit-group" 
                                                data-id="<?= (int)$group['id'] ?>" 
                                                title="Modificar grupo">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            <span>Editar</span>
                                        </button>
                                        
                                        <?php if ((int)$group['views_count'] === 0): ?>
                                            <button type="button" class="btn-action btn-action--delete btn-delete-group" 
                                                    data-id="<?= (int)$group['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($group['name']) ?>"
                                                    title="Eliminar grupo sin vistas">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-action btn-action--disabled" 
                                                    disabled 
                                                    title="No se puede eliminar: tiene <?= (int)$group['views_count'] ?> vista(s) asociada(s)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 5. Modal de Creación / Edición de Grupo -->
<div class="groups-modal" id="modal-group" aria-hidden="true">
    <div class="groups-modal__backdrop" id="modal-group-backdrop"></div>
    <div class="groups-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-group-title">
        
        <div class="groups-modal__header">
            <div class="groups-modal__header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div>
                <h3 class="groups-modal__title" id="modal-group-title">Nuevo Grupo de Menú</h3>
                <p class="groups-modal__subtitle" id="modal-group-subtitle">Define las propiedades del grupo para la navegación lateral.</p>
            </div>
            <button type="button" class="groups-modal__close" id="btn-close-modal" aria-label="Cerrar">&times;</button>
        </div>

        <form id="form-group-modal" action="" method="POST" class="groups-form">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="form-group-id" value="">

            <div class="groups-modal__body">
                
                <div id="modal-alert-error" class="groups-alert groups-alert--danger" style="display: none;" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span id="modal-alert-error-text"></span>
                </div>

                <!-- Nombre -->
                <div class="form-group">
                    <label for="input-group-name" class="form-label">
                        Nombre del Grupo <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="input-group-name" name="name" class="form-control" 
                           placeholder="ej. Finanzas, Reportes, Configuración" required maxlength="100">
                    <small class="form-hint">Es la etiqueta visible del encabezado en el menú lateral.</small>
                </div>

                <div class="form-row">
                    <!-- Slug -->
                    <div class="form-group col-md-8">
                        <label for="input-group-slug" class="form-label">
                            Slug Único <span class="text-danger">*</span>
                        </label>
                        <div class="input-with-prefix">
                            <span class="input-prefix">#</span>
                            <input type="text" id="input-group-slug" name="slug" class="form-control" 
                                   placeholder="ej. finanzas" required maxlength="100">
                        </div>
                        <small class="form-hint">Identificador URL amigable (minúsculas, guiones).</small>
                    </div>

                    <!-- Orden de Visualización -->
                    <div class="form-group col-md-4">
                        <label for="input-group-order" class="form-label">
                            Orden <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="input-group-order" name="display_order" class="form-control" 
                               min="0" max="999" value="<?= (int)$stats['max_order'] + 1 ?>" required>
                        <small class="form-hint">Posición secuencial.</small>
                    </div>
                </div>

                <!-- Descripción Opcional -->
                <div class="form-group">
                    <label for="input-group-description" class="form-label">Descripción Opcional</label>
                    <textarea id="input-group-description" name="description" class="form-control" rows="2" 
                              placeholder="Breve detalle sobre los módulos que agrupa..."></textarea>
                </div>

                <!-- Estado Activo -->
                <div class="form-group form-check-wrapper">
                    <label class="toggle-switch">
                        <input type="checkbox" id="input-group-active" name="is_active" value="1" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="toggle-label-group">
                        <span class="toggle-title">Activo en Navegación</span>
                        <span class="toggle-desc">Si se desactiva, el grupo y sus vistas no se mostrarán en el menú lateral.</span>
                    </div>
                </div>

            </div>

            <div class="groups-modal__footer">
                <button type="button" class="groups-btn-secondary" id="btn-cancel-modal">Cancelar</button>
                <button type="submit" class="groups-btn-primary" id="btn-submit-modal">
                    <span class="btn-spinner" style="display: none;"></span>
                    <span id="btn-submit-text">Guardar Grupo</span>
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Contenedor Toast Flotante para Notificaciones AJAX -->
<div class="groups-toast-container" id="groups-toast-container"></div>
