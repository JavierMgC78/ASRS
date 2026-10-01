<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/ViewCache.php';
require_once __DIR__ . '/../Core/MenuGroupCache.php';
require_once __DIR__ . '/../Core/PermissionCache.php';

use Core\Database;
use Core\ViewCache;
use Core\MenuGroupCache;
use Core\PermissionCache;

try {
    $db = Database::getInstance();

    echo "--- 1. Creando tabla conceptos_pago_cea ---\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS conceptos_pago_cea (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE,
            descripcion VARCHAR(255) NULL,
            monto_sugerido DECIMAL(10, 2) DEFAULT 0.00,
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "--- 2. Poblando conceptos_pago_cea ---\n";
    $conceptos = [
        ['nombre' => 'Colegiatura Mensual', 'descripcion' => 'Colegiatura regular del ciclo escolar', 'monto_sugerido' => 3500.00],
        ['nombre' => 'Inscripción Anual', 'descripcion' => 'Inscripción y matrícula para nuevo ciclo', 'monto_sugerido' => 4500.00],
        ['nombre' => 'Almuerzo / Comedor', 'descripcion' => 'Servicio de comedor escolar mensual', 'monto_sugerido' => 1200.00],
        ['nombre' => 'Libros y Material Didáctico', 'descripcion' => 'Paquete oficial de libros de texto', 'monto_sugerido' => 2800.00],
        ['nombre' => 'Uniforme Escolar', 'descripcion' => 'Uniformes institucionales y deportivos', 'monto_sugerido' => 1500.00],
        ['nombre' => 'Taller Extracurricular', 'descripcion' => 'Actividades deportivas, artísticas y robótica', 'monto_sugerido' => 850.00],
        ['nombre' => 'Seguro Médico Escolar', 'descripcion' => 'Póliza de seguro contra accidentes escolares', 'monto_sugerido' => 650.00],
        ['nombre' => 'Constancias y Trámites', 'descripcion' => 'Expedición de certificados y constancias', 'monto_sugerido' => 250.00],
    ];

    $stmtInsertConcepto = $db->prepare("
        INSERT INTO conceptos_pago_cea (nombre, descripcion, monto_sugerido, activo)
        VALUES (:nombre, :descripcion, :monto_sugerido, 1)
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), monto_sugerido = VALUES(monto_sugerido)
    ");

    foreach ($conceptos as $c) {
        $stmtInsertConcepto->execute([
            ':nombre' => $c['nombre'],
            ':descripcion' => $c['descripcion'],
            ':monto_sugerido' => $c['monto_sugerido']
        ]);
    }

    echo "--- 3. Creando tabla pagos_cea ---\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS pagos_cea (
            id INT AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(35) NOT NULL UNIQUE,
            alumno_id INT NOT NULL,
            cajero_id INT NULL,
            concepto_id INT NULL,
            concepto VARCHAR(150) NOT NULL,
            monto DECIMAL(10, 2) NOT NULL,
            forma_pago VARCHAR(50) NOT NULL,
            referencia VARCHAR(100) NULL,
            fecha_pago DATE NOT NULL,
            hora_pago TIME NOT NULL,
            observaciones TEXT NULL,
            estado ENUM('completado', 'cancelado', 'pendiente') DEFAULT 'completado',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_alumno (alumno_id),
            INDEX idx_fecha (fecha_pago),
            INDEX idx_folio (folio),
            CONSTRAINT fk_pagos_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos_cea(id) ON DELETE RESTRICT,
            CONSTRAINT fk_pagos_cajero FOREIGN KEY (cajero_id) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_pagos_concepto FOREIGN KEY (concepto_id) REFERENCES conceptos_pago_cea(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "--- 4. Creando / Asegurando grupo de menú 'Caja' ---\n";
    $stmtGroup = $db->prepare("SELECT id FROM menu_groups WHERE slug = 'caja' OR name = 'Caja'");
    $stmtGroup->execute();
    $groupId = $stmtGroup->fetchColumn();

    if (!$groupId) {
        $stmtInsertGroup = $db->prepare("
            INSERT INTO menu_groups (name, slug, display_order, icon, description, is_active, created_at, updated_at)
            VALUES ('Caja', 'caja', 4, '', 'Módulo financiero de caja, cobros y captura de pagos escolares', 1, NOW(), NOW())
        ");
        $stmtInsertGroup->execute();
        $groupId = $db->lastInsertId();
        echo "Grupo 'Caja' creado con ID: $groupId\n";
    } else {
        echo "Grupo 'Caja' ya existía con ID: $groupId\n";
    }

    echo "--- 5. Creando / Asegurando vista '/caja/capturar' en tabla views ---\n";
    $stmtView = $db->prepare("SELECT id FROM views WHERE uri = '/caja/capturar'");
    $stmtView->execute();
    $viewId = $stmtView->fetchColumn();

    if (!$viewId) {
        $stmtInsertView = $db->prepare("
            INSERT INTO views (uri, file_path, layout_type, menu_group, menu_title, name_file, show_in_menu, is_active, created_at)
            VALUES ('/caja/capturar', 'views/private/caja/capturar.php', 'private', 'Caja', 'Capturar Pago', 'capturar', 1, 1, NOW())
        ");
        $stmtInsertView->execute();
        $viewId = $db->lastInsertId();
        echo "Vista '/caja/capturar' creada con ID: $viewId\n";
    } else {
        $stmtUpdateView = $db->prepare("
            UPDATE views SET 
                uri = '/caja/capturar',
                file_path = 'views/private/caja/capturar.php',
                layout_type = 'private',
                menu_group = 'Caja',
                menu_title = 'Capturar Pago',
                name_file = 'capturar',
                show_in_menu = 1,
                is_active = 1
            WHERE id = :id
        ");
        $stmtUpdateView->execute([':id' => $viewId]);
        echo "Vista '/caja/capturar' actualizada con ID: $viewId\n";
    }

    echo "--- 6. Configurando permisos RBAC en role_view_permissions ---\n";
    // Rol 2 = 'caja'
    // Rol 1 = 'super_admin'
    $db->prepare("DELETE FROM role_view_permissions WHERE view_id = :view_id")->execute([':view_id' => $viewId]);

    $stmtPerm = $db->prepare("INSERT INTO role_view_permissions (role_id, view_id) VALUES (:role_id, :view_id)");
    
    // Rol 1: super_admin
    $stmtPerm->execute([':role_id' => 1, ':view_id' => $viewId]);
    // Rol 2: caja
    $stmtPerm->execute([':role_id' => 2, ':view_id' => $viewId]);
    echo "Permisos asignados exclusivamente a Caja (rol 2) y Super Admin (rol 1).\n";

    echo "--- 7. Regenerando cachés estáticas ---\n";
    MenuGroupCache::refresh();
    ViewCache::refresh();
    PermissionCache::refresh();
    echo "¡Cachés regeneradas con éxito!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
