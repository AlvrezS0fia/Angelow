<?php
// dashboard.php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANGELOW - Mi Cuenta</title>
    <link rel="shortcut icon" href="imagenes/general/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="imagenes/general/logo.png" alt="ANGELOW" class="logo-img">
            <div class="logo-text">
                <span>ANGELOW</span>
                <span>MI CUENTA</span>
            </div>
        </div>
        
        <div class="user-menu">
            <div class="user-avatar" style="background-image: url('<?php echo $user['avatar']; ?>')"></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main>
        <div class="welcome-card">
            <h1>¡Bienvenido, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p>Disfruta de las mejores colecciones de ropa infantil</p>
            <div class="login-time">
                <i class="far fa-clock"></i> Último acceso: <?php echo $user['login_time']; ?>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-shopping-bag"></i>
                <h3>Mis Pedidos</h3>
                <div class="number">0</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-heart"></i>
                <h3>Favoritos</h3>
                <div class="number">0</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-tag"></i>
                <h3>Ofertas</h3>
                <div class="number">12</div>
            </div>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn">
                <i class="fas fa-store"></i> Ir a la tienda
            </a>
        </div>
    </main>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="imagenes/general/logo.png" alt="ANGELOW">
                <div class="footer-logo-text">ANGELOW</div>
                <p>Ropa infantil de calidad</p>
            </div>
            <div class="footer-section">
                <h3>Contacto</h3>
                <p>+57 300 123 4567</p>
                <p>info@angelow.com</p>
            </div>
            <div class="footer-section">
                <h3>Síguenos</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> ANGELOW. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>