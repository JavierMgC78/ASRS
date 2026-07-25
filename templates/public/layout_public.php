<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Axe Framework</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public/layout_public.css">

    <?php
    // Inyección de CSS adicional definido en la ruta
    if (!empty($css_vista) && is_array($css_vista)) {
        foreach ($css_vista as $css) {
            echo "<link rel='stylesheet' href='" . htmlspecialchars($css) . "'>\n";
        }
    }
    ?>
    
</head>
<body>

    <!-- ══ HEADER PÚBLICO ════════════════════════════════════════════════ -->
    <header class="pub-header">
        <p>LAYOUT PÚBLICO</p>
        <nav class="pub-nav" aria-label="Navegación pública">

            <!-- Marca -->
            <a href="/" class="pub-brand" title="Inicio">
                <span class="pub-brand-icon">Ax</span>
                Axe Framework
            </a>

            <?php
            // $opciones_publicas viene inyectado desde index.php vía LayoutPublicController
            $uri_actual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            ?>
            <!-- Navegación principal -->
            <ul class="pub-nav-links">
                <?php foreach ($opciones_publicas as $opcion) : ?>
                    <li>
                        <a href="<?= htmlspecialchars($opcion['uri'], ENT_QUOTES, 'UTF-8') ?>" 
                           class="<?= ($uri_actual === $opcion['uri']) ? 'activo' : '' ?>">
                            <?= htmlspecialchars($opcion['titulo'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Botón de acceso (candado) -->
            <div>
                <?php if ($usuario_autenticado_id): ?>
                    <!-- Usuario autenticado → ir al panel -->
                    <a href="/dashboard" class="btn-acceso panel" title="Ir al panel de control">
                        <!-- Ícono engranaje -->
                        <svg class="icon-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06
                                     a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                                     A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83
                                     l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09
                                     A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83
                                     l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09
                                     a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83
                                     l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09
                                     a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        <span class="label">Panel</span>
                    </a>
                <?php else: ?>
                    <!-- Visitante → ir al login -->
                    <a href="/login" class="btn-acceso login" title="Iniciar sesión">
                        <!-- Ícono candado -->
                        <svg class="icon-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span class="label">Ingresar</span>
                    </a>
                <?php endif; ?>
            </div>

        </nav>
    </header>
    <!-- ═══ VISTA ════════════════════════════════════════════════════════════════ -->

    <!-- Contenido de la vista -->
    <main class="pub-main">
        <?php echo $contenido_vista ?? 'Error: Vista no capturada.'; ?>
    </main>



    <!-- ═══ FOOTER PÚBLICO ═══════════════════════════════════════════════════════════════ -->
    <footer class="pub-footer">
        &copy; <?= date('Y') ?> Axe Framework &mdash; Secure Routing System
    </footer>

    <?php
    // Inyección de JS adicional definido en la ruta
    if (!empty($js_vista) && is_array($js_vista)) {
        foreach ($js_vista as $js) {
            echo "<script src='" . htmlspecialchars($js) . "'></script>\n";
        }
    }
    ?>
</body>
</html>
