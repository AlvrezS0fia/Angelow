<?php
// Verificar si el usuario está logueado
if (!isset($_SESSION['user'])) {
    header('Location: ' . APP_URL . '/auth/login');
    exit();
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Angelow - Mapa y Seguimiento de Entregas</title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>const APP_URL = '<?= APP_URL ?>';</script>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/seguimiento.css">
</head>
<body>

  <!-- HEADER original -->
  <header>
    <div class="logo" onclick="window.location.href='<?= APP_URL ?>/'">
      <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
      <div class="logo-text">
        <span>ANGELOW</span>
        <span>SEGUIMIENTO</span>
      </div>
    </div>
    <div class="search-container">
      <img src="https://img.icons8.com/ios-filled/50/search--v1.png" class="search-icon" alt="Buscar">
      <input type="text" id="searchInput" class="search-input" placeholder="Buscar productos...">
      <button id="clearSearch" class="clear-search">×</button>
    </div>
    <div class="header-icons">
      <div class="icon-btn" id="cartBtnHeader">
        <img src="<?= APP_URL ?>/assets/imagenes/general/carro.png" alt="Carrito" style="width:24px;">
        <span id="cartCount" class="badge" style="display:none">0</span>
      </div>
      <div class="icon-btn" id="favBtnHeader">
        <img src="<?= APP_URL ?>/assets/imagenes/general/favoritos.png" alt="Favoritos" style="width:24px;">
        <span id="favHeaderBadge" class="badge" style="display:none">0</span>
      </div>
      <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="page-header">
      <h1 class="page-title">Rastrea tu Pedido</h1>
      <p class="page-subtitle">Sigue en tiempo real la ubicación de tu entrega y conoce el estado exacto de tu pedido</p>
    </div>
    <div class="tracking-wrapper">
      <!-- MAP CONTAINER -->
      <div class="map-container">
        <div id="map"></div>
        
        <!-- VENTANA FLOTANTE: Planificador de Ruta -->
        <div class="controls-panel collapsed" id="controlsPanel">
          <button class="controls-toggle-btn" id="controlsToggle" title="Planificador de ruta">
            <i class="fas fa-route"></i>
          </button>
          <div class="panel-header">
            <div class="panel-title">
              <i class="fas fa-route"></i>
              <h3>Planificador de Ruta</h3>
            </div>
            <div class="panel-actions">
              <button class="panel-action-btn" id="locateMe" title="Ubicar mi posición">
                <i class="fas fa-crosshairs"></i>
              </button>
              <button class="panel-close-btn" id="closePanel" title="Cerrar panel">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="panel-content">
            <!-- Punto de Origen -->
            <div class="input-group">
              <div class="input-header">
                <label class="input-label">
                  <i class="fas fa-map-marker-alt"></i> Punto de Origen
                </label>
                <button class="input-action" id="useCurrentLocation">
                  <img src="<?= APP_URL ?>/assets/imagenes/general/flechas.png" alt="Ubicación" style="width:16px; height:16px; margin-right:4px;">
                  Usar mi ubicación
                </button>
              </div>
              <div class="input-wrapper">
                <i class="input-icon fas fa-circle"></i>
                <input type="text" id="start-address" class="address-input" placeholder="Ubicación actual" readonly>
              </div>
            </div>
            <!-- Punto de Destino -->
            <div class="input-group">
              <div class="input-header">
                <label class="input-label">
                  <i class="fas fa-flag-checkered"></i> Punto de Destino
                </label>
                <span class="input-hint">*Requerido</span>
              </div>
              <div class="input-wrapper">
                <i class="input-icon fas fa-map-pin"></i>
                <input type="text" id="end-address" class="address-input" placeholder="Ingresa dirección de entrega">
              </div>
              <div class="address-suggestions">
                <div class="suggestion" data-address="Carrera 15 #88-64, Medellín">
                  <i class="fas fa-home"></i><span>Oficina Principal</span>
                </div>
                <div class="suggestion" data-address="Calle 100 #15-20, Medellín">
                  <i class="fas fa-store"></i><span>Tienda Angelow</span>
                </div>
              </div>
            </div>
            <!-- Actions -->
            <div class="route-actions">
              <button id="calculateRoute" class="btn btn-primary">
                <i class="fas fa-route"></i> Calcular Ruta
              </button>
              <div class="secondary-actions">
                <button id="clearRoute" class="btn btn-secondary" disabled>
                  <i class="fas fa-times"></i> Limpiar
                </button>
                <button id="saveRoute" class="btn btn-outline" disabled>
                  <i class="fas fa-bookmark"></i> Guardar
                </button>
              </div>
            </div>
            <!-- Información de Ruta -->
            <div class="route-info-card">
              <div class="info-card-header">
                <h4><i class="fas fa-info-circle"></i> Información de Ruta</h4>
              </div>
              <div class="info-grid">
                <div class="info-item highlighted">
                  <div class="info-icon"><i class="fas fa-road"></i></div>
                  <div class="info-details">
                    <span class="info-label">Distancia</span>
                    <span class="info-value" id="routeDistance">--</span>
                  </div>
                </div>
                <div class="info-item highlighted">
                  <div class="info-icon"><i class="fas fa-clock"></i></div>
                  <div class="info-details">
                    <span class="info-label">Tiempo</span>
                    <span class="info-value" id="routeTime">--</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Status Panel (abajo derecha) -->
        <div class="status-panel">
          <div class="status-header">
            <div class="status-title">
              <div class="status-indicator-container">
                <div class="status-indicator pulse status-waiting"></div>
                <span id="statusTitle">Estado: Esperando</span>
              </div>
              <button class="status-refresh" id="refreshStatus"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="status-meta"><span class="status-time" id="statusTime">Actualizado: ahora</span></div>
          </div>
          <div class="status-content">
            <div class="status-message"><i class="fas fa-info-circle"></i><p id="statusMessage">Ingresa una dirección para comenzar</p></div>
            <div class="detail-grid">
              <div class="detail-item"><i class="fas fa-user"></i><div><span class="detail-label">Repartidor</span><span class="detail-value" id="deliveryPerson">Asignando...</span></div></div>
              <div class="detail-item"><i class="fas fa-phone"></i><div><span class="detail-label">Contacto</span><span class="detail-value" id="deliveryContact">---</span></div></div>
            </div>
          </div>
        </div>
        
        <!-- GPS Indicator -->
        <div class="gps-indicator">
          <div class="gps-icon"><i class="fas fa-satellite"></i></div>
          <div class="gps-text"><span class="gps-status">GPS Activo</span><span class="gps-accuracy">Precisión: 15m</span></div>
        </div>
      </div>

      <!-- TRACKING PANEL (derecha) -->
      <div class="tracking-container">
        <div class="tracking-header">
          <div class="header-main"><h2><i class="fas fa-box-open"></i> Seguimiento</h2><div class="order-status-badge status-active"><i class="fas fa-circle"></i> Activo</div></div>
          <div class="header-secondary"><div class="order-meta"><div class="meta-item"><span class="meta-label">Pedido</span><span class="meta-value" id="orderNumber">001234</span></div><div class="meta-item"><span class="meta-label">Fecha</span><span class="meta-value" id="orderDate">Hoy, 14:30</span></div></div><button class="header-action" id="shareTracking"><i class="fas fa-share-alt"></i></button></div>
        </div>
        <div class="tracking-content">
          <div class="progress-timeline"><div class="timeline-header"><h3><i class="fas fa-history"></i> Progreso</h3><div class="timeline-progress"><div class="progress-bar"><div class="progress-fill" style="width: 0%"></div></div><span class="progress-text">0%</span></div></div><div class="timeline-steps"><div class="step"><div class="step-icon"><i class="fas fa-clipboard-check"></i></div><div class="step-content"><h4>Confirmado</h4><span class="step-time">--:--</span></div></div><div class="step"><div class="step-icon"><i class="fas fa-warehouse"></i></div><div class="step-content"><h4>Preparado</h4><span class="step-time">--:--</span></div></div><div class="step"><div class="step-icon"><i class="fas fa-shipping-fast"></i></div><div class="step-content"><h4>En Camino</h4><span class="step-time">--:--</span></div></div><div class="step"><div class="step-icon"><i class="fas fa-home"></i></div><div class="step-content"><h4>Entregado</h4><span class="step-time">--:--</span></div></div></div></div>
          <div class="driver-card"><div class="card-header"><h3><i class="fas fa-user-circle"></i> Repartidor</h3></div><div class="card-content"><div class="driver-profile"><div class="driver-avatar"><img src="https://ui-avatars.com/api/?name=Carlos+Rodriguez&background=5E9DE6&color=fff"></div><div class="driver-info"><h4 id="driverName">-</h4><div class="driver-rating"><div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div></div></div></div><div class="driver-contact"><button class="contact-btn call-btn"><i class="fas fa-phone"></i></button><button class="contact-btn message-btn"><i class="fas fa-comment"></i></button><button class="contact-btn location-btn"><i class="fas fa-map-marker-alt"></i></button></div></div></div>
          <div class="estimate-card"><div class="card-header"><h3><i class="fas fa-clock"></i> Estimación</h3></div><div class="card-content"><div class="estimate-main"><div class="estimate-time"><span class="time-value" id="estimatedTime">--</span><span class="time-unit">min</span></div></div><div class="estimate-details"><div class="detail"><i class="fas fa-road"></i><span><strong id="estimatedDistance">--</strong></span></div><div class="detail"><i class="fas fa-traffic-light"></i><span>Normal</span></div></div></div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- OVERLAYS de carrito y favoritos -->
  <div id="cartOverlay" class="overlay">
    <div class="cart">
      <div class="cart-header">
        <div><h2>Bolsa de compras</h2><p>Envío gratis</p></div>
        <button onclick="closeCart()" style="background:none;border:none;font-size:28px;cursor:pointer;">✕</button>
      </div>
      <div class="cart-content" id="cartItems"><div style="text-align:center;padding:100px 20px;color:#888;">Cargando...</div></div>
      <div class="cart-footer">
        <div class="total"><span>Total</span><span id="total">COP $0</span></div>
        <!-- BOTÓN MODIFICADO: redirige a la ruta MVC de compra -->
        <button onclick="window.location.href='<?= APP_URL ?>/compra'" class="checkout">FINALIZAR COMPRA</button>
      </div>
    </div>
  </div>
  <div id="favoritesOverlay" class="overlay">
    <div class="fav-sidebar">
      <div class="fav-header">
        <div><h2>Mis Favoritos (<span id="favTotal">0</span>)</h2><p>Envío gratis</p></div>
        <button id="closeFavorites" style="background:none;border:none;font-size:28px;cursor:pointer;">✕</button>
      </div>
      <div class="fav-content" id="favoritesList">Cargando...</div>
    </div>
  </div>

  <!-- FOOTER con enlaces corregidos -->
  <footer>
    <div class="footer-content">
      <div class="footer-logo"><img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW"><div class="footer-logo-text">ANGELOW</div><p>Ropa infantil de calidad</p></div>
      <div class="footer-section"><h3>Contacto</h3><p>+57 3135951664</p><p>info@angelow.com</p><p>Medellín, Colombia</p></div>
      <div class="footer-section"><h3>Ayuda</h3><ul class="footer-links"><li><a href="<?= APP_URL ?>/documentos/Pedidos_envios">Pedidos y Envíos</a></li><li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Devoluciones y Cambios</a></li><li><a href="<?= APP_URL ?>/documentos/Preguntas">Preguntas Frecuentes</a></li><li><a href="<?= APP_URL ?>/documentos/Guia_Tallas">Guía de Tallas</a></li><li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li></ul></div>
      <div class="footer-section"><h3>Legal</h3><ul class="footer-links"><li><a href="<?= APP_URL ?>/documentos/Politicas_Priv">Políticas de Privacidad</a></li><li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li><li><a href="<?= APP_URL ?>/documentos/Politicas_Env">Políticas de Envío</a></li><li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Políticas de Devolución</a></li></ul></div>
      <div class="footer-section"><h3>Síguenos</h3><div class="social-links"><a href="https://instagram.com/tuusuario" target="_blank"><img src="https://img.icons8.com/ios-filled/50/instagram-new.png" alt="Instagram"></a><a href="https://facebook.com/tuusuario" target="_blank"><img src="https://img.icons8.com/ios-filled/50/facebook-new.png" alt="Facebook"></a><a href="https://wa.me/573135951664" target="_blank"><img src="https://img.icons8.com/ios-filled/50/whatsapp.png" alt="WhatsApp"></a></div></div>
    </div>
    <div class="footer-bottom"><p>&copy; <span id="currentYear"></span> ANGELOW. Todos los derechos reservados.</p></div>
  </footer>

  <!-- SCRIPTS -->
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <script src="<?= APP_URL ?>/assets/js/seguimiento.js"></script>
</body>
</html>