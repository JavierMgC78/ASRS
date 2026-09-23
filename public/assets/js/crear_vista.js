/**
 * ASRS FRAMEWORK - VISTA "CREAR VISTA" JS ESPECÍFICO
 * Asset: crear_vista.js | Convención: menu_title → crear_vista
 *
 * Responsabilidades:
 *  - Normalizar menu_title en tiempo real a formato slug.
 *  - Autogenerar URI: /admin/{menu_group}/{slug}
 *  - Autogenerar file_path: views/{layout_type}/{slug}.php
 *  - Retroalimentación visual al usuario mientras escribe.
 */

(function () {
    'use strict';

    /**
     * Normaliza un string a formato slug seguro para URLs y rutas de archivo.
     * Equivalente PHP: Router::normalizeNameToAsset()
     *
     * @param {string} text
     * @returns {string}
     */
    function normalizeSlug(text) {
        const accentMap = {
            'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ñ': 'n', 'ü': 'u',
            'Á': 'a', 'É': 'e', 'Í': 'i', 'Ó': 'o', 'Ú': 'u', 'Ñ': 'n', 'Ü': 'u'
        };
        return text
            .trim()
            .toLowerCase()
            .replace(/[áéíóúñüÁÉÍÓÚÑÜ]/g, c => accentMap[c] || c)
            .replace(/\s+/g, '_')
            .replace(/[^a-z0-9_]/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_|_$/g, '');
    }

    /**
     * Actualiza en tiempo real los campos readonly de URI y file_path
     * basándose en los valores actuales del formulario.
     */
    function updatePreview() {
        const titleEl   = document.getElementById('menu_title');
        const groupEl   = document.getElementById('menu_group');
        const layoutEl  = document.getElementById('layout_type');
        const uriEl     = document.getElementById('uri');
        const pathEl    = document.getElementById('file_path');

        if (!titleEl || !groupEl || !layoutEl || !uriEl || !pathEl) return;

        const titleSlug = normalizeSlug(titleEl.value) || 'nueva_vista';
        const groupSlug = normalizeSlug(groupEl.value) || 'general';
        const layout    = layoutEl.value || 'private';

        uriEl.value  = '/admin/' + groupSlug + '/' + titleSlug;
        pathEl.value = 'views/' + layout + '/' + titleSlug + '.php';

        // Feedback visual: indicar al usuario si el slug generado difiere del texto
        updateSlugFeedback(titleEl, titleSlug);
    }

    /**
     * Muestra una pequeña nota debajo del input menu_title
     * con el slug resultante cuando difiere del valor original.
     *
     * @param {HTMLElement} inputEl
     * @param {string} slug
     */
    function updateSlugFeedback(inputEl, slug) {
        const feedbackId = 'slug-feedback';
        let feedback = document.getElementById(feedbackId);

        const originalValue = inputEl.value.trim();
        const normalizedMatch = normalizeSlug(originalValue) === originalValue.toLowerCase().replace(/\s/g, '_');

        if (!feedback) {
            feedback = document.createElement('small');
            feedback.id = feedbackId;
            feedback.style.cssText = 'color: #2563eb; font-size: 12px; margin-top: 2px; display: block; font-family: monospace;';
            inputEl.parentElement.appendChild(feedback);
        }

        if (originalValue && slug !== originalValue) {
            feedback.textContent = '→ Slug resultante: ' + slug;
            feedback.style.display = 'block';
        } else {
            feedback.style.display = 'none';
        }
    }

    /**
     * Inicialización: adjuntar listeners a los campos relevantes.
     */
    function init() {
        const fields = ['menu_title', 'menu_group', 'layout_type'];

        fields.forEach(function (id) {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input',  updatePreview);
                el.addEventListener('change', updatePreview);
            }
        });

        // Ejecutar el preview inicial al cargar la página
        updatePreview();

        // Prevenir doble submit por botón
        const form = document.getElementById('crearVistaForm');
        const submitBtn = document.getElementById('btn-submit-crear');
        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Registrando...';
            });
        }
    }

    // Esperar al DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
