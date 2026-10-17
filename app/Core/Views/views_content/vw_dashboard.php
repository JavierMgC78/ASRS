<?php
/**
 * Vista de Contenido: Dashboard de Administración (vw_dashboard.php)
 * Framework: ASRS (Axe Secure Router System)
 */
$currentRole = $userRole ?? ($_SESSION['role'] ?? 'admin');
?>
<div class="asrs-hero">
    <div class="asrs-hero-badge" style="background: rgba(74, 222, 128, 0.15); border-color: #4ade80; color: #4ade80;">
        <span class="asrs-badge-dot" style="background: #4ade80; box-shadow: 0 0 8px #4ade80;"></span>
        <span>SESIÓN PRIVADA AUTORIZADA • SEGURIDAD NIVEL 1 VALIDADA</span>
    </div>

    <h1 class="asrs-hero-title">
        Panel de Control <span class="asrs-gradient-text" style="background: linear-gradient(135deg, #4ade80, #22c55e); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Administrativo</span>
    </h1>

    <p class="asrs-hero-subtitle">
        Acceso concedido exitosamente. El despachador de vistas privadas de ASRS ha validado la existencia de la sesión y la concordancia del rol <strong><?= htmlspecialchars($currentRole) ?></strong> con las políticas de la ruta.
    </p>
</div>

<section class="asrs-metrics-grid">
    <div class="asrs-metric-card glow-gold">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">🛡️</span>
            <span class="asrs-metric-label">Estado de Seguridad</span>
        </div>
        <div class="asrs-metric-value" style="color: #4ade80;">Nivel 1 OK</div>
        <div class="asrs-metric-footer">Token y rol validados en Router</div>
    </div>

    <div class="asrs-metric-card glow-silver">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">👤</span>
            <span class="asrs-metric-label">Rol Activo</span>
        </div>
        <div class="asrs-metric-value"><?= htmlspecialchars($currentRole) ?></div>
        <div class="asrs-metric-footer">Privilegios totales del Core</div>
    </div>

    <div class="asrs-metric-card glow-red">
        <div class="asrs-metric-header">
            <span class="asrs-metric-icon">⚡</span>
            <span class="asrs-metric-label">Rendimiento</span>
        </div>
        <div class="asrs-metric-value">&lt; 1 ms</div>
        <div class="asrs-metric-footer">Sin impacto por el firewall perimetral</div>
    </div>
</section>
