<?php
// Verificar si el usuario está logueado (opcional, pero recomendado)
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
  <title>ANGELOW - Factura de Compra</title>
  <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/factura.css">
  <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>
<div class="invoice-wrapper">
  <div class="invoice-card">
    <!-- HEADER -->
    <div class="invoice-header">
      <div class="logo-area">
        <div class="logo-circle">
          <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" onerror="this.style.display='none'; this.parentElement.innerHTML='<span style=\'font-size:36px;font-weight:800;color:#5E9DE6;\'>A</span>'">
        </div>
        <div class="logo-text">
          <h1>ANGELOW</h1>
          <p>FACTURA ELECTRÓNICA</p>
        </div>
      </div>
      <div class="invoice-badge" id="invoiceBadge">
        PAGADA · FACTURA #<span id="facturaNumero"></span>
      </div>
    </div>

    <!-- INFORMACIÓN CLIENTE + TIENDA -->
    <div class="info-grid">
      <div class="info-card">
        <h3>TIENDA</h3>
        <div class="store-name">ANGELOW</div>
        <p>Ropa infantil de calidad</p>
        <p>NIT: 901.234.567-8</p>
        <p>Calle 123 #45-67, Medellín</p>
        <p class="highlight">info@angelow.com · +57 3135951664</p>
      </div>

      <div class="info-card" id="clienteInfo">
        <h3>CLIENTE</h3>
        <div class="detail-row"><span class="detail-label">Nombre:</span><span class="detail-value" id="clienteNombre"><?= htmlspecialchars($user['nombre'] ?? '') ?></span></div>
        <div class="detail-row"><span class="detail-label">C.C / NIT:</span><span class="detail-value" id="clienteCedula"><?= htmlspecialchars($user['cedula'] ?? '') ?></span></div>
        <div class="detail-row"><span class="detail-label">Correo:</span><span class="detail-value" id="clienteEmail"><?= htmlspecialchars($user['email'] ?? '') ?></span></div>
        <div class="detail-row"><span class="detail-label">Teléfono:</span><span class="detail-value" id="clienteTelefono"><?= htmlspecialchars($user['telefono'] ?? '') ?></span></div>
      </div>

      <div class="info-card">
        <h3>DETALLES DEL PAGO</h3>
        <div class="detail-row"><span class="detail-label">Fecha emisión:</span><span class="detail-value" id="fechaFactura"></span></div>
        <div class="detail-row"><span class="detail-label">Método de pago:</span><span class="detail-value" id="metodoPago">Mercado Pago · **** 4242</span></div>
        <div class="detail-row"><span class="detail-label">Estado:</span><span class="status-badge">Pagado</span></div>
      </div>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <div class="products-section">
      <h2>Detalle de compra</h2>
      <table class="invoice-table" id="productosTabla">
        <thead>
          <tr><th>Producto</th><th>Talla</th><th>Cant.</th><th>Precio</th><th>Total</th></tr>
        </thead>
        <tbody id="invoiceItemsBody">
          <!-- Los productos se cargarán dinámicamente con JS -->
        </tbody>
      </table>
    </div>

    <!-- RESUMEN + ENTREGA -->
    <div class="summary-section">
      <div class="delivery-box">
        <h3><span class="delivery-icon">📍</span> Dirección de envío</h3>
        <p id="direccionEnvio"></p>
        <p id="destinatarioEnvio" style="margin-top: 8px;"><strong>Destinatario:</strong></p>
        <hr style="margin: 16px 0; border-color: #E0E7F5;">
        <h3 style="margin-top: 8px;">Método de envío</h3>
        <p id="metodoEnvio"></p>
      </div>

      <div class="totals-card">
        <div class="totals-row"><span>Subtotal</span><span id="subtotalFactura">COP $0</span></div>
        <div class="totals-row"><span>Envío</span><span id="envioFactura">Gratis</span></div>
        <div class="totals-row discount" id="descuentoRow" style="display: none;"><span>Descuento (<span id="codigoDescuento"></span>)</span><span id="descuentoMonto">- COP $0</span></div>
        <div class="totals-row total"><span>Total</span><span id="totalFactura">COP $0</span></div>
      </div>
    </div>

    <!-- FOOTER Y ACCIONES -->
    <div class="invoice-footer">
      <div class="footer-links">
        <a href="<?= APP_URL ?>/documentos/Terminos.html">Términos y condiciones</a>
        <a href="<?= APP_URL ?>/documentos/Politicas_devolucion.html">Política de devoluciones</a>
        <a href="<?= APP_URL ?>/documentos/Preguntas.html">Soporte</a>
      </div>
      <div class="copyright">ANGELOW © <span id="currentYear"></span> - Factura electrónica válida</div>
    </div>

    <div class="action-buttons">
      <button class="btn btn-primary" onclick="window.print()">
        Imprimir / Guardar PDF
      </button>
      <button class="btn btn-secondary" onclick="window.location.href='<?= APP_URL ?>/'">
        Volver a la tienda
      </button>
    </div>
    <p style="text-align: center; font-size: 11px; color: #94A3B8; padding-bottom: 28px;">
      Esta factura se asimila a una factura electrónica según la Ley 527 de 1999.
      Para verificar su validez ingrese a factura.angelow.com/validar
    </p>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/factura.js" defer></script>
</body>
</html>