/**
 * ASRS FRAMEWORK - ALTA DE ALUMNO (crear.js)
 * Validación asíncrona de CURP, despliegue condicional por secciones y persistencia AJAX.
 */

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // 1. SELECTORES PRINCIPALES
    // ==========================================
    const curpInput         = document.getElementById('input-curp-check');
    const curpCounter       = document.getElementById('curp-char-count');
    const btnCheckCurp      = document.getElementById('btn-check-curp');
    const btnCheckCurpText  = document.getElementById('btn-check-curp-text');
    const curpSpinner       = document.getElementById('curp-spinner');
    const curpFeedbackBox   = document.getElementById('curp-feedback-box');

    const formAltaAlumno    = document.getElementById('form-alta-alumno');
    const hiddenCurp        = document.getElementById('hidden-curp');
    const displayLockedCurp = document.getElementById('display-locked-curp');
    const btnChangeCurp     = document.getElementById('btn-change-curp');

    const selectNivel       = document.getElementById('alumno_nivel');
    const selectGrado       = document.getElementById('alumno_grado');
    const inputFechaNac     = document.getElementById('alumno_fecha_nac');
    const selectGenero      = document.getElementById('alumno_genero');

    const toggleFactura     = document.getElementById('toggle-requiere-factura');
    const invoiceContainer  = document.getElementById('invoice-fields-container');

    const personsContainer  = document.getElementById('persons-container');
    const btnAddPerson      = document.getElementById('btn-add-person');

    const btnSubmitAlta     = document.getElementById('btn-submit-alta');
    const btnSubmitText     = document.getElementById('btn-submit-text');
    const submitSpinner     = document.getElementById('submit-spinner');
    const btnResetAlta      = document.getElementById('btn-reset-alta');
    const toastContainer    = document.getElementById('alumno-toast-container');

    // Catálogo de Grados por Nivel Educativo
    const gradosPorNivel = {
        'Preescolar': ['1° de Preescolar', '2° de Preescolar', '3° de Preescolar'],
        'Primaria': ['1° de Primaria', '2° de Primaria', '3° de Primaria', '4° de Primaria', '5° de Primaria', '6° de Primaria'],
        'Secundaria': ['1° de Secundaria', '2° de Secundaria', '3° de Secundaria'],
        'Bachillerato': ['1° Semestre', '2° Semestre', '3° Semestre', '4° Semestre', '5° Semestre', '6° Semestre']
    };

    // Regex oficial CURP mexicana (18 caracteres)
    const curpRegex = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/;

    // ==========================================
    // 2. EVENTOS DEL PASO 1 (CONSULTA ASÍNCRONA DE CURP)
    // ==========================================

    if (curpInput) {
        // Formateo en tiempo real a mayúsculas y conteo de caracteres
        curpInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            const len = this.value.length;
            if (curpCounter) {
                curpCounter.textContent = `${len}/18`;
            }

            curpInput.classList.remove('is-invalid', 'is-valid');
            if (curpFeedbackBox) {
                curpFeedbackBox.style.display = 'none';
                curpFeedbackBox.innerHTML = '';
            }

            // Si completa exactamente 18 caracteres y tiene formato válido, disparar consulta
            if (len === 18 && curpRegex.test(this.value)) {
                consultarCurpAjax(this.value);
            }
        });

        // Consulta al presionar Enter en el input
        curpInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                ejecutarValidacionCurp();
            }
        });
    }

    if (btnCheckCurp) {
        btnCheckCurp.addEventListener('click', function () {
            ejecutarValidacionCurp();
        });
    }

    function ejecutarValidacionCurp() {
        const curpVal = (curpInput.value || '').trim();
        if (!curpVal) {
            mostrarToast('Ingresa una CURP para consultar.', 'warning');
            curpInput.focus();
            return;
        }

        if (curpVal.length !== 18 || !curpRegex.test(curpVal)) {
            curpInput.classList.add('is-invalid');
            mostrarFeedbackCurp('error', 'Formato de CURP inválido. Debe contener exactamente 18 caracteres alfanuméricos válidos.');
            mostrarToast('La CURP no cumple con el formato oficial de 18 caracteres.', 'danger');
            return;
        }

        consultarCurpAjax(curpVal);
    }

    /**
     * Realiza la petición asíncrona (AJAX) al backend para consultar si la CURP ya existe.
     *
     * @param {string} curp Clave a consultar.
     */
    function consultarCurpAjax(curp) {
        // Estado de carga UI
        setCurpLoading(true);

        const formData = new FormData();
        formData.append('action', 'check_curp');
        formData.append('curp', curp);
        formData.append('ajax', '1');

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error en servidor (${response.status})`);
            }
            return response.json();
        })
        .then(res => {
            setCurpLoading(false);

            if (res.exists) {
                // ==========================================
                // CASO A: CURP DUPLICADA
                // ==========================================
                curpInput.classList.add('is-invalid');
                curpInput.classList.remove('is-valid');

                // Ocultar el resto del formulario
                ocultarFormularioCompleto();

                const al = res.alumno || {};
                const nombreCompleto = `${al.nombre || ''} ${al.primer_apellido || ''} ${al.segundo_apellido || ''}`.trim();

                mostrarFeedbackCurp('duplicate', `
                    <div class="duplicate-badge">CURP Ya Registrada (Duplicidad Detectada)</div>
                    <p style="margin: 4px 0 0 0; font-weight: 600;">${res.message}</p>
                    <div class="duplicate-details">
                        <div><strong>Alumno:</strong> ${nombreCompleto || 'Sin nombre'}</div>
                        <div><strong>Nivel / Grado:</strong> ${al.nivel_educativo || ''} ${al.grado || ''} (Grupo ${al.grupo || 'U'})</div>
                        <div><strong>Estatus:</strong> <span style="text-transform: capitalize;">${al.estado_alumno || 'Inscrito'}</span></div>
                        <div><strong>Fecha Alta:</strong> ${al.created_at || 'Previa'}</div>
                    </div>
                `);

                mostrarToast('Alerta: La CURP ya pertenece a un alumno registrado en el colegio.', 'danger');

            } else if (res.success) {
                // ==========================================
                // CASO B: CURP NO EXISTE (DISPONIBLE PARA ALTA)
                // ==========================================
                curpInput.classList.remove('is-invalid');
                curpInput.classList.add('is-valid');

                mostrarFeedbackCurp('available', `
                    <div class="available-msg-wrapper">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span><strong>CURP Disponible:</strong> No se encontraron registros previos. Se ha habilitado el expediente para captura.</span>
                    </div>
                `);

                mostrarToast('CURP validada exitosamente. Completando datos del alumno...', 'success');

                // Desplegar dinámicamente el formulario completo
                desplegarFormularioCompleto(curp);

            } else {
                // Error de validación de formato retornado por servidor
                curpInput.classList.add('is-invalid');
                mostrarFeedbackCurp('error', res.message || 'No se pudo verificar la CURP.');
                mostrarToast(res.message || 'Error al validar CURP.', 'danger');
            }
        })
        .catch(err => {
            setCurpLoading(false);
            console.error('Error AJAX CURP:', err);
            mostrarFeedbackCurp('error', 'Error de conexión al consultar la CURP. Verifica tu conexión.');
            mostrarToast('Error de red al consultar el servidor.', 'danger');
        });
    }

    function setCurpLoading(loading) {
        if (btnCheckCurp) {
            btnCheckCurp.disabled = loading;
        }
        if (curpSpinner) {
            curpSpinner.style.display = loading ? 'inline-block' : 'none';
        }
        if (btnCheckCurpText) {
            btnCheckCurpText.textContent = loading ? 'Consultando...' : 'Consultar CURP';
        }
    }

    function mostrarFeedbackCurp(type, htmlContent) {
        if (!curpFeedbackBox) return;
        curpFeedbackBox.className = 'curp-feedback-box';

        if (type === 'duplicate') {
            curpFeedbackBox.classList.add('curp-feedback-box--duplicate');
        } else if (type === 'available') {
            curpFeedbackBox.classList.add('curp-feedback-box--available');
        } else {
            curpFeedbackBox.classList.add('curp-feedback-box--duplicate');
        }

        curpFeedbackBox.innerHTML = htmlContent;
        curpFeedbackBox.style.display = 'block';
    }

    function ocultarFormularioCompleto() {
        if (formAltaAlumno) {
            formAltaAlumno.style.display = 'none';
        }
        if (hiddenCurp) {
            hiddenCurp.value = '';
        }
    }

    /**
     * Despliega de forma dinámica el resto del formulario organizado por secciones
     * e infiere automáticamente fecha de nacimiento y género desde la CURP.
     *
     * @param {string} curp
     */
    function desplegarFormularioCompleto(curp) {
        if (!formAltaAlumno) return;

        // Asignar al campo oculto y visual
        if (hiddenCurp) hiddenCurp.value = curp;
        if (displayLockedCurp) displayLockedCurp.textContent = curp;

        // Inferencia de Fecha de Nacimiento (Posición 4 a 9: AAMMDD)
        try {
            const anioStr = curp.substring(4, 6);
            const mesStr  = curp.substring(6, 8);
            const diaStr  = curp.substring(8, 10);

            // Determinar siglo (si año > 25 asumimos siglo 20, ej. 98 -> 1998, 08 -> 2008)
            const anioNum = parseInt(anioStr, 10);
            const siglo = anioNum > 30 ? '19' : '20';
            const fechaIso = `${siglo}${anioStr}-${mesStr}-${diaStr}`;

            if (inputFechaNac && !inputFechaNac.value) {
                inputFechaNac.value = fechaIso;
            }
        } catch (e) {
            console.warn('No se pudo inferir fecha de nacimiento:', e);
        }

        // Inferencia de Género (Posición 10: H = Hombre, M = Mujer)
        try {
            const sexoChar = curp.charAt(10);
            if (selectGenero) {
                if (sexoChar === 'H') {
                    selectGenero.value = 'M'; // M = Masculino en nuestro catálogo
                } else if (sexoChar === 'M') {
                    selectGenero.value = 'F'; // F = Femenino
                }
            }
        } catch (e) {
            console.warn('No se pudo inferir género:', e);
        }

        // Mostrar el formulario
        formAltaAlumno.style.display = 'flex';

        // Scroll suave hacia el formulario
        setTimeout(() => {
            formAltaAlumno.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Foco en el primer campo del alumno
            const primerCampo = document.getElementById('alumno_nombre');
            if (primerCampo) primerCampo.focus();
        }, 150);
    }

    // Permitir cambiar la CURP y reiniciar validación
    if (btnChangeCurp) {
        btnChangeCurp.addEventListener('click', function () {
            ocultarFormularioCompleto();
            if (curpFeedbackBox) curpFeedbackBox.style.display = 'none';
            if (curpInput) {
                curpInput.value = '';
                curpInput.classList.remove('is-valid', 'is-invalid');
                if (curpCounter) curpCounter.textContent = '0/18';
                curpInput.focus();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ==========================================
    // 3. CATÁLOGO DINÁMICO DE NIVELES Y GRADOS
    // ==========================================
    if (selectNivel && selectGrado) {
        selectNivel.addEventListener('change', function () {
            const nivel = this.value;
            selectGrado.innerHTML = '';

            if (!nivel || !gradosPorNivel[nivel]) {
                const optDef = document.createElement('option');
                optDef.value = '';
                optDef.textContent = 'Selecciona nivel primero...';
                selectGrado.appendChild(optDef);
                return;
            }

            const optVacia = document.createElement('option');
            optVacia.value = '';
            optVacia.textContent = 'Seleccionar grado...';
            selectGrado.appendChild(optVacia);

            gradosPorNivel[nivel].forEach(gradoTexto => {
                const opt = document.createElement('option');
                opt.value = gradoTexto;
                opt.textContent = gradoTexto;
                selectGrado.appendChild(opt);
            });
        });
    }

    // ==========================================
    // 4. PERSONAS AUTORIZADAS (REPEATER DINÁMICO)
    // ==========================================
    if (btnAddPerson && personsContainer) {
        btnAddPerson.addEventListener('click', function () {
            const total = personsContainer.querySelectorAll('.person-card-item').length;
            const newIndex = total + 1;

            const div = document.createElement('div');
            div.className = 'person-card-item';
            div.setAttribute('data-index', total);
            div.innerHTML = `
                <div class="person-card-header">
                    <span class="person-badge-num">Persona ${newIndex}</span>
                    <button type="button" class="btn-remove-person" title="Eliminar fila">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="auth_nombre[]" class="form-control" placeholder="Ej. Roberto Sánchez Gómez">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Parentesco / Relación</label>
                        <input type="text" name="auth_parentesco[]" class="form-control" placeholder="Ej. Tío / Familiar">
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
                        <input type="text" name="auth_observaciones[]" class="form-control" placeholder="Restricciones o especificaciones de entrega">
                    </div>
                </div>
            `;

            personsContainer.appendChild(div);

            // Re-asignar eventos de eliminar
            actualizarBotonesEliminarPersonas();
        });
    }

    function actualizarBotonesEliminarPersonas() {
        if (!personsContainer) return;
        const items = personsContainer.querySelectorAll('.person-card-item');

        items.forEach((item, idx) => {
            const btnDel = item.querySelector('.btn-remove-person');
            const badge = item.querySelector('.person-badge-num');

            if (badge) badge.textContent = `Persona ${idx + 1}`;

            if (btnDel) {
                // Si solo hay 1 fila, ocultar botón de borrar
                btnDel.style.display = items.length > 1 ? 'flex' : 'none';
                btnDel.onclick = function () {
                    item.remove();
                    actualizarBotonesEliminarPersonas();
                };
            }
        });
    }
    actualizarBotonesEliminarPersonas();

    // ==========================================
    // 5. SECCIÓN DE FACTURACIÓN (TOGGLE CONDICIONAL)
    // ==========================================
    if (toggleFactura && invoiceContainer) {
        toggleFactura.addEventListener('change', function () {
            const isChecked = this.checked;
            invoiceContainer.style.display = isChecked ? 'flex' : 'none';

            // Ajustar requerimientos HTML5
            const razon = document.getElementById('fac_razon');
            const rfc   = document.getElementById('fac_rfc');
            const cp    = document.getElementById('fac_cp');

            if (razon) razon.required = isChecked;
            if (rfc)   rfc.required   = isChecked;
            if (cp)    cp.required    = isChecked;
        });
    }

    // ==========================================
    // 6. NAVEGACIÓN ENTRE PESTAÑAS DE SECCIÓN
    // ==========================================
    const tabs = document.querySelectorAll('.sections-nav__tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-section');
            const targetEl = document.getElementById(targetId);

            if (targetEl) {
                tabs.forEach(t => t.classList.remove('is-active'));
                this.classList.add('is-active');
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ==========================================
    // 7. ENVÍO DEL FORMULARIO VÍA AJAX (PERSISTENCIA PDO)
    // ==========================================
    if (formAltaAlumno) {
        formAltaAlumno.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validaciones cliente obligatorias
            const curpFinal = (hiddenCurp.value || '').trim();
            if (!curpFinal) {
                mostrarToast('Error: Debes validar la CURP antes de enviar el registro.', 'danger');
                return;
            }

            const nombre = (document.getElementById('alumno_nombre').value || '').trim();
            const ape1   = (document.getElementById('alumno_primer_apellido').value || '').trim();
            const nivel  = (selectNivel.value || '').trim();
            const grado  = (selectGrado.value || '').trim();

            if (!nombre || !ape1 || !nivel || !grado) {
                mostrarToast('Por favor completa los campos obligatorios del Alumno (Nombre, Apellido, Nivel y Grado).', 'warning');
                return;
            }

            const t1Nombre = (document.getElementById('tutor1_nombre').value || '').trim();
            const t1Tel    = (document.getElementById('tutor1_tel_prin').value || '').trim();
            const t1Email  = (document.getElementById('tutor1_email').value || '').trim();

            if (!t1Nombre || !t1Tel || !t1Email) {
                mostrarToast('Por favor completa los datos obligatorios del Tutor 1 (Nombre, Teléfono y Correo).', 'warning');
                return;
            }

            // Estado cargando submit
            setSubmitLoading(true);

            const formData = new FormData(formAltaAlumno);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Error en servidor (${res.status})`);
                }
                return res.json();
            })
            .then(data => {
                setSubmitLoading(false);

                if (data.success) {
                    mostrarToast(data.message || '¡Alumno inscrito exitosamente!', 'success');

                    // Mostrar banner de éxito arriba
                    const alertContainer = document.getElementById('alumno-alert-container');
                    if (alertContainer) {
                        alertContainer.innerHTML = `
                            <div class="alumno-alert alumno-alert--success" role="alert">
                                <div class="alumno-alert__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                                <div class="alumno-alert__content">
                                    <div class="alumno-alert__title">¡Inscripción Exitosa!</div>
                                    <div class="alumno-alert__msg">${data.message}</div>
                                </div>
                                <button type="button" class="alumno-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
                            </div>
                        `;
                    }

                    // Resetear formulario para permitir nueva alta o redirigir
                    ocultarFormularioCompleto();
                    if (curpFeedbackBox) curpFeedbackBox.style.display = 'none';
                    if (curpInput) {
                        curpInput.value = '';
                        curpInput.classList.remove('is-valid', 'is-invalid');
                        if (curpCounter) curpCounter.textContent = '0/18';
                    }
                    formAltaAlumno.reset();
                    actualizarBotonesEliminarPersonas();

                    window.scrollTo({ top: 0, behavior: 'smooth' });

                } else {
                    mostrarToast(data.message || 'Error al persistir los datos del alumno.', 'danger');

                    const alertContainer = document.getElementById('alumno-alert-container');
                    if (alertContainer) {
                        alertContainer.innerHTML = `
                            <div class="alumno-alert alumno-alert--danger" role="alert">
                                <div class="alumno-alert__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div class="alumno-alert__content">
                                    <div class="alumno-alert__title">Atención</div>
                                    <div class="alumno-alert__msg">${data.message}</div>
                                </div>
                                <button type="button" class="alumno-alert__close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
                            </div>
                        `;
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            })
            .catch(err => {
                setSubmitLoading(false);
                console.error('Error al guardar alumno:', err);
                mostrarToast('Error de comunicación con el servidor al guardar el alumno.', 'danger');
            });
        });
    }

    function setSubmitLoading(loading) {
        if (btnSubmitAlta) btnSubmitAlta.disabled = loading;
        if (submitSpinner) submitSpinner.style.display = loading ? 'inline-block' : 'none';
        if (btnSubmitText) btnSubmitText.textContent = loading ? 'Guardando Expediente...' : 'Inscribir y Guardar Alumno';
    }

    if (btnResetAlta && formAltaAlumno) {
        btnResetAlta.addEventListener('click', function () {
            if (confirm('¿Estás seguro de que deseas limpiar todos los campos del expediente?')) {
                formAltaAlumno.reset();
                actualizarBotonesEliminarPersonas();
                mostrarToast('Campos del expediente limpiados.', 'warning');
            }
        });
    }

    // ==========================================
    // 8. NOTIFICACIONES TOAST FLOTANTES
    // ==========================================
    function mostrarToast(mensaje, tipo = 'info') {
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `alumno-toast alumno-toast--${tipo}`;

        let iconSvg = '';
        if (tipo === 'success') {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
        } else if (tipo === 'danger') {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
        } else if (tipo === 'warning') {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
        } else {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`;
        }

        toast.innerHTML = `
            ${iconSvg}
            <span style="flex-grow: 1;">${mensaje}</span>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) reverse forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
});
