<div class="contact-container">
    <section class="contact-header">
        <h1>Ponte en Contacto</h1>
        <p class="subtitle">Estamos listos para transformar tus ideas en soluciones tecnológicas de alto rendimiento.</p>
    </section>

    <div class="contact-grid">
        <!-- Información de Contacto (Mock / Provisional) -->
        <div class="contact-info">
            <h3>Información de la Agencia</h3>
            <p>¿Tienes un proyecto en mente o necesitas una arquitectura a la medida? Escríbenos y conversemos sobre cómo Coffee Flavored Software puede ayudarte.</p>
            
            <ul class="info-list">
                <li>
                    <strong>Ubicación:</strong> 
                    <span>Mérida, Yucatán, México</span>
                </li>
                <li>
                    <strong>Correo Electrónico:</strong> 
                    <span>contacto@coffeeflavoredsoftware.test</span>
                </li>
                <li>
                    <strong>Horario de Atención:</strong> 
                    <span>Lunes a Viernes, 9:00 AM - 6:00 PM</span>
                </li>
            </ul>
        </div>

        <!-- Formulario de Contacto Provisional -->
        <div class="contact-form-wrapper">
            <form class="contact-form" onsubmit="event.preventDefault(); alert('Mensaje simulado enviado con éxito.');">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" required>
                </div>

                <div class="form-group">
                    <label for="mensaje">Mensaje</label>
                    <textarea id="mensaje" name="mensaje" rows="4" placeholder="Cuéntanos sobre tu proyecto..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">Enviar Mensaje</button>
            </form>
        </div>
    </div>
</div>