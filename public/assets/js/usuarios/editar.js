/**
 * ASRS Framework - Lógica Frontend para Edición de Usuario
 * Archivo: public/assets/js/usuarios/editar.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Referencias DOM ──────────────────────────────────────────────────────────
    const form            = document.getElementById('form-edit-user');
    const inputName       = document.getElementById('input-name');
    const inputEmail      = document.getElementById('input-email');
    const selectRole      = document.getElementById('select-role');
    const checkActive     = document.getElementById('check-is-active');
    const inputPassword   = document.getElementById('input-password');
    const inputConfirm    = document.getElementById('input-password-confirm');
    const btnSave         = document.getElementById('btn-save-user');
    const btnSaveText     = document.getElementById('btn-save-text');
    const saveSpinner     = document.getElementById('save-spinner');
    const btnReset        = document.getElementById('btn-reset-user');
    const toastContainer  = document.getElementById('user-toast-container');

    // Mensajes de Feedback
    const fbName          = document.getElementById('feedback-name');
    const fbEmail         = document.getElementById('feedback-email');
    const fbPassword      = document.getElementById('feedback-password');
    const fbConfirm       = document.getElementById('feedback-password-confirm');
    const roleDescHint    = document.getElementById('role-description-hint');
    const strengthMeter   = document.getElementById('strength-meter');
    const switchLabel     = document.getElementById('switch-status-label');

    // Elementos del Preview Lateral
    const previewInitials = document.getElementById('profile-initials');
    const previewName     = document.getElementById('profile-name-preview');
    const previewEmail    = document.getElementById('profile-email-preview');
    const previewRole     = document.getElementById('profile-role-badge');
    const previewRoleType = document.getElementById('profile-role-type');
    const previewStatus   = document.getElementById('profile-status-badge');
    const previewDot      = document.getElementById('profile-status-dot');

    if (!form) return; // Si no hay formulario en pantalla, salir

    // Guardar valores iniciales para la función "Descartar cambios"
    const initialValues = {
        name:     inputName ? inputName.value : '',
        email:    inputEmail ? inputEmail.value : '',
        roleId:   selectRole ? selectRole.value : '',
        isActive: checkActive ? checkActive.checked : true,
    };

    // ── Helper: Notificación Toast ──────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `user-toast user-toast--${type}`;
        toast.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'success' 
                    ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                    : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                }
            </svg>
            <span>${message}</span>
        `;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    // ── Helper: Extraer Iniciales ───────────────────────────────────────────────
    function getInitials(name) {
        if (!name) return 'U';
        const parts = name.trim().split(/\s+/);
        let res = parts[0].charAt(0).toUpperCase();
        if (parts.length > 1) {
            res += parts[parts.length - 1].charAt(0).toUpperCase();
        }
        return res;
    }

    // ── Validación en tiempo real del Nombre ─────────────────────────────────────
    function validateName() {
        const val = inputName.value.trim();
        if (val.length === 0) {
            inputName.classList.add('is-invalid');
            inputName.classList.remove('is-valid');
            fbName.textContent = 'El nombre completo es obligatorio.';
            fbName.className = 'form-feedback is-error';
            return false;
        } else if (val.length < 3) {
            inputName.classList.add('is-invalid');
            inputName.classList.remove('is-valid');
            fbName.textContent = 'El nombre debe contener al menos 3 caracteres.';
            fbName.className = 'form-feedback is-error';
            return false;
        } else {
            inputName.classList.remove('is-invalid');
            inputName.classList.add('is-valid');
            fbName.textContent = '';
            fbName.className = 'form-feedback';
            return true;
        }
    }

    if (inputName) {
        inputName.addEventListener('input', () => {
            validateName();
            if (previewName) previewName.textContent = inputName.value.trim() || 'Nombre de Usuario';
            if (previewInitials) previewInitials.textContent = getInitials(inputName.value);
        });
        inputName.addEventListener('blur', validateName);
    }

    // ── Validación en tiempo real del Email ──────────────────────────────────────
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    function validateEmail() {
        const val = inputEmail.value.trim();
        if (val.length === 0) {
            inputEmail.classList.add('is-invalid');
            inputEmail.classList.remove('is-valid');
            fbEmail.textContent = 'El correo electrónico es obligatorio.';
            fbEmail.className = 'form-feedback is-error';
            return false;
        } else if (!emailRegex.test(val)) {
            inputEmail.classList.add('is-invalid');
            inputEmail.classList.remove('is-valid');
            fbEmail.textContent = 'Ingresa un formato de correo electrónico válido (ej. usuario@dominio.com).';
            fbEmail.className = 'form-feedback is-error';
            return false;
        } else {
            inputEmail.classList.remove('is-invalid');
            inputEmail.classList.add('is-valid');
            fbEmail.textContent = '';
            fbEmail.className = 'form-feedback';
            return true;
        }
    }

    if (inputEmail) {
        inputEmail.addEventListener('input', () => {
            validateEmail();
            if (previewEmail) previewEmail.textContent = inputEmail.value.trim() || 'correo@dominio.com';
        });
        inputEmail.addEventListener('blur', validateEmail);
    }

    // ── Cambio de Rol Reactivo ──────────────────────────────────────────────────
    function updateRolePreview() {
        if (!selectRole) return;
        const selectedOption = selectRole.options[selectRole.selectedIndex];
        if (selectedOption) {
            const roleName = selectedOption.text.split('(')[0].trim();
            const roleDesc = selectedOption.dataset.desc || '';
            const roleType = selectedOption.dataset.type || 'standard';

            if (previewRole) previewRole.textContent = roleName;
            if (previewRoleType) previewRoleType.textContent = roleType.charAt(0).toUpperCase() + roleType.slice(1);
            if (roleDescHint) {
                roleDescHint.textContent = roleDesc ? `Descripción del perfil: ${roleDesc}` : '';
            }
        }
    }

    if (selectRole) {
        selectRole.addEventListener('change', updateRolePreview);
        updateRolePreview(); // Inicializar descripción
    }

    // ── Switch de Estado Activo / Inactivo ───────────────────────────────────────
    if (checkActive) {
        checkActive.addEventListener('change', () => {
            const isActive = checkActive.checked;
            if (switchLabel) {
                switchLabel.textContent = isActive ? 'Cuenta Activa' : 'Cuenta Suspendida / Inactiva';
            }
            if (previewStatus) {
                previewStatus.textContent = isActive ? 'Activo' : 'Inactivo';
                previewStatus.className = `badge-status ${isActive ? 'badge-status--active' : 'badge-status--inactive'}`;
            }
            if (previewDot) {
                previewDot.className = `profile-status-indicator ${isActive ? 'is-online' : 'is-offline'}`;
            }
        });
    }

    // ── Contraseña y Medidor de Fuerza ──────────────────────────────────────────
    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 9) score++;
        if (/[A-Z]/.test(password) && /[0-9]/.test(password)) score++;
        return score;
    }

    function validatePasswords() {
        const pass = inputPassword.value;
        const conf = inputConfirm.value;

        // Si ambos están vacíos, no se cambia contraseña (es válido)
        if (pass.length === 0 && conf.length === 0) {
            inputPassword.classList.remove('is-invalid', 'is-valid');
            inputConfirm.classList.remove('is-invalid', 'is-valid');
            fbPassword.textContent = '';
            fbConfirm.textContent = '';
            if (strengthMeter) strengthMeter.className = 'strength-meter';
            return true;
        }

        let isValid = true;

        // Validar longitud de la contraseña
        if (pass.length < 6) {
            inputPassword.classList.add('is-invalid');
            inputPassword.classList.remove('is-valid');
            fbPassword.textContent = 'La nueva contraseña debe tener al menos 6 caracteres.';
            fbPassword.className = 'form-feedback is-error';
            isValid = false;
        } else {
            inputPassword.classList.remove('is-invalid');
            inputPassword.classList.add('is-valid');
            fbPassword.textContent = '';
            fbPassword.className = 'form-feedback';

            // Medidor de fuerza
            const strength = checkPasswordStrength(pass);
            if (strengthMeter) {
                if (strength <= 1) strengthMeter.className = 'strength-meter weak';
                else if (strength === 2) strengthMeter.className = 'strength-meter medium';
                else strengthMeter.className = 'strength-meter strong';
            }
        }

        // Validar confirmación
        if (conf.length > 0 || pass.length >= 6) {
            if (conf !== pass) {
                inputConfirm.classList.add('is-invalid');
                inputConfirm.classList.remove('is-valid');
                fbConfirm.textContent = 'Las contraseñas no coinciden.';
                fbConfirm.className = 'form-feedback is-error';
                isValid = false;
            } else {
                inputConfirm.classList.remove('is-invalid');
                inputConfirm.classList.add('is-valid');
                fbConfirm.textContent = 'Las contraseñas coinciden correctamente.';
                fbConfirm.className = 'form-feedback';
            }
        }

        return isValid;
    }

    if (inputPassword && inputConfirm) {
        inputPassword.addEventListener('input', validatePasswords);
        inputConfirm.addEventListener('input', validatePasswords);
    }

    // ── Toggle de Visibilidad de Contraseñas (Icono de Ojo) ──────────────────────
    const eyeButtons = document.querySelectorAll('.btn-toggle-eye');
    eyeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const targetInput = document.getElementById(targetId);
            if (!targetInput) return;

            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                if (eyeOpen) eyeOpen.style.display = 'none';
                if (eyeClosed) eyeClosed.style.display = 'inline';
            } else {
                targetInput.type = 'password';
                if (eyeOpen) eyeOpen.style.display = 'inline';
                if (eyeClosed) eyeClosed.style.display = 'none';
            }
        });
    });

    // ── Botón Descartar Cambios ─────────────────────────────────────────────────
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            if (!confirm('¿Deseas descartar todas las modificaciones y restaurar los valores iniciales?')) {
                return;
            }

            if (inputName) inputName.value = initialValues.name;
            if (inputEmail) inputEmail.value = initialValues.email;
            if (selectRole) selectRole.value = initialValues.roleId;
            if (checkActive) checkActive.checked = initialValues.isActive;
            if (inputPassword) inputPassword.value = '';
            if (inputConfirm) inputConfirm.value = '';

            // Limpiar clases de validación
            [inputName, inputEmail, inputPassword, inputConfirm].forEach(el => {
                if (el) el.classList.remove('is-invalid', 'is-valid');
            });
            [fbName, fbEmail, fbPassword, fbConfirm].forEach(el => {
                if (el) el.textContent = '';
            });

            // Disparar eventos para recalcular vistas previas
            if (inputName) inputName.dispatchEvent(new Event('input'));
            if (inputEmail) inputEmail.dispatchEvent(new Event('input'));
            if (selectRole) selectRole.dispatchEvent(new Event('change'));
            if (checkActive) checkActive.dispatchEvent(new Event('change'));
            if (strengthMeter) strengthMeter.className = 'strength-meter';

            showToast('Formulario restablecido a sus valores originales.', 'success');
        });
    }

    // ── Envío del Formulario (AJAX / Fetch) ──────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validaciones del cliente
        const isNameValid  = validateName();
        const isEmailValid = validateEmail();
        const isPassValid  = validatePasswords();

        if (!isNameValid || !isEmailValid || !isPassValid) {
            showToast('Por favor corrige los campos señalados antes de guardar.', 'danger');
            return;
        }

        // Estado de carga
        btnSave.disabled = true;
        if (saveSpinner) saveSpinner.style.display = 'inline-block';
        if (btnSaveText) btnSaveText.textContent = 'Guardando cambios...';

        const formData = new FormData(form);
        formData.append('ajax', '1');

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message || 'Usuario actualizado correctamente en la base de datos.', 'success');
                
                // Limpiar campos de contraseña tras guardado exitoso
                if (inputPassword) inputPassword.value = '';
                if (inputConfirm) inputConfirm.value = '';
                if (strengthMeter) strengthMeter.className = 'strength-meter';
                [inputPassword, inputConfirm].forEach(el => el && el.classList.remove('is-valid', 'is-invalid'));
                if (fbPassword) fbPassword.textContent = '';
                if (fbConfirm) fbConfirm.textContent = '';

                // Actualizar valores de referencia iniciales
                initialValues.name     = inputName ? inputName.value : '';
                initialValues.email    = inputEmail ? inputEmail.value : '';
                initialValues.roleId   = selectRole ? selectRole.value : '';
                initialValues.isActive = checkActive ? checkActive.checked : true;

                // Si se actualizó el nombre o rol del usuario logueado, recargar para refrescar topbar
                setTimeout(() => {
                    window.location.reload();
                }, 1200);

            } else {
                showToast(result.message || 'Error al procesar la actualización.', 'danger');
            }

        } catch (error) {
            showToast('Error de conexión al servidor. Inténtalo nuevamente.', 'danger');
        } finally {
            btnSave.disabled = false;
            if (saveSpinner) saveSpinner.style.display = 'none';
            if (btnSaveText) btnSaveText.textContent = 'Guardar Cambios';
        }
    });
});
