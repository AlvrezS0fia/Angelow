<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas de Pedidos y Envíos - ANGELOW</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pedidos_envios.css">
    <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>

<header>
    <a href="<?= APP_URL ?>/" class="logo"> 
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>PEDIDOS Y ENVÍOS</span>
        </div>
    </a>

    <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
    </div>
</header>

<div class="terms-container">

    <div class="welcome-banner">
        <h2>POLÍTICAS DE PEDIDOS Y ENVÍOS</h2>
        <p>Información clara y detallada sobre el proceso de compra, despacho y entrega de productos.</p>
    </div>

    <div class="terms-header">
        <h1>GESTIÓN DE PEDIDOS Y ENVÍOS</h1>
        <p>Aplicable a todas las compras realizadas a través de www.angelow.com</p>
    </div>

    <div class="terms-nav">
        <a href="#pedido" class="nav-link active">Realización del Pedido</a>
        <a href="#confirmacion" class="nav-link">Confirmación</a>
        <a href="#procesamiento" class="nav-link">Procesamiento</a>
        <a href="#envio" class="nav-link">Envíos</a>
        <a href="#entrega" class="nav-link">Entrega</a>
        <a href="#incidencias" class="nav-link">Incidencias</a>
    </div>

    <div class="terms-content">

        <div class="term-section" id="pedido">
            <h2>1. REALIZACIÓN DEL PEDIDO</h2>
            <p>Para efectuar un pedido, el cliente debe seleccionar los productos deseados, verificar tallas, cantidades y características, y agregarlos al carrito de compra.</p>
            <p>Antes de confirmar el pedido, se debe revisar cuidadosamente la información de envío, datos personales y método de pago seleccionado. La confirmación implica la aceptación total de las condiciones comerciales vigentes.</p>
        </div>

        <div class="term-section" id="confirmacion">
            <h2>2. CONFIRMACIÓN DEL PEDIDO</h2>
            <p>Una vez realizado el pago, el sistema enviará un correo electrónico de confirmación con el número de pedido y el resumen de la compra.</p>
            <p>El cliente debe conservar dicho comprobante para cualquier consulta o solicitud relacionada con el proceso de envío o garantía.</p>
        </div>

        <div class="term-section" id="procesamiento">
            <h2>3. PROCESAMIENTO</h2>
            <p>Los pedidos se procesan en días hábiles. El tiempo estimado de preparación es de 1 a 2 días hábiles después de la confirmación del pago.</p>
            <p>En caso de presentarse inconvenientes con inventario o validación de pago, el cliente será notificado oportunamente para definir la solución correspondiente.</p>
        </div>

        <div class="term-section" id="envio">
            <h2>4. ENVÍOS</h2>
            <p>Los envíos se realizan a todo el territorio nacional mediante operadores logísticos autorizados.</p>
            <ul>
                <li>Ciudades principales: 2 a 5 días hábiles.</li>
                <li>Municipios y zonas apartadas: 5 a 10 días hábiles.</li>
            </ul>
            <p>Los tiempos son estimados y pueden variar por factores externos como condiciones climáticas o restricciones logísticas.</p>
        </div>

        <div class="term-section" id="entrega">
            <h2>5. ENTREGA DEL PRODUCTO</h2>
            <p>La entrega se realizará en la dirección registrada por el cliente. Es responsabilidad del comprador asegurar que la información proporcionada sea correcta y completa.</p>
            <p>En caso de ausencia en el domicilio, el operador logístico podrá realizar intentos adicionales según su política interna.</p>
        </div>

        <div class="term-section" id="incidencias">
            <h2>6. INCIDENCIAS Y RECLAMOS</h2>
            <p>Si el pedido llega incompleto, en mal estado o con errores, el cliente debe reportarlo dentro de las primeras 48 horas posteriores a la entrega.</p>
            <p>Para presentar una reclamación, se debe enviar evidencia fotográfica y número de pedido a través de los canales oficiales de atención.</p>
        </div>

    </div>

</div>

<footer>
    <p>&copy; <span id="currentYear"></span> ANGELOW. Todos los derechos reservados.</p>
    <div class="footer-links">
        <a href="<?= APP_URL ?>/documentos/Pedidos_envios">Pedidos y Envíos</a>
        <a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Devoluciones y Cambios</a>
        <a href="<?= APP_URL ?>/documentos/Preguntas">Preguntas Frecuentes</a>
        <a href="<?= APP_URL ?>/documentos/Guia_Tallas">Guía de Tallas</a>
        <a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/pedidos_envios.js"></script>

</body>
</html>