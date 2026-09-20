<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h2>Acceso Privado</h2>
            <p>Coffee Flavored Software — Panel de Administración</p>
        </div>

        <form class="login-form" action="/admin/auth" method="POST">
            <div class="form-group">
                <label for="username">Usuario o Correo</label>
                <input type="text" id="username" name="username" placeholder="Ingresa tu usuario" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login-submit">Ingresar al Sistema</button>
        </form>

        <div class="login-footer-links">
            <a href="/">← Volver al sitio web</a>
        </div>
    </div>
</div>