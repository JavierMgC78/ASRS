/**
 * ASRS Kernel - Interacción Vista Acceder (Login)
 * Gestiona el colapso de secciones y el envío seguro de credenciales.
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log("[ASRS Login] Módulo de autenticación perimétrica listo.");

    // Los botones de colapso ya funcionan de forma global si se carga main.js, 
    // pero aseguramos la lógica específica de la vista si es necesario:
    const collapseButtons = document.querySelectorAll('.asrs-section-bar .asrs-collapse-btn');

    collapseButtons.forEach(button => {
        // Evitar duplicar listeners si el layout global ya los gestiona
        if (button.dataset.initialized) return;
        button.dataset.initialized = "true";

        button.addEventListener('click', (event) => {
            event.preventDefault();
            const section = button.closest('.asrs-section');
            if (!section) return;

            const isCollapsed = section.classList.toggle('collapsed');
            button.setAttribute('aria-expanded', !isCollapsed);
        });
    });
});