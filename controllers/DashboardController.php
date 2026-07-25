<?php

declare(strict_types=1);

/**
 * controllers/DashboardController.php
 *
 * CONTROLADOR DEL ÁREA PROTEGIDA — AXE FRAMEWORK
 * ─────────────────────────────────────────────────────────────────────────────
 * Responsabilidades:
 *   1. Leer la variable global $usuario_autenticado_id inyectada por el
 *      Middleware de autenticación (a través del enrutador central).
 *   2. Consultar la tabla `usuarios` para obtener el email del usuario actual.
 *   3. Preparar $datos_vista con los datos necesarios para la vista.
 *   4. [GESTOR DE RUTAS — FASE 1] Escuchar peticiones POST para crear nuevas
 *      rutas en la BD y recompilar automáticamente config/rutas_cache.php.
 *
 * Precondición: este controlador sólo es alcanzable si el Middleware validó
 * correctamente el Split Token (cookie axe_auth), por lo que
 * $usuario_autenticado_id siempre debe tener un valor entero válido aquí.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── 1. Recuperar el ID de usuario inyectado por el Middleware ─────────────────
// La variable es declarada en el ámbito global por el enrutador central.
/** @var int $usuario_autenticado_id */
global $usuario_autenticado_id;

// ── 2. Dependencias ───────────────────────────────────────────────────────────
/** @var PDO $pdo */
$pdo = require BASE_PATH . '/config/database.php';

