-- =====================================================================
-- ASRS Framework - Módulo de Gestión de Grupos de Menú
-- Archivo: Core/base_instalation/menu_groups.sql
-- =====================================================================

-- 1. Creación de la tabla `menu_groups`
CREATE TABLE IF NOT EXISTS `menu_groups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Nombre legible del grupo de menú (ej. Administración)',
  `slug` VARCHAR(100) NOT NULL COMMENT 'Identificador único y normalizado para URLs o carpetas',
  `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Orden de visualización secuencial en el sidebar',
  `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Nombre de icono opcional (SVG o nombre de clase)',
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'Descripción breve del módulo o sección',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Activo y visible en navegación, 0 = Inactivo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_menu_groups_name` (`name`),
  UNIQUE KEY `uk_menu_groups_slug` (`slug`),
  KEY `idx_menu_groups_order` (`display_order`),
  KEY `idx_menu_groups_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Catálogo y ordenamiento de grupos para navegación lateral';

-- 2. Población inicial con los grupos del sistema ASRS
INSERT INTO `menu_groups` (`id`, `name`, `slug`, `display_order`, `description`, `is_active`) VALUES
(1, 'Principal', 'principal', 1, 'Módulo principal y panel de control (Dashboard)', 1),
(2, 'Administración', 'administracion', 2, 'Gestión administrativa, usuarios y módulos del sistema', 1),
(3, 'Vistas', 'vistas', 3, 'Configuración y registro de vistas enrutadas', 1),
(4, 'Roles', 'roles', 4, 'Gestión de roles y permisos de acceso RBAC', 1),
(5, 'Grupos', 'grupos', 5, 'Gestión y ordenamiento de grupos de navegación', 1),
(6, 'General', 'general', 6, 'Vistas públicas y de acceso general a la plataforma', 1)
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `slug` = VALUES(`slug`),
  `display_order` = VALUES(`display_order`),
  `is_active` = VALUES(`is_active`);

-- 3. Registro de la vista de administración de Grupos de Menú en la tabla `views`
INSERT INTO `views` (`uri`, `file_path`, `layout_type`, `menu_group`, `menu_title`, `name_file`, `show_in_menu`, `is_active`)
SELECT '/grupos/ver', 'views/private/grupos/ver.php', 'private', 'Grupos', 'Gestión de Grupos', 'ver', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM `views` WHERE `uri` = '/grupos/ver' OR `uri` = '/admin/grupos/ver'
);

-- 4. Asignar permiso de la vista creada al rol super_admin (role_id = 1)
INSERT IGNORE INTO `role_view_permissions` (`role_id`, `view_id`)
SELECT 1, `id` FROM `views` WHERE `uri` = '/grupos/ver' LIMIT 1;
