<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    // Constructor privado para evitar instanciación directa (Patrón Singleton)
    private function __construct()
    {
    }

    // Prevenir clonación del objeto
    private function __clone()
    {
    }

    // Método estático para obtener la instancia única de PDO
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = 'localhost';
            $db = 'asrs_db'; // Reemplaza con el nombre real de tu BD
            $user = 'root';       // Reemplaza con tu usuario
            $pass = '';    // Reemplaza con tu contraseña
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Seguridad nativa crítica para ASRS
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // En producción puedes manejar un log o mensaje amigable
                throw new PDOException("Error de conexión: " . $e->getMessage(), (int) $e->getCode());
            }
        }

        return self::$instance;
    }
}