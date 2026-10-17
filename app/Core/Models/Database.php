<?php
namespace App\Core\Models;

use PDO;
use PDOException;

class Database {
    // Instancia única de la conexión PDO
    private static ?PDO $instance = null;

    /**
     * Constructor privado para evitar la instanciación directa desde fuera (Singleton).
     */
    private function __construct() {}

    /**
     * Previene la clonación del objeto.
     */
    private function __clone() {}

    /**
     * Previene la deserialización del objeto.
     */
    public function __wakeup() {
        throw new \Exception("No se permite deserializar la instancia de Base de Datos.");
    }

    /**
     * Obtiene la instancia única de la conexión a la base de datos (asrs_db).
     * 
     * @return PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            // Parámetros de conexión para entorno XAMPP
            $host     = '127.0.0.1';
            $dbname   = 'asrs_db';
            $username = 'root';
            $password = '';
            $charset  = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retornar arrays asociativos por defecto
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Usar prepared statements nativos reales
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // En producción puedes registrar el error en un log en lugar de mostrarlo directamente
                throw new PDOException("Error crítico de conexión a la base de datos: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}