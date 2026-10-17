<?php
namespace App\Core;

use App\Core\Controllers\PublicViewController;
use App\Core\Controllers\PrivateViewController;
use RuntimeException;

/**
 * Enrutador Principal - ASRS (Axe Secure Router System)
 * Implementa resolución estática O(1), lectura de metadatos de rutas,
 * inyección de vistas y seguridad perimetral de Nivel 1.
 */
class Router {
    private array $routes = [];

    public function __construct() {
        // 1. Asegurar que la caché de rutas exista (invocando al gestor de caché)
        CacheRoutesBuilder::checkAndGenerate();

        // 2. Cargar el mapa de rutas estático en memoria para rendimiento óptimo O(1)
        $this->routes = require __DIR__ . '/../../config/routes_cache.php';
    }

    /**
     * Resuelve la URI solicitada normalizándola para garantizar búsqueda directa O(1).
     * Soporta tanto dominios raíz como subdirectorios de desarrollo (ej. XAMPP).
     *
     * @return string
     */
    protected function resolveRequestedUri(): string {
        $rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // 1. Verificación O(1) directa en el mapa de rutas
        if (isset($this->routes[$rawUri])) {
            return $rawUri;
        }

        // 2. Normalización en caso de subdirectorios (ej. /Proyectos/Axe/public/)
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($rawUri, $scriptDir)) {
            $normalized = substr($rawUri, strlen($scriptDir));
        } else {
            $normalized = $rawUri;
        }

        $normalized = '/' . trim($normalized, '/');
        if ($normalized === '//') {
            $normalized = '/';
        }

        // Búsqueda O(1) con URI normalizada
        if (isset($this->routes[$normalized])) {
            return $normalized;
        }

        // Probar quitando trailing slash si aplica (ej. /login/ -> /login)
        $trimmed = rtrim($normalized, '/');
        if ($trimmed !== '' && isset($this->routes[$trimmed])) {
            return $trimmed;
        }

