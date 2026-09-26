<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Axe Secure Router System') ?></title>
    
    <!-- Fuentes Google: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Global del Template Público -->
    <link rel="stylesheet" href="<?= ($baseUrl ?? '') ?>/assets/css/template_public.css">

    <!-- CSS Específico de la Vista
         Ruta estándar: assets/css/{menu_group}/{name_file}.css
         La validación file_exists() es realizada por Core\Router::resolveViewAssets().
         $specificCss es null si el archivo no existe físicamente (sin error 404). -->
    <?php if (!empty($specificCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($specificCss) ?>">
    <?php endif; ?>
</head>
<body class="asrs-body">

    <!-- Encabezado del Sistema Público -->
    <header class="public-header">
        <div class="header-container">
            <a href="<?= ($baseUrl ?? '') ?>/" class="brand-container">
                <div class="brand-logo-wrapper">
                    <svg class="brand-svg-logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="10" fill="url(#logo-grad)"/>
                        <path d="M12 28L20 12L28 28H23L20 22L17 28H12Z" fill="white"/>
                        <path d="M16 20H24" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="logo-grad" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#6366F1"/>
                                <stop offset="1" stop-color="#06B6D4"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="brand-name"><?= htmlspecialchars($brandName ?? 'Axe System') ?></span>
                    <span class="brand-tag">ASRS Framework</span>
                </div>
            </a>

            <!-- Área del Menú Dinámico -->
            <nav class="public-nav">
                <ul>
                    <?php if (!empty($dynamicMenu)): ?>
                        <?php foreach ($dynamicMenu as $groupItems): ?>
                            <?php foreach ($groupItems as $item): 
                                $isActive = (isset($currentUri) && ($currentUri === $item['raw_uri'] || ($currentUri === '/' && $item['raw_uri'] === '/')));
                            ?>
                                <li>
                                    <a href="<?= htmlspecialchars($item['uri']) ?>" class="<?= $isActive ? 'active' : '' ?>">
                                        <?= htmlspecialchars($item['menu_title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- Botón específico para el logueo a la zona privada -->
            <div class="auth-action">
                <a href="<?= ($baseUrl ?? '') ?>/admin/login" class="btn-login">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Acceso Privado</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Contenido Principal con inyección de la vista renderizada virtualmente -->
    <main class="main-content">
        <?= $viewContent ?? '<p class="loading-state">Cargando contenido del sistema...</p>' ?>
    </main>

    <!-- Footer del Sistema -->
    <footer class="public-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <span class="footer-logo-title">Axe Secure Router System</span>
                <p>Arquitectura de enrutamiento O(1) de alto rendimiento para aplicaciones PHP modernas.</p>
            </div>
            <div class="footer-meta">
                <p>&copy; <?= date('Y') ?> Axe System. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- JS Global del Template Público -->
    <script src="<?= ($baseUrl ?? '') ?>/assets/js/template_public.js"></script>

    <!-- JS Específico de la Vista
         Ruta estándar: assets/js/{menu_group}/{name_file}.js
         La validación file_exists() es realizada por Core\Router::resolveViewAssets().
         $specificJs es null si el archivo no existe físicamente (sin error 404). -->
    <?php if (!empty($specificJs)): ?>
        <script src="<?= htmlspecialchars($specificJs) ?>"></script>
    <?php endif; ?>

</body>
</html>