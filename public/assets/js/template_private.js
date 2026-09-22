/**
 * ==========================================================
 * ASRS FRAMEWORK - TEMPLATE PRIVADO JS GLOBAL
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // Aquí puedes colocar lógica global del panel privado,
    // como gestión de notificaciones, confirmaciones de logout o colapso de menús.
    
    const logoutForm = document.querySelector('.logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', (e) => {
            // Opcional: Confirmación previa al cerrar sesión
            // if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
            //     e.preventDefault();
            // }
        });
    }
});