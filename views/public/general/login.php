<?php
// El controlador ya fue ejecutado por Router::dispatch() antes del output buffer.
// Solo leemos el posible mensaje de error pasado via $GLOBALS.
$errorMessage = $GLOBALS['__login_error'] ?? null;
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h2>Acceso Privado</h2>
            <p>Coffee Flavored Software — Panel de Administración</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 0.75rem 1.25rem; margin-bottom: 1.25rem; border: 1px solid transparent; border-radius: 0.375rem; font-size: 0.9rem;">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="/admin/login" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
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