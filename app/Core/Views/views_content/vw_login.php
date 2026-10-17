<?php
/**
 * Vista de Contenido: Login (vw_login.php)
 * Framework: ASRS (Axe Secure Router System)
 */
$flashMessage = $_SESSION['asrs_flash_message'] ?? null;
unset($_SESSION['asrs_flash_message']);
?>
<div class="asrs-login-wrapper" style="max-width: 440px; margin: 40px auto; width: 100%;">
    <div style="background-color: var(--asrs-panel-bg); border: 1px solid rgba(212, 217, 237, 0.2); border-radius: 8px; padding: 35px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="display: inline-block; font-size: 2.2rem; margin-bottom: 8px;">🔐</div>
            <h2 style="color: var(--asrs-white); font-size: 1.6rem; margin-bottom: 6px;">Portal Seguro ASRS</h2>
            <p style="color: var(--asrs-text-muted); font-size: 0.9rem;">Autenticación perimetral de Nivel 1</p>
        </div>

        <?php if (!empty($flashMessage)) : ?>
            <div style="background: rgba(227, 179, 49, 0.15); border-left: 4px solid var(--asrs-neon-gold); color: #fce79a; padding: 12px; border-radius: 4px; font-size: 0.88rem; margin-bottom: 20px;">
                ⚠️ <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST" style="display: flex; flex-direction: column; gap: 18px;">
            <div>
                <label style="display: block; color: var(--asrs-border-silver); font-size: 0.85rem; margin-bottom: 6px; font-weight: 600;">Usuario o Correo Institucional</label>
                <input type="text" name="username" placeholder="admin@asrs.local" style="width: 100%; padding: 12px 14px; background: #0f1724; border: 1px solid rgba(212, 217, 237, 0.2); border-radius: 4px; color: var(--asrs-white); font-size: 0.95rem; outline: none; transition: border-color 0.2s ease;">
            </div>

            <div>
                <label style="display: block; color: var(--asrs-border-silver); font-size: 0.85rem; margin-bottom: 6px; font-weight: 600;">Contraseña de Acceso</label>
                <input type="password" name="password" placeholder="••••••••••••" style="width: 100%; padding: 12px 14px; background: #0f1724; border: 1px solid rgba(212, 217, 237, 0.2); border-radius: 4px; color: var(--asrs-white); font-size: 0.95rem; outline: none; transition: border-color 0.2s ease;">
            </div>

            <button type="submit" class="asrs-btn-login" style="width: 100%; padding: 12px; margin-top: 10px; border: none; cursor: pointer; font-size: 1rem;">
                Iniciar Sesión Segura
            </button>
        </form>

        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(212, 217, 237, 0.1); font-size: 0.82rem; color: var(--asrs-text-muted); text-align: center;">
            <p>ASRS Gateway • Sesiones cifradas y verificación de roles.</p>
        </div>
    </div>
</div>
