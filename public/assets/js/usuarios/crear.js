/**
 * ASRS Framework - Lógica Frontend para Crear Usuario
 * Archivo: public/assets/js/usuarios/crear.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Referencias DOM ──────────────────────────────────────────────────────────
    const form            = document.getElementById('form-create-user');
    const inputName       = document.getElementById('input-name');
    const inputEmail      = document.getElementById('input-email');
    const selectRole      = document.getElementById('select-role');
    const checkActive     = document.getElementById('check-is-active');
    const inputPassword   = document.getElementById('input-password');
    const inputConfirm    = document.getElementById('input-password-confirm');
    const btnSubmit       = document.getElementById('btn-submit-user');
    const btnSubmitText   = document.getElementById('btn-submit-text');
    const createSpinner   = document.getElementById('create-spinner');
    const btnClear        = document.getElementById('btn-clear-form');
    const toastContainer  = document.getElementById('user-toast-container');

    // Mensajes de Feedback y Ayuda
    const fbName          = document.getElementById('feedback-name');
    const fbEmail         = document.getElementById('feedback-email');
    const fbPassword      = document.getElementById('feedback-password');
    const fbConfirm       = document.getElementById('feedback-password-confirm');
    const roleDescHint    = document.getElementById('role-description-hint');
    const strengthMeter   = document.getElementById('strength-meter');
    const switchLabel     = document.getElementById('switch-status-label');

    // Elementos de la Ficha Previa Lateral
    const previewInitials = document.getElementById('profile-initials');
    const previewName     = document.getElementById('profile-name-preview');
    const previewEmail    = document.getElementById('profile-email-preview');
    const previewRole     = document.getElementById('profile-role-badge');
    const previewRoleType = document.getElementById('profile-role-type');
    const previewStatus   = document.getElementById('profile-status-badge');
    const previewDot      = document.getElementById('profile-status-dot');

    if (!form) return;

    // ── Helper: Notificación Toast Flotante ─────────────────────────────────────
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
        if (!name || name.trim().length === 0) return 'NU';
        const parts = name.trim().split(/\s+/);
        let res = parts[0].charAt(0).toUpperCase();
        if (parts.length > 1) {
            res += parts[parts.length - 1].charAt(0).toUpperCase();
        }
        return res;
    }

    // ── 1. Validación de Nombre Completo ────────────────────────────────────────
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
            if (previewName) previewName.textContent = inputName.value.trim() || 'Nuevo Usuario';
            if (previewInitials) previewInitials.textContent = getInitials(inputName.value);
        });
        inputName.addEventListener('blur', validateName);
    }

    // ── 2. Validación de Correo Electrónico ─────────────────────────────────────
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
            fbEmail.textContent = 'Ingresa un correo con formato válido (ej. usuario@dominio.com).';
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

    // ── 3. Validación de Rol Asignado ───────────────────────────────────────────
    function validateRole() {
        if (!selectRole || !selectRole.value) {
            selectRole.classList.add('is-invalid');
            selectRole.classList.remove('is-valid');
            if (roleDescHint) {
                roleDescHint.textContent = 'Debes seleccionar un rol para el usuario.';
                roleDescHint.className = 'form-hint text-danger';
            }
            return false;
        } else {
            selectRole.classList.remove('is-invalid');
            selectRole.classList.add('is-valid');
            return true;
        }
    }

    function updateRolePreview() {
        if (!selectRole) return;
        const selectedOption = selectRole.options[selectRole.selectedIndex];
        if (selectedOption && selectRole.value) {
            const roleName = selectedOption.text.split('(')[0].trim();
            const roleDesc = selectedOption.dataset.desc || '';
            const roleType = selectedOption.dataset.type || 'standard';

            if (previewRole) previewRole.textContent = roleName;
            if (previewRoleType) previewRoleType.textContent = roleType.charAt(0).toUpperCase() + roleType.slice(1);
            if (roleDescHint) {
                roleDescHint.textContent = roleDesc ? `Perfil: ${roleDesc}` : '';
                roleDescHint.className = 'form-hint';
            }
        } else {
            if (previewRole) previewRole.textContent = 'Sin Rol';
            if (previewRoleType) previewRoleType.textContent = 'Por Definir';
        }
    }

    if (selectRole) {
        selectRole.addEventListener('change', () => {
            validateRole();
            updateRolePreview();
        });
        if (selectRole.value) updateRolePreview();
    }

    // ── 4. Validación de Contraseñas y Medidor de Seguridad ─────────────────────
    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 9) score++;
        if (/[A-Z]/.test(password) && /[0-9]/.test(password)) score++;
        return score;
    }

    function validatePassword() {
        const pass = inputPassword.value;
        if (pass.length === 0) {
            inputPassword.classList.add('is-invalid');
            inputPassword.classList.remove('is-valid');
            fbPassword.textContent = 'La contraseña inicial es obligatoria.';
            fbPassword.className = 'form-feedback is-error';
            if (strengthMeter) strengthMeter.className = 'strength-meter';
            return false;
        } else if (pass.length < 6) {
            inputPassword.classList.add('is-invalid');
            inputPassword.classList.remove('is-valid');
            fbPassword.textContent = 'La contraseña debe contener al menos 6 caracteres.';
            fbPassword.className = 'form-feedback is-error';
            if (strengthMeter) strengthMeter.className = 'strength-meter weak';
            return false;
        } else {
            inputPassword.classList.remove('is-invalid');
            inputPassword.classList.add('is-valid');
            fbPassword.textContent = '';
            fbPassword.className = 'form-feedback';

            const strength = checkPasswordStrength(pass);
            if (strengthMeter) {
                if (strength <= 1) strengthMeter.className = 'strength-meter weak';
                else if (strength === 2) strengthMeter.className = 'strength-meter medium';
                else strengthMeter.className = 'strength-meter strong';
            }
            return true;
        }
    }

    function validateConfirm() {
        const pass = inputPassword.value;
        const conf = inputConfirm.value;

        if (conf.length === 0) {
            inputConfirm.classList.add('is-invalid');
            inputConfirm.classList.remove('is-valid');
            fbConfirm.textContent = 'Por favor confirma la contraseña.';
            fbConfirm.className = 'form-feedback is-error';
            return false;
        } else if (conf !== pass) {
            inputConfirm.classList.add('is-invalid');
            inputConfirm.classList.remove('is-valid');
            fbConfirm.textContent = 'Las contraseñas no coinciden.';
            fbConfirm.className = 'form-feedback is-error';
            return false;
        } else {
            inputConfirm.classList.remove('is-invalid');
            inputConfirm.classList.add('is-valid');
            fbConfirm.textContent = 'Las contraseñas coinciden correctamente.';
            fbConfirm.className = 'form-feedback';
            return true;
        }
    }

    if (inputPassword) {
        inputPassword.addEventListener('input', () => {
            validatePassword();
            if (inputConfirm.value.length > 0) validateConfirm();
        });
        inputPassword.addEventListener('blur', validatePassword);
    }

    if (inputConfirm) {
        inputConfirm.addEventListener('input', validateConfirm);
        inputConfirm.addEventListener('blur', validateConfirm);
    }

    // ── 5. Switch de Estado Inicial ─────────────────────────────────────────────
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

    // ── 6. Toggle de Ojo para Contraseñas ───────────────────────────────────────
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

    // ── 7. Botón Limpiar Campos ─────────────────────────────────────────────────
    if (btnClear) {
        btnClear.addEventListener('click', () => {
            form.reset();
            [inputName, inputEmail, selectRole, inputPassword, inputConfirm].forEach(el => {
                if (el) el.classList.remove('is-valid', 'is-invalid');
            });
            [fbName, fbEmail, fbPassword, fbConfirm].forEach(el => {
                if (el) el.textContent = '';
            });
            if (strengthMeter) strengthMeter.className = 'strength-meter';
            if (previewName) previewName.textContent = 'Nuevo Usuario';
            if (previewEmail) previewEmail.textContent = 'correo@dominio.com';
            if (previewInitials) previewInitials.textContent = 'NU';
            if (previewRole) previewRole.textContent = 'Sin Rol';
            if (previewRoleType) previewRoleType.textContent = 'Por Definir';
            if (previewStatus) {
                previewStatus.textContent = 'Activo';
                previewStatus.className = 'badge-status badge-status--active';
            }
            if (previewDot) previewDot.className = 'profile-status-indicator is-online';
            if (switchLabel) switchLabel.textContent = 'Cuenta Activa';
            if (roleDescHint) roleDescHint.textContent = '';
            showToast('Formulario limpiado.', 'success');
        });
    }

    // ── 8. Envío de Formulario con Validación y AJAX ────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Ejecutar todas las validaciones
        const isNameOk = validateName();
        const isEmailOk = validateEmail();
        const isRoleOk = validateRole();
        const isPassOk = validatePassword();
        const isConfOk = validateConfirm();

        if (!isNameOk || !isEmailOk || !isRoleOk || !isPassOk || !isConfOk) {
            showToast('Por favor completa todos los campos requeridos correctamente.', 'danger');
            
            // Foco en el primer campo inválido
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) firstInvalid.focus();
            return;
        }

        // Estado de carga en el botón
        btnSubmit.disabled = true;
        if (createSpinner) createSpinner.style.display = 'inline-block';
        if (btnSubmitText) btnSubmitText.textContent = 'Registrando...';

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
                showToast(result.message || 'Usuario creado exitosamente.', 'success');
                
                // Redirigir suavemente a la edición del nuevo usuario o recargar
                const newId = result.id;
                setTimeout(() => {
                    if (newId) {
                        window.location.href = `/admin/usuarios/editar?id=${newId}`;
                    } else {
                        window.location.reload();
                    }
                }, 1200);

            } else {
                showToast(result.message || 'Error al registrar el usuario.', 'danger');
            }

        } catch (error) {
            showToast('Error de conexión con el servidor. Inténtalo nuevamente.', 'danger');
        } finally {
            btnSubmit.disabled = false;
            if (createSpinner) createSpinner.style.display = 'none';
            if (btnSubmitText) btnSubmitText.textContent = 'Registrar Usuario';
        }
    });
});
