/**
 * ASRS Kernel - Interacción Vista Arquitectura
 * Animación sutil de consola y telemetría de contornos.
 */

document.addEventListener("DOMContentLoaded", () => {
    console.log("[ASRS Architecture] Módulo de arquitectura perimétrica cargado.");

    // Efecto interactivo opcional sobre las tarjetas outline
    const cards = document.querySelectorAll(".outline-card");
    cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            const tag = card.querySelector(".card-tag");
            if (tag) tag.style.borderColor = "#00f3ff";
        });
        card.addEventListener("mouseleave", () => {
            const tag = card.querySelector(".card-tag");
            if (tag) tag.style.borderColor = "#E3B331";
        });
    });
});