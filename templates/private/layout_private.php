<?php
// ── Mapa de roles centralizado (fuente única de verdad) ─────────────────────
$roles = require BASE_PATH . '/config/roles.php';

// ══ MENÚ DE NAVEGACIÓN ADMIN ═════════════════════════════════════════════════
// Cada entrada define el título visible, la URL destino, el nivel mínimo
// requerido para verla y un grupo opcional para agrupar visualmente.
// El nivel se compara contra $usuario_nivel, disponible en scope desde
// el middleware de autenticación (index.php) vía Split Token en cookie.

$menu_admin = [
    [
        'titulo'        => '🏠 Inicio',
        'url'           => '/',
        'nivel_minimo'  => 0,
        'grupo'         => 'principal',
    ],
    [
        'titulo'        => '⚙️ Dashboard',
        'url'           => '/dashboard',
        'nivel_minimo'  => 0,
        'grupo'         => 'principal',
    ],
    [
        'titulo'        => '👥 Gestor de Usuarios',
        'url'           => '/gestor-usuarios',
        'nivel_minimo'  => 70,
        'grupo'         => 'herramientas',
    ],
    [
        'titulo'        => '🔍 Bitácora de Auditoría',
        'url'           => '/bitacora-auditoria',
        'nivel_minimo'  => 100,
        'grupo'         => 'herramientas',
    ],
    [
        'titulo'        => '🗺️ Gestor de Rutas',
        'url'           => '/gestor-rutas',
        'nivel_minimo'  => 100,
        'grupo'         => 'herramientas',
    ],
    [
        'titulo'        => '🛡️ Gestor de Roles',
        'url'           => '/gestor-roles',
        'nivel_minimo'  => 100,
        'grupo'         => 'herramientas',
    ],
    [
        'titulo'        => '🛡️ cobrar',
        'url'           => '/cobrar',
        'nivel_minimo'  => 20,
        'grupo'         => 'Cobranza',
    ],
];

// Nivel del usuario autenticado: viene resuelto por el middleware de
// autenticación de index.php (Split Token). Fallback a 0 por seguridad.
$nivel_usuario = isset($usuario_nivel) ? (int) $usuario_nivel : 0;

