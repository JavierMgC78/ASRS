<?php

/**
 * core/Compilador.php
 *
 * COMPILADOR DE CACHÉ DE RUTAS — AXE FRAMEWORK
 * ─────────────────────────────────────────────────────────────────────────────
 * Responsabilidad única: leer los registros de rutas desde la base de datos
 * y regenerar de forma atómica el archivo estático config/rutas_cache.php.
 *
 * Este compilador actúa como la "fuente de escritura" del sistema de
 * enrutamiento. Debe invocarse manualmente (o desde un panel de admin)
 * cada vez que se modifiquen las rutas en la base de datos.
 *
 * Nunca es llamado en el ciclo normal de un request del usuario final.
 * ─────────────────────────────────────────────────────────────────────────────
 */

class Compilador
{
    /**
     * Extrae todas las rutas de la base de datos y regenera el archivo
     * config/rutas_cache.php de forma atómica mediante escritura en archivo
     * temporal + rename(), garantizando que en ningún momento el archivo
     * de caché quede en un estado inconsistente o corrupto.
     *
     * @param  PDO  $pdo  Instancia de conexión a la base de datos.
     * @return void
     * @throws RuntimeException  Si la escritura o el renombrado del archivo fallan.
     */
    public static function actualizarRutas(PDO $pdo): void
    {
        // ── 1. Extracción de datos ────────────────────────────────────────────
        $stmt = $pdo->query('SELECT * FROM rutas');
        $filas = $stmt->fetchAll();

        // ── 2. Estructuración del arreglo de rutas ────────────────────────────
        $rutas = [];

        foreach ($filas as $fila) {
            // Las columnas 'css' y 'js' se almacenan como JSON en la BD.
            // Se decodifican a arreglos nativos de PHP; si son nulos o
            // cadenas vacías, se asigna un arreglo vacío como valor seguro.
            $css = !empty($fila['css']) ? json_decode($fila['css'], true) : [];
            $js  = !empty($fila['js'])  ? json_decode($fila['js'],  true) : [];

            // La URI actúa como clave principal del arreglo.
            $rutas[$fila['uri']] = [
                'uri'            => $fila['uri'],
                'nombre_opcion'  => $fila['nombre_opcion'],
                'vista'          => $fila['vista'],
                'plantilla'      => $fila['plantilla'],
                'controlador'    => $fila['controlador'],
                'requiere_login' => (bool) $fila['requiere_login'],
                'nivel_minimo'   => (int) $fila['nivel_minimo'],
                'css'            => $css ?? [],
                'js'             => $js  ?? [],
            ];
        }

        // ── 3. Generación del código PHP exportable ───────────────────────────
        // var_export() produce una representación válida de PHP del arreglo,
        // lista para ser incluida directamente como archivo de configuración.
        $contenidoPhp = '<?php return ' . var_export($rutas, true) . ';';

        // ── 4. Escritura atómica (temp → rename) ──────────────────────────────
        // Se escribe primero en un archivo temporal para evitar que una
        // interrupción a mitad de escritura deje el caché en estado inválido.
        $rutaTemp  = __DIR__ . '/../config/temp_rutas.php';
        $rutaCache = __DIR__ . '/../config/rutas_cache.php';

        $escritura = file_put_contents($rutaTemp, $contenidoPhp);

        if ($escritura === false) {
            throw new RuntimeException(
                'Compilador: No se pudo escribir el archivo temporal en ' . $rutaTemp
            );
        }

        // rename() es una operación atómica en la mayoría de sistemas de
        // archivos: el archivo de caché se reemplaza en un único paso.
        $renombrado = rename($rutaTemp, $rutaCache);

        if ($renombrado === false) {
            throw new RuntimeException(
                'Compilador: No se pudo reemplazar el archivo de caché en ' . $rutaCache
            );
        }
    }
}