        return $rawUri;
    }

    /**
     * Despacha la petición actual según el mapa de caché.
     * Lee los parámetros asociados a la ruta: 'type', 'controller', 'method', 'layout', 'content' y 'roles'.
     * Evalúa el tipo de ruta y delega al despachador público o privado (Nivel 1).
     */
    public function dispatch(): void {
        $uri = $this->resolveRequestedUri();

        // Verificación en el mapa estático de rutas con latencia O(1)
        if (!isset($this->routes[$uri])) {
            $this->renderNotFound($uri);
            return;
        }

        // Lectura de los parámetros asociados a la ruta registrada
        $route      = $this->routes[$uri];
        $type       = $route['type'] ?? 'public';
        $controller = $route['controller'] ?? null;
        $method     = $route['method'] ?? null;
        $layout     = $route['layout'] ?? 'lyt_public';
        $content    = $route['content'] ?? 'vw_home';
        $roles      = $route['roles'] ?? [];

        // Evaluación del parámetro 'type' proveniente de la ruta registrada
        if ($type === 'public') {
            // Delegar a controlador de vistas públicas
            $dispatcher = new PublicViewController();
            $dispatcher->dispatch($route);
        } elseif ($type === 'private') {
            // Delegar a controlador especializado en vistas privadas (Seguridad Nivel 1)
            $dispatcher = new PrivateViewController();
            $dispatcher->dispatch($route);
        } else {
            throw new RuntimeException("Error en Router ASRS: Tipo de ruta desconocido '{$type}' para la URI '{$uri}'.");
        }
    }

    /**
     * Emite una respuesta HTTP 404 estilizada bajo la identidad visual de ASRS.
     *
     * @param string $uri
     */
    protected function renderNotFound(string $uri): void {
        http_response_code(404);

        $sanitizedUri = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
        $activeRoutesCount = count($this->routes);

        echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>ASRS | 404 Ruta No Encontrada</title>
    <link rel='stylesheet' href='/AXE/public/assets/core/css/layouts/lyt_public.css'>
    <style>
        .asrs-error-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 70vh;
            padding: 20px;
        }
        .asrs-error-card {
            background-color: var(--asrs-panel-bg);
            border: 1px solid rgba(217, 37, 37, 0.4);
            border-left: 5px solid var(--asrs-neon-red);
            border-radius: 8px;
            padding: 40px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 25px rgba(217, 37, 37, 0.2);
            position: relative;
        }
        .asrs-badge-404 {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(217, 37, 37, 0.15);
            border: 1px solid var(--asrs-neon-red);
            color: #ff9999;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .asrs-error-title {
            color: var(--asrs-white);
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .asrs-error-title span {
            color: var(--asrs-neon-red);
            text-shadow: 0 0 12px rgba(217, 37, 37, 0.5);
        }
        .asrs-error-desc {
            color: var(--asrs-border-silver);
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .asrs-telemetry-box {
            background: #0d1520;
            border: 1px solid rgba(212, 217, 237, 0.15);
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 30px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9rem;
        }
        .asrs-telemetry-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .asrs-telemetry-row:last-child {
            margin-bottom: 0;
        }
        .asrs-telemetry-key {
            color: var(--asrs-text-muted);
        }
        .asrs-telemetry-val {
            color: var(--asrs-neon-gold);
            font-weight: bold;
        }
        .asrs-error-actions {
            display: flex;
            gap: 15px;
        }
        .asrs-btn-home {
            background: linear-gradient(135deg, var(--asrs-neon-gold), #b88a18);
            color: #101926;
            padding: 12px 26px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            box-shadow: 0 0 15px rgba(227, 179, 49, 0.3);
            transition: all 0.2s ease;
        }
        .asrs-btn-home:hover {
            box-shadow: 0 0 20px rgba(227, 179, 49, 0.6);
            transform: translateY(-2px);
        }
        .asrs-btn-outline {
            background: transparent;
            color: var(--asrs-border-silver);
            border: 1px solid rgba(212, 217, 237, 0.3);
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .asrs-btn-outline:hover {
            color: var(--asrs-white);
            border-color: var(--asrs-neon-gold);
        }
    </style>
</head>
<body class='asrs-layout-public'>
    <header class='asrs-navbar'>
        <div class='asrs-nav-brand'>
            <span class='asrs-brand-icon'>⚡</span>
            <span class='asrs-brand-text'>ASRS <small>Framework</small></span>
        </div>
        <div class='asrs-nav-action'>
            <a href='/' class='asrs-nav-item'>Inicio</a>
        </div>
    </header>

    <main class='asrs-main-content'>
        <div class='asrs-error-container'>
            <div class='asrs-error-card'>
                <div class='asrs-badge-404'>
                    <span style='color: var(--asrs-neon-red);'>●</span> ERROR HTTP 404
                </div>
                <h1 class='asrs-error-title'>Ruta <span>No Encontrada</span></h1>
                <p class='asrs-error-desc'>
                    La URI solicitada no se encuentra registrada en el mapa estático de rutas compiladas o ha sido deshabilitada en la base de datos maestra.
                </p>

                <div class='asrs-telemetry-box'>
                    <div class='asrs-telemetry-row'>
                        <span class='asrs-telemetry-key'>URI Solicitada:</span>
                        <span class='asrs-telemetry-val'>{$sanitizedUri}</span>
                    </div>
                    <div class='asrs-telemetry-row'>
                        <span class='asrs-telemetry-key'>Método HTTP:</span>
                        <span class='asrs-telemetry-val'>" . htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET') . "</span>
                    </div>
                    <div class='asrs-telemetry-row'>
                        <span class='asrs-telemetry-key'>Latencia Hash Map:</span>
                        <span class='asrs-telemetry-val'>O(1) Fallida</span>
                    </div>
                    <div class='asrs-telemetry-row'>
                        <span class='asrs-telemetry-key'>Rutas Compiladas:</span>
                        <span class='asrs-telemetry-val'>{$activeRoutesCount} activas</span>
                    </div>
                </div>

                <div class='asrs-error-actions'>
                    <a href='/' class='asrs-btn-home'>← Volver al Inicio</a>
                    <a href='javascript:history.back()' class='asrs-btn-outline'>Página Anterior</a>
                </div>
            </div>
        </div>
    </main>

    <footer class='asrs-footer'>
        <div class='asrs-footer-container'>
            <p class='asrs-footer-copy'>&copy; " . date('Y') . " ASRS (Axe Secure Router System). Despachador de Rutas Ultra Rápido.</p>
        </div>
    </footer>
</body>
</html>";
    }
}