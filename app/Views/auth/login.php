<?php
// Ya no se necesita session_start() porque lo hace el front controller
// Si ya está logueado, redirigir a la tienda
if (isset($_SESSION['user'])) {
    header('Location: ' . APP_URL . '/');
    exit();
}
// Configuración de Google (se puede mover a config/app.php si se desea)
$client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '518631585090-vel52de86h7lk3uetco3dc3schnqokh7.apps.googleusercontent.com';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANGELOW - Iniciar Sesión</title>
    <!-- Inyección de la URL base para JavaScript -->
<script>
    const APP_URL = "<?php echo rtrim(APP_URL, '/'); ?>";
    console.log('APP_URL desde PHP:', APP_URL); // Verifica que no tenga barra final
</script>    
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/login.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<!-- HEADER (igual que original, con APP_URL) -->
<header class="auth-header">
    <a href="<?= APP_URL ?>/" class="auth-logo">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="auth-logo-img">
        <div class="auth-logo-text">
            <span>ANGELOW</span>
            <span>PEDIDOS</span>
        </div>
    </a>
    <a href="<?= APP_URL ?>/" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        Volver al inicio
    </a>
</header>

<main class="auth-main">
    <!-- LADO IZQUIERDO - INFORMACIÓN -->
    <div class="auth-left">
        <div class="auth-left-content">
            <h1>Bienvenido a la familia Angelow</h1>
            <p>Accede a tu cuenta para descubrir las mejores colecciones de ropa infantil, ofertas exclusivas y una experiencia de compra personalizada.</p>
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-shipping-fast"></i></div>
                    <div class="auth-feature-text"><h3>Envío Gratis</h3><p>En pedidos superiores a $150.000</p></div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-heart"></i></div>
                    <div class="auth-feature-text"><h3>Lista de Favoritos</h3><p>Guarda tus productos favoritos</p></div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-percentage"></i></div>
                    <div class="auth-feature-text"><h3>Ofertas Exclusivas</h3><p>Solo para miembros registrados</p></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- LADO DERECHO - FORMULARIO  -->
    <div class="auth-right">
        <div class="auth-container">
            <h1 class="auth-title">Iniciar Sesión</h1>
            <p class="auth-subtitle">Accede a tu cuenta para continuar</p>
            <div id="messageBox" class="message-box" style="display: none;"></div>
            
            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">INICIAR SESIÓN</b/utton>
                <button class="auth-tab" data-tab="register">REGISTRARSE</button>
            </div> 
            
            <!-- Formulario de Login echo por samuel y sara -->
            <div id="loginForm" class="auth-form active">
                <div class="google-btn-wrapper">
                    <div id="g_id_onload"
                         data-client_id="<?= $client_id ?>"
                         data-context="signin"
                         data-ux_mode="popup"
                         data-callback="handleCredentialResponse"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin google-btn"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="signin_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="300">
                    </div>
                </div>
                <div class="divider"><span>o usa tu correo</span></div>
                <div class="form-group">
                    <label class="form-label required">CORREO ELECTRÓNICO</label>
                    <input type="email" class="form-input" id="loginEmail" placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label class="form-label required">CONTRASEÑA</label>
                    <div class="password-group">
                        <input type="password" class="form-input" id="loginPassword" placeholder="••••••••">
                        <button type="button" class="toggle-password" onclick="togglePassword('loginPassword', this)">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="rememberLogin">
                    <label for="rememberLogin">Recordar mi cuenta</label>
                </div>
                <button type="button" class="auth-button" id="loginButton" onclick="handleEmailLogin()">
                    <span id="loginButtonText">INICIAR SESIÓN</span>
                    <div id="loginLoading" class="loading" style="display: none;"></div>
                </button>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="#" id="forgotPassword" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>
                <!-- Botón demo (solo pruebas) -->
                <button type="button" class="demo-btn" onclick="demoLogin()">
                    <i class="fas fa-rocket"></i> Modo Demo (sin Google)
                </button>
            </div>
            
            <!-- Formulario de Registro -->
            <div id="registerForm" class="auth-form">
                <div class="google-btn-wrapper">
                    <div class="g_id_signin google-btn"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="signup_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="300">
                    </div>
                </div>
                <div class="divider"><span>o usa tu correo</span></div>
                <div class="form-group">
                    <label class="form-label required">CORREO ELECTRÓNICO</label>
                    <input type="email" class="form-input" id="registerEmail" placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label class="form-label required">NOMBRE COMPLETO</label>
                    <input type="text" class="form-input" id="registerName" placeholder="Tu nombre completo">
                </div>
                <div class="form-group">
                    <label class="form-label required">CONTRASEÑA</label>
                    <div class="password-group">
                        <input type="password" class="form-input" id="registerPassword" placeholder="••••••••">
                        <button type="button" class="toggle-password" onclick="togglePassword('registerPassword', this)">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar"></div><div class="strength-bar"></div>
                        <div class="strength-bar"></div><div class="strength-bar"></div>
                    </div>
                    <div class="password-rules">
                        <p>La contraseña debe contener:</p>
                        <ul>
                            <li id="ruleLength" class="invalid"><i class="far fa-circle"></i> Al menos 8 caracteres</li>
                            <li id="ruleUppercase" class="invalid"><i class="far fa-circle"></i> Una letra mayúscula</li>
                            <li id="ruleNumber" class="invalid"><i class="far fa-circle"></i> Un número</li>
                            <li id="ruleSpecial" class="invalid"><i class="far fa-circle"></i> Un carácter especial</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label required">CONFIRMAR CONTRASEÑA</label>
                    <div class="password-group">
                        <input type="password" class="form-input" id="confirmPassword" placeholder="••••••••">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" class="password-match"></div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="newsletter" checked>
                    <label for="newsletter">Me gustaría recibir noticias sobre productos y servicios de Angelow.</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="terms">
                    <label for="terms">He leído y acepto los <a href="<?= APP_URL ?>/documentos/Terminos.html">Términos y Condiciones</a> de Angelow.</label>
                </div>
                <button type="button" class="auth-button" id="registerButton" onclick="handleEmailRegister()">
                    <span id="registerButtonText">REGISTRARME</span>
                    <div id="registerLoading" class="loading" style="display: none;"></div>
                </button>
            </div>
            
            <!-- Formulario de Recuperación de Contraseña -->
            <div id="forgotPasswordForm" class="auth-form">
                <div class="form-group">
                    <h3 style="text-align: center; margin-bottom: 20px;">Recuperar Contraseña</h3>
                    <p style="text-align: center; margin-bottom: 20px;">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
                </div>
                <div class="form-group">
                    <label class="form-label required">CORREO ELECTRÓNICO</label>
                    <input type="email" class="form-input" id="forgotEmail" placeholder="tu@email.com">
                </div>
                <button type="button" class="auth-button" id="sendResetLink" onclick="handleForgotPassword()">
                    <span id="resetButtonText">ENVIAR ENLACE</span>
                    <div id="resetLoading" class="loading" style="display: none;"></div>
                </button>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="#" id="backToLogin" class="forgot-link">← Volver al inicio de sesión</a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer class="auth-footer">
    <div class="footer-content">
        <div class="footer-logo">
            <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW">
            <div class="footer-logo-text">ANGELOW</div>
            <p>Ropa infantil de calidad con amor y estilo</p>
        </div>
        <div class="footer-section">
            <h3>Contacto</h3>
            <p>+57 300 123 4567</p>
            <p>info@angelow.com</p>
            <p>Bogotá, Colombia</p>
        </div>
        <div class="footer-section">
            <h3>Síguenos</h3>
            <div class="social-links">
                <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="#" target="_blank"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <span id="currentYear"></span> ANGELOW. Todos los derechos reservados.</p>
    </div>
</footer>

<!-- Formulario oculto para enviar datos a Google (ruta MVC) -->
<form id="google-login-form" method="POST" action="<?= APP_URL ?>/auth/google" style="display: none;">
    <input type="hidden" name="user_data" id="google-user-data">
</form>

<script src="<?= APP_URL ?>/assets/js/login.js"></script>
</body>
</html>
