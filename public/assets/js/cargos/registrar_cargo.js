// Espejo de compatibilidad para resolveViewAssets de ASRS
// Archivo base: public/assets/js/registrar_cargo.js
const script = document.createElement('script');
script.src = (window.baseUrl || '') + '/assets/js/registrar_cargo.js';
document.head.appendChild(script);
