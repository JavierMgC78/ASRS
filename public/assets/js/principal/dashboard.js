/**
 * ==========================================================
 * ASRS FRAMEWORK - VISTA DASHBOARD JS ESPECÍFICO
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // Simulación de carga dinámica de métricas para el panel principal
    const userCountElement = document.getElementById('active-users-count');
    
    if (userCountElement) {
        // Aquí posteriormente puedes conectar una petición fetch hacia tu API interna si lo requieres
        setTimeout(() => {
            userCountElement.textContent = '1 Activo (Sesión actual)';
        }, 300);
    }
});