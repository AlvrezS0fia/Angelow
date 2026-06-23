// ELEMENTOS DEL DOM
const messageBox = document.getElementById('messageBox');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const forgotForm = document.getElementById('forgotPasswordForm');
const tabs = document.querySelectorAll('.auth-tab');

// Normalizar APP_URL: eliminar barra final si existe (evita duplicación)
if (typeof APP_URL !== 'undefined' && APP_URL.endsWith('/')) {
    window.APP_URL = APP_URL.slice(0, -1);
}

// MANEJO DE TABS
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        const tabName = tab.dataset.tab;
        if (tabName === 'login') {
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
            forgotForm.classList.remove('active');
        } else {
            loginForm.classList.remove('active');
            registerForm.classList.add('active');
            forgotForm.classList.remove('active');
        }
        hideMessage();
    });
});

// MOSTRAR/OCULTAR CONTRASEÑA 
window.togglePassword = function(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
};

// VALIDACIÓN DE CONTRASEÑA 
const registerPassword = document.getElementById('registerPassword');
if (registerPassword) {
    registerPassword.addEventListener('input', validatePasswordStrength);
}

function validatePasswordStrength() {
    const password = registerPassword.value;
    const rules = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    
    const ruleLength = document.getElementById('ruleLength');
    const ruleUppercase = document.getElementById('ruleUppercase');
    const ruleNumber = document.getElementById('ruleNumber');
    const ruleSpecial = document.getElementById('ruleSpecial');
    
    if (ruleLength) ruleLength.className = rules.length ? 'valid' : 'invalid';
    if (ruleUppercase) ruleUppercase.className = rules.uppercase ? 'valid' : 'invalid';
    if (ruleNumber) ruleNumber.className = rules.number ? 'valid' : 'invalid';
    if (ruleSpecial) ruleSpecial.className = rules.special ? 'valid' : 'invalid';
    
    const strengthBars = document.querySelectorAll('.strength-bar');
    const validCount = Object.values(rules).filter(Boolean).length;
    
    strengthBars.forEach((bar, index) => {
        bar.className = 'strength-bar';
        if (index < validCount) {
            if (validCount <= 2) bar.classList.add('weak');
            else if (validCount === 3) bar.classList.add('medium');
            else bar.classList.add('strong');
        }
    });
    
    validatePasswordMatch();
}

// VALIDAR COINCIDENCIA DE CONTRASEÑAS 
const confirmPassword = document.getElementById('confirmPassword');
if (confirmPassword) {
    confirmPassword.addEventListener('input', validatePasswordMatch);
}

function validatePasswordMatch() {
    const password = document.getElementById('registerPassword');
    const confirm = document.getElementById('confirmPassword');
    const matchDiv = document.getElementById('passwordMatch');
    
    if (!password || !confirm || !matchDiv) return;
    
    if (confirm.value.length === 0) {
        matchDiv.style.display = 'none';
        return;
    }
    
    matchDiv.style.display = 'block';
    if (password.value === confirm.value) {
        matchDiv.className = 'password-match match-success';
        matchDiv.innerHTML = '✓ Las contraseñas coinciden';
    } else {
        matchDiv.className = 'password-match match-error';
        matchDiv.innerHTML = '✗ Las contraseñas no coinciden';
    }
}

// MOSTRAR MENSAJES 
function showMessage(message, type = 'error') {
    if (!messageBox) return;
    messageBox.style.display = 'flex';
    messageBox.className = `message-box ${type}`;
    messageBox.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    setTimeout(hideMessage, 5000);
}

function hideMessage() {
    if (messageBox) messageBox.style.display = 'none';
}

// LOGIN CON EMAIL - CORREGIDO (evita duplicar URL)
window.handleEmailLogin = async function() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const remember = document.getElementById('rememberLogin')?.checked || false;
    
    if (!email || !password) {
        showMessage('Por favor completa todos los campos');
        return;
    }
    
    if (!email.includes('@')) {
        showMessage('Ingresa un correo electrónico válido');
        return;
    }
    
    showLoading('loginButton', true);
    
    try {
        const response = await fetch(`${APP_URL}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, remember })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                let redirectUrl = APP_URL + '/';  // por defecto: inicio
                if (data.redirect) {
                    // Si es URL absoluta (http...), usarla directamente
                    if (data.redirect.startsWith('http')) {
                        redirectUrl = data.redirect;
                    } else {
                        // Si es relativa, concatenar limpiando barras
                        const cleanBase = APP_URL.replace(/\/$/, '');
                        const cleanRedirect = data.redirect.replace(/^\//, '');
                        redirectUrl = cleanBase + '/' + cleanRedirect;
                    }
                }
                window.location.href = redirectUrl;
            }, 1000);
        } else {
            showMessage(data.message);
            showLoading('loginButton', false);
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error de conexión. Intenta de nuevo.');
        showLoading('loginButton', false);
    }
};

// REGISTRO CON EMAIL
window.handleEmailRegister = async function() {
    const email = document.getElementById('registerEmail').value.trim();
    const nombre = document.getElementById('registerName').value.trim();
    const password = document.getElementById('registerPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    const terms = document.getElementById('terms')?.checked || false;
    const newsletter = document.getElementById('newsletter')?.checked || false;
    
    if (!email || !nombre || !password || !confirm) {
        showMessage('Por favor completa todos los campos');
        return;
    }
    
    if (!terms) {
        showMessage('Debes aceptar los términos y condiciones');
        return;
    }
    
    if (password !== confirm) {
        showMessage('Las contraseñas no coinciden');
        return;
    }
    
    if (password.length < 8) {
        showMessage('La contraseña debe tener al menos 8 caracteres');
        return;
    }
    
    if (!/[A-Z]/.test(password)) {
        showMessage('La contraseña debe contener al menos una mayúscula');
        return;
    }
    
    if (!/[0-9]/.test(password)) {
        showMessage('La contraseña debe contener al menos un número');
        return;
    }
    
    if (!/[^a-zA-Z0-9]/.test(password)) {
        showMessage('La contraseña debe contener al menos un carácter especial');
        return;
    }
    
    showLoading('registerButton', true);
    
    try {
        const response = await fetch(`${APP_URL}/auth/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, nombre, password, terms, newsletter })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = APP_URL + '/';
            }, 1500);
        } else {
            showMessage(data.message);
            showLoading('registerButton', false);
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error de conexión. Intenta de nuevo.');
        showLoading('registerButton', false);
    }
};

