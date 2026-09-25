<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Panel Privado - Centro Educativo América'; ?></title>
    
    <!-- CSS Global del Template Privado (Según convención de ASRS) -->
    <link rel="stylesheet" href="/assets/css/template_private.css">

    <!-- CSS Específico de la Vista (Carga Convencional Dinámica por Core\Router) -->
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
                // Se detecta si el grupo contiene la vista activa para abrirlo por defecto.
                if (isset($dynamicMenu) && is_array($dynamicMenu) && count($dynamicMenu) > 0) {
                    foreach ($dynamicMenu as $groupName => $groupItems) {
                        if (!is_array($groupItems) || count($groupItems) === 0) continue;

                        // Detectar si algún ítem del grupo es la ruta activa
                        $groupHasActive = false;
                        foreach ($groupItems as $item) {
                            if (($currentUri ?? '') === ($item['raw_uri'] ?? '')) {
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
                            $isActive = ($currentUri ?? '') === ($item['raw_uri'] ?? '');
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
                ?>
            </nav>

            <!-- PIE DEL SIDEBAR CON BOTÓN DE LOGOUT -->
            <div class="sidebar-footer">
                <form action="/admin/logout" method="POST" class="logout-form">
                    <button type="submit" class="btn-logout">Cerrar Sesión</button>
                </form>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL -->
        <div class="asrs-main-wrapper">
            <header class="asrs-topbar">
                <div class="topbar-left">
                    <span class="welcome-text">Hola, <strong><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Administrador'); ?></strong></span>
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
    <script src="/assets/js/template_private.js"></script>

    <!-- JS Específico de la Vista (Carga Convencional Dinámica por Core\Router) -->
    <?php if (!empty($specificJs)): ?>
        <script src="<?= htmlspecialchars($specificJs) ?>"></script>
    <?php endif; ?>
</body>
</html>