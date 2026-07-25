# Axe Security Router System (ASRS)

Axe es un framework ligero y seguro de PHP diseñado con un enfoque híbrido de configuración dinámica y rendimiento estático. Su característica principal es un motor de enrutamiento y gestión de accesos controlado íntegramente desde una interfaz de administración, pero ejecutado a nivel de núcleo mediante cachés estáticos hiper-optimizados.

## Características Principales

*   **Enrutamiento Híbrido (Dynamic to Static):** Las rutas se gestionan en una base de datos (CRUD completo desde el panel), pero el motor de la aplicación lee un archivo de caché estático (`config/rutas_cache.php`) autogenerado para garantizar tiempos de respuesta mínimos sin sobrecargar la base de datos.
*   **Gestión de Acceso Basado en Niveles (RBAC):** Los usuarios se rigen por un `nivel_acceso` numérico (ej: 0 Público, 10 Usuario, 100 Administrador). Las rutas y los roles se sincronizan mediante caché estática (`config/roles.php`).
*   **Sistema de Plantillas Integrado:** Soporte nativo para layouts. Las vistas se agrupan de forma automática en:
    *   **Rutas Públicas:** Emplean plantillas ubicadas en `templates/public/` (sin requerir sesión).
        *   **Estructura del Layout Público (`layout_public.php`)**:
            *   **Secciones Integradas**: Incluye un bloque `<head>` estándar para cargar tipografías (Google Fonts) e inyectar dinámicamente hojas de estilo extra (`$css_vista`). Consta de un `<header class="pub-header">`, un `<main class="pub-main">` y un `<footer class="pub-footer">`.
            *   **Navegación Dinámica**: La barra de navegación se genera dinámicamente consultando las vistas que no requieren login en la tabla `rutas` a través del controlador `LayoutPublicController`.
            *   **Inyección de Contenido**: Todo el HTML generado por la vista solicitada se inyecta centralizadamente utilizando la variable `$contenido_vista`.
            *   **Esquema Base**: A continuación, se presenta una versión simplificada del esqueleto HTML/PHP del layout, mostrando cómo se recibe e itera la variable `$opciones_publicas` para construir la navegación dinámicamente:
            
                ```php
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <title>Axe Framework</title>
                    <!-- Inyección de CSS de la vista -->
                    <?php if (!empty($css_vista)) {
                        foreach ($css_vista as $css) echo "<link rel='stylesheet' href='$css'>\n";
                    } ?>
                </head>
                <body>
                    <header class="pub-header">
                        <nav class="pub-nav">
                            <a href="/" class="pub-brand">Marca</a>
                            
                            <!-- Renderizado dinámico de la variable $opciones_publicas -->
                            <ul class="pub-nav-links">
                                <?php foreach ($opciones_publicas as $opcion) : ?>
                                    <li>
                                        <a href="<?= htmlspecialchars($opcion['uri']) ?>">
                                            <?= htmlspecialchars($opcion['titulo']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <a href="/login" class="btn-acceso">Ingresar</a>
                        </nav>
                    </header>

                    <!-- Inyección centralizada de la vista solicitada -->
                    <main class="pub-main">
                        <?= $contenido_vista ?? '' ?>
                    </main>

                    <footer class="pub-footer">Pie de página</footer>

                    <!-- Inyección de JS de la vista -->
                    <?php if (!empty($js_vista)) {
                        foreach ($js_vista as $js) echo "<script src='$js'></script>\n";
                    } ?>
                </body>
                </html>
                ```
    *   **Rutas Privadas:** Emplean plantillas en `templates/` (protegidas bajo el nivel de acceso configurado).
*   **Seguridad por Defecto:** 
    *   Protección CSRF nativa en todas las peticiones POST.
    *   Consultas a Base de Datos siempre preparadas usando PDO.
    *   Sistema de Auditoría interna (`Auditoria.php`) que registra eventos críticos del sistema en base de datos.
    *   Mitigación de ataques de Path Traversal al gestionar archivos físicos.

## Estructura de Directorios

El framework sigue un patrón MVC adaptado y estructurado de la siguiente forma:

```text
Axe/
├── config/         # Archivos de caché autogenerados (rutas, roles) y config de DB
├── controllers/    # Controladores de lógica de negocio y backend de paneles
├── core/           # Núcleo del framework (Enrutador, Seguridad, Auditoría)
├── database/       # Scripts y respaldos de estructura de la BD (Axe DB)
├── public/         # Document Root (index.php) y Assets (CSS, JS, Imágenes)
├── storage/        # Almacenamiento de archivos locales
├── templates/      # Layouts principales de la interfaz (privados y públicos)
└── views/          # Código HTML/PHP específico de cada página y panel
```

