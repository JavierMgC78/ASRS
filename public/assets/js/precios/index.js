/**
 * ==========================================================
 * ASRS FRAMEWORK - JS PRECIOS Y TARIFAS (index.js)
 * Axe Secure Router System - Módulo Centralizado de Precios
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const moduloContainer = document.getElementById('precios-modulo');
    const endpointUrl = window.location.href;

    // Elementos de Navegación de Tabs
    const tabButtons = document.querySelectorAll('.precios-tab-btn');
    const tabPanels  = document.querySelectorAll('.precios-panel');

    // Modal Nuevo Concepto
    const modalBackdrop      = document.getElementById('modal-nuevo-concepto');
    const btnOpenModalNuevo  = document.getElementById('btn-open-modal-nuevo');
    const btnCloseModal      = document.getElementById('btn-close-modal');
    const btnCancelarModal   = document.getElementById('btn-cancelar-modal');
    const formNuevoConcepto  = document.getElementById('form-nuevo-concepto');
    const selectNuevoNivel   = document.getElementById('nuevo_nivel');
    const inputNuevoConcepto = document.getElementById('nuevo_concepto');
    const inputNuevoMonto    = document.getElementById('nuevo_monto');
    const inputNuevaDesc     = document.getElementById('nueva_descripcion');
    const btnSubmitNuevo     = document.getElementById('btn-submit-nuevo');

    // Contenedor Toast
    const toastContainer     = document.getElementById('precios-toast-container');

    // -------------------------------------------------------------------------
    // 1. SISTEMA DE PESTAÑAS (TABS UI/UX) FLUIDAS
    // -------------------------------------------------------------------------
    function activarTab(nivelDeseado) {
        if (!nivelDeseado) return;

        tabButtons.forEach(btn => {
            const nivel = btn.getAttribute('data-nivel');
            const isActive = (nivel === nivelDeseado);
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        tabPanels.forEach(panel => {
            const nivel = panel.getAttribute('data-nivel');
            const isActive = (nivel === nivelDeseado);
            panel.classList.toggle('is-active', isActive);
        });

        // Guardar en sessionStorage para persistencia ante F5
        try {
            sessionStorage.setItem('asrs_precios_active_tab', nivelDeseado);
        } catch (e) {
            // Manejo silencioso en navegación privada estricta
        }
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const nivel = btn.getAttribute('data-nivel');
            activarTab(nivel);
        });
    });

    // Restaurar tab activo previo si existe
    try {
        const savedTab = sessionStorage.getItem('asrs_precios_active_tab');
        if (savedTab && ['preescolar', 'primaria', 'secundaria'].includes(savedTab)) {
            activarTab(savedTab);
        }
    } catch (e) {}

    // -------------------------------------------------------------------------
    // 2. ACTUALIZACIÓN RÁPIDA DE TARIFA INDIVIDUAL (PDO)
    // -------------------------------------------------------------------------
    async function actualizarTarifaFila(btnFila) {
        const id = btnFila.getAttribute('data-id');
        const nivel = btnFila.getAttribute('data-nivel');
        const inputMonto = document.getElementById(`input-monto-${id}`);
        if (!inputMonto) return;

        const montoVal = parseFloat(inputMonto.value);
        if (isNaN(montoVal) || montoVal < 0) {
            mostrarToast('Por favor ingrese un monto numérico válido mayor o igual a 0.', 'error');
            inputMonto.focus();
            return;
        }

        const btnText    = btnFila.querySelector('.btn-text');
        const btnSpinner = btnFila.querySelector('.btn-spinner');
        const btnCheck   = btnFila.querySelector('.btn-check-icon');

        // Estado cargando
        btnFila.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline-block';
        if (btnCheck) btnCheck.style.display = 'none';

        try {
            const response = await fetch(endpointUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_tarifa',
                    id: parseInt(id, 10),
                    monto: montoVal
                })
            });

            const result = await response.json();

            if (result.success && result.data) {
                // Actualizar input con formato limpio
                inputMonto.value = parseFloat(result.data.monto).toFixed(2);
                inputMonto.setAttribute('data-original', inputMonto.value);

                // Actualizar timestamp en fila
                const lblUpd = document.getElementById(`lbl-upd-${id}`);
                if (lblUpd && result.data.updated_at) {
                    lblUpd.textContent = result.data.updated_at;
                }

                // Actualizar métricas del nivel
                if (result.data.metricas) {
                    actualizarMetricasUI(result.data.nivel, result.data.metricas);
                }

                // Mostrar checkmark de éxito temporal
                btnFila.classList.add('is-success');
                if (btnSpinner) btnSpinner.style.display = 'none';
                if (btnCheck) btnCheck.style.display = 'inline-block';

                mostrarToast(result.message || 'Tarifa actualizada correctamente.', 'success');

                setTimeout(() => {
                    btnFila.classList.remove('is-success');
                    if (btnCheck) btnCheck.style.display = 'none';
                    if (btnText) btnText.style.display = 'inline';
                    btnFila.disabled = false;
                }, 1500);

            } else {
                throw new Error(result.error || 'No fue posible actualizar la tarifa.');
            }

        } catch (error) {
            console.error('Error al actualizar tarifa:', error);
            mostrarToast(error.message || 'Error de conexión con el servidor.', 'error');
            if (btnSpinner) btnSpinner.style.display = 'none';
            if (btnText) btnText.style.display = 'inline';
            btnFila.disabled = false;
        }
    }

    // Delegación de eventos para botones de fila
    document.addEventListener('click', (e) => {
        const btnFila = e.target.closest('.btn-guardar-fila');
        if (btnFila) {
            e.preventDefault();
            actualizarTarifaFila(btnFila);
        }
    });

    // Guardar con Enter dentro del input de monto
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.classList.contains('input-monto-editable')) {
            e.preventDefault();
            const id = e.target.getAttribute('data-id');
            const row = document.getElementById(`tarifa-row-${id}`);
            if (row) {
                const btn = row.querySelector('.btn-guardar-fila');
                if (btn) actualizarTarifaFila(btn);
            }
        }
    });

    // -------------------------------------------------------------------------
    // 3. ACTUALIZACIÓN EN LOTE (GUARDAR TODO EL NIVEL)
    // -------------------------------------------------------------------------
    document.querySelectorAll('.btn-guardar-todo').forEach(btn => {
        btn.addEventListener('click', async () => {
            const nivel = btn.getAttribute('data-nivel');
            const panel = document.getElementById(`tab-panel-${nivel}`);
            if (!panel) return;

            const inputs = panel.querySelectorAll('.input-monto-editable');
            const tarifasPayload = [];

            inputs.forEach(inp => {
                const id = inp.getAttribute('data-id');
                const monto = parseFloat(inp.value);
                if (id && !isNaN(monto) && monto >= 0) {
                    tarifasPayload.push({
                        id: parseInt(id, 10),
                        monto: monto
                    });
                }
            });

            if (tarifasPayload.length === 0) {
                mostrarToast('No hay tarifas válidas para actualizar.', 'error');
                return;
            }

            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span>Guardando...</span>
            `;

            try {
                const response = await fetch(endpointUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_nivel_bulk',
                        nivel: nivel,
                        tarifas: tarifasPayload
                    })
                });

                const result = await response.json();

                if (result.success && result.data) {
                    mostrarToast(result.message || 'Todas las tarifas del nivel fueron actualizadas.', 'success');
                    if (result.data.metricas) {
                        actualizarMetricasUI(nivel, result.data.metricas);
                    }
                } else {
                    throw new Error(result.error || 'Error al guardar las tarifas.');
                }

            } catch (err) {
                console.error(err);
                mostrarToast(err.message || 'Error al conectar con el servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    });

    // -------------------------------------------------------------------------
    // 4. SWITCH ACTIVAR / DESACTIVAR CONCEPTO
    // -------------------------------------------------------------------------
    document.addEventListener('change', async (e) => {
        if (e.target.classList.contains('chk-toggle-activo')) {
            const chk = e.target;
            const id = chk.getAttribute('data-id');
            const isActivo = chk.checked;
            const row = document.getElementById(`tarifa-row-${id}`);

            try {
                const response = await fetch(endpointUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'toggle_activo',
                        id: parseInt(id, 10),
                        activo: isActivo ? 1 : 0
                    })
                });

                const result = await response.json();

                if (result.success && result.data) {
                    if (row) {
                        row.classList.toggle('is-disabled-row', !isActivo);
                    }
                    if (result.data.metricas) {
                        const panel = row ? row.closest('.precios-panel') : null;
                        const nivel = panel ? panel.getAttribute('data-nivel') : null;
                        if (nivel) {
                            actualizarMetricasUI(nivel, result.data.metricas);
                        }
                    }
                    mostrarToast(result.message || 'Estado actualizado.', 'success');
                } else {
                    throw new Error(result.error || 'No se pudo actualizar el estado.');
                }
            } catch (err) {
                console.error(err);
                chk.checked = !isActivo; // revertir
                mostrarToast(err.message || 'Error al cambiar estado.', 'error');
            }
        }
    });

    // -------------------------------------------------------------------------
    // 5. MODAL NUEVO CONCEPTO
    // -------------------------------------------------------------------------
    function abrirModalNuevo() {
        if (!modalBackdrop) return;
        // Establecer el nivel actual seleccionado como default en el select
        const activeTab = document.querySelector('.precios-tab-btn.is-active');
        if (activeTab && selectNuevoNivel) {
            selectNuevoNivel.value = activeTab.getAttribute('data-nivel') || 'preescolar';
        }
        formNuevoConcepto.reset();
        modalBackdrop.style.display = 'flex';
        inputNuevoConcepto.focus();
    }

    function cerrarModalNuevo() {
        if (!modalBackdrop) return;
        modalBackdrop.style.display = 'none';
    }

    if (btnOpenModalNuevo) btnOpenModalNuevo.addEventListener('click', abrirModalNuevo);
    if (btnCloseModal) btnCloseModal.addEventListener('click', cerrarModalNuevo);
    if (btnCancelarModal) btnCancelarModal.addEventListener('click', cerrarModalNuevo);

    // Cerrar con click fuera
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) {
                cerrarModalNuevo();
            }
        });
    }

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalBackdrop && modalBackdrop.style.display === 'flex') {
            cerrarModalNuevo();
        }
    });

    // Envío del formulario de nuevo concepto
    if (formNuevoConcepto) {
        formNuevoConcepto.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nivel = selectNuevoNivel.value;
            const concepto = inputNuevoConcepto.value.trim();
            const monto = parseFloat(inputNuevoMonto.value);
            const descripcion = inputNuevaDesc.value.trim();

            if (!concepto) {
                mostrarToast('El nombre del concepto es obligatorio.', 'error');
                inputNuevoConcepto.focus();
                return;
            }

            if (isNaN(monto) || monto < 0) {
                mostrarToast('El monto debe ser un número mayor o igual a 0.', 'error');
                inputNuevoMonto.focus();
                return;
            }

            btnSubmitNuevo.disabled = true;
            btnSubmitNuevo.querySelector('.btn-text').textContent = 'Guardando...';

            try {
                const response = await fetch(endpointUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_concepto',
                        nivel: nivel,
                        concepto: concepto,
                        monto: monto,
                        descripcion: descripcion
                    })
                });

                const result = await response.json();

                if (result.success && result.data) {
                    mostrarToast(result.message || 'Concepto registrado correctamente.', 'success');
                    cerrarModalNuevo();

                    // Insertar fila en la tabla correspondiente
                    insertarFilaEnTabla(result.data);

                    // Actualizar métricas del nivel
                    if (result.data.metricas) {
                        actualizarMetricasUI(nivel, result.data.metricas);
                    }

                    // Cambiar a la pestaña del nuevo concepto
                    activarTab(nivel);

                } else {
                    throw new Error(result.error || 'Error al guardar nuevo concepto.');
                }
            } catch (err) {
                console.error(err);
                mostrarToast(err.message || 'Error de conexión al registrar concepto.', 'error');
            } finally {
                btnSubmitNuevo.disabled = false;
                btnSubmitNuevo.querySelector('.btn-text').textContent = 'Guardar Concepto';
            }
        });
    }

    // -------------------------------------------------------------------------
    // 6. HELPERS DE ACTUALIZACIÓN DE DOM
    // -------------------------------------------------------------------------
    function actualizarMetricasUI(nivel, metricas) {
        if (!metricas) return;

        // Tarjetas KPIs
        const kpiCol = document.getElementById(`kpi-col-${nivel}`);
        const kpiIns = document.getElementById(`kpi-ins-${nivel}`);
        const kpiPaq = document.getElementById(`kpi-paq-${nivel}`);
        const kpiTot = document.getElementById(`kpi-tot-${nivel}`);

        if (kpiCol) kpiCol.textContent = metricas.colegiatura_fmt || '0.00';
        if (kpiIns) kpiIns.textContent = metricas.inscripcion_fmt || '0.00';
        if (kpiPaq) kpiPaq.textContent = metricas.paquete_fmt || '0.00';
        if (kpiTot) kpiTot.textContent = metricas.total_activos || 0;

        // Badges en las pestañas superiores
        const badgeCol = document.getElementById(`badge-col-${nivel}`);
        const badgeCount = document.getElementById(`badge-count-${nivel}`);

        if (badgeCol) badgeCol.textContent = `$${metricas.colegiatura_fmt || '0.00'}/mes`;
        if (badgeCount) badgeCount.textContent = `${metricas.total_activos || 0} conceptos`;
    }

    function insertarFilaEnTabla(data) {
        const tbody = document.getElementById(`tbody-tarifas-${data.nivel}`);
        if (!tbody) return;

        // Quitar fila vacía si existe
        const emptyRow = tbody.querySelector('.table-empty-row');
        if (emptyRow) {
            emptyRow.parentElement.remove();
        }

        const tr = document.createElement('tr');
        tr.className = 'tarifa-row';
        tr.id = `tarifa-row-${data.id}`;
        tr.setAttribute('data-id', data.id);
        tr.setAttribute('data-nivel', data.nivel);

        const montoFmt = parseFloat(data.monto).toFixed(2);

        tr.innerHTML = `
            <td class="col-id">
                <span class="id-tag">#${data.id}</span>
            </td>
            <td class="col-concepto">
                <div class="concepto-title font-bold">${escapeHtml(data.concepto)}</div>
                <span class="concepto-updated">Última actualización: <span id="lbl-upd-${data.id}">Recién creado</span></span>
            </td>
            <td class="col-desc">
                <span class="desc-text">${escapeHtml(data.descripcion || 'Sin descripción adicional')}</span>
            </td>
            <td class="col-monto">
                <div class="monto-input-group">
                    <span class="monto-prefix">$</span>
                    <input type="number" 
                           class="input-monto-editable" 
                           id="input-monto-${data.id}" 
                           value="${montoFmt}" 
                           step="0.50" 
                           min="0" 
                           data-id="${data.id}" 
                           data-original="${montoFmt}">
                    <span class="monto-suffix">MXN</span>
                </div>
            </td>
            <td class="col-estado text-center">
                <label class="switch-toggle" title="Activar o desactivar este concepto">
                    <input type="checkbox" class="chk-toggle-activo" data-id="${data.id}" checked>
                    <span class="switch-slider"></span>
                </label>
            </td>
            <td class="col-accion text-center">
                <button type="button" class="btn-guardar-fila" data-id="${data.id}" data-nivel="${data.nivel}" title="Guardar tarifa para este concepto">
                    <span class="btn-text">Actualizar</span>
                    <svg class="btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                        <line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                    </svg>
                    <svg class="btn-check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
    }

    // -------------------------------------------------------------------------
    // 7. TOAST NOTIFICACIONES FLOTANTES
    // -------------------------------------------------------------------------
    function mostrarToast(mensaje, tipo = 'success') {
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast toast--${tipo}`;

        const iconSvg = tipo === 'success'
            ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`
            : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;

        toast.innerHTML = `
            <span>${iconSvg}</span>
            <span>${escapeHtml(mensaje)}</span>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            setTimeout(() => toast.remove(), 260);
        }, 3400);
    }

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
});
