/**
 * ASRS Framework - Modal bloqueante de Cargos CARE en Caja
 * Ruta: public/assets/js/caja/cea_cargos_modal.js
 *
 * Aislado de capturar.js: observa la tarjeta de alumno seleccionado (#alumno-selected-card)
 * y consulta cargos 'pendiente' del alumno. No modifica el flujo de cobro regular salvo
 * que el cajero elija "Incluir en el cobro actual" (precarga concepto y monto).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const card          = document.getElementById('alumno-selected-card');
        const inputAlumnoId = document.getElementById('input_alumno_id');
        const form          = document.getElementById('form-capturar-pago');
        if (!card || !inputAlumnoId || !form) return;

        const cfg = window.ASRS_CAJA || {};
        const endpoint = cfg.endpointUrl || window.location.href.split('?')[0];

        let lastPromptedId = null;   // alumno ya notificado en esta selección
        let isOpen = false;
        let previousFocus = null;
        let currentCargos = [];
        let currentAlumno = null;
        let currentTotal  = 0;
        let isIncludedInForm = false;
        let selectedIds = new Set(); // Conjunto de IDs seleccionados activamente con checkboxes

        // ── Utilidades ──────────────────────────────────────────────
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
        const money = (n) => '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fecha = (s) => {
            const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || '');
            return m ? `${m[3]}/${m[2]}/${m[1]}` : esc(s);
        };
        const chipClass = (c) => {
            const l = String(c || '').toLowerCase();
            if (l.includes('lunch') || l.includes('comedor')) return 'cea-cargos-chip--lunch';
            if (l.includes('estancia')) return 'cea-cargos-chip--estancia';
            return '';
        };

        // ── Botón / Insignia de Acceso Persistente en la Tarjeta de Alumno ──
        let btnBadge = document.getElementById('btn-reabrir-cargos-care');
        if (!btnBadge) {
            btnBadge = document.createElement('button');
            btnBadge.type = 'button';
            btnBadge.id = 'btn-reabrir-cargos-care';
            btnBadge.className = 'cea-badge-cargos-pendientes';
            btnBadge.style.display = 'none';
            btnBadge.title = 'Ver y gestionar cargos CARE pendientes de cobro';
            btnBadge.innerHTML = `
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
            `;
            const btnChange = document.getElementById('btn-change-alumno');
            if (btnChange && btnChange.parentNode === card) {
                card.insertBefore(btnBadge, btnChange);
            } else {
                card.appendChild(btnBadge);
            }
        }

        const badgeTitle  = btnBadge.querySelector('#cea-badge-cargos-title');
        const badgeAmount = btnBadge.querySelector('#cea-badge-cargos-amount');

        function actualizarInsigniaPersistente(count, total, included = false) {
            if (!btnBadge) return;
            if (count <= 0) {
                btnBadge.style.display = 'none';
                btnBadge.classList.remove('is-included');
                return;
            }

            btnBadge.style.display = 'inline-flex';
            if (included) {
                btnBadge.classList.add('is-included');
                if (badgeTitle) badgeTitle.textContent = `✓ Cargos CARE incluidos (${count})`;
                if (badgeAmount) badgeAmount.textContent = `${money(total)} MXN`;
                btnBadge.title = `Cargos CARE precargados en el cobro actual (${count} servicios, ${money(total)} MXN). Clic para ver detalles.`;
            } else {
                btnBadge.classList.remove('is-included');
                if (badgeTitle) badgeTitle.textContent = `Adeudos CARE (${count})`;
                if (badgeAmount) badgeAmount.textContent = `${money(total)} MXN`;
                btnBadge.title = `El alumno tiene ${count} adeudos pendientes de servicios eventuales (${money(total)} MXN). Clic para reabrir y agregarlos.`;
            }
        }

        // Al hacer clic en el botón persistente, reabrir la ventana modal
        btnBadge.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (currentCargos.length > 0) {
                openModal();
            } else {
                const id = (inputAlumnoId.value || '').trim();
                if (id) consultarCargos(id, true);
            }
        });

        // ── Construcción del modal (una sola vez) ───────────────────
        const backdrop = document.createElement('div');
        backdrop.className = 'cea-cargos-backdrop';
        backdrop.id = 'cea-cargos-backdrop';
        backdrop.innerHTML = `
            <div class="cea-cargos-dialog" id="cea-cargos-dialog" role="alertdialog" aria-modal="true"
                 aria-labelledby="cea-cargos-title" aria-describedby="cea-cargos-desc" tabindex="-1">
                <div class="cea-cargos-dialog__header">
                    <div class="cea-cargos-dialog__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 class="cea-cargos-dialog__title" id="cea-cargos-title">Cargos CARE pendientes</h2>
                        <p class="cea-cargos-dialog__subtitle" id="cea-cargos-desc">Selecciona los adeudos que deseas incluir en el ticket de cobro.</p>
                    </div>
                </div>
                <div class="cea-cargos-dialog__body">
                    <div class="cea-cargos-alumno">
                        <div class="cea-cargos-alumno__avatar" id="cea-cargos-avatar">AL</div>
                        <div>
                            <h3 class="cea-cargos-alumno__name" id="cea-cargos-nombre">Alumno</h3>
                            <div class="cea-cargos-alumno__meta" id="cea-cargos-meta"></div>
                        </div>
                    </div>
                    <div class="cea-cargos-table-wrap">
                        <table class="cea-cargos-table" id="cea-cargos-table">
                            <thead><tr>
                                <th class="cea-cargos-th-check">
                                    <input type="checkbox" id="cea-check-all" class="cea-cargos-checkbox" checked title="Seleccionar o deseleccionar todos los cargos">
                                </th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Fecha solicitud</th>
                                <th>Fecha servicio</th>
                            </tr></thead>
                            <tbody id="cea-cargos-tbody"></tbody>
                        </table>
                    </div>
                    <div class="cea-cargos-total">
                        <div class="cea-cargos-total__left">
                            <span class="cea-cargos-total__label">Total a cobrar:</span>
                            <span class="cea-cargos-total__sub" id="cea-cargos-selected-meta">0 cargos seleccionados</span>
                        </div>
                        <strong id="cea-cargos-total">$0.00</strong>
                    </div>
                    <p class="cea-cargos-note">
                        Marca o desmarca las casillas según lo que el padre decida liquidar. Solo los cargos marcados serán incluidos en el ticket y pasarán a estatus <strong>pagado</strong>.
                    </p>
                </div>
                <div class="cea-cargos-dialog__footer">
                    <button type="button" class="cea-cargos-btn cea-cargos-btn--ghost" id="cea-cargos-omitir">Cerrar / Omitir por ahora</button>
                    <button type="button" class="cea-cargos-btn cea-cargos-btn--primary" id="cea-cargos-incluir">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span id="cea-cargos-incluir-text">Incluir en el cobro actual</span>
                    </button>
                </div>
            </div>`;
        document.body.appendChild(backdrop);

        const dialog        = backdrop.querySelector('#cea-cargos-dialog');
        const btnOmitir     = backdrop.querySelector('#cea-cargos-omitir');
        const btnIncluir    = backdrop.querySelector('#cea-cargos-incluir');
        const btnIncluirTxt = backdrop.querySelector('#cea-cargos-incluir-text');
        const tbodyCargos   = backdrop.querySelector('#cea-cargos-tbody');
        const checkMaster   = backdrop.querySelector('#cea-check-all');
        const totalDisplay  = backdrop.querySelector('#cea-cargos-total');
        const metaDisplay   = backdrop.querySelector('#cea-cargos-selected-meta');

        // ── Apertura / cierre (solo mediante botones explícitos) ────
        function openModal() {
            isOpen = true;
            previousFocus = document.activeElement;
            backdrop.classList.add('is-open');
            document.body.classList.add('cea-cargos-lock');
            btnIncluir.focus();
        }

        function closeModal() {
            isOpen = false;
            backdrop.classList.remove('is-open');
            document.body.classList.remove('cea-cargos-lock');
            if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
        }

        // Bloqueo: clic en backdrop no cierra
        backdrop.addEventListener('mousedown', (e) => {
            if (e.target === backdrop) { e.preventDefault(); e.stopPropagation(); dialog.focus(); }
        });
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) { e.preventDefault(); e.stopPropagation(); }
        });

        // Bloqueo: ESC no cierra + trampa de foco (fase de captura para anticipar otros handlers)
        document.addEventListener('keydown', (e) => {
            if (!isOpen) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }
            if (e.key === 'Tab') {
                const f = [btnOmitir, btnIncluir].filter((b) => !b.disabled);
                const idx = f.indexOf(document.activeElement);
                e.preventDefault();
                const next = e.shiftKey ? (idx <= 0 ? f.length - 1 : idx - 1) : (idx + 1) % f.length;
                f[next].focus();
            }
        }, true);

        // ── Gestión dinámica de checkboxes y cálculos ───────────────
        function actualizarCalculosSeleccion() {
            const selectedCargos = currentCargos.filter((c) => selectedIds.has(Number(c.id)));
            const count = selectedCargos.length;
            const totalCount = currentCargos.length;
            const sum = selectedCargos.reduce((acc, c) => acc + Number(c.monto || 0), 0);

            // Actualizar total y texto informativo
            if (totalDisplay) totalDisplay.textContent = money(sum);
            if (metaDisplay) {
                metaDisplay.textContent = `${count} de ${totalCount} adeudo${totalCount === 1 ? '' : 's'} seleccionado${count === 1 ? '' : 's'}`;
            }

            // Sincronizar checkbox general de la cabecera
            if (checkMaster) {
                if (count === 0) {
                    checkMaster.checked = false;
                    checkMaster.indeterminate = false;
                } else if (count === totalCount) {
                    checkMaster.checked = true;
                    checkMaster.indeterminate = false;
                } else {
                    checkMaster.checked = false;
                    checkMaster.indeterminate = true;
                }
            }

            // Habilitar o deshabilitar botón de inclusión según selección
            if (count === 0) {
                btnIncluir.disabled = true;
                if (btnIncluirTxt) btnIncluirTxt.textContent = 'Seleccione cargos a incluir';
            } else {
                btnIncluir.disabled = false;
                if (btnIncluirTxt) {
                    btnIncluirTxt.textContent = count === totalCount
                        ? `Incluir todos en el cobro (${money(sum)})`
                        : `Incluir ${count} seleccionado${count === 1 ? '' : 's'} (${money(sum)})`;
                }
            }
        }

        // Checkbox Master: seleccionar o deseleccionar todos
        if (checkMaster) {
            checkMaster.addEventListener('change', () => {
                const checked = checkMaster.checked;
                if (checked) {
                    currentCargos.forEach((c) => selectedIds.add(Number(c.id)));
                } else {
                    selectedIds.clear();
                }

                tbodyCargos.querySelectorAll('.cea-cargo-item-check').forEach((chk) => {
                    chk.checked = checked;
                });
                tbodyCargos.querySelectorAll('.cea-cargos-row').forEach((tr) => {
                    tr.classList.toggle('is-selected', checked);
                });

                actualizarCalculosSeleccion();
            });
        }

        // Delegación de eventos en las filas del cuerpo de la tabla
        if (tbodyCargos) {
            // Cambio en casillas individuales
            tbodyCargos.addEventListener('change', (e) => {
                const chk = e.target.closest('.cea-cargo-item-check');
                if (!chk) return;

                const id = Number(chk.dataset.id);
                const tr = chk.closest('.cea-cargos-row');

                if (chk.checked) {
                    selectedIds.add(id);
                    if (tr) tr.classList.add('is-selected');
                } else {
                    selectedIds.delete(id);
                    if (tr) tr.classList.remove('is-selected');
                }

                actualizarCalculosSeleccion();
            });

            // Clic en la fila para conmutar la casilla fácilmente
            tbodyCargos.addEventListener('click', (e) => {
                if (e.target.matches('input[type="checkbox"]')) return;
                const tr = e.target.closest('.cea-cargos-row');
                if (!tr) return;

                const chk = tr.querySelector('.cea-cargo-item-check');
                if (chk) {
                    chk.checked = !chk.checked;
                    chk.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        // ── Acciones de los botones del modal ───────────────────────
        btnOmitir.addEventListener('click', () => {
            // El cajero cierra el modal; la insignia persistente se mantiene visible
            closeModal();
            if (currentCargos.length > 0) {
                actualizarInsigniaPersistente(currentCargos.length, currentTotal, isIncludedInForm);
            }
        });

        btnIncluir.addEventListener('click', () => {
            incluirEnCobro();
            closeModal();
        });

        function incluirEnCobro() {
            const selectedCargos = currentCargos.filter((c) => selectedIds.has(Number(c.id)));
            if (!selectedCargos.length) return;

            const total = selectedCargos.reduce((s, c) => s + Number(c.monto || 0), 0);

            // Integración nativa con la sección de renglones dinámicos de Caja
            if (window.ASRS_CAJA && typeof window.ASRS_CAJA.agregarRenglon === 'function') {
                const rowsContainer = document.getElementById('caja-conceptos-rows');
                if (rowsContainer) {
                    // Remover filas CARE previas para no duplicar al cambiar la selección en el modal
                    rowsContainer.querySelectorAll('.caja-concepto-row.is-care-row').forEach(r => r.remove());
                }

                // Insertar cada cargo seleccionado como su propio renglón independiente con concepto y monto precargados
                selectedCargos.forEach(c => {
                    window.ASRS_CAJA.agregarRenglon({
                        concepto: 'CARE: ' + c.concepto,
                        monto: c.monto,
                        careId: c.id,
                        isCustom: true
                    });
                });

                if (typeof window.ASRS_CAJA.recalcularTotales === 'function') {
                    window.ASRS_CAJA.recalcularTotales();
                }

                isIncludedInForm = true;
                actualizarInsigniaPersistente(selectedCargos.length, total, true);
                return;
            }

            // Fallback retrocompatible para formulario estático previo
            const selectConcepto = document.getElementById('select_concepto');
            const inputPers      = document.getElementById('input_concepto_personalizado');
            const inputMonto     = document.getElementById('input_monto');
            if (!selectConcepto || !inputPers || !inputMonto) return;

            const nombres = [...new Set(selectedCargos.map((c) => c.concepto))].join(' + ');
            let texto = ('CARE: ' + nombres).slice(0, 100);

            selectConcepto.value = 'OTRO';
            selectConcepto.dispatchEvent(new Event('change', { bubbles: true }));
            inputPers.value = texto;
            inputPers.dispatchEvent(new Event('input', { bubbles: true }));

            inputMonto.value = total.toFixed(2);
            inputMonto.dispatchEvent(new Event('input', { bubbles: true }));
            inputMonto.classList.add('amount-updated');
            setTimeout(() => inputMonto.classList.remove('amount-updated'), 450);

            let hidden = document.getElementById('cea_cargos_ids');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'cea_cargos_ids';
                hidden.id = 'cea_cargos_ids';
                form.appendChild(hidden);
            }
            hidden.value = selectedCargos.map((c) => c.id).join(',');

            const hint = document.getElementById('monto-hint');
            if (hint) {
                hint.textContent = `Cargos CARE incluidos (${selectedCargos.length}): ${money(total)} MXN. Editable libremente.`;
            }

            isIncludedInForm = true;
            actualizarInsigniaPersistente(selectedCargos.length, total, true);
        }

        // ── Render y consulta ───────────────────────────────────────
        function render(alumno, cargos, total) {
            const nombre = (document.getElementById('sel-alumno-nombre')?.textContent || '').trim() || 'Alumno';
            const curp   = (document.getElementById('sel-alumno-curp')?.textContent || '').trim();
            const nivel  = (document.getElementById('sel-alumno-nivel')?.textContent || '').trim();
            const grado  = (document.getElementById('sel-alumno-grado-grupo')?.textContent || '').trim();
            const initials = (document.getElementById('sel-avatar-initials')?.textContent || 'AL').trim();

            backdrop.querySelector('#cea-cargos-avatar').textContent = initials;
            backdrop.querySelector('#cea-cargos-nombre').textContent = nombre;
            backdrop.querySelector('#cea-cargos-meta').textContent =
                [`ID #${alumno}`, curp && `CURP ${curp}`, nivel, grado].filter((x) => x && x !== '--').join(' • ');

            // Sincronizar selección: si ya hay cargos incluidos en la caja, pre-marcar esos; si no, todos
            const renglonesActivos = (window.ASRS_CAJA && typeof window.ASRS_CAJA.obtenerRenglones === 'function')
                ? window.ASRS_CAJA.obtenerRenglones()
                : [];
            const idsEnFormulario = new Set(renglonesActivos.map(r => Number(r.careId)).filter(Boolean));

            if (idsEnFormulario.size > 0) {
                selectedIds = new Set(cargos.filter(c => idsEnFormulario.has(Number(c.id))).map(c => Number(c.id)));
            } else {
                selectedIds = new Set(cargos.map((c) => Number(c.id)));
            }

            tbodyCargos.innerHTML = cargos.map((c) => {
                const id = Number(c.id);
                const isSel = selectedIds.has(id);
                return `
                    <tr class="cea-cargos-row ${isSel ? 'is-selected' : ''}" data-id="${id}">
                        <td class="cea-cargos-td-check">
                            <input type="checkbox" class="cea-cargo-item-check cea-cargos-checkbox" data-id="${id}" ${isSel ? 'checked' : ''} aria-label="Seleccionar cargo de ${esc(c.concepto)}">
                        </td>
                        <td><span class="cea-cargos-chip ${chipClass(c.concepto)}">${esc(c.concepto)}</span></td>
                        <td class="is-amount">${money(c.monto)}</td>
                        <td>${fecha(c.fecha_solicitud)}</td>
                        <td>${fecha(c.fecha_servicio)}</td>
                    </tr>`;
            }).join('');

            actualizarCalculosSeleccion();
        }

        function consultarCargos(alumnoId, autoOpen = true) {
            fetch(`${endpoint}?action=get_cargos_pendientes&alumno_id=${encodeURIComponent(alumnoId)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then((r) => r.json())
            .then((resp) => {
                // La selección pudo cambiar mientras se consultaba
                if (String(inputAlumnoId.value) !== String(alumnoId)) return;
                if (!resp.success || !Array.isArray(resp.data) || resp.data.length === 0) {
                    currentCargos = [];
                    currentTotal = 0;
                    isIncludedInForm = false;
                    actualizarInsigniaPersistente(0, 0);
                    return;
                }

                currentAlumno = alumnoId;
                currentCargos = resp.data;
                currentTotal  = Number(resp.total || 0);
                isIncludedInForm = false;
                render(alumnoId, resp.data, resp.total);
                actualizarInsigniaPersistente(resp.data.length, resp.total, false);
                if (autoOpen) {
                    openModal();
                }
            })
            .catch((err) => console.error('CARE: error consultando cargos pendientes', err));
        }

        // ── Detección de selección y deselección de alumno ───────────
        function evaluarSeleccion() {
            const visible = card.style.display !== 'none' && card.style.display !== '';
            const id = (inputAlumnoId.value || '').trim();

            if (!visible || !id) {
                lastPromptedId = null;   // permite re-notificar si se vuelve a seleccionar
                currentCargos = [];
                currentAlumno = null;
                currentTotal = 0;
                isIncludedInForm = false;
                actualizarInsigniaPersistente(0, 0);
                const hidden = document.getElementById('cea_cargos_ids');
                if (hidden) hidden.value = '';
                if (isOpen) closeModal();
                return;
            }
            if (id === lastPromptedId) return;

            lastPromptedId = id;
            consultarCargos(id, true);
        }

        new MutationObserver(evaluarSeleccion).observe(card, { attributes: true, attributeFilter: ['style'] });
        evaluarSeleccion();

        // ── Sincronización con eventos de Caja (Pago Exitoso / Reseteo) ──
        document.addEventListener('caja:pago_exitoso', (e) => {
            const hidden = document.getElementById('cea_cargos_ids');
            if (hidden) hidden.value = '';
            isIncludedInForm = false;

            const alumnoId = (inputAlumnoId.value || '').trim();
            if (alumnoId) {
                // Reconsultar cargos pendientes del alumno para actualizar insignia o apagarla
                consultarCargos(alumnoId, false);
            } else {
                actualizarInsigniaPersistente(0, 0);
            }
        });

        // Si el usuario cambia el concepto del formulario manualmente a uno regular (fallback)
        const selectConcepto = document.getElementById('select_concepto');
        if (selectConcepto) {
            selectConcepto.addEventListener('change', () => {
                if (selectConcepto.value !== 'OTRO' && isIncludedInForm) {
                    isIncludedInForm = false;
                    const hidden = document.getElementById('cea_cargos_ids');
                    if (hidden) hidden.value = '';
                    if (currentCargos.length > 0) {
                        actualizarInsigniaPersistente(currentCargos.length, currentTotal, false);
                    }
                }
            });
        }

        // Sincronización en vivo cuando cambian o se eliminan renglones CARE en la Caja
        document.addEventListener('caja:care_rows_changed', (e) => {
            const activeCareIds = (e.detail && Array.isArray(e.detail.activeCareIds))
                ? e.detail.activeCareIds.map(Number)
                : [];

            if (activeCareIds.length === 0) {
                isIncludedInForm = false;
                if (currentCargos.length > 0) {
                    actualizarInsigniaPersistente(currentCargos.length, currentTotal, false);
                }
            } else {
                isIncludedInForm = true;
                const sum = currentCargos
                    .filter(c => activeCareIds.includes(Number(c.id)))
                    .reduce((acc, c) => acc + Number(c.monto || 0), 0);
                actualizarInsigniaPersistente(activeCareIds.length, sum, true);
            }
        });
    });
})();
