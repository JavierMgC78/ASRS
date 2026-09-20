/**
 * ASRS - Script Global del Template Público
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log("Template público cargado correctamente desde Axe Secure Router System.");

    // Resaltar elemento activo del menú si no se resaltó en backend
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.public-nav a');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});
