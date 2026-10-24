/**
 * ASRS Kernel - Módulo de Seguridad Perimétrica
 * Inicialización y telemetría de verificación de integridad del sistema.
 */

document.addEventListener("DOMContentLoaded", () => {
    console.log("[ASRS Security] Subsistema de blindaje perimetral y análisis de resiliencia activo.");

    // Efecto interactivo dinámico en las tarjetas de seguridad
    const cards = document.querySelectorAll(".security-card");
    cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            card.style.background = "rgba(22, 35, 54, 0.8)";
        });
        card.addEventListener("mouseleave", () => {
            card.style.background = "rgba(16, 25, 38, 0.6)";
        });
    });
});