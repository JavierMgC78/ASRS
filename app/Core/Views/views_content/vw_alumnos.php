<?php
/**
 * Vista de Contenido: Listado de Alumnos (vw_alumnos.php)
 * Framework: ASRS / Módulo SEGI
 */
?>
<div class="asrs-hero">
    <div class="asrs-hero-badge">
        <span class="asrs-badge-dot"></span>
        <span>MÓDULO SEGI • CONTROL ESCOLAR</span>
    </div>

    <h1 class="asrs-hero-title">
        Directorio de <span class="asrs-gradient-text">Alumnos</span>
    </h1>

    <p class="asrs-hero-subtitle">
        Acceso restringido para perfiles <strong>admin</strong> y <strong>teacher</strong> validado por Seguridad de Nivel 1 en el Router.
    </p>
</div>

<div style="background-color: var(--asrs-panel-bg); border: 1px solid rgba(212, 217, 237, 0.15); border-radius: 8px; padding: 25px; margin-top: 30px;">
    <h3 style="color: var(--asrs-white); margin-bottom: 15px;">Listado de Estudiantes Registrados</h3>
    <table style="width: 100%; border-collapse: collapse; color: var(--asrs-border-silver); font-size: 0.95rem;">
        <thead>
            <tr style="border-bottom: 1px solid rgba(212, 217, 237, 0.2); text-align: left;">
                <th style="padding: 10px; color: var(--asrs-neon-gold);">Matrícula</th>
                <th style="padding: 10px; color: var(--asrs-neon-gold);">Nombre Completo</th>
                <th style="padding: 10px; color: var(--asrs-neon-gold);">Carrera</th>
                <th style="padding: 10px; color: var(--asrs-neon-gold);">Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid rgba(212, 217, 237, 0.1);">
                <td style="padding: 10px;">SEGI-2026-001</td>
                <td style="padding: 10px; color: var(--asrs-white);">Carlos Eduardo Peña</td>
                <td style="padding: 10px;">Ingeniería de Software</td>
                <td style="padding: 10px;"><span style="color: #4ade80;">Activo</span></td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(212, 217, 237, 0.1);">
                <td style="padding: 10px;">SEGI-2026-002</td>
                <td style="padding: 10px; color: var(--asrs-white);">Mariana González Ruiz</td>
                <td style="padding: 10px;">Ciberseguridad</td>
                <td style="padding: 10px;"><span style="color: #4ade80;">Activo</span></td>
            </tr>
        </tbody>
    </table>
</div>
