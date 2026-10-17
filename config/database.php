<?php
/**
 * Configuración de Base de Datos - ASRS (Axe Secure Router System)
 * Entorno de desarrollo: XAMPP
 */

return [
    'host'    => '127.0.0.1',
    'dbname'  => 'asrs_db',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];