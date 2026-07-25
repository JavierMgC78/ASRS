<?php
/**
 * Controlador estático para el layout público.
 * Su propósito es extraer dinámicamente las rutas públicas desde la caché
 * para renderizar el menú de navegación principal.
 *
 * Principio: toda la lógica de filtrado ocurre aquí; la vista solo renderiza.
 */
class LayoutPublicController {
    
    /**
     * Extrae el menú público desde el archivo de caché `rutas_cache.php`.
     * Considera como opción de menú aquellas rutas que:
     *   - No requieren login (`requiere_login === false`)
     *   - Tienen un `nombre_opcion` definido (no vacío)
     *   - No son rutas excluidas (/login, /logout)
     *
     * @return array Array de arrays asociativos con claves 'uri' y 'titulo'
     */
    public static function obtenerMenu(): array {
        $archivo_cache = __DIR__ . '/../config/rutas_cache.php';

        if (!file_exists($archivo_cache)) {
            error_log('LayoutPublicController [obtenerMenu]: archivo de caché no encontrado.');
            return [];
        }

        $rutas = require $archivo_cache;

        // URIs que no deben aparecer en el nav aunque sean públicas:
        // - /login  → tiene su propio botón dedicado en el header
        // - /logout → solo debe ser accesible desde el dashboard (rutas privadas)
        $excluidas = ['/login', '/logout'];

        $opciones_publicas = [];

        foreach ($rutas as $uri => $ruta) {
            if ($ruta['requiere_login'] === false
                && !empty($ruta['nombre_opcion'])
                && !in_array($uri, $excluidas, true)
            ) {
                $opciones_publicas[] = [
                    'uri'    => $uri,
                    'titulo' => ucfirst($ruta['nombre_opcion']),
                ];
            }
        }

        return $opciones_publicas;
    }
}
