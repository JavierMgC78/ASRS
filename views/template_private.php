<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Panel Privado - Centro Educativo América'; ?></title>
    
    <!-- CSS Global del Template Privado (Según convención de ASRS) -->
    <link rel="stylesheet" href="<?= ($baseUrl ?? '') ?>/assets/css/template_private.css">

    <!-- CSS Específico de la Vista
         Ruta estándar: assets/css/{menu_group}/{name_file}.css
         La validación file_exists() es realizada por Core\Router::resolveViewAssets().
         $specificCss es null si el archivo no existe físicamente (sin error 404). -->
    <?php if (!empty($specificCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($specificCss) ?>">
    <?php endif; ?>
</head>
<body>
    <div class="asrs-private-layout">
        
        <!-- SIDEBAR / MENÚ DE NAVEGACIÓN DINÁMICO -->
        <aside class="asrs-sidebar">
            <div class="sidebar-brand">
                <h3>CEA ASRS</h3>
                <span class="user-role-badge"><?php echo htmlspecialchars($_SESSION['user']['role_name'] ?? 'Usuario'); ?></span>
            </div>

            <nav class="sidebar-nav">
                <?php 
                // $dynamicMenu es un array agrupado: ['NombreGrupo' => [items...]]
                // Ha sido filtrado dinámicamente según los permisos del rol del usuario activo
                // mediante la caché estática de permisos RBAC en Router::generateDynamicMenu().
                $hasVisibleModules = false;

                if (isset($dynamicMenu) && is_array($dynamicMenu) && count($dynamicMenu) > 0) {
                    foreach ($dynamicMenu as $groupName => $groupItems) {
                        // Ocultar automáticamente grupos que no tengan vistas autorizadas
                        if (!is_array($groupItems) || count($groupItems) === 0) continue;

                        $hasVisibleModules = true;

                        // Detectar si algún ítem del grupo es la ruta activa
                        // Comparamos la URI pública completa (con baseUrl) del ítem
                        // contra baseUrl + currentUri para abrir el acordeón correspondiente.
                        $fullCurrentUri = ($baseUrl ?? '') . ($currentUri ?? '');
                        $groupHasActive = false;
                        foreach ($groupItems as $item) {
                            if ($fullCurrentUri === ($item['uri'] ?? '')) {
                                $groupHasActive = true;
                                break;
                            }
                        }

                        $openClass = $groupHasActive ? ' is-open' : '';
                        echo '<div class="menu-accordion' . $openClass . '">';
                        echo '  <button class="accordion-trigger" type="button" aria-expanded="' . ($groupHasActive ? 'true' : 'false') . '">';
                        echo '    <span class="accordion-label">' . htmlspecialchars($groupName) . '</span>';
                        echo '    <svg class="accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
                        echo '  </button>';
                        echo '  <div class="accordion-panel">';
                        echo '    <ul class="menu-list">';
                        foreach ($groupItems as $item) {
                            if (!is_array($item)) continue;
                            $isActive = $fullCurrentUri === ($item['uri'] ?? '');
                            echo '      <li class="' . ($isActive ? 'active' : '') . '">';
                            echo '        <a href="' . htmlspecialchars($item['uri']) . '">'
                                       . htmlspecialchars($item['menu_title'])
                                       . '</a>';
                            echo '      </li>';
                        }
                        echo '    </ul>';
                        echo '  </div>';
                        echo '</div>';
                    }
                }

                if (!$hasVisibleModules) {
                    echo '<div class="sidebar-empty-notice">';
                    echo '  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
                    echo '  <span>Sin módulos asignados</span>';
                    echo '</div>';
                }
                ?>
            </nav>

            <!-- PIE DEL SIDEBAR CON BOTÓN DE LOGOUT -->
            <div class="sidebar-footer">
                <form action="<?= ($baseUrl ?? '') ?>/admin/logout" method="POST" class="logout-form">
                    <button type="submit" class="btn-logout">Cerrar Sesión</button>
                </form>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL -->
            <header class="asrs-topbar">
                <div class="topbar-left">
                    <span class="welcome-text">Hola, <strong><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Administrador'); ?></strong></span>
                </div>
                <div class="topbar-right">
                    <a href="<?= ($baseUrl ?? '') ?>/"
                       class="topbar-portal-link"
                       target="_blank"
                       rel="noopener noreferrer"
                       title="Abrir portal público en nueva pestaña">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10
                                     15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                        <span>Ver Portal Público</span>
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round"
                             class="topbar-external-icon">
                            <polyline points="15 3 21 3 21 9"/>
                            <path d="M10 14L21 3"/>
                            <polyline points="21 14 21 21 3 21 3 9"/>
                        </svg>
                    </a>
                </div>
            </header>

            <main class="asrs-content">
                <!-- Inyección del contenido virtualizado de la vista actual -->
                <?php echo $viewContent ?? ''; ?>
            </main>

            <footer class="asrs-footer">
                <p>&copy; <?php echo date('Y'); ?> Centro Educativo América - ASRS Framework</p>
            </footer>
        </div>

    </div>

    <!-- JS Global del Template Privado -->
    <script src="<?= ($baseUrl ?? '') ?>/assets/js/template_private.js"></script>

    <!-- JS Específico de la Vista
         Ruta estándar: assets/js/{menu_group}/{name_file}.js
         La validación file_exists() es realizada por Core\Router::resolveViewAssets().
         $specificJs es null si el archivo no existe físicamente (sin error 404). -->
    <?php if (!empty($specificJs)): ?>
        <script src="<?= htmlspecialchars($specificJs) ?>"></script>
    <?php endif; ?>
</body>
</html>