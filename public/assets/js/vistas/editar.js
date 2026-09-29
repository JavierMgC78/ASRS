/**
 * ASRS Framework - Script para la Vista de Gestión de Vistas (Listado y Edición Inline)
 * Ubicación: public/assets/js/vistas/editar.js
 * 
 * Gestiona:
 * - Edición inline fila por fila con actualización vía AJAX.
 * - Validación en tiempo real de campos.
 * - Notificaciones dinámicas visuales de éxito/error.
 * - Filtrado y búsqueda instantánea en la tabla.
 */

document.addEventListener('DOMContentLoaded', () => {

    const table = document.getElementById('vistas-table');
    if (!table) return;

    const searchInput     = document.getElementById('vistas-search-input');
    const searchClear     = document.getElementById('vistas-search-clear');
    const filterLayout    = document.getElementById('filter-layout');
    const filterStatus    = document.getElementById('filter-status');
    const visibleCountEl  = document.getElementById('vistas-visible-count');
    const dynamicAlertBox = document.getElementById('vistas-dynamic-alert-box');

    // =========================================================================
    // ALERTAS VISUALES DINÁMICAS
    // =========================================================================

    /**
     * Muestra una notificación visual animada en la parte superior.
     * @param {'success'|'danger'|'warning'|'info'} type 
     * @param {string} title 
     * @param {string} message 
     * @param {number} autoDismissMs 
     */
    function showVisualAlert(type, title, message, autoDismissMs = 6000) {
        if (!dynamicAlertBox) return;

        // Limpiar alertas dinámicas previas
        dynamicAlertBox.innerHTML = '';

        const alertEl = document.createElement('div');
        alertEl.className = `vistas-alert vistas-alert--${type}`;
        alertEl.setAttribute('role', 'alert');

        let iconSvg = '';
        if (type === 'success') {
            iconSvg = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>`;
        } else if (type === 'danger') {
            iconSvg = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>`;
        } else {
            iconSvg = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>`;
        }

        alertEl.innerHTML = `
            <div class="vistas-alert__icon">${iconSvg}</div>
            <div class="vistas-alert__content">
                <strong>${escapeHtml(title)}</strong>
                <div>${message}</div>
            </div>
            <button type="button" class="vistas-alert__close" aria-label="Cerrar">&times;</button>
        `;

        const closeBtn = alertEl.querySelector('.vistas-alert__close');
        closeBtn.addEventListener('click', () => alertEl.remove());

        dynamicAlertBox.appendChild(alertEl);
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (autoDismissMs > 0) {
            setTimeout(() => {
                if (alertEl.parentElement) {
                    alertEl.remove();
                }
            }, autoDismissMs);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // =========================================================================
    // GESTIÓN DE EDICIÓN INLINE EN FILAS
    // =========================================================================

    /**
     * Almacén de valores originales por fila antes de editar para permitir cancelar.
     */
    const originalRowValues = {};

    /**
     * Activa el modo de edición inline en una fila.
     * @param {HTMLTableRowElement} row 
     */
    function enterEditMode(row) {
        const rowId = row.dataset.id;
        if (!rowId) return;

        // Guardar valores actuales antes de editar
        const titleInput     = row.querySelector('.input-title');
        const groupInput     = row.querySelector('.input-group');
        const layoutSelect   = row.querySelector('.select-layout');
        const uriInput       = row.querySelector('.input-uri');
        const nameFileInput  = row.querySelector('.input-namefile');
        const filePathInput  = row.querySelector('.input-filepath');
        const menuCheckbox   = row.querySelector('.input-showinmenu');
        const activeCheckbox = row.querySelector('.input-isactive');

        originalRowValues[rowId] = {
            title:      titleInput ? titleInput.value : '',
            group:      groupInput ? groupInput.value : '',
            layout:     layoutSelect ? layoutSelect.value : 'private',
            uri:        uriInput ? uriInput.value : '',
            nameFile:   nameFileInput ? nameFileInput.value : '',
            filePath:   filePathInput ? filePathInput.value : '',
            showInMenu: menuCheckbox ? menuCheckbox.checked : false,
            isActive:   activeCheckbox ? activeCheckbox.checked : false,
        };

        // Cambiar clases y visibilidad
        row.classList.add('is-editing');
        row.querySelectorAll('.cell-view').forEach(el => el.style.display = 'none');
        row.querySelectorAll('.cell-edit').forEach(el => el.style.display = 'block');

        const actionsView = row.querySelector('.actions-view');
        const actionsEdit = row.querySelector('.actions-edit');
        if (actionsView) actionsView.style.display = 'none';
        if (actionsEdit) actionsEdit.style.display = 'flex';

        // Poner foco en el campo de título
        if (titleInput) {
            titleInput.focus();
            titleInput.select();
        }
    }

    /**
     * Cancela el modo de edición y restaura los valores originales de la fila.
     * @param {HTMLTableRowElement} row 
     */
    function cancelEditMode(row) {
        const rowId = row.dataset.id;
        if (!rowId) return;

        const orig = originalRowValues[rowId];
        if (orig) {
            const titleInput     = row.querySelector('.input-title');
            const groupInput     = row.querySelector('.input-group');
            const layoutSelect   = row.querySelector('.select-layout');
            const uriInput       = row.querySelector('.input-uri');
            const nameFileInput  = row.querySelector('.input-namefile');
            const filePathInput  = row.querySelector('.input-filepath');
            const menuCheckbox   = row.querySelector('.input-showinmenu');
            const activeCheckbox = row.querySelector('.input-isactive');

            if (titleInput)     titleInput.value = orig.title;
            if (groupInput)     groupInput.value = orig.group;
            if (layoutSelect)   layoutSelect.value = orig.layout;
            if (uriInput)       uriInput.value = orig.uri;
            if (nameFileInput)  nameFileInput.value = orig.nameFile;
            if (filePathInput)  filePathInput.value = orig.filePath;
            if (menuCheckbox)   menuCheckbox.checked = orig.showInMenu;
            if (activeCheckbox) activeCheckbox.checked = orig.isActive;

            // Limpiar errores visuales
            row.querySelectorAll('.inline-input').forEach(inp => inp.classList.remove('is-invalid'));
        }

        // Restaurar visibilidad
        row.classList.remove('is-editing');
        row.querySelectorAll('.cell-view').forEach(el => el.style.display = 'block');
        row.querySelectorAll('.cell-edit').forEach(el => el.style.display = 'none');

        const actionsView = row.querySelector('.actions-view');
        const actionsEdit = row.querySelector('.actions-edit');
        if (actionsView) actionsView.style.display = 'flex';
        if (actionsEdit) actionsEdit.style.display = 'none';
    }

    /**
     * Guarda los cambios de una fila enviando los datos por AJAX.
     * @param {HTMLTableRowElement} row 
     */
    async function saveRowInline(row) {
        const rowId = row.dataset.id;
        if (!rowId) return;

        const titleInput     = row.querySelector('.input-title');
        const groupInput     = row.querySelector('.input-group');
        const layoutSelect   = row.querySelector('.select-layout');
        const uriInput       = row.querySelector('.input-uri');
        const nameFileInput  = row.querySelector('.input-namefile');
        const filePathInput  = row.querySelector('.input-filepath');
        const menuCheckbox   = row.querySelector('.input-showinmenu');
        const activeCheckbox = row.querySelector('.input-isactive');
        const saveBtn        = row.querySelector('.btn-row-save');

        // Validaciones en cliente
        let hasErrors = false;
        row.querySelectorAll('.inline-input').forEach(inp => inp.classList.remove('is-invalid'));

        if (!titleInput || titleInput.value.trim() === '') {
            if (titleInput) titleInput.classList.add('is-invalid');
            hasErrors = true;
        }
        if (!groupInput || groupInput.value.trim() === '') {
            if (groupInput) groupInput.classList.add('is-invalid');
            hasErrors = true;
        }
        if (!uriInput || uriInput.value.trim() === '') {
            if (uriInput) uriInput.classList.add('is-invalid');
            hasErrors = true;
        }
        if (!nameFileInput || nameFileInput.value.trim() === '') {
            if (nameFileInput) nameFileInput.classList.add('is-invalid');
            hasErrors = true;
        }
        if (!filePathInput || filePathInput.value.trim() === '') {
            if (filePathInput) filePathInput.classList.add('is-invalid');
            hasErrors = true;
        }

        if (hasErrors) {
            showVisualAlert(
                'danger',
                'Campos incompletos',
                'Por favor completa todos los campos requeridos en la fila antes de guardar.',
                4000
            );
            return;
        }

        // Preparar FormData
        const formData = new FormData();
        formData.append('id', rowId);
        formData.append('menu_title', titleInput.value.trim());
        formData.append('menu_group', groupInput.value.trim());
        formData.append('layout_type', layoutSelect.value);
        formData.append('uri', uriInput.value.trim());
        formData.append('name_file', nameFileInput.value.trim());
        formData.append('file_path', filePathInput.value.trim());
        if (menuCheckbox && menuCheckbox.checked) {
            formData.append('show_in_menu', '1');
        }
        if (activeCheckbox && activeCheckbox.checked) {
            formData.append('is_active', '1');
        }
        formData.append('ajax', '1');

        // Estado visual de guardando
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-inline"></span> <span class="btn-text">Guardando...</span>`;
        }

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Actualizar textos y badges de la fila en modo lectura
                const titleView   = row.querySelector('.title-text');
                const groupView   = row.querySelector('.group-badge');
                const layoutView  = row.querySelector('.layout-badge');
                const uriView     = row.querySelector('.uri-code');
                const previewHint = row.querySelector('.public-preview-hint');
                const nameView    = row.querySelector('.namefile-text');
                const pathView    = row.querySelector('.filepath-subtext');
                const menuView    = row.querySelector('.status-indicator');
                const statusView  = row.querySelector('.status-pill');

                if (titleView)   titleView.textContent   = result.data.menu_title;
                if (groupView)   groupView.textContent   = result.data.menu_group;
                if (uriView)     uriView.textContent     = result.data.uri;
                if (nameView)    nameView.textContent    = result.data.name_file;
                if (pathView) {
                    pathView.textContent = result.data.file_path;
                    pathView.title       = result.data.file_path;
                }
                if (previewHint) previewHint.textContent = result.public_uri;

                if (layoutView) {
                    const isPrivate = result.data.layout_type === 'private';
                    layoutView.textContent = isPrivate ? 'Privado (Admin)' : 'Público';
                    layoutView.className   = `layout-badge ${isPrivate ? 'layout-badge--private' : 'layout-badge--public'}`;
                    row.dataset.layout     = result.data.layout_type;
                }

                if (menuView) {
                    const inMenu = !!result.data.show_in_menu;
                    menuView.textContent = inMenu ? 'Sí' : 'No';
                    menuView.className   = `status-indicator ${inMenu ? 'is-yes' : 'is-no'}`;
                }

                if (statusView) {
                    const isActive = !!result.data.is_active;
                    statusView.textContent = isActive ? 'Activa' : 'Inactiva';
                    statusView.className   = `status-pill ${isActive ? 'status-pill--active' : 'status-pill--inactive'}`;
                    row.dataset.status     = isActive ? 'active' : 'inactive';
                }

                // Salir de modo edición
                row.classList.remove('is-editing');
                row.querySelectorAll('.cell-view').forEach(el => el.style.display = 'block');
                row.querySelectorAll('.cell-edit').forEach(el => el.style.display = 'none');

                const actionsView = row.querySelector('.actions-view');
                const actionsEdit = row.querySelector('.actions-edit');
                if (actionsView) actionsView.style.display = 'flex';
                if (actionsEdit) actionsEdit.style.display = 'none';

                // Añadir destello de guardado exitoso
                row.classList.remove('row-just-saved');
                void row.offsetWidth; // Forzar reflow
                row.classList.add('row-just-saved');

                // Notificación global
                showVisualAlert(
                    'success',
                    '¡Actualización Exitosa!',
                    `La vista <strong>${escapeHtml(result.data.menu_title)}</strong> (#${rowId}) ha sido actualizada y el archivo de caché de enrutamiento se ha regenerado correctamente.`,
                    5000
                );

            } else {
                showVisualAlert(
                    'danger',
                    'Error al Guardar',
                    result.error || 'Ocurrió un error inesperado al procesar los datos.'
                );
            }

        } catch (error) {
            console.error('Error al guardar vista inline:', error);
            showVisualAlert(
                'danger',
                'Error de Comunicación',
                'No se pudo conectar con el servidor para actualizar la vista. Inténtalo de nuevo.'
            );
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = `
                    <svg class="save-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span class="btn-text">Guardar</span>
                `;
            }
        }
    }

    // =========================================================================
    // DELEGACIÓN DE EVENTOS EN LA TABLA
    // =========================================================================

    table.addEventListener('click', (e) => {
        // Clic en botón "Editar"
        const editBtn = e.target.closest('.btn-row-edit');
        if (editBtn) {
            const row = editBtn.closest('.vistas-row');
            if (row) enterEditMode(row);
            return;
        }

        // Clic en botón "Cancelar"
        const cancelBtn = e.target.closest('.btn-row-cancel');
        if (cancelBtn) {
            const row = cancelBtn.closest('.vistas-row');
            if (row) cancelEditMode(row);
            return;
        }

        // Clic en botón "Guardar"
        const saveBtn = e.target.closest('.btn-row-save');
        if (saveBtn) {
            e.preventDefault();
            const row = saveBtn.closest('.vistas-row');
            if (row) saveRowInline(row);
            return;
        }
    });

    // Tecla Enter para guardar rápidamente dentro de un input inline
    table.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.classList.contains('inline-input')) {
            e.preventDefault();
            const row = e.target.closest('.vistas-row');
            if (row) saveRowInline(row);
        } else if (e.key === 'Escape' && e.target.classList.contains('inline-input')) {
            const row = e.target.closest('.vistas-row');
            if (row) cancelEditMode(row);
        }
    });

    // Limpieza de error en tiempo real
    table.addEventListener('input', (e) => {
        if (e.target.classList.contains('inline-input')) {
            e.target.classList.remove('is-invalid');
        }
    });

    // =========================================================================
    // FILTROS Y BÚSQUEDA DINÁMICA
    // =========================================================================

    function applyFilters() {
        const query        = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const layoutFilter = filterLayout ? filterLayout.value : 'all';
        const statusFilter = filterStatus ? filterStatus.value : 'all';

        const rows = table.querySelectorAll('tbody .vistas-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowLayout = row.dataset.layout || '';
            const rowStatus = row.dataset.status || '';

            // 1. Filtro por Layout
            const matchLayout = (layoutFilter === 'all' || rowLayout === layoutFilter);

            // 2. Filtro por Estado
            const matchStatus = (statusFilter === 'all' || rowStatus === statusFilter);

            // 3. Filtro por Texto
            let matchText = true;
            if (query !== '') {
                const titleText    = (row.querySelector('.title-text')?.textContent || '').toLowerCase();
                const groupText    = (row.querySelector('.group-badge')?.textContent || '').toLowerCase();
                const uriText      = (row.querySelector('.uri-code')?.textContent || '').toLowerCase();
                const nameText     = (row.querySelector('.namefile-text')?.textContent || '').toLowerCase();
                const filePathText = (row.querySelector('.filepath-subtext')?.textContent || '').toLowerCase();

                matchText = titleText.includes(query)
                         || groupText.includes(query)
                         || uriText.includes(query)
                         || nameText.includes(query)
                         || filePathText.includes(query);
            }

            if (matchLayout && matchStatus && matchText) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Actualizar visibilidad de cabeceras de grupo (ocultar si no tienen filas visibles)
        const groupHeaders = table.querySelectorAll('tbody .vistas-group-header-row');
        groupHeaders.forEach(gHeader => {
            const gKey = gHeader.dataset.groupKey;
            const hasVisibleRows = Array.from(table.querySelectorAll(`tbody .vistas-row[data-group-key="${gKey}"]`))
                                        .some(r => r.style.display !== 'none');
            gHeader.style.display = hasVisibleRows ? '' : 'none';
        });

        // Actualizar visibilidad de cabeceras de layout (ocultar si no tienen filas visibles)
        const layoutHeaders = table.querySelectorAll('tbody .vistas-layout-header-row');
        layoutHeaders.forEach(lHeader => {
            const lKey = lHeader.dataset.layout;
            const hasVisibleRows = Array.from(table.querySelectorAll(`tbody .vistas-row[data-layout="${lKey}"]`))
                                        .some(r => r.style.display !== 'none');
            lHeader.style.display = hasVisibleRows ? '' : 'none';
        });

        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }

        if (searchClear) {
            searchClear.style.display = query !== '' ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (searchClear) {
        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        });
    }

    if (filterLayout) {
        filterLayout.addEventListener('change', applyFilters);
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', applyFilters);
    }

});
