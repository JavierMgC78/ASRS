<?php
date_default_timezone_set('America/Merida');
define('BASE_PATH', dirname(__DIR__));

// ── SISTEMA DE LOGS SILENCIOSO ────────────────────────────────────────────────
// 1. Crear el directorio de logs si no existe
$logs_dir = BASE_PATH . '/storage/logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0777, true);
}

// 2. Directivas PHP: ocultar errores en frontend y activar registro interno
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 3. Enrutar todos los errores al archivo centralizado del framework
ini_set('error_log', $logs_dir . '/axe_errors.log');
// ─────────────────────────────────────────────────────────────────────────────

// ── INTERCEPTOR DE INSTALACIÓN ────────────────────────────────────────────────
// Si config.php no existe en la raíz del proyecto, el sistema aún no ha sido
// configurado. Se bloquea cualquier petición y se fuerza el flujo de instalación.
if (!file_exists(BASE_PATH . '/config.php')) {
    if (($_GET['action'] ?? '') !== 'install') {
        header('Location: ?action=install');
        exit;
    }
    require BASE_PATH . '/views/install.php';
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

// ── FIX B1: Headers de seguridad HTTP ────────────────────────────────────────

// Se envían antes de cualquier output para garantizar su presencia en toda
// respuesta del framework.
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; connect-src 'self';");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ── AUTO-COMPILACIÓN DE CACHÉ DE RUTAS ───────────────────────────────────────
// Si el archivo de caché no existe (fue borrado o nunca se generó), se
// compila automáticamente desde la base de datos antes de continuar.
// Esto evita errores fatales y hace el sistema auto-reparable.
$ruta_cache = BASE_PATH . '/config/rutas_cache.php';
if (!file_exists($ruta_cache)) {
    require_once BASE_PATH . '/config/database.php';   // retorna $pdo
    require_once BASE_PATH . '/core/Compilador.php';
    try {
        Compilador::actualizarRutas($pdo);
    } catch (RuntimeException $e) {
        error_log('Axe Router: fallo al regenerar caché de rutas — ' . $e->getMessage());
        http_response_code(503);
        exit('Error interno: no se pudo inicializar el sistema de rutas.');
    }
}
$rutas = require $ruta_cache;
// ─────────────────────────────────────────────────────────────────────────────

if (!array_key_exists($uri, $rutas)) {
    http_response_code(404);
    exit("404 - Ruta no encontrada.");
}

$ruta_activa = $rutas[$uri];

// ── 5. Middleware: Autenticación, Autorización e Identidad Global ─────────────
$usuario_autenticado_id = null;
$usuario_nivel = 0;
$usuario_activo = 0;

if (isset($_COOKIE['axe_auth'])) {
    require_once BASE_PATH . '/config/database.php';
    require_once BASE_PATH . '/core/Seguridad.php';

    // FIX M1: Usar la constante centralizada para el separador del token
    $partes_cookie = explode(Seguridad::TOKEN_SEPARATOR, $_COOKIE['axe_auth'], 2);
    if (count($partes_cookie) === 2) {
        $selector        = $partes_cookie[0];
        $validador_claro = $partes_cookie[1];

        // JOIN para traer la jerarquía y el estatus del usuario
        $sql = "SELECT t.usuario_id, t.validador_hash, t.expiracion, u.nivel_acceso, u.activo
                FROM auth_tokens t
                JOIN usuarios u ON t.usuario_id = u.id
                WHERE t.selector = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selector]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($registro && $registro['expiracion'] > date('Y-m-d H:i:s')) {
            if (Seguridad::verificarToken($registro['validador_hash'], $validador_claro)) {
                $usuario_autenticado_id = $registro['usuario_id'];
                $usuario_nivel  = (int) $registro['nivel_acceso'];
                $usuario_activo = (int) $registro['activo'];
            }
        }
    }
}

// ── FIX A2: Generar token CSRF para usuarios autenticados ─────────────────────

// Se usa la sesión PHP exclusivamente para el token CSRF.
// La arquitectura de autenticación (Split Token en cookie) no se altera.
$csrf_token = '';
if ($usuario_autenticado_id) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];
}

