<?php
/**
 * Cascarón Público - ASRS (Axe Secure Router System)
 * Layout Base para páginas públicas
 * Archivo: app/Core/Views/layouts/lyt_public.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'ASRS | Acceso Público') ?></title>

    <!-- Estilos institucionales del Core / Layout -->
    <link rel="stylesheet" href="/assets/Core/css/layouts/lyt_public.css">
    
    <!-- Inyección dinámica de CSS específico por convención de vista -->
    <?php 
    if (!empty($css_asset) && (file_exists(($_SERVER['DOCUMENT_ROOT'] ?? '') . $css_asset) || file_exists(dirname(__DIR__, 4) . '/public' . preg_replace('#^/(AXE|Proyectos/Axe)/public#i', '', $css_asset)))) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($css_asset) . '">' . "\n";
    }
    ?>

</head>
<body class="asrs-layout-public">

    <!-- MENU SUPERIOR -->
    <header class="asrs-navbar">
        <div class="asrs-nav-brand">
            <span class="asrs-brand-icon">⚡</span>
            <span class="asrs-brand-text">ASRS <small>Framework</small></span>
        </div>
        <nav class="asrs-nav-links">
            <a href="/" class="asrs-nav-item">Inicio</a>
            <a href="/features" class="asrs-nav-item">Características</a>
            <a href="/docs" class="asrs-nav-item">Documentación</a>
        </nav>
        <div class="asrs-nav-action">
            <a href="/login" class="asrs-btn-login" title="Permite autenticarse y acceder a la zona privada">Acceder</a>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL (Área de inyección dinámica) -->
    <main class="asrs-main-content">
        <div class="asrs-content-wrapper">
            <?php 
            // Inyección segura de la vista de contenido capturada por el buffer del Router (Output Buffering)
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

    <!-- FOOTER PROFESIONAL -->
    <footer class="asrs-footer">
        <div class="asrs-footer-container">
            <p class="asrs-footer-copy">&copy; <?= date('Y'); ?> ASRS (Axe Secure Router System). Todos los derechos reservados.</p>
            <p class="asrs-footer-author">Diseñado por <strong>Javier Medina</strong> [Coffee Flavored Software]</p>
        </div>
    </footer>

    <!-- Scripts específicos -->
    <script src="/assets/core/js/layouts/lyt_public.js"></script>
    <!-- Inyección dinámica de JS específico por convención de vista -->
    <?php 
    if (!empty($js_asset) && (file_exists(($_SERVER['DOCUMENT_ROOT'] ?? '') . $js_asset) || file_exists(dirname(__DIR__, 4) . '/public' . preg_replace('#^/(AXE|Proyectos/Axe)/public#i', '', $js_asset)))) {
        echo '<script src="' . htmlspecialchars($js_asset) . '"></script>' . "\n";
    }
    ?>
</body>
</html>
