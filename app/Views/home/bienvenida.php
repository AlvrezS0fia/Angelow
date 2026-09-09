<?php
// La variable $user es inyectada por HomeController
$user = $user ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>ANGELOW - INICIO INTERACTIVO | OFERTAS DINÁMICAS</title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bienvenida.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/carrusel.css">

  <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>

<script>
  (function() {
    const phpUser = <?php echo json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.phpUser = phpUser || null;
    if (phpUser) {
      const localUser = localStorage.getItem('angelow_user');
      if (!localUser || JSON.stringify(JSON.parse(localUser)) !== JSON.stringify(phpUser)) {
        localStorage.setItem('angelow_user', JSON.stringify(phpUser));
      }
      const userFavs = localStorage.getItem(`angelow_favorites_${phpUser.email}`) || localStorage.getItem(`angelow_favorites_${phpUser.id}`);
      if (userFavs && !localStorage.getItem('angelow_favorites')) {
        localStorage.setItem('angelow_favorites', userFavs);
      }
    } else {
      if (localStorage.getItem('angelow_user')) {
        localStorage.removeItem('angelow_user');
        localStorage.removeItem('angelow_favorites');
      }
    }
  })();
</script>

<div class="toast-container" id="toastContainer"></div>

<header>
  <div class="logo">
    <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
    <div class="logo-text">
      <span>ANGELOW</span>
      <span>INICIO</span>
    </div>
  </div>
  <div class="search-container">
    <img src="https://img.icons8.com/ios-filled/50/search--v1.png" class="search-icon" alt="Buscar">
    <input type="text" id="searchInput" class="search-input" placeholder="Buscar productos...">
    <button id="clearSearch" class="clear-search">×</button>
  </div>
  <div class="header-icons">
    <a href="<?= MICROSERVICE_URL ?>" class="btn-gold ser-repartidor-btn" style="padding:10px 22px;font-size:.9rem;margin:0;white-space:nowrap;">Ser Repartidor</a>
    <div class="icon-btn" id="contactBtn"><a href="<?= APP_URL ?>/contactenos"><img src="<?= APP_URL ?>/assets/imagenes/general/contactenos.png" alt="Contacto" style="width:24px;"></a></div>
    <div class="icon-btn" id="cartBtnHeader"><img src="<?= APP_URL ?>/assets/imagenes/general/carro.png" alt="Carrito" style="width:24px;"><span id="cartCount" class="badge" style="display:none"></span></div>
    <div class="icon-btn" id="favBtnHeader"><img src="<?= APP_URL ?>/assets/imagenes/general/favoritos.png" alt="Favoritos" style="width:24px;"><span id="favHeaderBadge" class="badge" style="display:none">0</span></div>
    <div class="icon-btn" id="profileBtn"><img src="<?= APP_URL ?>/assets/imagenes/general/avatar.png" alt="Perfil" style="width:24px;"><div class="dropdown-menu" id="dropdownMenu"><a href="<?= APP_URL ?>/perfil" class="dropdown-item" id="loginLink">Mi perfil</a><a href="#" class="dropdown-item" id="openFavoritesFromMenu"><span>Mis Favoritos</span><span id="favBadge" class="badge" style="position:static; margin-left:auto; display:none;">0</span></a></div></div>
  </div>
</header>

<!-- CARRUSEL HERO MEJORADO (CON MÁS OFERTAS) -->
<section class="hero-carousel" id="heroCarouselSection">
  <div class="carousel-container" id="heroCarouselContainer"></div>
</section>

<div class="categories"><div class="categories-inner" id="categoriesList"></div></div>
<section><div class="products-grid" id="productsGrid"></div></section>

<!--  NUEVA SECCIÓN: OFERTAS RELÁMPAGO INTERACTIVAS  -->
<section class="flash-deals-section" id="flashDealsSection">
  <h2 class="section-title">OFERTAS RELÁMPAGO</h2>
  <p class="section-sub">Stock limitado con tiempo descendente por producto</p>
  <div class="offers-grid" id="dynamicOffersGrid"></div>
  
  <!-- Widget interactivo: Scratch para descuento -->
  <div class="scratch-card">
    <h3>Rasca y gana descuento</h3>
    <div class="scratch-surface" id="scratchSurface">
      <div class="scratch-text" id="scratchText">Rasca aquí</div>
    </div>
    <div class="scratch-coupon" id="couponResult"></div>
  </div>
</section>

<div class="product-detail-overlay" id="productDetail"><button class="detail-close" onclick="closeProductDetail()">✕</button><div class="product-detail" id="productDetailContent"></div></div>
<div id="cartOverlay" class="overlay"><div class="cart"><div class="cart-header"><div><h2>Bolsa de compras</h2><p>Envío gratis</p></div><button onclick="closeCart()" style="background:none;border:none;font-size:28px;cursor:pointer;">✕</button></div><div class="cart-content" id="cartItems"><div style="text-align:center;padding:100px 20px;color:#888;">Tu carrito está vacío</div></div><div class="cart-footer"><div class="total"><span>Total</span><span id="total">COP $0</span></div><button onclick="proceedToCheckout()" class="checkout" id="checkoutBtn">FINALIZAR COMPRA</button></div></div></div>
<div id="favoritesOverlay" class="overlay"><div class="fav-sidebar"><div class="fav-header"><div><h2>Mis Favoritos (<span id="favTotal">0</span>)</h2><p>Envío gratis</p></div><button id="closeFavorites" style="background:none;border:none;font-size:28px;cursor:pointer;">✕</button></div><div class="fav-content" id="favoritesList"></div></div></div>

<footer>
  <div class="footer-content">
    <div class="footer-logo"><img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW"><div class="footer-logo-text">ANGELOW</div><p>Ropa infantil de calidad</p></div>
    <div class="footer-section"><h3>Contacto</h3><p>+57 3135951664</p><p>info@angelow.com</p><p>Medellín, Colombia</p></div>
    <div class="footer-section"><h3>Ayuda</h3><ul class="footer-links"><li><a href="<?= APP_URL ?>/documentos/Pedidos_envios">Pedidos y Envíos</a></li><li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Devoluciones y Cambios</a></li><li><a href="<?= APP_URL ?>/documentos/Preguntas">Preguntas Frecuentes</a></li><li><a href="<?= APP_URL ?>/documentos/Guia_Tallas">Guía de Tallas</a></li><li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li></ul></div>
    <div class="footer-section"><h3>Legal</h3><ul class="footer-links"><li><a href="<?= APP_URL ?>/documentos/Politicas_Priv">Políticas de Privacidad</a></li><li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li><li><a href="<?= APP_URL ?>/documentos/Politicas_Env">Políticas de Envío</a></li><li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Políticas de Devolución</a></li></ul></div>
    <div class="footer-section"><h3>Síguenos</h3><div class="social-links"><a href="#"><img src="https://img.icons8.com/ios-filled/50/instagram-new.png" alt="IG"></a><a href="#"><img src="https://img.icons8.com/ios-filled/50/facebook-new.png" alt="FB"></a><a href="#"><img src="https://img.icons8.com/ios-filled/50/whatsapp.png" alt="WA"></a></div></div>
  </div>
  <div class="footer-bottom"><p>&copy; <span id="currentYear"></span> ANGELOW. Todos los derechos reservados.</p></div>
</footer>

<!-- Solo estos scripts -->
<script src="<?= APP_URL ?>/assets/chatbot/botpress.js"></script>
<script src="<?= APP_URL ?>/assets/js/bienvenida.js"></script>
<script src="<?= APP_URL ?>/assets/js/carrusel.js"></script>
</body>
</html>