<?php
/**
 * Vista de Contenido: Inicio (vw_home.php)
 * Framework: ASRS (Axe Secure Router System)
 */
?>
<div class="asrs-hero">
    <div class="asrs-hero-badge">
        <span class="asrs-badge-dot"></span>
        <span>AXE SECURE ROUTER SYSTEM v3.0</span>
    </div>
    
    <h1 class="asrs-hero-title">
        Enrutamiento Ultra Rápido <span class="asrs-gradient-text">O(1)</span> & Arquitectura Modular
    </h1>
    
    <p class="asrs-hero-subtitle">
        Diseñado para ofrecer rendimiento superior con caché nativa en memoria, renderizado virtual encapsulado y separación estricta entre zonas públicas y privadas bajo un estándar de seguridad de Nivel 1.
    </p>

    <div class="asrs-hero-actions">
        <a href="#metrics-section" class="asrs-btn asrs-btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            <span>Ver Métricas en Vivo</span>
        </a>
        <a href="#route-tester" class="asrs-btn asrs-btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="10 8 16 12 10 16 10 8"></polygon>
            </svg>
            <span>Probar Enrutador</span>
        </a>
    </div>
</div>

<!-- Grid de Métricas de Rendimiento -->
<section id="metrics-section" class="asrs-metrics-grid">
    <div class="asrs-metric-card glow-gold">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">⚡</span>
            <span class="asrs-metric-label">Latencia de Enrutado</span>
        </div>
        <div class="asrs-metric-value" id="metric-latency">0.04 ms</div>
        <div class="asrs-metric-footer">Búsqueda directa por hash map O(1)</div>
    </div>

    <div class="asrs-metric-card glow-silver">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">📦</span>
            <span class="asrs-metric-label">Vistas Cacheadas</span>
        </div>
        <div class="asrs-metric-value">4 Rutas</div>
        <div class="asrs-metric-footer">Sincronizadas desde config/routes_cache.php</div>
    </div>

    <div class="asrs-metric-card glow-red">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">🛡️</span>
            <span class="asrs-metric-label">Seguridad Global</span>
        </div>
        <div class="asrs-metric-value">Nivel 1 (Roles)</div>
        <div class="asrs-metric-footer">Validación perimetral de rutas</div>
    </div>

    <div class="asrs-metric-card glow-gold">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">🚀</span>
            <span class="asrs-metric-label">Consumo de Memoria</span>
        </div>
        <div class="asrs-metric-value"><?= memory_get_usage() >= 1048576 ? number_format(memory_get_usage() / 1048576, 2) . ' MB' : number_format(memory_get_usage() / 1024, 1) . ' KB' ?></div>
        <div class="asrs-metric-footer">Ejecución ultra ligera</div>
    </div>
</section>

<!-- Características Principales -->
<section class="asrs-features-section">
    <div class="asrs-section-header">
        <h2 class="asrs-section-title">Pilares de la Arquitectura ASRS</h2>
        <p class="asrs-section-desc">Estructurado para máxima escalabilidad, velocidad y mantenimiento limpio.</p>
    </div>

    <div class="asrs-features-grid">
        <div class="asrs-feature-card">
            <div class="asrs-feature-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
            </div>
            <h3>Caché de Vistas O(1)</h3>
            <p>Las rutas se autocompilan en un mapa de alta velocidad en <code>config/routes_cache.php</code>, eliminando consultas a base de datos en cada request cotidiano.</p>
        </div>

        <div class="asrs-feature-card">
            <div class="asrs-feature-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </div>
            <h3>Autocompilación (Lazy Generation)</h3>
            <p>El sistema detecta la ausencia de caché y consulta dinámicamente la tabla maestra <code>views</code> para recrear el entorno de forma transparente.</p>
        </div>

        <div class="asrs-feature-card">
            <div class="asrs-feature-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h3>Seguridad de Nivel 1 y Fino</h3>
            <p>Combina validaciones globales de roles perimetrales en el Router con controles de negocio granulares dentro de controladores y modelos.</p>
        </div>
    </div>
</section>