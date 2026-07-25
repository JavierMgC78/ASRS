<?php
/**
 * views/gestor-rutas.php
 *
 * VISTA PARCIAL — Módulo Gestor de Rutas (CRUD Completo Inline)
 * ─────────────────────────────────────────────────────────────────────────────
 * Variables disponibles (inyectadas por GestorRutasController via extract()):
 *   $lista_rutas               (array)       — Registros de la tabla `rutas`.
 *   $roles_disponibles         (array)       — Mapa nivel→etiqueta de config/roles.php.
 *   $plantillas_privadas       (array)       — Nombres de archivos en templates/*.php.
 *   $plantillas_publicas       (array)       — Nombres de archivos en templates/public/*.php.
 *   $plantillas_disponibles    (array)       — Alias retrocompat (= $plantillas_privadas).
 *   $vistas_disponibles        (array)       — Nombres de archivos en views/*.php.
 *   $controladores_disponibles (array)       — Nombres de archivos en controllers/*.php.
 *   $mensaje_gestor_rutas      (string|null) — Feedback de la operación POST.
 *   $csrf_token                (string)      — Token CSRF de sesión (inyectado por index.php).
 */
?>
<style>
    /* ── Reset parcial ── */
    *, *::before, *::after { box-sizing: border-box; }

    /* ── Cabecera de página ── */
    .gr-page-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
    .gr-header-icon {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, rgba(99,102,241,.2), rgba(139,92,246,.2));
        border: 1px solid rgba(99,102,241,.3);
        border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        box-shadow: 0 0 18px rgba(99,102,241,.15);
    }
    .gr-header-text h2 {
        font-size: 1.55rem; font-weight: 800;
        color: #f1f5f9; letter-spacing: -.02em;
        margin-bottom: .25rem;
    }
    .gr-header-text h2 span {
        background: linear-gradient(90deg, #818cf8, #c4b5fd);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .gr-header-text p { font-size: .87rem; color: #64748b; line-height: 1.6; }

    /* ── Alerta de sistema ── */
    .gr-alert {
        display: flex; align-items: flex-start;
        gap: .85rem;
        padding: .95rem 1.3rem;
        border-radius: 11px;
        font-size: .87rem; font-weight: 500; line-height: 1.5;
        margin-bottom: 1.5rem;
        position: relative; overflow: hidden;
        animation: grAlertIn .3s cubic-bezier(.34,1.56,.64,1);
    }
    .gr-alert::before {
        content: ''; position: absolute;
        left: 0; top: 0; bottom: 0; width: 3px;
    }
    @keyframes grAlertIn {
        from { opacity: 0; transform: translateY(-10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .gr-alert-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: .05rem; }
    .gr-alert-success {
        background: rgba(16,185,129,.1);
        border: 1px solid rgba(16,185,129,.3);
        color: #6ee7b7;
    }
    .gr-alert-success::before { background: #10b981; }
    .gr-alert-error {
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.3);
        color: #fca5a5;
    }
    .gr-alert-error::before { background: #ef4444; }

    /* ── Card ── */
    .gr-card {
        background: linear-gradient(145deg, #13162a, #10131f);
        border: 1px solid rgba(99,102,241,.14);
        border-radius: 17px;
        padding: 1.6rem 1.75rem;
        box-shadow: 0 10px 38px rgba(0,0,0,.4);
        margin-bottom: 1.5rem;
    }

    /* ── Cabecera de tabla ── */
    .gr-table-meta {
        display: flex; align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        padding-bottom: 1.1rem;
        border-bottom: 1px solid rgba(99,102,241,.1);
        flex-wrap: wrap;
        gap: .75rem;
    }
    .gr-table-meta-left { display: flex; align-items: center; gap: .85rem; }
    .gr-table-meta-right { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .gr-table-meta-icon {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, rgba(99,102,241,.18), rgba(139,92,246,.1));
        border: 1px solid rgba(99,102,241,.25);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .gr-table-meta-text h3 { font-size: 1rem; font-weight: 700; color: #e2e8f0; }
    .gr-table-meta-text p  { font-size: .78rem; color: #475569; margin-top: .1rem; }
    .gr-count-badge {
        display: inline-flex; align-items: center;
        padding: .3rem .8rem;
        background: rgba(99,102,241,.1);
        border: 1px solid rgba(99,102,241,.2);
        border-radius: 20px;
        font-size: .76rem; color: #818cf8; font-weight: 600;
    }


    /* ── Tabla ── */
    .gr-table-scroll {
        overflow-x: auto;
        border-radius: 11px;
        border: 1px solid rgba(99,102,241,.1);
    }
    .gr-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed; /* anchos fijos: todas las filas respetan el colgroup */
        min-width: 860px;
    }
    .gr-table thead {
        background: linear-gradient(135deg, rgba(99,102,241,.12), rgba(139,92,246,.08));
    }
    .gr-table thead th {
        padding: .85rem 1rem;
        text-align: left;
        font-size: .7rem; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        color: #6366f1;
        border-bottom: 1px solid rgba(99,102,241,.15);
        white-space: nowrap;
    }
    .gr-table tbody tr {
        border-bottom: 1px solid rgba(255,255,255,.04);
        transition: background .18s;
    }
    .gr-table tbody tr:last-child { border-bottom: none; }
    .gr-table tbody tr:hover { background: rgba(99,102,241,.05); }
    .gr-table tbody td {
        padding: .85rem 1rem;
        font-size: .84rem;
        vertical-align: middle;
        color: #cbd5e1;
    }

    /* ── Celdas específicas ── */
    .gr-cell-id {
        font-family: 'Courier New', monospace;
        font-size: .77rem; color: #64748b; font-weight: 600;
    }
    .gr-cell-uri {
        color: #818cf8; font-weight: 600;
        font-family: 'Courier New', monospace;
        font-size: .82rem;
    }
    .gr-cell-vista, .gr-cell-ctrl {
        color: #94a3b8; font-size: .79rem;
        font-family: 'Courier New', monospace;
        max-width: 180px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        display: block;
    }
    .gr-cell-ctrl-null {
        color: #334155; font-style: italic; font-size: .78rem;
    }

    /* ── Select inline (nivel y plantilla) ── */
    .gr-inline-form { display: inline-flex; align-items: center; }
    .gr-layout-select {
        background: rgba(10,12,20,.8);
        border: 1px solid rgba(99,102,241,.25);
        border-radius: 8px;
        padding: .38rem 2rem .38rem .65rem;
        color: #a5b4fc;
        font-size: .78rem;
        font-family: inherit;
        cursor: pointer;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236366f1' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .55rem center;
        transition: border-color .2s, box-shadow .2s, background-color .2s;
        min-width: 140px;
    }
    .gr-layout-select:focus {
        border-color: rgba(99,102,241,.6);
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    }
    .gr-layout-select:hover {
        border-color: rgba(99,102,241,.45);
        background-color: rgba(10,12,20,.95);
    }
    .gr-layout-select option { background: #1a1d2e; color: #e2e8f0; }
    .gr-layout-select.saving {
        opacity: .6;
        pointer-events: none;
    }

    /* ── Botón eliminar ── */
    .gr-btn-delete {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.25);
        border-radius: 7px;
        color: #f87171; font-size: .85rem;
        cursor: pointer;
        transition: background .18s, border-color .18s, transform .15s;
        padding: 0; line-height: 1;
    }
    .gr-btn-delete:hover {
        background: rgba(239,68,68,.22);
        border-color: rgba(239,68,68,.5);
        transform: scale(1.08);
    }
    .gr-btn-delete:active { transform: scale(.96); }
    .gr-delete-form { display: inline-flex; }

    /* ── Estado vacío ── */
    .gr-empty {
        text-align: center; padding: 3rem 1.5rem;
        color: #475569;
    }
    .gr-empty-icon { font-size: 2.4rem; margin-bottom: .9rem; opacity: .5; }
    .gr-empty p { font-size: .88rem; line-height: 1.6; }

    /* ── Fila de inserción rápida (Inline Insert) ── */
    .gr-insert-row {
        background: linear-gradient(90deg, rgba(99,102,241,.07), rgba(139,92,246,.04));
        border-bottom: 1px solid rgba(99,102,241,.18) !important;
        /* Sin position:relative — los ::before en <tr> son inconsistentes entre navegadores */
    }
    .gr-insert-row td {
        padding: .65rem 1rem !important;
        vertical-align: middle;
    }
    /* Barra lateral de acento: box-shadow en el primer <td>, no ::before en <tr> */
    .gr-insert-first-td {
        box-shadow: inset 3px 0 0 0 #6366f1;
    }
    /* Inputs de la fila inline */
    .gr-insert-input {
        background: rgba(10,12,20,.8);
        border: 1px solid rgba(99,102,241,.2);
        border-radius: 7px;
        color: #e2e8f0;
        font-size: .8rem;
        padding: .38rem .6rem;
        width: 100%;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        font-family: 'Courier New', monospace;
    }
    .gr-insert-input:focus {
        border-color: rgba(99,102,241,.55);
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
        background: rgba(10,12,20,.95);
    }
    .gr-insert-input::placeholder { color: #334155; }
    /* Select de la fila inline */
    .gr-insert-select {
        background: rgba(10,12,20,.8);
        border: 1px solid rgba(99,102,241,.2);
        border-radius: 7px;
        color: #e2e8f0;
        font-size: .78rem;
        padding: .38rem 2rem .38rem .6rem;
        width: 100%;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236366f1' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .55rem center;
        cursor: pointer;
        transition: border-color .2s, box-shadow .2s;
    }
    .gr-insert-select:focus {
        border-color: rgba(99,102,241,.55);
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    }
    .gr-insert-select option { background: #1a1d2e; color: #e2e8f0; }
    /* Etiqueta de "Nuevo" en columna ID */
    .gr-insert-id-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem;
        background: rgba(99,102,241,.12);
        border: 1px solid rgba(99,102,241,.25);
        border-radius: 20px;
        font-size: .7rem; color: #818cf8; font-weight: 700;
        letter-spacing: .04em;
        white-space: nowrap;
    }
    /* Botón guardar inline */
    .gr-btn-insert-save {
        display: inline-flex; align-items: center; justify-content: center;
        gap: .3rem;
        padding: .42rem .9rem;
        background: linear-gradient(135deg, rgba(16,185,129,.25), rgba(5,150,105,.15));
        border: 1px solid rgba(16,185,129,.35);
        border-radius: 8px;
        color: #6ee7b7;
        font-size: .78rem; font-weight: 700;
        cursor: pointer;
        transition: background .18s, border-color .18s, transform .15s, box-shadow .2s;
        white-space: nowrap;
    }
    .gr-btn-insert-save:hover {
        background: linear-gradient(135deg, rgba(16,185,129,.38), rgba(5,150,105,.25));
        border-color: rgba(16,185,129,.6);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(16,185,129,.2);
    }
    .gr-btn-insert-save:active { transform: scale(.96); }

    /* ── Botón editar ── */
    .gr-btn-edit {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        background: rgba(99,102,241,.12);
        border: 1px solid rgba(99,102,241,.28);
        border-radius: 7px;
        color: #a5b4fc; font-size: .85rem;
        cursor: pointer;
        transition: background .18s, border-color .18s, transform .15s;
        padding: 0; line-height: 1;
    }
    .gr-btn-edit:hover {
        background: rgba(99,102,241,.26);
        border-color: rgba(99,102,241,.55);
        transform: scale(1.08);
    }
    .gr-btn-edit:active { transform: scale(.96); }

    /* ── Acciones: alinear los dos botones en fila ── */
    .gr-actions-cell {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }

    /* ── Overlay modal ── */
    .gr-modal-overlay {
        position: fixed; inset: 0; z-index: 9000;
        background: rgba(0,0,0,.6);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; pointer-events: none;
        transition: opacity .22s ease;
    }
    .gr-modal-overlay.is-open {
        opacity: 1; pointer-events: auto;
    }
    /* Caja del modal */
    .gr-modal {
        background: linear-gradient(145deg, #13162a, #10131f);
        border: 1px solid rgba(99,102,241,.22);
        border-radius: 18px;
        padding: 2rem 2.25rem 1.75rem;
        width: min(480px, 94vw);
        box-shadow: 0 24px 60px rgba(0,0,0,.55), 0 0 0 1px rgba(99,102,241,.08);
        transform: translateY(16px) scale(.97);
        transition: transform .25s cubic-bezier(.34,1.56,.64,1), opacity .22s ease;
        opacity: 0;
    }
    .gr-modal-overlay.is-open .gr-modal {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    /* Cabecera del modal */
    .gr-modal-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        margin-bottom: 1.6rem;
        gap: 1rem;
    }
    .gr-modal-title {
        display: flex; align-items: center; gap: .75rem;
    }
    .gr-modal-title-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, rgba(99,102,241,.22), rgba(139,92,246,.15));
        border: 1px solid rgba(99,102,241,.3);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .gr-modal-title h4 {
        font-size: 1rem; font-weight: 800;
        color: #e2e8f0; letter-spacing: -.01em;
        margin-bottom: .15rem;
    }
    .gr-modal-title p {
        font-size: .76rem; color: #475569;
    }
    .gr-modal-close {
        background: none; border: none; cursor: pointer;
        color: #475569; font-size: 1.2rem; padding: .2rem;
        line-height: 1; border-radius: 6px;
        transition: color .15s, background .15s;
        flex-shrink: 0;
    }
    .gr-modal-close:hover { color: #94a3b8; background: rgba(255,255,255,.06); }
    /* Campos del modal */
    .gr-modal-field { margin-bottom: 1.2rem; }
    .gr-modal-label {
        display: block;
        font-size: .74rem; font-weight: 700;
        letter-spacing: .07em; text-transform: uppercase;
        color: #6366f1;
        margin-bottom: .45rem;
    }
    .gr-modal-select {
        background: rgba(10,12,20,.85);
        border: 1px solid rgba(99,102,241,.22);
        border-radius: 9px;
        color: #e2e8f0;
        font-size: .83rem;
        padding: .5rem 2.2rem .5rem .8rem;
        width: 100%;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236366f1' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .75rem center;
        cursor: pointer;
        transition: border-color .2s, box-shadow .2s;
        font-family: 'Courier New', monospace;
    }
    .gr-modal-select:focus {
        border-color: rgba(99,102,241,.58);
        box-shadow: 0 0 0 3px rgba(99,102,241,.12);
    }
    .gr-modal-select option { background: #1a1d2e; color: #e2e8f0; }
    /* Divider */
    .gr-modal-divider {
        border: none;
        border-top: 1px solid rgba(99,102,241,.1);
        margin: 1.5rem 0 1.25rem;
    }
    /* Footer botones */
    .gr-modal-footer {
        display: flex; justify-content: flex-end;
        gap: .75rem;
    }
    .gr-btn-modal-cancel {
        padding: .5rem 1.1rem;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 9px;
        color: #64748b; font-size: .83rem; font-weight: 600;
        cursor: pointer;
        transition: background .18s, color .18s;
    }
    .gr-btn-modal-cancel:hover { background: rgba(255,255,255,.09); color: #94a3b8; }
    .gr-btn-modal-save {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem 1.3rem;
        background: linear-gradient(135deg, rgba(99,102,241,.35), rgba(139,92,246,.25));
        border: 1px solid rgba(99,102,241,.45);
        border-radius: 9px;
        color: #a5b4fc; font-size: .83rem; font-weight: 700;
        cursor: pointer;
        transition: background .18s, border-color .18s, transform .15s, box-shadow .2s;
    }
    .gr-btn-modal-save:hover {
        background: linear-gradient(135deg, rgba(99,102,241,.5), rgba(139,92,246,.38));
        border-color: rgba(99,102,241,.65);
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(99,102,241,.25);
    }
    .gr-btn-modal-save:active { transform: scale(.97); }

    /* ── Edición inline de la celda Vista ── */
    .gr-vista-cell {
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .gr-vista-text {
        color: #94a3b8; font-size: .79rem;
        font-family: 'Courier New', monospace;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        flex: 1;
        min-width: 0;
    }
    .gr-btn-edit-vista {
        display: inline-flex; align-items: center; justify-content: center;
        width: 22px; height: 22px;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 5px;
        color: #475569;
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
        transition: color .15s, border-color .15s, background .15s;
        opacity: 0;
    }
    tr:hover .gr-btn-edit-vista,
    .gr-btn-edit-vista:focus {
        opacity: 1;
    }
    .gr-btn-edit-vista:hover {
        color: #818cf8;
        border-color: rgba(99,102,241,.3);
        background: rgba(99,102,241,.08);
    }
    /* Modo edición */
    .gr-vista-edit-wrap {
        display: none;
        align-items: center;
        gap: .35rem;
        width: 100%;
    }
    .gr-vista-edit-wrap.is-editing {
        display: flex;
    }
    .gr-vista-cell.is-editing .gr-vista-text,
    .gr-vista-cell.is-editing .gr-btn-edit-vista {
        display: none;
    }
    .gr-vista-input {
        flex: 1;
        min-width: 0;
        background: rgba(10,12,20,.9);
        border: 1px solid rgba(99,102,241,.45);
        border-radius: 6px;
        color: #e2e8f0;
        font-size: .78rem;
        font-family: 'Courier New', monospace;
        padding: .28rem .5rem;
        outline: none;
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
        transition: border-color .18s, box-shadow .18s;
    }
    .gr-vista-input:focus {
        border-color: rgba(99,102,241,.7);
        box-shadow: 0 0 0 3px rgba(99,102,241,.18);
    }
    .gr-vista-input.is-error {
        border-color: rgba(239,68,68,.55);
        box-shadow: 0 0 0 3px rgba(239,68,68,.12);
    }
    .gr-btn-vista-save {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px;
        background: rgba(16,185,129,.18);
        border: 1px solid rgba(16,185,129,.35);
        border-radius: 6px;
        color: #6ee7b7;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
        transition: background .15s, border-color .15s, transform .12s;
    }
    .gr-btn-vista-save:hover {
        background: rgba(16,185,129,.3);
        border-color: rgba(16,185,129,.6);
        transform: scale(1.08);
    }
    .gr-btn-vista-cancel {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px;
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.25);
        border-radius: 6px;
        color: #f87171;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
        transition: background .15s, border-color .15s, transform .12s;
    }
    .gr-btn-vista-cancel:hover {
        background: rgba(239,68,68,.2);
        border-color: rgba(239,68,68,.5);
        transform: scale(1.08);
    }
    .gr-btn-vista-save.is-loading,
    .gr-btn-vista-cancel.is-loading {
        opacity: .5; pointer-events: none;
    }
    /* ── Toast inline ── */
    .gr-vista-toast {
        font-size: .72rem;
        padding: .18rem .5rem;
        border-radius: 5px;
        white-space: nowrap;
        animation: grToastIn .25s ease;
    }
    @keyframes grToastIn {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .gr-vista-toast.ok  { background: rgba(16,185,129,.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
    .gr-vista-toast.err { background: rgba(239,68,68,.12);  color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }

    /* ── Indicador de estado de archivo físico ── */
    .gr-status-dot-error {
        display: inline-block;
        width: 8px;
        height: 8px;
        background-color: #ef4444; /* red-500 */
        border-radius: 50%;
        box-shadow: 0 0 6px rgba(239,68,68,.6);
        flex-shrink: 0;
    }

    /* ── Separadores de Sección en Tabla ── */
    .gr-section-header {
        background-color: rgba(30, 41, 59, 0.6);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    .gr-section-header td {
        padding: 0.6rem 1rem !important;
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* ── Responsive ── */
    @media (max-width: 860px) {
        .gr-card { padding: 1.2rem; }
    }
</style>

<!-- ── CABECERA DE PÁGINA ── -->
<div class="gr-page-header">
    <div class="gr-header-icon">🗺️</div>
    <div class="gr-header-text">
        <h2>Gestor de <span>Rutas</span></h2>
        <p>CRUD completo del directorio de rutas del sistema. Edita el nivel o plantilla inline, crea o elimina rutas.</p>
    </div>
</div>

<!-- ── ALERTA DEL SISTEMA (condicional) ── -->
<?php if (!empty($mensaje_gestor_rutas)) : ?>
    <?php
        $gr_es_error  = str_starts_with($mensaje_gestor_rutas, '❌');
        $gr_cls_alert = $gr_es_error ? 'gr-alert-error' : 'gr-alert-success';
        $gr_ico_alert = $gr_es_error ? '✗' : '✓';
    ?>
    <div class="gr-alert <?= $gr_cls_alert ?>" role="alert" aria-live="polite" id="gr-system-alert">
        <span class="gr-alert-icon"><?= $gr_ico_alert ?></span>
        <span><?= htmlspecialchars($mensaje_gestor_rutas, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<!-- ── PANEL: TABLA DE RUTAS ── -->
<div class="gr-card" id="panel-lista-rutas">

    <?php $total_rutas = count($lista_rutas ?? []); ?>

    <div class="gr-table-meta">
        <div class="gr-table-meta-left">
            <div class="gr-table-meta-icon">📋</div>
            <div class="gr-table-meta-text">
                <h3>Directorio de Rutas</h3>
                <p>Edita el nivel o plantilla inline. Crea o elimina rutas con los controles de cada fila.</p>
            </div>
        </div>
        <div class="gr-table-meta-right">
            <span class="gr-count-badge">
                <?= $total_rutas ?> ruta<?= $total_rutas !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div>

    <?php $csrf_safe = htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>

    <!-- ══ FORMULARIO EXTERNO DE INSERCIÓN RÁPIDA (HTML5 form association) ══ -->
    <!-- Declarado FUERA de la tabla para no romper la estructura <tr>/<td>.   -->
    <form
        id="formNuevaRuta"
        method="POST"
        action="/gestor-rutas"
        novalidate
    >
        <input type="hidden" name="csrf_token" value="<?= $csrf_safe ?>">
        <input type="hidden" name="accion" value="crear_ruta">
    </form>
    <!-- ══ FIN FORMULARIO EXTERNO ══ -->

    <div class="gr-table-scroll">
        <table class="gr-table" id="tabla-rutas">
            <!-- colgroup: define anchos fijos para las 7 columnas -->
            <colgroup>
                <col style="width: 70px">   <!-- # ID -->
                <col style="width: 18%">    <!-- URI -->
                <col style="width: 16%">    <!-- Vista -->
                <col style="width: 16%">    <!-- Controlador -->
                <col style="width: 14%">    <!-- Nivel Mín. -->
                <col style="width: 14%">    <!-- Plantilla -->
                <col style="width: 110px">  <!-- Acciones -->
            </colgroup>
            <thead>
                <tr>
                    <th scope="col"># ID</th>
                    <th scope="col">URI</th>
                    <th scope="col">Vista</th>
                    <th scope="col">Controlador</th>
                    <th scope="col">Nivel Mín.</th>
                    <th scope="col">Plantilla</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>

                <!-- ══ FILA DE INSERCIÓN RÁPIDA (Inline Insert) ══ -->
                <!-- Usa el atributo HTML5 form="formNuevaRuta" para vincular   -->
                <!-- cada control al formulario externo sin anidar <form> en <tr>-->
                <tr class="gr-insert-row" id="fila-nueva-ruta">

                    <!-- Col 1: ID — indicador visual (box-shadow para barra lateral) -->
                    <td class="gr-insert-first-td">
                        <span class="gr-insert-id-badge">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Nuevo
                        </span>
                    </td>

                    <!-- Col 2: URI -->
                    <td>
                        <input
                            type="text"
                            name="uri"
                            id="inline-uri"
                            class="gr-insert-input"
                            placeholder="/nueva-ruta"
                            pattern="^/.*"
                            autocomplete="off"
                            required
                            form="formNuevaRuta"
                            aria-label="URI de la nueva ruta"
                        >
                    </td>

                    <!-- Col 3: Vista -->
                    <td>
                        <select name="vista" id="inline-vista" class="gr-insert-select" required form="formNuevaRuta" aria-label="Vista para la nueva ruta">
                            <option value="">— Vista —</option>
                            <?php foreach ($vistas_disponibles as $vista) : ?>
                                <option value="views/<?= htmlspecialchars($vista, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($vista, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <!-- Col 4: Controlador (opcional) -->
                    <td>
                        <select name="controlador" id="inline-controlador" class="gr-insert-select" form="formNuevaRuta" aria-label="Controlador para la nueva ruta (opcional)">
                            <option value="">— Sin ctrl —</option>
                            <?php foreach ($controladores_disponibles as $ctrl) : ?>
                                <option value="controllers/<?= htmlspecialchars($ctrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($ctrl, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <!-- Col 5: Nivel mínimo -->
                    <td>
                        <select name="nivel_minimo" id="inline-nivel" class="gr-insert-select" required form="formNuevaRuta" aria-label="Nivel mínimo de acceso para la nueva ruta">
                            <?php foreach ($roles_disponibles as $val => $label) : ?>
                                <option value="<?= $val ?>"><?= $val ?> — <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <!-- Col 6: Plantilla (opciones según nivel seleccionado) -->
                    <td>
                        <select name="plantilla" id="inline-plantilla" class="gr-insert-select" required form="formNuevaRuta" aria-label="Plantilla para la nueva ruta">
                            <option value="">— Plantilla —</option>
                            <?php if (!empty($plantillas_privadas)) : ?>
                                <optgroup label="🔒 Privadas (templates/)">
                                    <?php foreach ($plantillas_privadas as $plt) : ?>
                                        <option value="templates/<?= htmlspecialchars($plt, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($plt, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($plantillas_publicas)) : ?>
                                <optgroup label="🌐 Públicas (templates/public/)">
                                    <?php foreach ($plantillas_publicas as $plt) : ?>
                                        <option value="templates/public/<?= htmlspecialchars($plt, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($plt, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </td>

                    <!-- Col 7: Acción — Guardar -->
                    <td>
                        <button
                            type="submit"
                            class="gr-btn-insert-save"
                            id="btn-inline-guardar"
                            form="formNuevaRuta"
                            aria-label="Guardar nueva ruta"
                            title="Guardar nueva ruta"
                        >
                            <!-- Icono check -->
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Guardar
                        </button>
                    </td>

                </tr>
                <!-- ══ FIN FILA DE INSERCIÓN RÁPIDA ══ -->

                <?php 
                    // Renderizamos en base a las agrupaciones si hay rutas
                    $hay_rutas = !empty($lista_rutas);

                    // Función anónima para renderizar filas de datos (para no repetir código)
                    $renderizar_ruta = function($ruta) use ($vistas_disponibles, $controladores_disponibles, $roles_disponibles, $plantillas_privadas, $plantillas_publicas, $csrf_safe, $niveles_permitidos) {
                        $safe_id          = htmlspecialchars((string) $ruta['id'],          ENT_QUOTES, 'UTF-8');
                        $safe_uri         = htmlspecialchars((string) $ruta['uri'],         ENT_QUOTES, 'UTF-8');
                        $safe_vista       = htmlspecialchars((string) $ruta['vista'],       ENT_QUOTES, 'UTF-8');
                        $safe_ctrl        = htmlspecialchars((string) ($ruta['controlador'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $nivel_num        = (int) $ruta['nivel_minimo'];
                        $plantilla_actual = (string) $ruta['plantilla'];
                        
                        // Validación de existencia de archivo físico
                        $vista_fisica = realpath(BASE_PATH . '/' . $ruta['vista']);
                        $vista_existe = $vista_fisica !== false && is_file($vista_fisica);
                        ?>
                        <tr id="fila-ruta-<?= $safe_id ?>">

                            <!-- ID -->
                            <td class="gr-cell-id">#<?= $safe_id ?></td>

                            <!-- URI -->
                            <td><span class="gr-cell-uri"><?= $safe_uri ?></span></td>

                            <!-- Vista -->
                            <td>
                                <div class="gr-vista-cell" id="vista-cell-<?= $safe_id ?>">
                                    <!-- Indicador de archivo no encontrado -->
                                    <?php if (!$vista_existe) : ?>
                                        <span class="gr-status-dot-error" title="El archivo físico no existe en el servidor" id="vista-dot-<?= $safe_id ?>"></span>
                                    <?php endif; ?>
                                    <!-- Texto actual -->
                                    <span class="gr-vista-text" id="vista-text-<?= $safe_id ?>" title="<?= $safe_vista ?>">
                                        <?= $safe_vista ?>
                                    </span>
                                    <!-- Botón lápiz (inline, discreto) -->
                                    <button
                                        type="button"
                                        class="gr-btn-edit-vista"
                                        id="btn-edit-vista-<?= $safe_id ?>"
                                        aria-label="Editar vista de la ruta <?= $safe_uri ?>"
                                        title="Editar vista"
                                        onclick="grActivarEdicionVista(<?= $ruta['id'] ?>)"
                                    >
                                        <!-- lápiz mini -->
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <!-- Controles de edición (ocultos por defecto) -->
                                    <div class="gr-vista-edit-wrap" id="vista-edit-<?= $safe_id ?>">
                                        <input
                                            type="text"
                                            class="gr-vista-input"
                                            id="vista-input-<?= $safe_id ?>"
                                            value="<?= $safe_vista ?>"
                                            aria-label="Ruta de vista para <?= $safe_uri ?>"
                                            data-original="<?= $safe_vista ?>"
                                            data-id="<?= $safe_id ?>"
                                        >
                                        <!-- Guardar -->
                                        <button
                                            type="button"
                                            class="gr-btn-vista-save"
                                            id="btn-save-vista-<?= $safe_id ?>"
                                            title="Guardar (Enter)"
                                            aria-label="Guardar vista"
                                            onclick="grGuardarVista(<?= $ruta['id'] ?>)"
                                        >
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                        </button>
                                        <!-- Cancelar -->
                                        <button
                                            type="button"
                                            class="gr-btn-vista-cancel"
                                            id="btn-cancel-vista-<?= $safe_id ?>"
                                            title="Cancelar (Esc)"
                                            aria-label="Cancelar edición"
                                            onclick="grCancelarEdicionVista(<?= $ruta['id'] ?>)"
                                        >
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            <!-- Controlador -->
                            <td>
                                <?php if (!empty($ruta['controlador'])) : ?>
                                    <span class="gr-cell-ctrl" title="<?= $safe_ctrl ?>">
                                        <?= $safe_ctrl ?>
                                    </span>
                                <?php else : ?>
                                    <span class="gr-cell-ctrl-null">— sin controlador —</span>
                                <?php endif; ?>
                            </td>

                            <!-- Nivel mínimo — <select> inline con autoguardado -->
                            <td>
                                <form
                                    method="POST"
                                    action="/gestor-rutas"
                                    class="gr-inline-form"
                                    id="form-nivel-<?= $safe_id ?>"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_safe ?>">
                                    <input type="hidden" name="accion" value="actualizar_nivel">
                                    <input type="hidden" name="id" value="<?= $safe_id ?>">
                                    <select
                                        name="nivel_minimo"
                                        class="gr-layout-select"
                                        id="select-nivel-<?= $safe_id ?>"
                                        aria-label="Nivel mínimo de la ruta <?= $safe_uri ?>"
                                        onchange="this.classList.add('saving'); this.form.submit();"
                                    >
                                        <?php foreach ($roles_disponibles as $val => $label) : ?>
                                            <option value="<?= $val ?>" <?= ($nivel_num === $val) ? 'selected' : '' ?>>
                                                Nivel <?= $val ?> — <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>

                            <!-- Plantilla — <select> con autoguardado (opciones según nivel de la ruta) -->
                            <td>
                                <form
                                    method="POST"
                                    action="/gestor-rutas"
                                    class="gr-inline-form"
                                    id="form-layout-<?= $safe_id ?>"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_safe ?>">
                                    <input type="hidden" name="accion" value="actualizar_layout">
                                    <input type="hidden" name="id" value="<?= $safe_id ?>">
                                    <select
                                        name="plantilla"
                                        class="gr-layout-select"
                                        id="select-layout-<?= $safe_id ?>"
                                        aria-label="Plantilla de la ruta <?= $safe_uri ?>"
                                        onchange="this.classList.add('saving'); this.form.submit();"
                                    >
                                        <?php
                                        // Rutas públicas (nivel 0) → templates/public/
                                        // Rutas privadas (nivel > 0) → templates/
                                        $es_publica   = ($nivel_num === 0);
                                        $lista_plt    = $es_publica ? $plantillas_publicas  : $plantillas_privadas;
                                        $prefijo_plt  = $es_publica ? 'templates/public/' : 'templates/';
                                        ?>
                                        <?php foreach ($lista_plt as $archivo) : ?>
                                            <?php
                                                $valor_opcion = $prefijo_plt . $archivo;
                                                $selected     = ($plantilla_actual === $valor_opcion) ? 'selected' : '';
                                                $safe_archivo = htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8');
                                                $safe_valor   = htmlspecialchars($valor_opcion, ENT_QUOTES, 'UTF-8');
                                            ?>
                                            <option value="<?= $safe_valor ?>" <?= $selected ?>>
                                                <?= $safe_archivo ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (empty($lista_plt)) : ?>
                                            <option value="" disabled>Sin plantillas disponibles</option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                            </td>

                             <!-- Acciones — Botones Editar + Eliminar -->
                             <td>
                                 <div class="gr-actions-cell">

                                     <!-- Botón Editar -->
                                     <button
                                         type="button"
                                         class="gr-btn-edit"
                                         id="btn-edit-<?= $safe_id ?>"
                                         aria-label="Editar vista y controlador de la ruta <?= $safe_uri ?>"
                                         title="Editar vista / controlador"
                                         onclick="grAbrirModalEdicion(<?= $ruta['id'] ?>, <?= json_encode($ruta['vista']) ?>, <?= json_encode($ruta['controlador'] ?? '') ?>)"
                                     >
                                         <!-- Icono lápiz -->
                                         <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                             <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                             <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                         </svg>
                                     </button>

                                     <!-- Botón Eliminar -->
                                     <form
                                         method="POST"
                                         action="/gestor-rutas"
                                         class="gr-delete-form"
                                         id="form-delete-<?= $safe_id ?>"
                                         onsubmit="return confirm('\u00bfEst\u00e1s seguro de eliminar la ruta \u00ab<?= addslashes($safe_uri) ?>\u00bb?\nEsta acci\u00f3n no se puede deshacer.');"
                                     >
                                         <input type="hidden" name="csrf_token" value="<?= $csrf_safe ?>">
                                         <input type="hidden" name="accion" value="eliminar_ruta">
                                         <input type="hidden" name="id" value="<?= $safe_id ?>">
                                         <button
                                             type="submit"
                                             class="gr-btn-delete"
                                             id="btn-delete-<?= $safe_id ?>"
                                             aria-label="Eliminar ruta <?= $safe_uri ?>"
                                             title="Eliminar ruta"
                                         >
                                             <!-- Icono papelera -->
                                             <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                 <polyline points="3 6 5 6 21 6"/>
                                                 <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                 <path d="M10 11v6M14 11v6"/>
                                                 <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                             </svg>
                                         </button>
                                     </form>

                                 </div><!-- /.gr-actions-cell -->
                            </td>

                        </tr>
                        <?php
                    };
                ?>

                <?php if ($hay_rutas) : ?>

                    <?php if (!empty($lista_rutas['publicas'])) : ?>
                        <tr class="gr-section-header">
                            <td colspan="7">🌐 Rutas Públicas (Sin Login Requerido)</td>
                        </tr>
                        <?php foreach ($lista_rutas['publicas'] as $ruta) {
                            $renderizar_ruta($ruta);
                        } ?>
                    <?php endif; ?>

                    <?php if (!empty($lista_rutas['privadas'])) : ?>
                        <?php foreach ($lista_rutas['privadas'] as $nivel => $rutas_nivel) : ?>
                            <?php 
                                $nombre_rol = $roles_disponibles[$nivel] ?? 'Desconocido'; 
                            ?>
                            <tr class="gr-section-header">
                                <td colspan="7">🔒 Nivel <?= (int)$nivel ?> — <?= htmlspecialchars($nombre_rol, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php foreach ($rutas_nivel as $ruta) {
                                $renderizar_ruta($ruta);
                            } ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php else : ?>
                    <tr>
                        <td colspan="7">
                            <div class="gr-empty" id="gr-empty-state">
                                <div class="gr-empty-icon">🗺️</div>
                                <p>No hay rutas registradas todavía.<br>
                                   Usa la fila superior para crear la primera.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<!-- ══ MODAL DE EDICIÓN: Vista y Controlador ══ -->
<div
    class="gr-modal-overlay"
    id="gr-modal-edicion"
    role="dialog"
    aria-modal="true"
    aria-labelledby="gr-modal-titulo"
    tabindex="-1"
>
    <div class="gr-modal">

        <!-- Cabecera -->
        <div class="gr-modal-header">
            <div class="gr-modal-title">
                <div class="gr-modal-title-icon">✏️</div>
                <div>
                    <h4 id="gr-modal-titulo">Editar Ruta</h4>
                    <p id="gr-modal-subtitulo">Modifica la vista y el controlador asignados.</p>
                </div>
            </div>
            <button
                type="button"
                class="gr-modal-close"
                id="gr-modal-btn-cerrar"
                aria-label="Cerrar modal"
                onclick="grCerrarModal()"
            >✕</button>
        </div>

        <!-- Formulario POST -->
        <form
            id="formEditarRuta"
            method="POST"
            action="/gestor-rutas"
            novalidate
        >
            <input type="hidden" name="csrf_token" value="<?= $csrf_safe ?>">
            <input type="hidden" name="accion" value="actualizar_vista_ctrl">
            <input type="hidden" name="id" id="gr-modal-id" value="">

            <!-- Campo: Vista -->
            <div class="gr-modal-field">
                <label class="gr-modal-label" for="gr-modal-vista">Vista asignada</label>
                <select
                    name="vista"
                    id="gr-modal-vista"
                    class="gr-modal-select"
                    required
                    aria-required="true"
                >
                    <?php foreach ($vistas_disponibles as $vista) : ?>
                        <option value="views/<?= htmlspecialchars($vista, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($vista, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo: Controlador (opcional) -->
            <div class="gr-modal-field">
                <label class="gr-modal-label" for="gr-modal-ctrl">Controlador asignado <span style="color:#334155;font-weight:400;text-transform:none;letter-spacing:0">(opcional)</span></label>
                <select
                    name="controlador"
                    id="gr-modal-ctrl"
                    class="gr-modal-select"
                >
                    <option value="">— Sin controlador —</option>
                    <?php foreach ($controladores_disponibles as $ctrl) : ?>
                        <option value="controllers/<?= htmlspecialchars($ctrl, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($ctrl, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="gr-modal-divider">

            <!-- Footer -->
            <div class="gr-modal-footer">
                <button type="button" class="gr-btn-modal-cancel" id="gr-modal-btn-cancelar" onclick="grCerrarModal()">Cancelar</button>
                <button type="submit" class="gr-btn-modal-save" id="gr-modal-btn-guardar">
                    <!-- Icono check -->
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    Guardar cambios
                </button>
            </div>
        </form>

    </div><!-- /.gr-modal -->
</div><!-- /.gr-modal-overlay -->
<!-- ══ FIN MODAL ══ -->

<script>
(function () {
    'use strict';

    // ───────────────────────────────────────────────────────────────────────
    // MODAL DE EDICIÓN (vista y controlador) — comportamiento ya existente
    // ───────────────────────────────────────────────────────────────────────
    const overlay   = document.getElementById('gr-modal-edicion');
    const inputId   = document.getElementById('gr-modal-id');
    const selVista  = document.getElementById('gr-modal-vista');
    const selCtrl   = document.getElementById('gr-modal-ctrl');
    const subtitulo = document.getElementById('gr-modal-subtitulo');

    /** Abre el modal de edición y precarga los valores actuales de la ruta. */
    window.grAbrirModalEdicion = function (id, vistaActual, ctrlActual) {
        inputId.value = id;
        subtitulo.textContent = 'Ruta ID #' + id;

        if (selVista) {
            let found = false;
            for (const opt of selVista.options) {
                if (opt.value === vistaActual) { opt.selected = true; found = true; }
                else { opt.selected = false; }
            }
            if (!found) selVista.selectedIndex = 0;
        }

        if (selCtrl) {
            let found = false;
            for (const opt of selCtrl.options) {
                if (opt.value === ctrlActual) { opt.selected = true; found = true; }
                else { opt.selected = false; }
            }
            if (!found) selCtrl.selectedIndex = 0;
        }

        overlay.classList.add('is-open');
        overlay.focus();
    };

    /** Cierra el modal con animación. */
    window.grCerrarModal = function () {
        overlay.classList.remove('is-open');
    };

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) grCerrarModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
            grCerrarModal();
        }
    });

    // ───────────────────────────────────────────────────────────────────────
    // EDICIÓN INLINE DE VISTA (AJAX)
    // ───────────────────────────────────────────────────────────────────────
    // Leer el CSRF token del primer input oculto disponible en la página
    const CSRF_TOKEN = (document.querySelector('input[name="csrf_token"]') || {}).value || '';

    /**
     * Activa el modo edición inline en la celda Vista de la fila indicada.
     * @param {number} id - ID de la ruta.
     */
    window.grActivarEdicionVista = function (id) {
        const cell     = document.getElementById('vista-cell-' + id);
        const editWrap = document.getElementById('vista-edit-' + id);
        const input    = document.getElementById('vista-input-' + id);

        if (!cell || !editWrap || !input) return;

        cell.classList.add('is-editing');
        editWrap.classList.add('is-editing');
        input.classList.remove('is-error');

        input.focus();
        input.select();

        // Guardar con Enter, cancelar con Escape
        input._grKeyHandler = function (e) {
            if (e.key === 'Enter')  { e.preventDefault(); grGuardarVista(id); }
            if (e.key === 'Escape') { grCancelarEdicionVista(id); }
        };
        input.addEventListener('keydown', input._grKeyHandler);
    };

    /**
     * Cancela la edición inline y restaura el valor original.
     * @param {number} id
     */
    window.grCancelarEdicionVista = function (id) {
        const cell     = document.getElementById('vista-cell-' + id);
        const editWrap = document.getElementById('vista-edit-' + id);
        const input    = document.getElementById('vista-input-' + id);

        if (!cell || !editWrap || !input) return;

        input.value = input.dataset.original;
        input.classList.remove('is-error');

        cell.classList.remove('is-editing');
        editWrap.classList.remove('is-editing');

        if (input._grKeyHandler) {
            input.removeEventListener('keydown', input._grKeyHandler);
        }
    };

    /**
     * Envía la nueva vista vía AJAX y actualiza la celda sin recargar.
     * @param {number} id
     */
    window.grGuardarVista = function (id) {
        const cell      = document.getElementById('vista-cell-' + id);
        const editWrap  = document.getElementById('vista-edit-' + id);
        const input     = document.getElementById('vista-input-' + id);
        const btnSave   = document.getElementById('btn-save-vista-' + id);
        const btnCancel = document.getElementById('btn-cancel-vista-' + id);
        const textSpan  = document.getElementById('vista-text-' + id);

        if (!input) return;

        const nuevaVista = input.value.trim();
        input.classList.remove('is-error');

        // Validación mínima en cliente
        if (!nuevaVista.startsWith('views/') || !nuevaVista.endsWith('.php')) {
            input.classList.add('is-error');
            grMostrarToastVista(id, 'Debe comenzar con views/ y terminar en .php', 'err');
            input.focus();
            return;
        }

        // Sin cambios → solo cancela
        if (nuevaVista === input.dataset.original) {
            grCancelarEdicionVista(id);
            return;
        }

        // Estado de carga
        btnSave.classList.add('is-loading');
        btnCancel.classList.add('is-loading');
        input.disabled = true;

        const body = new URLSearchParams({
            csrf_token : CSRF_TOKEN,
            accion     : 'ajax_actualizar_vista',
            id         : String(id),
            vista      : nuevaVista,
        });

        fetch('/gestor-rutas', {
            method  : 'POST',
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
            body    : body.toString(),
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (data) {
            if (data.ok) {
                // Éxito: actualizar texto visible y salir del modo edición
                textSpan.textContent   = data.vista;
                textSpan.title         = data.vista;
                input.dataset.original = data.vista;

                cell.classList.remove('is-editing');
                editWrap.classList.remove('is-editing');
                if (input._grKeyHandler) input.removeEventListener('keydown', input._grKeyHandler);

                grMostrarToastVista(id, '✓ Guardado', 'ok');
            } else {
                input.classList.add('is-error');
                grMostrarToastVista(id, data.error || 'Error al guardar', 'err');
                input.focus();
            }
        })
        .catch(function () {
            input.classList.add('is-error');
            grMostrarToastVista(id, 'Error de red. Inténtalo de nuevo.', 'err');
            input.focus();
        })
        .finally(function () {
            btnSave.classList.remove('is-loading');
            btnCancel.classList.remove('is-loading');
            input.disabled = false;
        });
    };

    /**
     * Muestra un pequeño toast inline debajo de la celda, que desaparece solo.
     * @param {number} id
     * @param {string} msg
     * @param {'ok'|'err'} tipo
     */
    function grMostrarToastVista(id, msg, tipo) {
        const previo = document.getElementById('vista-toast-' + id);
        if (previo) previo.remove();

        const td = document.getElementById('vista-cell-' + id) &&
                   document.getElementById('vista-cell-' + id).closest('td');
        if (!td) return;

        const toast = document.createElement('div');
        toast.id          = 'vista-toast-' + id;
        toast.className   = 'gr-vista-toast ' + tipo;
        toast.textContent = msg;
        td.appendChild(toast);

        setTimeout(function () { toast.remove(); }, 3000);
    }

}());
</script>
