<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASRS | Axe Secure Router System</title>
    <link rel="stylesheet" href="/assets/asrsWeb/css/defaultStyles.css">
    <!-- Estilos CSS: layout + contenido (convención contents/{content}.css), resueltos por RoutingEngine -->
    <?php foreach (($cssFiles ?? []) as $css): ?>
    <link rel="stylesheet" href="/<?= htmlspecialchars(ltrim($css, '/')) ?>">
    <?php endforeach; ?>
</head>
<body>
    <!-- HEADER / BARRA DE NAVEGACIÓN PERIMETRAL -->
    <header class="asrs-header">
        <div class="asrs-container nav-container">
            <!-- 1) Área para Logo -->
            <div class="asrs-logo-box">
                <img src="/assets/asrsWeb/img/logo_axe.png" alt="ASRS Shield Logo" class="asrs-logo">
                <span class="asrs-brand-text">ASRS <span class="highlight">KERNEL</span></span>
            </div>

            <!-- 2) Menú de Navegación (3 opciones) -->
            <nav class="asrs-nav">
                <ul class="nav-list">
                    <li><a href="/asrsweb/inicio" class="nav-link active">Home</a></li>
                    <li><a href="/asrsweb/arquitectura" class="nav-link">Arquitectura</a></li>
                    <li><a href="/asrsweb/seguridad" class="nav-link">Seguridad</a></li>
                </ul>
            </nav>

            <!-- 3) Botón de Acceder -->
            <div class="asrs-actions">
                <a href="/asrsweb/login" class="btn-neon-outline">Acceder</a>
            </div>
        </div>
    </header>

    <!-- CONTENEDOR PRINCIPAL / INYECCIÓN DE VISTA -->
    <main class="asrs-main-content">
        <div class="asrs-container">
            <?= $content ?>
        </div>
    </main>

    <!-- 4) FOOTER INSTITUCIONAL -->
    <footer class="asrs-footer">
        <div class="asrs-container footer-flex">
            <p class="footer-copy">&copy; 2026 ASRS Kernel. Todos los derechos reservados.</p>
            <p class="footer-author">Desarrollado por <strong>Coffee Flavored Software</strong></p>
        </div>
    </footer>

    <!-- Scripts de cliente: layout + contenido (convención contents/{content}.js) -->
    <?php foreach (($jsFiles ?? []) as $js): ?>
    <script src="/<?= htmlspecialchars(ltrim($js, '/')) ?>"></script>
    <?php endforeach; ?>
</body>
</html>