// ── 3. Consultar el email del usuario autenticado ─────────────────────────────
try {
    $stmt = $pdo->prepare(
        'SELECT email FROM usuarios WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => (int) $usuario_autenticado_id]);
    $usuario = $stmt->fetch();
} catch (PDOException $e) {
    // Si la consulta falla mostramos la vista con un valor de respaldo seguro.
    $usuario = ['email' => ''];
}

// ── 4. Escanear directorio de plantillas desde el sistema de archivos ─────────
// Se usa glob() sobre templates/*.php para obtener los archivos físicos.
// basename() extrae solo el nombre del archivo (ej. "default.php").
// El arreglo resultante se inyecta en la vista (para el <select>) y
// se reutiliza en la validación POST, evitando depender de config/plantillas.php.
$archivos_plantillas = glob(BASE_PATH . '/templates/*.php');
$plantillas_disponibles = [];
if (is_array($archivos_plantillas)) {
    foreach ($archivos_plantillas as $ruta_fisica) {
        $plantillas_disponibles[] = basename($ruta_fisica);
    }
}

// ── 5. Preparar los datos base para la vista ──────────────────────────────────
// El front controller extrae este arreglo con extract($datos_vista) antes
// de incluir la vista, por lo que cada clave se convierte en una variable local.
$datos_vista = [
    'email_usuario'          => $usuario['email'] ?? '',
    'plantillas_disponibles' => $plantillas_disponibles,
];

// ── 5. GESTORES — Escuchar peticiones POST ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── FIX A2: Validar token CSRF antes de procesar cualquier acción ──────────
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $csrf_recibido = (string) filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_recibido)) {
        http_response_code(403);
        exit('Token CSRF inválido. Recarga la página e inténtalo de nuevo.');
    }

    // Leer la acción que identifica qué formulario fue enviado.
    $accion = trim((string) filter_input(INPUT_POST, 'accion', FILTER_DEFAULT));

    // ══════════════════════════════════════════════════════════════════════════
    // ── HANDLER A: Gestor de Rutas ────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    if ($accion === 'crear_ruta') {

        // ── 5a. Captura y Sanitización de campos ──────────────────────────────

        // URI de la ruta (ej. /cobranza). Se elimina espacio y se fuerza string.
        $uri = trim((string) filter_input(INPUT_POST, 'uri', FILTER_DEFAULT));

        // Ruta de la vista relativa al proyecto (ej. views/cobranza.php).
        $vista = trim((string) filter_input(INPUT_POST, 'vista', FILTER_DEFAULT));

        // Plantilla maestra (layout) seleccionada desde el <select> del formulario.
        // FILTER_DEFAULT devuelve string limpio; trim() elimina espacios residuales.
        $plantilla = trim((string) filter_input(INPUT_POST, 'plantilla', FILTER_DEFAULT));

        // Ruta del controlador (puede venir vacío; se trata como NULL en BD).
        $controlador_raw = trim((string) filter_input(INPUT_POST, 'controlador', FILTER_DEFAULT));
        $controlador = ($controlador_raw !== '') ? $controlador_raw : null;

        // requiere_login: se fuerza a 1 o 0 independientemente del valor enviado.
        $requiere_login = filter_input(INPUT_POST, 'requiere_login', FILTER_VALIDATE_INT) === 1 ? 1 : 0;

        // nivel_minimo: se fuerza a entero (0 por defecto si no es numérico).
        $nivel_minimo = (int) filter_input(INPUT_POST, 'nivel_minimo', FILTER_VALIDATE_INT);
        if ($nivel_minimo < 0) {
            $nivel_minimo = 0;
        }

        // ── FIX C2: Validación estricta de las rutas ingresadas ───────────────
        // Impide path traversal (../../) e inyección de rutas arbitrarias.

        // URI: solo letras, números, guiones y barras. Debe empezar por /
        if (!preg_match('#^/[a-zA-Z0-9/_-]*$#', $uri) || strlen($uri) < 2 || strlen($uri) > 100) {
            $datos_vista['mensaje_gestor'] = '❌ URI inválida. Usa solo letras, números, guiones y barras (ej. /mi-ruta).';
            goto fin_gestor;
        }

        // Vista: debe estar en la carpeta views/ y tener extensión .php
        if (!preg_match('#^views/[a-zA-Z0-9_-]+\.php$#', $vista)) {
            $datos_vista['mensaje_gestor'] = '❌ Ruta de vista inválida. Formato esperado: views/nombre.php';
            goto fin_gestor;
        }

        // Plantilla: validación por lista blanca contra el sistema de archivos.
        // Se reconstruye el valor esperado ("templates/archivo.php") y se verifica
        // que exista en $plantillas_disponibles (escaneado por glob() al inicio).
        // Esto previene path traversal e inyección de rutas arbitrarias sin depender
        // del catálogo config/plantillas.php.
        $archivo_plantilla = basename($plantilla); // Extrae solo el nombre de archivo
        $valor_esperado    = 'templates/' . $archivo_plantilla;
        if (
            $plantilla !== $valor_esperado ||
            !in_array($archivo_plantilla, $plantillas_disponibles, true)
        ) {
            $datos_vista['mensaje_gestor'] = '❌ Plantilla no válida. Selecciona una opción del listado.';
            goto fin_gestor;
        }

        // Controlador (opcional): debe estar en controllers/ y seguir el patrón NombreController.php
        if ($controlador !== null && !preg_match('#^controllers/[a-zA-Z0-9]+\.php$#', $controlador)) {
            $datos_vista['mensaje_gestor'] = '❌ Ruta de controlador inválida. Formato esperado: controllers/NombreController.php';
            goto fin_gestor;
        }

        // ── 5b. Automatización de Assets ──────────────────────────────────────
        // Extrae el nombre base de la vista sin extensión (ej. "cobranza").
        $base = pathinfo($vista, PATHINFO_FILENAME);

        // Genera cadenas JSON estrictas para css y js.
        $css_json = '["/assets/css/' . $base . '.css"]';
        $js_json  = '["/assets/js/'  . $base . '.js"]';

        // ── 5c. Inserción en Base de Datos ────────────────────────────────────
        try {
            $insert = $pdo->prepare(
                'INSERT INTO rutas
                    (uri, vista, plantilla, controlador, requiere_login, nivel_minimo, css, js)
                 VALUES
                    (:uri, :vista, :plantilla, :controlador, :requiere_login, :nivel_minimo, :css, :js)'
            );

            $insert->execute([
                ':uri'            => $uri,
                ':vista'          => $vista,
                ':plantilla'      => $plantilla,
                ':controlador'    => $controlador,
                ':requiere_login' => $requiere_login,
                ':nivel_minimo'   => $nivel_minimo,
                ':css'            => $css_json,
                ':js'             => $js_json,
            ]);

            // ── 5d. Recompilación de Caché ────────────────────────────────────
            // Recuperar todas las rutas de la tabla para regenerar el archivo cache.
            $select_rutas = $pdo->query(
                'SELECT uri, nombre_opcion, vista, plantilla, controlador, requiere_login, nivel_minimo, css, js
                 FROM rutas
                 ORDER BY id ASC'
            );

            $rutas_array = [];

            foreach ($select_rutas as $fila) {
                // Decodificar JSON de css; si falla o está vacío se usa arreglo vacío.
                $css_decoded = json_decode((string) $fila['css'], true);
                $css_final   = is_array($css_decoded) ? $css_decoded : [];

                // Decodificar JSON de js; si falla o está vacío se usa arreglo vacío.
                $js_decoded = json_decode((string) $fila['js'], true);
                $js_final   = is_array($js_decoded) ? $js_decoded : [];

                $rutas_array[$fila['uri']] = [
                    'uri'            => trim($fila['uri']),
                    'nombre_opcion'  => $fila['nombre_opcion'],
                    'vista'          => trim($fila['vista']),
                    'plantilla'      => trim($fila['plantilla']),
                    'controlador'    => $fila['controlador'] ? trim($fila['controlador']) : null,
                    'requiere_login' => (bool) $fila['requiere_login'],
                    'nivel_minimo'   => (int) $fila['nivel_minimo'],
                    'css'            => $css_final,
                    'js'             => $js_final,
                ];
            }

            // Generar contenido PHP del archivo de caché usando var_export().
            $fecha_compilacion = date('Y-m-d H:i:s');
            $export_php        = var_export($rutas_array, true);

            $cache_contenido = <<<PHP
<?php
// Archivo auto-generado por el Compilador de Axe Framework
// Fecha de última compilación: {$fecha_compilacion}

return {$export_php};
PHP;

            // Sobrescribir config/rutas_cache.php con el nuevo contenido.
            file_put_contents(BASE_PATH . '/config/rutas_cache.php', $cache_contenido);

            // ── 5e. Mensaje de éxito ──────────────────────────────────────────
            $mensaje_gestor = 'Ruta "' . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8') . '" creada correctamente y caché recompilada.';

        } catch (PDOException $e) {
            // FIX M2: Loguear error internamente, no exponer al usuario.
            error_log('DashboardController [crear_ruta]: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $mensaje_gestor = '❌ La URI ingresada ya existe. Elige una diferente.';
            } else {
                $mensaje_gestor = '❌ Error interno al guardar la ruta. Contacta al administrador.';
            }
        }

        // Añadir el mensaje al arreglo de datos de la vista.
        $datos_vista['mensaje_gestor'] = $mensaje_gestor ?? null;

        fin_gestor: // Etiqueta de salida rápida para validaciones fallidas

    // ══════════════════════════════════════════════════════════════════════════
    // ── HANDLER B: Gestor de Plantillas ──────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    } elseif ($accion === 'crear_plantilla') {

        // ── 6a. Captura y sanitización ────────────────────────────────────────

        // Nombre base de la plantilla (ej. "admin"). Sin ruta ni extensión.
        $nombre_plantilla = trim((string) filter_input(INPUT_POST, 'nombre_plantilla', FILTER_DEFAULT));

        // Descripción legible para mostrar en los selectores del sistema.
        $descripcion_plantilla = trim((string) filter_input(INPUT_POST, 'descripcion_plantilla', FILTER_DEFAULT));

        // ── 6b. Validación ────────────────────────────────────────────────────

        // Nombre: solo letras, números y guiones. Longitud 2-50.
        if (!preg_match('#^[a-zA-Z0-9-]+$#', $nombre_plantilla) || strlen($nombre_plantilla) < 2 || strlen($nombre_plantilla) > 50) {
            $datos_vista['mensaje_plantilla'] = '❌ Nombre inválido. Usa solo letras, números y guiones (2-50 caracteres).';
            goto fin_plantilla;
        }

        // Descripción: no puede estar vacía, máximo 200 caracteres.
        if (empty($descripcion_plantilla) || strlen($descripcion_plantilla) > 200) {
            $datos_vista['mensaje_plantilla'] = '❌ La descripción es obligatoria (máximo 200 caracteres).';
            goto fin_plantilla;
        }

        // Construir la clave y la ruta del archivo (el sistema la arma, no el usuario).
        $clave_plantilla  = 'templates/' . $nombre_plantilla . '.php';
        $ruta_fisica      = BASE_PATH . '/' . $clave_plantilla;

        // ── 6c. Verificar duplicado en el catálogo ────────────────────────────
        $catalogo_actual = require BASE_PATH . '/config/plantillas.php';
        if (array_key_exists($clave_plantilla, $catalogo_actual)) {
            $datos_vista['mensaje_plantilla'] = '❌ La plantilla "' . htmlspecialchars($clave_plantilla, ENT_QUOTES, 'UTF-8') . '" ya existe en el catálogo.';
            goto fin_plantilla;
        }

        // ── 6d. Crear el archivo físico (esqueleto mínimo) ───────────────────
        // El archivo generado contiene el mínimo necesario para que el sistema
        // de buffer de Axe funcione: imprime $contenido_vista e inyecta assets.
        $nombre_seguro = htmlspecialchars($nombre_plantilla, ENT_QUOTES, 'UTF-8');
        $fecha_creacion = date('Y-m-d H:i:s');

        $esqueleto = <<<TEMPLATE
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Axe Framework</title>
    <?php
    // Inyección de CSS por vista
    if (!empty(\$css_vista) && is_array(\$css_vista)) {
        foreach (\$css_vista as \$css) {
            echo "<link rel='stylesheet' href='{\$css}'>\n";
        }
    }
    ?>
