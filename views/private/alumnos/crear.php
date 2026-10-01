<?php
/**
 * Vista: Alta de Alumno
 * Grupo:     Alumnos
 * Archivo:   crear.php
 * Ruta:      views/private/alumnos/crear.php
 * Assets:    assets/css/alumnos/crear.css
 *            assets/js/alumnos/crear.js
 */

use Core\controllers\AlumnosController;
use Core\Router;

// Ejecutar lógica del controlador PDO para procesar peticiones y validaciones
$moduleData = AlumnosController::handleCreate();

$errorMsg   = $moduleData['error']    ?? null;
$successMsg = $moduleData['success']  ?? null;
$alumnoId   = $moduleData['alumnoId'] ?? null;

$dashboardUrl = Router::url('dashboard');
?>

<div class="alumno-create-container">

    <!-- 1. Encabezado de la Página -->
    <header class="alumno-create-header">
        <div class="alumno-create-header__text">
            <nav class="alumno-breadcrumb" aria-label="Migas de pan">
                <span class="alumno-breadcrumb__item">Control Escolar</span>
                <span class="alumno-breadcrumb__separator">/</span>
                <span class="alumno-breadcrumb__item">Alumnos</span>
                <span class="alumno-breadcrumb__separator">/</span>
                <span class="alumno-breadcrumb__active">Alta de Alumno</span>
            </nav>
            <h1 class="alumno-create-title">Registro y Alta de Alumno</h1>
            <p class="alumno-create-subtitle">
                Captura de expediente escolar, asignación académica, tutores, seguridad de entrega y régimen fiscal.
            </p>
        </div>

        <div class="alumno-create-header__actions">
            <a href="<?= htmlspecialchars(Router::url('alumnos/ver', 'private')) ?>" class="btn-secondary" title="Ver listado completo de alumnos">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>Ver Directorio</span>
            </a>
            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn-secondary" title="Volver al panel principal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Volver al Panel</span>
            </a>
        </div>
    </header>

    <!-- 2. Alertas del Servidor (Render tradicional) -->
    <div id="alumno-alert-container">
        <?php if (!empty($successMsg)): ?>
            <div class="alumno-alert alumno-alert--success" role="alert">
                <div class="alumno-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="alumno-alert__content">
                    <div class="alumno-alert__title">¡Inscripción Exitosa!</div>
                    <div class="alumno-alert__msg"><?= $successMsg ?></div>
                </div>
                <button type="button" class="alumno-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar alerta">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alumno-alert alumno-alert--danger" role="alert">
                <div class="alumno-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="alumno-alert__content">
                    <div class="alumno-alert__title">Error al procesar el alta</div>
                    <div class="alumno-alert__msg"><?= $errorMsg ?></div>
                </div>
                <button type="button" class="alumno-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar alerta">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. PASO 1: Validación Asíncrona de CURP (Card de Verificación) -->
    <div class="curp-verification-card" id="curp-verification-card">
        <div class="curp-verification-card__header">
            <div class="curp-badge-step">Paso 1</div>
            <div>
                <h3 class="curp-verification-title">Validación de Identidad por CURP</h3>
                <p class="curp-verification-desc">
                    Ingresa la Clave Única de Registro de Población (18 caracteres). El sistema validará asíncronamente en tiempo real si el alumno ya se encuentra registrado antes de habilitar el expediente.
                </p>
            </div>
        </div>

        <div class="curp-input-group">
            <div class="curp-input-wrapper">
                <svg class="curp-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                    <line x1="7" y1="8" x2="17" y2="8"></line>
                    <line x1="7" y1="12" x2="17" y2="12"></line>
                    <line x1="7" y1="16" x2="12" y2="16"></line>
                </svg>
                <input 
                    type="text" 
                    id="input-curp-check" 
                    name="curp_check" 
                    class="form-control form-control--curp" 
                    placeholder="Ej. ABCD980101HDFRRN09" 
                    maxlength="18" 
                    autocomplete="off"
                    spellcheck="false"
                >
                <span class="curp-counter" id="curp-char-count">0/18</span>
            </div>

            <button type="button" class="btn-check-curp" id="btn-check-curp">
                <span class="btn-spinner" style="display: none;" id="curp-spinner"></span>
                <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span id="btn-check-curp-text">Consultar CURP</span>
            </button>
        </div>

        <!-- Feedback Dinámico de la Validación de CURP -->
        <div id="curp-feedback-box" class="curp-feedback-box" style="display: none;"></div>
    </div>

    <!-- 4. PASO 2: FORMULARIO COMPLETO POR SECCIONES (Oculto inicialmente hasta validar CURP) -->
    <form id="form-alta-alumno" method="POST" action="" class="alumno-form" style="display: none;" novalidate>
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="ajax" value="1">
        <input type="hidden" id="hidden-curp" name="curp" value="">

        <!-- Barra de Progreso y Navegación de Secciones -->
        <nav class="sections-nav" aria-label="Navegación de secciones">
            <a href="#sec-alumno" class="sections-nav__tab is-active" data-section="sec-alumno">
                <span class="tab-number">1</span>
                <span class="tab-label">Alumno</span>
            </a>
            <a href="#sec-plataforma" class="sections-nav__tab" data-section="sec-plataforma">
                <span class="tab-number">2</span>
                <span class="tab-label">Plataforma</span>
            </a>
            <a href="#sec-tutor1" class="sections-nav__tab" data-section="sec-tutor1">
                <span class="tab-number">3</span>
                <span class="tab-label">Tutor 1</span>
            </a>
            <a href="#sec-tutor2" class="sections-nav__tab" data-section="sec-tutor2">
                <span class="tab-number">4</span>
                <span class="tab-label">Tutor 2</span>
            </a>
            <a href="#sec-autorizadas" class="sections-nav__tab" data-section="sec-autorizadas">
                <span class="tab-number">5</span>
                <span class="tab-label">Autorizados</span>
            </a>
            <a href="#sec-facturacion" class="sections-nav__tab" data-section="sec-facturacion">
                <span class="tab-number">6</span>
                <span class="tab-label">Facturación</span>
            </a>
        </nav>

        <!-- SECCIÓN 1: DATOS GENERALES DEL ALUMNO -->
        <div class="alumno-section-card" id="sec-alumno">
            <div class="alumno-section-card__header">
                <div class="section-badge">Sección 1</div>
                <div>
                    <h3 class="section-title">Datos Personales y Escolares del Alumno</h3>
                    <p class="section-desc">Información básica del estudiante, nivel educativo, grado y contacto médico.</p>
                </div>
            </div>

            <div class="alumno-section-card__body">
                <!-- Banner informativo de CURP validada -->
                <div class="curp-locked-banner">
                    <div class="curp-locked-info">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                        <span>CURP Verificada: <strong id="display-locked-curp">---</strong></span>
                    </div>
                    <button type="button" class="btn-change-curp" id="btn-change-curp">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>Cambiar CURP</span>
                    </button>
                </div>

                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="alumno_nombre" class="form-label">Nombre(s) <span class="required-mark">*</span></label>
                        <input type="text" id="alumno_nombre" name="nombre" class="form-control" placeholder="Ej. Mateo Alejandro" required>
                    </div>

                    <div class="form-group">
                        <label for="alumno_primer_apellido" class="form-label">Primer Apellido <span class="required-mark">*</span></label>
                        <input type="text" id="alumno_primer_apellido" name="primer_apellido" class="form-control" placeholder="Ej. Gómez" required>
                    </div>

                    <div class="form-group">
                        <label for="alumno_segundo_apellido" class="form-label">Segundo Apellido</label>
                        <input type="text" id="alumno_segundo_apellido" name="segundo_apellido" class="form-control" placeholder="Ej. Morales">
                    </div>
                </div>

                <div class="form-grid form-grid--4">
                    <div class="form-group">
                        <label for="alumno_fecha_nac" class="form-label">Fecha de Nacimiento <span class="required-mark">*</span></label>
                        <input type="date" id="alumno_fecha_nac" name="fecha_nacimiento" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="alumno_genero" class="form-label">Género</label>
                        <select id="alumno_genero" name="genero" class="form-select">
                            <option value="">Seleccionar género...</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="Otro">Otro / No especificado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="alumno_nivel" class="form-label">Nivel Educativo <span class="required-mark">*</span></label>
                        <select id="alumno_nivel" name="nivel_educativo" class="form-select" required>
                            <option value="">Seleccionar nivel...</option>
                            <option value="Preescolar">Preescolar</option>
                            <option value="Primaria">Primaria</option>
                            <option value="Secundaria">Secundaria</option>
                            <option value="Bachillerato">Bachillerato</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="alumno_grado" class="form-label">Grado de Ingreso <span class="required-mark">*</span></label>
                        <select id="alumno_grado" name="grado" class="form-select" required>
                            <option value="">Selecciona nivel primero...</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="alumno_grupo" class="form-label">Grupo Asignado</label>
                        <input type="text" id="alumno_grupo" name="grupo" class="form-control" placeholder="Ej. A, B o Único" value="A">
                    </div>

                    <div class="form-group">
                        <label for="alumno_telefono" class="form-label">Teléfono de Contacto</label>
                        <input type="tel" id="alumno_telefono" name="telefono" class="form-control" placeholder="10 dígitos">
                    </div>

                    <div class="form-group">
                        <label for="alumno_email" class="form-label">Correo Electrónico Personal</label>
                        <input type="email" id="alumno_email" name="email" class="form-control" placeholder="alumno@correo.com">
                    </div>
                </div>

                <div class="form-divider"><span>Domicilio y Ubicación</span></div>

                <div class="form-grid form-grid--3">
                    <div class="form-group form-group--span2">
                        <label for="alumno_direccion" class="form-label">Calle y Número</label>
                        <input type="text" id="alumno_direccion" name="direccion" class="form-control" placeholder="Ej. Av. Reforma #123 Interior 4">
                    </div>

                    <div class="form-group">
                        <label for="alumno_colonia" class="form-label">Colonia / Fraccionamiento</label>
                        <input type="text" id="alumno_colonia" name="colonia" class="form-control" placeholder="Ej. Centro">
                    </div>
                </div>

                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="alumno_cp" class="form-label">Código Postal</label>
                        <input type="text" id="alumno_cp" name="codigo_postal" class="form-control" placeholder="Ej. 72000" maxlength="5">
                    </div>

                    <div class="form-group">
                        <label for="alumno_municipio" class="form-label">Municipio</label>
                        <input type="text" id="alumno_municipio" name="municipio" class="form-control" value="Puebla">
                    </div>

                    <div class="form-group">
                        <label for="alumno_estado" class="form-label">Estado</label>
                        <input type="text" id="alumno_estado" name="estado" class="form-control" value="Puebla">
                    </div>
                </div>

                <div class="form-divider"><span>Ficha Médica y Emergencias</span></div>

                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="alumno_tipo_sangre" class="form-label">Tipo de Sangre</label>
                        <select id="alumno_tipo_sangre" name="tipo_sangre" class="form-select">
                            <option value="">Seleccionar...</option>
                            <option value="O+">O Positivo (O+)</option>
                            <option value="O-">O Negativo (O-)</option>
                            <option value="A+">A Positivo (A+)</option>
                            <option value="A-">A Negativo (A-)</option>
                            <option value="B+">B Positivo (B+)</option>
                            <option value="B-">B Negativo (B-)</option>
                            <option value="AB+">AB Positivo (AB+)</option>
                            <option value="AB-">AB Negativo (AB-)</option>
                        </select>
                    </div>

                    <div class="form-group form-group--span2">
                        <label for="alumno_alergias" class="form-label">Alergias / Padecimientos Crónicos</label>
                        <input type="text" id="alumno_alergias" name="alergias_condiciones" class="form-control" placeholder="Ninguna conocida o describir padecimientos">
                    </div>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label for="alumno_emerg_nom" class="form-label">Contacto de Emergencia Adicional</label>
                        <input type="text" id="alumno_emerg_nom" name="contacto_emergencia" class="form-control" placeholder="Nombre completo">
                    </div>

                    <div class="form-group">
                        <label for="alumno_emerg_tel" class="form-label">Teléfono de Emergencia</label>
                        <input type="tel" id="alumno_emerg_tel" name="telefono_emergencia" class="form-control" placeholder="Teléfono directo">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: PLATAFORMA ESCOLAR Y ACCESO DIGITAL -->
        <div class="alumno-section-card" id="sec-plataforma">
            <div class="alumno-section-card__header">
                <div class="section-badge">Sección 2</div>
                <div>
                    <h3 class="section-title">Credenciales y Plataforma Escolar</h3>
                    <p class="section-desc">Generación de matrícula institucional, cuenta de acceso al portal y credenciales.</p>
                </div>
            </div>

            <div class="alumno-section-card__body">
                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="plat_matricula" class="form-label">Matrícula Escolar Sugerida</label>
                        <input type="text" id="plat_matricula" name="matricula" class="form-control" placeholder="Auto-generada (Ej. CEA-2026-0042)">
                        <span class="form-hint">Si se deja vacío, el sistema generará una matrícula consecutiva.</span>
                    </div>

                    <div class="form-group">
                        <label for="plat_usuario" class="form-label">Usuario de Plataforma</label>
                        <input type="text" id="plat_usuario" name="usuario_plataforma" class="form-control" placeholder="Ej. mgomez26">
                        <span class="form-hint">Usuario para acceder al portal estudiantil.</span>
                    </div>

                    <div class="form-group">
                        <label for="plat_email" class="form-label">Correo Institucional</label>
                        <input type="email" id="plat_email" name="email_institucional" class="form-control" placeholder="nombre@cea.edu.mx">
                    </div>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label for="plat_password" class="form-label">Contraseña Temporal de Acceso</label>
                        <input type="text" id="plat_password" name="password_plataforma" class="form-control" placeholder="Por defecto será la matrícula del alumno">
                        <span class="form-hint">El alumno o tutor podrá modificarla en su primer inicio de sesión.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Estatus de Acceso a Plataforma</label>
                        <div class="toggle-control-box">
                            <label class="switch-ui">
                                <input type="checkbox" name="acceso_activo" value="1" checked>
                                <span class="slider-ui"></span>
                            </label>
                            <span class="toggle-label">Habilitar acceso inmediato al portal del alumno</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: TUTOR 1 (PRINCIPAL / CONTACTO OBLIGATORIO) -->
        <div class="alumno-section-card" id="sec-tutor1">
            <div class="alumno-section-card__header">
                <div class="section-badge">Sección 3</div>
                <div>
                    <h3 class="section-title">Datos del Tutor 1 (Contacto Principal)</h3>
                    <p class="section-desc">Padre, madre o tutor legal responsable de la patria potestad y comunicación directa.</p>
                </div>
            </div>

            <div class="alumno-section-card__body">
                <div class="form-grid form-grid--3">
                    <div class="form-group form-group--span2">
                        <label for="tutor1_nombre" class="form-label">Nombre Completo <span class="required-mark">*</span></label>
                        <input type="text" id="tutor1_nombre" name="tutor1_nombre" class="form-control" placeholder="Ej. Roberto Gómez Fernández" required>
                    </div>

                    <div class="form-group">
                        <label for="tutor1_parentesco" class="form-label">Parentesco <span class="required-mark">*</span></label>
                        <select id="tutor1_parentesco" name="tutor1_parentesco" class="form-select" required>
                            <option value="">Seleccionar parentesco...</option>
                            <option value="Padre" selected>Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Abuelo/a">Abuelo / Abuela</option>
                            <option value="Tutor Legal">Tutor Legal</option>
                            <option value="Tío/a">Tío / Tía</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="tutor1_tel_prin" class="form-label">Teléfono Celular / WhatsApp <span class="required-mark">*</span></label>
                        <input type="tel" id="tutor1_tel_prin" name="tutor1_telefono_principal" class="form-control" placeholder="10 dígitos" required>
                    </div>

                    <div class="form-group">
                        <label for="tutor1_tel_sec" class="form-label">Teléfono Secundario / Fijo</label>
                        <input type="tel" id="tutor1_tel_sec" name="tutor1_telefono_secundario" class="form-control" placeholder="Teléfono de casa u oficina">
                    </div>

                    <div class="form-group">
                        <label for="tutor1_email" class="form-label">Correo Electrónico <span class="required-mark">*</span></label>
                        <input type="email" id="tutor1_email" name="tutor1_email" class="form-control" placeholder="tutor@ejemplo.com" required>
                    </div>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label for="tutor1_ocupacion" class="form-label">Ocupación / Profesión</label>
                        <input type="text" id="tutor1_ocupacion" name="tutor1_ocupacion" class="form-control" placeholder="Ej. Ingeniero Civil">
                    </div>

                    <div class="form-group">
                        <label for="tutor1_trabajo" class="form-label">Lugar de Trabajo</label>
                        <input type="text" id="tutor1_trabajo" name="tutor1_lugar_trabajo" class="form-control" placeholder="Nombre de empresa o negocio">
                    </div>
                </div>

                <div class="tutor-flags-box">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="tutor1_vive_con_alumno" value="1" checked>
                        <span class="checkbox-indicator"></span>
                        <span class="checkbox-text">Vive con el alumno en el mismo domicilio</span>
                    </label>

                    <label class="custom-checkbox">
                        <input type="checkbox" name="tutor1_es_responsable_economico" value="1" checked>
                        <span class="checkbox-indicator"></span>
                        <span class="checkbox-text">Es el responsable del pago de colegiaturas</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 4: TUTOR 2 (SECUNDARIO / OPCIONAL) -->
        <div class="alumno-section-card" id="sec-tutor2">
            <div class="alumno-section-card__header">
                <div class="section-badge">Sección 4</div>
                <div>
                    <h3 class="section-title">Datos del Tutor 2 (Opcional / Segundo Contacto)</h3>
                    <p class="section-desc">Madre, padre o segundo contacto autorizado para toma de decisiones y emergencias.</p>
                </div>
            </div>

            <div class="alumno-section-card__body">
                <div class="form-grid form-grid--3">
                    <div class="form-group form-group--span2">
                        <label for="tutor2_nombre" class="form-label">Nombre Completo</label>
                        <input type="text" id="tutor2_nombre" name="tutor2_nombre" class="form-control" placeholder="Ej. Laura Morales Sánchez">
                    </div>

                    <div class="form-group">
                        <label for="tutor2_parentesco" class="form-label">Parentesco</label>
                        <select id="tutor2_parentesco" name="tutor2_parentesco" class="form-select">
                            <option value="">Seleccionar parentesco...</option>
                            <option value="Madre" selected>Madre</option>
                            <option value="Padre">Padre</option>
                            <option value="Abuelo/a">Abuelo / Abuela</option>
                            <option value="Tutor Legal">Tutor Legal</option>
                            <option value="Tío/a">Tío / Tía</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label for="tutor2_tel_prin" class="form-label">Teléfono Celular / WhatsApp</label>
                        <input type="tel" id="tutor2_tel_prin" name="tutor2_telefono_principal" class="form-control" placeholder="10 dígitos">
                    </div>

                    <div class="form-group">
                        <label for="tutor2_tel_sec" class="form-label">Teléfono Secundario / Fijo</label>
                        <input type="tel" id="tutor2_tel_sec" name="tutor2_telefono_secundario" class="form-control" placeholder="Teléfono de casa u oficina">
                    </div>

                    <div class="form-group">
                        <label for="tutor2_email" class="form-label">Correo Electrónico</label>
                        <input type="email" id="tutor2_email" name="tutor2_email" class="form-control" placeholder="segundo.tutor@ejemplo.com">
                    </div>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label for="tutor2_ocupacion" class="form-label">Ocupación / Profesión</label>
                        <input type="text" id="tutor2_ocupacion" name="tutor2_ocupacion" class="form-control" placeholder="Ej. Contadora Pública">
                    </div>

                    <div class="form-group">
                        <label for="tutor2_trabajo" class="form-label">Lugar de Trabajo</label>
                        <input type="text" id="tutor2_trabajo" name="tutor2_lugar_trabajo" class="form-control" placeholder="Nombre de empresa o negocio">
                    </div>
                </div>

                <div class="tutor-flags-box">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="tutor2_vive_con_alumno" value="1" checked>
                        <span class="checkbox-indicator"></span>
                        <span class="checkbox-text">Vive con el alumno en el mismo domicilio</span>
                    </label>

                    <label class="custom-checkbox">
                        <input type="checkbox" name="tutor2_es_responsable_economico" value="1">
                        <span class="checkbox-indicator"></span>
                        <span class="checkbox-text">Es responsable compartido de pagos</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 5: PERSONAS AUTORIZADAS PARA RECOGER AL ALUMNO -->
        <div class="alumno-section-card" id="sec-autorizadas">
            <div class="alumno-section-card__header alumno-section-card__header--flex">
                <div>
                    <div class="section-badge">Sección 5</div>
                    <h3 class="section-title">Personas Autorizadas para Recoger al Alumno</h3>
                    <p class="section-desc">Personal externo o familiares facultados por el tutor para retirar al alumno de la institución.</p>
                </div>
                <button type="button" class="btn-add-person" id="btn-add-person">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Agregar Persona</span>
                </button>
            </div>

            <div class="alumno-section-card__body">
                <div class="persons-list" id="persons-container">
                    <!-- Fila 1 inicial predeterminada -->
                    <div class="person-card-item" data-index="0">
                        <div class="person-card-header">
                            <span class="person-badge-num">Persona 1</span>
                            <button type="button" class="btn-remove-person" title="Eliminar fila" style="display: none;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="form-grid form-grid--3">
                            <div class="form-group">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" name="auth_nombre[]" class="form-control" placeholder="Ej. Carmen Sánchez Ruiz">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Parentesco / Relación</label>
                                <input type="text" name="auth_parentesco[]" class="form-control" placeholder="Ej. Tía / Transporte Escolar">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Teléfono de Contacto</label>
                                <input type="tel" name="auth_telefono[]" class="form-control" placeholder="10 dígitos">
                            </div>
                        </div>
                        <div class="form-grid form-grid--2">
                            <div class="form-group">
                                <label class="form-label">Identificación Oficial Requerida</label>
                                <select name="auth_identificacion[]" class="form-select">
                                    <option value="INE" selected>Credencial para Votar (INE/IFE)</option>
                                    <option value="Pasaporte">Pasaporte Vigente</option>
                                    <option value="Cedula">Cédula Profesional</option>
                                    <option value="Licencia">Licencia de Manejar</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Observaciones / Restricciones</label>
                                <input type="text" name="auth_observaciones[]" class="form-control" placeholder="Ej. Solo días viernes o con previo aviso">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 6: DATOS DE FACTURACIÓN FISCAL -->
        <div class="alumno-section-card" id="sec-facturacion">
            <div class="alumno-section-card__header">
                <div class="section-badge">Sección 6</div>
                <div>
                    <h3 class="section-title">Datos Fiscales y Facturación (CFDI)</h3>
                    <p class="section-desc">Habilite si el padre de familia o tutor requiere emisión de comprobantes fiscales de colegiaturas.</p>
                </div>
            </div>

            <div class="alumno-section-card__body">
                <div class="invoice-toggle-box">
                    <label class="switch-ui">
                        <input type="checkbox" id="toggle-requiere-factura" name="requiere_factura" value="1">
                        <span class="slider-ui"></span>
                    </label>
                    <div class="toggle-desc">
                        <strong>¿El tutor requiere comprobante fiscal digital por internet (CFDI 4.0)?</strong>
                        <p>Al activar esta opción se solicitará el RFC, Razón Social y Régimen Fiscal para emitir facturas deducibles de colegiatura.</p>
                    </div>
                </div>

                <div id="invoice-fields-container" class="invoice-fields" style="display: none;">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <label for="fac_razon" class="form-label">Razón Social / Nombre Fiscal <span class="required-mark">*</span></label>
                            <input type="text" id="fac_razon" name="razon_social" class="form-control" placeholder="Nombre completo o Empresa tal cual consta en la CSF">
                        </div>

                        <div class="form-group">
                            <label for="fac_rfc" class="form-label">RFC (Registro Federal de Contribuyentes) <span class="required-mark">*</span></label>
                            <input type="text" id="fac_rfc" name="rfc" class="form-control form-control--uppercase" placeholder="12 o 13 caracteres (Ej. GOMR800101XYZ)" maxlength="13">
                        </div>
                    </div>

                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <label for="fac_regimen" class="form-label">Régimen Fiscal <span class="required-mark">*</span></label>
                            <select id="fac_regimen" name="regimen_fiscal" class="form-select">
                                <option value="">Selecciona el régimen fiscal...</option>
                                <option value="605 - Sueldos y Salarios e Ingresos Asimilados a Salarios" selected>605 - Sueldos y Salarios e Ingresos Asimilados a Salarios</option>
                                <option value="612 - Personas Físicas con Actividades Empresariales y Profesionales">612 - Personas Físicas con Actividades Empresariales y Profesionales</option>
                                <option value="626 - Régimen Simplificado de Confianza (RESICO)">626 - Régimen Simplificado de Confianza (RESICO)</option>
                                <option value="601 - General de Ley Personas Morales">601 - General de Ley Personas Morales</option>
                                <option value="603 - Personas Morales con Fines no Lucrativos">603 - Personas Morales con Fines no Lucrativos</option>
                                <option value="616 - Sin obligaciones fiscales">616 - Sin obligaciones fiscales</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fac_uso" class="form-label">Uso de CFDI <span class="required-mark">*</span></label>
                            <select id="fac_uso" name="uso_cfdi" class="form-select">
                                <option value="D10 - Pagos por servicios educativos (colegiaturas)" selected>D10 - Pagos por servicios educativos (colegiaturas)</option>
                                <option value="G03 - Gastos en general">G03 - Gastos en general</option>
                                <option value="CP01 - Pagos">CP01 - Pagos</option>
                                <option value="S01 - Sin efectos fiscales">S01 - Sin efectos fiscales</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid form-grid--3">
                        <div class="form-group">
                            <label for="fac_cp" class="form-label">Código Postal Fiscal <span class="required-mark">*</span></label>
                            <input type="text" id="fac_cp" name="codigo_postal_fiscal" class="form-control" placeholder="Ej. 72000" maxlength="5">
                        </div>

                        <div class="form-group form-group--span2">
                            <label for="fac_email" class="form-label">Correo para Envío de Facturas (PDF/XML)</label>
                            <input type="email" id="fac_email" name="correo_facturacion" class="form-control" placeholder="facturacion@ejemplo.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fac_domicilio" class="form-label">Domicilio Fiscal Completo</label>
                        <textarea id="fac_domicilio" name="domicilio_fiscal" class="form-control" rows="2" placeholder="Calle, número, colonia, municipio, estado"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. BARRA DE ACCIONES INFERIOR (Sticky) -->
        <div class="form-actions-bar">
            <div class="form-actions-bar__info">
                <span class="badge-status-dot"></span>
                <span>Listo para persistir expediente relacional en el sistema escolar ASRS.</span>
            </div>
            <div class="form-actions-bar__buttons">
                <button type="button" class="btn-reset-form" id="btn-reset-alta">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <span>Limpiar Campos</span>
                </button>

                <button type="submit" class="btn-submit-alta" id="btn-submit-alta">
                    <span class="btn-spinner" style="display: none;" id="submit-spinner"></span>
                    <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <polyline points="16 11 18 13 22 9"></polyline>
                    </svg>
                    <span id="btn-submit-text">Inscribir y Guardar Alumno</span>
                </button>
            </div>
        </div>

    </form>

</div>

<!-- Modal / Toast Flotante Dinámico -->
<div class="alumno-toast-container" id="alumno-toast-container"></div>
