<section class="asrs-view-section">
    <div class="section-header">
        <h2 class="neon-title">Arquitectura del Kernel ASRS</h2>
        <p class="section-subtitle">Segregación absoluta de responsabilidades y diseño Multi-App de alto rendimiento.</p>
    </div>

    <!-- Contenedores en esquema Wireframe / Outline Grid -->
    <div class="architecture-grid">
        <!-- Bloque 1: El Núcleo (axeCore) -->
        <div class="outline-card">
            <div class="card-tag">[CORE_INFRA]</div>
            <h3>asrsCore (El Núcleo)</h3>
            <p>Motor de infraestructura independiente y ciego a la lógica de negocio. Administra la seguridad perimetral, el control de sesiones mediante Split Token y la resolución de rutas en tiempo real $O(1)$.</p>
            <ul class="tech-specs">
                <li>Autoloading PSR-4 Dinámico</li>
                <li>Conexión Singleton PDO</li>
                <li>Caché unificada (<code>routes.php</code> y <code>apps.php</code>)</li>
            </ul>
        </div>

        <!-- Bloque 2: Aplicaciones Modulares -->
        <div class="outline-card">
            <div class="card-tag">[MULTI_APP]</div>
            <h3>Aplicaciones Modulares</h3>
            <p>Entornos desacoplados (como <code>ceaWeb</code>, <code>ceaSegi</code> o <code>asraWeb</code>) que operan como capas conectadas al núcleo, permitiendo evolución o sustitución sin comprometer la estabilidad.</p>
            <ul class="tech-specs">
                <li>Estructura MVC independiente</li>
                <li>Prefijos de bases de datos aislados</li>
                <li>Layouts y assets desacoplados</li>
            </ul>
        </div>

        <!-- Bloque 3: Punto de Entrada Único -->
        <div class="outline-card">
            <div class="card-tag">[PERIMETER]</div>
            <h3>Punto de Entrada (public/)</h3>
            <p>Directorio único expuesto al servidor web protegido mediante reescritura de Apache (<code>.htaccess</code>), evitando la exposición directa de archivos sensibles y modelos del sistema.</p>
            <ul class="tech-specs">
                <li>Filtros perimetrales estrictos</li>
                <li>Excepción para recursos estáticos (assets)</li>
                <li>Despacho encapsulado por Output Buffering</li>
            </ul>
        </div>
    </div>
</section>