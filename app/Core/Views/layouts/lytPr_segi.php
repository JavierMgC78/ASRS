<?php
/**
 * Cascarón Privado SEGI - ASRS (Axe Secure Router System)
 * Layout Base para el módulo SEGI
 * Archivo: app/Core/Views/layouts/lytPr_segi.php
 */
$currentUserRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? 'invitado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'ASRS | Módulo SEGI') ?></title>
    <link rel="stylesheet" href="/AXE/public/assets/core/css/layouts/lyt_public.css">
    <!-- Inyección dinámica de CSS específico por convención de vista -->
    <?php 
    if (!empty($css_asset) && (file_exists(($_SERVER['DOCUMENT_ROOT'] ?? '') . $css_asset) || file_exists(dirname(__DIR__, 4) . '/public' . preg_replace('#^/(AXE|Proyectos/Axe)/public#i', '', $css_asset)))) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($css_asset) . '">' . "\n";
    }
    ?>
</head>
<body class="asrs-layout-public">

    <header class="asrs-navbar">
        <div class="asrs-nav-brand">
            <span class="asrs-brand-icon">🎓</span>
            <span class="asrs-brand-text">SEGI <small>Control Escolar</small></span>
        </div>
        <nav class="asrs-nav-links">
            <a href="/" class="asrs-nav-item">Inicio</a>
            <a href="/segi/alumnos" class="asrs-nav-item" style="color: var(--asrs-neon-gold);">Alumnos</a>
            <span style="font-size: 0.8rem; color: #4ade80; border: 1px solid #4ade80; padding: 2px 8px; border-radius: 10px;">Rol: <?= htmlspecialchars($currentUserRole) ?></span>
        </nav>
        <div class="asrs-nav-action">
            <a href="/logout" class="asrs-btn-login" style="background: #333; box-shadow: none;">Salir</a>
        </div>
    </header>

    <main class="asrs-main-content">
        <div class="asrs-content-wrapper">
            <?php 
            if (isset($viewContent)) {
                if (is_string($viewContent) && file_exists($viewContent)) {
                    include $viewContent;
                } else {
                    echo $viewContent;
                }
            } else {
                echo "<div class='asrs-alert-error'>Error crítico: Vista de contenido no encontrada o no definida.</div>";
            }
            ?>
        </div>
    </main>

    <footer class="asrs-footer">
        <div class="asrs-footer-container">
            <p class="asrs-footer-copy">&copy; <?= date('Y'); ?> SEGI / ASRS Framework.</p>
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
