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

    // Renglones Dinámicos de Cobro (Sección 2)
    const rowsContainer          = document.getElementById('caja-conceptos-rows');
    const btnAddRow              = document.getElementById('btn-add-row');
    const totalAmountEl          = document.getElementById('caja-total-amount');
    const totalCountEl           = document.getElementById('caja-total-count');
    const inputDesglose          = document.getElementById('input_desglose_conceptos');
    const catalogoConceptos      = config.conceptosCatalogo || window.ASRS_CONCEPTOS_CATALOGO || [];

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
    // 2. GESTIÓN DE RENGLONES DINÁMICOS DE COBRO (CONCEPTOS Y MONTOS ALINEADOS)
    // -------------------------------------------------------------------------
    function crearRenglonConcepto({ concepto = '', monto = '', conceptoId = '', careId = null, isCustom = false, focus = false } = {}) {
        if (!rowsContainer) return null;

        const row = document.createElement('div');
        row.className = `caja-concepto-row ${careId ? 'is-care-row' : ''}`;
        if (careId) {
            row.setAttribute('data-care-id', String(careId));
        }

        const rowId = 'row_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
        row.setAttribute('data-row-id', rowId);

        // Opciones del select del catálogo institucional
        let optionsHtml = '';
        let matchesCatalogo = false;
        catalogoConceptos.forEach(c => {
            const selected = (concepto && c.nombre === concepto) || (conceptoId && String(c.id) === String(conceptoId));
            if (selected) matchesCatalogo = true;
            const montoAttr = Number(c.monto_sugerido || 0);
            const montoTexto = montoAttr > 0 ? ` — $${montoAttr.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '';
            optionsHtml += `<option value="${escapeHtml(c.nombre)}" data-id="${c.id}" data-monto="${montoAttr}" ${selected ? 'selected' : ''}>${escapeHtml(c.nombre)}${montoTexto}</option>`;
        });

        const isCustomMode = isCustom || Boolean(careId) || (concepto !== '' && !matchesCatalogo);

        row.innerHTML = `
            <div class="row-cell row-cell--num">
                <span class="row-num-badge">1</span>
            </div>
            <div class="row-cell row-cell--concepto">
                <div class="row-concepto-wrapper">
                    <select class="form-control form-select row-select-concepto" aria-label="Concepto de cobro">
                        <option value="" data-id="" data-monto="" ${(!concepto && !isCustomMode) ? 'selected' : ''} disabled>-- Seleccione un concepto escolar --</option>
                        ${optionsHtml}
                        <option value="OTRO" data-id="" data-monto="" ${isCustomMode ? 'selected' : ''}>Otro concepto personalizado...</option>
                    </select>
                    <div class="row-custom-wrapper" style="${isCustomMode ? 'display: flex;' : 'display: none;'}">
                        <input type="text" class="form-control row-input-custom" placeholder="Especifique el concepto de cobro..." value="${escapeHtml(concepto)}">
                        ${careId ? '<span class="row-care-badge" title="Cargo CARE de servicios eventuales">CARE</span>' : ''}
                    </div>
                </div>
            </div>
            <div class="row-cell row-cell--monto">
                <div class="input-money-wrapper">
                    <span class="input-money-symbol">$</span>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control--amount row-input-monto" placeholder="0.00" value="${monto ? Number(monto).toFixed(2) : ''}" required>
                </div>
            </div>
            <div class="row-cell row-cell--actions">
                <button type="button" class="btn-remove-row" title="Eliminar este concepto" aria-label="Eliminar renglón">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </button>
            </div>
        `;

        rowsContainer.appendChild(row);

        // Referencias del renglón
        const select = row.querySelector('.row-select-concepto');
        const customWrapper = row.querySelector('.row-custom-wrapper');
        const customInput = row.querySelector('.row-input-custom');
        const montoInput = row.querySelector('.row-input-monto');
        const btnRemove = row.querySelector('.btn-remove-row');

        select.addEventListener('change', () => {
            const val = select.value;
            if (val === 'OTRO') {
                customWrapper.style.display = 'flex';
                customInput.required = true;
                customInput.focus();
            } else {
                customWrapper.style.display = 'none';
                customInput.required = false;

                // Si tenía etiqueta CARE y se cambia a un concepto regular, desmarcar
                if (row.classList.contains('is-care-row')) {
                    row.classList.remove('is-care-row');
                    const careIdOld = row.getAttribute('data-care-id');
                    row.removeAttribute('data-care-id');
                    const badge = row.querySelector('.row-care-badge');
                    if (badge) badge.remove();
                    if (careIdOld) {
                        document.dispatchEvent(new CustomEvent('caja:care_row_removed', { detail: { careId: careIdOld } }));
                    }
                }

                const opt = select.options[select.selectedIndex];
                const sugerido = opt ? parseFloat(opt.getAttribute('data-monto')) : 0;
                if (!isNaN(sugerido) && sugerido > 0) {
                    montoInput.value = sugerido.toFixed(2);
                    montoInput.classList.add('amount-updated');
                    setTimeout(() => montoInput.classList.remove('amount-updated'), 450);
                }
            }
            recalcularTotales();
        });

        customInput.addEventListener('input', recalcularTotales);
        montoInput.addEventListener('input', recalcularTotales);

        btnRemove.addEventListener('click', () => {
            const totalRows = rowsContainer.querySelectorAll('.caja-concepto-row').length;
            const careIdOld = row.getAttribute('data-care-id');

            if (totalRows > 1) {
                row.remove();
            } else {
                // Si es la única fila, limpiar campos en lugar de borrar la estructura
                select.selectedIndex = 0;
                customWrapper.style.display = 'none';
                customInput.value = '';
                montoInput.value = '';
                row.classList.remove('is-care-row');
                row.removeAttribute('data-care-id');
                const badge = row.querySelector('.row-care-badge');
                if (badge) badge.remove();
            }

            if (careIdOld) {
                document.dispatchEvent(new CustomEvent('caja:care_row_removed', { detail: { careId: careIdOld } }));
            }

            renumerarFilas();
            recalcularTotales();
        });

        renumerarFilas();

        if (focus) {
            select.focus();
        }

        return row;
    }

    function renumerarFilas() {
        if (!rowsContainer) return;
        const rows = rowsContainer.querySelectorAll('.caja-concepto-row');
        rows.forEach((r, idx) => {
            const badge = r.querySelector('.row-num-badge');
            if (badge) badge.textContent = idx + 1;
        });
    }

    function obtenerDatosRenglones() {
        if (!rowsContainer) return [];
        const rows = rowsContainer.querySelectorAll('.caja-concepto-row');
        const items = [];

        rows.forEach(r => {
            const select = r.querySelector('.row-select-concepto');
            const customInput = r.querySelector('.row-input-custom');
            const montoInput = r.querySelector('.row-input-monto');
            const careId = r.getAttribute('data-care-id');

            const opt = select ? select.options[select.selectedIndex] : null;
            let nombre = '';
            let conceptoId = opt ? opt.getAttribute('data-id') : null;

            if (select && select.value === 'OTRO') {
                nombre = customInput ? customInput.value.trim() : '';
                conceptoId = null;
            } else if (opt && opt.value !== '') {
                nombre = opt.value;
            } else if (customInput && customInput.value.trim() !== '') {
                nombre = customInput.value.trim();
            }

            const monto = montoInput ? parseFloat(montoInput.value) : 0;
            const montoValido = !isNaN(monto) && monto > 0;

            items.push({
                concepto: nombre,
                conceptoId: conceptoId || null,
                monto: montoValido ? monto : 0,
                careId: careId || null,
                hasData: Boolean(nombre || montoValido)
            });
        });

        return items;
    }

    function recalcularTotales() {
        const items = obtenerDatosRenglones();
        const activos = items.filter(it => it.hasData && it.monto > 0);

        const totalSum = activos.reduce((acc, it) => acc + it.monto, 0);
        const totalCount = activos.length;

        // Actualizar UI del total general
        if (totalAmountEl) {
            totalAmountEl.textContent = totalSum.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (totalCountEl) {
            totalCountEl.textContent = `${totalCount} concepto${totalCount === 1 ? '' : 's'} activo${totalCount === 1 ? '' : 's'}`;
        }

        // Sincronizar inputs ocultos principales para backend
        if (inputMonto) {
            inputMonto.value = totalSum > 0 ? totalSum.toFixed(2) : '';
        }

        const nombresUnicos = activos.map(it => it.concepto).filter(Boolean);
        const conceptoConsolidado = nombresUnicos.length > 0 ? nombresUnicos.join(' + ') : '';

        if (inputConcepto) {
            inputConcepto.value = conceptoConsolidado.slice(0, 150);
        }
        if (inputConceptoId) {
            const primerId = activos.find(it => it.conceptoId)?.conceptoId;
            inputConceptoId.value = primerId || '';
        }

        // Desglose de renglones para observaciones y auditoría
        const lineasDesglose = activos.map(it => `• ${it.concepto}: $${it.monto.toFixed(2)} MXN`);
        if (inputDesglose) {
            inputDesglose.value = lineasDesglose.length > 0 ? `Desglose de cobro:\n${lineasDesglose.join('\n')}` : '';
        }

        // Sincronizar IDs de CARE activos en las filas
        const careIdsActivos = items.map(it => it.careId).filter(Boolean);
        let ceaHidden = document.getElementById('cea_cargos_ids');
        if (!ceaHidden && formPago) {
            ceaHidden = document.createElement('input');
            ceaHidden.type = 'hidden';
            ceaHidden.name = 'cea_cargos_ids';
            ceaHidden.id = 'cea_cargos_ids';
            formPago.appendChild(ceaHidden);
        }
        if (ceaHidden) {
            ceaHidden.value = careIdsActivos.join(',');
        }

        // Notificar a módulos externos (como el modal de cargos CARE)
        document.dispatchEvent(new CustomEvent('caja:care_rows_changed', {
            detail: {
                activeCareIds: careIdsActivos,
                totalSum: totalSum,
                totalCount: totalCount
            }
        }));
    }

    // Botón para añadir nuevo renglón (+)
    if (btnAddRow) {
        btnAddRow.addEventListener('click', () => {
            crearRenglonConcepto({ focus: true });
            recalcularTotales();
        });
    }

    // Exponer API en window.ASRS_CAJA para integración con módulos como CARE
    window.ASRS_CAJA = window.ASRS_CAJA || {};
    window.ASRS_CAJA.agregarRenglon = function(datos) {
        if (!rowsContainer) return null;
        const filas = rowsContainer.querySelectorAll('.caja-concepto-row');
        if (filas.length === 1) {
            const primera = filas[0];
            const sel = primera.querySelector('.row-select-concepto');
            const m = primera.querySelector('.row-input-monto');
            const cust = primera.querySelector('.row-input-custom');
            if ((!sel || !sel.value) && (!m || !m.value) && (!cust || !cust.value)) {
                primera.remove();
            }
        }
        const r = crearRenglonConcepto(datos);
        recalcularTotales();
        return r;
    };

    window.ASRS_CAJA.obtenerRenglones = obtenerDatosRenglones;
    window.ASRS_CAJA.recalcularTotales = recalcularTotales;

    // Inicializar con un renglón por defecto
    if (rowsContainer && rowsContainer.children.length === 0) {
        crearRenglonConcepto({ focus: false });
        recalcularTotales();
    }

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

        // 2. Validar que haya al menos un renglón con concepto y monto válido
        const renglones = obtenerDatosRenglones();
        const renglonesValidos = renglones.filter(r => r.concepto && r.monto > 0);

        if (renglonesValidos.length === 0) {
            mostrarAlertaError('Debe ingresar al menos un concepto de cobro con un monto válido mayor a $0.00.');
            const primeraFila = rowsContainer ? rowsContainer.querySelector('.caja-concepto-row') : null;
            if (primeraFila) {
                const sel = primeraFila.querySelector('.row-select-concepto');
                if (sel) sel.focus();
            }
            return;
        }

        recalcularTotales();

        // 3. Validar monto consolidado
        const monto = parseFloat(inputMonto.value);
        if (isNaN(monto) || monto <= 0) {
            mostrarAlertaError('El monto total consolidado a cobrar debe ser mayor a $0.00.');
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

                // [Módulo CARE] Notificar éxito del pago para actualizar cargos y badges
                document.dispatchEvent(new CustomEvent('caja:pago_exitoso', {
                    detail: {
                        pago: body.data,
                        alumnoId: alumnoId
                    }
                }));
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

        const tbody = document.getElementById('vouch-tbody');
        const renglones = obtenerDatosRenglones().filter(r => r.concepto && r.monto > 0);

        if (tbody && renglones.length > 1) {
            tbody.innerHTML = renglones.map((r, i) => `
                <tr>
                    <td>${escapeHtml(r.concepto)}</td>
                    <td>${i === 0 ? escapeHtml(pago.forma_pago || '--') : '—'}</td>
                    <td>${i === 0 ? escapeHtml(pago.referencia || 'N/A') : '—'}</td>
                    <td class="text-right font-bold">$ ${r.monto.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
        } else if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td id="vouch-concepto">${escapeHtml(pago.concepto || '--')}</td>
                    <td id="vouch-forma-pago">${escapeHtml(pago.forma_pago || '--')}</td>
                    <td id="vouch-referencia">${escapeHtml(pago.referencia || 'N/A')}</td>
                    <td class="text-right font-bold" id="vouch-monto">${pago.monto_formateado || ('$ ' + pago.monto)}</td>
                </tr>
            `;
        }

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
        if (rowsContainer) {
            rowsContainer.innerHTML = '';
            crearRenglonConcepto({ focus: false });
        }
        if (inputConcepto) inputConcepto.value = '';
        if (inputConceptoId) inputConceptoId.value = '';
        if (inputMonto) inputMonto.value = '';
        if (inputDesglose) inputDesglose.value = '';
        recalcularTotales();
        const ceaHidden = document.getElementById('cea_cargos_ids');
        if (ceaHidden) ceaHidden.value = '';
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
