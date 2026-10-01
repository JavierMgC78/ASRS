<?php
/**
 * Vista: Directorio y Listado General de Alumnos
 * Grupo:   Alumnos
 * Archivo: ver.php
 * Ruta:    views/private/alumnos/ver.php
 * Assets:  assets/css/alumnos/ver.css
 *          assets/js/alumnos/ver.js
 */

use Core\controllers\AlumnosController;
use Core\Router;

// Ejecutar lógica del controlador PDO para obtener datos y filtros
$data = AlumnosController::handleList();

$alumnos       = $data['alumnos']       ?? [];
$stats         = $data['stats']         ?? [];
$filters       = $data['filters']       ?? [];
$filterOptions = $data['filterOptions'] ?? [];
$currentRole   = $data['currentRole']   ?? '';
$errorMsg      = $data['error']         ?? null;

$crearUrl      = Router::url('alumnos/crear', 'private');
$canCrear      = Router::userCanAccess(14); // Verificar si puede registrar alumnos (super_admin, inscripcion)
?>

<div class="alumnos-list-container">

    <!-- 1. Encabezado de la Página y Migas de Pan -->
    <header class="alumnos-page-header">
        <div class="alumnos-header-text">
            <nav class="alumnos-breadcrumb" aria-label="Migas de pan">
                <span class="breadcrumb-item">Control Escolar</span>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item">Alumnos</span>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-active">Listado General</span>
            </nav>
            <h1 class="alumnos-page-title">Directorio de Alumnos</h1>
            <p class="alumnos-page-subtitle">
                Consulta y gestión integral de expedientes escolares ordenados lógicamente por nivel, grado y grupo.
            </p>
        </div>

        <div class="alumnos-header-actions">
            <?php if ($canCrear): ?>
                <a href="<?= htmlspecialchars($crearUrl) ?>" class="btn-primary" id="btn-nuevo-alumno">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Alumno</span>
                </a>
            <?php endif; ?>
            <button type="button" class="btn-secondary" id="btn-export-csv" title="Exportar vista actual a CSV">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Exportar</span>
            </button>
        </div>
    </header>

    <?php if (!empty($errorMsg)): ?>
        <div class="alumnos-alert alumnos-alert--error" role="alert">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- 2. Cuadrícula de Métricas de Matrícula -->
    <section class="alumnos-stats-grid" aria-label="Estadísticas de matrícula">
        <!-- Tarjeta 1: Total Alumnos -->
        <div class="stat-card">
            <div class="stat-icon stat-icon--blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Alumnos</div>
                <div class="stat-number" id="stat-total"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="stat-meta">Registrados en la plataforma</div>
            </div>
        </div>

        <!-- Tarjeta 2: Alumnos Activos / Inscritos -->
        <div class="stat-card">
            <div class="stat-icon stat-icon--emerald">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Inscritos / Activos</div>
                <div class="stat-number" id="stat-inscritos"><?= (int)(($stats['inscritos'] ?? 0) + ($stats['activos'] ?? 0)) ?></div>
                <div class="stat-meta">Con expediente vigente</div>
            </div>
        </div>

        <!-- Tarjeta 3: Distribución por Grupos -->
        <div class="stat-card">
            <div class="stat-icon stat-icon--violet">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Grupos Activos</div>
                <div class="stat-number" id="stat-grupos"><?= count($stats['por_grupo'] ?? []) ?></div>
                <div class="stat-meta">Grados y secciones activas</div>
            </div>
        </div>

        <!-- Tarjeta 4: Rol Actual y Alcance -->
        <div class="stat-card">
            <div class="stat-icon stat-icon--amber">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Perfil de Acceso</div>
                <div class="stat-number stat-number--role"><?= htmlspecialchars(ucfirst($currentRole)) ?></div>
                <div class="stat-meta">Vista general del colegio</div>
            </div>
        </div>
    </section>

    <!-- 3. Barra de Búsqueda y Filtros de Grado / Grupo -->
    <section class="alumnos-filter-card">
        <form id="form-alumnos-filters" method="GET" action="" class="filter-form">
            <div class="filter-form-grid">
                
                <!-- Búsqueda por texto (Nombre, CURP, Matrícula) -->
                <div class="filter-item filter-item--search">
                    <label for="filter-search" class="filter-label">Buscar Alumno</label>
                    <div class="search-input-wrapper">
                        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input 
                            type="text" 
                            id="filter-search" 
                            name="search" 
                            class="form-control form-control--search" 
                            placeholder="Nombre, apellidos, matrícula o CURP..."
                            value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                            autocomplete="off"
                        >
                        <?php if (!empty($filters['search'])): ?>
                            <button type="button" class="btn-clear-search" id="btn-clear-search" title="Limpiar búsqueda">&times;</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtro: Nivel Educativo -->
                <div class="filter-item">
                    <label for="filter-nivel" class="filter-label">Nivel Educativo</label>
                    <select id="filter-nivel" name="nivel" class="form-select">
                        <option value="">Todos los niveles</option>
                        <?php foreach ($filterOptions['niveles'] as $niv): ?>
                            <option value="<?= htmlspecialchars($niv) ?>" <?= ($filters['nivel'] ?? '') === $niv ? 'selected' : '' ?>>
                                <?= htmlspecialchars($niv) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro: Grado Escolar -->
                <div class="filter-item">
                    <label for="filter-grado" class="filter-label">Grado Escolar</label>
                    <select id="filter-grado" name="grado" class="form-select">
                        <option value="">Todos los grados</option>
                        <?php foreach ($filterOptions['grados'] as $grd): ?>
                            <option value="<?= htmlspecialchars($grd) ?>" <?= ($filters['grado'] ?? '') === $grd ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grd) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro: Grupo -->
                <div class="filter-item">
                    <label for="filter-grupo" class="filter-label">Grupo</label>
                    <select id="filter-grupo" name="grupo" class="form-select">
                        <option value="">Todos los grupos</option>
                        <?php foreach ($filterOptions['grupos'] as $grp): ?>
                            <option value="<?= htmlspecialchars($grp) ?>" <?= ($filters['grupo'] ?? '') === $grp ? 'selected' : '' ?>>
                                Grupo <?= htmlspecialchars($grp) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro: Estado del Alumno -->
                <div class="filter-item">
                    <label for="filter-estado" class="filter-label">Estatus</label>
                    <select id="filter-estado" name="estado" class="form-select">
                        <option value="">Cualquier estatus</option>
                        <?php foreach ($filterOptions['estados'] as $est): ?>
                            <option value="<?= htmlspecialchars($est) ?>" <?= ($filters['estado'] ?? '') === $est ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($est)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botones de Acción de Filtro -->
                <div class="filter-item filter-item--actions">
                    <button type="submit" class="btn-filter-submit" id="btn-filter-submit">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <span>Filtrar</span>
                    </button>
                    <a href="<?= Router::url('alumnos/ver', 'private') ?>" class="btn-filter-reset" title="Restablecer todos los filtros">
                        Limpiar
                    </a>
                </div>

            </div>
        </form>
    </section>

    <!-- 4. Tabla de Alumnos Ordenada Lógicamente -->
    <section class="alumnos-table-card">
        <div class="table-toolbar">
            <div class="table-results-counter">
                Mostrando <strong id="visible-count"><?= count($alumnos) ?></strong> alumno<?= count($alumnos) !== 1 ? 's' : '' ?> ordenado<?= count($alumnos) !== 1 ? 's' : '' ?> por <strong>Grado y Grupo</strong>
            </div>
            <div class="table-legend">
                <span class="legend-badge legend-badge--preescolar">Preescolar</span>
                <span class="legend-badge legend-badge--primaria">Primaria</span>
                <span class="legend-badge legend-badge--secundaria">Secundaria</span>
                <span class="legend-badge legend-badge--bachillerato">Bachillerato</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="alumnos-table" id="tabla-alumnos">
                <thead>
                    <tr>
                        <th class="th-matricula">Matrícula</th>
                        <th class="th-alumno">Estudiante / CURP</th>
                        <th class="th-academico">Grado y Grupo</th>
                        <th class="th-tutor">Tutor / Contacto</th>
                        <th class="th-estado">Estatus</th>
                        <th class="th-acciones text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="alumnos-tbody">
                    <?php if (empty($alumnos)): ?>
                        <tr class="tr-empty-state">
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </div>
                                    <h3>No se encontraron alumnos</h3>
                                    <p>No existen registros que coincidan con los criterios de búsqueda o filtros seleccionados.</p>
                                    <?php if (!empty($filters['search']) || !empty($filters['nivel']) || !empty($filters['grado']) || !empty($filters['grupo']) || !empty($filters['estado'])): ?>
                                        <a href="<?= Router::url('alumnos/ver', 'private') ?>" class="btn-secondary" style="margin-top: 12px;">Restablecer Filtros</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $currentGroupHeading = null;
                        foreach ($alumnos as $alumno): 
                            $nivel = htmlspecialchars($alumno['nivel_educativo'] ?? '');
                            $grado = htmlspecialchars($alumno['grado'] ?? '');
                            $grupo = htmlspecialchars($alumno['grupo'] ?? 'A');
                            $groupHeadingKey = "{$nivel} • {$grado} - Grupo {$grupo}";

                            // Badge de nivel educativo
                            $nivelSlug = strtolower(preg_replace('/[^a-zA-Z]/', '', $alumno['nivel_educativo'] ?? ''));
                            $estadoSlug = strtolower(trim($alumno['estado_alumno'] ?? 'inscrito'));

                            // Iniciales para el avatar
                            $iniciales = strtoupper(
                                mb_substr($alumno['nombre'] ?? '', 0, 1) . 
                                mb_substr($alumno['primer_apellido'] ?? '', 0, 1)
                            );
                        ?>
                            <!-- Separador visual de grupo para lectura jerárquica -->
                            <?php if ($currentGroupHeading !== $groupHeadingKey): ?>
                                <?php $currentGroupHeading = $groupHeadingKey; ?>
                                <tr class="tr-group-header">
                                    <td colspan="6">
                                        <div class="group-header-chip group-header-chip--<?= $nivelSlug ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                            </svg>
                                            <span><?= $currentGroupHeading ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr class="alumno-row" data-id="<?= (int)$alumno['id'] ?>" data-curp="<?= htmlspecialchars($alumno['curp']) ?>">
                                <!-- Matrícula -->
                                <td class="td-matricula">
                                    <div class="matricula-tag" title="Clic para copiar matrícula" data-copy="<?= htmlspecialchars($alumno['matricula'] ?? 'S/M') ?>">
                                        <code><?= htmlspecialchars($alumno['matricula'] ?? 'S/M') ?></code>
                                        <svg class="copy-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </div>
                                    <div class="meta-fecha" title="Fecha de registro">
                                        <?= !empty($alumno['created_at']) ? date('d/m/Y', strtotime($alumno['created_at'])) : '—' ?>
                                    </div>
                                </td>

                                <!-- Alumno (Nombre completo y CURP) -->
                                <td class="td-alumno">
                                    <div class="alumno-profile-item">
                                        <div class="alumno-avatar alumno-avatar--<?= $nivelSlug ?>">
                                            <?= $iniciales ?>
                                        </div>
                                        <div class="alumno-names">
                                            <div class="nombre-principal">
                                                <strong><?= htmlspecialchars($alumno['primer_apellido'] . ' ' . $alumno['segundo_apellido']) ?></strong>, 
                                                <?= htmlspecialchars($alumno['nombre']) ?>
                                            </div>
                                            <div class="curp-tag" title="Clic para copiar CURP" data-copy="<?= htmlspecialchars($alumno['curp']) ?>">
                                                <span>CURP: <?= htmlspecialchars($alumno['curp']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Grado y Grupo -->
                                <td class="td-academico">
                                    <div class="badge-academico badge-academico--<?= $nivelSlug ?>">
                                        <span class="badge-nivel"><?= $nivel ?></span>
                                        <span class="badge-grado-grupo"><?= $grado ?> "<?= $grupo ?>"</span>
                                    </div>
                                </td>

                                <!-- Tutor / Contacto -->
                                <td class="td-tutor">
                                    <?php if (!empty($alumno['tutor_nombre'])): ?>
                                        <div class="tutor-info">
                                            <span class="tutor-name"><?= htmlspecialchars($alumno['tutor_nombre']) ?></span>
                                            <span class="tutor-relacion">(<?= htmlspecialchars($alumno['tutor_parentesco'] ?? 'Tutor') ?>)</span>
                                        </div>
                                        <div class="tutor-contact">
                                            <?php if (!empty($alumno['tutor_telefono'])): ?>
                                                <a href="tel:<?= htmlspecialchars($alumno['tutor_telefono']) ?>" class="contact-link">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                                    </svg>
                                                    <span><?= htmlspecialchars($alumno['tutor_telefono']) ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Sin tutor registrado</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Estatus -->
                                <td class="td-estado">
                                    <span class="status-pill status-pill--<?= $estadoSlug ?>">
                                        <span class="status-dot"></span>
                                        <span><?= htmlspecialchars(ucfirst($estadoSlug)) ?></span>
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="td-acciones text-center">
                                    <button 
                                        type="button" 
                                        class="btn-action-view btn-open-detail" 
                                        data-id="<?= (int)$alumno['id'] ?>"
                                        title="Ver Ficha y Expediente Completo"
                                    >
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span>Ficha</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<!-- 5. MODAL DE EXPEDIENTE / FICHA DEL ALUMNO -->
<div class="alumno-modal-backdrop" id="alumno-modal-backdrop" style="display: none;" aria-hidden="true">
    <div class="alumno-modal" id="alumno-modal" role="dialog" aria-modal="true" aria-labelledby="modal-alumno-title">
        
        <header class="alumno-modal__header">
            <div class="modal-profile">
                <div class="modal-avatar" id="modal-avatar">--</div>
                <div>
                    <h3 class="modal-title" id="modal-alumno-title">Expediente del Alumno</h3>
                    <div class="modal-meta" id="modal-alumno-meta">Cargando información...</div>
                </div>
            </div>
            <button type="button" class="btn-modal-close" id="btn-modal-close" aria-label="Cerrar ventana">&times;</button>
        </header>

        <!-- Navegación de Pestañas del Expediente -->
        <nav class="modal-tabs" aria-label="Pestañas del expediente">
            <button type="button" class="modal-tab is-active" data-tab="tab-personal">Datos Personales</button>
            <button type="button" class="modal-tab" data-tab="tab-tutores">Tutores & Familia</button>
            <button type="button" class="modal-tab" data-tab="tab-medico">Salud & Emergencia</button>
            <button type="button" class="modal-tab" data-tab="tab-plataforma">Plataforma Escolar</button>
            <button type="button" class="modal-tab" data-tab="tab-facturacion">Facturación</button>
        </nav>

        <div class="alumno-modal__body" id="modal-body-content">
            <div class="modal-loading-spinner" id="modal-spinner">
                <div class="spinner-ring"></div>
                <span>Cargando expediente escolar...</span>
            </div>

            <div class="modal-tab-content" id="tab-personal" style="display: none;"></div>
            <div class="modal-tab-content" id="tab-tutores" style="display: none;"></div>
            <div class="modal-tab-content" id="tab-medico" style="display: none;"></div>
            <div class="modal-tab-content" id="tab-plataforma" style="display: none;"></div>
            <div class="modal-tab-content" id="tab-facturacion" style="display: none;"></div>
        </div>

        <footer class="alumno-modal__footer">
            <button type="button" class="btn-secondary" id="btn-modal-print">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Imprimir Ficha</span>
            </button>
            <button type="button" class="btn-primary" id="btn-modal-close-footer">Cerrar</button>
        </footer>

    </div>
</div>

<!-- Toast Container para Copia de CURP / Matrícula -->
<div class="alumnos-toast" id="alumnos-toast" style="display: none;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>
    <span id="toast-text">Copiado al portapapeles</span>
</div>
