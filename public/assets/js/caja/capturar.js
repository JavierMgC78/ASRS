/**
 * ==========================================================
 * ASRS FRAMEWORK - JS CAPTURAR PAGO (capturar.js)
 * Axe Secure Router System - Módulo de Caja y Tesorería
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const config = window.ASRS_CAJA || {
        endpointUrl: window.location.href,
        cajeroNombre: 'Cajero en Turno'
    };

    // -------------------------------------------------------------------------
    // ELEMENTOS DEL DOM
    // -------------------------------------------------------------------------
    const formPago               = document.getElementById('form-capturar-pago');
    const inputAlumnoId          = document.getElementById('input_alumno_id');
    const inputConceptoId        = document.getElementById('input_concepto_id');
    const inputConcepto          = document.getElementById('input_concepto');
    const inputMonto             = document.getElementById('input_monto');
    const selectFormaPago        = document.getElementById('select_forma_pago');
    const containerReferencia    = document.getElementById('container-referencia-bancaria');
    const inputReferencia        = document.getElementById('input_referencia');
    const formaPagoHint          = document.getElementById('forma-pago-hint');
    const inputFechaPago         = document.getElementById('input_fecha_pago');
    const inputHoraPago          = document.getElementById('input_hora_pago');
    const inputObservaciones     = document.getElementById('input_observaciones');

    // Búsqueda de alumnos
    const searchInput            = document.getElementById('alumno_search_input');
    const searchDropdown         = document.getElementById('search-results-dropdown');
    const searchList             = document.getElementById('search-results-list');
    const searchSpinner          = document.getElementById('search-spinner');
    const btnClearSearch         = document.getElementById('btn-clear-search');
    const alumnoCardSelected     = document.getElementById('alumno-selected-card');
    const btnChangeAlumno        = document.getElementById('btn-change-alumno');
    const alumnoSearchBox        = document.getElementById('alumno-search-box');
    const alumnoValidationMsg    = document.getElementById('alumno-validation-msg');

    // Datos del Alumno Seleccionado (Card en formulario)
    const selAvatarInitials      = document.getElementById('sel-avatar-initials');
    const selAlumnoIdLabel       = document.getElementById('sel-alumno-id-label');
    const selAlumnoNombre        = document.getElementById('sel-alumno-nombre');
    const selAlumnoCurp          = document.getElementById('sel-alumno-curp');
    const selAlumnoNivel         = document.getElementById('sel-alumno-nivel');
    const selAlumnoGradoGrupo    = document.getElementById('sel-alumno-grado-grupo');

    // Conceptos de Cobro (Selector Desplegable)
    const selectConcepto                 = document.getElementById('select_concepto');
    const containerConceptoPersonalizado = document.getElementById('container-concepto-personalizado');
    const inputConceptoPersonalizado     = document.getElementById('input_concepto_personalizado');
    const montoHint                      = document.getElementById('monto-hint');

    // Panel Lateral Inteligente
    const panelEmptyState        = document.getElementById('panel-empty-state');
    const panelLoadingState      = document.getElementById('panel-loading-state');
    const panelContentState      = document.getElementById('panel-content-state');
    
    // Campos del Panel Lateral
    const panelValNombre         = document.getElementById('panel-val-nombre');
    const panelValCurp           = document.getElementById('panel-val-curp');
    const panelValNivel          = document.getElementById('panel-val-nivel');
    const panelValGradoGrupo     = document.getElementById('panel-val-grado-grupo');
    const lineMatricula          = document.getElementById('line-matricula');
    const panelValMatricula      = document.getElementById('panel-val-matricula');
    const lineTelefonoAlumno     = document.getElementById('line-telefono-alumno');
    const panelValTelefonoAlumno = document.getElementById('panel-val-telefono-alumno');
    const lineEmailAlumno        = document.getElementById('line-email-alumno');
    const panelValEmailAlumno    = document.getElementById('panel-val-email-alumno');
    const panelValEstado         = document.getElementById('panel-val-estado');

    const panelTutoresContainer  = document.getElementById('panel-tutores-container');
    const panelFacturacionCont   = document.getElementById('panel-facturacion-container');
    const panelHistorialCont     = document.getElementById('panel-historial-container');
    const panelCardAlumno        = document.getElementById('panel-card-alumno');
    const btnToggleDatosAlumno   = document.getElementById('btn-toggle-datos-alumno');

    // Botones y Alertas
    const btnSubmitPago          = document.getElementById('btn-submit-pago');
    const btnSubmitSpinner       = document.getElementById('btn-submit-spinner');
    const btnSubmitIcon          = document.getElementById('btn-submit-icon');
    const btnSubmitText          = document.getElementById('btn-submit-text');
    const btnResetForm           = document.getElementById('btn-reset-form');

    // Modal de Comprobante
    const modalComprobante       = document.getElementById('modal-comprobante');
    const btnCerrarModal         = document.getElementById('btn-cerrar-modal');
    const btnImprimirRecibo      = document.getElementById('btn-imprimir-recibo');
    const btnNuevoPago           = document.getElementById('btn-nuevo-pago');

    // Campos del Voucher
    const vouchFolio             = document.getElementById('vouch-folio');
    const vouchFechaHora         = document.getElementById('vouch-fecha-hora');
    const vouchCajero            = document.getElementById('vouch-cajero');
    const vouchEstado            = document.getElementById('vouch-estado');
    const vouchAlumno            = document.getElementById('vouch-alumno');
    const vouchCurp              = document.getElementById('vouch-curp');
    const vouchGradoGrupo        = document.getElementById('vouch-grado-grupo');
    const vouchConcepto          = document.getElementById('vouch-concepto');
    const vouchFormaPago         = document.getElementById('vouch-forma-pago');
    const vouchReferencia        = document.getElementById('vouch-referencia');
    const vouchMonto             = document.getElementById('vouch-monto');
    const vouchTotal             = document.getElementById('vouch-total');
    const vouchNotas             = document.getElementById('vouch-notas');
    const vouchNotasContainer    = document.getElementById('vouch-notas-container');
    const vouchBarcodeText       = document.getElementById('vouch-barcode-text');

    // Variables de estado
    let searchDebounceTimer = null;
    let currentSelectedAlumno = null;

    // -------------------------------------------------------------------------
    // HELPER DE LIMPIEZA DE CADENAS (SIN NULOS NI UNDEFINED)
    // -------------------------------------------------------------------------
    function cleanVal(val) {
        if (val === null || val === undefined) return null;
        const str = String(val).trim();
        const lower = str.toLowerCase();
        if (str === '' || lower === 'null' || lower === 'undefined' || lower === 'none') {
            return null;
        }
        return str;
    }

    // -------------------------------------------------------------------------
    // 1. DINÁMICA DE FORMA DE PAGO Y REFERENCIA BANCARIA
    // -------------------------------------------------------------------------
    function actualizarDinamicaFormaPago() {
        const selectedOption = selectFormaPago.options[selectFormaPago.selectedIndex];
        if (!selectedOption) return;

        const esBancario = selectedOption.getAttribute('data-bancario') === '1';
        const valor = selectedOption.value;

        if (esBancario) {
            // Mostrar campo de referencia con animación y marcar como obligatorio
            containerReferencia.style.display = 'flex';
            inputReferencia.required = true;
            inputReferencia.setAttribute('aria-required', 'true');

            if (valor.includes('BBVA')) {
                inputReferencia.placeholder = 'Ej. BBVA-94820138 o Folio de Transferencia';
                formaPagoHint.textContent = 'Transferencia electrónica interbancaria o SPEI BBVA.';
            } else if (valor.includes('Tarjeta')) {
                inputReferencia.placeholder = 'Ej. AUT-847291 o Núm. de Autorización TPV';
                formaPagoHint.textContent = 'Cobro procesado mediante terminal punto de venta bancaria.';
            } else {
                inputReferencia.placeholder = 'Ej. DEP-482019 o Núm. de Ficha / Rastreo';
                formaPagoHint.textContent = 'Depósito bancario directo en sucursal o ventanilla.';
            }

            // Si el campo estaba vacío, no enfocamos de golpe para no desorientar,
            // pero si el usuario interactuó con el select, le damos foco
            if (document.activeElement === selectFormaPago) {
                inputReferencia.focus();
            }
        } else {
            // Es Efectivo: ocultar campo y remover obligatoriedad
            containerReferencia.style.display = 'none';
            inputReferencia.required = false;
            inputReferencia.removeAttribute('aria-required');
            inputReferencia.value = '';
            formaPagoHint.textContent = 'Pago directo en ventanilla en efectivo.';
        }
    }

    selectFormaPago.addEventListener('change', actualizarDinamicaFormaPago);
    actualizarDinamicaFormaPago(); // Ejecutar al cargar la vista

    // -------------------------------------------------------------------------
    // 2. AUTOMATIZACIÓN DE CONCEPTO Y MONTO SUGERIDO (SELECTOR DESPLEGABLE)
    // -------------------------------------------------------------------------
    selectConcepto.addEventListener('change', () => {
        const selectedOption = selectConcepto.options[selectConcepto.selectedIndex];
        if (!selectedOption) return;

        const val = selectedOption.value;

        if (val === 'OTRO') {
            // Desplegar campo de concepto personalizado
            containerConceptoPersonalizado.style.display = 'block';
            inputConceptoPersonalizado.required = true;
            inputConceptoPersonalizado.focus();
            inputConcepto.value = inputConceptoPersonalizado.value.trim();
            inputConceptoId.value = '';
            if (montoHint) {
                montoHint.textContent = 'Ingrese el monto correspondiente para este concepto.';
            }
        } else if (val !== '') {
            // Ocultar campo de concepto personalizado
            containerConceptoPersonalizado.style.display = 'none';
            inputConceptoPersonalizado.required = false;
            inputConcepto.value = val;

            const id = selectedOption.getAttribute('data-id');
            inputConceptoId.value = id || '';

            // Automatizar el monto oficial sugerido si está configurado
            const montoSugerido = parseFloat(selectedOption.getAttribute('data-monto'));
            if (!isNaN(montoSugerido) && montoSugerido > 0) {
                inputMonto.value = montoSugerido.toFixed(2);
                
                // Feedback visual sutil (pulso) para confirmar la carga automática
                inputMonto.classList.add('amount-updated');
                setTimeout(() => {
                    inputMonto.classList.remove('amount-updated');
                }, 450);

                if (montoHint) {
                    montoHint.textContent = `Precio oficial sugerido ($${montoSugerido.toFixed(2)} MXN). Editable libremente.`;
                }
            } else {
                inputMonto.value = '';
                if (montoHint) {
                    montoHint.textContent = 'Ingrese el importe a cobrar en ventanilla.';
                }
            }
        } else {
            containerConceptoPersonalizado.style.display = 'none';
            inputConcepto.value = '';
            inputConceptoId.value = '';
        }
    });

    // Sincronizar texto si se usa concepto personalizado
    inputConceptoPersonalizado.addEventListener('input', () => {
        inputConcepto.value = inputConceptoPersonalizado.value.trim();
    });

    // -------------------------------------------------------------------------
    // 3. BÚSQUEDA DE ALUMNO POR APELLIDO PATERNO EN TIEMPO REAL (DEBOUNCE FLUIDO)
    // -------------------------------------------------------------------------
    let searchAbortController = null;
    let highlightedIndex = -1;

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();

        if (query.length > 0) {
            btnClearSearch.style.display = 'flex';
        } else {
            btnClearSearch.style.display = 'none';
            ocultarResultadosBusqueda();
            searchSpinner.style.display = 'none';
            if (searchAbortController) {
                searchAbortController.abort();
            }
            return;
        }

        clearTimeout(searchDebounceTimer);
        searchSpinner.style.display = 'block';

        // Debounce fluido optimizado (200 ms)
        searchDebounceTimer = setTimeout(() => {
            ejecutarBusquedaAlumnos(query);
        }, 200);
    });

    // Control de teclado para no enviar el formulario y permitir navegación accesible
    searchInput.addEventListener('keydown', (e) => {
        const items = searchList.querySelectorAll('.search-result-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                highlightedIndex = (highlightedIndex + 1) % items.length;
                actualizarItemResaltado(items);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
                actualizarItemResaltado(items);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault(); // Evitar envío accidental del formulario de pago
            if (items.length > 0) {
                if (highlightedIndex >= 0 && highlightedIndex < items.length) {
                    items[highlightedIndex].click();
                } else {
                    items[0].click();
                }
            }
        } else if (e.key === 'Escape') {
            ocultarResultadosBusqueda();
        }
    });

    function actualizarItemResaltado(items) {
        items.forEach((item, idx) => {
            if (idx === highlightedIndex) {
                item.classList.add('is-highlighted');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('is-highlighted');
            }
        });
    }

    btnClearSearch.addEventListener('click', () => {
        searchInput.value = '';
        btnClearSearch.style.display = 'none';
        ocultarResultadosBusqueda();
        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchSpinner.style.display = 'none';
        searchInput.focus();
    });

    function ejecutarBusquedaAlumnos(query) {
        // Cancelar petición anterior si aún estaba en tránsito
        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchAbortController = new AbortController();

        const url = `${config.endpointUrl}?action=search_alumnos&query=${encodeURIComponent(query)}`;

        fetch(url, {
            signal: searchAbortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(response => {
            searchSpinner.style.display = 'none';
            highlightedIndex = -1;

            if (response.success && Array.isArray(response.data)) {
                renderizarResultadosBusqueda(response.data, query);
            } else {
                renderizarResultadosBusqueda([], query);
            }
        })
        .catch(err => {
            if (err.name === 'AbortError') return; // Cancelación esperada por tecleo rápido
            console.error('Error en búsqueda de alumnos:', err);
            searchSpinner.style.display = 'none';
            renderizarResultadosBusqueda([], query);
        });
    }

    function renderizarResultadosBusqueda(alumnos, term) {
        searchList.innerHTML = '';
        highlightedIndex = -1;

        if (alumnos.length === 0) {
            const emptyLi = document.createElement('li');
            emptyLi.className = 'search-empty-notice';
            emptyLi.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 4px 0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <span>No se encontraron alumnos con el apellido "<strong>${escapeHtml(term)}</strong>".</span>
                </div>
            `;
            searchList.appendChild(emptyLi);
            searchDropdown.style.display = 'block';
            return;
        }

        const safeTerm = escapeRegExp(term);
        const regex = new RegExp(`(${safeTerm})`, 'gi');

        alumnos.forEach((alumno, index) => {
            const li = document.createElement('li');
            li.className = 'search-result-item';
            li.setAttribute('role', 'option');
            li.setAttribute('data-index', index);

            const paterno = alumno.primer_apellido || '';
            const materno = alumno.segundo_apellido || '';
            const nombres = alumno.nombre || '';
            const curp    = alumno.curp || '';
            const grado   = alumno.grado || '';
            const grupo   = alumno.grupo || '';
            const nivel   = alumno.nivel_educativo || '';

            // Resaltar apellido paterno y nombres
            const paternoDestacado = paterno.replace(regex, '<strong>$1</strong>');
            const nombresDestacados = nombres.replace(regex, '<strong>$1</strong>');

            li.innerHTML = `
                <div class="search-result-item__main">
                    <div class="search-result-name">
                        ${paternoDestacado} ${escapeHtml(materno)} ${nombresDestacados}
                    </div>
                    <div class="search-result-meta">
                        <span>CURP: <code>${escapeHtml(curp)}</code></span>
                        <span>&bull;</span>
                        <span>${escapeHtml(nivel)} - ${escapeHtml(grado)} ${escapeHtml(grupo)}</span>
                    </div>
                </div>
                <div class="search-result-badge">
                    ID #${alumno.id}
                </div>
            `;

            li.addEventListener('click', () => {
                seleccionarAlumno(alumno);
            });

            searchList.appendChild(li);
        });

        searchDropdown.style.display = 'block';
    }

    function ocultarResultadosBusqueda() {
        searchDropdown.style.display = 'none';
    }

    // Cerrar dropdown al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!alumnoSearchBox.contains(e.target)) {
            ocultarResultadosBusqueda();
        }
    });

    // -------------------------------------------------------------------------
    // 4. SELECCIÓN DE ALUMNO Y CARGA EN EL PANEL INTELIGENTE
    // -------------------------------------------------------------------------
    function seleccionarAlumno(alumno) {
        currentSelectedAlumno = alumno;
        inputAlumnoId.value = alumno.id;

        // Ocultar buscador e inputs
        ocultarResultadosBusqueda();
        alumnoSearchBox.style.display = 'none';
        alumnoValidationMsg.style.display = 'none';

        // Llenar card de alumno seleccionado
        const paterno = alumno.primer_apellido || '';
        const materno = alumno.segundo_apellido || '';
        const nombres = alumno.nombre || '';
        const iniciales = ((paterno[0] || '') + (nombres[0] || '')).toUpperCase() || 'AL';

        selAvatarInitials.textContent = iniciales;
        selAlumnoIdLabel.textContent = `ID #${alumno.id}`;
        selAlumnoNombre.textContent = `${paterno} ${materno} ${nombres}`.trim();
        selAlumnoCurp.textContent = alumno.curp || '--';
        selAlumnoNivel.textContent = alumno.nivel_educativo || '--';
        selAlumnoGradoGrupo.textContent = `${alumno.grado || ''} ${alumno.grupo || ''}`.trim() || '--';

        alumnoCardSelected.style.display = 'flex';

        // Si el objeto alumno ya contiene los tutores y facturación limpia desde la búsqueda, renderizar de inmediato
        if (alumno.tutores && alumno.facturacion) {
            renderizarPanelLateral({
                alumno: alumno,
                tutores: alumno.tutores,
                facturacion: alumno.facturacion,
                historial: []
            });
            panelEmptyState.style.display = 'none';
            panelLoadingState.style.display = 'none';
            panelContentState.style.display = 'flex';
        }

        // Asegurar que todos los acordeones del panel estén expandidos al seleccionar
        document.querySelectorAll('.panel-card--collapsible').forEach(card => {
            card.classList.add('is-expanded');
            const btn = card.querySelector('.panel-card__header-btn');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        });

        // Cargar ficha completa del alumno y tutores para sincronizar historial
        cargarFichaCompletaAlumno(alumno.id);
    }

    btnChangeAlumno.addEventListener('click', () => {
        resetSeleccionAlumno();
        searchInput.focus();
    });

    function resetSeleccionAlumno() {
        currentSelectedAlumno = null;
        inputAlumnoId.value = '';
        alumnoCardSelected.style.display = 'none';
        alumnoSearchBox.style.display = 'block';
        searchInput.value = '';
        btnClearSearch.style.display = 'none';

        // Regresar panel lateral al estado inicial
        panelContentState.style.display = 'none';
        panelLoadingState.style.display = 'none';
        panelEmptyState.style.display = 'flex';
    }

    // -------------------------------------------------------------------------
    // 5. CARGA ASÍNCRONA DEL PANEL LATERAL INTELIGENTE (SIN NULOS)
    // -------------------------------------------------------------------------
    function cargarFichaCompletaAlumno(alumnoId) {
        panelEmptyState.style.display = 'none';
        panelContentState.style.display = 'none';
        panelLoadingState.style.display = 'flex';

        const url = `${config.endpointUrl}?action=get_alumno_info&alumno_id=${alumnoId}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(response => {
            panelLoadingState.style.display = 'none';

            if (response.success && response.data) {
                renderizarPanelLateral(response.data);
                panelContentState.style.display = 'flex';
            } else {
                panelEmptyState.style.display = 'flex';
            }
        })
        .catch(err => {
            console.error('Error al cargar ficha del alumno:', err);
            panelLoadingState.style.display = 'none';
            panelEmptyState.style.display = 'flex';
        });
    }

    /**
     * RENDERIZADOR INTELIGENTE (LIMPIEZA ESTRICTA: CERO VALORES NULOS O UNDEFINED)
     */
    function renderizarPanelLateral(data) {
        const alumno = data.alumno || {};
        const tutores = Array.isArray(data.tutores) ? data.tutores : [];
        const facturacion = data.facturacion || {};
        const historial = Array.isArray(data.historial) ? data.historial : [];

        // --- A) DATOS DEL ALUMNO ---
        panelValNombre.textContent = cleanVal(alumno.nombre_completo) || cleanVal(alumno.nombre) || '--';
        panelValCurp.textContent   = cleanVal(alumno.curp) || '--';
        panelValNivel.textContent  = cleanVal(alumno.nivel_educativo) || '--';

        const gradoGrupo = `${cleanVal(alumno.grado) || ''} ${cleanVal(alumno.grupo) || ''}`.trim();
        panelValGradoGrupo.textContent = gradoGrupo || '--';

        // Campos condicionales (ocultar si son nulos)
        const matricula = cleanVal(alumno.matricula);
        if (matricula) {
            panelValMatricula.textContent = matricula;
            lineMatricula.style.display = 'flex';
        } else {
            lineMatricula.style.display = 'none';
        }

        const telAlumno = cleanVal(alumno.telefono);
        if (telAlumno) {
            panelValTelefonoAlumno.textContent = telAlumno;
            lineTelefonoAlumno.style.display = 'flex';
        } else {
            lineTelefonoAlumno.style.display = 'none';
        }

        const emailAlumno = cleanVal(alumno.email);
        if (emailAlumno) {
            panelValEmailAlumno.textContent = emailAlumno;
            lineEmailAlumno.style.display = 'flex';
        } else {
            lineEmailAlumno.style.display = 'none';
        }

        const estado = cleanVal(alumno.estado_alumno) || 'Inscrito';
        panelValEstado.textContent = estado.toUpperCase();
        panelValEstado.className = `panel-badge ${estado === 'activo' || estado === 'inscrito' ? 'badge-tag--success' : 'badge-tag'}`;

        // --- B) TUTORES REGISTRADOS (FILTRADO INTELIGENTE SIN NULOS) ---
        panelTutoresContainer.innerHTML = '';

        if (tutores.length === 0) {
            panelTutoresContainer.innerHTML = `
                <div class="panel-empty-text">Sin tutores registrados en el expediente escolar.</div>
            `;
        } else {
            tutores.forEach((tutor, idx) => {
                const nombre = cleanVal(tutor.nombre_completo);
                if (!nombre) return; // Omitir si no tiene nombre

                const block = document.createElement('div');
                block.className = 'tutor-block';

                const parentesco = cleanVal(tutor.parentesco) || 'Tutor';
                const esResponsable = tutor.es_responsable_economico;

                let headHtml = `
                    <div class="tutor-block__head">
                        <span class="tutor-block__name">${escapeHtml(nombre)}</span>
                        <span class="tutor-block__rel">${escapeHtml(parentesco)}</span>
                    </div>
                `;

                if (esResponsable) {
                    headHtml += `
                        <div>
                            <span class="badge-responsable">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Responsable Económico
                            </span>
                        </div>
                    `;
                }

                // Generar líneas SOLO si existen valores limpios (sin nulos)
                let linesHtml = '';

                const telPrinc = cleanVal(tutor.telefono_principal);
                if (telPrinc) {
                    linesHtml += `
                        <div class="panel-line">
                            <span class="panel-label">Tel. Principal:</span>
                            <span class="panel-value">${escapeHtml(telPrinc)}</span>
                        </div>
                    `;
                }

                const telSec = cleanVal(tutor.telefono_secundario);
                if (telSec) {
                    linesHtml += `
                        <div class="panel-line">
                            <span class="panel-label">Tel. Secundario:</span>
                            <span class="panel-value">${escapeHtml(telSec)}</span>
                        </div>
                    `;
                }

                const email = cleanVal(tutor.email);
                if (email) {
                    linesHtml += `
                        <div class="panel-line">
                            <span class="panel-label">Correo:</span>
                            <span class="panel-value">${escapeHtml(email)}</span>
                        </div>
                    `;
                }

                const ocupacion = cleanVal(tutor.ocupacion);
                if (ocupacion) {
                    linesHtml += `
                        <div class="panel-line">
                            <span class="panel-label">Ocupación:</span>
                            <span class="panel-value">${escapeHtml(ocupacion)}</span>
                        </div>
                    `;
                }

                const lugarTrabajo = cleanVal(tutor.lugar_trabajo);
                if (lugarTrabajo) {
                    linesHtml += `
                        <div class="panel-line">
                            <span class="panel-label">Lugar de Trabajo:</span>
                            <span class="panel-value">${escapeHtml(lugarTrabajo)}</span>
                        </div>
                    `;
                }

                block.innerHTML = headHtml + linesHtml;
                panelTutoresContainer.appendChild(block);
            });
        }

        // --- C) DATOS DE FACTURACIÓN (FILTRADO INTELIGENTE SIN NULOS) ---
        panelFacturacionCont.innerHTML = '';

        const requiereFactura = (facturacion.requiere_factura === true || facturacion.requiere_factura === 1);
        const rfc = cleanVal(facturacion.rfc);
        const razonSocial = cleanVal(facturacion.razon_social);

        if (requiereFactura && (rfc || razonSocial)) {
            let factHtml = `
                <div class="panel-line">
                    <span class="panel-label">Estado Fiscal:</span>
                    <span class="panel-badge badge-tag--success">Requiere Factura CFDI</span>
                </div>
            `;

            if (razonSocial) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">Razón Social:</span>
                        <span class="panel-value font-medium">${escapeHtml(razonSocial)}</span>
                    </div>
                `;
            }

            if (rfc) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">RFC:</span>
                        <span class="panel-value font-bold">${escapeHtml(rfc)}</span>
                    </div>
                `;
            }

            const regimen = cleanVal(facturacion.regimen_fiscal);
            if (regimen) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">Régimen Fiscal:</span>
                        <span class="panel-value">${escapeHtml(regimen)}</span>
                    </div>
                `;
            }

            const usoCfdi = cleanVal(facturacion.uso_cfdi);
            if (usoCfdi) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">Uso de CFDI:</span>
                        <span class="panel-value">${escapeHtml(usoCfdi)}</span>
                    </div>
                `;
            }

            const correoFact = cleanVal(facturacion.correo_facturacion);
            if (correoFact) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">Correo Fiscal:</span>
                        <span class="panel-value">${escapeHtml(correoFact)}</span>
                    </div>
                `;
            }

            const cpFiscal = cleanVal(facturacion.codigo_postal_fiscal);
            if (cpFiscal) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">C.P. Fiscal:</span>
                        <span class="panel-value">${escapeHtml(cpFiscal)}</span>
                    </div>
                `;
            }

            const domicilio = cleanVal(facturacion.domicilio_fiscal);
            if (domicilio) {
                factHtml += `
                    <div class="panel-line">
                        <span class="panel-label">Domicilio Fiscal:</span>
                        <span class="panel-value">${escapeHtml(domicilio)}</span>
                    </div>
                `;
            }

            panelFacturacionCont.innerHTML = factHtml;
        } else {
            panelFacturacionCont.innerHTML = `
                <div class="facturacion-no-requiere">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>No requiere factura fiscal. Emisión contable para Público en General.</span>
                </div>
            `;
        }

        // --- D) HISTORIAL RECIENTE ---
        panelHistorialCont.innerHTML = '';
        if (historial.length === 0) {
            panelHistorialCont.innerHTML = `<div class="panel-empty-text">Sin pagos previos registrados.</div>`;
        } else {
            historial.forEach(p => {
                const item = document.createElement('div');
                item.className = 'historial-item';
                const montoFormat = parseFloat(p.monto || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });
                item.innerHTML = `
                    <div class="historial-item__left">
                        <span class="historial-folio">${escapeHtml(p.folio || '')}</span>
                        <span class="historial-concepto">${escapeHtml(p.concepto || '')} &bull; ${escapeHtml(p.fecha_pago || '')}</span>
                    </div>
                    <div class="historial-monto">
                        $${montoFormat}
                    </div>
                `;
                panelHistorialCont.appendChild(item);
            });
        }
    }

    // -------------------------------------------------------------------------
    // 5.1 COMPONENTE ACORDEÓN: ALTERNAR VISIBILIDAD DE TODAS LAS CAJAS
    // -------------------------------------------------------------------------
    document.querySelectorAll('.panel-card--collapsible').forEach(card => {
        const btn = card.querySelector('.panel-card__header-btn');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const isCurrentlyExpanded = card.classList.contains('is-expanded');
                if (isCurrentlyExpanded) {
                    card.classList.remove('is-expanded');
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    card.classList.add('is-expanded');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        }
    });

    // -------------------------------------------------------------------------
    // 6. PROCESAMIENTO Y PERSISTENCIA DEL PAGO (PDO)
    // -------------------------------------------------------------------------
    formPago.addEventListener('submit', (e) => {
        e.preventDefault();

        // 1. Validar que se haya seleccionado un alumno
        const alumnoId = parseInt(inputAlumnoId.value, 10);
        if (!alumnoId || isNaN(alumnoId) || alumnoId <= 0) {
            alumnoValidationMsg.style.display = 'block';
            searchInput.focus();
            window.scrollTo({ top: formPago.offsetTop - 40, behavior: 'smooth' });
            return;
        }

        // 2. Validar y sincronizar concepto de cobro
        let concepto = inputConcepto.value.trim();
        if (selectConcepto && selectConcepto.value === 'OTRO') {
            concepto = inputConceptoPersonalizado ? inputConceptoPersonalizado.value.trim() : '';
            inputConcepto.value = concepto;
        } else if (selectConcepto && selectConcepto.value && selectConcepto.value !== '') {
            concepto = selectConcepto.value;
            inputConcepto.value = concepto;
        }

        if (!concepto) {
            mostrarAlertaError('Debe seleccionar o especificar un concepto de cobro escolar.');
            if (selectConcepto && selectConcepto.value === 'OTRO' && inputConceptoPersonalizado) {
                inputConceptoPersonalizado.focus();
            } else if (selectConcepto) {
                selectConcepto.focus();
            }
            return;
        }

        // 3. Validar monto
        const monto = parseFloat(inputMonto.value);
        if (isNaN(monto) || monto <= 0) {
            mostrarAlertaError('Ingrese un monto válido mayor a $0.00.');
            inputMonto.focus();
            return;
        }

        // 4. Validar referencia bancaria si es forma de pago bancaria
        const selectedOption = selectFormaPago.options[selectFormaPago.selectedIndex];
        const esBancario = selectedOption && selectedOption.getAttribute('data-bancario') === '1';
        const referencia = inputReferencia.value.trim();

        if (esBancario && !referencia) {
            mostrarAlertaError(`El campo Referencia / Folio Bancario es obligatorio para ${selectedOption.value}.`);
            inputReferencia.focus();
            return;
        }

        // 5. Preparar datos para envío AJAX
        const formData = new FormData(formPago);
        formData.append('action', 'guardar_pago');

        // Estado visual del botón durante la persistencia
        btnSubmitSpinner.style.display = 'inline-block';
        btnSubmitIcon.style.display = 'none';
        btnSubmitText.textContent = 'Procesando transacción...';
        btnSubmitPago.disabled = true;

        fetch(config.endpointUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            restaurarBotonSubmit();

            if (body.success && body.data) {
                mostrarComprobanteOficial(body.data);
                // Si el alumno está activo, recargar su panel lateral para actualizar el historial
                if (alumnoId) {
                    cargarFichaCompletaAlumno(alumnoId);
                }
            } else {
                mostrarAlertaError(body.message || 'Error al registrar el pago en la base de datos.');
            }
        })
        .catch(err => {
            console.error('Error al guardar el pago:', err);
            restaurarBotonSubmit();
            mostrarAlertaError('Error de conexión o fallo interno al guardar la transacción.');
        });
    });

    function restaurarBotonSubmit() {
        btnSubmitSpinner.style.display = 'none';
        btnSubmitIcon.style.display = 'inline-block';
        btnSubmitText.textContent = 'Procesar y Emitir Pago';
        btnSubmitPago.disabled = false;
    }

    // -------------------------------------------------------------------------
    // 7. COMPROBANTE OFICIAL (MODAL Y VOUCHER DE PAGO)
    // -------------------------------------------------------------------------
    function mostrarComprobanteOficial(pago) {
        vouchFolio.textContent       = pago.folio || 'REC-XXXX';
        vouchFechaHora.textContent   = `${pago.fecha_pago || ''} ${pago.hora_pago || ''}`.trim();
        vouchCajero.textContent      = pago.cajero_nombre || config.cajeroNombre;
        vouchEstado.textContent      = 'Completado';
        vouchAlumno.textContent      = pago.alumno_nombre || '--';
        vouchCurp.textContent        = pago.alumno_curp || '--';

        const gradoGrupo = (currentSelectedAlumno ? `${currentSelectedAlumno.grado || ''} ${currentSelectedAlumno.grupo || ''}` : '--');
        vouchGradoGrupo.textContent  = gradoGrupo;

        vouchConcepto.textContent    = pago.concepto || '--';
        vouchFormaPago.textContent   = pago.forma_pago || '--';
        vouchReferencia.textContent  = pago.referencia || 'N/A';
        vouchMonto.textContent       = pago.monto_formateado || `$ ${pago.monto}`;
        vouchTotal.textContent       = `${pago.monto_formateado || '$ ' + pago.monto} MXN`;
        vouchBarcodeText.textContent = `CEA-${pago.folio || '0000'}`;

        const obs = inputObservaciones.value.trim();
        if (obs) {
            vouchNotas.textContent = obs;
            vouchNotasContainer.style.display = 'block';
        } else {
            vouchNotasContainer.style.display = 'none';
        }

        modalComprobante.style.display = 'flex';
    }

    btnCerrarModal.addEventListener('click', () => {
        modalComprobante.style.display = 'none';
    });

    btnImprimirRecibo.addEventListener('click', () => {
        window.print();
    });

    btnNuevoPago.addEventListener('click', () => {
        modalComprobante.style.display = 'none';
        limpiarFormularioCompleto();
    });

    btnResetForm.addEventListener('click', () => {
        setTimeout(limpiarFormularioCompleto, 10);
    });

    function limpiarFormularioCompleto() {
        formPago.reset();
        resetSeleccionAlumno();
        if (selectConcepto) selectConcepto.selectedIndex = 0;
        if (containerConceptoPersonalizado) containerConceptoPersonalizado.style.display = 'none';
        if (inputConceptoPersonalizado) inputConceptoPersonalizado.value = '';
        if (inputConcepto) inputConcepto.value = '';
        if (inputConceptoId) inputConceptoId.value = '';
        if (inputMonto) inputMonto.value = '';
        if (montoHint) montoHint.textContent = 'Importe oficial sugerido (editable libremente).';
        inputFechaPago.value = config.fechaActual || '';
        inputHoraPago.value = config.horaActual || '';
        actualizarDinamicaFormaPago();
    }

    function mostrarAlertaError(msg) {
        const alertBox = document.getElementById('caja-alert-container');
        if (!alertBox) return;

        alertBox.innerHTML = `
            <div class="caja-alert caja-alert--danger" role="alert">
                <div class="caja-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="caja-alert__content">
                    <div class="caja-alert__title">Atención requerida</div>
                    <div class="caja-alert__msg">${escapeHtml(msg)}</div>
                </div>
                <button type="button" class="caja-alert__close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        `;
        window.scrollTo({ top: alertBox.offsetTop - 20, behavior: 'smooth' });
    }

    // -------------------------------------------------------------------------
    // HELPERS DE UTILIDAD
    // -------------------------------------------------------------------------
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
});
