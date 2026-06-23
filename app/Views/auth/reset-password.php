<?php
// Verificar que el token existe
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: ' . APP_URL . '/auth/login');
    exit;
}

// URL del logo (ahora en public/img/)
$logo_url = APP_URL . '/img/logos.png';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Angelow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 520px;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
        }
        
        .header {
            background: #ffffff;
            padding: 24px 32px;
            border-bottom: 1px solid #eef2f6;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-img {
            display: block;
            border-radius: 12px;
            width: 44px;
            height: 44px;
            object-fit: contain;
        }
        
        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .badge {
            background: #5e9de6;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 30px;
        }
        
        .hero {
            background: linear-gradient(135deg, #5e9de6 0%, #5e9de6 100%);
            padding: 48px 32px 40px;
            text-align: center;
        }
        
        .hero-logo {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .hero-logo-img {
            display: inline-block;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: white;
            padding: 10px;
            object-fit: contain;
        }
        
        .hero-title {
            margin: 0 0 12px;
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
        }
        
        .hero-subtitle {
            margin: 0;
            font-size: 16px;
            color: rgba(255,255,255,0.9);
        }
        
        .content {
            padding: 32px 32px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
        }
        
        input:focus {
            outline: none;
            border-color: #5e9de6;
            box-shadow: 0 0 0 3px rgba(94,157,230,0.1);
        }
        
        .password-hint {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
        }
        
        .button-container {
            text-align: center;
            margin: 32px 0 24px;
        }
        
        .btn-primary {
            display: inline-block;
            width: 100%;
            background: linear-gradient(135deg, #5e9de6 0%, #5e9de6 100%);
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            box-shadow: 0 4px 12px rgba(94,157,230,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            display: block;
        }
        
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            display: block;
        }
        
        .requisitos {
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 16px;
            margin: 20px 0 8px;
        }
        
        .requisitos p {
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .requisitos ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .requisitos li {
            font-size: 12px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .requisitos li.valid {
            color: #2e7d32;
        }
        
        .requisitos li.invalid {
            color: #991b1b;
        }
        
        .footer {
            background: #fafbfc;
            padding: 20px 32px 24px;
            text-align: center;
            border-top: 1px solid #eef2f6;
            margin-top: 20px;
        }
        
        .footer-text {
            margin: 0 0 12px;
            font-size: 12px;
            color: #94a3b8;
        }
        
        .footer-links {
            margin: 0;
        }
        
        .footer-link {
            color: #5e9de6;
            text-decoration: none;
            font-size: 12px;
            margin: 0 12px;
        }
        
        .copyright {
            margin: 16px 0 0;
            font-size: 11px;
            color: #cbd5e1;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- HEADER CON LOGO -->
    <div class="header">
        <div class="logo-container">
            <div class="logo">
                <img src="<?php echo $logo_url; ?>" alt="Angelow" class="logo-img">
                <span class="logo-text">Angelow</span>
            </div>
            <div class="badge">SEGURIDAD</div>
        </div>
    </div>

    <!-- HERO BANNER CON LOGO CENTRADO -->
    <div class="hero">
        <div class="hero-logo">
            <img src="<?php echo $logo_url; ?>" alt="Angelow" class="hero-logo-img">
        </div>
        <h1 class="hero-title">Restablecer contraseña</h1>
        <p class="hero-subtitle">Ingresa tu nueva contraseña para continuar</p>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="content">
        <div id="message" class="message"></div>

        <form id="resetPasswordForm">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label>Nueva Contraseña</label>
                <input type="password" id="newPassword" required placeholder="Ingresa tu nueva contraseña">
                <div class="password-hint">Mínimo 8 caracteres, una mayúscula, un número y un símbolo</div>
            </div>

            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <input type="password" id="confirmPassword" required placeholder="Repite la nueva contraseña">
            </div>

            <div class="requisitos">
                <p>🔒 Requisitos de seguridad:</p>
                <ul>
                    <li id="reqLength" class="invalid">✓ Mínimo 8 caracteres</li>
                    <li id="reqUppercase" class="invalid">✓ Al menos una mayúscula</li>
                    <li id="reqNumber" class="invalid">✓ Al menos un número</li>
                    <li id="reqSpecial" class="invalid">✓ Al menos un símbolo (!@#$%^&*)</li>
                    <li id="reqMatch" class="invalid">✓ Las contraseñas coinciden</li>
                </ul>
            </div>

            <div class="button-container">
                <button type="submit" class="btn-primary" id="submitBtn">Restablecer contraseña →</button>
            </div>
        </form>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p class="footer-text">Angelow — Tu tienda favorita</p>
        <p class="footer-links">
            <a href="<?php echo APP_URL; ?>" class="footer-link">🌐 Tienda</a>
            <a href="mailto:angelow.contacto@gmail.com" class="footer-link">📧 Soporte</a>
        </p>
        <p class="copyright">© <?php echo date('Y'); ?> Angelow. Todos los derechos reservados.</p>
    </div>
</div>

<script>
    const APP_URL = '<?php echo APP_URL; ?>';
    
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    function validatePassword() {
        const password = newPassword.value;
        const confirm = confirmPassword.value;
        
        const rules = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password),
            match: password === confirm && password.length > 0
        };
        
        document.getElementById('reqLength').className = rules.length ? 'valid' : 'invalid';
        document.getElementById('reqUppercase').className = rules.uppercase ? 'valid' : 'invalid';
        document.getElementById('reqNumber').className = rules.number ? 'valid' : 'invalid';
        document.getElementById('reqSpecial').className = rules.special ? 'valid' : 'invalid';
        document.getElementById('reqMatch').className = (rules.match && confirm.length > 0) ? 'valid' : 'invalid';
        
        return rules.length && rules.uppercase && rules.number && rules.special && rules.match;
    }
    
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
    
    document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const token = document.getElementById('token').value;
        const newPasswordValue = newPassword.value;
        const confirmPasswordValue = confirmPassword.value;
        
        if (!validatePassword()) {
            showMessage('La contraseña no cumple los requisitos de seguridad', 'error');
            return;
        }
        
        if (newPasswordValue !== confirmPasswordValue) {
            showMessage('Las contraseñas no coinciden', 'error');
            return;
        }
        
        showLoading(true);
        
        try {
            const response = await fetch(`${APP_URL}/auth/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: token,
                    password: newPasswordValue
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(data.message, 'success');
                setTimeout(() => {
                    window.location.href = APP_URL + '/auth/login';
                }, 2000);
            } else {
                showMessage(data.message, 'error');
                showLoading(false);
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Error de conexión. Intenta de nuevo.', 'error');
            showLoading(false);
        }
    });
    
    function showMessage(message, type) {
        const msgBox = document.getElementById('message');
        msgBox.textContent = message;
        msgBox.className = `message ${type}`;
        setTimeout(() => {
            if (msgBox.className === `message ${type}`) {
                msgBox.style.display = 'none';
                msgBox.className = 'message';
            }
        }, 5000);
    }
    
    function showLoading(show) {
        const btn = document.getElementById('submitBtn');
        if (show) {
            btn.textContent = 'Procesando...';
            btn.disabled = true;
        } else {
            btn.textContent = 'Restablecer contraseña →';
            btn.disabled = false;
        }
    }
</script>

</body>
</html>