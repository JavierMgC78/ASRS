/**
 * Comportamiento Base del Cascarón Público - ASRS
 * Inicialización de componentes globales para la interfaz pública.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Registro de telemetría ligera de carga o efectos sutiles de interfaz
    console.log('[ASRS Core] Cascarón público (lyt_public) inicializado correctamente.');

    // Ejemplo de interactividad ligera para navegación activa o efectos visuales
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.asrs-nav-links .asrs-nav-item');

    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.style.color = 'var(--asrs-neon-gold)';
            link.style.textShadow = '0 0 6px rgba(227, 179, 49, 0.4)';
        }
    });
});