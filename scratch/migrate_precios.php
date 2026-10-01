<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database;
use Core\ViewCache;
use Core\MenuGroupCache;

try {
    $db = Database::getInstance();

    echo "1. Creando tabla conceptos_precios_cea...\n";
    $sqlTabla = "CREATE TABLE IF NOT EXISTS conceptos_precios_cea (
        id INT AUTO_INCREMENT PRIMARY KEY,
        concepto VARCHAR(150) NOT NULL,
        nivel ENUM('preescolar', 'primaria', 'secundaria') NOT NULL,
        monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        descripcion VARCHAR(255) NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_nivel (nivel),
        INDEX idx_activo (activo),
        UNIQUE KEY uq_concepto_nivel (concepto, nivel)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sqlTabla);
    echo "✓ Tabla conceptos_precios_cea lista.\n";

    echo "2. Insertando conceptos oficiales del CEA para cada nivel...\n";
    $conceptosOficiales = [
        // Preescolar (Acentos Ámbar)
        ['concepto' => 'Colegiatura Mensual',         'nivel' => 'preescolar', 'monto' => 2800.00, 'descripcion' => 'Colegiatura regular mensual para educación preescolar'],
        ['concepto' => 'Inscripción Anual',           'nivel' => 'preescolar', 'monto' => 3800.00, 'descripcion' => 'Inscripción y matrícula anual para ciclo escolar preescolar'],
        ['concepto' => 'Almuerzo / Comedor',          'nivel' => 'preescolar', 'monto' => 1100.00, 'descripcion' => 'Servicio nutricional de comedor escolar mensual'],
        ['concepto' => 'Libros y Material Didáctico', 'nivel' => 'preescolar', 'monto' => 2200.00, 'descripcion' => 'Paquete institucional de libros y material de estimulación'],
        ['concepto' => 'Uniforme Escolar',            'nivel' => 'preescolar', 'monto' => 1400.00, 'descripcion' => 'Kit oficial de uniforme diario y deportivo preescolar'],
        ['concepto' => 'Taller Extracurricular',      'nivel' => 'preescolar', 'monto' => 750.00,  'descripcion' => 'Actividades lúdicas, psicomotricidad y artes'],
        ['concepto' => 'Seguro Médico Escolar',       'nivel' => 'preescolar', 'monto' => 650.00,  'descripcion' => 'Póliza médica institucional contra accidentes'],
        ['concepto' => 'Constancias y Trámites',      'nivel' => 'preescolar', 'monto' => 200.00,  'descripcion' => 'Expedición de certificados y constancias oficiales'],

        // Primaria (Acentos Azul Royal)
        ['concepto' => 'Colegiatura Mensual',         'nivel' => 'primaria',   'monto' => 3500.00, 'descripcion' => 'Colegiatura regular mensual para educación primaria'],
        ['concepto' => 'Inscripción Anual',           'nivel' => 'primaria',   'monto' => 4500.00, 'descripcion' => 'Inscripción y matrícula anual para ciclo escolar primaria'],
        ['concepto' => 'Almuerzo / Comedor',          'nivel' => 'primaria',   'monto' => 1250.00, 'descripcion' => 'Servicio de comedor escolar mensual primaria'],
        ['concepto' => 'Libros y Material Didáctico', 'nivel' => 'primaria',   'monto' => 2800.00, 'descripcion' => 'Paquete oficial de libros de texto y plataformas digitales'],
        ['concepto' => 'Uniforme Escolar',            'nivel' => 'primaria',   'monto' => 1600.00, 'descripcion' => 'Kit oficial de uniforme diario y deportivo primaria'],
        ['concepto' => 'Taller Extracurricular',      'nivel' => 'primaria',   'monto' => 850.00,  'descripcion' => 'Robótica, música, deportes y ajedrez'],
        ['concepto' => 'Seguro Médico Escolar',       'nivel' => 'primaria',   'monto' => 650.00,  'descripcion' => 'Póliza médica institucional contra accidentes'],
        ['concepto' => 'Constancias y Trámites',      'nivel' => 'primaria',   'monto' => 250.00,  'descripcion' => 'Expedición de certificados, credenciales y constancias'],

        // Secundaria (Acentos Rojo Quemado)
        ['concepto' => 'Colegiatura Mensual',         'nivel' => 'secundaria', 'monto' => 4200.00, 'descripcion' => 'Colegiatura regular mensual para educación secundaria'],
        ['concepto' => 'Inscripción Anual',           'nivel' => 'secundaria', 'monto' => 5200.00, 'descripcion' => 'Inscripción y matrícula anual para ciclo escolar secundaria'],
        ['concepto' => 'Almuerzo / Comedor',          'nivel' => 'secundaria', 'monto' => 1350.00, 'descripcion' => 'Servicio de comedor escolar mensual secundaria'],
        ['concepto' => 'Libros y Material Didáctico', 'nivel' => 'secundaria', 'monto' => 3400.00, 'descripcion' => 'Paquete oficial de libros especializados y laboratorios'],
        ['concepto' => 'Uniforme Escolar',            'nivel' => 'secundaria', 'monto' => 1750.00, 'descripcion' => 'Kit oficial de uniforme diario y deportivo secundaria'],
        ['concepto' => 'Taller Extracurricular',      'nivel' => 'secundaria', 'monto' => 950.00,  'descripcion' => 'Club de ciencias, artes escénicas y ligas deportivas'],
        ['concepto' => 'Seguro Médico Escolar',       'nivel' => 'secundaria', 'monto' => 650.00,  'descripcion' => 'Póliza médica institucional contra accidentes'],
        ['concepto' => 'Constancias y Trámites',      'nivel' => 'secundaria', 'monto' => 300.00,  'descripcion' => 'Certificaciones académicas, boletas y trámites SEP'],
    ];

    $stmtInsert = $db->prepare("INSERT INTO conceptos_precios_cea (concepto, nivel, monto, descripcion, activo)
        VALUES (:concepto, :nivel, :monto, :descripcion, 1)
        ON DUPLICATE KEY UPDATE 
            descripcion = VALUES(descripcion),
            activo = 1");

    foreach ($conceptosOficiales as $c) {
        $stmtInsert->execute([
            ':concepto'    => $c['concepto'],
            ':nivel'       => $c['nivel'],
            ':monto'       => $c['monto'],
            ':descripcion' => $c['descripcion'],
        ]);
    }
    echo "✓ Tarifas oficiales por nivel insertadas/actualizadas correctamente.\n";

    echo "3. Configurando grupo de menú 'Precios'...\n";
    $stmtGroup = $db->prepare("SELECT id FROM menu_groups WHERE name = 'Precios' LIMIT 1");
    $stmtGroup->execute();
    $groupId = $stmtGroup->fetchColumn();

    if (!$groupId) {
        $db->prepare("INSERT INTO menu_groups (name, slug, display_order, icon, description, is_active)
            VALUES ('Precios', 'precios', 5, 'tag', 'Gestión de tarifas y precios oficiales por nivel educativo', 1)")->execute();
        $groupId = $db->lastInsertId();
        echo "✓ Grupo 'Precios' creado con ID {$groupId}.\n";
    } else {
        echo "✓ Grupo 'Precios' ya existe con ID {$groupId}.\n";
    }

    echo "4. Registrando vista en tabla 'views'...\n";
    $stmtView = $db->prepare("SELECT id FROM views WHERE uri = '/precios' LIMIT 1");
    $stmtView->execute();
    $viewId = $stmtView->fetchColumn();

    if (!$viewId) {
        $db->prepare("INSERT INTO views (uri, file_path, layout_type, menu_group, menu_title, name_file, show_in_menu, is_active)
            VALUES ('/precios', 'views/private/precios/index.php', 'private', 'Precios', 'Precios', 'index', 1, 1)")->execute();
        $viewId = $db->lastInsertId();
        echo "✓ Vista '/precios' creada con ID {$viewId}.\n";
    } else {
        $db->prepare("UPDATE views SET 
            file_path = 'views/private/precios/index.php',
            layout_type = 'private',
            menu_group = 'Precios',
            menu_title = 'Precios',
            name_file = 'index',
            show_in_menu = 1,
            is_active = 1
            WHERE id = :id")->execute([':id' => $viewId]);
        echo "✓ Vista '/precios' actualizada con ID {$viewId}.\n";
    }

    echo "5. Asignando permisos RBAC (Exclusivo Super Admin / Rol 1)...\n";
    // Limpiar permisos previos de esta vista
    $db->prepare("DELETE FROM role_view_permissions WHERE view_id = :view_id")->execute([':view_id' => $viewId]);
    $db->prepare("DELETE FROM role_views WHERE view_id = :view_id")->execute([':view_id' => $viewId]);

    // Asignar exclusivamente a role_id = 1 (super_admin)
    $db->prepare("INSERT IGNORE INTO role_view_permissions (role_id, view_id) VALUES (1, :view_id)")->execute([':view_id' => $viewId]);
    $db->prepare("INSERT IGNORE INTO role_views (role_id, view_id) VALUES (1, :view_id)")->execute([':view_id' => $viewId]);
    echo "✓ Permisos RBAC vinculados exclusivamente al rol Super Admin (ID 1).\n";

    echo "6. Regenerando archivos de caché de vistas y menús...\n";
    ViewCache::generate();
    MenuGroupCache::generate();
    echo "✓ Caché regenerada exitosamente.\n";

    echo "\n=== MIGRACIÓN Y CONFIGURACIÓN COMPLETADA CON ÉXITO ===\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
