<?php
/**
 * Cascarón Privado de Administración - ASRS (Axe Secure Router System)
 * Layout Base para páginas privadas del Core (Admin)
 * Archivo: app/Core/Views/layouts/lytPr_admin.php
 */
$currentUserRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? 'invitado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'ASRS | Panel Privado') ?></title>
    <!-- Estilos institucionales del Core / Layout -->
    <link rel="stylesheet" href="/AXE/public/assets/core/css/layouts/lyt_public.css">
    <!-- Inyección dinámica de CSS específico por convención de vista -->
    <?php 
    if (!empty($css_asset) && (file_exists(($_SERVER['DOCUMENT_ROOT'] ?? '') . $css_asset) || file_exists(dirname(__DIR__, 4) . '/public' . preg_replace('#^/(AXE|Proyectos/Axe)/public#i', '', $css_asset)))) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($css_asset) . '">' . "\n";
    }
    ?>
    <style>
        .asrs-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(227, 179, 49, 0.15);
            border: 1px solid var(--asrs-neon-gold);
            color: var(--asrs-neon-gold);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="asrs-layout-public">

    <!-- MENU SUPERIOR PRIVADO -->
    <header class="asrs-navbar">
        <div class="asrs-nav-brand">
            <span class="asrs-brand-icon">⚡</span>
            <span class="asrs-brand-text">ASRS <small>Admin Console</small></span>
        </div>
        <nav class="asrs-nav-links">
            <a href="/" class="asrs-nav-item">Sitio Público</a>
            <a href="/admin/dashboard" class="asrs-nav-item" style="color: var(--asrs-neon-gold);">Dashboard</a>
            <span class="asrs-admin-badge">Rol: <?= htmlspecialchars($currentUserRole) ?></span>
        </nav>
        <div class="asrs-nav-action">
            <a href="/logout" class="asrs-btn-login" style="background: linear-gradient(135deg, #444, #222); box-shadow: none;">Salir</a>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL (Inyección dinámica $viewContent) -->
    <main class="asrs-main-content">
        <div class="asrs-content-wrapper">
            <?php 
            // Inyección segura de la vista privada capturada por Output Buffering
            if (isset($viewContent)) {
                if (is_string($viewContent) && file_exists($viewContent)) {
                    include $viewContent;
                } else {
                    echo $viewContent;
                }
            } else {
                echo "<div class='asrs-alert-error'>Error crítico: Vista privada no encontrada o no definida.</div>";
            }
            ?>
        </div>
    </main>

    <!-- FOOTER PRIVADO -->
    <footer class="asrs-footer">
        <div class="asrs-footer-container">
            <p class="asrs-footer-copy">&copy; <?= date('Y'); ?> ASRS (Axe Secure Router System). Zona Privada con Seguridad Nivel 1.</p>
        </div>
    </footer>

    <!-- Inyección dinámica de JS específico por convención de vista -->
    <?php 
    if (!empty($js_asset) && (file_exists(($_SERVER['DOCUMENT_ROOT'] ?? '') . $js_asset) || file_exists(dirname(__DIR__, 4) . '/public' . preg_replace('#^/(AXE|Proyectos/Axe)/public#i', '', $js_asset)))) {
        echo '<script src="' . htmlspecialchars($js_asset) . '"></script>' . "\n";
    }
    ?>
</body>
</html>
