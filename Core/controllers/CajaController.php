<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use PDO;
use Throwable;

class CajaController
{
    /**
     * Roles autorizados para acceder y operar el módulo de Caja.
     * Restringido exclusivamente a 'caja' y 'super_admin'.
     */
    public const ALLOWED_ROLES = ['caja', 'super_admin'];

    /**
     * Manejador central para la vista y solicitudes AJAX del módulo de Captura de Pagos.
     *
     * @return array Datos para renderizar en la vista.
     */
    public static function handleCapture(): array
    {
        // 1. Control de Acceso RBAC estricto
        AuthMiddleware::requireAnyRole(self::ALLOWED_ROLES);

        $db = Database::getInstance();
        $errorMsg = null;
        $successMsg = null;
        $pagoRegistrado = null;

        // 2. Procesamiento de peticiones AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_GET['ajax']) || isset($_POST['ajax'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        $action = $_REQUEST['action'] ?? null;

        // A) AJAX: Búsqueda en tiempo real de alumnos por apellido paterno
        if ($action === 'search_alumnos') {
            self::handleSearchAlumnos($db);
            exit;
        }

        // B) AJAX: Obtener ficha completa del alumno (incluyendo tutores y facturación limpia)
        if ($action === 'get_alumno_info') {
            self::handleGetAlumnoInfo($db);
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // C) AJAX: Guardar / Procesar la transacción de pago
        if ($action === 'guardar_pago' || ($isAjax && $requestMethod === 'POST')) {
            self::handleSavePaymentAjax($db);
            exit;
        }

        // D) Fallback tradicional POST (si se envía sin JS)
        if ($requestMethod === 'POST' && isset($_POST['submit_pago'])) {
            $resultado = self::savePayment($db, $_POST);
            if ($resultado['success']) {
                $successMsg = $resultado['message'];
                $pagoRegistrado = $resultado['data'];
            } else {
                $errorMsg = $resultado['message'];
            }
        }

        // 3. Obtener catálogo de conceptos activos desde conceptos_pago_cea
        $conceptos = self::getConceptosCatalogo($db);

        // 4. Formas de pago estándar
        $formasPago = [
            ['id' => 'Efectivo', 'nombre' => 'Efectivo', 'es_bancario' => false],
            ['id' => 'Transferencia BBVA', 'nombre' => 'Transferencia BBVA', 'es_bancario' => true],
            ['id' => 'Depósito Bancario', 'nombre' => 'Depósito Bancario', 'es_bancario' => true],
            ['id' => 'Tarjeta de Débito', 'nombre' => 'Tarjeta de Débito', 'es_bancario' => true],
            ['id' => 'Tarjeta de Crédito', 'nombre' => 'Tarjeta de Crédito', 'es_bancario' => true],
        ];

        return [
            'conceptos'       => $conceptos,
            'formasPago'      => $formasPago,
            'error'           => $errorMsg,
            'success'         => $successMsg,
            'pagoRegistrado'  => $pagoRegistrado,
            'cajero'          => $_SESSION['user'] ?? [],
            'fechaActual'     => date('Y-m-d'),
            'horaActual'      => date('H:i')
        ];
    }

    /**
     * Búsqueda en tiempo real de alumnos por apellido paterno (con comodines flexibles %valor%).
     * Devuelve siempre un arreglo JSON válido con ID, nombre completo, tutores y facturación limpia de nulos.
     */
    private static function handleSearchAlumnos(PDO $db): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $query = trim($_GET['query'] ?? '');

        if (mb_strlen($query) < 1) {
            echo json_encode(['success' => true, 'total' => 0, 'data' => []]);
            return;
        }

        try {
            // Coincidencia flexible con comodines %valor% para permitir fragmentos o letras iniciales
            $wildcard = '%' . $query . '%';
            $starts   = $query . '%';

            $sql = "SELECT 
                        a.id,
                        a.curp,
                        a.nombre,
                        a.primer_apellido,
                        a.segundo_apellido,
                        CONCAT(a.primer_apellido, ' ', COALESCE(a.segundo_apellido, ''), ' ', a.nombre) AS nombre_completo_ordenado,
                        CONCAT(a.nombre, ' ', a.primer_apellido, ' ', COALESCE(a.segundo_apellido, '')) AS nombre_completo,
                        a.nivel_educativo,
                        a.grado,
                        a.grupo,
                        a.estado_alumno,
                        a.telefono,
                        a.email,
                        ap.matricula
                    FROM alumnos_cea a
                    LEFT JOIN alumnos_plataforma_cea ap ON a.id = ap.alumno_id
                    WHERE a.primer_apellido LIKE :term_paterno
                       OR a.segundo_apellido LIKE :term_materno
                       OR a.nombre LIKE :term_nombre
                       OR a.curp LIKE :term_curp
                    ORDER BY 
                        (a.primer_apellido LIKE :term_start) DESC,
                        a.primer_apellido ASC,
                        a.segundo_apellido ASC,
                        a.nombre ASC
                    LIMIT 20";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':term_paterno', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term_materno', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term_nombre', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term_curp', $wildcard, PDO::PARAM_STR);
            $stmt->bindValue(':term_start', $starts, PDO::PARAM_STR);
            $stmt->execute();

            $alumnosRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $alumnos = [];

            foreach ($alumnosRaw as $row) {
                $alumnoId = (int)$row['id'];
                $row['tutores'] = self::getTutoresClean($db, $alumnoId);
                $row['facturacion'] = self::getFacturacionClean($db, $alumnoId);
                $alumnos[] = $row;
            }

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
     * Obtiene los datos detallados de un alumno, tutores y facturación con sanitización limpia (sin nulos).
     */
    private static function handleGetAlumnoInfo(PDO $db): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $alumnoId = filter_var($_GET['alumno_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$alumnoId || $alumnoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de alumno no válido']);
            return;
        }

        try {
            // 1. Datos del Alumno
            $stmtAlumno = $db->prepare("
                SELECT 
                    a.id,
                    a.curp,
                    a.nombre,
                    a.primer_apellido,
                    a.segundo_apellido,
                    CONCAT(a.primer_apellido, ' ', COALESCE(a.segundo_apellido, ''), ' ', a.nombre) AS nombre_ordenado,
                    CONCAT(a.nombre, ' ', a.primer_apellido, ' ', COALESCE(a.segundo_apellido, '')) AS nombre_completo,
                    a.nivel_educativo,
                    a.grado,
                    a.grupo,
                    a.estado_alumno,
                    a.telefono,
                    a.email,
                    ap.matricula
                FROM alumnos_cea a
                LEFT JOIN alumnos_plataforma_cea ap ON a.id = ap.alumno_id
                WHERE a.id = :id
                LIMIT 1
            ");
            $stmtAlumno->execute([':id' => $alumnoId]);
            $alumno = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

            if (!$alumno) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Alumno no encontrado']);
                return;
            }

            // 2. Tutores limpios sin nulos
            $tutoresLimpios = self::getTutoresClean($db, $alumnoId);

            // 3. Facturación limpia sin nulos
            $facturacionLimpia = self::getFacturacionClean($db, $alumnoId);

            // 4. Últimos pagos registrados del alumno
            $stmtHist = $db->prepare("
                SELECT folio, concepto, monto, forma_pago, referencia, fecha_pago, hora_pago, estado
                FROM pagos_cea
                WHERE alumno_id = :alumno_id
                ORDER BY id DESC
                LIMIT 5
            ");
            $stmtHist->execute([':alumno_id' => $alumnoId]);
            $historialPagos = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data'    => [
                    'alumno'      => $alumno,
                    'tutores'     => $tutoresLimpios,
                    'facturacion' => $facturacionLimpia,
                    'historial'   => $historialPagos
                ]
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener la ficha del alumno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene los tutores registrados de un alumno omitiendo campos nulos o no capturados.
     */
    public static function getTutoresClean(PDO $db, int $alumnoId): array
    {
        $stmtTutores = $db->prepare("
            SELECT 
                tipo_tutor,
                nombre_completo,
                parentesco,
                telefono_principal,
                telefono_secundario,
                email,
                ocupacion,
                lugar_trabajo,
                es_responsable_economico
            FROM tutores_cea
            WHERE alumno_id = :alumno_id
            ORDER BY tipo_tutor ASC
        ");
        $stmtTutores->execute([':alumno_id' => $alumnoId]);
        $tutoresRaw = $stmtTutores->fetchAll(PDO::FETCH_ASSOC);

        $tutoresLimpios = [];
        foreach ($tutoresRaw as $tutor) {
            $nombre = trim($tutor['nombre_completo'] ?? '');
            if ($nombre === '' || strtolower($nombre) === 'null' || strtolower($nombre) === 'undefined') {
                continue;
            }

            $tutoresLimpios[] = [
                'tipo_tutor'              => $tutor['tipo_tutor'] ?? 'tutor1',
                'nombre_completo'         => $nombre,
                'parentesco'              => self::cleanString($tutor['parentesco'] ?? '') ?? 'Tutor',
                'telefono_principal'      => self::cleanString($tutor['telefono_principal'] ?? ''),
                'telefono_secundario'     => self::cleanString($tutor['telefono_secundario'] ?? ''),
                'email'                   => self::cleanString($tutor['email'] ?? ''),
                'ocupacion'               => self::cleanString($tutor['ocupacion'] ?? ''),
                'lugar_trabajo'           => self::cleanString($tutor['lugar_trabajo'] ?? ''),
                'es_responsable_economico'=> (int)($tutor['es_responsable_economico'] ?? 0) === 1
            ];
        }

        return $tutoresLimpios;
    }

    /**
     * Obtiene los datos fiscales de un alumno limpios de nulos.
     */
    public static function getFacturacionClean(PDO $db, int $alumnoId): array
    {
        $stmtFact = $db->prepare("
            SELECT 
                requiere_factura,
                razon_social,
                rfc,
                regimen_fiscal,
                uso_cfdi,
                domicilio_fiscal,
                codigo_postal_fiscal,
                correo_facturacion
            FROM facturacion_cea
            WHERE alumno_id = :alumno_id
            LIMIT 1
        ");
        $stmtFact->execute([':alumno_id' => $alumnoId]);
        $factRaw = $stmtFact->fetch(PDO::FETCH_ASSOC);

        if (!$factRaw) {
            return [
                'requiere_factura'     => false,
                'razon_social'         => null,
                'rfc'                  => null,
                'regimen_fiscal'       => null,
                'uso_cfdi'             => null,
                'domicilio_fiscal'     => null,
                'codigo_postal_fiscal' => null,
                'correo_facturacion'   => null
            ];
        }

        $requiere = (int)($factRaw['requiere_factura'] ?? 0) === 1;

        return [
            'requiere_factura'     => $requiere,
            'razon_social'         => self::cleanString($factRaw['razon_social'] ?? ''),
            'rfc'                  => self::cleanString($factRaw['rfc'] ?? ''),
            'regimen_fiscal'       => self::cleanString($factRaw['regimen_fiscal'] ?? ''),
            'uso_cfdi'             => self::cleanString($factRaw['uso_cfdi'] ?? ''),
            'domicilio_fiscal'     => self::cleanString($factRaw['domicilio_fiscal'] ?? ''),
            'codigo_postal_fiscal' => self::cleanString($factRaw['codigo_postal_fiscal'] ?? ''),
            'correo_facturacion'   => self::cleanString($factRaw['correo_facturacion'] ?? '')
        ];
    }


    /**
     * Endpoint AJAX para procesar y persistir el pago en pagos_cea.
     */
    private static function handleSavePaymentAjax(PDO $db): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // Leer datos JSON o POST tradicional
        $input = $_POST;
        if (empty($input)) {
            $rawJson = file_get_contents('php://input');
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        $res = self::savePayment($db, $input);

        if (!$res['success']) {
            http_response_code(422);
        }

        echo json_encode($res);
    }

    /**
     * Lógica de validación e inserción PDO para el pago.
     *
     * @param PDO   $db    Instancia de PDO
     * @param array $data  Parámetros del formulario
     * @return array
     */
    public static function savePayment(PDO $db, array $data): array
    {
        $alumnoId   = filter_var($data['alumno_id'] ?? 0, FILTER_VALIDATE_INT);
        $conceptoId = filter_var($data['concepto_id'] ?? 0, FILTER_VALIDATE_INT);
        $conceptoTxt = trim($data['concepto'] ?? '');
        $monto      = filter_var($data['monto'] ?? 0, FILTER_VALIDATE_FLOAT);
        $formaPago  = trim($data['forma_pago'] ?? '');
        $referencia = trim($data['referencia'] ?? '');
        $fechaPago  = trim($data['fecha_pago'] ?? date('Y-m-d'));
        $horaPago   = trim($data['hora_pago'] ?? date('H:i'));
        $observaciones = trim($data['observaciones'] ?? '');
        $desglose      = trim($data['desglose_conceptos'] ?? '');
        if ($desglose !== '') {
            $observaciones = $observaciones !== '' ? ($observaciones . "\n\n" . $desglose) : $desglose;
        }
        $cajeroId   = (int)($_SESSION['user']['id'] ?? 0) ?: null;

        // Validaciones de negocio
        if (!$alumnoId || $alumnoId <= 0) {
            return ['success' => false, 'message' => 'Debe seleccionar un alumno válido para capturar el pago.'];
        }

        // Verificar que el alumno exista en alumnos_cea
        $stmtCheckAlumno = $db->prepare("SELECT id, curp, CONCAT(nombre, ' ', primer_apellido, ' ', COALESCE(segundo_apellido, '')) as nombre_completo FROM alumnos_cea WHERE id = :id");
        $stmtCheckAlumno->execute([':id' => $alumnoId]);
        $alumnoInfo = $stmtCheckAlumno->fetch(PDO::FETCH_ASSOC);

        if (!$alumnoInfo) {
            return ['success' => false, 'message' => 'El alumno seleccionado no se encuentra en el registro institucional.'];
        }

        // Validar concepto
        if (empty($conceptoTxt) && $conceptoId > 0) {
            $stmtConc = $db->prepare("SELECT nombre FROM conceptos_pago_cea WHERE id = :id");
            $stmtConc->execute([':id' => $conceptoId]);
            $conceptoTxt = $stmtConc->fetchColumn() ?: '';
        }

        if (empty($conceptoTxt)) {
            return ['success' => false, 'message' => 'El concepto de cobro es obligatorio.'];
        }

        // Validar monto
        if ($monto === false || $monto <= 0) {
            return ['success' => false, 'message' => 'El monto del pago debe ser mayor a $0.00.'];
        }

        // Validar forma de pago
        if (empty($formaPago)) {
            return ['success' => false, 'message' => 'Seleccione una forma de pago válida.'];
        }

        // Validar referencia bancaria: obligatoria si NO es Efectivo
        $esEfectivo = (strcasecmp($formaPago, 'Efectivo') === 0);
        if (!$esEfectivo && empty($referencia)) {
            return [
                'success' => false,
                'message' => "El campo Referencia / Folio Bancario es obligatorio para pagos realizados mediante {$formaPago}."
            ];
        }

        // Si es efectivo, la referencia se almacena como nula o vacía limpia
        if ($esEfectivo) {
            $referencia = null;
        }

        // Validar formato de fecha y hora
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
            $fechaPago = date('Y-m-d');
        }
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horaPago)) {
            $horaPago = date('H:i:s');
        } elseif (strlen($horaPago) === 5) {
            $horaPago .= ':00';
        }

        try {
            $db->beginTransaction();

            // Generar folio único para el comprobante
            $prefijo = 'REC-' . date('Ym') . '-';
            $stmtUltimoFolio = $db->prepare("SELECT folio FROM pagos_cea WHERE folio LIKE :prefijo ORDER BY id DESC LIMIT 1");
            $stmtUltimoFolio->execute([':prefijo' => $prefijo . '%']);
            $ultimoFolio = $stmtUltimoFolio->fetchColumn();

            if ($ultimoFolio) {
                $num = (int)substr($ultimoFolio, strrpos($ultimoFolio, '-') + 1);
                $folioNuevo = $prefijo . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $folioNuevo = $prefijo . '0001';
            }

            // Inserción en pagos_cea con prepared statement PDO
            $sql = "INSERT INTO pagos_cea (
                        folio,
                        alumno_id,
                        cajero_id,
                        concepto_id,
                        concepto,
                        monto,
                        forma_pago,
                        referencia,
                        fecha_pago,
                        hora_pago,
                        observaciones,
                        estado
                    ) VALUES (
                        :folio,
                        :alumno_id,
                        :cajero_id,
                        :concepto_id,
                        :concepto,
                        :monto,
                        :forma_pago,
                        :referencia,
                        :fecha_pago,
                        :hora_pago,
                        :observaciones,
                        'completado'
                    )";

            $stmtInsert = $db->prepare($sql);
            $stmtInsert->bindValue(':folio', $folioNuevo, PDO::PARAM_STR);
            $stmtInsert->bindValue(':alumno_id', $alumnoId, PDO::PARAM_INT);
            $stmtInsert->bindValue(':cajero_id', $cajeroId, $cajeroId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtInsert->bindValue(':concepto_id', $conceptoId > 0 ? $conceptoId : null, $conceptoId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtInsert->bindValue(':concepto', $conceptoTxt, PDO::PARAM_STR);
            $stmtInsert->bindValue(':monto', $monto);
            $stmtInsert->bindValue(':forma_pago', $formaPago, PDO::PARAM_STR);
            $stmtInsert->bindValue(':referencia', $referencia, $referencia !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtInsert->bindValue(':fecha_pago', $fechaPago, PDO::PARAM_STR);
            $stmtInsert->bindValue(':hora_pago', $horaPago, PDO::PARAM_STR);
            $stmtInsert->bindValue(':observaciones', $observaciones !== '' ? $observaciones : null, $observaciones !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $stmtInsert->execute();
            $pagoId = (int)$db->lastInsertId();

            // [Módulo CARE] Si la transacción incluye adeudos CARE, actualizar estatus a 'pagado'
            $cargosCareIds = $data['cea_cargos_ids'] ?? null;
            $cargosActualizados = 0;
            if (!empty($cargosCareIds)) {
                if (!class_exists('controllers\Cea_CargosController')) {
                    if (file_exists(__DIR__ . '/../../controllers/Cea_CargosController.php')) {
                        require_once __DIR__ . '/../../controllers/Cea_CargosController.php';
                    } elseif (file_exists(__DIR__ . '/Cea_CargosController.php')) {
                        require_once __DIR__ . '/Cea_CargosController.php';
                    }
                }

                if (class_exists('controllers\Cea_CargosController') && method_exists('controllers\Cea_CargosController', 'marcarCargosComoPagados')) {
                    $cargosActualizados = \controllers\Cea_CargosController::marcarCargosComoPagados($db, $cargosCareIds, $alumnoId, $pagoId);
                } elseif (class_exists('Core\controllers\Cea_CargosController') && method_exists('Core\controllers\Cea_CargosController', 'marcarCargosComoPagados')) {
                    $cargosActualizados = \Core\controllers\Cea_CargosController::marcarCargosComoPagados($db, $cargosCareIds, $alumnoId, $pagoId);
                }
            }

            $db->commit();

            return [
                'success' => true,
                'message' => "Pago registrado exitosamente con folio {$folioNuevo}." . ($cargosActualizados > 0 ? " (Se liquidaron {$cargosActualizados} cargos CARE asociados)" : ""),
                'data'    => [
                    'pago_id'              => $pagoId,
                    'folio'                => $folioNuevo,
                    'alumno_id'            => $alumnoId,
                    'alumno_nombre'        => $alumnoInfo['nombre_completo'],
                    'alumno_curp'          => $alumnoInfo['curp'],
                    'concepto'             => $conceptoTxt,
                    'cargos_care_pagados'  => $cargosActualizados,
                    'monto'           => number_format($monto, 2, '.', ''),
                    'monto_formateado'=> '$ ' . number_format($monto, 2, '.', ','),
                    'forma_pago'      => $formaPago,
                    'referencia'      => $referencia ?: 'N/A (Pago en Efectivo)',
                    'fecha_pago'      => $fechaPago,
                    'hora_pago'       => substr($horaPago, 0, 5),
                    'cajero_nombre'   => $_SESSION['user']['name'] ?? 'Cajero CEA',
                    'created_at'      => date('Y-m-d H:i:s')
                ]
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Error al guardar la transacción en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retorna el catálogo de conceptos activos desde conceptos_pago_cea.
     */
    public static function getConceptosCatalogo(PDO $db): array
    {
        try {
            $stmt = $db->query("
                SELECT id, nombre, descripcion, monto_sugerido
                FROM conceptos_pago_cea
                WHERE activo = 1
                ORDER BY id ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Sanitiza cadenas eliminando 'null', 'undefined' y vacíos.
     */
    private static function cleanString(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }
        $trimmed = trim($str);
        $lower = strtolower($trimmed);
        if ($trimmed === '' || $lower === 'null' || $lower === 'undefined') {
            return null;
        }
        return $trimmed;
    }
}
