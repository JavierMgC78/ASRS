-- ==============================================================================
-- Axe Framework - Esquema Base Oficial (Unificado)
-- Motor InnoDB | Codificación utf8mb4
-- ==============================================================================

-- 1. Tabla de Usuarios (Identidades y Control de Acceso)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_acceso` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Tokens (Arquitectura Split Token)
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `selector` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validador_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiracion` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_selector` (`selector`),
  CONSTRAINT `fk_usuario_token` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Rutas (Motor del Front Controller)
CREATE TABLE IF NOT EXISTS `rutas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_opcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vista` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plantilla` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `controlador` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requiere_login` tinyint(1) NOT NULL DEFAULT 0,
  `nivel_minimo` int(11) NOT NULL DEFAULT 0,
  `css` json DEFAULT NULL,
  `js` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uri_unique` (`uri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Defensa contra Fuerza Bruta (Rate Limiting - FIX A3)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL COMMENT 'IPv4 o IPv6 del cliente',
  `exitoso` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = login exitoso, 0 = fallo',
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ip_fecha` (`ip`, `creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de intentos de autenticación para rate limiting (FIX A3)';

-- 5. Bitácora de Auditoría Forense
CREATE TABLE IF NOT EXISTS `bitacora_auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `evento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recurso` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_origen` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalles` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla de Roles (Catálogo de Niveles de Acceso)
CREATE TABLE IF NOT EXISTS `roles` (
  `nivel`       int(11)      NOT NULL,
  `nombre`      varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo canónico de niveles de acceso del sistema.';

-- ==============================================================================
-- Inserción de Datos Estructurales (Roles Base del Sistema)
-- ==============================================================================
INSERT IGNORE INTO `roles` (`nivel`, `nombre`, `descripcion`) VALUES
(0,   'Estándar / Alumnos',  'Usuarios con acceso básico de solo lectura. Nivel de entrada al sistema.'),
(10,  'Operativo / Docentes', 'Usuarios operativos con permisos para gestionar contenido y registros.'),
(50,  'Gerencial',            'Usuarios con acceso ampliado para supervisión y reportes avanzados.'),
(100, 'Super Admin',          'Acceso total al sistema. Gestión de usuarios, roles, rutas y configuración.');

-- ==============================================================================
-- Inserción de Datos Estructurales (Rutas del Core)
-- ==============================================================================
INSERT IGNORE INTO `rutas` (`uri`, `nombre_opcion`, `vista`, `plantilla`, `controlador`, `requiere_login`, `nivel_minimo`, `css`, `js`) VALUES
('/', '', 'views/public/home_public.php', 'templates/public/layout_public.php', 'controllers/HomeController.php', 0, 0, NULL, NULL),
('/contacto', 'contacto', 'views/contacto.php', 'templates/default.php', NULL, 0, 0, NULL, NULL),
('/login', 'login', 'views/login.php', 'templates/default.php', 'controllers/LoginController.php', 0, 0, NULL, NULL),
('/nosotros', 'nosotros', 'views/public/nosotros_public.php', 'templates/public/layout_public.php', NULL, 0, 0, '["/assets/css/public/nosotrosPublic.css"]', '["/assets/js/public/nosotrosPublic.js"]'),
('/dashboard', 'dashboard', 'views/private/dashboard_private.php', 'templates/private/layout_private.php', 'controllers/DashboardController.php', 1, 100, NULL, NULL),
('/logout', 'logout', 'views/home.php', 'templates/default.php', 'controllers/LogoutController.php', 0, 0, NULL, NULL),
('/acerca-de', 'acerca-de', 'views/public/acerca-de_public.php', 'templates/public/layout_public.php', NULL, 0, 50, '["/assets/css/acerca-de.css"]', '["/assets/js/acerca-de.js"]'),
('/gestor-usuarios', 'gestor-usuarios', 'views/private/gestor-usuarios_private.php', 'templates/private/layout_private.php', 'controllers/UsuariosController.php', 1, 100, '["/assets/css/usuarios.css"]', '["/assets/js/usuarios.js"]'),
('/bitacora-auditoria', 'bitacora-auditoria', 'views/private/bitacora-auditoria_private.php', 'templates/private/layout_private.php', 'controllers/AuditoriaController.php', 1, 100, '["/assets/css/auditoria.css"]', '["/assets/js/auditoria.js"]'),
('/gestor-rutas', 'gestor-rutas', 'views/private/gestor-rutas_private.php', 'templates/private/layout_private.php', 'controllers/GestorRutasController.php', 1, 100, NULL, NULL),
('/editor-vistas', 'editor-vistas', 'views/editor-vistas.php', 'templates/layoutAdmin.php', 'controllers/EditorVistasController.php', 1, 100, NULL, NULL),
('/gestor-roles', 'gestor-roles', 'views/private/gestor-roles_private.php', 'templates/private/layout_private.php', 'controllers/GestorRolesController.php', 1, 100, NULL, NULL);