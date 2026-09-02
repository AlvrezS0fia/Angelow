<?php
// Verificar si el usuario está logueado, si no, redirigir al login
if (!isset($_SESSION['user'])) {
    header('Location: ' . APP_URL . '/auth/login');
    exit();
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ANGELOW - Finalizar Compra</title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/compra.css">
  <script>const APP_URL = '<?= APP_URL ?>';</script>
  <script>window.CURRENT_USER = <?= json_encode($user ?? null) ?>;</script>
  <script src="<?= APP_URL ?>/assets/js/compra.js" defer></script>
</head>
<body>

  <!-- TOAST CONTAINER -->
  <div class="toast-container" id="toastContainer"></div>

  <header>
  <div class="logo">
    <div class="header-left">
      <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
      <div class="logo-text">
        <span class="brand-name">ANGELOW</span>
        <span class="brand-sub">FINALIZAR COMPRA</span>
      </div>
    </div>
    <a href="<?= APP_URL ?>/" class="back-btn-header">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      Volver
    </a>
  </div>
</header>

  <div class="progress-container">
    <div class="progress-steps">
      <div class="progress-line" id="progressLine"></div>
      <div class="step active" id="step1">
        <div class="step-circle">1</div>
        <div class="step-label">Identificación</div>
      </div>
      <div class="step" id="step2">
        <div class="step-circle">2</div>
        <div class="step-label">Envío</div>
      </div>
      <div class="step" id="step3">
        <div class="step-circle">3</div>
        <div class="step-label">Pago</div>
      </div>
    </div>
  </div>

  <div class="checkout-container">
    <div class="main-content">
      
      <!-- STEP 1: IDENTIFICACIÓN -->
      <div class="step-content active" id="content1">
        
        <div class="form-card">
          <h2>Identificación</h2>
          <p style="color: var(--text-secondary); margin-bottom: 24px;">
            Completa tu información esencial para la finalización de la compra.
          </p>
          
          <div class="form-group full">
            <label class="required">Correo</label>
            <input type="email" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            <span class="error-message">Por favor ingresa un correo válido</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="required">Nombre</label>
              <input type="text" id="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="required">Apellidos</label>
              <input type="text" id="apellidos" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="required">Cédula de Ciudadanía</label>
              <input type="text" id="cedula" value="<?= htmlspecialchars($user['cedula'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="required">Teléfono / Móvil</label>
              <input type="tel" id="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" required>
            </div>
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="acceptTerms" required>
            <label for="acceptTerms">
              Autorizo a Estrategia Comercial de Colombia SAS para que el número de celular y correo electrónico sean contactados y me envíen información relacionada con mi pedido. Igualmente enviarme información de la marca como lanzamientos y promociones. Autorizo el uso de datos personales y <a href="<?= APP_URL ?>/documentos/Terminos.html" target="_blank">Términos y condiciones de uso del sitio web</a>.
            </label>
          </div>
        </div>

        <div class="button-group">
          <button class="btn btn-primary" onclick="goToStep(2)">IR PARA LA ENTREGA</button>
        </div>
      </div>

      <!-- STEP 2: ENVÍO -->
      <div class="step-content" id="content2">
        <a href="#" class="back-link" onclick="goToStep(1); return false;">← Volver</a>
        
        <div class="form-card">
          <h2>Envío</h2>
          
          <div class="form-row">
            <div class="form-group">
              <label class="required">Departamento</label>
              <select id="departamento" required>
                <option value="Antioquia">Antioquia</option>
              </select>
            </div>
            <div class="form-group">
              <label class="required">Municipio</label>
              <select id="municipio" required>
                <option value="Medellín">Medellín</option>
              </select>
            </div>
          </div>

          <div class="form-group full">
            <label class="required">Dirección completa</label>
            <input type="text" id="direccion" placeholder="Calle 123 #45-67" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Información adicional (apto, piso, etc)</label>
              <input type="text" id="infoAdicional" placeholder="Apto 301">
            </div>
            <div class="form-group">
              <label>Dirección Complementaria</label>
              <input type="text" id="direccionComplementaria" placeholder="Torre A">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="required">Barrio</label>
              <input type="text" id="barrio" placeholder="Tu barrio" required>
            </div>
            <div class="form-group">
              <label>Opcional</label>
              <input type="text" id="opcional" placeholder="Referencia">
            </div>
          </div>

          <div class="form-group full">
            <label>Destinatario</label>
            <input type="text" id="destinatario" placeholder="Nombre de quien recibe">
          </div>
        </div>

        <div class="form-card">
          <h2>Método de Envío</h2>
          <div class="shipping-options">
            <div class="shipping-option selected" onclick="selectShipping(this, 'normal')">
              <input type="radio" name="shipping" value="normal" checked>
              <div class="shipping-info">
                <h4>Normal</h4>
                <p>En todas 2 o más días hábiles</p>
              </div>
              <div class="shipping-price">Gratis</div>
            </div>
            <div class="shipping-option" onclick="selectShipping(this, 'express')">
              <input type="radio" name="shipping" value="express">
              <div class="shipping-info">
                <h4>Envío Express</h4>
                <p>1 día hábil con Coordinadora</p>
              </div>
              <div class="shipping-price">15.000 COP</div>
            </div>
          </div>
        </div>

        <div class="button-group">
          <button class="btn btn-secondary" onclick="goToStep(1)">Volver</button>
          <button class="btn btn-primary" onclick="goToStep(3)">CONTINUAR AL PAGO</button>
        </div>
      </div>

      <!-- STEP 3: PAGO -->
      <div class="step-content" id="content3">
        <a href="#" class="back-link" onclick="goToStep(2); return false;">← Volver</a>
        
        <div class="form-card">
          <h2>Pago</h2>
          
          <div class="payment-options">
            <div class="payment-option selected" onclick="selectPayment(this, 'pse')">
              <div class="payment-header">
                <input type="radio" name="payment" value="pse" checked>
                <h4>Único Valor / Botón de Pago</h4>
              </div>
              <p style="font-size: 13px; color: var(--text-secondary); margin-top: 8px;">
                Compra de forma segura con el medio de pago que prefieres.
              </p>
              <div class="payment-icons">
                <img src="https://img.icons8.com/color/48/visa.png" alt="Visa">
                <img src="https://img.icons8.com/color/48/mastercard.png" alt="Mastercard">
                <img src="https://img.icons8.com/color/48/amex.png" alt="Amex">
              </div>
            </div>

            <div class="payment-option" onclick="selectPayment(this, 'mercadopago')">
              <div class="payment-header">
                <input type="radio" name="payment" value="mercadopago">
                <img src="https://http2.mlstatic.com/storage/logos-api-admin/51b446b0-571c-11e8-9a2d-4b2bd7b1bf77-l.svg" alt="Mercado Pago" style="width: 120px;">
              </div>
              <p style="font-size: 13px; color: var(--text-secondary); margin-top: 8px;">
                Paga con tus tarjetas guardadas o dinero disponible sin compartir datos.
              </p>
            </div>
          </div>

          <div style="margin-top: 24px; padding: 20px; background: var(--bg-soft); border-radius: 16px; display: flex; align-items: center; gap: 12px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
              <p style="font-size: 14px; font-weight: 600; margin-bottom: 4px;">Pago con tus tarjetas guardadas</p>
              <p style="font-size: 13px; color: var(--text-secondary);">
                Si no tienes una cuenta, podrás crear sin compartir datos.
              </p>
            </div>
          </div>

          <div style="margin-top: 24px; display: flex; align-items: center; gap: 12px; font-size: 14px; color: var(--text-secondary);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
            </svg>
            <span>Tu información es segura con Mercado Pago</span>
          </div>

          <div style="margin-top: 24px; padding: 16px; background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--success); border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5zm0 0v8m0 4h.01M5 19h14"></path>
              </svg>
              <div>
                <p style="font-size: 14px; font-weight: 600; color: var(--success); margin-bottom: 4px;">Créditos</p>
                <p style="font-size: 13px; color: var(--text-secondary);">Compra a crédito fácil y seguro ¡sin papeleos!</p>
              </div>
            </div>
          </div>

          <div style="margin-top: 24px; padding: 16px; background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary); border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
              </svg>
              <div>
                <p style="font-size: 14px; font-weight: 600; color: var(--primary); margin-bottom: 4px;">Botón Bancolombia</p>
                <p style="font-size: 13px; color: var(--text-secondary);">Paga desde tu app sin salir de esta página</p>
              </div>
            </div>
          </div>
        </div>

        <div class="button-group">
          <button class="btn btn-secondary" onclick="goToStep(2)">Volver</button>
          <button class="btn btn-primary" onclick="completePurchase()">COMPLETAR COMPRA</button>
        </div>
      </div>

      <!-- SUCCESS MESSAGE -->
      <div class="step-content" id="contentSuccess">
        <div class="form-card">
          <div class="success-message">
            <div class="success-icon">
              <svg viewBox="0 0 24 24" fill="none">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <h2>¡Pedido Confirmado!</h2>
            <p>Tu compra ha sido procesada exitosamente</p>
            <div class="order-number">Pedido #<span id="orderNumber"></span></div>
            <p style="margin-bottom: 32px;">
              Hemos enviado un correo de confirmación a<br>
              <strong id="orderEmail"></strong>
            </p>
            <div class="button-group">
              <button class="btn btn-secondary" onclick="window.location.href='<?= APP_URL ?>/'">Volver a la tienda</button>
              <button class="btn btn-primary" onclick="window.location.href='<?= APP_URL ?>/perfil'">Ver mis pedidos</button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- SUMMARY SIDEBAR -->
    <aside class="summary-card">
      <h3>Resumen de la compra</h3>
      
      <div class="summary-items" id="summaryItems">
        <!-- Se llenará con JavaScript -->
      </div>

      <div class="promo-code">
        <h4>Cupón de descuento</h4>
        <div class="promo-input">
          <input type="text" id="promoInput" placeholder="Código">
          <button onclick="applyPromo()">APLICAR</button>
        </div>
      </div>

      <div class="summary-totals">
        <div class="summary-row">
          <span>Subtotal</span>
          <span id="summarySubtotal">COP $0</span>
        </div>
        <div class="summary-row" id="shippingRow">
          <span>Envío</span>
          <span id="summaryShipping">Gratis</span>
        </div>
        <div class="summary-row discount" id="discountRow" style="display: none;">
          <span>Descuento (<span id="discountCode"></span>)</span>
          <span id="summaryDiscount">- COP $0</span>
        </div>
        <div class="summary-row total">
          <span>Total</span>
          <span id="summaryTotal">COP $0</span>
        </div>
      </div>
    </aside>
  </div>

  <footer>
    <div class="footer-content">
      <div class="footer-logo">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" onerror="this.src='https://via.placeholder.com/100?text=ANGELOW'">
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
          <li><a href="<?= APP_URL ?>/documentos/Pedidos_envios.html">Pedidos y Envíos</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion.html">Devoluciones y Cambios</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Preguntas.html">Preguntas Frecuentes</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Guia_Tallas.html">Guía de Tallas</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Terminos.html">Devoluciones</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h3>Legal</h3>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/documentos/Politicas_Priv.php">Políticas de Privacidad</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Terminos.php">Términos y Condiciones</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Politicas_Env.php">Políticas de Envío</a></li>
          <li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion.php">Políticas de Devolución</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h3>Síguenos</h3>
        <div class="social-links">
          <a href="https://instagram.com/tuusuario" target="_blank"><img src="https://img.icons8.com/ios-filled/50/instagram-new.png" alt="Instagram"></a>
          <a href="https://facebook.com/tuusuario" target="_blank"><img src="https://img.icons8.com/ios-filled/50/facebook-new.png" alt="Facebook"></a>
          <a href="https://wa.me/573135951664" target="_blank"><img src="https://img.icons8.com/ios-filled/50/whatsapp.png" alt="WhatsApp"></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <span id="currentYear"></span> ANGELOW. Todos los derechos reservados.</p>
    </div>
  </footer>
</body>
</html>