// B. Aduana de Acceso y Autorización Jerárquica
if (isset($ruta_activa['requiere_login']) && $ruta_activa['requiere_login'] == 1) {

    // Filtro 1: Identidad
    if (!$usuario_autenticado_id) {
        setcookie('axe_auth', '', time() - 3600, '/');
        header("Location: /login");
        exit;
    }

    // Filtro 2: Estatus (¿Está suspendido?)
    if ($usuario_activo !== 1) {
        http_response_code(403);
        $titulo_error   = "Cuenta Suspendida";
        $mensaje_error  = "Tu acceso al sistema ha sido revocado. Por favor, contacta a coordinación para más detalles.";
        $ruta_retorno   = '/logout'; // Cierra sesión y regresa a la zona pública
        require BASE_PATH . '/views/403.php';
        exit;
    }

    // Filtro 3: Jerarquía Matemática
    $nivel_requerido = isset($ruta_activa['nivel_minimo']) ? (int) $ruta_activa['nivel_minimo'] : 0;
    if ($usuario_nivel < $nivel_requerido) {
        http_response_code(403);
        $titulo_error  = "Acceso Restringido";
        $mensaje_error = "Esta área requiere un nivel de autorización superior (Nivel $nivel_requerido). Tu nivel actual es $usuario_nivel.";
        $ruta_retorno  = '/'; // Regresa al inicio público; la sesión se preserva
        require BASE_PATH . '/views/403.php';
        exit;
    }
}

// ── 6. Carga de vistas y controladores ───────────────────────────────────────

// ── FIX C2: Validar que vista y plantilla estén dentro de sus directorios ─────
// Resolve las rutas para detectar cualquier intento de path traversal.
$views_dir     = realpath(BASE_PATH . '/views');
$templates_dir = realpath(BASE_PATH . '/templates');
$controllers_dir = realpath(BASE_PATH . '/controllers');

$ruta_vista_raw     = BASE_PATH . '/' . $ruta_activa['vista'];
$ruta_plantilla_raw = BASE_PATH . '/' . $ruta_activa['plantilla'];

$ruta_vista     = realpath($ruta_vista_raw);
$ruta_plantilla = realpath($ruta_plantilla_raw);

if (!$ruta_vista || !str_starts_with($ruta_vista, $views_dir)) {
    http_response_code(500);
    error_log("Axe Security: vista fuera de directorio permitido: " . $ruta_activa['vista']);
    exit("Error interno del servidor.");
}

if (!$ruta_plantilla || !str_starts_with($ruta_plantilla, $templates_dir)) {
    http_response_code(500);
    error_log("Axe Security: plantilla fuera de directorio permitido: " . $ruta_activa['plantilla']);
    exit("Error interno del servidor.");
}

if (isset($ruta_activa['controlador']) && !empty($ruta_activa['controlador'])) {
    $ruta_controlador_raw = BASE_PATH . '/' . $ruta_activa['controlador'];
    $ruta_controlador     = realpath($ruta_controlador_raw);

    // FIX C2: El controlador debe existir y estar dentro de /controllers
    if (!$ruta_controlador || !str_starts_with($ruta_controlador, $controllers_dir)) {
        http_response_code(500);
        error_log("Axe Security: controlador fuera de directorio permitido: " . $ruta_activa['controlador']);
        exit("Error interno del servidor.");
    }

    require $ruta_controlador;
    if (isset($datos_vista) && is_array($datos_vista)) {
        extract($datos_vista);
    }
}

ob_start();
require $ruta_vista;
$contenido_vista = ob_get_clean();

$css_vista = $ruta_activa['css'] ?? [];
$js_vista  = $ruta_activa['js']  ?? [];

// ── Auto-enlace CSS del layout ───────────────────────────────────────────────
// Genera dinámicamente la ruta del CSS estructural del layout a partir del
// nombre de archivo de la plantilla, sin leer disco ni usar regex.
// Ej: 'templates/public/layout_public.php' → '/assets/css/public/layout_public.css'
$nombre_layout = pathinfo($ruta_activa['plantilla'], PATHINFO_FILENAME);
$css_layout    = null;

if (str_contains($nombre_layout, '_public')) {
    $css_layout = '/assets/css/public/' . $nombre_layout . '.css';
} elseif (str_contains($nombre_layout, '_private')) {
    $css_layout = '/assets/css/private/' . $nombre_layout . '.css';
}

// ── Menú público dinámico ────────────────────────────────────────────────────
// Si la plantilla actual es el layout público, se inyectan las opciones del menú.
// La lógica de filtrado vive en LayoutPublicController; aquí solo se invoca.
if (str_contains($ruta_activa['plantilla'], 'layout_public.php')) {
    require_once BASE_PATH . '/controllers/LayoutPublicController.php';
    $opciones_publicas = LayoutPublicController::obtenerMenu();
}

require $ruta_plantilla;