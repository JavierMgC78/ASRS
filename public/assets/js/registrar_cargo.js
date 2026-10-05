/**
 * ASRS Framework - Módulo de Cargos de Servicios CARE
 * Archivo: public/assets/js/registrar_cargo.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Referencias DOM ──────────────────────────────────────────────────────────
    const formCargo           = document.getElementById('form-registrar-cargo');
    const inputAlumnoSearch   = document.getElementById('input-alumno-search');
    const resultsContainer    = document.getElementById('alumno-search-results');
    const searchSpinner       = document.getElementById('alumno-search-spinner');
    const inputAlumnoId       = document.getElementById('input-alumno-id');
    const selectedBadge       = document.getElementById('alumno-selected-badge');
    const selectedAvatar      = document.getElementById('alumno-selected-avatar');
    const selectedName        = document.getElementById('alumno-selected-name');
    const selectedDetails     = document.getElementById('alumno-selected-details');
    const btnChangeAlumno     = document.getElementById('btn-change-alumno');
    const fallbackSelect      = document.getElementById('select-alumno-fallback');
    
    // Conceptos CARE
    const inputConcepto       = document.getElementById('input-concepto');
    const careServiceCards    = document.querySelectorAll('.care-service-card');
    
    // Monto y Fechas
    const inputMonto          = document.getElementById('input-monto');
    const inputFechaSolicitud = document.getElementById('input-fecha-solicitud');
    const inputFechaServicio  = document.getElementById('input-fecha-servicio');
    const btnSubmit           = document.getElementById('btn-submit-cargo');
    const btnSubmitText       = document.getElementById('btn-submit-text');
    const btnSpinner          = document.getElementById('btn-spinner');
    const btnClearForm        = document.getElementById('btn-clear-cargo');
    
    // Contenedores dinámicos
    const toastContainer      = document.getElementById('cargos-toast-container');
    const alertContainer      = document.getElementById('cargos-alert-container');
    const tablaCargosBody     = document.getElementById('tabla-ultimos-cargos-body');
    const emptyStateRow       = document.getElementById('cargos-empty-row');

    if (!formCargo) return;

    let searchTimeout = null;

    // ── Helper: Notificación Toast ───────────────────────────────────────────────
    function showToast(message, type = 'success') {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `cargos-toast cargos-toast--${type}`;
        toast.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                ${type === 'success' 
                    ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                    : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                }
            </svg>
            <span>${message}</span>
        `;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    // ── Helper: Alerta Superior en Card ──────────────────────────────────────────
    function showAlert(message, type = 'success') {
        if (!alertContainer) return;
        alertContainer.innerHTML = `
            <div class="cargos-alert cargos-alert--${type}" role="alert">
                <div class="cargos-alert__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        ${type === 'success'
                            ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                            : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                        }
                    </svg>
                </div>
                <div class="cargos-alert__message">${message}</div>
                <button type="button" class="cargos-alert__close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        `;
    }

    // ── 1. Búsqueda de Alumnos en Vivo ───────────────────────────────────────────
    if (inputAlumnoSearch) {
        inputAlumnoSearch.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            clearTimeout(searchTimeout);

            if (term.length < 2) {
                closeResults();
                return;
            }

            if (searchSpinner) searchSpinner.style.display = 'inline-block';

            searchTimeout = setTimeout(() => {
                fetch(`?ajax=1&action=search_alumnos&term=${encodeURIComponent(term)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (searchSpinner) searchSpinner.style.display = 'none';
                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        renderResults(data.data);
                    } else {
                        renderNoResults();
                    }
                })
                .catch(err => {
                    if (searchSpinner) searchSpinner.style.display = 'none';
                    renderNoResults('Error al consultar alumnos.');
                });
            }, 250);
        });

        // Cerrar resultados si se hace click fuera
        document.addEventListener('click', (e) => {
            if (!inputAlumnoSearch.contains(e.target) && !resultsContainer.contains(e.target)) {
                closeResults();
            }
        });
    }

    function renderResults(alumnos) {
        if (!resultsContainer) return;
        resultsContainer.innerHTML = '';
        alumnos.forEach(alumno => {
            const li = document.createElement('li');
            li.className = 'alumno-result-item';
            
            const initials = `${(alumno.primer_apellido || '').charAt(0)}${(alumno.nombre || '').charAt(0)}`.toUpperCase() || 'AL';
            const gradoGrupo = [alumno.grado, alumno.grupo].filter(Boolean).join(' ');

            li.innerHTML = `
                <div class="alumno-avatar-thumb">${initials}</div>
                <div class="alumno-result-info">
                    <span class="alumno-result-name">${alumno.nombre_completo || `${alumno.primer_apellido} ${alumno.nombre}`}</span>
                    <span class="alumno-result-meta">
                        <span><strong>CURP:</strong> ${alumno.curp}</span>
                        ${gradoGrupo ? `<span><strong>Grado:</strong> ${gradoGrupo}</span>` : ''}
                        <span><strong>Nivel:</strong> ${alumno.nivel_educativo || 'Sin nivel'}</span>
                    </span>
                </div>
            `;

            li.addEventListener('click', () => {
                selectAlumno(alumno);
                closeResults();
            });

            resultsContainer.appendChild(li);
        });

        resultsContainer.classList.add('is-open');
    }

    function renderNoResults(msg = 'No se encontraron alumnos coincidentes.') {
        if (!resultsContainer) return;
        resultsContainer.innerHTML = `
            <li class="alumno-result-item" style="cursor: default; color: #94a3b8; justify-content: center;">
                <span>${msg}</span>
            </li>
        `;
        resultsContainer.classList.add('is-open');
    }

    function closeResults() {
        if (resultsContainer) {
            resultsContainer.classList.remove('is-open');
        }
    }

    function selectAlumno(alumno) {
        if (!alumno || !alumno.id) return;
        if (inputAlumnoId) inputAlumnoId.value = alumno.id;

        const initials = `${(alumno.primer_apellido || '').charAt(0)}${(alumno.nombre || '').charAt(0)}`.toUpperCase() || 'AL';
        const nombreCompleto = alumno.nombre_completo || `${alumno.primer_apellido} ${alumno.segundo_apellido || ''} ${alumno.nombre}`;
        const gradoGrupo = [alumno.grado, alumno.grupo].filter(Boolean).join(' ');

        if (selectedAvatar) selectedAvatar.textContent = initials;
        if (selectedName) selectedName.textContent = nombreCompleto;
        if (selectedDetails) {
            selectedDetails.innerHTML = `
                <span class="tag-badge">${alumno.nivel_educativo || 'Nivel Escolar'}</span>
                ${gradoGrupo ? `<span class="tag-badge">${gradoGrupo}</span>` : ''}
                <span>CURP: <strong>${alumno.curp || 'S/D'}</strong></span>
            `;
        }

        if (selectedBadge) selectedBadge.style.display = 'flex';
        if (inputAlumnoSearch) {
            inputAlumnoSearch.value = '';
            inputAlumnoSearch.parentElement.style.display = 'none';
        }
        if (fallbackSelect) fallbackSelect.value = alumno.id;
    }

    if (btnChangeAlumno) {
        btnChangeAlumno.addEventListener('click', () => {
            if (inputAlumnoId) inputAlumnoId.value = '';
            if (selectedBadge) selectedBadge.style.display = 'none';
            if (inputAlumnoSearch) {
                inputAlumnoSearch.parentElement.style.display = 'flex';
                inputAlumnoSearch.focus();
            }
            if (fallbackSelect) fallbackSelect.value = '';
        });
    }

    // Si se usa el select nativo de contingencia
    if (fallbackSelect) {
        fallbackSelect.addEventListener('change', (e) => {
            const val = e.target.value;
            if (val) {
                fetch(`?ajax=1&action=get_alumno&id=${encodeURIComponent(val)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        selectAlumno(data.data);
                    }
                });
            }
        });
    }

    // ── 2. Selección de Conceptos CARE ───────────────────────────────────────────
    careServiceCards.forEach(card => {
        card.addEventListener('click', () => {
            careServiceCards.forEach(c => c.classList.remove('is-active'));
            card.classList.add('is-active');

            const conceptoVal = card.getAttribute('data-concept');
            const sugeridoVal = card.getAttribute('data-price');

            if (inputConcepto) inputConcepto.value = conceptoVal;
            if (inputMonto && sugeridoVal) {
                inputMonto.value = parseFloat(sugeridoVal).toFixed(2);
            }
        });
    });

    // ── 3. Sincronización de Fechas ──────────────────────────────────────────────
    // Si cambia fecha de solicitud y fecha de servicio está vacía o igual, actualizarla
    if (inputFechaSolicitud && inputFechaServicio) {
        inputFechaSolicitud.addEventListener('change', (e) => {
            if (!inputFechaServicio.value) {
                inputFechaServicio.value = e.target.value;
            }
        });
    }

    // ── 4. Envío Asíncrono del Formulario (AJAX) ─────────────────────────────────
    formCargo.addEventListener('submit', (e) => {
        e.preventDefault();

        // Validaciones front
        const alumnoId = inputAlumnoId ? inputAlumnoId.value.trim() : '';
        const concepto = inputConcepto ? inputConcepto.value.trim() : '';
        const monto    = inputMonto ? parseFloat(inputMonto.value.trim()) : 0;
        const fechaSol = inputFechaSolicitud ? inputFechaSolicitud.value.trim() : '';
        const fechaSrv = inputFechaServicio ? inputFechaServicio.value.trim() : '';

        if (!alumnoId) {
            showAlert('Por favor busca y selecciona un alumno antes de registrar el cargo.', 'danger');
            showToast('Selecciona un alumno.', 'danger');
            if (inputAlumnoSearch && inputAlumnoSearch.parentElement.style.display !== 'none') {
                inputAlumnoSearch.focus();
            }
            return;
        }

        if (!concepto) {
            showAlert('Por favor selecciona uno de los servicios CARE (Transporte, Lunch, Estancia).', 'danger');
            showToast('Selecciona un concepto.', 'danger');
            return;
        }

        if (isNaN(monto) || monto <= 0) {
            showAlert('El monto debe ser una cantidad válida mayor a $0.00.', 'danger');
            showToast('Monto inválido.', 'danger');
            if (inputMonto) inputMonto.focus();
            return;
        }

        if (!fechaSol || !fechaSrv) {
            showAlert('Verifica que ambas fechas (Solicitud y Servicio) estén completas.', 'danger');
            showToast('Fechas incompletas.', 'danger');
            return;
        }

        // Estado cargando
        setLoading(true);

        const formData = new FormData(formCargo);
        formData.append('ajax', '1');
        formData.append('action', 'registrar_cargo');

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al procesar el cargo.');
            }
            return data;
        })
        .then(data => {
            setLoading(false);
            showAlert(data.message, 'success');
            showToast('Cargo registrado correctamente.', 'success');

            // Añadir fila a la tabla de últimos cargos
            if (data.data) {
                prependRowToTable(data.data);
            }

            // Resetear alumno y formulario (manteniendo fecha de hoy)
            resetFormKeepingDates();
        })
        .catch(err => {
            setLoading(false);
            showAlert(err.message, 'danger');
            showToast(err.message, 'danger');
        });
    });

    function setLoading(isLoading) {
        if (!btnSubmit) return;
        btnSubmit.disabled = isLoading;
        if (btnSpinner) btnSpinner.style.display = isLoading ? 'inline-block' : 'none';
        if (btnSubmitText) {
            btnSubmitText.textContent = isLoading ? 'Registrando Adeudo...' : 'Registrar Adeudo';
        }
    }

    function resetFormKeepingDates() {
        if (btnChangeAlumno) btnChangeAlumno.click();
        if (inputMonto) inputMonto.value = '';
        careServiceCards.forEach(c => c.classList.remove('is-active'));
        if (inputConcepto) inputConcepto.value = '';
    }

    if (btnClearForm) {
        btnClearForm.addEventListener('click', () => {
            resetFormKeepingDates();
            if (alertContainer) alertContainer.innerHTML = '';
            showToast('Formulario limpiado.', 'success');
        });
    }

    // ── 5. Inserción Dinámica en Tabla de Historial ───────────────────────────────
    function prependRowToTable(cargo) {
        if (!tablaCargosBody) return;
        if (emptyStateRow) emptyStateRow.remove();

        let badgeClass = 'concept-badge--otro';
        const cLower = (cargo.concepto || '').toLowerCase();
        if (cLower.includes('transporte')) badgeClass = 'concept-badge--transporte';
        else if (cLower.includes('lunch') || cLower.includes('comedor')) badgeClass = 'concept-badge--lunch';
        else if (cLower.includes('estancia')) badgeClass = 'concept-badge--estancia';

        const tr = document.createElement('tr');
        tr.style.animation = 'fadeInDown 0.3s ease-out';
        tr.innerHTML = `
            <td><strong>#${cargo.id}</strong></td>
            <td>
                <strong>${cargo.alumno_nombre}</strong><br>
                <small style="color: #64748b;">${cargo.curp} &bull; ${cargo.grado_grupo || ''} (${cargo.nivel_educativo || ''})</small>
            </td>
            <td>
                <span class="concept-badge ${badgeClass}">
                    ${cargo.concepto}
                </span>
            </td>
            <td><strong style="color: #0f172a;">${cargo.monto_formato}</strong></td>
            <td><span style="font-size: 12px; color: #475569;">${cargo.fecha_solicitud}</span></td>
            <td><span style="font-size: 12px; color: #475569;">${cargo.fecha_servicio}</span></td>
            <td><span class="status-badge status-badge--pendiente">Pendiente</span></td>
            <td><small style="color: #94a3b8;">Hoy</small></td>
        `;

        tablaCargosBody.insertBefore(tr, tablaCargosBody.firstChild);
    }
});
