/**
 * ASRS FRAMEWORK - VISTA LISTADO DE ALUMNOS (ver.js)
 * Manejo de filtros en tiempo real, copia al portapapeles, modal de expediente y exportación CSV.
 */

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // 1. SELECTORES PRINCIPALES
    // ==========================================
    const searchInput      = document.getElementById('filter-search');
    const selectNivel      = document.getElementById('filter-nivel');
    const selectGrado      = document.getElementById('filter-grado');
    const selectGrupo      = document.getElementById('filter-grupo');
    const selectEstado     = document.getElementById('filter-estado');
    const btnClearSearch   = document.getElementById('btn-clear-search');
    const visibleCounter   = document.getElementById('visible-count');
    const tbody            = document.getElementById('alumnos-tbody');
    const btnExportCsv     = document.getElementById('btn-export-csv');

    // Modal
    const modalBackdrop    = document.getElementById('alumno-modal-backdrop');
    const btnModalClose    = document.getElementById('btn-modal-close');
    const btnModalCloseFtr = document.getElementById('btn-modal-close-footer');
    const btnModalPrint    = document.getElementById('btn-modal-print');
    const modalTabs        = document.querySelectorAll('.modal-tab');
    const modalSpinner     = document.getElementById('modal-spinner');

    const modalAvatar      = document.getElementById('modal-avatar');
    const modalTitle       = document.getElementById('modal-alumno-title');
    const modalMeta        = document.getElementById('modal-alumno-meta');

    // Toast
    const toast            = document.getElementById('alumnos-toast');
    const toastText        = document.getElementById('toast-text');
    let toastTimeout       = null;

    // Cache local de expedientes cargados para rendimiento instantáneo
    const studentCache = new Map();

    // ==========================================
    // 2. COPIAR AL PORTAPAPELES (CURP / MATRÍCULA)
    // ==========================================
    document.addEventListener('click', function (e) {
        const copyEl = e.target.closest('[data-copy]');
        if (copyEl) {
            const textToCopy = copyEl.getAttribute('data-copy');
            if (textToCopy && textToCopy !== 'S/M') {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showToast(`Copiado: ${textToCopy}`);
                }).catch(() => {
                    showToast('Texto copiado al portapapeles');
                });
            }
        }
    });

    function showToast(msg) {
        if (!toast || !toastText) return;
        toastText.textContent = msg;
        toast.style.display = 'flex';

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.style.display = 'none';
        }, 2200);
    }

    // ==========================================
    // 3. FILTRADO CLIENT-SIDE INSTANTÁNEO
    // ==========================================
    function filterTableRows() {
        const query  = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const nivel  = (selectNivel ? selectNivel.value : '').toLowerCase();
        const grado  = (selectGrado ? selectGrado.value : '').toLowerCase();
        const grupo  = (selectGrupo ? selectGrupo.value : '').toLowerCase();
        const estado = (selectEstado ? selectEstado.value : '').toLowerCase();

        const rows = document.querySelectorAll('.alumno-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.innerText.toLowerCase();
            const rowNivel = (row.querySelector('.badge-nivel')?.textContent || '').toLowerCase();
            const rowGradoGrupo = (row.querySelector('.badge-grado-grupo')?.textContent || '').toLowerCase();
            const rowEstado = (row.querySelector('.status-pill')?.textContent || '').toLowerCase();

            let matchQuery = !query || rowText.includes(query);
            let matchNivel = !nivel || rowNivel.includes(nivel);
            let matchGrado = !grado || rowGradoGrupo.includes(grado);
            let matchGrupo = !grupo || rowGradoGrupo.includes(`"${grupo}"`) || rowGradoGrupo.includes(`grupo ${grupo}`);
            let matchEstado = !estado || rowEstado.includes(estado);

            if (matchQuery && matchNivel && matchGrado && matchGrupo && matchEstado) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Actualizar visualización de separadores de grupo
        updateGroupHeadersVisibility();

        if (visibleCounter) {
            visibleCounter.textContent = visibleCount;
        }

        // Manejar empty state si todo se oculta
        const emptyRow = document.querySelector('.tr-empty-state');
        if (visibleCount === 0 && !emptyRow && tbody) {
            const tr = document.createElement('tr');
            tr.className = 'tr-empty-filter';
            tr.innerHTML = `
                <td colspan="6" style="padding: 40px; text-align: center; color: #64748b;">
                    <div style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 4px;">Sin resultados</div>
                    <div style="font-size: 13px;">No hay alumnos que coincidan con los filtros aplicados.</div>
                </td>
            `;
            tbody.appendChild(tr);
        } else if (visibleCount > 0) {
            const filterEmpty = document.querySelector('.tr-empty-filter');
            if (filterEmpty) filterEmpty.remove();
        }
    }

    function updateGroupHeadersVisibility() {
        const groupHeaders = document.querySelectorAll('.tr-group-header');
        groupHeaders.forEach(header => {
            let next = header.nextElementSibling;
            let hasVisibleUnderneath = false;
            while (next && !next.classList.contains('tr-group-header')) {
                if (next.classList.contains('alumno-row') && next.style.display !== 'none') {
                    hasVisibleUnderneath = true;
                    break;
                }
                next = next.nextElementSibling;
            }
            header.style.display = hasVisibleUnderneath ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterTableRows, 150));
    }
    if (selectNivel) selectNivel.addEventListener('change', filterTableRows);
    if (selectGrado) selectGrado.addEventListener('change', filterTableRows);
    if (selectGrupo) selectGrupo.addEventListener('change', filterTableRows);
    if (selectEstado) selectEstado.addEventListener('change', filterTableRows);

    if (btnClearSearch && searchInput) {
        btnClearSearch.addEventListener('click', function () {
            searchInput.value = '';
            btnClearSearch.remove();
            filterTableRows();
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // ==========================================
    // 4. MODAL DE EXPEDIENTE / FICHA DEL ALUMNO
    // ==========================================
    document.addEventListener('click', function (e) {
        const btnOpen = e.target.closest('.btn-open-detail');
        if (btnOpen) {
            const alumnoId = btnOpen.getAttribute('data-id');
            if (alumnoId) {
                openStudentModal(alumnoId);
            }
        }
    });

    function openStudentModal(alumnoId) {
        if (!modalBackdrop) return;
        modalBackdrop.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Reset tabs
        setModalTab('tab-personal');

        // Mostrar spinner
        if (modalSpinner) modalSpinner.style.display = 'flex';
        hideAllTabContents();

        if (studentCache.has(alumnoId)) {
            renderStudentDetail(studentCache.get(alumnoId));
            return;
        }

        // Petición AJAX para obtener el expediente completo
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_detail');
        url.searchParams.set('alumno_id', alumnoId);
        url.searchParams.set('ajax', '1');

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data) {
                studentCache.set(alumnoId, res.data);
                renderStudentDetail(res.data);
            } else {
                showModalError(res.message || 'Error al obtener el expediente del alumno.');
            }
        })
        .catch(err => {
            showModalError('Error de comunicación con el servidor: ' + err.message);
        });
    }

    function renderStudentDetail(data) {
        if (modalSpinner) modalSpinner.style.display = 'none';

        const al = data.alumno || {};
        const pl = data.plataforma || {};
        const tutores = data.tutores || [];
        const auths = data.personas_autorizadas || [];
        const fac = data.facturacion || {};

        // Header
        const nombreCompleto = `${al.nombre || ''} ${al.primer_apellido || ''} ${al.segundo_apellido || ''}`.trim();
        const iniciales = ((al.nombre ? al.nombre[0] : '') + (al.primer_apellido ? al.primer_apellido[0] : '')).toUpperCase();
        const nivelSlug = (al.nivel_educativo || '').toLowerCase().replace(/[^a-z]/g, '') || 'primaria';
        
        if (modalAvatar) {
            modalAvatar.textContent = iniciales || 'AL';
            modalAvatar.className = `modal-avatar modal-avatar--${nivelSlug}`;
        }
        if (modalTitle) modalTitle.textContent = nombreCompleto;
        if (modalMeta) {
            modalMeta.innerHTML = `
                <span class="legend-badge legend-badge--${nivelSlug}">${al.nivel_educativo || 'General'}</span>
                <span>•</span>
                <strong>${al.grado || ''} "${al.grupo || 'A'}"</strong>
                <span>•</span>
                <span>Matrícula: <code>${pl.matricula || 'S/M'}</code></span>
            `;
        }

        // Pestaña 1: Datos Personales
        const tabPersonal = document.getElementById('tab-personal');
        if (tabPersonal) {
            tabPersonal.innerHTML = `
                <div class="modal-info-grid">
                    <div class="modal-info-item">
                        <div class="info-label">CURP</div>
                        <div class="info-value"><code>${al.curp || '—'}</code></div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Fecha de Nacimiento</div>
                        <div class="info-value">${al.fecha_nacimiento || '—'}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Género</div>
                        <div class="info-value">${al.genero === 'M' ? 'Masculino' : (al.genero === 'F' ? 'Femenino' : 'Otro')}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Teléfono del Alumno</div>
                        <div class="info-value">${al.telefono || 'No registrado'}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Correo Electrónico</div>
                        <div class="info-value">${al.email || 'No registrado'}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Estatus Escolar</div>
                        <div class="info-value"><span class="status-pill status-pill--${(al.estado_alumno || '').toLowerCase()}"><span class="status-dot"></span>${al.estado_alumno || 'Inscrito'}</span></div>
                    </div>
                    <div class="modal-info-item modal-info-item--full">
                        <div class="info-label">Domicilio Completo</div>
                        <div class="info-value">${al.direccion || '—'}, Col. ${al.colonia || '—'}, C.P. ${al.codigo_postal || '—'}, ${al.municipio || 'Puebla'}, ${al.estado || 'Puebla'}</div>
                    </div>
                </div>
            `;
        }

        // Pestaña 2: Tutores y Familia
        const tabTutores = document.getElementById('tab-tutores');
        if (tabTutores) {
            let htmlTutores = '<div class="modal-info-grid">';
            if (tutores.length === 0) {
                htmlTutores += '<div class="modal-info-item modal-info-item--full"><div class="info-value text-muted">Sin tutores registrados en el expediente.</div></div>';
            } else {
                tutores.forEach((t, i) => {
                    htmlTutores += `
                        <div class="modal-info-item modal-info-item--full" style="border-left: 3px solid #2563eb;">
                            <div class="info-label">${t.tipo_tutor === 'tutor1' ? 'Tutor 1 (Principal)' : 'Tutor 2 (Secundario)'} - ${t.parentesco || 'Familiar'}</div>
                            <div class="info-value" style="font-size: 15px; margin-bottom: 6px;">${t.nombre_completo || '—'}</div>
                            <div style="font-size: 13px; color: #475569; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div><strong>Tel. Principal:</strong> ${t.telefono_principal || '—'}</div>
                                <div><strong>Tel. Secundario:</strong> ${t.telefono_secundario || '—'}</div>
                                <div><strong>Email:</strong> ${t.email || '—'}</div>
                                <div><strong>Ocupación:</strong> ${t.ocupacion || '—'}</div>
                                <div><strong>Lugar de Trabajo:</strong> ${t.lugar_trabajo || '—'}</div>
                                <div><strong>Resp. Económico:</strong> ${parseInt(t.es_responsable_economico) === 1 ? 'Sí' : 'No'}</div>
                            </div>
                        </div>
                    `;
                });
            }

            if (auths.length > 0) {
                htmlTutores += '<div class="modal-info-item modal-info-item--full"><div class="info-label" style="margin-top: 6px;">Personas Autorizadas para Recoger al Alumno</div>';
                auths.forEach(a => {
                    htmlTutores += `<div style="font-size: 13px; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">• <strong>${a.nombre_completo}</strong> (${a.parentesco || 'Autorizado'}) - Tel: ${a.telefono || '—'} [ID: ${a.identificacion_tipo || 'INE'}]</div>`;
                });
                htmlTutores += '</div>';
            }
            htmlTutores += '</div>';
            tabTutores.innerHTML = htmlTutores;
        }

        // Pestaña 3: Salud & Emergencia
        const tabMedico = document.getElementById('tab-medico');
        if (tabMedico) {
            tabMedico.innerHTML = `
                <div class="modal-info-grid">
                    <div class="modal-info-item">
                        <div class="info-label">Tipo de Sangre</div>
                        <div class="info-value" style="color: #dc2626;">${al.tipo_sangre || 'No especificado'}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Teléfono de Emergencia</div>
                        <div class="info-value">${al.telefono_emergencia || 'No registrado'}</div>
                    </div>
                    <div class="modal-info-item modal-info-item--full">
                        <div class="info-label">Contacto de Emergencia</div>
                        <div class="info-value">${al.contacto_emergencia || 'No registrado'}</div>
                    </div>
                    <div class="modal-info-item modal-info-item--full">
                        <div class="info-label">Alergias o Condiciones Médicas Relevantes</div>
                        <div class="info-value" style="line-height: 1.5;">${al.alergias_condiciones ? al.alergias_condiciones : '<span style="color: #16a34a;">Sin alergias o condiciones registradas</span>'}</div>
                    </div>
                </div>
            `;
        }

        // Pestaña 4: Plataforma Escolar
        const tabPlat = document.getElementById('tab-plataforma');
        if (tabPlat) {
            tabPlat.innerHTML = `
                <div class="modal-info-grid">
                    <div class="modal-info-item">
                        <div class="info-label">Matrícula Institucional</div>
                        <div class="info-value"><code>${pl.matricula || 'S/M'}</code></div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Usuario de Acceso</div>
                        <div class="info-value"><code>${pl.usuario_plataforma || '—'}</code></div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Email Institucional</div>
                        <div class="info-value">${pl.email_institucional || 'Sin correo institucional'}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="info-label">Estado de Cuenta</div>
                        <div class="info-value">${parseInt(pl.acceso_activo) === 1 ? '<span style="color: #16a34a; font-weight: 700;">✓ Acceso Habilitado</span>' : '<span style="color: #dc2626; font-weight: 700;">✗ Inactivo</span>'}</div>
                    </div>
                    <div class="modal-info-item modal-info-item--full">
                        <div class="info-label">Notas de Acceso</div>
                        <div class="info-value">${pl.notas_acceso || 'Sin observaciones de acceso registradas.'}</div>
                    </div>
                </div>
            `;
        }

        // Pestaña 5: Facturación
        const tabFac = document.getElementById('tab-facturacion');
        if (tabFac) {
            const reqFac = parseInt(fac.requiere_factura) === 1;
            tabFac.innerHTML = `
                <div class="modal-info-grid">
                    <div class="modal-info-item modal-info-item--full">
                        <div class="info-label">¿Requiere Facturación de Colegiaturas?</div>
                        <div class="info-value">${reqFac ? '<span style="color: #2563eb; font-weight: 700;">Sí, emite comprobantes fiscales</span>' : 'No requiere factura'}</div>
                    </div>
                    ${reqFac ? `
                        <div class="modal-info-item">
                            <div class="info-label">Razón Social</div>
                            <div class="info-value">${fac.razon_social || '—'}</div>
                        </div>
                        <div class="modal-info-item">
                            <div class="info-label">RFC</div>
                            <div class="info-value"><code>${fac.rfc || '—'}</code></div>
                        </div>
                        <div class="modal-info-item">
                            <div class="info-label">Régimen Fiscal</div>
                            <div class="info-value">${fac.regimen_fiscal || '—'}</div>
                        </div>
                        <div class="modal-info-item">
                            <div class="info-label">Uso de CFDI</div>
                            <div class="info-value">${fac.uso_cfdi || '—'}</div>
                        </div>
                        <div class="modal-info-item modal-info-item--full">
                            <div class="info-label">Domicilio Fiscal</div>
                            <div class="info-value">${fac.domicilio_fiscal || '—'} (C.P. ${fac.codigo_postal_fiscal || '—'})</div>
                        </div>
                        <div class="modal-info-item modal-info-item--full">
                            <div class="info-label">Correo para Envío de Facturas</div>
                            <div class="info-value">${fac.correo_facturacion || '—'}</div>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        // Mostrar pestaña activa
        const activeTab = document.querySelector('.modal-tab.is-active');
        if (activeTab) {
            const targetId = activeTab.getAttribute('data-tab');
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.style.display = 'block';
        }
    }

    function showModalError(msg) {
        if (modalSpinner) modalSpinner.style.display = 'none';
        const body = document.getElementById('modal-body-content');
        if (body) {
            body.innerHTML = `<div style="padding: 30px; text-align: center; color: #ef4444;">${msg}</div>`;
        }
    }

    function hideAllTabContents() {
        document.querySelectorAll('.modal-tab-content').forEach(el => {
            el.style.display = 'none';
        });
    }

    function setModalTab(tabId) {
        modalTabs.forEach(tab => {
            if (tab.getAttribute('data-tab') === tabId) {
                tab.classList.add('is-active');
            } else {
                tab.classList.remove('is-active');
            }
        });

        hideAllTabContents();
        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
    }

    modalTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const tabId = this.getAttribute('data-tab');
            setModalTab(tabId);
        });
    });

    function closeStudentModal() {
        if (!modalBackdrop) return;
        modalBackdrop.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (btnModalClose) btnModalClose.addEventListener('click', closeStudentModal);
    if (btnModalCloseFtr) btnModalCloseFtr.addEventListener('click', closeStudentModal);
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function (e) {
            if (e.target === modalBackdrop) {
                closeStudentModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalBackdrop && modalBackdrop.style.display !== 'none') {
            closeStudentModal();
        }
    });

    if (btnModalPrint) {
        btnModalPrint.addEventListener('click', function () {
            window.print();
        });
    }

    // ==========================================
    // 5. EXPORTACIÓN A CSV DE LA VISTA ACTUAL
    // ==========================================
    if (btnExportCsv) {
        btnExportCsv.addEventListener('click', function () {
            const rows = document.querySelectorAll('.alumno-row');
            if (rows.length === 0) {
                showToast('No hay registros para exportar');
                return;
            }

            const csvHeaders = ['Matricula', 'CURP', 'Nombre', 'Nivel', 'Grado', 'Grupo', 'Tutor', 'Telefono', 'Estatus'];
            const csvRows = [csvHeaders.join(',')];

            rows.forEach(row => {
                if (row.style.display === 'none') return;

                const matricula = (row.querySelector('.matricula-tag code')?.textContent || '').trim();
                const curp      = (row.getAttribute('data-curp') || '').trim();
                const nombre    = (row.querySelector('.nombre-principal')?.textContent || '').replace(/[\n\r]+/g, ' ').replace(/"/g, '""').trim();
                const nivel     = (row.querySelector('.badge-nivel')?.textContent || '').trim();
                const gradoGrp  = (row.querySelector('.badge-grado-grupo')?.textContent || '').trim();
                const tutor     = (row.querySelector('.tutor-name')?.textContent || '').replace(/"/g, '""').trim();
                const tel       = (row.querySelector('.contact-link span')?.textContent || '').trim();
                const estado    = (row.querySelector('.status-pill span:last-child')?.textContent || '').trim();

                csvRows.push([
                    `"${matricula}"`,
                    `"${curp}"`,
                    `"${nombre}"`,
                    `"${nivel}"`,
                    `"${gradoGrp}"`,
                    `""`,
                    `"${tutor}"`,
                    `"${tel}"`,
                    `"${estado}"`
                ].join(','));
            });

            const blob = new Blob(["\uFEFF" + csvRows.join("\n")], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            const dateStr = new Date().toISOString().slice(0, 10);
            link.setAttribute('download', `alumnos_cea_${dateStr}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast('Archivo CSV generado exitosamente');
        });
    }
});
