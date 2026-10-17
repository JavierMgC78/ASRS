<?php
/**
 * Archivo generado automáticamente por ASRS
 * Fecha de compilación: 2026-10-17 23:19:27
 */

return [
    '/' => [
        'type'       => 'public',
        'controller' => 'App\Core\Controllers\HomeController',
        'method'     => 'index',
        'layout'     => 'lyt_public',
        'content'    => 'vw_home',
        'roles'      => array (
),
        'assets'     => [
            'css' => '/assets/core/css/views_content/vw_home.css',
            'js'  => '/assets/core/js/views_content/vw_home.js'
        ]
    ],
    '/login' => [
        'type'       => 'public',
        'controller' => 'App\Core\Controllers\AuthController',
        'method'     => 'showLogin',
        'layout'     => 'lyt_public',
        'content'    => 'vw_login',
        'roles'      => array (
),
        'assets'     => [
            'css' => '/AXE/public/assets/core/css/views_content/vw_login.css',
            'js'  => '/AXE/public/assets/core/js/views_content/vw_login.js'
        ]
    ],
    '/admin/dashboard' => [
        'type'       => 'private',
        'controller' => 'App\Core\Controllers\AdminController',
        'method'     => 'index',
        'layout'     => 'lytPr_admin',
        'content'    => 'vw_dashboard',
        'roles'      => array (
  0 => 'admin',
),
        'assets'     => [
            'css' => '/AXE/public/assets/core/css/views_content/vw_dashboard.css',
            'js'  => '/AXE/public/assets/core/js/views_content/vw_dashboard.js'
        ]
    ],
    '/segi/alumnos' => [
        'type'       => 'private',
        'controller' => 'App\Segi\Controllers\AlumnoController',
        'method'     => 'listar',
        'layout'     => 'lytPr_segi',
        'content'    => 'vw_alumnos',
        'roles'      => array (
  0 => 'admin',
  1 => 'teacher',
),
        'assets'     => [
            'css' => '/AXE/public/assets/segi/css/vw_alumnos.css',
            'js'  => '/AXE/public/assets/segi/js/vw_alumnos.js'
        ]
    ],
];
