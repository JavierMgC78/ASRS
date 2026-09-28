/**
 * ASRS Framework - Script para la Vista de Roles (Ver)
 * Ubicación: assets/js/roles/roles_ver.js
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('Módulo de roles inicializado correctamente.');

    // Opcional: Pequeña mejora interactiva para resaltar filas al pasar el cursor o filtrar
    const rows = document.querySelectorAll('.roles-table__row');
    rows.forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.transition = 'background-color 0.2s ease';
        });
    });
});