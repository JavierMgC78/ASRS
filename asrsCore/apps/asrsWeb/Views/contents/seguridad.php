<section class="asrs-view-section">
    <div class="section-header">
        <h2 class="neon-title">Blindaje Perimetral y Seguridad por Diseño</h2>
        <p class="section-subtitle">Arquitectura defensiva frente a vulnerabilidades del ecosistema y control total del vector de ataque.</p>
    </div>

    <!-- Grid de Tarjetas de Seguridad (Wireframe Outline) -->
    <div class="security-grid">
        <!-- Tarjeta 1: Aislamiento Perimetral -->
        <div class="outline-card security-card">
            <div class="card-tag">[PERIMETER_DEFENSE]</div>
            <h3>Punto de Entrada Único</h3>
            <p>Todo el tráfico HTTP pasa estrictamente a través de <code>public/index.php</code> mediante reglas de reescritura. Los modelos, controladores y el núcleo (<code>asrsCore</code>) están completamente aislados fuera del directorio web público, bloqueando la ejecución directa de scripts.</p>
        </div>

        <!-- Tarjeta 2: Split Token Authentication -->
        <div class="outline-card security-card">
            <div class="card-tag">[AUTH_PROTOCOL]</div>
            <h3>Patrón Split Token</h3>
            <p>Gestión de sesiones sin estado basada en doble factor: cookie <code>HttpOnly</code> para la Parte A y almacenamiento local o cabecera <code>Authorization</code> para la Parte B, mitigando riesgos de interceptación y ataques XSS.</p>
        </div>

        <!-- Tarjeta 3: Ventaja frente a vulnerabilidades de PHP -->
        <div class="outline-card security-card highlight-card">
            <div class="card-tag">[CORE_RESILIENCE]</div>
            <h3>Resiliencia ante Fallas del Lenguaje</h3>
            <p>Ante vulnerabilidades críticas reportadas en el núcleo de PHP, ASRS demuestra su ventaja estratégica: <strong>cero dependencias de terceros y ausencia de plugins opacos</strong>. Mientras los CMS monolíticos exponen cientos de vectores de ataque por complementos obsoletos o desatendidos, ASRS reduce la superficie al mínimo absoluto, permitiendo un control y parcheo directo del binario base sin intermediarios.</p>
        </div>
    </div>
</section>