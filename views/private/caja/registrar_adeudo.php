<?php
/**
 * Vista: Registrar Cargo (Servicios CARE: Transporte, Lunch, Estancia)
 * Grupo:     Caja
 * Archivo:   registrar_adeudo.php
 * Ruta:      views/private/caja/registrar_adeudo.php
 * Assets:    assets/css/registrar_cargo.css
 *            assets/js/registrar_cargo.js
 */

// Carga robusta del controlador institucional
if (!class_exists('controllers\Cea_CargosController')) {
    if (file_exists(__DIR__ . '/../../../controllers/Cea_CargosController.php')) {
        require_once __DIR__ . '/../../../controllers/Cea_CargosController.php';
    } elseif (file_exists(__DIR__ . '/../../../Core/controllers/Cea_CargosController.php')) {
        require_once __DIR__ . '/../../../Core/controllers/Cea_CargosController.php';
    }
}

use controllers\Cea_CargosController;
use Core\Router;

// Procesar lógica del controlador (incluyendo peticiones AJAX y envíos POST)
$data = Cea_CargosController::handleRegister();

$serviciosCare    = $data['serviciosCare']    ?? [];
$alumnosRecientes = $data['alumnosRecientes'] ?? [];
$ultimosCargos    = $data['ultimosCargos']    ?? [];
$errorMsg         = $data['errorMsg']         ?? null;
$successMsg       = $data['successMsg']       ?? null;
$cargoCreado      = $data['cargoCreado']      ?? null;
$fechaHoy         = $data['fechaHoy']         ?? date('Y-m-d');
$baseUrl          = $baseUrl ?? rtrim(Router::url('', 'public'), '/');
?>

<!-- Estilos específicos del módulo de cargos -->
<link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/registrar_cargo.css">