// RECUPERAR CONTRASEÑA
window.handleForgotPassword = async function() {
    const email = document.getElementById('forgotEmail').value.trim();
    
    if (!email) {
        showMessage('Por favor ingresa tu correo electrónico');
        return;
    }
    
    if (!email.includes('@')) {
        showMessage('Ingresa un correo electrónico válido');
        return;
    }
    
    showLoading('sendResetLink', true);
    
    try {
        const response = await fetch(`${APP_URL}/auth/forgot-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        
        showMessage(data.message, data.success ? 'success' : 'info');
        showLoading('sendResetLink', false);
        
        if (data.success) {
            setTimeout(() => {
                if (forgotForm) forgotForm.classList.remove('active');
                if (loginForm) loginForm.classList.add('active');
                const loginTab = document.querySelector('[data-tab="login"]');
                if (loginTab) loginTab.classList.add('active');
                document.getElementById('forgotEmail').value = '';
            }, 3000);
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error de conexión. Intenta de nuevo.');
        showLoading('sendResetLink', false);
    }
};

// CONTROL DE LOADING 
function showLoading(buttonId, show) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    const buttonText = button.querySelector('span');
    const loading = button.querySelector('.loading');
    
    if (show) {
        button.disabled = true;
        if (buttonText) buttonText.style.display = 'none';
        if (loading) loading.style.display = 'inline-block';
    } else {
        button.disabled = false;
        if (buttonText) buttonText.style.display = 'inline';
        if (loading) loading.style.display = 'none';
    }
}

// GOOGLE LOGIN
window.handleCredentialResponse = function(response) {
    console.log("Token recibido de Google");
    
    try {
        const base64Url = response.credential.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const userData = JSON.parse(atob(base64));
        
        const form = document.getElementById('google-login-form');
        const input = document.getElementById('google-user-data');
        
        if (form && input) {
            input.value = JSON.stringify(userData);
            form.action = `${APP_URL}/auth/google`;
            form.submit();
        } else {
            console.error("Formulario no encontrado");
            showMessage("Error al procesar el login con Google", "error");
        }
    } catch (e) {
        console.error("Error decodificando token:", e);
        showMessage("Error al procesar el login con Google", "error");
    }
};

// MODO DEMO (solo pruebas)
window.demoLogin = function() {
    const demoUser = {
        name: "Usuario Demo",
        email: "demo@angelow.com",
        picture: "https://ui-avatars.com/api/?name=Usuario+Demo&background=5E9DE6&color=fff",
        sub: "demo_" + Date.now()
    };
    
    const form = document.getElementById('google-login-form');
    const input = document.getElementById('google-user-data');
    
    if (form && input) {
        input.value = JSON.stringify(demoUser);
        form.action = `${APP_URL}/auth/google`;
        form.submit();
    } else {
        window.location.href = `${APP_URL}/auth/google?demo=1`;
    }
};

// OLVIDÉ MI CONTRASEÑA 
const forgotLink = document.getElementById('forgotPassword');
if (forgotLink) {
    forgotLink.addEventListener('click', (e) => {
        e.preventDefault();
        loginForm.classList.remove('active');
        registerForm.classList.remove('active');
        forgotForm.classList.add('active');
        tabs.forEach(t => t.classList.remove('active'));
    });
}

const backToLogin = document.getElementById('backToLogin');
if (backToLogin) {
    backToLogin.addEventListener('click', (e) => {
        e.preventDefault();
        forgotForm.classList.remove('active');
        loginForm.classList.add('active');
        const loginTab = document.querySelector('[data-tab="login"]');
        if (loginTab) loginTab.classList.add('active');
    });
}

// AÑO ACTUAL 
const currentYearSpan = document.getElementById('currentYear');
if (currentYearSpan) {
    currentYearSpan.textContent = new Date().getFullYear();
}

// VERIFICAR PROTOCOLO 
if (window.location.protocol === 'file:') {
    console.warn('⚠️ Debes usar http://localhost para que funcione Google Login');
}

// SOPORTE PARA ENTER KEY 
function setupEnterKey(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    if (input && button) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                button.click();
            }
        });
    }
}

setupEnterKey('loginEmail', 'loginButton');
setupEnterKey('loginPassword', 'loginButton');
setupEnterKey('registerEmail', 'registerButton');
setupEnterKey('registerName', 'registerButton');
setupEnterKey('registerPassword', 'registerButton');
setupEnterKey('confirmPassword', 'registerButton');
setupEnterKey('forgotEmail', 'sendResetLink');