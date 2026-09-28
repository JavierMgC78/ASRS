document.addEventListener('DOMContentLoaded', () => {

    const groupInput   = document.getElementById('menu_group');
    const nameInput    = document.getElementById('name_file');
    const pathInput    = document.getElementById('file_path');
    const uriInput     = document.getElementById('uri');
    const layoutSelect = document.getElementById('layout_type');
    const form         = document.getElementById('form-crear-vista');

    // =========================================================================
    // NORMALIZACIÓN DE SLUG
    // =========================================================================

    /**
     * Convierte un texto libre a slug válido para URI y nombre de archivo.
     * Elimina acentos, reemplaza espacios/guiones por guiones bajos.
     * @param {string} text
     * @returns {string}
     */
    function slugify(text) {
        return text
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s_-]/g, '')
            .replace(/[\s-]+/g, '_');
    }

    // =========================================================================
    // AUTO-COMPLETADO DE CAMPOS DERIVADOS
    // =========================================================================

    /**
     * Actualiza file_path y uri automáticamente según menu_group, name_file y layout_type.
     * Contrato de URI: sin prefijo /admin/ — el Router lo añade para vistas privadas.
     */
    function updateDerivedFields() {
        const layout = layoutSelect ? layoutSelect.value : 'private';
        const group  = groupInput   ? slugify(groupInput.value) : '';
        const name   = nameInput    ? slugify(nameInput.value)  : '';

        if (group && name) {
            if (pathInput && !pathInput.dataset.manuallyEdited) {
                pathInput.value = `views/${layout}/${group}/${name}.php`;
            }
            if (uriInput && !uriInput.dataset.manuallyEdited) {
                uriInput.value = `${group}/${name}`;
            }
        }
    }

    // Marcar como editado manualmente cuando el usuario escribe directamente
    [pathInput, uriInput].forEach(input => {
        if (!input) return;
        input.addEventListener('input', () => {
            input.dataset.manuallyEdited = 'true';
        });
        input.addEventListener('change', () => {
            if (input.value.trim() === '') {
                delete input.dataset.manuallyEdited;
                updateDerivedFields();
            }
        });
    });

    [groupInput, nameInput].forEach(input => {
        if (input) input.addEventListener('input', updateDerivedFields);
    });
    if (layoutSelect) {
        layoutSelect.addEventListener('change', updateDerivedFields);
    }

    updateDerivedFields();

    // =========================================================================
    // MODAL DE CONFIRMACIÓN POR LAYOUT TYPE
    // =========================================================================

    /**
     * Crea e inyecta el modal en el DOM una sola vez; lo reutiliza en sucesivas aperturas.
     */
    function getOrCreateModal() {
        let modal = document.getElementById('modal-confirmar-vista');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'modal-confirmar-vista';
        modal.className = 'modal-overlay';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'modal-confirmar-titulo');
        modal.innerHTML = `
            <div class="modal-card">
                <div class="modal-icon" id="modal-icon-badge"></div>
                <h3 class="modal-title" id="modal-confirmar-titulo"></h3>
                <p  class="modal-body"  id="modal-confirmar-body"></p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" id="modal-btn-cancelar">
                        Cancelar — Corregir
                    </button>
                    <button type="button" class="modal-btn modal-btn-confirm" id="modal-btn-confirmar">
                        Sí, guardar vista
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        return modal;
    }

    /**
     * Abre el modal con contenido apropiado según el layout_type.
     * @param {'public'|'private'} layout
     * @returns {Promise<boolean>} Resuelve true si el usuario confirma, false si cancela.
     */
    function openConfirmModal(layout) {
        return new Promise((resolve) => {
            const modal     = getOrCreateModal();
            const icon      = modal.querySelector('#modal-icon-badge');
            const title     = modal.querySelector('#modal-confirmar-titulo');
            const body      = modal.querySelector('#modal-confirmar-body');
            const btnOk     = modal.querySelector('#modal-btn-confirmar');
            const btnCancel = modal.querySelector('#modal-btn-cancelar');

            const isPublic = layout === 'public';

            icon.textContent  = isPublic ? '🌐' : '🔒';
            icon.className    = `modal-icon ${isPublic ? 'modal-icon-public' : 'modal-icon-private'}`;
            title.textContent = isPublic ? 'Vista Pública' : 'Vista Privada (Admin)';
            body.innerHTML    = isPublic
                ? `Esta vista se está guardando como <strong>PÚBLICA</strong>.<br>
                   Será accesible para <strong>cualquier visitante</strong> sin autenticación.<br>
                   ¿Confirma que esta vista debe ser pública?`
                : `Esta vista se está guardando como <strong>PRIVADA</strong>.<br>
                   Solo usuarios autenticados con los permisos correctos podrán acceder.<br>
                   ¿Confirma que esta vista debe ser privada?`;

            btnOk.className = `modal-btn ${isPublic ? 'modal-btn-public' : 'modal-btn-private'}`;

            requestAnimationFrame(() => {
                modal.classList.add('is-open');
                btnOk.focus();
            });

            function cleanup() {
                btnOk.removeEventListener('click', onConfirm);
                btnCancel.removeEventListener('click', onCancel);
                // Sobrescribir el listener de Escape para evitar resolver dos veces
                modal._escapeResolve = null;
            }

            function onConfirm() { cleanup(); closeModal(); resolve(true);  }
            function onCancel()  { cleanup(); closeModal(); resolve(false); }

            btnOk.addEventListener('click',     onConfirm);
            btnCancel.addEventListener('click', onCancel);

            // Permitir que el listener de Escape cierre resolviendo false
            modal._escapeResolve = () => { cleanup(); resolve(false); };
        });
    }

    /** Cierra el modal removiendo la clase de visibilidad. */
    function closeModal() {
        const modal = document.getElementById('modal-confirmar-vista');
        if (!modal) return;
        modal.classList.remove('is-open');
        if (modal._escapeResolve) {
            const resolveFn = modal._escapeResolve;
            modal._escapeResolve = null;
            resolveFn();
        }
    }

    // Sobrescribir el listener de Escape para pasar la resolución
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const modal = document.getElementById('modal-confirmar-vista');
        if (modal && modal.classList.contains('is-open') && modal._escapeResolve) {
            closeModal();
        }
    });

    // =========================================================================
    // INTERCEPTAR SUBMIT DEL FORMULARIO
    // =========================================================================

    /**
     * Bandera: true cuando el usuario ya confirmó en el modal.
     * Permite que el segundo disparo de submit (form.requestSubmit) pase sin abrir el modal.
     */
    let isConfirmed = false;

    if (form && layoutSelect) {
        form.addEventListener('submit', async (e) => {
            if (isConfirmed) {
                isConfirmed = false;
                return; // Dejar pasar el submit nativo
            }

            e.preventDefault();

            const layout    = layoutSelect.value;
            const confirmed = await openConfirmModal(layout);

            if (confirmed) {
                isConfirmed = true;
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    }

});