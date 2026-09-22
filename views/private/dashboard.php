<div class="dashboard-container">
    <div class="dashboard-header-card">
        <h2>Panel de Control Institucional</h2>
        <p>Bienvenido al sistema centralizado del Centro Educativo América. Desde aquí puedes supervisar las operaciones y accesos del sistema.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Tarjeta de Resumen 1 -->
        <div class="dash-card">
            <span class="card-icon">👥</span>
            <div class="card-info">
                <h3>Usuarios Activos</h3>
                <p class="card-number" id="active-users-count">Cargando...</p>
            </div>
        </div>

        <!-- Tarjeta de Resumen 2 -->
        <div class="dash-card">
            <span class="card-icon">🛡️</span>
            <div class="card-info">
                <h3>Rol del Sistema</h3>
                <p class="card-role"><?php echo htmlspecialchars($_SESSION['user']['role_name'] ?? 'Super Admin'); ?></p>
            </div>
        </div>

        <!-- Tarjeta de Resumen 3 -->
        <div class="dash-card">
            <span class="card-icon">⚡</span>
            <div class="card-info">
                <h3>Estado ASRS</h3>
                <p class="card-status active">Operativo (Caché activo)</p>
            </div>
        </div>
    </div>
</div>