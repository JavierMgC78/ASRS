/**
 * ASRS Framework - Script para la Vista de Roles (Crear)
 * Ubicación: public/assets/js/roles/crear.js
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-crear-rol');
    if (!form) return;

    const nameInput = document.getElementById('name');
    const typeSelect = document.getElementById('type');
    const descTextarea = document.getElementById('description');
    const submitBtn = document.getElementById('btn-submit-rol');
    const submitText = document.getElementById('btn-submit-text');

    /**
     * Muestra el mensaje de error y resalta el campo como inválido.
     * @param {HTMLElement} element 
     * @param {string} message 
     * @param {string} feedbackId 
     */
    function showError(element, message, feedbackId) {
        element.classList.add('is-invalid');
        element.setAttribute('aria-invalid', 'true');
        const feedbackEl = document.getElementById(feedbackId);
        if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.classList.add('is-visible');
        }
    }

    /**
     * Limpia el estado de error de un elemento.
     * @param {HTMLElement} element 
     * @param {string} feedbackId 
     */
    function clearError(element, feedbackId) {
        element.classList.remove('is-invalid');
        element.removeAttribute('aria-invalid');
        const feedbackEl = document.getElementById(feedbackId);
        if (feedbackEl) {
            feedbackEl.textContent = '';
            feedbackEl.classList.remove('is-visible');
        }
    }

    // Escuchas en tiempo real para remover errores conforme el usuario interactúa
    if (nameInput) {
        nameInput.addEventListener('input', () => {
            if (nameInput.value.trim().length >= 3) {
                clearError(nameInput, 'feedback-name');
            }
        });
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            if (typeSelect.value !== '') {
                clearError(typeSelect, 'feedback-type');
            }
        });
    }

    if (descTextarea) {
        descTextarea.addEventListener('input', () => {
            if (descTextarea.value.length <= 255) {
                clearError(descTextarea, 'feedback-description');
            }
        });
    }

    // Validación en el evento submit del formulario
    form.addEventListener('submit', (e) => {
        let hasErrors = false;
        let firstInvalidField = null;

        // 1. Validar Nombre
        if (nameInput) {
            const nameVal = nameInput.value.trim();
            if (nameVal === '') {
                showError(nameInput, 'El nombre del rol es obligatorio.', 'feedback-name');
                hasErrors = true;
                firstInvalidField = firstInvalidField || nameInput;
            } else if (nameVal.length < 3) {
                showError(nameInput, 'El nombre del rol debe tener al menos 3 caracteres.', 'feedback-name');
                hasErrors = true;
                firstInvalidField = firstInvalidField || nameInput;
            } else if (nameVal.length > 60) {
                showError(nameInput, 'El nombre no puede exceder 60 caracteres.', 'feedback-name');
                hasErrors = true;
                firstInvalidField = firstInvalidField || nameInput;
            } else {
                clearError(nameInput, 'feedback-name');
            }
        }

        // 2. Validar Tipo
        if (typeSelect) {
            if (typeSelect.value === '') {
                showError(typeSelect, 'Debes seleccionar un tipo de rol válido.', 'feedback-type');
                hasErrors = true;
                firstInvalidField = firstInvalidField || typeSelect;
            } else {
                clearError(typeSelect, 'feedback-type');
            }
        }

        // 3. Validar Descripción (longitud máxima)
        if (descTextarea && descTextarea.value.length > 255) {
            showError(descTextarea, 'La descripción no puede exceder 255 caracteres.', 'feedback-description');
            hasErrors = true;
            firstInvalidField = firstInvalidField || descTextarea;
        }

        // Si existen errores, detener envío y enfocar el primer campo inválido
        if (hasErrors) {
            e.preventDefault();
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return false;
        }

        // Si es válido, bloquear botón para evitar envíos múltiples
        if (submitBtn) {
            submitBtn.disabled = true;
            if (submitText) {
                submitText.textContent = 'Registrando rol...';
            }
        }
    });
});
