/**
 * ASRS Kernel - Telemetría Interactiva Vista Inicio
 * Simulación de monitoreo en tiempo real para latencia y memoria del sistema.
 */

document.addEventListener("DOMContentLoaded", () => {
    console.log("[ASRS Telemetry] Módulo de métricas en tiempo real iniciado.");

    const latencyEl = document.getElementById("metric-latency");
    const memoryEl = document.getElementById("metric-memory");

    function updateMetrics() {
        // Simulación sutil y realista de fluctuación en la telemetría del Kernel
        const latency = (Math.random() * (3.8 - 1.1) + 1.1).toFixed(2);
        const memory = (Math.random() * (14.5 - 12.5) + 12.5).toFixed(1);

        if (latencyEl) latencyEl.textContent = `${latency} ms`;
        if (memoryEl) memoryEl.textContent = `${memory} MB`;
    }

    // Ejecución inicial y ciclo de actualización en tiempo real cada 2.5 segundos
    updateMetrics();
    setInterval(updateMetrics, 2500);
});