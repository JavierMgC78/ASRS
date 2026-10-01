<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use PDO;
use Throwable;

class AlumnosController
{
    /**
     * Roles permitidos para gestionar las altas de alumnos.
     */
    private const ALLOWED_ROLES = ['inscripcion', 'super_admin'];

    /**
     * Roles autorizados para visualizar el listado general de alumnos.
     * Super Admin, Cajero, Inscripción y Docente.
     */
    public const ALLOWED_ROLES_LIST = ['super_admin', 'caja', 'inscripcion', 'docente'];

    /**
     * Manejador principal para la vista de Listado General de Alumnos ('/admin/alumnos/ver').
     * Implementa consulta con ordenamiento lógico por grado y grupo, y filtros.
     *
     * @return array
     */
    public static function handleList(): array
    {
        // 1. Control de acceso RBAC por roles autorizados
        AuthMiddleware::requireAnyRole(self::ALLOWED_ROLES_LIST);

        $db = Database::getInstance();
        $errorMsg   = null;
        $successMsg = null;

        // Rol activo en sesión
        $currentRole   = $_SESSION['user']['role_name'] ?? $_SESSION['user']['role'] ?? '';
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        // 2. Procesar solicitudes AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_GET['ajax']) || isset($_POST['ajax'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        $action = $_REQUEST['action'] ?? null;

        // Acción AJAX: Obtener ficha completa de un alumno para modal de detalle
        if ($action === 'get_detail') {
            $alumnoId = filter_var($_REQUEST['alumno_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($alumnoId && $alumnoId > 0) {
                $detail = self::getAlumnoFullDetail($alumnoId);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => $detail !== null,
                    'data'    => $detail,
                    'message' => $detail ? 'Detalle obtenido exitosamente' : 'Alumno no encontrado'
                ]);
                exit;
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Identificador de alumno no válido']);
            exit;
        }

        // 3. Captura y sanitización de filtros de búsqueda
        $search = trim($_GET['search'] ?? '');
        $nivel  = trim($_GET['nivel'] ?? '');
        $grado  = trim($_GET['grado'] ?? '');
        $grupo  = trim($_GET['grupo'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        // 4. Base para filtro de docente en fases posteriores:
        // En esta fase, los docentes visualizan el listado completo según el requerimiento,
        // dejando preparada la infraestructura para restringir al grupo asignado cuando se defina.
        $docenteGrupoAsignado = null;
        // if ($currentRole === 'docente') {
        //     $docenteGrupoAsignado = self::getDocenteAssignedGroup($currentUserId);
        // }

        // 5. Construcción dinámica de la consulta SQL relacional con tablas _cea
        $sql = "SELECT 
                    a.id,
                    a.curp,
                    a.nombre,
                    a.primer_apellido,
                    a.segundo_apellido,
                    CONCAT(a.nombre, ' ', a.primer_apellido, ' ', COALESCE(a.segundo_apellido, '')) AS nombre_completo,
                    a.fecha_nacimiento,
                    a.genero,
                    a.nivel_educativo,
                    a.grado,
                    a.grupo,
                    a.direccion,
                    a.colonia,
                    a.codigo_postal,
                    a.municipio,
                    a.estado,
                    a.telefono,
                    a.email,
                    a.tipo_sangre,
                    a.alergias_condiciones,
                    a.contacto_emergencia,
                    a.telefono_emergencia,
                    a.estado_alumno,
                    a.created_at,
                    p.matricula,
                    p.usuario_plataforma,
                    p.email_institucional,
                    p.acceso_activo,
                    t.nombre_completo AS tutor_nombre,
                    t.parentesco AS tutor_parentesco,
                    t.telefono_principal AS tutor_telefono,
                    t.email AS tutor_email
                FROM alumnos_cea a
                LEFT JOIN alumnos_plataforma_cea p ON a.id = p.alumno_id
                LEFT JOIN tutores_cea t ON a.id = t.alumno_id AND t.tipo_tutor = 'tutor1'
                WHERE 1=1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                a.nombre LIKE :s_nom 
                OR a.primer_apellido LIKE :s_ape1 
                OR a.segundo_apellido LIKE :s_ape2 
                OR a.curp LIKE :s_curp 
                OR p.matricula LIKE :s_mat
                OR CONCAT(a.nombre, ' ', a.primer_apellido, ' ', COALESCE(a.segundo_apellido, '')) LIKE :s_full
            )";
            $searchTerm = "%{$search}%";
            $params[':s_nom']  = $searchTerm;
            $params[':s_ape1'] = $searchTerm;
            $params[':s_ape2'] = $searchTerm;
            $params[':s_curp'] = $searchTerm;
            $params[':s_mat']  = $searchTerm;
            $params[':s_full'] = $searchTerm;
        }

        if ($nivel !== '') {
            $sql .= " AND a.nivel_educativo = :nivel";
            $params[':nivel'] = $nivel;
        }

        if ($grado !== '') {
            $sql .= " AND a.grado = :grado";
            $params[':grado'] = $grado;
        }

        if ($grupo !== '') {
            $sql .= " AND a.grupo = :grupo";
            $params[':grupo'] = $grupo;
        }

        if ($estado !== '') {
            $sql .= " AND a.estado_alumno = :estado";
            $params[':estado'] = $estado;
        }

        // Ordenamiento LÓGICO estricto por nivel educativo, grado numérico, grupo y apellido
        $sql .= " ORDER BY 
                    CASE a.nivel_educativo
                        WHEN 'Preescolar' THEN 1
                        WHEN 'Primaria' THEN 2
                        WHEN 'Secundaria' THEN 3
                        WHEN 'Bachillerato' THEN 4
                        ELSE 5
                    END ASC,
                    CAST(SUBSTRING_INDEX(a.grado, '°', 1) AS UNSIGNED) ASC,
                    a.grado ASC,
                    a.grupo ASC,
                    a.primer_apellido ASC,
                    a.segundo_apellido ASC,
                    a.nombre ASC";

        $alumnos = [];
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $errorMsg = 'Error al consultar los registros de alumnos: ' . $e->getMessage();
        }

