/**
 * Script Específico para la Vista Inicio (inicio.js)
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('Script de Inicio inicializado.');

    // 1. Probador de Rutas Interactivo
    const routeButtons = document.querySelectorAll('.btn-route');
    const consoleUri = document.getElementById('console-uri');
    const consoleFile = document.getElementById('console-file');
    const consoleLayout = document.getElementById('console-layout');
    const consoleAccess = document.getElementById('console-access');

    if (routeButtons.length > 0) {
        routeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Actualizar clases activas
                routeButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Obtener dataset
                const uri = btn.getAttribute('data-uri');
                const file = btn.getAttribute('data-file');
                const layout = btn.getAttribute('data-layout');
                const access = btn.getAttribute('data-access');

                // Animación sutil de actualización en la consola
                const consoleBox = document.querySelector('.tester-console');
                consoleBox.style.opacity = '0.4';

                setTimeout(() => {
                    if (consoleUri) consoleUri.textContent = uri;
                    if (consoleFile) consoleFile.textContent = file;
                    if (consoleLayout) consoleLayout.textContent = layout === 'public' ? 'template_public.php' : 'template_private.php';
                    if (consoleAccess) consoleAccess.textContent = access;
                    consoleBox.style.opacity = '1';
                }, 150);
            });
        });
    }

    // 2. Simulación de variación de latencia en vivo para demostrar velocidad
    const metricLatency = document.getElementById('metric-latency');
    if (metricLatency) {
        setInterval(() => {
            const randomLatency = (0.03 + Math.random() * 0.03).toFixed(2);
            metricLatency.textContent = `${randomLatency} ms`;
        }, 3000);
    }
});
