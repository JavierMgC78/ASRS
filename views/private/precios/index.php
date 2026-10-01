<?php
/**
 * Vista: Gestión de Precios y Tarifas por Nivel Educativo
 * Grupo:     Precios
 * Archivo:   index.php
 * Ruta:      views/private/precios/index.php
 * Assets:    assets/css/precios/index.css
 *            assets/js/precios/index.js
 */

use Core\controllers\PreciosController;
use Core\Router;

// Ejecutar lógica del controlador y validación RBAC de Super Admin
$data = PreciosController::handle();

$tarifasPorNivel  = $data['tarifas'] ?? [];
$metricasPorNivel = $data['metricas'] ?? [];
$niveles          = $data['niveles'] ?? ['preescolar', 'primaria', 'secundaria'];
$usuarioNombre    = $data['usuario'] ?? 'Administrador';
$successMsg       = $data['success'] ?? null;
$errorMsg         = $data['error'] ?? null;
$baseUrl          = $baseUrl ?? rtrim(Router::url('', 'public'), '/');
?>

<div class="precios-modulo-container" id="precios-modulo" data-base-url="<?= htmlspecialchars($baseUrl) ?>">

    <!-- 1. Encabezado Institucional del Módulo -->
    <header class="precios-header-card">
        <div class="precios-header__main">
            <div class="precios-header__badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Catálogo Oficial CEA &bull; Ciclo Escolar Activo</span>
            </div>
            <h1 class="precios-header__title">Gestión de Precios y Tarifas por Nivel</h1>
            <p class="precios-header__desc">
                Administración centralizada de colegiaturas, inscripciones, servicios de comedor y paquetes didácticos oficiales para Preescolar, Primaria y Secundaria.
            </p>
        </div>

        <div class="precios-header__actions">
            <div class="precios-header__stat-chip">
                <span class="chip-label">Perfil Activo</span>
                <span class="chip-value"><?= htmlspecialchars($usuarioNombre) ?></span>
            </div>
            <button type="button" class="btn-nuevo-concepto" id="btn-open-modal-nuevo" title="Registrar un nuevo concepto en el catálogo">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Nuevo Concepto</span>
            </button>
        </div>
    </header>

    <!-- Alertas Flash -->
    <div id="precios-alert-container">
        <?php if (!empty($successMsg)): ?>
            <div class="caja-alert caja-alert--success" role="alert">
                <div class="caja-alert__icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div class="caja-alert__content">
                    <div class="caja-alert__msg"><?= htmlspecialchars($successMsg) ?></div>
                </div>
                <button type="button" class="caja-alert__close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="caja-alert caja-alert--danger" role="alert">
                <div class="caja-alert__icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="caja-alert__content">
                    <div class="caja-alert__msg"><?= htmlspecialchars($errorMsg) ?></div>
                </div>
                <button type="button" class="caja-alert__close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. Barra Superior de Pestañas (Tabs UI/UX con Colores Oficiales) -->
    <nav class="precios-tabs-nav" role="tablist" aria-label="Niveles Educativos">
        
        <!-- Tab 1: Preescolar (Ámbar #FFB300) -->
        <button type="button" 
                class="precios-tab-btn is-active tab-theme--preescolar" 
                role="tab" 
                id="tab-btn-preescolar" 
                aria-selected="true" 
                aria-controls="tab-panel-preescolar"
                data-nivel="preescolar">
            <div class="tab-btn__indicator"></div>
            <div class="tab-btn__icon-wrap">
                <!-- Icono Bloques / Lúdico -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div class="tab-btn__text">
                <span class="tab-btn__title">Preescolar</span>
                <span class="tab-btn__sub">
                    <span class="sub-colegiatura" id="badge-col-preescolar">$<?= $metricasPorNivel['preescolar']['colegiatura_fmt'] ?? '0.00' ?>/mes</span>
                    <span class="sub-dot">&bull;</span>
                    <span class="sub-count" id="badge-count-preescolar"><?= $metricasPorNivel['preescolar']['total_activos'] ?? 0 ?> conceptos</span>
                </span>
            </div>
        </button>

        <!-- Tab 2: Primaria (Azul Royal #2962FF) -->
        <button type="button" 
                class="precios-tab-btn tab-theme--primaria" 
                role="tab" 
                id="tab-btn-primaria" 
                aria-selected="false" 
                aria-controls="tab-panel-primaria"
                data-nivel="primaria">
            <div class="tab-btn__indicator"></div>
            <div class="tab-btn__icon-wrap">
                <!-- Icono Libro / Mochila -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div class="tab-btn__text">
                <span class="tab-btn__title">Primaria</span>
                <span class="tab-btn__sub">
                    <span class="sub-colegiatura" id="badge-col-primaria">$<?= $metricasPorNivel['primaria']['colegiatura_fmt'] ?? '0.00' ?>/mes</span>
                    <span class="sub-dot">&bull;</span>
                    <span class="sub-count" id="badge-count-primaria"><?= $metricasPorNivel['primaria']['total_activos'] ?? 0 ?> conceptos</span>
                </span>
            </div>
        </button>

        <!-- Tab 3: Secundaria (Rojo Quemado #B71C1C) -->
        <button type="button" 
                class="precios-tab-btn tab-theme--secundaria" 
                role="tab" 
                id="tab-btn-secundaria" 
                aria-selected="false" 
                aria-controls="tab-panel-secundaria"
                data-nivel="secundaria">
            <div class="tab-btn__indicator"></div>
            <div class="tab-btn__icon-wrap">
                <!-- Icono Birrete / Academia -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div class="tab-btn__text">
                <span class="tab-btn__title">Secundaria</span>
                <span class="tab-btn__sub">
                    <span class="sub-colegiatura" id="badge-col-secundaria">$<?= $metricasPorNivel['secundaria']['colegiatura_fmt'] ?? '0.00' ?>/mes</span>
                    <span class="sub-dot">&bull;</span>
                    <span class="sub-count" id="badge-count-secundaria"><?= $metricasPorNivel['secundaria']['total_activos'] ?? 0 ?> conceptos</span>
                </span>
            </div>
        </button>

    </nav>

    <!-- 3. Contenedores de Paneles de Pestaña -->
    <div class="precios-panels-wrapper">

        <?php foreach ($niveles as $index => $nivel): 
            $isActive = ($index === 0);
            $conceptosNivel = $tarifasPorNivel[$nivel] ?? [];
            $metricas = $metricasPorNivel[$nivel] ?? [];
            $themeClass = 'theme--' . $nivel;
            $tituloNivel = ucfirst($nivel);
        ?>

        <!-- Panel del Nivel: <?= htmlspecialchars($tituloNivel) ?> -->
        <section class="precios-panel <?= $themeClass ?> <?= $isActive ? 'is-active' : '' ?>" 
                 role="tabpanel" 
                 id="tab-panel-<?= $nivel ?>" 
                 aria-labelledby="tab-btn-<?= $nivel ?>"
                 data-nivel="<?= $nivel ?>">

            <!-- Tarjetas de Métricas de Nivel -->
            <div class="nivel-kpi-grid">
                
                <div class="kpi-card kpi-card--primary">
                    <div class="kpi-card__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                    </div>
                    <div class="kpi-card__content">
                        <span class="kpi-card__label">Colegiatura Mensual</span>
                        <div class="kpi-card__val">
                            <span class="kpi-currency">$</span>
                            <span class="kpi-amount" id="kpi-col-<?= $nivel ?>"><?= $metricas['colegiatura_fmt'] ?? '0.00' ?></span>
                            <span class="kpi-freq">MXN / mes</span>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div class="kpi-card__content">
                        <span class="kpi-card__label">Inscripción y Matrícula</span>
                        <div class="kpi-card__val">
                            <span class="kpi-currency">$</span>
                            <span class="kpi-amount" id="kpi-ins-<?= $nivel ?>"><?= $metricas['inscripcion_fmt'] ?? '0.00' ?></span>
                            <span class="kpi-freq">MXN / ciclo</span>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <div class="kpi-card__content">
                        <span class="kpi-card__label">Paquete Anual Estimado</span>
                        <div class="kpi-card__val">
                            <span class="kpi-currency">$</span>
                            <span class="kpi-amount" id="kpi-paq-<?= $nivel ?>"><?= $metricas['paquete_fmt'] ?? '0.00' ?></span>
                            <span class="kpi-freq">Total conceptos activos</span>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="kpi-card__content">
                        <span class="kpi-card__label">Conceptos Registrados</span>
                        <div class="kpi-card__val">
                            <span class="kpi-amount font-large" id="kpi-tot-<?= $nivel ?>"><?= $metricas['total_activos'] ?? 0 ?></span>
                            <span class="kpi-freq">vigentes en caja</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tabla de Tarifas del Nivel -->
            <div class="tarifas-table-card">
                
                <div class="tarifas-table-header">
                    <div class="table-header__left">
                        <h2 class="table-header__title">Tarifario Oficial &bull; <?= htmlspecialchars($tituloNivel) ?></h2>
                        <span class="table-header__subtitle">Modifique el monto y presione Guardar o la tecla Enter. Los cambios impactan en tiempo real al módulo de Caja.</span>
                    </div>
                    <div class="table-header__actions">
                        <button type="button" class="btn-guardar-todo" data-nivel="<?= $nivel ?>" title="Guardar todos los montos editados en este nivel">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span>Guardar Todo el Nivel</span>
                        </button>
                    </div>
                </div>

                <div class="tarifas-table-responsive">
                    <table class="tarifas-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Concepto Escolar</th>
                                <th>Descripción / Cobertura</th>
                                <th style="width: 220px;">Monto Oficial (MXN)</th>
                                <th style="width: 110px; text-align: center;">Estado</th>
                                <th style="width: 140px; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-tarifas-<?= $nivel ?>">
                            <?php if (empty($conceptosNivel)): ?>
                                <tr>
                                    <td colspan="6" class="table-empty-row">
                                        No hay conceptos registrados para este nivel.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($conceptosNivel as $c): 
                                    $montoFmt = number_format((float)$c['monto'], 2, '.', '');
                                    $isActivo = !empty($c['activo']);
                                    $rowClass = $isActivo ? '' : 'is-disabled-row';
                                    $updatedAt = !empty($c['updated_at']) ? date('d/m/Y H:i', strtotime($c['updated_at'])) : '--';
                                ?>
                                <tr class="tarifa-row <?= $rowClass ?>" id="tarifa-row-<?= $c['id'] ?>" data-id="<?= $c['id'] ?>" data-nivel="<?= $nivel ?>">
                                    <td class="col-id">
                                        <span class="id-tag">#<?= $c['id'] ?></span>
                                    </td>
                                    <td class="col-concepto">
                                        <div class="concepto-title font-bold"><?= htmlspecialchars($c['concepto']) ?></div>
                                        <span class="concepto-updated">Última actualización: <span id="lbl-upd-<?= $c['id'] ?>"><?= $updatedAt ?></span></span>
                                    </td>
                                    <td class="col-desc">
                                        <span class="desc-text"><?= htmlspecialchars($c['descripcion'] ?? 'Sin descripción adicional') ?></span>
                                    </td>
                                    <td class="col-monto">
                                        <div class="monto-input-group">
                                            <span class="monto-prefix">$</span>
                                            <input type="number" 
                                                   class="input-monto-editable" 
                                                   id="input-monto-<?= $c['id'] ?>" 
                                                   value="<?= $montoFmt ?>" 
                                                   step="0.50" 
                                                   min="0" 
                                                   data-id="<?= $c['id'] ?>" 
                                                   data-original="<?= $montoFmt ?>" 
                                                   aria-label="Monto para <?= htmlspecialchars($c['concepto']) ?>">
                                            <span class="monto-suffix">MXN</span>
                                        </div>
                                    </td>
                                    <td class="col-estado text-center">
                                        <label class="switch-toggle" title="Activar o desactivar este concepto">
                                            <input type="checkbox" 
                                                   class="chk-toggle-activo" 
                                                   data-id="<?= $c['id'] ?>" 
                                                   <?= $isActivo ? 'checked' : '' ?>>
                                            <span class="switch-slider"></span>
                                        </label>
                                    </td>
                                    <td class="col-accion text-center">
                                        <button type="button" 
                                                class="btn-guardar-fila" 
                                                data-id="<?= $c['id'] ?>" 
                                                data-nivel="<?= $nivel ?>" 
                                                title="Guardar tarifa para este concepto">
                                            <span class="btn-text">Actualizar</span>
                                            <svg class="btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                                <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                                                <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                                <line x1="18" y1="12" x2="22" y2="12"></line>
                                                <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                                                <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                                            </svg>
                                            <svg class="btn-check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </section>
        <?php endforeach; ?>

    </div>

    <!-- Modal Rápido: Agregar Nuevo Concepto -->
    <div class="precios-modal-backdrop" id="modal-nuevo-concepto" style="display: none;">
        <div class="precios-modal-card" role="dialog" aria-labelledby="modal-title" aria-modal="true">
            <div class="modal-card__header">
                <div class="modal-card__header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="modal-card__title" id="modal-title">Registrar Nuevo Concepto</h3>
                    <p class="modal-card__sub">Añade un concepto y asígnalo a una tarifa oficial.</p>
                </div>
                <button type="button" class="modal-card__close" id="btn-close-modal">&times;</button>
            </div>

            <form id="form-nuevo-concepto">
                <div class="modal-card__body">
                    
                    <div class="form-group">
                        <label for="nuevo_nivel" class="form-label">Nivel Educativo *</label>
                        <select id="nuevo_nivel" class="form-control" required>
                            <option value="preescolar">Preescolar (Ámbar)</option>
                            <option value="primaria">Primaria (Azul Royal)</option>
                            <option value="secundaria">Secundaria (Rojo Quemado)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nuevo_concepto" class="form-label">Nombre del Concepto *</label>
                        <input type="text" id="nuevo_concepto" class="form-control" placeholder="Ej. Curso Propedéutico / Seguro" required maxlength="150">
                    </div>

                    <div class="form-group">
                        <label for="nuevo_monto" class="form-label">Monto Sugerido Oficial (MXN) *</label>
                        <div class="monto-input-group">
                            <span class="monto-prefix">$</span>
                            <input type="number" id="nuevo_monto" class="form-control" placeholder="0.00" step="0.50" min="0" required>
                            <span class="monto-suffix">MXN</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nueva_descripcion" class="form-label">Descripción o Reglas de Aplicación</label>
                        <textarea id="nueva_descripcion" class="form-control" rows="2" placeholder="Detalles sobre cuándo y cómo aplica este cobro..." maxlength="255"></textarea>
                    </div>

                </div>

                <div class="modal-card__footer">
                    <button type="button" class="btn-cancelar" id="btn-cancelar-modal">Cancelar</button>
                    <button type="submit" class="btn-guardar-modal" id="btn-submit-nuevo">
                        <span class="btn-text">Guardar Concepto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast de Notificación Flotante -->
    <div id="precios-toast-container" class="toast-container" aria-live="polite"></div>

</div>
