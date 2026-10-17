<?php
/**
 * Archivo generado automáticamente por ASRS
 * Fecha de compilación: 2026-10-17 07:14:27
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
            'css' => '/AXE/public/assets/core/css/home.css',
            'js'  => '/AXE/public/assets/core/js/home.js'
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
            'css' => '/AXE/public/assets/core/css/auth.css',
            'js'  => '/AXE/public/assets/core/js/auth.js'
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
            'css' => '/AXE/public/assets/core/css/dashboard.css',
            'js'  => '/AXE/public/assets/core/js/dashboard.js'
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
            'css' => '/AXE/public/assets/segi/css/alumnos.css',
            'js'  => '/AXE/public/assets/segi/js/alumnos.js'
        ]
    ],
];
