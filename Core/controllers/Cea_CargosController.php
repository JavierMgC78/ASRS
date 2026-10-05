<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use PDO;
use Throwable;

class Cea_CargosController
{
    /**
     * Roles autorizados para acceder y registrar cargos de servicios CARE.
     */
    public const ALLOWED_ROLES = ['super_admin', 'caja', 'coordinacion_nivel', 'inscripcion'];

    /**
     * Catálogo institucional de conceptos CARE admitidos.
     */
    public const SERVICIOS_CARE = [
        'Transporte' => [
            'id'             => 'Transporte',
            'nombre'         => 'Transporte Escolar',
            'monto_sugerido' => 1500.00,
            'icono'          => 'bus',
            'descripcion'    => 'Servicio de traslado institucional diario con asignación de ruta matutina / vespertina.'
        ],
        'Lunch' => [
            'id'             => 'Lunch',
            'nombre'         => 'Lunch / Comedor',
            'monto_sugerido' => 1200.00,
            'icono'          => 'utensils',
            'descripcion'    => 'Servicio de comedor escolar mensual con menú nutricional balanceado y supervisado.'
        ],
        'Estancia' => [
            'id'             => 'Estancia',
            'nombre'         => 'Estancia Infantil',
            'monto_sugerido' => 800.00,
            'icono'          => 'clock',
            'descripcion'    => 'Horario extendido vespertino con cuidado infantil y acompañamiento en tareas.'
        ]
    ];

