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
});