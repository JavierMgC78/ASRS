/**
 * ASRS Kernel - Cliente JS Principal (asraWeb)
 * Control de inicialización perimétrica y validación de interfaz de contornos.
 */

document.addEventListener("DOMContentLoaded", () => {
    console.log("[ASRS v1] Perímetro UI inicializado correctamente.");
    console.log("[ASRS v1] Coffee Flavored Software - Estética Neón Dark Activa.");

    // Resaltado dinámico de la ruta activa en el menú superior
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll(".asrs-nav .nav-link");

    navLinks.forEach(link => {
        if (link.getAttribute("href") === currentPath) {
            navLinks.forEach(l => l.classList.remove("active"));
            link.classList.add("active");
        }
    });
});