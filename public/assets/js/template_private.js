/**
 * ==========================================================
 * ASRS FRAMEWORK - TEMPLATE PRIVADO JS GLOBAL
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── ACCORDION DEL SIDEBAR ───────────────────────────────
    // Delega el evento en el <nav> para manejar cualquier cantidad de grupos.
    const sidebarNav = document.querySelector('.sidebar-nav');

    if (sidebarNav) {
        sidebarNav.addEventListener('click', (e) => {
            const trigger = e.target.closest('.accordion-trigger');
            if (!trigger) return;

            const accordion = trigger.closest('.menu-accordion');
            if (!accordion) return;

            const isOpen = accordion.classList.contains('is-open');

            // Toggle del grupo clickeado
            accordion.classList.toggle('is-open', !isOpen);
            trigger.setAttribute('aria-expanded', String(!isOpen));
        });
    }

    // ── CONFIRMACIÓN DE LOGOUT ──────────────────────────────
    const logoutForm = document.querySelector('.logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', (e) => {
            // Opcional: descomenta para activar el diálogo de confirmación
            // if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
            //     e.preventDefault();
            // }
        });
    }

    // ── COLLAPSIBLE SECTION HEADERS EN FORMULARIOS ASRS ──────
    document.addEventListener('click', (e) => {
        const header = e.target.closest('.asrs-section-header');
        if (!header) return;

        // Si el clic ocurrió sobre un control interactivo (botón, enlace, input, select), no colapsar
        if (e.target.closest('button, a, input, select, textarea') && !e.target.closest('.asrs-section-toggle-icon')) {
            return;
        }

        const container = header.closest('.asrs-form-container');
        if (container) {
            container.classList.toggle('is-collapsed');
        }
    });
});
