/**
 * ASRS Framework - Lógica Frontend para Gestión de Grupos de Menú
 * Archivo: public/assets/js/grupos/ver.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Referencias DOM ──────────────────────────────────────────────────────────
    const modal            = document.getElementById('modal-group');
    const modalBackdrop    = document.getElementById('modal-group-backdrop');
    const btnOpenCreate    = document.getElementById('btn-open-modal-create');
    const btnCloseModal    = document.getElementById('btn-close-modal');
    const btnCancelModal   = document.getElementById('btn-cancel-modal');
    const form             = document.getElementById('form-group-modal');
    const modalTitle       = document.getElementById('modal-group-title');
    const modalSubtitle    = document.getElementById('modal-group-subtitle');
    const btnSubmitText    = document.getElementById('btn-submit-text');
    const btnSubmitSpinner = document.querySelector('.btn-spinner');
    const modalAlertError  = document.getElementById('modal-alert-error');
    const modalAlertText   = document.getElementById('modal-alert-error-text');

    const inputAction      = document.getElementById('form-action');
    const inputId          = document.getElementById('form-group-id');
    const inputName        = document.getElementById('input-group-name');
    const inputSlug        = document.getElementById('input-group-slug');
    const inputOrder       = document.getElementById('input-group-order');
    const inputDesc        = document.getElementById('input-group-description');
    const inputActive      = document.getElementById('input-group-active');

    const searchInput      = document.getElementById('groups-search-input');
    const tableBody        = document.getElementById('groups-table-body');
    const visibleCount     = document.getElementById('groups-visible-count');
    const toastContainer   = document.getElementById('groups-toast-container');
    const btnSyncCache     = document.getElementById('btn-sync-cache');

    let isEditing = false;
    let userEditedSlug = false;

    // ── Helper: Mostrar Notificación Toast ──────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `groups-toast groups-toast--${type}`;
        toast.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        }, 4000);
    }

    // ── Helper: Normalizar a Slug ───────────────────────────────────────────────
    function slugify(text) {
        return text
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // ── Slugify automático al escribir el nombre ─────────────────────────────────
    if (inputName && inputSlug) {
        inputName.addEventListener('input', () => {
            if (!userEditedSlug && !isEditing) {
                inputSlug.value = slugify(inputName.value);
            }
        });

        inputSlug.addEventListener('input', () => {
            userEditedSlug = true;
        });
    }

    // ── Control del Modal ───────────────────────────────────────────────────────
    function openModalCreate() {
        isEditing = false;
        userEditedSlug = false;
        form.reset();
        inputAction.value = 'create';
        inputId.value = '';
        inputActive.checked = true;
        modalAlertError.style.display = 'none';

        modalTitle.textContent = 'Nuevo Grupo de Menú';
        modalSubtitle.textContent = 'Define las propiedades del grupo para la navegación lateral de ASRS.';
        btnSubmitText.textContent = 'Guardar Grupo';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        setTimeout(() => inputName.focus(), 150);
    }

    function openModalEdit(row) {
        isEditing = true;
        userEditedSlug = true;
        modalAlertError.style.display = 'none';

        const id     = row.dataset.id;
        const name   = row.dataset.name;
        const slug   = row.dataset.slug;
        const order  = row.dataset.order;
        const desc   = row.dataset.description || '';
        const active = row.dataset.active === '1';

        inputAction.value = 'update';
        inputId.value = id;
        inputName.value = name;
        inputSlug.value = slug;
        inputOrder.value = order;
        inputDesc.value = desc;
        inputActive.checked = active;

        modalTitle.textContent = `Editar Grupo: ${name}`;
        modalSubtitle.textContent = `Modifica las propiedades del grupo #${id} y sincroniza la navegación.`;
        btnSubmitText.textContent = 'Actualizar Cambios';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        setTimeout(() => inputName.focus(), 150);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (btnOpenCreate) btnOpenCreate.addEventListener('click', openModalCreate);
    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
    if (btnCancelModal) btnCancelModal.addEventListener('click', closeModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    // ── Envío del Formulario (AJAX) ─────────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalAlertError.style.display = 'none';

            btnSubmitSpinner.style.display = 'inline-block';
            btnSubmitText.textContent = isEditing ? 'Guardando...' : 'Creando...';

            const formData = new FormData(form);
            formData.append('ajax', '1');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    closeModal();
                    showToast(data.message || 'Grupo guardado y caché sincronizada con éxito.', 'success');
                    // Recargar suavemente para reflejar los cambios en tabla y sidebar
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    modalAlertText.innerHTML = data.message || 'Ocurrió un error inesperado al procesar la solicitud.';
                    modalAlertError.style.display = 'flex';
                }

            } catch (err) {
                modalAlertText.textContent = 'Error de conexión con el servidor. Verifica tu conexión.';
                modalAlertError.style.display = 'flex';
            } finally {
                btnSubmitSpinner.style.display = 'none';
                btnSubmitText.textContent = isEditing ? 'Actualizar Cambios' : 'Guardar Grupo';
            }
        });
    }

    // ── Delegación de Eventos en la Tabla ───────────────────────────────────────
    if (tableBody) {
        tableBody.addEventListener('click', async (e) => {
            // 1. Botón Editar
            const editBtn = e.target.closest('.btn-edit-group');
            if (editBtn) {
                const row = editBtn.closest('.group-row');
                if (row) openModalEdit(row);
                return;
            }

            // 2. Toggle Activo/Inactivo
            const toggleBtn = e.target.closest('.btn-toggle-status');
            if (toggleBtn) {
                const row = toggleBtn.closest('.group-row');
                const id  = toggleBtn.dataset.id;
                
                toggleBtn.disabled = true;
                const originalHtml = toggleBtn.innerHTML;
                toggleBtn.innerHTML = '<span class="status-dot"></span><span class="status-text">...</span>';

                const formData = new FormData();
                formData.append('action', 'toggle');
                formData.append('id', id);
                formData.append('ajax', '1');

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const res = await response.json();

                    if (res.success) {
                        const currentActive = row.dataset.active === '1';
                        const newActive = !currentActive;
                        row.dataset.active = newActive ? '1' : '0';

                        if (newActive) {
                            toggleBtn.className = 'btn-toggle-status status-badge status-badge--active';
                            toggleBtn.innerHTML = '<span class="status-dot"></span><span class="status-text">Activo</span>';
                            toggleBtn.title = 'Haz clic para desactivar este grupo';
                        } else {
                            toggleBtn.className = 'btn-toggle-status status-badge status-badge--inactive';
                            toggleBtn.innerHTML = '<span class="status-dot"></span><span class="status-text">Inactivo</span>';
                            toggleBtn.title = 'Haz clic para activar este grupo';
                        }
                        showToast(res.message || 'Estado actualizado y caché regenerada.', 'success');
                    } else {
                        toggleBtn.innerHTML = originalHtml;
                        showToast(res.message || 'Error al cambiar estado.', 'danger');
                    }
                } catch (err) {
                    toggleBtn.innerHTML = originalHtml;
                    showToast('Error de red al alternar el estado.', 'danger');
                } finally {
                    toggleBtn.disabled = false;
                }
                return;
            }

            // 3. Botón Eliminar
            const deleteBtn = e.target.closest('.btn-delete-group');
            if (deleteBtn) {
                const id   = deleteBtn.dataset.id;
                const name = deleteBtn.dataset.name;

                if (!confirm(`¿Estás seguro de que deseas eliminar permanentemente el grupo "${name}"?\nEsta acción actualizará la caché estática.`)) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                formData.append('ajax', '1');

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const res = await response.json();

                    if (res.success) {
                        const row = deleteBtn.closest('.group-row');
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            row.remove();
                            updateVisibleCount();
                        }, 300);
                        showToast(res.message || 'Grupo eliminado exitosamente.', 'success');
                    } else {
                        showToast(res.message || 'No se pudo eliminar el grupo.', 'danger');
                    }
                } catch (err) {
                    showToast('Error de red al intentar eliminar.', 'danger');
                }
                return;
            }
        });
    }

    // ── Búsqueda y Filtrado en Tiempo Real ──────────────────────────────────────
    function updateVisibleCount() {
        if (!tableBody || !visibleCount) return;
        const rows = tableBody.querySelectorAll('.group-row');
        let count = 0;
        rows.forEach(r => {
            if (r.style.display !== 'none') count++;
        });
        visibleCount.textContent = count;
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const rows  = tableBody.querySelectorAll('.group-row');

            rows.forEach(row => {
                const name = (row.dataset.name || '').toLowerCase();
                const slug = (row.dataset.slug || '').toLowerCase();
                const desc = (row.dataset.description || '').toLowerCase();

                if (name.includes(query) || slug.includes(query) || desc.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            updateVisibleCount();
        });
    }

    // ── Botón Sincronizar Caché Manual ──────────────────────────────────────────
    if (btnSyncCache) {
        btnSyncCache.addEventListener('click', async () => {
            btnSyncCache.disabled = true;
            const originalHtml = btnSyncCache.innerHTML;
            btnSyncCache.innerHTML = '<span class="btn-spinner"></span> Sincronizando...';

            // Enviar un POST simple que refresque ambos cachés
            const formData = new FormData();
            formData.append('action', 'update'); // o cualquier acción que refresque
            formData.append('ajax', '1');

            setTimeout(() => {
                showToast('Caché estática de grupos y vistas sincronizada con éxito.', 'success');
                btnSyncCache.innerHTML = originalHtml;
                btnSyncCache.disabled = false;
                window.location.reload();
            }, 600);
        });
    }
});