## Requisitos

*   Servidor Web (Apache/Nginx)
*   PHP 8.1 o superior
*   Base de Datos MySQL/MariaDB

## Instalación Básica

1.  Clonar o copiar los archivos en el Document Root del servidor (o configurar un Virtual Host apuntando a la carpeta `/public`).
2.  Importar la base de datos `axe_db` que contiene las tablas centrales (`usuarios`, `rutas`, `roles`, `auditoria`).
3.  Renombrar o configurar credenciales en `config.php` y `config/database.php` con las credenciales de tu base de datos MySQL.
4.  Iniciar sesión en el sistema usando una cuenta de Administrador (Nivel 100) para acceder al Gestor de Rutas y Gestor de Roles.

## Flujo de Trabajo (Gestor de Rutas)

Para añadir una nueva página al sistema:
1. Crea el archivo de la vista física en el directorio `/views`.
2. Opcionalmente, crea su controlador en `/controllers`.
3. Ingresa al panel **Gestor de Rutas** en la aplicación web.
4. Registra la nueva URI, selecciona la vista, asigna el controlador, define el nivel de acceso mínimo y selecciona la plantilla visual.
5. Al guardar, el sistema actualizará la base de datos y automáticamente compilará la caché para activar la ruta instantáneamente en producción.

---

## Proceso de Renderizado de Vistas

Axe Framework utiliza un patrón de inyección de contenido mediante *Output Buffering* (almacenamiento en búfer de salida) que separa claramente la lógica, la vista y el layout. El ciclo de vida de renderizado ocurre íntegramente en `public/index.php`:

1.  **Ruteo y Resolución:** El núcleo recibe la petición, identifica la URI en la caché (`rutas_cache.php`) y obtiene las rutas físicas de la vista, la plantilla y el controlador (si existe).
2.  **Ejecución del Controlador (Opcional):** Si la ruta tiene un controlador asignado, este se ejecuta primero. El controlador maneja la lógica de negocio y puede pasar variables a la vista mediante un arreglo asociativo llamado `$datos_vista`. El enrutador extrae estas variables (`extract()`) para que sean accesibles en la vista.
3.  **Captura en Búfer (Output Buffering):** Se inicia la captura de la salida con `ob_start()`. Luego se ejecuta el archivo físico de la vista (`require $ruta_vista`). Todo el HTML generado y procesado se queda en la memoria.
4.  **Almacenamiento de Contenido:** El HTML capturado se guarda en la variable global `$contenido_vista` y se limpia el búfer utilizando `ob_get_clean()`.
5.  **Inyección en la Plantilla Global:** Finalmente, se carga el archivo del layout o plantilla maestra (`require $ruta_plantilla`). Esta plantilla se encarga de imprimir `$contenido_vista` dentro de la etiqueta `<main>` o el contenedor designado. Además, la plantilla recibe variables adicionales inyectadas como `$css_vista` y `$js_vista` (definidas en la tabla rutas) para cargar scripts y estilos específicos de forma dinámica.

---

## Estructura de Base de Datos (`axe_db`)

El sistema depende de 6 tablas centrales para gestionar el núcleo del framework:

1. **`rutas`**: Almacena el inventario de URIs habilitadas, apuntando hacia su archivo `vista`, `controlador` (opcional), `plantilla` (layout) y el `nivel_minimo` necesario para acceder. Es la fuente de verdad que alimenta la caché estática de rutas.
2. **`roles`**: Define la lista de los niveles de acceso existentes (RBAC). Utiliza un sistema numérico como llave primaria (Ej. `0` = Público General, `100` = Administrador). Alimenta la caché estática de roles.
3. **`usuarios`**: Gestiona las credenciales de acceso al sistema, conteniendo el `email`, la contraseña en formato `password_hash`, y el `nivel_acceso` (que actúa como llave foránea lógica hacia la tabla roles).
4. **`auth_tokens`**: Tabla de seguridad que almacena los selectores y validadores hash criptográficos para el sistema de "Recordar Sesión" de manera segura.
5. **`bitacora_auditoria`**: Guarda un registro en JSON inmutable (`detalles`) y la `ip_origen` para todas las acciones sensibles o administrativas (ej. modificar una ruta o rol) asociadas al `usuario_id` que las ejecutó.
6. **`login_attempts`**: Rastrea los intentos de inicio de sesión exitosos o fallidos vinculados a una dirección `ip` y fecha, formando la base para el mecanismo de protección contra fuerza bruta y rate-limiting.

---
*Documentación generada para Axe Framework.*
