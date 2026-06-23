<?php
$user = $user ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ANGELOW - Contáctenos</title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/contactenos.css">
  <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>

<header>
  <div class="logo" onclick="window.location.href='<?= APP_URL ?>/'">
    <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
    <div class="logo-text">
      <span>ANGELOW</span>
      <span>CONTÁCTENOS</span>
    </div>
  </div>
  <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
    <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
  </div>
</header>

<main class="main-contact">
  <div class="contact-header">
    <h1>Contáctenos</h1>
    <p>Estamos aquí para escucharte. Envíanos un mensaje o comunícate a través de nuestras redes sociales.</p>
  </div>

  <div class="contact-grid">
    <!-- Formulario -->
    <div class="form-card">
      <h2><i class="fas fa-paper-plane"></i> Envíanos un mensaje</h2>
      <form id="contactForm">
        <div class="input-group">
          <label>Nombre completo <span class="required">*</span></label>
          <div class="input-icon-wrapper">
            <i class="fas fa-user"></i>
            <input type="text" id="nombre" placeholder="Tu nombre completo">
          </div>
          <div class="error-msg" id="err-nombre">El nombre es obligatorio (mínimo 3 caracteres).</div>
        </div>

        <div class="input-group">
          <label>Correo electrónico <span class="required">*</span></label>
          <div class="input-icon-wrapper">
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" placeholder="ejemplo@correo.com">
          </div>
          <div class="error-msg" id="err-email">Ingresa un correo electrónico válido.</div>
        </div>

        <div class="input-group">
          <label>Teléfono <span class="required">*</span></label>
          <div class="input-icon-wrapper">
            <i class="fas fa-phone-alt"></i>
            <input type="tel" id="telefono" placeholder="+57 300 123 4567">
          </div>
          <div class="error-msg" id="err-telefono">Teléfono inválido (mínimo 7 dígitos).</div>
        </div>

        <div class="input-group">
          <label>Asunto <span class="required">*</span></label>
          <div class="input-icon-wrapper">
            <i class="fas fa-tag"></i>
            <input type="text" id="asunto" placeholder="¿Sobre qué nos contactas?">
          </div>
          <div class="error-msg" id="err-asunto">El asunto es obligatorio (mínimo 4 caracteres).</div>
        </div>

        <div class="input-group">
          <label>Mensaje <span class="required">*</span></label>
          <div class="input-icon-wrapper textarea-icon">
            <i class="fas fa-comment-dots"></i>
            <textarea id="mensaje" rows="5" placeholder="Escribe tu mensaje aquí..."></textarea>
          </div>
          <div class="error-msg" id="err-mensaje">El mensaje es obligatorio (mínimo 10 caracteres).</div>
        </div>

        <button type="submit" class="btn-submit">Enviar mensaje <i class="fas fa-arrow-right"></i></button>
        <div id="formMessage" class="form-message" style="display:none;"></div>
      </form>
    </div>

    <!-- Columna derecha: mapa, info y redes -->
    <div class="info-col">
      <!-- Mapa circular -->
      <div class="map-circle">
        <iframe
          class="map-frame"
          src="https://www.openstreetmap.org/export/embed.html?bbox=-74.1200%2C4.5800%2C-74.0200%2C4.6800&layer=mapnik&marker=4.6097%2C-74.0817"
          allowfullscreen
          title="Mapa de ubicación"
        ></iframe>
        <div class="map-label">
          <i class="fas fa-map-marker-alt"></i> Medellín, Antioquia
        </div>
      </div>

      <!-- Tarjetas de información -->
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
          <div class="info-content">
            <span class="info-label">Teléfono</span>
            <span class="info-value">+57 313 595 1664</span>
          </div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="fas fa-envelope"></i></div>
          <div class="info-content">
            <span class="info-label">Correo</span>
            <span class="info-value">info@angelow.com</span>
          </div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="info-content">
            <span class="info-label">Dirección</span>
            <span class="info-value">Calle 123 #45-67, Medellín</span>
          </div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="fas fa-clock"></i></div>
          <div class="info-content">
            <span class="info-label">Horario</span>
            <span class="info-value">Lun–Vie 9am–6pm<br>Sáb 10am–2pm</span>
          </div>
        </div>
      </div>

      <!-- Redes sociales -->
      <div class="socials-card">
        <h3>Síguenos</h3>
        <div class="social-icons">
          <a href="#" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
          <a href="#" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
        </div>
      </div>
    </div>
  </div>
</main>

<footer>
  <div class="footer-content">
    <div class="footer-logo">
      <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW">
      <div class="footer-logo-text">ANGELOW</div>
      <p>Ropa infantil de calidad</p>
    </div>
    <div class="footer-section">
      <h3>Contacto</h3>
      <p>+57 3135951664</p>
      <p>info@angelow.com</p>
      <p>Medellín, Colombia</p>
    </div>
    <div class="footer-section">
      <h3>Ayuda</h3>
      <ul class="footer-links">
        <li><a href="<?= APP_URL ?>/documentos/Pedidos_envios">Pedidos y Envíos</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Devoluciones y Cambios</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Preguntas">Preguntas Frecuentes</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Guia_Tallas">Guía de Tallas</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li>
      </ul>
    </div>
    <div class="footer-section">
      <h3>Legal</h3>
      <ul class="footer-links">
        <li><a href="<?= APP_URL ?>/documentos/Politicas_Priv">Políticas de Privacidad</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Politicas_Env">Políticas de Envío</a></li>
        <li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Políticas de Devolución</a></li>
      </ul>
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
    <p>&copy; <span id="currentYear"></span> ANGELOW. Todos los derechos reservados.</p>
  </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/contactenos.js"></script>
</body>
</html>