</head>
<body>

    <?php echo \$contenido_vista ?? ''; ?>

    <?php
    // Inyección de JS por vista
    if (!empty(\$js_vista) && is_array(\$js_vista)) {
        foreach (\$js_vista as \$js) {
            echo "<script src='{\$js}'></script>\n";
        }
    }
    ?>
</body>
</html>
TEMPLATE;

        if (file_put_contents($ruta_fisica, $esqueleto) === false) {
            error_log('DashboardController [crear_plantilla]: No se pudo crear el archivo ' . $ruta_fisica);
            $datos_vista['mensaje_plantilla'] = '❌ Error al crear el archivo físico. Verifica los permisos del directorio templates/.';
            goto fin_plantilla;
        }

        // ── 6e. Actualizar config/plantillas.php de forma atómica ─────────────
        // Añadir la nueva entrada al catálogo existente y reescribir el archivo.
        $catalogo_actual[$clave_plantilla] = [
            'ruta'        => $clave_plantilla,
            'descripcion' => $descripcion_plantilla,
        ];

        $export_catalogo = var_export($catalogo_actual, true);
        $fecha_actualizacion = date('Y-m-d H:i:s');

        $nuevo_catalogo = <<<PHP
<?php

/**
 * config/plantillas.php
 *
 * REGISTRO DE PLANTILLAS MAESTRAS — AXE FRAMEWORK
 * Archivo auto-gestionado por el Gestor de Plantillas del Dashboard.
 * Última actualización: {$fecha_actualizacion}
 */