<div class="cargos-container">

    <!-- 1. Encabezado Institucional de la Vista -->
    <header class="cargos-header">
        <div class="cargos-header__info">
            <nav class="cargos-breadcrumb" aria-label="Migas de pan">
                <span class="cargos-breadcrumb__item">Módulo Financiero</span>
                <span class="cargos-breadcrumb__separator">/</span>
                <span class="cargos-breadcrumb__item">Caja</span>
                <span class="cargos-breadcrumb__separator">/</span>
                <span class="cargos-breadcrumb__active">Registrar Adeudo CARE</span>
            </nav>
            <h1 class="cargos-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: #2563eb; flex-shrink: 0;">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                <span>Registrar Adeudo Escolar</span>
                <span class="cargos-badge-care">Servicios CARE</span>
            </h1>
            <p class="cargos-subtitle">
                Genera cargos individuales y adeudos programados para los servicios de transporte institucional, comedor escolar y estancia infantil.
            </p>
        </div>

        <div class="cargos-header__actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="<?= htmlspecialchars(Router::url('caja/registrar_pago', 'private')) ?>" class="btn-secondary" title="Ir a Ventanilla de Pagos">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                <span>Ventanilla de Pagos</span>
            </a>
            <a href="<?= htmlspecialchars(Router::url('dashboard')) ?>" class="btn-secondary" title="Volver al panel principal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Panel Principal</span>
            </a>
        </div>
    </header>

    <!-- 2. Alertas del Sistema -->
    <div id="cargos-alert-container">
        <?php if (!empty($successMsg)): ?>
            <div class="cargos-alert cargos-alert--success" role="alert">
                <div class="cargos-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="cargos-alert__message">
                    <?= htmlspecialchars($successMsg) ?>
                </div>
                <button type="button" class="cargos-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="cargos-alert cargos-alert--danger" role="alert">
                <div class="cargos-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="cargos-alert__message">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
                <button type="button" class="cargos-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Formulario Principal de Registro de Cargo (Estructura Modular ASRS) -->
    <form id="form-registrar-cargo" action="" method="POST" class="cargos-form" novalidate>

        <!-- ======================================================== -->
        <!-- SECCIÓN 1: SELECCIÓN E IDENTIFICACIÓN DEL ALUMNO        -->
        <!-- ======================================================== -->
        <div class="asrs-form-container">
            <div class="asrs-section-header">
                <div class="asrs-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>1. Identificación y Selección del Alumno Titular</span>
                </div>
                <span class="asrs-section-toggle-icon">▼</span>
            </div>

            <div class="asrs-section-body">
                <div class="asrs-form-group">
                    <label for="input-alumno-search" class="asrs-form-label">
                        Alumno Titular <span class="required-mark">*</span>
                    </label>

                    <!-- Buscador en tiempo real -->
                    <div class="alumno-search-box">
                        <div class="input-icon-wrap">
                            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input 
                                type="text" 
                                id="input-alumno-search" 
                                class="asrs-form-control form-control" 
                                placeholder="Escribe el nombre, apellidos o CURP del alumno..." 
                                autocomplete="off"
                            >
                            <span class="btn-spinner" id="alumno-search-spinner" style="display: none; position: absolute; right: 14px;"></span>
                        </div>

                        <!-- Dropdown flotante con resultados -->
                        <ul class="alumno-search-results" id="alumno-search-results"></ul>
                    </div>

                    <!-- Ficha visual del alumno una vez seleccionado -->
                    <div class="alumno-selected-badge" id="alumno-selected-badge" style="display: none;">
                        <div class="alumno-selected-profile">
                            <div class="alumno-selected-avatar" id="alumno-selected-avatar">AL</div>
                            <div>
                                <h4 class="alumno-selected-name" id="alumno-selected-name">Nombre Alumno</h4>
                                <div class="alumno-selected-details" id="alumno-selected-details">
                                    <span class="tag-badge">Nivel</span>
                                    <span class="tag-badge">Grado</span>
                                    <span>CURP</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-change-alumno" id="btn-change-alumno">Cambiar Alumno</button>
                    </div>

                    <!-- Ficha institucional contextual (.asrs-collapsible-info) -->
                    <div class="asrs-collapsible-info is-expanded" id="adeudo-alumno-info" style="margin-top: 14px;">
                        <div style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 9px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>Los adeudos registrados se sincronizan automáticamente con la cuenta corriente del alumno y la ventanilla de cobro en Caja.</span>
                        </div>
                    </div>

                    <!-- Input oculto para el envío del ID -->
                    <input type="hidden" name="alumno_id" id="input-alumno-id" value="">

                    <!-- Selector Fallback para accesibilidad o sin JS -->
                    <noscript>
                        <select name="alumno_id" id="select-alumno-fallback" class="asrs-form-control form-select" style="margin-top: 8px;">
                            <option value="">-- Selecciona un alumno de la lista --</option>
                            <?php foreach ($alumnosRecientes as $al): ?>
                                <option value="<?= (int)$al['id'] ?>">
                                    <?= htmlspecialchars("{$al['primer_apellido']} {$al['segundo_apellido']} {$al['nombre']}") ?> (<?= htmlspecialchars($al['curp']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </noscript>

                    <small class="form-hint">Escribe al menos 2 letras para desplegar sugerencias de alumnos inscritos.</small>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECCIÓN 2: CONCEPTO DEL SERVICIO Y MONTO CARE           -->
        <!-- ======================================================== -->
        <div class="asrs-form-container">
            <div class="asrs-section-header">
                <div class="asrs-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                        <line x1="6" y1="15" x2="10" y2="15"></line>
                    </svg>
                    <span>2. Concepto del Servicio e Importe a Devengar</span>
                </div>
                <span class="asrs-section-toggle-icon">▼</span>
            </div>

            <div class="asrs-section-body">
                <!-- Concepto del Servicio CARE -->
                <div class="asrs-form-group">
                    <label class="asrs-form-label">
                        Concepto de Servicio CARE <span class="required-mark">*</span>
                    </label>

                    <!-- Tarjetas interactivas de servicio -->
                    <div class="care-services-grid">
                        <!-- 1. Transporte -->
                        <div class="care-service-card" data-concept="Transporte" data-price="1500.00">
                            <div class="care-service-card__header">
                                <div class="care-service-icon care-service-icon--transporte">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="15" rx="3"></rect>
                                        <line x1="3" y1="9" x2="21" y2="9"></line>
                                        <circle cx="7.5" cy="14.5" r="1.5"></circle>
                                        <circle cx="16.5" cy="14.5" r="1.5"></circle>
                                    </svg>
                                </div>
                                <div class="care-service-check">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="care-service-name">Transporte Escolar</h3>
                            <p class="care-service-desc">Traslado en unidades escolares con monitoreo y rutas asignadas.</p>
                            <span class="care-service-price">Sugerido: $1,500.00</span>
                        </div>

                        <!-- 2. Lunch / Comedor -->
                        <div class="care-service-card" data-concept="Lunch" data-price="1200.00">
                            <div class="care-service-card__header">
                                <div class="care-service-icon care-service-icon--lunch">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                        <line x1="6" y1="1" x2="6" y2="4"></line>
                                        <line x1="10" y1="1" x2="10" y2="4"></line>
                                        <line x1="14" y1="1" x2="14" y2="4"></line>
                                    </svg>
                                </div>
                                <div class="care-service-check">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="care-service-name">Lunch / Comedor</h3>
                            <p class="care-service-desc">Alimentación completa supervisada por nutriólogos institucionales.</p>
                            <span class="care-service-price">Sugerido: $1,200.00</span>
                        </div>

                        <!-- 3. Estancia Infantil -->
                        <div class="care-service-card" data-concept="Estancia" data-price="800.00">
                            <div class="care-service-card__header">
                                <div class="care-service-icon care-service-icon--estancia">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <div class="care-service-check">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="care-service-name">Estancia Infantil</h3>
                            <p class="care-service-desc">Cuidado vespertino, apoyo escolar y actividades recreativas en plantel.</p>
                            <span class="care-service-price">Sugerido: $800.00</span>
                        </div>
                    </div>

                    <!-- Input o Select de Concepto -->
                    <input type="hidden" name="concepto" id="input-concepto" value="" required>

                    <!-- Selector opcional de contingencia -->
                    <noscript>
                        <select name="concepto" class="asrs-form-control form-select" required>
                            <option value="">-- Selecciona el servicio --</option>
                            <option value="Transporte">Transporte Escolar</option>
                            <option value="Lunch">Lunch / Comedor</option>
                            <option value="Estancia">Estancia Infantil</option>
                        </select>
                    </noscript>
                </div>

                <!-- Monto del cargo -->
                <div class="asrs-form-group">
                    <label for="input-monto" class="asrs-form-label">
                        Monto del Adeudo (MXN) <span class="required-mark">*</span>
                    </label>
                    <div class="monto-input-wrapper">
                        <span class="monto-currency-prefix">$</span>
                        <input 
                            type="number" 
                            id="input-monto" 
                            name="monto" 
                            class="asrs-form-control form-control form-control--monto" 
                            placeholder="0.00" 
                            step="0.01" 
                            min="0.01" 
                            required 
                            autocomplete="off"
                        >
                    </div>
                    <small class="form-hint">Puedes modificar la tarifa sugerida según el periodo o plan acordado con el padre de familia.</small>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECCIÓN 3: PROGRAMACIÓN DE FECHAS Y CONFIRMACIÓN        -->
        <!-- ======================================================== -->
        <div class="asrs-form-container">
            <div class="asrs-section-header">
                <div class="asrs-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>3. Programación de Fechas y Confirmación del Adeudo</span>
                </div>
                <span class="asrs-section-toggle-icon">▼</span>
            </div>

            <div class="asrs-section-body">
                <div class="asrs-form-row-2">
                    <!-- Fecha de Solicitud -->
                    <div class="asrs-form-group">
                        <label for="input-fecha-solicitud" class="asrs-form-label">
                            Fecha de Solicitud <span class="required-mark">*</span>
                        </label>
                        <div class="date-input-wrap">
                            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <input 
                                type="date" 
                                id="input-fecha-solicitud" 
                                name="fecha_solicitud" 
                                class="asrs-form-control form-control" 
                                value="<?= htmlspecialchars($fechaHoy) ?>" 
                                required
                            >
                        </div>
                        <small class="form-hint">Fecha en que se registra y solicita formalmente el servicio.</small>
                    </div>

                    <!-- Fecha de Servicio -->
                    <div class="asrs-form-group">
                        <label for="input-fecha-servicio" class="asrs-form-label">
                            Fecha del Servicio a Brindar <span class="required-mark">*</span>
                        </label>
                        <div class="date-input-wrap">
                            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 14 14"></polyline>
                            </svg>
                            <input 
                                type="date" 
                                id="input-fecha-servicio" 
                                name="fecha_servicio" 
                                class="asrs-form-control form-control" 
                                value="<?= htmlspecialchars($fechaHoy) ?>" 
                                required
                            >
                        </div>
                        <small class="form-hint">Fecha programada en que se ejecutará o brindará la prestación.</small>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="cargos-form-actions">
                    <button type="button" class="btn-secondary" id="btn-clear-cargo">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"></polyline>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                        <span>Limpiar</span>
                    </button>

                    <button type="submit" class="btn-primary" id="btn-submit-cargo" name="btn_submit_cargo" value="1">
                        <span class="btn-spinner" id="btn-spinner" style="display: none;"></span>
                        <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span id="btn-submit-text">Registrar Adeudo</span>
                    </button>
                </div>
            </div>
        </div>

    </form>

    <!-- ======================================================== -->
    <!-- SECCIÓN 4: TABLA DE HISTORIAL DE ÚLTIMOS CARGOS          -->
    <!-- ======================================================== -->
    <div class="asrs-form-container">
        <div class="asrs-section-header">
            <div class="asrs-section-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>4. Últimos Adeudos Registrados en el Sistema</span>
            </div>
            <span class="asrs-section-toggle-icon">▼</span>
        </div>

        <div class="asrs-section-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="cargos-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Alumno</th>
                            <th>Concepto CARE</th>
                            <th>Monto</th>
                            <th>Fecha Solicitud</th>
                            <th>Fecha Servicio</th>
                            <th>Estatus</th>
                            <th>Registrado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-ultimos-cargos-body">
                        <?php if (empty($ultimosCargos)): ?>
                            <tr id="cargos-empty-row">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                            <line x1="2" y1="10" x2="22" y2="10"></line>
                                        </svg>
                                        <p>Aún no se han registrado adeudos para servicios CARE.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimosCargos as $c): ?>
                                <?php 
                                    $cLower = strtolower($c['concepto']);
                                    $badgeClass = 'concept-badge--otro';
                                    if (str_contains($cLower, 'transporte')) $badgeClass = 'concept-badge--transporte';
                                    elseif (str_contains($cLower, 'lunch') || str_contains($cLower, 'comedor')) $badgeClass = 'concept-badge--lunch';
                                    elseif (str_contains($cLower, 'estancia')) $badgeClass = 'concept-badge--estancia';
                                ?>
                                <tr>
                                    <td><strong>#<?= (int)$c['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($c['alumno_nombre']) ?></strong><br>
                                        <small style="color: #64748b;"><?= htmlspecialchars($c['curp']) ?> &bull; <?= htmlspecialchars("{$c['grado']} {$c['grupo']}") ?> (<?= htmlspecialchars($c['nivel_educativo']) ?>)</small>
                                    </td>
                                    <td>
                                        <span class="concept-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($c['concepto']) ?>
                                        </span>
                                    </td>
                                    <td><strong style="color: #0f172a;">$<?= number_format((float)$c['monto'], 2) ?></strong></td>
                                    <td><span style="font-size: 12px; color: #475569;"><?= htmlspecialchars($c['fecha_solicitud']) ?></span></td>
                                    <td><span style="font-size: 12px; color: #475569;"><?= htmlspecialchars($c['fecha_servicio']) ?></span></td>
                                    <td>
                                        <span class="status-badge status-badge--<?= htmlspecialchars($c['estatus']) ?>">
                                            <?= htmlspecialchars(ucfirst($c['estatus'])) ?>
                                        </span>
                                    </td>
                                    <td><small style="color: #94a3b8;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Contenedor Toast Flotante -->
<div class="cargos-toast-container" id="cargos-toast-container"></div>

<!-- Script interactivo del módulo de cargos -->
<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/registrar_cargo.js"></script>