// Pre-filtrar el menú por nivel para no mezclar lógica en el HTML.
$menu_principal    = array_filter($menu_admin, fn($item) => $item['grupo'] === 'principal'    && $nivel_usuario >= $item['nivel_minimo']);
$menu_cobranza     = array_filter($menu_admin, fn($item) => $item['grupo'] === 'Cobranza'     && $nivel_usuario >= $item['nivel_minimo']);
$menu_herramientas = array_filter($menu_admin, fn($item) => $item['grupo'] === 'herramientas' && $nivel_usuario >= $item['nivel_minimo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Panel de Administración', ENT_QUOTES, 'UTF-8') ?> | Axe Framework</title>

    <!-- CSS estructural del layout admin -->
    <link rel="stylesheet" href="/assets/css/layoutAdmin.css">

    <?php
    // Inyección de CSS específico por vista (columna css de la tabla rutas)
    if (!empty($css_vista) && is_array($css_vista)) {
        foreach ($css_vista as $css) {
            echo "<link rel='stylesheet' href='" . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . "'>\n";
        }
    }
    ?>
</head>
<body>

    <div class="d-flex" style="min-height: 100vh;">

        <!-- ══ SIDEBAR ══════════════════════════════════════════════════════ -->
        <aside class="bg-secondary p-3 d-flex flex-column" style="width: 280px; min-width: 280px; flex-shrink: 0; min-height: 100vh;">

            <!-- Marca -->
            <div class="d-flex align-items-center mb-3 me-md-auto text-white text-decoration-none">
                <span class="fs-4 fw-bold text-primary">ADMIN Panel</span>
            </div>
            <hr>

            <!-- Enlace rápido: volver al sitio público -->
            <a href="/" class="nav-link d-flex align-items-center gap-2 mb-2 px-2 py-2 rounded"
               title="Ir al sitio web público"
               style="color:#a5b4fc;border:1px solid rgba(165,180,252,.2);background:rgba(165,180,252,.06);transition:background .2s,color .2s;"
               onmouseover="this.style.background='rgba(165,180,252,.15)';this.style.color='#c7d2fe';"
               onmouseout="this.style.background='rgba(165,180,252,.06)';this.style.color='#a5b4fc';">
                <i class="fas fa-globe" style="font-size:.85rem;"></i>
                <span style="font-size:.85rem;font-weight:600;">Ver Sitio Web</span>
            </a>

            <!-- Navegación principal -->
            <ul class="nav flex-column mb-auto">
                <?php foreach ($menu_principal as $item): ?>
                <li class="nav-item">
                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" class="nav-link text-white" data-nav>
                        <?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Sección de Cobranza -->
            <?php if (!empty($menu_cobranza)): ?>
            <hr>
            <p class="text-white-50" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem;">Cobranza</p>
            <ul class="nav flex-column mb-auto">
                <?php foreach ($menu_cobranza as $item): ?>
                <li class="nav-item">
                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" class="nav-link text-white" data-nav>
                        <?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <!-- Sección de Herramientas -->
            <?php if (!empty($menu_herramientas)): ?>
            <hr>
            <p class="text-white-50" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem;">Herramientas</p>
            <ul class="nav flex-column mb-auto">
                <?php foreach ($menu_herramientas as $item): ?>
                <li class="nav-item">
                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" class="nav-link text-white" data-nav>
                        <?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($nivel_usuario >= 100): ?>
            <!-- Botón: Refrescar caché de rutas -->
            <form method="POST" action="/gestor-rutas" style="margin-top:.6rem;"
                  onsubmit="return confirm('¿Regenerar rutas_cache.php desde la BD? El archivo actual se borrará.');">
                <input type="hidden" name="accion"     value="refrescar_cache">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit"
                        title="Borra rutas_cache.php y lo regenera desde la tabla rutas"
                        style="width:100%;display:flex;align-items:center;gap:.45rem;font-size:.8rem;font-weight:600;
                               color:#fbbf24;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);
                               border-radius:6px;padding:.38rem .75rem;cursor:pointer;transition:background .2s,color .2s;"
                        onmouseover="this.style.background='rgba(251,191,36,.2)';this.style.color='#fde68a';"
                        onmouseout="this.style.background='rgba(251,191,36,.08)';this.style.color='#fbbf24';">
                    🔄 <span>Refrescar Caché Rutas</span>
                </button>
            </form>
            <?php endif; ?>
            <?php endif; ?>

            <hr>
            <!-- Cerrar Sesión: visible siempre para cualquier usuario autenticado -->
            <ul class="nav flex-column">
                <li>
                    <a href="/logout" class="nav-link text-danger">
                        🚪 Cerrar Sesión
                    </a>
                </li>
            </ul>

        </aside>
        <!-- ════════════════════════════════════════════════════════════════ -->

        <!-- ══ ÁREA DE CONTENIDO ════════════════════════════════════════════ -->
        <main class="flex-grow-1 p-4" style="background-color: #0f111a; overflow: auto; min-width: 0;">

            <!-- Topbar interno del área de contenido -->
            <header class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                <h2 class="h3 mb-0"><?= htmlspecialchars($titulo ?? 'Panel de Control', ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="user-info text-muted d-flex align-items-center gap-2">
                    <?php
                        // Etiqueta del rol leída del mapa centralizado config/roles.php
                        $etiqueta_rol = $roles[$nivel_usuario] ?? 'Desconocido';
                    ?>
                    <!-- Enlace rápido al sitio público desde el topbar -->
                    <a href="/"
                       title="Ver el sitio web público"
                       style="display:inline-flex;align-items:center;gap:.4rem;font-size:.8rem;font-weight:600;color:#a5b4fc;text-decoration:none;padding:.28rem .75rem;border:1px solid rgba(165,180,252,.25);border-radius:20px;background:rgba(165,180,252,.07);transition:background .2s,color .2s;"
                       onmouseover="this.style.background='rgba(165,180,252,.18)';this.style.color='#c7d2fe';"
                       onmouseout="this.style.background='rgba(165,180,252,.07)';this.style.color='#a5b4fc';">
                        <i class="fas fa-globe" style="font-size:.75rem;"></i> Ver Sitio Web
                    </a>
                    <span
                        class="badge"
                        style="font-size:.72rem;font-weight:600;background:rgba(99,102,241,.15);color:#818cf8;border:1px solid rgba(99,102,241,.3);border-radius:20px;padding:.28rem .75rem;letter-spacing:.02em;"
                        title="Nivel <?= (int) $nivel_usuario ?>"
                    >
                        <?= htmlspecialchars($etiqueta_rol, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <small><?= htmlspecialchars($email_usuario ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </header>

            <!-- Contenido dinámico inyectado por ob_start() / ob_get_clean() -->
            <div id="contenedor-vista">
                <?php echo $contenido_vista ?? 'Error: Vista no capturada.'; ?>
            </div>

        </main>
        <!-- ════════════════════════════════════════════════════════════════ -->

    </div>

    <?php
    // Inyección de JS específico por vista (columna js de la tabla rutas)
    if (!empty($js_vista) && is_array($js_vista)) {
        foreach ($js_vista as $js) {
            echo "<script src='" . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . "'></script>\n";
        }
    }
    ?>

    <!-- Script de enlace activo: marca el item del sidebar según la URL actual -->
    <script>
        (function () {
            const path = window.location.pathname;
            document.querySelectorAll('[data-nav]').forEach(function (link) {
                const href = link.getAttribute('href');
                // Coincidencia exacta para "/" para no marcar todo como activo
                const isActive = href === '/'
                    ? path === '/'
                    : path === href || path.startsWith(href + '/');
                if (isActive) {
                    link.classList.add('active');
                }
            });
        })();
    </script>

</body>
</html>