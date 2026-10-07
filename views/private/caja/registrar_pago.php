<?php
/**
 * Vista: Capturar Pago
 * Grupo:     Caja
 * Archivo:   capturar.php
 * Ruta:      views/private/caja/capturar.php
 * Assets:    assets/css/caja/capturar.css
 *            assets/js/caja/capturar.js
 */

use Core\controllers\CajaController;
use Core\Router;

// [Módulo CARE] Endpoint aislado de cargos pendientes (solo responde si action=get_cargos_pendientes)
require_once __DIR__ . '/../../../Core/controllers/Cea_CargosController.php';
require_once __DIR__ . '/../../../controllers/Cea_CargosController.php';
\controllers\Cea_CargosController::handleCajaAjax();

// Ejecutar lógica de procesamiento del controlador y validación RBAC
$data = CajaController::handleCapture();

$conceptos       = $data['conceptos'] ?? [];
$formasPago      = $data['formasPago'] ?? [];
$errorMsg        = $data['error'] ?? null;
$successMsg      = $data['success'] ?? null;
$pagoRegistrado  = $data['pagoRegistrado'] ?? null;
$cajero          = $data['cajero'] ?? [];
$fechaActual     = $data['fechaActual'] ?? date('Y-m-d');
$horaActual      = $data['horaActual'] ?? date('H:i');
$baseUrl         = $baseUrl ?? rtrim(Router::url('', 'public'), '/');
?>

