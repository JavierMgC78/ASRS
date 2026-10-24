<section class="asrs-view-section">
    <div class="section-header">
        <h2 class="neon-title">Dashboard de Telemetría Perimétrica</h2>
        <p class="section-subtitle">Monitoreo en tiempo real del Kernel ASRS, rendimiento Multi-App y estado del núcleo.</p>
    </div>

    <!-- Panel de Tarjetas de Métricas en Tiempo Real (Outline Grid) -->
    <div class="metrics-grid">
        <!-- Métrica 1: Latencia -->
        <div class="outline-card metric-card">
            <div class="card-tag">[TELEMETRY_01]</div>
            <h3>Latencia de Carga</h3>
            <div class="metric-value" id="metric-latency">-- ms</div>
            <p class="metric-desc">Tiempo de respuesta optimizado O(1).</p>
        </div>

        <!-- Métrica 2: Memoria Ocupada -->
        <div class="outline-card metric-card">
            <div class="card-tag">[TELEMETRY_02]</div>
            <h3>Memoria Ocupada</h3>
            <div class="metric-value" id="metric-memory">-- MB</div>
            <p class="metric-desc">Asignación de RAM en el proceso actual.</p>
        </div>

        <!-- Métrica 3: Estado de Caché de Rutas -->
        <div class="outline-card metric-card">
            <div class="card-tag">[TELEMETRY_03]</div>
            <h3>Motor de Enrutamiento</h3>
            <div class="metric-value status-active">OPTIMO</div>
            <p class="metric-desc">Caché unificada <code>routes.php</code> activa.</p>
        </div>

        <!-- Métrica 4: Blindaje Perimetral -->
        <div class="outline-card metric-card">
            <div class="card-tag">[TELEMETRY_04]</div>
            <h3>Perímetro de Seguridad</h3>
            <div class="metric-value status-active">BLINDADO</div>
            <p class="metric-desc">Split Token & Auth perimetral operando.</p>
        </div>
    </div>
</section>