        // 6. Obtener opciones disponibles para los filtros (grados, grupos, niveles)
        $filterOptions = [
            'niveles' => ['Preescolar', 'Primaria', 'Secundaria', 'Bachillerato'],
            'grados'  => [],
            'grupos'  => [],
            'estados' => ['inscrito', 'activo', 'preinscrito', 'baja', 'egresado']
        ];

        try {
            $filterOptions['grados'] = $db->query("
                SELECT DISTINCT grado 
                FROM alumnos_cea 
                WHERE grado IS NOT NULL AND grado != ''
                ORDER BY CAST(SUBSTRING_INDEX(grado, '°', 1) AS UNSIGNED) ASC, grado ASC
            ")->fetchAll(PDO::FETCH_COLUMN);

            $filterOptions['grupos'] = $db->query("
                SELECT DISTINCT grupo 
                FROM alumnos_cea 
                WHERE grupo IS NOT NULL AND grupo != '' 
                ORDER BY grupo ASC
            ")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            // Silencioso
        }

        // 7. Calcular métricas estadísticas de la matrícula
        $stats = [
            'total'      => count($alumnos),
            'inscritos'  => 0,
            'activos'    => 0,
            'bajas'      => 0,
            'por_nivel'  => ['Preescolar' => 0, 'Primaria' => 0, 'Secundaria' => 0, 'Bachillerato' => 0],
            'por_grupo'  => [],
        ];

        foreach ($alumnos as $al) {
            $est = strtolower($al['estado_alumno'] ?? '');
            if ($est === 'inscrito') $stats['inscritos']++;
            if ($est === 'activo')   $stats['activos']++;
            if ($est === 'baja')     $stats['bajas']++;

            $niv = $al['nivel_educativo'] ?? '';
            if (isset($stats['por_nivel'][$niv])) {
                $stats['por_nivel'][$niv]++;
            }

            $grpKey = trim(($al['grado'] ?? '') . ' ' . ($al['grupo'] ?? ''));
            if ($grpKey !== '') {
                $stats['por_grupo'][$grpKey] = ($stats['por_grupo'][$grpKey] ?? 0) + 1;
            }
        }

        if ($isAjax && $action === 'filter') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'alumnos' => $alumnos,
                'stats'   => $stats
            ]);
            exit;
        }

        return [
            'alumnos'       => $alumnos,
            'stats'         => $stats,
            'filters'       => [
                'search' => $search,
                'nivel'  => $nivel,
                'grado'  => $grado,
                'grupo'  => $grupo,
                'estado' => $estado,
            ],
            'filterOptions' => $filterOptions,
            'currentRole'   => $currentRole,
            'error'         => $errorMsg,
            'success'       => $successMsg,
        ];
    }

    /**
     * Consulta el expediente relacional completo de un alumno para visualización en modal.
     *
     * @param int $alumnoId Identificador del alumno.
     * @return array|null
     */
    public static function getAlumnoFullDetail(int $alumnoId): ?array
    {
        try {
            $db = Database::getInstance();
            
            // 1. Alumno
            $stmt = $db->prepare("SELECT * FROM alumnos_cea WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $alumnoId]);
            $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$alumno) return null;

            // 2. Plataforma
            $stmtPlat = $db->prepare("SELECT matricula, usuario_plataforma, email_institucional, acceso_activo, notas_acceso, created_at FROM alumnos_plataforma_cea WHERE alumno_id = :id LIMIT 1");
            $stmtPlat->execute([':id' => $alumnoId]);
            $plataforma = $stmtPlat->fetch(PDO::FETCH_ASSOC) ?: [];

            // 3. Tutores
            $stmtTut = $db->prepare("SELECT * FROM tutores_cea WHERE alumno_id = :id ORDER BY tipo_tutor ASC");
            $stmtTut->execute([':id' => $alumnoId]);
            $tutores = $stmtTut->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 4. Personas Autorizadas
            $stmtAuth = $db->prepare("SELECT * FROM personas_autorizadas_cea WHERE alumno_id = :id");
            $stmtAuth->execute([':id' => $alumnoId]);
            $autorizadas = $stmtAuth->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 5. Facturación
            $stmtFact = $db->prepare("SELECT * FROM facturacion_cea WHERE alumno_id = :id LIMIT 1");
            $stmtFact->execute([':id' => $alumnoId]);
            $facturacion = $stmtFact->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'alumno'               => $alumno,
                'plataforma'           => $plataforma,
                'tutores'              => $tutores,
                'personas_autorizadas' => $autorizadas,
                'facturacion'          => $facturacion,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Manejador principal para la vista de Alta de Alumno.
     *
     * @return array{error: string|null, success: string|null, alumnoId: int|null}
     */
    public static function handleCreate(): array
    {
        // 1. Control de acceso: Verificar que el usuario tenga rol de inscripción o super_admin
        AuthMiddleware::requireAnyRole(self::ALLOWED_ROLES);

        $errorMsg   = null;
        $successMsg = null;
        $alumnoId   = null;

        // 2. Procesar solicitudes AJAX o POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || isset($_POST['ajax'])
                || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            $action = $_POST['action'] ?? 'create';

            // Acción 2.1: Validación asíncrona de CURP
            if ($action === 'check_curp') {
                $curp = trim($_POST['curp'] ?? '');
                $result = self::checkCurp($curp);

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($result);
                exit;
            }

            // Acción 2.2: Alta y persistencia completa del alumno
            $createResult = self::processCreate($_POST);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'  => empty($createResult['error']),
                    'message'  => $createResult['error'] ?? $createResult['success'],
                    'alumnoId' => $createResult['id'] ?? null,
                ]);
                exit;
            }

            $errorMsg   = $createResult['error'] ?? null;
            $successMsg = $createResult['success'] ?? null;
            $alumnoId   = $createResult['id'] ?? null;
        }

        return [
            'error'    => $errorMsg,
            'success'  => $successMsg,
            'alumnoId' => $alumnoId,
        ];
    }

    /**
     * Consulta asíncrona en la base de datos si una CURP ya está registrada.
     *
     * @param string $curp Clave Única de Registro de Población.
     * @return array{success: bool, exists: bool, message: string, alumno?: array, curp?: string}
     */
    public static function checkCurp(string $curp): array
    {
        $curp = strtoupper(trim($curp));

        if ($curp === '') {
            return [
                'success' => false,
                'exists'  => false,
                'message' => 'Por favor ingresa una CURP para consultar.',
            ];
        }

        // Validación de formato oficial mexicano (18 caracteres)
        $pattern = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/';
        if (!preg_match($pattern, $curp)) {
            return [
                'success' => false,
                'exists'  => false,
                'message' => 'El formato de la CURP es inválido. Debe contener exactamente 18 caracteres alfanuméricos válidos.',
            ];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id, curp, nombre, primer_apellido, segundo_apellido, nivel_educativo, grado, grupo, estado_alumno, created_at 
                                  FROM alumnos_cea 
                                  WHERE curp = :curp 
                                  LIMIT 1");
            $stmt->bindValue(':curp', $curp, PDO::PARAM_STR);
            $stmt->execute();

            $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($alumno) {
                $nombreCompleto = trim("{$alumno['nombre']} {$alumno['primer_apellido']} {$alumno['segundo_apellido']}");
                return [
                    'success' => false,
                    'exists'  => true,
                    'message' => "La CURP ya se encuentra registrada en el sistema a nombre de {$nombreCompleto} ({$alumno['nivel_educativo']} {$alumno['grado']} - Estatus: {$alumno['estado_alumno']}).",
                    'alumno'  => $alumno,
                ];
            }

            return [
                'success' => true,
                'exists'  => false,
                'message' => "CURP verificada y disponible para nuevo ingreso.",
                'curp'    => $curp,
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'exists'  => false,
                'message' => 'Error al consultar la base de datos: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Procesa la inserción transaccional de un alumno con todas sus secciones relacionales.
     *
     * @param array $data Datos enviados en el formulario.
     * @return array{error: string|null, success: string|null, id: int|null}
     */
    public static function processCreate(array $data): array
    {
        // ==========================================
        // 1. Extracción y Normalización de Datos
        // ==========================================

        // Sección: Alumno
        $curp             = strtoupper(trim($data['curp'] ?? ''));
        $nombre           = trim($data['nombre'] ?? '');
        $primerApellido   = trim($data['primer_apellido'] ?? '');
        $segundoApellido  = trim($data['segundo_apellido'] ?? '');
        $fechaNacimiento  = trim($data['fecha_nacimiento'] ?? '') ?: null;
        $genero           = trim($data['genero'] ?? '') ?: null;
        $nivelEducativo   = trim($data['nivel_educativo'] ?? '');
        $grado            = trim($data['grado'] ?? '');
        $grupo            = trim($data['grupo'] ?? '') ?: null;
        $direccion        = trim($data['direccion'] ?? '') ?: null;
        $colonia          = trim($data['colonia'] ?? '') ?: null;
        $codigoPostal     = trim($data['codigo_postal'] ?? '') ?: null;
        $municipio        = trim($data['municipio'] ?? 'Puebla') ?: 'Puebla';
        $estado           = trim($data['estado'] ?? 'Puebla') ?: 'Puebla';
        $telefono         = trim($data['telefono'] ?? '') ?: null;
        $email            = trim($data['email'] ?? '') ?: null;
        $tipoSangre       = trim($data['tipo_sangre'] ?? '') ?: null;
        $alergias         = trim($data['alergias_condiciones'] ?? '') ?: null;
        $contactoEmerg    = trim($data['contacto_emergencia'] ?? '') ?: null;
        $telEmergencia    = trim($data['telefono_emergencia'] ?? '') ?: null;

        // Sección: Plataforma Escolar
        $matricula          = trim($data['matricula'] ?? '') ?: null;
        $usuarioPlataforma  = trim($data['usuario_plataforma'] ?? '') ?: null;
        $emailInstitucional = trim($data['email_institucional'] ?? '') ?: null;
        $passwordPlataforma = trim($data['password_plataforma'] ?? '') ?: null;
        $accesoActivo       = isset($data['acceso_activo']) ? ((int)$data['acceso_activo'] === 1 ? 1 : 0) : 1;
        $notasAcceso        = trim($data['notas_acceso'] ?? '') ?: null;

        // Sección: Tutor 1 (Principal)
        $tutor1Nombre       = trim($data['tutor1_nombre'] ?? '');
        $tutor1Parentesco   = trim($data['tutor1_parentesco'] ?? '');
        $tutor1TelPrin      = trim($data['tutor1_telefono_principal'] ?? '');
        $tutor1TelSec       = trim($data['tutor1_telefono_secundario'] ?? '') ?: null;
        $tutor1Email        = trim($data['tutor1_email'] ?? '') ?: null;
        $tutor1Ocupacion    = trim($data['tutor1_ocupacion'] ?? '') ?: null;
        $tutor1LugarTrabajo = trim($data['tutor1_lugar_trabajo'] ?? '') ?: null;
        $tutor1Vive         = isset($data['tutor1_vive_con_alumno']) ? 1 : 0;
        $tutor1RespEco      = isset($data['tutor1_es_responsable_economico']) ? 1 : 0;

        // Sección: Tutor 2 (Secundario / Opcional)
        $tutor2Nombre       = trim($data['tutor2_nombre'] ?? '');
        $tutor2Parentesco   = trim($data['tutor2_parentesco'] ?? '');
        $tutor2TelPrin      = trim($data['tutor2_telefono_principal'] ?? '');
        $tutor2TelSec       = trim($data['tutor2_telefono_secundario'] ?? '') ?: null;
        $tutor2Email        = trim($data['tutor2_email'] ?? '') ?: null;
        $tutor2Ocupacion    = trim($data['tutor2_ocupacion'] ?? '') ?: null;
        $tutor2LugarTrabajo = trim($data['tutor2_lugar_trabajo'] ?? '') ?: null;
        $tutor2Vive         = isset($data['tutor2_vive_con_alumno']) ? 1 : 0;
        $tutor2RespEco      = isset($data['tutor2_es_responsable_economico']) ? 1 : 0;

        // Sección: Personas Autorizadas
        $authNombres        = $data['auth_nombre'] ?? [];
        $authParentescos    = $data['auth_parentesco'] ?? [];
        $authTelefonos      = $data['auth_telefono'] ?? [];
        $authIdentifs       = $data['auth_identificacion'] ?? [];
        $authNotas          = $data['auth_observaciones'] ?? [];

        // Sección: Facturación
        $requiereFactura    = isset($data['requiere_factura']) && (int)$data['requiere_factura'] === 1 ? 1 : 0;
        $razonSocial        = trim($data['razon_social'] ?? '') ?: null;
        $rfc                = strtoupper(trim($data['rfc'] ?? '')) ?: null;
        $regimenFiscal      = trim($data['regimen_fiscal'] ?? '') ?: null;
        $usoCfdi            = trim($data['uso_cfdi'] ?? '') ?: null;
        $domicilioFiscal    = trim($data['domicilio_fiscal'] ?? '') ?: null;
        $cpFiscal           = trim($data['codigo_postal_fiscal'] ?? '') ?: null;
        $correoFacturacion  = trim($data['correo_facturacion'] ?? '') ?: null;

        // ==========================================
        // 2. Validaciones Rigurosas del Servidor
        // ==========================================

        if ($curp === '') {
            return ['error' => 'La CURP del alumno es obligatoria.', 'success' => null, 'id' => null];
        }

        $pattern = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/';
        if (!preg_match($pattern, $curp)) {
            return ['error' => 'El formato de la CURP es inválido (debe tener 18 caracteres alfanuméricos válidos).', 'success' => null, 'id' => null];
        }

        if ($nombre === '') {
            return ['error' => 'El nombre del alumno es obligatorio.', 'success' => null, 'id' => null];
        }
        if ($primerApellido === '') {
            return ['error' => 'El primer apellido del alumno es obligatorio.', 'success' => null, 'id' => null];
        }
        if ($nivelEducativo === '') {
            return ['error' => 'Debes seleccionar el nivel educativo del alumno.', 'success' => null, 'id' => null];
        }
        if ($grado === '') {
            return ['error' => 'Debes indicar el grado de inscripción del alumno.', 'success' => null, 'id' => null];
        }

        // Tutor 1 es obligatorio
        if ($tutor1Nombre === '') {
            return ['error' => 'Los datos del Tutor 1 son obligatorios (Nombre completo requerido).', 'success' => null, 'id' => null];
        }
        if ($tutor1Parentesco === '') {
            return ['error' => 'Debes seleccionar el parentesco del Tutor 1.', 'success' => null, 'id' => null];
        }
        if ($tutor1TelPrin === '') {
            return ['error' => 'El teléfono principal del Tutor 1 es obligatorio.', 'success' => null, 'id' => null];
        }

        // Facturación condicional
        if ($requiereFactura === 1) {
            if (!$razonSocial) {
                return ['error' => 'Si requiere factura, la Razón Social es obligatoria.', 'success' => null, 'id' => null];
            }
            if (!$rfc) {
                return ['error' => 'Si requiere factura, el RFC es obligatorio.', 'success' => null, 'id' => null];
            }
            if (!$cpFiscal) {
                return ['error' => 'Si requiere factura, el Código Postal Fiscal es obligatorio.', 'success' => null, 'id' => null];
            }
        }

        // ==========================================
        // 3. Ejecución Transaccional con PDO
        // ==========================================
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // 3.1. Verificar unicidad de la CURP con bloqueo o consulta directa
            $stmtCheck = $db->prepare("SELECT id FROM alumnos_cea WHERE curp = :curp LIMIT 1");
            $stmtCheck->bindValue(':curp', $curp, PDO::PARAM_STR);
            $stmtCheck->execute();

            if ($stmtCheck->fetch()) {
                $db->rollBack();
                return [
                    'error'   => "La CURP '{$curp}' ya se encuentra registrada en la base de datos.",
                    'success' => null,
                    'id'      => null
                ];
            }

            // 3.2. Insertar en alumnos_cea
            $sqlAlumno = "INSERT INTO alumnos_cea (
                curp, nombre, primer_apellido, segundo_apellido, fecha_nacimiento, genero,
                nivel_educativo, grado, grupo, direccion, colonia, codigo_postal,
                municipio, estado, telefono, email, tipo_sangre, alergias_condiciones,
                contacto_emergencia, telefono_emergencia, estado_alumno, created_at
            ) VALUES (
                :curp, :nombre, :primer_apellido, :segundo_apellido, :fecha_nacimiento, :genero,
                :nivel_educativo, :grado, :grupo, :direccion, :colonia, :codigo_postal,
                :municipio, :estado, :telefono, :email, :tipo_sangre, :alergias_condiciones,
                :contacto_emergencia, :telefono_emergencia, 'inscrito', NOW()
            )";

            $stmtAlumno = $db->prepare($sqlAlumno);
            $stmtAlumno->bindValue(':curp',                 $curp,                 PDO::PARAM_STR);
            $stmtAlumno->bindValue(':nombre',               $nombre,               PDO::PARAM_STR);
            $stmtAlumno->bindValue(':primer_apellido',      $primerApellido,       PDO::PARAM_STR);
            $stmtAlumno->bindValue(':segundo_apellido',     $segundoApellido,      PDO::PARAM_STR);
            $stmtAlumno->bindValue(':fecha_nacimiento',     $fechaNacimiento,      PDO::PARAM_STR);
            $stmtAlumno->bindValue(':genero',               $genero,               PDO::PARAM_STR);
            $stmtAlumno->bindValue(':nivel_educativo',      $nivelEducativo,       PDO::PARAM_STR);
            $stmtAlumno->bindValue(':grado',                $grado,                PDO::PARAM_STR);
            $stmtAlumno->bindValue(':grupo',                $grupo,                PDO::PARAM_STR);
            $stmtAlumno->bindValue(':direccion',            $direccion,            PDO::PARAM_STR);
            $stmtAlumno->bindValue(':colonia',              $colonia,              PDO::PARAM_STR);
            $stmtAlumno->bindValue(':codigo_postal',        $codigoPostal,         PDO::PARAM_STR);
            $stmtAlumno->bindValue(':municipio',            $municipio,            PDO::PARAM_STR);
            $stmtAlumno->bindValue(':estado',               $estado,               PDO::PARAM_STR);
            $stmtAlumno->bindValue(':telefono',             $telefono,             PDO::PARAM_STR);
            $stmtAlumno->bindValue(':email',                $email,                PDO::PARAM_STR);
            $stmtAlumno->bindValue(':tipo_sangre',          $tipoSangre,           PDO::PARAM_STR);
            $stmtAlumno->bindValue(':alergias_condiciones', $alergias,             PDO::PARAM_STR);
            $stmtAlumno->bindValue(':contacto_emergencia',  $contactoEmerg,        PDO::PARAM_STR);
            $stmtAlumno->bindValue(':telefono_emergencia',  $telEmergencia,        PDO::PARAM_STR);
            $stmtAlumno->execute();

            $alumnoId = (int)$db->lastInsertId();

            // 3.3. Insertar en alumnos_plataforma_cea
            if (!$matricula) {
                $matricula = 'CEA-' . date('Y') . '-' . str_pad((string)$alumnoId, 4, '0', STR_PAD_LEFT);
            }
            if (!$usuarioPlataforma) {
                // Sugerir usuario: primera letra del nombre + primer apellido + id
                $cleanNom = strtolower(preg_replace('/[^a-zA-Z]/', '', $nombre));
                $cleanApe = strtolower(preg_replace('/[^a-zA-Z]/', '', $primerApellido));
                $usuarioPlataforma = substr($cleanNom, 0, 1) . $cleanApe . $alumnoId;
            }

            $passHash = null;
            if ($passwordPlataforma) {
                $passHash = password_hash($passwordPlataforma, PASSWORD_DEFAULT);
            } else {
                // Contraseña por defecto: fecha de nacimiento o matrícula
                $defaultPass = $matricula;
                $passHash = password_hash($defaultPass, PASSWORD_DEFAULT);
            }

            $sqlPlat = "INSERT INTO alumnos_plataforma_cea (
                alumno_id, matricula, usuario_plataforma, email_institucional, password_hash,
                acceso_activo, notas_acceso, created_at
            ) VALUES (
                :alumno_id, :matricula, :usuario_plataforma, :email_institucional, :password_hash,
                :acceso_activo, :notas_acceso, NOW()
            )";

            $stmtPlat = $db->prepare($sqlPlat);
            $stmtPlat->bindValue(':alumno_id',           $alumnoId,           PDO::PARAM_INT);
            $stmtPlat->bindValue(':matricula',           $matricula,           PDO::PARAM_STR);
            $stmtPlat->bindValue(':usuario_plataforma',  $usuarioPlataforma,  PDO::PARAM_STR);
            $stmtPlat->bindValue(':email_institucional', $emailInstitucional, PDO::PARAM_STR);
            $stmtPlat->bindValue(':password_hash',       $passHash,           PDO::PARAM_STR);
            $stmtPlat->bindValue(':acceso_activo',       $accesoActivo,       PDO::PARAM_INT);
            $stmtPlat->bindValue(':notas_acceso',        $notasAcceso,        PDO::PARAM_STR);
            $stmtPlat->execute();

            // 3.4. Insertar Tutores en tutores_cea
            // Tutor 1
            $sqlTutor = "INSERT INTO tutores_cea (
                alumno_id, tipo_tutor, nombre_completo, parentesco, telefono_principal,
                telefono_secundario, email, ocupacion, lugar_trabajo, vive_con_alumno,
                es_responsable_economico, created_at
            ) VALUES (
                :alumno_id, :tipo_tutor, :nombre_completo, :parentesco, :telefono_principal,
                :telefono_secundario, :email, :ocupacion, :lugar_trabajo, :vive_con_alumno,
                :es_responsable_economico, NOW()
            )";

            $stmtTutor = $db->prepare($sqlTutor);
            $stmtTutor->bindValue(':alumno_id',                $alumnoId,           PDO::PARAM_INT);
            $stmtTutor->bindValue(':tipo_tutor',               'tutor1',            PDO::PARAM_STR);
            $stmtTutor->bindValue(':nombre_completo',          $tutor1Nombre,       PDO::PARAM_STR);
            $stmtTutor->bindValue(':parentesco',               $tutor1Parentesco,   PDO::PARAM_STR);
            $stmtTutor->bindValue(':telefono_principal',       $tutor1TelPrin,      PDO::PARAM_STR);
            $stmtTutor->bindValue(':telefono_secundario',      $tutor1TelSec,       PDO::PARAM_STR);
            $stmtTutor->bindValue(':email',                    $tutor1Email,        PDO::PARAM_STR);
            $stmtTutor->bindValue(':ocupacion',                $tutor1Ocupacion,    PDO::PARAM_STR);
            $stmtTutor->bindValue(':lugar_trabajo',            $tutor1LugarTrabajo, PDO::PARAM_STR);
            $stmtTutor->bindValue(':vive_con_alumno',          $tutor1Vive,         PDO::PARAM_INT);
            $stmtTutor->bindValue(':es_responsable_economico', $tutor1RespEco,      PDO::PARAM_INT);
            $stmtTutor->execute();

            // Tutor 2 (si se especificó nombre)
            if ($tutor2Nombre !== '') {
                $stmtTutor2 = $db->prepare($sqlTutor);
                $stmtTutor2->bindValue(':alumno_id',                $alumnoId,           PDO::PARAM_INT);
                $stmtTutor2->bindValue(':tipo_tutor',               'tutor2',            PDO::PARAM_STR);
                $stmtTutor2->bindValue(':nombre_completo',          $tutor2Nombre,       PDO::PARAM_STR);
                $stmtTutor2->bindValue(':parentesco',               $tutor2Parentesco ?: 'No especificado', PDO::PARAM_STR);
                $stmtTutor2->bindValue(':telefono_principal',       $tutor2TelPrin ?: $tutor1TelPrin,       PDO::PARAM_STR);
                $stmtTutor2->bindValue(':telefono_secundario',      $tutor2TelSec,       PDO::PARAM_STR);
                $stmtTutor2->bindValue(':email',                    $tutor2Email,        PDO::PARAM_STR);
                $stmtTutor2->bindValue(':ocupacion',                $tutor2Ocupacion,    PDO::PARAM_STR);
                $stmtTutor2->bindValue(':lugar_trabajo',            $tutor2LugarTrabajo, PDO::PARAM_STR);
                $stmtTutor2->bindValue(':vive_con_alumno',          $tutor2Vive,         PDO::PARAM_INT);
                $stmtTutor2->bindValue(':es_responsable_economico', $tutor2RespEco,      PDO::PARAM_INT);
                $stmtTutor2->execute();
            }

            // 3.5. Insertar Personas Autorizadas en personas_autorizadas_cea
            if (is_array($authNombres)) {
                $sqlAuth = "INSERT INTO personas_autorizadas_cea (
                    alumno_id, nombre_completo, parentesco, telefono, identificacion_tipo, observaciones, created_at
                ) VALUES (
                    :alumno_id, :nombre_completo, :parentesco, :telefono, :identificacion_tipo, :observaciones, NOW()
                )";
                $stmtAuth = $db->prepare($sqlAuth);

                foreach ($authNombres as $idx => $authNom) {
                    $authNom = trim($authNom);
                    if ($authNom === '') continue;

                    $pParentesco = trim($authParentescos[$idx] ?? 'Familiar/Conocido');
                    $pTel        = trim($authTelefonos[$idx] ?? '');
                    $pIden       = trim($authIdentifs[$idx] ?? 'INE');
                    $pObs        = trim($authNotas[$idx] ?? '');

                    $stmtAuth->bindValue(':alumno_id',            $alumnoId,    PDO::PARAM_INT);
                    $stmtAuth->bindValue(':nombre_completo',      $authNom,     PDO::PARAM_STR);
                    $stmtAuth->bindValue(':parentesco',           $pParentesco, PDO::PARAM_STR);
                    $stmtAuth->bindValue(':telefono',             $pTel,        PDO::PARAM_STR);
                    $stmtAuth->bindValue(':identificacion_tipo',  $pIden,       PDO::PARAM_STR);
                    $stmtAuth->bindValue(':observaciones',        $pObs,        PDO::PARAM_STR);
                    $stmtAuth->execute();
                }
            }

            // 3.6. Insertar Facturación en facturacion_cea
            $sqlFact = "INSERT INTO facturacion_cea (
                alumno_id, requiere_factura, razon_social, rfc, regimen_fiscal, uso_cfdi,
                domicilio_fiscal, codigo_postal_fiscal, correo_facturacion, created_at
            ) VALUES (
                :alumno_id, :requiere_factura, :razon_social, :rfc, :regimen_fiscal, :uso_cfdi,
                :domicilio_fiscal, :codigo_postal_fiscal, :correo_facturacion, NOW()
            )";

            $stmtFact = $db->prepare($sqlFact);
            $stmtFact->bindValue(':alumno_id',             $alumnoId,          PDO::PARAM_INT);
            $stmtFact->bindValue(':requiere_factura',      $requiereFactura,   PDO::PARAM_INT);
            $stmtFact->bindValue(':razon_social',          $razonSocial,       PDO::PARAM_STR);
            $stmtFact->bindValue(':rfc',                   $rfc,               PDO::PARAM_STR);
            $stmtFact->bindValue(':regimen_fiscal',        $regimenFiscal,     PDO::PARAM_STR);
            $stmtFact->bindValue(':uso_cfdi',              $usoCfdi,           PDO::PARAM_STR);
            $stmtFact->bindValue(':domicilio_fiscal',      $domicilioFiscal,   PDO::PARAM_STR);
            $stmtFact->bindValue(':codigo_postal_fiscal',  $cpFiscal,          PDO::PARAM_STR);
            $stmtFact->bindValue(':correo_facturacion',    $correoFacturacion, PDO::PARAM_STR);
            $stmtFact->execute();

            // Confirmar transacción
            $db->commit();

            return [
                'error'   => null,
                'success' => "Alumno <strong>{$nombre} {$primerApellido}</strong> registrado exitosamente en el sistema con Matrícula <strong>{$matricula}</strong>.",
                'id'      => $alumnoId,
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'error'   => 'Error en base de datos al registrar el alumno: ' . $e->getMessage(),
                'success' => null,
                'id'      => null,
            ];
        }
    }
}