<div class="caja-modulo-container">

    <!-- 1. Encabezado Institucional del Módulo -->
    <header class="caja-header">
        <div class="caja-header__info">
            <nav class="caja-breadcrumb" aria-label="Migas de pan">
                <span class="caja-breadcrumb__item">Módulo Financiero</span>
                <span class="caja-breadcrumb__separator">/</span>
                <span class="caja-breadcrumb__item">Caja</span>
                <span class="caja-breadcrumb__separator">/</span>
                <span class="caja-breadcrumb__active">Capturar Pago</span>
            </nav>
            <h1 class="caja-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="caja-title__icon">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Captura y Emisión de Pagos Escolares
            </h1>
            <p class="caja-subtitle">
                Recepción de cobros, emisión de comprobantes oficiales con folio único y consulta de ficha institucional de tutores y facturación.
            </p>
        </div>

        <div class="caja-header__actions">
            <div class="cajero-badge" title="Cajero Activo">
                <span class="cajero-badge__dot"></span>
                <div class="cajero-badge__text">
                    <span class="cajero-badge__label">Cajero en Turno</span>
                    <strong class="cajero-badge__name"><?= htmlspecialchars($cajero['name'] ?? 'Caja Institucional') ?></strong>
                </div>
            </div>
            <a href="<?= htmlspecialchars(Router::url('alumnos/ver', 'private')) ?>" class="btn-secondary" title="Ver Directorio de Alumnos">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Directorio Alumnos</span>
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

    <!-- 2. Alertas del Servidor (Render tradicional) -->
    <div id="caja-alert-container">
        <?php if (!empty($successMsg)): ?>
            <div class="caja-alert caja-alert--success" role="alert">
                <div class="caja-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="caja-alert__content">
                    <div class="caja-alert__title">¡Transacción Exitosa!</div>
                    <div class="caja-alert__msg"><?= htmlspecialchars($successMsg) ?></div>
                </div>
                <button type="button" class="caja-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="caja-alert caja-alert--danger" role="alert">
                <div class="caja-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="caja-alert__content">
                    <div class="caja-alert__title">Error en la transacción</div>
                    <div class="caja-alert__msg"><?= htmlspecialchars($errorMsg) ?></div>
                </div>
                <button type="button" class="caja-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Formulario Principal de Cobro (Template Estándar Centralizado ASRS) -->
    <form id="form-capturar-pago" method="POST" action="<?= htmlspecialchars(Router::url('caja/registrar_pago', 'private')) ?>" novalidate>
        <input type="hidden" name="submit_pago" value="1">
        <input type="hidden" name="alumno_id" id="input_alumno_id" value="">
        <input type="hidden" name="concepto_id" id="input_concepto_id" value="">

        <!-- ======================================================== -->
        <!-- SECCIÓN 1: BÚSQUEDA E IDENTIFICACIÓN DEL ALUMNO         -->
        <!-- ======================================================== -->
        <div class="asrs-form-container">
            <div class="asrs-section-header">
                <div class="asrs-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>1. Identificación y Búsqueda del Alumno</span>
                </div>
                <span class="asrs-section-toggle-icon">▼</span>
            </div>

            <div class="asrs-section-body">
                <!-- Input con Búsqueda en Vivo -->
                <div class="alumno-search-box" id="alumno-search-box">
                    <div class="search-input-wrapper">
                        <span class="search-input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            id="alumno_search_input" 
                            class="search-input" 
                            placeholder="Escriba el apellido paterno (ej. Gómez, González, Rodríguez)..." 
                            autocomplete="off"
                            spellcheck="false"
                        >
                        <button type="button" id="btn-clear-search" class="search-clear-btn" title="Limpiar búsqueda" style="display: none;">
                            &times;
                        </button>
                        <span id="search-spinner" class="search-spinner" style="display: none;"></span>
                    </div>

                    <!-- Dropdown Flotante de Resultados -->
                    <div id="search-results-dropdown" class="search-results-dropdown" style="display: none;">
                        <ul id="search-results-list" class="search-results-list"></ul>
                    </div>
                </div>

                <!-- Tarjeta de Alumno Seleccionado (Inicialmente oculta) -->
                <div id="alumno-selected-card" class="alumno-selected-card" style="display: none;">
                    <div class="alumno-selected-avatar">
                        <span id="sel-avatar-initials">--</span>
                    </div>
                    <div class="alumno-selected-info">
                        <div class="alumno-selected-top">
                            <span class="badge-tag badge-tag--success">Alumno Seleccionado</span>
                            <span class="alumno-selected-id" id="sel-alumno-id-label">ID #--</span>
                        </div>
                        <h3 class="alumno-selected-name" id="sel-alumno-nombre">--</h3>
                        <div class="alumno-selected-meta">
                            <span><strong>CURP:</strong> <code id="sel-alumno-curp">--</code></span>
                            <span class="meta-dot">&bull;</span>
                            <span><strong>Nivel:</strong> <span id="sel-alumno-nivel">--</span></span>
                            <span class="meta-dot">&bull;</span>
                            <span><strong>Grado y Grupo:</strong> <span id="sel-alumno-grado-grupo">--</span></span>
                        </div>
                    </div>
                    <!-- [Módulo CARE] Botón / Insignia Persistente de Adeudos Eventuales -->
                    <button type="button" id="btn-reabrir-cargos-care" class="cea-badge-cargos-pendientes" style="display: none;" title="Ver y gestionar cargos CARE pendientes de cobro">
                        <span class="cea-badge-cargos-pendientes__pulse" aria-hidden="true"></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="cea-badge-cargos-pendientes__icon">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <span class="cea-badge-cargos-pendientes__content">
                            <span class="cea-badge-cargos-pendientes__title" id="cea-badge-cargos-title">Adeudos CARE (0)</span>
                            <span class="cea-badge-cargos-pendientes__amount" id="cea-badge-cargos-amount">$0.00 MXN</span>
                        </span>
                        <span class="cea-badge-cargos-pendientes__action">
                            <span>Ver cargos</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </span>
                    </button>

                    <button type="button" id="btn-change-alumno" class="btn-change-alumno" title="Buscar otro alumno">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span>Cambiar</span>
                    </button>
                </div>

                <div id="alumno-validation-msg" class="field-error" style="display: none;">
                    * Debe seleccionar un alumno para procesar el pago.
                </div>

                <!-- ======================================================== -->
                <!-- FICHA INSTITUCIONAL INTEGRADA (BLOQUE CONTEXTUAL COLAPSABLE) -->
                <!-- ======================================================== -->
                <div class="asrs-collapsible-info panel-inteligente" id="panel-inteligente">
                    
                    <!-- Encabezado del Panel Institucional / Trigger de Alternancia -->
                    <div class="panel-header panel-header--toggle" id="panel-inteligente-toggle" role="button" tabindex="0" aria-expanded="false" aria-controls="panel-inteligente" title="Mostrar u ocultar la ficha institucional">
                        <div class="panel-header__icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="panel-header__text">
                            <h3 class="panel-header__title">Ficha Institucional</h3>
                            <p class="panel-header__desc">Expediente del alumno, tutores autorizados y facturación sin nulos</p>
                        </div>
                        <svg class="panel-header__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>

                    <!-- Estado Inicial: Ningún Alumno Seleccionado (Compacto) -->
                    <div id="panel-empty-state" class="panel-empty">
                        <div class="panel-empty__graphic">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </div>
                        <div class="panel-empty__content">
                            <h4 class="panel-empty__heading">Sin Alumno Seleccionado</h4>
                            <p class="panel-empty__text">
                                Busque y seleccione un alumno en el paso 1 para desplegar aquí su expediente, tutores verificados y régimen fiscal en tiempo real sin campos vacíos.
                            </p>
                        </div>
                    </div>

                    <!-- Estado Cargando: Spinner del Panel Lateral -->
                    <div id="panel-loading-state" class="panel-loading" style="display: none;">
                        <div class="panel-spinner"></div>
                        <span>Cargando expediente institucional...</span>
                    </div>

                    <!-- Estado Activo: Información Limpia Sin Nulos (Despliegue Horizontal) -->
                    <div id="panel-content-state" class="panel-content" style="display: none;">
                        
                        <!-- Tarjeta: Datos Académicos (Acordeón Desplegable Exclusivo) -->
                        <div class="panel-card panel-card--collapsible is-expanded" id="panel-card-alumno">
                            <button type="button" 
                                    class="panel-card__header-btn" 
                                    id="btn-toggle-datos-alumno" 
                                    aria-expanded="true" 
                                    aria-controls="panel-body-alumno"
                                    title="Alternar visibilidad de los datos del alumno">
                                <div class="panel-card__title-content">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                    </svg>
                                    <span>Datos del Alumno</span>
                                </div>
                                <div class="panel-card__chevron-wrap">
                                    <svg class="panel-card__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                            </button>
                            <div class="panel-card__collapse" id="panel-body-alumno">
                                <div class="panel-card__body">
                                    <div class="panel-line" id="line-nombre-completo">
                                        <span class="panel-label">Nombre Completo:</span>
                                        <span class="panel-value font-medium" id="panel-val-nombre">--</span>
                                    </div>
                                    <div class="panel-line" id="line-curp">
                                        <span class="panel-label">CURP:</span>
                                        <span class="panel-value" id="panel-val-curp">--</span>
                                    </div>
                                    <div class="panel-line" id="line-nivel">
                                        <span class="panel-label">Nivel Educativo:</span>
                                        <span class="panel-value" id="panel-val-nivel">--</span>
                                    </div>
                                    <div class="panel-line" id="line-grado-grupo">
                                        <span class="panel-label">Grado y Grupo:</span>
                                        <span class="panel-value" id="panel-val-grado-grupo">--</span>
                                    </div>
                                    <div class="panel-line" id="line-matricula" style="display: none;">
                                        <span class="panel-label">Matrícula:</span>
                                        <span class="panel-value" id="panel-val-matricula">--</span>
                                    </div>
                                    <div class="panel-line" id="line-telefono-alumno" style="display: none;">
                                        <span class="panel-label">Teléfono:</span>
                                        <span class="panel-value" id="panel-val-telefono-alumno">--</span>
                                    </div>
                                    <div class="panel-line" id="line-email-alumno" style="display: none;">
                                        <span class="panel-label">Correo:</span>
                                        <span class="panel-value" id="panel-val-email-alumno">--</span>
                                    </div>
                                    <div class="panel-line" id="line-estado-alumno">
                                        <span class="panel-label">Estado:</span>
                                        <span class="panel-badge badge-tag--success" id="panel-val-estado">Activo</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Tutores Registrados (Acordeón Desplegable) -->
                        <div class="panel-card panel-card--collapsible is-expanded" id="panel-card-tutores">
                            <button type="button" 
                                    class="panel-card__header-btn" 
                                    id="btn-toggle-tutores" 
                                    aria-expanded="true" 
                                    aria-controls="panel-body-tutores"
                                    title="Alternar visibilidad de tutores y contacto">
                                <div class="panel-card__title-content">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <span>Tutores y Contacto</span>
                                </div>
                                <div class="panel-card__chevron-wrap">
                                    <svg class="panel-card__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                            </button>
                            <div class="panel-card__collapse" id="panel-body-tutores">
                                <div class="panel-card__body" id="panel-tutores-container">
                                    <!-- Los tutores se inyectan dinámicamente mediante JS omitiendo cualquier campo nulo -->
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Facturación / Régimen Fiscal (Acordeón Desplegable) -->
                        <div class="panel-card panel-card--collapsible is-expanded" id="panel-card-facturacion">
                            <button type="button" 
                                    class="panel-card__header-btn" 
                                    id="btn-toggle-facturacion" 
                                    aria-expanded="true" 
                                    aria-controls="panel-body-facturacion"
                                    title="Alternar visibilidad de datos de facturación">
                                <div class="panel-card__title-content">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    <span>Datos de Facturación</span>
                                </div>
                                <div class="panel-card__chevron-wrap">
                                    <svg class="panel-card__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                            </button>
                            <div class="panel-card__collapse" id="panel-body-facturacion">
                                <div class="panel-card__body" id="panel-facturacion-container">
                                    <!-- Datos fiscales o badge de no requiere factura -->
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Historial Reciente de Pagos del Alumno (Acordeón Desplegable) -->
                        <div class="panel-card panel-card--collapsible is-expanded" id="panel-card-historial">
                            <button type="button" 
                                    class="panel-card__header-btn" 
                                    id="btn-toggle-historial" 
                                    aria-expanded="true" 
                                    aria-controls="panel-body-historial"
                                    title="Alternar visibilidad del historial de pagos">
                                <div class="panel-card__title-content">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span>Últimos Pagos Registrados</span>
                                </div>
                                <div class="panel-card__chevron-wrap">
                                    <svg class="panel-card__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                            </button>
                            <div class="panel-card__collapse" id="panel-body-historial">
                                <div class="panel-card__body" id="panel-historial-container">
                                    <div class="panel-empty-text">Sin pagos previos registrados.</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECCIÓN 2: DETALLES DEL COBRO / RENGLONES DINÁMICOS       -->
        <!-- ======================================================== -->
        <div class="asrs-form-container">
            <div class="asrs-section-header">
                <div class="asrs-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>2. Conceptos de Cobro y Desglose de Importes</span>
                </div>
                <span class="asrs-section-toggle-icon">▼</span>
            </div>

            <div class="asrs-section-body">
                <!-- Campos ocultos de consolidación para backend -->
                <input type="hidden" name="concepto" id="input_concepto" value="">
                <input type="hidden" name="concepto_id" id="input_concepto_id" value="">
                <input type="hidden" name="monto" id="input_monto" value="">
                <input type="hidden" name="desglose_conceptos" id="input_desglose_conceptos" value="">

                <div class="caja-conceptos-wrapper">
                    <!-- Encabezado de Columnas Alineadas -->
                    <div class="caja-conceptos-header">
                        <span class="col-header col-header--num">#</span>
                        <span class="col-header col-header--concepto">Concepto / Servicio <span class="required">*</span></span>
                        <span class="col-header col-header--monto">Monto a Cobrar ($ MXN) <span class="required">*</span></span>
                        <span class="col-header col-header--actions">Acción</span>
                    </div>

                    <!-- Lista de Renglones Dinámicos -->
                    <div id="caja-conceptos-rows" class="caja-conceptos-rows">
                        <!-- Renglones inyectados dinámicamente por registrar_pago.js -->
                    </div>

                    <!-- Barra de Acciones: Botón Agregar (+) -->
                    <div class="caja-conceptos-toolbar">
                        <button type="button" id="btn-add-row" class="btn-add-row" title="Añadir una nueva línea de cobro">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Añadir concepto (+)</span>
                        </button>
                        <span class="caja-toolbar-hint">Puede añadir colegiaturas, cuotas escolares o adeudos CARE en un mismo ticket.</span>
                    </div>

                    <!-- Barra de Total General Consolidado -->
                    <div class="caja-total-bar" id="caja-total-bar">
                        <div class="caja-total-bar__info">
                            <span class="caja-total-bar__title">Total General a Cobrar:</span>
                            <span class="caja-total-bar__count" id="caja-total-count">0 conceptos</span>
                        </div>
                        <div class="caja-total-bar__value">
                            <span class="caja-total-bar__symbol">$</span>
                            <span class="caja-total-bar__amount" id="caja-total-amount">0.00</span>
                            <span class="caja-total-bar__currency">MXN</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECCIÓN 3: FORMA DE PAGO Y DINÁMICA DE REFERENCIA       -->
        <!-- ======================================================== -->
        <div class="asrs-form-container">
            <div class="asrs-section-header">
                <div class="asrs-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span>3. Forma de Pago y Referencia Bancaria</span>
                </div>
                <span class="asrs-section-toggle-icon">▼</span>
            </div>

            <div class="asrs-section-body">
                <div class="asrs-form-row-2">
                    <!-- Selector de Forma de Pago -->
                    <div class="asrs-form-group">
                        <label for="select_forma_pago" class="asrs-form-label">
                            Forma de Pago <span class="required">*</span>
                        </label>
                        <select name="forma_pago" id="select_forma_pago" class="asrs-form-control form-select" required>
                            <?php foreach ($formasPago as $fp): ?>
                                <option 
                                    value="<?= htmlspecialchars($fp['id']) ?>"
                                    data-bancario="<?= $fp['es_bancario'] ? '1' : '0' ?>"
                                    <?= ($fp['id'] === 'Efectivo') ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($fp['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint" id="forma-pago-hint">Pago directo en ventanilla en efectivo.</small>
                    </div>

                    <!-- Campo Dinámico de Referencia Bancaria -->
                    <div class="asrs-form-group" id="container-referencia-bancaria" style="display: none;">
                        <label for="input_referencia" class="asrs-form-label">
                            <span class="bancario-badge-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </span>
                            Referencia / Folio Bancario <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="referencia" 
                            id="input_referencia" 
                            class="asrs-form-control form-control--bancario" 
                            placeholder="Ej. BBVA-84920138 o Folio de Autorización"
                        >
                        <small class="form-hint" id="referencia-hint">
                            Ingrese el folio de transferencia BBVA o número de autorización bancaria.
                        </small>
                    </div>
                </div>

                <!-- Fila de Fecha y Hora -->
                <div class="asrs-form-row-2">
                    <div class="asrs-form-group">
                        <label for="input_fecha_pago" class="asrs-form-label">
                            Fecha de Operación <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="fecha_pago" 
                            id="input_fecha_pago" 
                            class="asrs-form-control" 
                            value="<?= htmlspecialchars($fechaActual) ?>" 
                            required
                        >
                    </div>

                    <div class="asrs-form-group">
                        <label for="input_hora_pago" class="asrs-form-label">
                            Hora de Operación <span class="required">*</span>
                        </label>
                        <input 
                            type="time" 
                            name="hora_pago" 
                            id="input_hora_pago" 
                            class="asrs-form-control" 
                            value="<?= htmlspecialchars($horaActual) ?>" 
                            required
                        >
                    </div>
                </div>

                <!-- Observaciones opcionales -->
                <div class="asrs-form-group">
                    <label for="input_observaciones" class="asrs-form-label">
                        Observaciones o Notas Contables <span class="optional-tag">(Opcional)</span>
                    </label>
                    <textarea 
                        name="observaciones" 
                        id="input_observaciones" 
                        class="asrs-form-control form-textarea" 
                        rows="2" 
                        placeholder="Comentarios adicionales, periodo cubierto o aclaraciones..."
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- BOTÓN PRINCIPAL DE COBRO -->
        <div class="caja-actions">
            <button type="reset" id="btn-reset-form" class="btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                <span>Limpiar Formulario</span>
            </button>

            <button type="submit" id="btn-submit-pago" class="btn-primary btn-primary--cta">
                <span id="btn-submit-spinner" class="btn-spinner" style="display: none;"></span>
                <svg id="btn-submit-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span id="btn-submit-text">Procesar y Emitir Pago</span>
            </button>
        </div>
    </form>

    <!-- 4. MODAL / VOUCHER DE COMPROBANTE DE PAGO -->
    <div id="modal-comprobante" class="modal-overlay" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content voucher-receipt" id="voucher-printable">
                
                <!-- Encabezado del Recibo Oficial -->
                <div class="voucher-header">
                    <div class="voucher-logo">
                        <div class="voucher-logo-badge">CEA</div>
                    </div>
                    <div class="voucher-header__text">
                        <h2 class="voucher-inst-title">Centro Educativo América</h2>
                        <p class="voucher-inst-sub">Comprobante Oficial de Ingreso a Caja</p>
                        <span class="voucher-badge-official">Original - Alumno / Tutor</span>
                    </div>
                </div>

                <div class="voucher-divider"></div>

                <!-- Datos del Folio y Fecha -->
                <div class="voucher-meta-grid">
                    <div class="voucher-meta-item">
                        <span class="voucher-label">Folio de Recibo:</span>
                        <strong class="voucher-folio" id="vouch-folio">REC-000000</strong>
                    </div>
                    <div class="voucher-meta-item text-right">
                        <span class="voucher-label">Fecha y Hora:</span>
                        <span class="voucher-value" id="vouch-fecha-hora">--/--/---- --:--</span>
                    </div>
                    <div class="voucher-meta-item">
                        <span class="voucher-label">Cajero en Turno:</span>
                        <span class="voucher-value" id="vouch-cajero">--</span>
                    </div>
                    <div class="voucher-meta-item text-right">
                        <span class="voucher-label">Estado:</span>
                        <span class="voucher-badge-status" id="vouch-estado">Completado</span>
                    </div>
                </div>

                <div class="voucher-divider"></div>

                <!-- Datos del Alumno -->
                <div class="voucher-alumno-box">
                    <div class="voucher-alumno-line">
                        <span class="voucher-label">Alumno:</span>
                        <strong class="voucher-alumno-name" id="vouch-alumno">--</strong>
                    </div>
                    <div class="voucher-alumno-sub">
                        <span><strong>CURP:</strong> <span id="vouch-curp">--</span></span>
                        <span><strong>Grado/Grupo:</strong> <span id="vouch-grado-grupo">--</span></span>
                    </div>
                </div>

                <!-- Detalle de la Transacción -->
                <div class="voucher-table-wrapper">
                    <table class="voucher-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Forma de Pago</th>
                                <th>Referencia</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="vouch-tbody">
                            <tr>
                                <td id="vouch-concepto">--</td>
                                <td id="vouch-forma-pago">--</td>
                                <td id="vouch-referencia">--</td>
                                <td class="text-right font-bold" id="vouch-monto">$ 0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total Destacado -->
                <div class="voucher-total-box">
                    <span class="voucher-total-label">Total Liquidado:</span>
                    <span class="voucher-total-amount" id="vouch-total">$ 0.00 MXN</span>
                </div>

                <!-- Observaciones -->
                <div class="voucher-notes" id="vouch-notas-container" style="display: none;">
                    <strong>Observaciones:</strong> <span id="vouch-notas"></span>
                </div>

                <!-- Pie de Recibo Oficial -->
                <div class="voucher-footer">
                    <div class="voucher-barcode">
                        <div class="barcode-lines"></div>
                        <span id="vouch-barcode-text">CEA-REC-000000</span>
                    </div>
                    <p class="voucher-disclaimer">
                        Este comprobante es un recibo oficial emitido por el sistema ASRS del Centro Educativo América. Consérvelo para cualquier aclaración o trámite administrativo posterior.
                    </p>
                </div>
            </div>

            <!-- Botones de Acción del Modal -->
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="btn-cerrar-modal">
                    <span>Cerrar</span>
                </button>
                <button type="button" class="btn-primary" id="btn-imprimir-recibo">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Imprimir Comprobante</span>
                </button>
                <button type="button" class="btn-success" id="btn-nuevo-pago">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nueva Captura</span>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Inyección de variables seguras para el Javascript específico -->
<script>
    window.ASRS_CAJA = {
        baseUrl: <?= json_encode($baseUrl) ?>,
        endpointUrl: <?= json_encode(Router::url('caja/registrar_pago', 'private')) ?>,
        cajeroNombre: <?= json_encode($cajero['name'] ?? 'Cajero CEA') ?>,
        fechaActual: <?= json_encode($fechaActual) ?>,
        horaActual: <?= json_encode($horaActual) ?>,
        conceptosCatalogo: <?= json_encode($conceptos) ?>
    };
</script>

<!-- [Módulo CARE] Modal bloqueante de cargos pendientes (assets dedicados) -->
<link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/caja/cea_cargos_modal.css">
<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/caja/cea_cargos_modal.js"></script>