    /**
     * Manejador principal para la vista y peticiones AJAX de Registro de Cargos.
     *
     * @return array Datos para renderizar en la vista.
     */
    public static function handleRegister(): array
    {
        // 1. Control de Acceso RBAC
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (class_exists('Core\AuthMiddleware') && method_exists('Core\AuthMiddleware', 'requireAnyRole')) {
            AuthMiddleware::requireAnyRole(self::ALLOWED_ROLES);
        }

        $db = Database::getInstance();
        $errorMsg   = null;
        $successMsg = null;
        $cargoCreado = null;

        // 2. Detección de peticiones AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_GET['ajax']) || isset($_POST['ajax'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        $action = $_REQUEST['action'] ?? null;

        // A) AJAX: Búsqueda de alumnos en tiempo real
        if ($action === 'search_alumnos') {
            self::handleSearchAlumnos($db);
            exit;
        }

        // B) AJAX: Obtener ficha detallada de un alumno por ID
        if ($action === 'get_alumno') {
            self::handleGetAlumno($db);
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // C) AJAX: Guardar cargo
        if ($action === 'registrar_cargo' && $isAjax && $requestMethod === 'POST') {
            self::handleRegistrarCargoAjax($db);
            exit;
        }

        // D) Fallback tradicional POST (envío directo por formulario)
        if ($requestMethod === 'POST' && isset($_POST['btn_submit_cargo'])) {
            $resultado = self::insertarCargo($db, $_POST);
            if ($resultado['success']) {
                $successMsg = $resultado['message'];
                $cargoCreado = $resultado['data'] ?? null;
            } else {
                $errorMsg = $resultado['message'];
            }
        }

        // 3. Obtener los alumnos activos recientes para el selector nativo (fallback)
        $alumnosRecientes = self::obtenerAlumnosRecientes($db);

        // 4. Obtener los últimos 10 cargos registrados para feedback visual
        $ultimosCargos = self::obtenerUltimosCargos($db);

        return [
            'serviciosCare'    => self::SERVICIOS_CARE,
            'alumnosRecientes' => $alumnosRecientes,
            'ultimosCargos'    => $ultimosCargos,
            'errorMsg'         => $errorMsg,
            'successMsg'       => $successMsg,
            'cargoCreado'      => $cargoCreado,
            'fechaHoy'         => date('Y-m-d')
        ];
    }

    /**
     * Consulta los cargos CARE en estatus 'pendiente' de un alumno específico.
     * Solo lectura: no altera el estatus de ningún cargo.
     *
     * @param PDO $db
     * @param int $alumnoId
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerCargosPendientesPorAlumno(PDO $db, int $alumnoId): array
    {
        if ($alumnoId <= 0) {
            return [];
        }

        $stmt = $db->prepare(
            "SELECT id, alumno_id, concepto, monto, fecha_solicitud, fecha_servicio, estatus
             FROM cargos_alumnos_cea
             WHERE alumno_id = :alumno_id AND estatus = 'pendiente'
             ORDER BY fecha_servicio ASC, id ASC"
        );
        $stmt->bindValue(':alumno_id', $alumnoId, PDO::PARAM_INT);
        $stmt->execute();

        $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cargos as &$c) {
            $c['id']        = (int)$c['id'];
            $c['alumno_id'] = (int)$c['alumno_id'];
            $c['monto']     = (float)$c['monto'];
        }
        unset($c);

        return $cargos;
    }

    /**
     * Actualiza el estatus de los cargos CARE especificados a 'pagado'
     * y asocia el ID del recibo de pago correspondiente si se proporciona.
     *
     * @param PDO          $db        Instancia PDO de base de datos
     * @param array|string $cargosIds ID único, array de IDs o string de IDs separados por coma
     * @param int          $alumnoId  ID del alumno titular de los cargos (validación estricta)
     * @param int|null     $pagoId    ID del registro generado en pagos_cea (opcional para recibo_id)
     * @return int                    Cantidad de registros efectivamente actualizados
     */
    public static function marcarCargosComoPagados(PDO $db, $cargosIds, int $alumnoId, ?int $pagoId = null): int
    {
        if (empty($cargosIds) || $alumnoId <= 0) {
            return 0;
        }

        // Normalizar entrada a array de identificadores enteros válidos
        if (is_string($cargosIds)) {
            $cargosIds = explode(',', $cargosIds);
        }

        if (!is_array($cargosIds)) {
            $cargosIds = [$cargosIds];
        }

        $cleanIds = [];
        foreach ($cargosIds as $id) {
            $val = filter_var(trim((string)$id), FILTER_VALIDATE_INT);
            if ($val && $val > 0) {
                $cleanIds[] = (int)$val;
            }
        }
        $cleanIds = array_values(array_unique($cleanIds));

        if (empty($cleanIds)) {
            return 0;
        }

        // Construir placeholders dinámicos para la cláusula IN (?, ?, ...)
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

        $sql = "UPDATE cargos_alumnos_cea 
                SET estatus = 'pagado', 
                    recibo_id = ? 
                WHERE id IN ({$placeholders}) 
                  AND alumno_id = ? 
                  AND estatus = 'pendiente'";

        $stmt = $db->prepare($sql);

        // Los parámetros son: [recibo_id, id_1, id_2, ..., alumno_id]
        $params = array_merge([$pagoId], $cleanIds, [$alumnoId]);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Endpoint AJAX consumido por la Caja institucional:
     * - action=get_cargos_pendientes: Retorna el listado de cargos CARE pendientes del alumno.
     * - action=marcar_cargos_pagados: Actualiza manualmente cargos a estatus 'pagado' si se requiere.
     * Responde JSON y termina la ejecución únicamente si la petición corresponde a estas acciones.
     */
    public static function handleCajaAjax(): void
    {
        $action = $_REQUEST['action'] ?? null;
        if ($action !== 'get_cargos_pendientes' && $action !== 'marcar_cargos_pagados') {
            return;
        }

        // Solo roles autorizados de Caja y Administración
        AuthMiddleware::requireAnyRole(['caja', 'super_admin']);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $db = Database::getInstance();

        // 1. Consulta de cargos pendientes
        if ($action === 'get_cargos_pendientes') {
            $alumnoId = filter_var($_GET['alumno_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$alumnoId || $alumnoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de alumno no válido.', 'data' => []]);
                exit;
            }

            try {
                $cargos = self::obtenerCargosPendientesPorAlumno($db, $alumnoId);
                $total = array_sum(array_column($cargos, 'monto'));

                echo json_encode([
                    'success' => true,
                    'count'   => count($cargos),
                    'total'   => round($total, 2),
                    'data'    => $cargos
                ]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al consultar cargos: ' . $e->getMessage(), 'data' => []]);
            }
            exit;
        }

        // 2. Actualización a estatus pagado (soporte AJAX directo si se invoca)
        if ($action === 'marcar_cargos_pagados') {
            $alumnoId = filter_var($_REQUEST['alumno_id'] ?? 0, FILTER_VALIDATE_INT);
            $cargosIds = $_REQUEST['cargos_ids'] ?? [];
            $pagoId   = filter_var($_REQUEST['pago_id'] ?? 0, FILTER_VALIDATE_INT) ?: null;

            if (!$alumnoId || $alumnoId <= 0 || empty($cargosIds)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros insuficientes para marcar cargos como pagados.']);
                exit;
            }

            try {
                $afectados = self::marcarCargosComoPagados($db, $cargosIds, $alumnoId, $pagoId);
                echo json_encode([
                    'success'    => true,
                    'message'    => "Se actualizaron {$afectados} cargos a estatus pagado.",
                    'afectados'  => $afectados
                ]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar cargos: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    /**
     * Búsqueda en vivo de alumnos por nombre, apellidos o CURP.
     */
    private static function handleSearchAlumnos(PDO $db): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $term = trim($_GET['term'] ?? '');
        if (mb_strlen($term) < 2) {
            echo json_encode(['success' => true, 'total' => 0, 'data' => []]);
            return;
        }

        try {
            $wildcard = '%' . $term . '%';
            $sql = "SELECT 
                        a.id,
                        a.curp,
                        a.nombre,
                        a.primer_apellido,
                        a.segundo_apellido,
                        CONCAT(a.primer_apellido, ' ', COALESCE(a.segundo_apellido, ''), ' ', a.nombre) AS nombre_completo,
                        a.nivel_educativo,
                        a.grado,
                        a.grupo,
                        a.estado_alumno
                    FROM alumnos_cea a
                    WHERE a.primer_apellido LIKE :term1
                       OR a.segundo_apellido LIKE :term2
                       OR a.nombre LIKE :term3
                       OR a.curp LIKE :term4
                    ORDER BY 
                        (a.primer_apellido LIKE :term_start) DESC,
                        a.primer_apellido ASC,
                        a.nombre ASC
                    LIMIT 20";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':term1', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term2', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term3', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term4', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term_start', $term . '%', PDO::PARAM_STR);
            $stmt->execute();

            $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'total'   => count($alumnos),
                'data'    => $alumnos
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al consultar alumnos: ' . $e->getMessage(),
                'data'    => []
            ]);
        }
    }

    /**
     * Obtiene los datos de un alumno por ID.
     */
    private static function handleGetAlumno(PDO $db): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Identificador de alumno no válido.']);
            return;
        }

        try {
            $stmt = $db->prepare("SELECT id, curp, nombre, primer_apellido, segundo_apellido, nivel_educativo, grado, grupo, estado_alumno FROM alumnos_cea WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$alumno) {
                echo json_encode(['success' => false, 'message' => 'Alumno no encontrado.']);
                return;
            }

            $alumno['nombre_completo'] = trim("{$alumno['primer_apellido']} {$alumno['segundo_apellido']} {$alumno['nombre']}");

            echo json_encode(['success' => true, 'data' => $alumno]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesador AJAX para registrar un cargo en cargos_alumnos_cea.
     */
    private static function handleRegistrarCargoAjax(PDO $db): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // Obtener datos de POST o de JSON
        $input = $_POST;
        if (empty($input)) {
            $rawJson = file_get_contents('php://input');
            if (!empty($rawJson)) {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    $input = $decoded;
                }
            }
        }

        $resultado = self::insertarCargo($db, $input);

        if (!$resultado['success']) {
            http_response_code(422);
        }

        echo json_encode($resultado);
    }

    /**
     * Inserta un registro validado en la tabla cargos_alumnos_cea.
     *
     * @param PDO $db
     * @param array $data
     * @return array
     */
    public static function insertarCargo(PDO $db, array $data): array
    {
        // 1. Sanitizar y validar alumno_id
        $alumnoId = filter_var($data['alumno_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$alumnoId || $alumnoId <= 0) {
            return [
                'success' => false,
                'message' => 'Debes seleccionar un alumno válido de la matrícula escolar.'
            ];
        }

        // Verificar existencia del alumno
        $stmtAlumno = $db->prepare("SELECT id, nombre, primer_apellido, segundo_apellido, curp, nivel_educativo, grado, grupo FROM alumnos_cea WHERE id = :id LIMIT 1");
        $stmtAlumno->execute([':id' => $alumnoId]);
        $alumno = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

        if (!$alumno) {
            return [
                'success' => false,
                'message' => 'El alumno seleccionado no existe en el sistema escolar.'
            ];
        }

        // 2. Sanitizar y validar concepto
        $concepto = trim((string)($data['concepto'] ?? ''));
        if ($concepto === '') {
            return [
                'success' => false,
                'message' => 'Debes especificar el concepto del servicio (Transporte, Lunch o Estancia).'
            ];
        }
        if (mb_strlen($concepto) > 100) {
            $concepto = mb_substr($concepto, 0, 100);
        }

        // 3. Sanitizar y validar monto
        $rawMonto = str_replace(['$', ',', ' '], '', (string)($data['monto'] ?? ''));
        $monto = filter_var($rawMonto, FILTER_VALIDATE_FLOAT);
        if ($monto === false || $monto <= 0) {
            return [
                'success' => false,
                'message' => 'El monto del cargo debe ser una cantidad numérica mayor a $0.00 MXN.'
            ];
        }
        $monto = round($monto, 2);

        // 4. Validar fecha_solicitud
        $fechaSolicitud = trim((string)($data['fecha_solicitud'] ?? ''));
        if (!self::validarFechaYmd($fechaSolicitud)) {
            $fechaSolicitud = date('Y-m-d');
        }

        // 5. Validar fecha_servicio
        $fechaServicio = trim((string)($data['fecha_servicio'] ?? ''));
        if (!self::validarFechaYmd($fechaServicio)) {
            $fechaServicio = $fechaSolicitud;
        }

        try {
            // Inserción en cargos_alumnos_cea
            $sql = "INSERT INTO cargos_alumnos_cea 
                        (alumno_id, concepto, monto, fecha_solicitud, fecha_servicio, estatus, created_at)
                    VALUES 
                        (:alumno_id, :concepto, :monto, :fecha_solicitud, :fecha_servicio, 'pendiente', NOW())";

            $stmtInsert = $db->prepare($sql);
            $stmtInsert->bindValue(':alumno_id', $alumnoId, PDO::PARAM_INT);
            $stmtInsert->bindValue(':concepto', $concepto, PDO::PARAM_STR);
            $stmtInsert->bindValue(':monto', $monto);
            $stmtInsert->bindValue(':fecha_solicitud', $fechaSolicitud, PDO::PARAM_STR);
            $stmtInsert->bindValue(':fecha_servicio', $fechaServicio, PDO::PARAM_STR);
            $stmtInsert->execute();

            $cargoId = (int)$db->lastInsertId();
            $nombreAlumnoCompleto = trim("{$alumno['primer_apellido']} {$alumno['segundo_apellido']} {$alumno['nombre']}");

            return [
                'success'  => true,
                'message'  => sprintf(
                    'Cargo #%d registrado exitosamente por $%s para el alumno %s (%s).',
                    $cargoId,
                    number_format($monto, 2),
                    $nombreAlumnoCompleto,
                    $concepto
                ),
                'cargo_id' => $cargoId,
                'data'     => [
                    'id'               => $cargoId,
                    'alumno_id'        => $alumnoId,
                    'alumno_nombre'    => $nombreAlumnoCompleto,
                    'curp'             => $alumno['curp'],
                    'grado_grupo'      => trim("{$alumno['grado']} {$alumno['grupo']}"),
                    'nivel_educativo'  => $alumno['nivel_educativo'],
                    'concepto'         => $concepto,
                    'monto'            => $monto,
                    'monto_formato'    => '$' . number_format($monto, 2),
                    'fecha_solicitud'  => $fechaSolicitud,
                    'fecha_servicio'   => $fechaServicio,
                    'estatus'          => 'pendiente',
                    'created_at'       => date('Y-m-d H:i:s')
                ]
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar el cargo en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida que una cadena tenga el formato YYYY-MM-DD y sea fecha real.
     */
    private static function validarFechaYmd(string $fecha): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        [$y, $m, $d] = explode('-', $fecha);
        return checkdate((int)$m, (int)$d, (int)$y);
    }

    /**
     * Consulta los alumnos activos más recientes para el select de contingencia.
     */
    private static function obtenerAlumnosRecientes(PDO $db, int $limit = 50): array
    {
        try {
            $stmt = $db->query(
                "SELECT id, curp, nombre, primer_apellido, segundo_apellido, nivel_educativo, grado, grupo 
                 FROM alumnos_cea 
                 ORDER BY primer_apellido ASC, nombre ASC 
                 LIMIT {$limit}"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene los últimos cargos registrados con datos del alumno para la tabla informativa.
     */
    private static function obtenerUltimosCargos(PDO $db, int $limit = 10): array
    {
        try {
            $stmt = $db->query(
                "SELECT 
                    c.id,
                    c.alumno_id,
                    c.concepto,
                    c.monto,
                    c.fecha_solicitud,
                    c.fecha_servicio,
                    c.estatus,
                    c.created_at,
                    CONCAT(a.primer_apellido, ' ', COALESCE(a.segundo_apellido, ''), ' ', a.nombre) AS alumno_nombre,
                    a.curp,
                    a.nivel_educativo,
                    a.grado,
                    a.grupo
                 FROM cargos_alumnos_cea c
                 INNER JOIN alumnos_cea a ON c.alumno_id = a.id
                 ORDER BY c.id DESC
                 LIMIT {$limit}"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
