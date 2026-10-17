<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function __construct() {
        // 1. Asegurar que la caché de rutas exista (invocando al gestor de caché)
        CacheRoutesBuilder::checkAndGenerate();

        // 2. Cargar el mapa de rutas estático en memoria para rendimiento óptimo
        $this->routes = require __DIR__ . '/../../config/routes_cache.php';
    }

    public function dispatch(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        echo "<div style='font-family: Arial, sans-serif; padding: 20px; color: #101926;'>";
        echo "<h3 style='color: #2b7a78;'>Router de ASRS Operando con Éxito</h3>";
        echo "<p>Rutas activas en memoria: <strong>" . count($this->routes) . "</strong></p>";
        echo "</div>";
    }
}