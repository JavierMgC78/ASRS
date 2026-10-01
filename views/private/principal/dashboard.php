<?php
/**
 * Vista: Panel de Control Principal (Dashboard)
 * Grupo: Principal
 * Archivo: dashboard.php
 */

use Core\Database;
use Core\Router;

$userName  = htmlspecialchars($_SESSION['user']['name'] ?? 'Usuario');
$userRole  = htmlspecialchars($_SESSION['user']['role_name'] ?? $_SESSION['user']['role'] ?? 'Usuario');
$roleType  = $_SESSION['user']['role_type'] ?? 'standard';

$totalAlumnos  = 0;
$totalUsuarios = 0;
$canAlumnos    = Router::userCanAccess(14); // Alta de Alumnos
$canVerAlumnos = Router::userCanAccess(15); // Ver Alumnos
$canRoles      = Router::userCanAccess(6);  // Ver Roles
$canUsuarios   = Router::userCanAccess(4);  // Usuarios

try {
    $db = Database::getInstance();
    $totalAlumnos = (int)$db->query("SELECT COUNT(*) FROM alumnos_cea")->fetchColumn();
    if ($roleType === 'special' || $canUsuarios) {
        $totalUsuarios = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
} catch (\Throwable $e) {
    // Continuar con valores por defecto
}
?>

<div class="dashboard-container">
    <!-- Tarjeta de Bienvenida y Encabezado -->
    <div class="dashboard-header-card">
        <div class="header-text">
            <h2>Bienvenido, <?= $userName ?></h2>
            <p>Panel de control del sistema de gestión institucional ASRS. Rol activo: <span class="badge-role"><?= $userRole ?></span></p>
        </div>
        <div class="header-status">
            <span class="status-indicator online"></span>
            <span class="status-text">Sesión Segura Activa</span>
        </div>
    </div>

    <!-- Cuadrícula de Tarjetas Métricas y Accesos -->
    <div class="dashboard-grid">
        <!-- Tarjeta 1: Alumnos / Control Escolar -->
        <a href="<?= $canVerAlumnos ? Router::url('alumnos/ver', 'private') : '#!' ?>" class="dash-card <?= $canVerAlumnos ? 'dash-card--action' : '' ?>" title="<?= $canVerAlumnos ? 'Abrir Directorio de Alumnos' : 'Total de alumnos registrados' ?>">
            <div class="card-icon icon-students">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div class="card-info">
                <h3>Alumnos Registrados</h3>
                <div class="card-number"><?= $totalAlumnos ?></div>
                <div class="card-subtext"><?= $canVerAlumnos ? 'Consultar listado general &rarr;' : 'Expedientes en sistema' ?></div>
            </div>
        </a>

        <!-- Tarjeta 2: Estado del Sistema -->
        <div class="dash-card">
            <div class="card-icon icon-shield">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
            </div>
            <div class="card-info">
                <h3>Seguridad & RBAC</h3>
                <div class="card-status active">Autorizado</div>
                <div class="card-subtext">Control por Split Token</div>
            </div>
        </div>

        <?php if ($canAlumnos): ?>
        <!-- Tarjeta 3: Acceso Directo a Inscripciones -->
        <a href="<?= Router::url('alumnos/crear', 'private') ?>" class="dash-card dash-card--action" title="Abrir Formulario de Alta de Alumno">
            <div class="card-icon icon-action">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </div>
            <div class="card-info">
                <h3>Módulo de Inscripción</h3>
                <div class="card-action-title">Nueva Inscripción &rarr;</div>
                <div class="card-subtext">Registrar nuevo alumno</div>
            </div>
        </a>
        <?php endif; ?>

        <?php if ($roleType === 'special' || $canUsuarios): ?>
        <!-- Tarjeta 4: Usuarios del Sistema (Para Administradores) -->
        <div class="dash-card">
            <div class="card-icon icon-users">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="card-info">
                <h3>Usuarios Registrados</h3>
                <div class="card-number"><?= $totalUsuarios ?></div>
                <div class="card-subtext">Cuentas activas en ASRS</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>