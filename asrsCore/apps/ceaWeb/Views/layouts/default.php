<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro Educativo América | CEA</title>
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="assets/css/layouts/default.css">
    <!-- Google Fonts: Inter & Playfair Display -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header Fijo (Sticky Header) -->
    <header class="cea-header" id="ceaHeader">
        <div class="cea-header-container">
            <!-- Espacio para el Logotipo -->
            <a href="#" class="cea-logo">
                <img src="assets/images/logo-cea.png" alt="Logotipo Centro Educativo América" id="logoImg">
            </a>

            <!-- Menú de Navegación -->
            <nav class="cea-nav" id="ceaNav">
                <ul class="cea-nav-list">
                    <li><a href="#inicio" class="active">Inicio</a></li>
                    <li><a href="#nosotros">Nosotros</a></li>
                    <li><a href="#oferta">Oferta Educativa</a></li>
                    <li><a href="#care">C.A.R.E.</a></li>
                    <li><a href="#vida-escolar">Vida Escolar</a></li>
                    <li><a href="#admisiones">Admisiones</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                </ul>
            </nav>

            <!-- Botón de Acceso / CTA -->
            <div class="cea-actions">
                <a href="#portal" class="cea-btn-access"><i class="fa-solid fa-user-lock"></i> Acceder</a>
                <button class="cea-menu-toggle" id="menuToggle" aria-label="Abrir menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Contenido Principal (Slot para las vistas) -->
    <main class="cea-main-content">
        <!-- Aquí se inyectará el contenido dinámico de cada vista -->
        <section style="padding: 100px 20px; text-align: center; min-height: 60vh;">
            <h1>Educación, Formación y Desarrollo</h1>
            <p>Contenido principal de la vista en curso...</p>
        </section>
    </main>

    <!-- Footer Institucional -->
    <footer class="cea-footer">
        <div class="cea-footer-container">
            <div class="cea-footer-col">
                <img src="assets/images/logo-cea-white.png" alt="CEA Blanco" class="footer-logo">
                <p>Formando líderes con pensamiento crítico y calidez humana en Mérida, Yucatán.</p>
            </div>
            <div class="cea-footer-col">
                <h4>Enlaces Rápidos</h4>
                <ul>
                    <li><a href="#nosotros">Nuestra Filosofía</a></li>
                    <li><a href="#oferta">Niveles Educativos</a></li>
                    <li><a href="#admisiones">Proceso de Admisión</a></li>
                    <li><a href="#portal">Portal para Padres</a></li>
                </ul>
            </div>
            <div class="cea-footer-col">
                <h4>Contacto</h4>
                <p><i class="fa-solid fa-location-dot"></i> Mérida, Yucatán, México</p>
                <p><i class="fa-solid fa-phone"></i> (999) 000-0000</p>
                <div class="cea-socials">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Leyenda profesional obligatoria -->
        <div class="cea-footer-bottom">
            <p>&copy; 2026 Centro Educativo América. Todos los derechos reservados. Diseñado y desarrollado por Coffee Flavored Marketing.</p>
        </div>
    </footer>

    <!-- Script JS -->
    <script src="assets/js/layout.js"></script>
</body>
</html>