return {$export_catalogo};
PHP;

        // Escritura atómica: temp → rename
        $ruta_temp_catalogo  = BASE_PATH . '/config/temp_plantillas.php';
        $ruta_real_catalogo  = BASE_PATH . '/config/plantillas.php';

        if (file_put_contents($ruta_temp_catalogo, $nuevo_catalogo) === false) {
            // Si falla la escritura del catálogo, eliminar el archivo físico ya creado
            // para mantener consistencia (no tener archivo sin registro).
            @unlink($ruta_fisica);
            error_log('DashboardController [crear_plantilla]: No se pudo escribir el catálogo temporal.');
            $datos_vista['mensaje_plantilla'] = '❌ Error al actualizar el catálogo. Operación revertida.';
            goto fin_plantilla;
        }

        if (!rename($ruta_temp_catalogo, $ruta_real_catalogo)) {
            @unlink($ruta_fisica);
            @unlink($ruta_temp_catalogo);
            error_log('DashboardController [crear_plantilla]: No se pudo reemplazar el catálogo.');
            $datos_vista['mensaje_plantilla'] = '❌ Error al guardar el catálogo. Operación revertida.';
            goto fin_plantilla;
        }

        // ── 6f. Mensaje de éxito ──────────────────────────────────────────────
        $datos_vista['mensaje_plantilla'] = 'Plantilla "' . htmlspecialchars($clave_plantilla, ENT_QUOTES, 'UTF-8') . '" creada correctamente y registrada en el catálogo.';

        fin_plantilla: // Etiqueta de salida rápida para validaciones fallidas
    }
}

