<?php

namespace Core\controllers;

use Core\AuthMiddleware;
use Core\Database;
use PDO;
use Throwable;

class PreciosController
{
    /**
     * Roles autorizados para acceder y gestionar el módulo de Precios.
     * Restringido exclusivamente al rol de Administrador / Super Admin.
     */
    public const ALLOWED_ROLES = ['super_admin'];

    /**
     * Niveles educativos oficiales del CEA soportados por el sistema.
     */
    public const NIVELES = ['preescolar', 'primaria', 'secundaria'];

    /**
     * Manejador principal para la vista y las operaciones AJAX de Precios y Tarifas.
     *
     * @return array Datos para renderizar en la vista.
     */
    public static function handle(): array
    {
        // 1. Control de Acceso RBAC estricto
        AuthMiddleware::requireAnyRole(self::ALLOWED_ROLES);

        $db = Database::getInstance();

        // 2. Procesamiento de peticiones AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_GET['ajax']) || isset($_POST['ajax'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        $action = $_REQUEST['action'] ?? null;

        if ($isAjax || $action !== null) {
            self::handleAjaxRequests($db, $action);
            exit;
        }

        // 3. Fallback POST tradicional (sin JS)
        $msgSuccess = null;
        $msgError = null;

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod === 'POST') {
            $postAction = $_POST['action'] ?? '';
            if ($postAction === 'update_single') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
                if ($id && $monto !== false && $monto >= 0) {
                    $stmt = $db->prepare("UPDATE conceptos_precios_cea SET monto = :monto, updated_at = NOW() WHERE id = :id");
                    $stmt->execute([':monto' => $monto, ':id' => $id]);
                    $msgSuccess = "Tarifa actualizada con éxito.";
                } else {
                    $msgError = "Monto inválido para actualizar la tarifa.";
                }
            }
        }

        // 4. Consulta de tarifas agrupadas por nivel
        $tarifasPorNivel = self::obtenerTarifasAgrupadas($db);
        $metricasPorNivel = self::calcularMetricas($tarifasPorNivel);

        return [
            'tarifas'   => $tarifasPorNivel,
            'metricas'  => $metricasPorNivel,
            'niveles'   => self::NIVELES,
            'success'   => $msgSuccess,
            'error'     => $msgError,
            'usuario'   => $_SESSION['user']['name'] ?? 'Administrador',
            'rol'       => $_SESSION['user']['role_name'] ?? 'Super Admin',
        ];
    }

    /**
     * Procesa todas las peticiones AJAX de gestión de tarifas con respuestas JSON y PDO preparado.
     */
    private static function handleAjaxRequests(PDO $db, ?string $action): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Leer datos JSON del cuerpo de la petición si aplica
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true) ?? [];
        $data = !empty($jsonData) ? $jsonData : $_POST;

        $action = $action ?? ($data['action'] ?? null);

        try {
            switch ($action) {
                case 'update_tarifa':
                    self::ajaxUpdateTarifa($db, $data);
                    break;

                case 'update_nivel_bulk':
                    self::ajaxUpdateNivelBulk($db, $data);
                    break;

                case 'toggle_activo':
                    self::ajaxToggleActivo($db, $data);
                    break;

                case 'add_concepto':
                    self::ajaxAddConcepto($db, $data);
                    break;

                case 'get_metricas':
                    self::ajaxGetMetricas($db);
                    break;

                default:
                    echo json_encode([
                        'success' => false,
                        'error'   => 'Acción no reconocida o no especificada.'
                    ]);
                    break;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => 'Error en el servidor: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Actualiza el monto de una tarifa individual con consulta preparada.
     */
    private static function ajaxUpdateTarifa(PDO $db, array $data): void
    {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $montoRaw = str_replace([',', '$', ' '], '', (string)($data['monto'] ?? ''));
        $monto = filter_var($montoRaw, FILTER_VALIDATE_FLOAT);

        if (!$id || $id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Identificador de concepto inválido.']);
            return;
        }

        if ($monto === false || $monto < 0) {
            echo json_encode(['success' => false, 'error' => 'El monto debe ser un número positivo mayor o igual a 0.']);
            return;
        }

        // Actualizar mediante consulta preparada
        $stmt = $db->prepare("UPDATE conceptos_precios_cea 
            SET monto = :monto, updated_at = NOW() 
            WHERE id = :id");
        $stmt->execute([
            ':monto' => $monto,
            ':id'    => $id
        ]);

        if ($stmt->rowCount() === 0) {
            // Verificar si el registro existe y el monto no cambió
            $check = $db->prepare("SELECT id, nivel, concepto, monto, updated_at FROM conceptos_precios_cea WHERE id = :id");
            $check->execute([':id' => $id]);
            $registro = $check->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                echo json_encode(['success' => false, 'error' => 'No se encontró la tarifa solicitada.']);
                return;
            }
        } else {
            $check = $db->prepare("SELECT id, nivel, concepto, monto, updated_at FROM conceptos_precios_cea WHERE id = :id");
            $check->execute([':id' => $id]);
            $registro = $check->fetch(PDO::FETCH_ASSOC);
        }

        $nivel = $registro['nivel'] ?? 'primaria';
        $metricasNivel = self::calcularMetricasNivel($db, $nivel);

        echo json_encode([
            'success'   => true,
            'message'   => "Tarifa para '{$registro['concepto']}' actualizada a $" . number_format($monto, 2) . " MXN.",
            'data'      => [
                'id'         => $id,
                'concepto'   => $registro['concepto'],
                'nivel'      => $nivel,
                'monto'      => (float)$registro['monto'],
                'monto_fmt'  => number_format((float)$registro['monto'], 2),
                'updated_at' => date('d/m/Y H:i', strtotime($registro['updated_at'] ?? 'now')),
                'metricas'   => $metricasNivel
            ]
        ]);
    }

    /**
     * AJAX: Actualización en lote (bulk) para todas las tarifas de un nivel en una transacción PDO atómica.
     */
    private static function ajaxUpdateNivelBulk(PDO $db, array $data): void
    {
        $nivel = strtolower(trim((string)($data['nivel'] ?? '')));
        $tarifas = $data['tarifas'] ?? [];

        if (!in_array($nivel, self::NIVELES, true)) {
            echo json_encode(['success' => false, 'error' => 'Nivel educativo inválido.']);
            return;
        }

        if (!is_array($tarifas) || empty($tarifas)) {
            echo json_encode(['success' => false, 'error' => 'No se proporcionaron tarifas para actualizar.']);
            return;
        }

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE conceptos_precios_cea 
                SET monto = :monto, updated_at = NOW() 
                WHERE id = :id AND nivel = :nivel");

            $actualizados = 0;
            foreach ($tarifas as $item) {
                $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
                $montoRaw = str_replace([',', '$', ' '], '', (string)($item['monto'] ?? ''));
                $monto = filter_var($montoRaw, FILTER_VALIDATE_FLOAT);

                if ($id && $monto !== false && $monto >= 0) {
                    $stmt->execute([
                        ':monto' => $monto,
                        ':id'    => $id,
                        ':nivel' => $nivel
                    ]);
                    $actualizados++;
                }
            }

            $db->commit();

            $metricasNivel = self::calcularMetricasNivel($db, $nivel);

            echo json_encode([
                'success' => true,
                'message' => "Se actualizaron {$actualizados} tarifas para el nivel " . ucfirst($nivel) . ".",
                'data'    => [
                    'nivel'        => $nivel,
                    'actualizados' => $actualizados,
                    'metricas'     => $metricasNivel
                ]
            ]);

        } catch (Throwable $ex) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Error al guardar tarifas en lote: ' . $ex->getMessage()]);
        }
    }

    /**
     * AJAX: Activar o desactivar un concepto de cobro.
     */
    private static function ajaxToggleActivo(PDO $db, array $data): void
    {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $activo = !empty($data['activo']) ? 1 : 0;

        if (!$id || $id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Identificador inválido.']);
            return;
        }

        $stmt = $db->prepare("UPDATE conceptos_precios_cea SET activo = :activo, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':activo' => $activo, ':id' => $id]);

        $check = $db->prepare("SELECT id, nivel, concepto, activo FROM conceptos_precios_cea WHERE id = :id");
        $check->execute([':id' => $id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        $metricas = self::calcularMetricasNivel($db, $row['nivel'] ?? 'primaria');

        echo json_encode([
            'success' => true,
            'message' => $activo ? 'Concepto activado.' : 'Concepto desactivado.',
            'data'    => [
                'id'       => $id,
                'activo'   => $activo,
                'metricas' => $metricas
            ]
        ]);
    }

    /**
     * AJAX: Agregar un nuevo concepto opcional a un nivel educativo.
     */
    private static function ajaxAddConcepto(PDO $db, array $data): void
    {
        $concepto = trim((string)($data['concepto'] ?? ''));
        $nivel = strtolower(trim((string)($data['nivel'] ?? '')));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $montoRaw = str_replace([',', '$', ' '], '', (string)($data['monto'] ?? '0'));
        $monto = filter_var($montoRaw, FILTER_VALIDATE_FLOAT) ?: 0.00;

        if ($concepto === '') {
            echo json_encode(['success' => false, 'error' => 'El nombre del concepto es obligatorio.']);
            return;
        }

        if (!in_array($nivel, self::NIVELES, true)) {
            echo json_encode(['success' => false, 'error' => 'Nivel educativo no válido.']);
            return;
        }

        // Verificar duplicados
        $check = $db->prepare("SELECT id FROM conceptos_precios_cea WHERE concepto = :concepto AND nivel = :nivel LIMIT 1");
        $check->execute([':concepto' => $concepto, ':nivel' => $nivel]);
        if ($check->fetchColumn()) {
            echo json_encode(['success' => false, 'error' => 'Ya existe un concepto con ese nombre en este nivel.']);
            return;
        }

        $stmt = $db->prepare("INSERT INTO conceptos_precios_cea (concepto, nivel, monto, descripcion, activo) 
            VALUES (:concepto, :nivel, :monto, :descripcion, 1)");
        $stmt->execute([
            ':concepto'    => $concepto,
            ':nivel'       => $nivel,
            ':monto'       => $monto,
            ':descripcion' => $descripcion ?: null
        ]);

        $newId = (int)$db->lastInsertId();
        $metricas = self::calcularMetricasNivel($db, $nivel);

        echo json_encode([
            'success' => true,
            'message' => "Concepto '{$concepto}' agregado exitosamente al nivel " . ucfirst($nivel) . ".",
            'data'    => [
                'id'          => $newId,
                'concepto'    => $concepto,
                'nivel'       => $nivel,
                'monto'       => $monto,
                'monto_fmt'   => number_format($monto, 2),
                'descripcion' => $descripcion,
                'activo'      => 1,
                'metricas'    => $metricas
            ]
        ]);
    }

    /**
     * Obtiene todas las tarifas agrupadas en un array asociativo por nivel educativo.
     *
     * @return array<string, array>
     */
    private static function obtenerTarifasAgrupadas(PDO $db): array
    {
        $stmt = $db->query("SELECT id, concepto, nivel, monto, descripcion, activo, created_at, updated_at 
            FROM conceptos_precios_cea 
            ORDER BY FIELD(nivel, 'preescolar', 'primaria', 'secundaria'), id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $agrupadas = [
            'preescolar' => [],
            'primaria'   => [],
            'secundaria' => []
        ];

        foreach ($rows as $row) {
            $nivel = strtolower($row['nivel']);
            if (isset($agrupadas[$nivel])) {
                $agrupadas[$nivel][] = $row;
            }
        }

        return $agrupadas;
    }

    /**
     * Calcula métricas agregadas globales por nivel (colegiatura, inscripción, paquete total).
     */
    private static function calcularMetricas(array $tarifasPorNivel): array
    {
        $metricas = [];
        foreach (self::NIVELES as $nivel) {
            $conceptos = $tarifasPorNivel[$nivel] ?? [];
            $metricas[$nivel] = self::procesarMetricasDeArray($conceptos);
        }
        return $metricas;
    }

    /**
     * Calcula métricas de un nivel directamente desde la base de datos para respuestas AJAX rápidas.
     */
    private static function calcularMetricasNivel(PDO $db, string $nivel): array
    {
        $stmt = $db->prepare("SELECT id, concepto, monto, activo FROM conceptos_precios_cea WHERE nivel = :nivel");
        $stmt->execute([':nivel' => $nivel]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return self::procesarMetricasDeArray($rows);
    }

    /**
     * Extrae métricas clave (colegiatura, inscripción, paquete total y conteo) de una lista de conceptos.
     */
    private static function procesarMetricasDeArray(array $conceptos): array
    {
        $colegiatura = 0.00;
        $inscripcion = 0.00;
        $paqueteTotal = 0.00;
        $totalActivos = 0;

        foreach ($conceptos as $c) {
            $monto = (float)($c['monto'] ?? 0);
            $nombre = mb_strtolower($c['concepto'] ?? '');
            $activo = !empty($c['activo']);

            if ($activo) {
                $totalActivos++;
                $paqueteTotal += $monto;

                if (str_contains($nombre, 'colegiatura')) {
                    $colegiatura = $monto;
                } elseif (str_contains($nombre, 'inscripción') || str_contains($nombre, 'inscripcion')) {
                    $inscripcion = $monto;
                }
            }
        }

        return [
            'colegiatura'      => $colegiatura,
            'colegiatura_fmt'  => number_format($colegiatura, 2),
            'inscripcion'      => $inscripcion,
            'inscripcion_fmt'  => number_format($inscripcion, 2),
            'paquete_total'    => $paqueteTotal,
            'paquete_fmt'      => number_format($paqueteTotal, 2),
            'total_conceptos'  => count($conceptos),
            'total_activos'    => $totalActivos,
        ];
    }
}
