<section class="asrs-view-section">
    <div class="asrs-form-container">
        <div class="asrs-form-header-title">
            <h2>ACCESO AL SISTEMA ASRS</h2>
        </div>
        
        <form id="asrsLoginForm" class="asrs-form" action="/auth/login" method="POST" novalidate>
            
            <!-- SECCIÓN: CREDENCIALES DE ACCESO -->
            <div class="asrs-section" data-section="1">
                <div class="asrs-section-bar">
                    <span class="asrs-section-title">CREDENCIALES DE AUTENTICACIÓN</span>
                    <button type="button" class="asrs-collapse-btn" aria-expanded="true" title="Desplegar / Colapsar sección">
                        <span class="asrs-icon-arrow">▼</span>
                    </button>
                </div>
                <div class="asrs-section-content">
                    <div class="asrs-fields-grid" style="grid-template-columns: 1fr;">
                        <div class="asrs-field-group">
                            <label for="username">IDENTIFICADOR / USUARIO</label>
                            <input type="text" id="username" name="username" class="asrs-input" placeholder="Ingrese su usuario..." required>
                        </div>
                        <div class="asrs-field-group" style="margin-top: 15px;">
                            <label for="password">CONTRASEÑA PERIMETRAL</label>
                            <input type="password" id="password" name="password" class="asrs-input" placeholder="••••••••••••" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: PROTOCOLO DE SEGURIDAD (Informativo / Split Token) -->
            <div class="asrs-section" data-section="2">
                <div class="asrs-section-bar">
                    <span class="asrs-section-title">SEGURIDAD Y SESIÓN</span>
                    <button type="button" class="asrs-collapse-btn" aria-expanded="true" title="Desplegar / Colapsar sección">
                        <span class="asrs-icon-arrow">▼</span>
                    </button>
                </div>
                <div class="asrs-section-content">
                    <div class="asrs-fields-grid">
                        <div class="asrs-field-group">
                            <label>PROTOCOLO ACTIVO</label>
                            <div style="background-color: #0b111a; border: 1px solid #324463; border-radius: 4px; padding: 10px; color: var(--asrs-neon-gold); font-size: 0.9rem; font-family: monospace;">
                                [SECURE] Split Token Pattern (HttpOnly + Auth Header)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACCIONES GENERALES -->
            <div class="asrs-form-actions">
                <button type="submit" class="asrs-btn asrs-btn-primary">INICIAR SESIÓN</button>
            </div>

        </form>
    </div>
</section>