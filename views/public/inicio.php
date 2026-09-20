<!-- Vista Inicio - Axe Secure Router System (ASRS) -->
<div class="inicio-hero">
    <div class="hero-badge">
        <span class="badge-dot"></span>
        <span>AXE SECURE ROUTER SYSTEM v2.0</span>
    </div>
    
    <h1 class="hero-title">
        Enrutamiento Ultra Rápido <span class="gradient-text">O(1)</span> & Architecture Modular
    </h1>
    
    <p class="hero-subtitle">
        Diseñado para ofrecer rendimiento superior con caché nativa en memoria, renderizado virtual encapsulado y separación estricta entre zonas públicas y privadas.
    </p>

    <div class="hero-actions">
        <a href="#metrics-section" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            <span>Ver Métricas en Vivo</span>
        </a>
        <a href="#route-tester" class="btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="10 8 16 12 10 16 10 8"></polygon>
            </svg>
            <span>Probar Enrutador</span>
        </a>
    </div>
</div>

<!-- Grid de Métricas de Rendimiento -->
<section id="metrics-section" class="metrics-grid">
    <div class="metric-card glow-indigo">
        <div class="metric-header">
            <span class="metric-icon">⚡</span>
            <span class="metric-label">Latencia de Enrutado</span>
        </div>
        <div class="metric-value" id="metric-latency">0.04 ms</div>
        <div class="metric-footer">Búsqueda directa por hash map O(1)</div>
    </div>

    <div class="metric-card glow-cyan">
        <div class="metric-header">
            <span class="metric-icon">📦</span>
            <span class="metric-label">Vistas Cacheadas</span>
        </div>
        <div class="metric-value">7 Rutas</div>
        <div class="metric-footer">Sincronizadas desde ViewsCache</div>
    </div>

    <div class="metric-card glow-purple">
        <div class="metric-header">
            <span class="metric-icon">🛡️</span>
            <span class="metric-label">Seguridad de Acceso</span>
        </div>
        <div class="metric-value">Zonas LA / AU</div>
        <div class="metric-footer">Validación dinámica de permisos</div>
    </div>

    <div class="metric-card glow-emerald">
        <div class="metric-header">
            <span class="metric-icon">🚀</span>
            <span class="metric-label">Consumo de Memoria</span>
        </div>
        <div class="metric-value">&lt; 140 KB</div>
        <div class="metric-footer">Ejecución ultra ligera</div>
    </div>
</section>

<!-- Características Principales -->
<section class="features-section">
    <div class="section-header">
        <h2 class="section-title">Pilares de la Arquitectura ASRS</h2>
        <p class="section-desc">Estructurado para máxima escalabilidad, velocidad y mantenimiento limpio.</p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
            </div>
            <h3>Caché de Vistas O(1)</h3>
            <p>Las rutas se precargan en un array indexado de alta velocidad en <code>storage/views_cache.php</code>, eliminado consultas a BD en cada request.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </div>
            <h3>Renderizado Virtual (OB)</h3>
            <p>Utiliza <code>Output Buffering</code> para capturar la vista solicitada e inyectarla limpiamente en el template maestro correspondiente.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h3>Control de Zonas y Seguridad</h3>
            <p>Separa dinámicamente las rutas públicas (<code>template_public.php</code>) de las privadas (<code>template_private.php</code>) garantizando control de sesión.</p>
        </div>
    </div>
</section>

<!-- Probador de Rutas Interactivo (Demo en Vivo) -->
<section id="route-tester" class="tester-section">
    <div class="tester-card">
        <div class="tester-header">
            <h3>Inspector de Rutas en Tiempo Real</h3>
            <span class="status-indicator">Sistema Operativo</span>
        </div>

        <div class="tester-controls">
            <label>Selecciona una URI para simular el enrutado:</label>
            <div class="route-buttons">
                <button class="btn-route active" data-uri="/" data-file="views/public/inicio.php" data-layout="public" data-access="Libre Acceso (LA)">/</button>
                <button class="btn-route" data-uri="/nosotros" data-file="views/public/nosotros.php" data-layout="public" data-access="Libre Acceso (LA)">/nosotros</button>
                <button class="btn-route" data-uri="/contacto" data-file="views/public/contacto.php" data-layout="public" data-access="Libre Acceso (LA)">/contacto</button>
                <button class="btn-route" data-uri="/admin/dashboard" data-file="views/private/dashboard.php" data-layout="private" data-access="Usuario Autorizado (AU)">/admin/dashboard</button>
            </div>
        </div>

        <div class="tester-console">
            <div class="console-row">
                <span class="console-key">URI Solicitada:</span>
                <span class="console-val" id="console-uri">/</span>
            </div>
            <div class="console-row">
                <span class="console-key">Archivo Físico:</span>
                <span class="console-val highlight-blue" id="console-file">views/public/inicio.php</span>
            </div>
            <div class="console-row">
                <span class="console-key">Layout Maestro:</span>
                <span class="console-val highlight-purple" id="console-layout">template_public.php</span>
            </div>
            <div class="console-row">
                <span class="console-key">Nivel de Acceso:</span>
                <span class="console-val highlight-green" id="console-access">Libre Acceso (LA)</span>
            </div>
            <div class="console-row">
                <span class="console-key">Estado de Caché:</span>
                <span class="console-val text-success">HIT [O(1)]</span>
            </div>
        </div>
    </div>
</section>
