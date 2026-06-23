<?php
// La variable APP_URL está disponible globalmente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - ANGELOW</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/politicas_priv.css">
</head>
<body>

<header>
    <a href="<?= APP_URL ?>/" class="logo"> 
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>PRIVACIDAD Y DATOS</span>
        </div>
    </a>

    <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
    </div>
</header>

<div class="terms-container">

    <div class="welcome-banner">
        <h2>POLÍTICA DE PRIVACIDAD Y PROTECCIÓN DE DATOS</h2>
        <p>Protegemos la información de nuestros clientes y sus familias con los más altos estándares de seguridad</p>
    </div>

    <div class="terms-header">
        <h1>COMPROMISO CON TU PRIVACIDAD</h1>
        <p>En cumplimiento de la normativa colombiana vigente para la protección de datos personales</p>
    </div>

    <div class="terms-nav">
        <a href="#responsable" class="nav-link active">Responsable</a>
        <a href="#datos" class="nav-link">Datos Recopilados</a>
        <a href="#finalidad" class="nav-link">Finalidad</a>
        <a href="#menores" class="nav-link">Protección Menores</a>
        <a href="#derechos" class="nav-link">Tus Derechos</a>
        <a href="#seguridad" class="nav-link">Seguridad</a>
        <a href="#legal" class="nav-link">Marco Legal</a>
    </div>

    <div class="terms-content">
        <!-- SECCIÓN 1: Responsable -->
        <div class="term-section" id="responsable">
            <h2>1. RESPONSABLE DEL TRATAMIENTO DE DATOS</h2>
            <p><strong>ANGELOW</strong> - Tienda Online de Ropa Infantil, identificada con NIT 901.234.567-8, domiciliada en Medellín, Colombia, actúa como responsable del tratamiento de los datos personales recopilados a través de nuestra tienda online.</p>
            
            <div class="highlight-box">
                <h3>Contacto para Datos Personales</h3>
                <p><strong>Email:</strong> privacidad@angelow.com</p>
                <p><strong>Teléfono:</strong> +57 313 595 1664</p>
                <p><strong>Horario:</strong> Lunes a Viernes, 8:00 AM - 6:00 PM</p>
            </div>
        </div>

        <!-- SECCIÓN 2: Datos recopilados -->
        <div class="term-section" id="datos">
            <h2>2. DATOS PERSONALES RECOPILADOS</h2>
            <p>Para ofrecer nuestros servicios, ANGELOW puede recopilar los siguientes tipos de información:</p>
            
            <h3>Datos del titular (padre, madre o tutor legal):</h3>
            <ul>
                <li>Nombre completo y documento de identidad</li>
                <li>Correo electrónico y número de teléfono</li>
                <li>Dirección de envío y facturación</li>
                <li>Información de pago (procesada de forma segura por terceros)</li>
            </ul>

            <h3>Datos del menor (opcional):</h3>
            <ul>
                <li>Nombre y edad (únicamente para personalización de productos)</li>
                <li>Tallas y preferencias de productos</li>
            </ul>

            <div class="legal-reference">
                <strong>Base Legal:</strong> Ley 1581 de 2012.
            </div>
        </div>

        <!-- SECCIÓN 3: Finalidad -->
        <div class="term-section" id="finalidad">
            <h2>3. FINALIDAD DEL TRATAMIENTO</h2>
            <p>Los datos personales recopilados serán utilizados para las siguientes finalidades:</p>
            <ol>
                <li>Procesamiento de pedidos: gestionar compras, pagos, envíos y entregas.</li>
                <li>Atención al cliente: responder consultas, solicitudes de cambio o devolución.</li>
                <li>Comunicaciones comerciales: enviar información sobre promociones y novedades (previa autorización).</li>
                <li>Mejora del servicio: análisis de preferencias para optimizar nuestra oferta.</li>
                <li>Cumplimiento legal y seguridad: prevenir fraudes.</li>
            </ol>
        </div>

        <!-- SECCIÓN 4: Protección menores -->
        <div class="term-section" id="menores">
            <h2>4. PROTECCIÓN ESPECIAL DE DATOS DE MENORES</h2>
            <div class="highlight-box">
                <h3>Compromiso con la Infancia</h3>
                <p>ANGELOW reconoce que los datos de niños, niñas y adolescentes gozan de protección especial y se compromete a tratarlos con el máximo cuidado.</p>
            </div>
            <ul>
                <li>Solo recopilamos datos de menores cuando es estrictamente necesario.</li>
                <li>Nunca solicitamos datos sensibles de menores.</li>
                <li>El tratamiento requiere autorización previa del representante legal.</li>
                <li>No compartimos ni vendemos datos de menores a terceros.</li>
            </ul>
            <div class="legal-reference">
                <strong>Marco Legal:</strong> Ley 1098 de 2006 (Código de Infancia y Adolescencia).
            </div>
        </div>

        <!-- SECCIÓN 5: Derechos del titular -->
        <div class="term-section" id="derechos">
            <h2>5. DERECHOS DEL TITULAR</h2>
            <p>Como titular de datos personales, usted tiene derecho a:</p>
            <ol>
                <li>Conocer, actualizar y rectificar sus datos.</li>
                <li>Solicitar prueba de la autorización.</li>
                <li>Ser informado sobre el uso de sus datos.</li>
                <li>Revocar la autorización y solicitar la supresión.</li>
                <li>Acceder gratuitamente a sus datos.</li>
                <li>Presentar quejas ante la Superintendencia de Industria y Comercio (SIC).</li>
            </ol>
            <div class="highlight-box">
                <h3>Cómo ejercer tus derechos</h3>
                <p>Envía una solicitud a <strong>privacidad@angelow.com</strong> con tu nombre completo, descripción clara y correo de contacto. Respuesta en máximo 15 días hábiles.</p>
            </div>
        </div>

        <!-- SECCIÓN 6: Seguridad -->
        <div class="term-section" id="seguridad">
            <h2>6. MEDIDAS DE SEGURIDAD</h2>
            <ul>
                <li>Cifrado SSL/TLS en todas las transmisiones.</li>
                <li>Acceso restringido a personal autorizado.</li>
                <li>Respaldos periódicos.</li>
                <li>Actualización constante de sistemas.</li>
                <li>Aliados comerciales certificados.</li>
            </ul>
        </div>

        <!-- SECCIÓN 7: Marco Legal -->
        <div class="term-section" id="legal">
            <h2>7. MARCO LEGAL COLOMBIANO</h2>
            <div class="legal-reference">
                <strong>Ley 1581 de 2012</strong> – Protección de Datos Personales.
            </div>
            <div class="legal-reference">
                <strong>Decreto 1074 de 2015</strong> – Reglamentación del sector comercio.
            </div>
            <div class="legal-reference">
                <strong>Ley 1098 de 2006</strong> – Código de Infancia y Adolescencia.
            </div>
            <div class="legal-reference">
                <strong>Ley 1480 de 2011</strong> – Estatuto del Consumidor.
            </div>
            <p><strong>Autoridad de Control:</strong> Superintendencia de Industria y Comercio (SIC).</p>
        </div>

        <!-- Sección adicional: Aceptación -->
        <div class="term-section" id="aceptacion">
            <h2>8. ACEPTACIÓN DE LA POLÍTICA</h2>
            <p>Al utilizar nuestro sitio web, usted declara haber leído, comprendido y aceptado los términos de esta Política de Privacidad.</p>
            <div class="highlight-box">
                <h3>Consentimiento Informado</h3>
                <p>Al realizar una compra o registrarse, otorga su consentimiento libre, previo, expreso e informado para el tratamiento de sus datos conforme a esta política.</p>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <span id="currentYear"></span> ANGELOW - Tienda Online de Ropa Infantil</p>
    <p>Todos los derechos reservados</p>
    <div class="footer-links">
        <a href="<?= APP_URL ?>/documentos/Politicas_Priv">Política de Privacidad</a>
        <a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a>
        <a href="<?= APP_URL ?>/documentos/Pedidos_envios">Pedidos y Envíos</a>
        <a href="<?= APP_URL ?>/contacto">Contacto</a>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/politicas_priv.js"></script>
</body>
</html>