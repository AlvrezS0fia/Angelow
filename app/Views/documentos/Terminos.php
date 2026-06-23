<?php
// La variable APP_URL está disponible globalmente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones - ANGELOW</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/terminos.css">
</head>
<body>

<header>
    <a href="<?= APP_URL ?>/" class="logo"> 
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>TÉRMINOS Y CONDICIONES</span>
        </div>
    </a>

    <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
    </div>
</header>

<div class="terms-container">

    <div class="welcome-banner">
        <h2>TÉRMINOS Y CONDICIONES</h2>
        <p>Ropa infantil con amor, estilo y transparencia. Conoce nuestras políticas para una experiencia segura y confiable.</p>
    </div>

    <div class="terms-header">
        <h1>TÉRMINOS Y CONDICIONES DE ANGELOW</h1>
        <p class="subtitle">Comercialización de artículos de ropa infantil</p>
    </div>

    <div class="company-info">
        <h2>INFORMACIÓN LEGAL DE ANGELOW</h2>
        <div class="info-grid">
            <div class="info-item">
                <h3>Empresa</h3>
                <p>ANGELOW Colombia S.A.S., sociedad legalmente constituida bajo las leyes de la República de Colombia, especializada en moda infantil de alta calidad.</p>
            </div>
            <div class="info-item">
                <h3>Ubicación</h3>
                <p>Medellín, Colombia. Carrera 45 #20-35, Piso 3, Medellín, Antioquia. Centro de distribución y atención al cliente.</p>
            </div>
            <div class="info-item">
                <h3>Datos Legales</h3>
                <p>NIT: 901.234.567-8 • Cámara de Comercio de Medellín: 123456 • RUT: 901234567-8-9</p>
            </div>
        </div>

        <div class="contact-highlight">
            <h3>CANALES DE ATENCIÓN AL CLIENTE</h3>
            <p style="color: var(--text-secondary); margin-bottom: 15px;">Estamos para ayudarte en cada paso. Contáctanos por cualquiera de estos canales:</p>
            
            <div class="contact-channels">
                <div class="contact-channel">
                    <i class="fas fa-comment-dots"></i>
                    <div class="contact-info">
                        <h4>Chat en línea</h4>
                        <p>En nuestro sitio web • 24/7 para consultas</p>
                    </div>
                </div>
                <div class="contact-channel">
                    <i class="fab fa-whatsapp"></i>
                    <div class="contact-info">
                        <h4>WhatsApp Business</h4>
                        <p>+57 313 595 1664 • Respuesta en minutos</p>
                    </div>
                </div>
                <div class="contact-channel">
                    <i class="fas fa-envelope"></i>
                    <div class="contact-info">
                        <h4>Correo electrónico</h4>
                        <p>servicio@angelow.com • Soporte especializado</p>
                    </div>
                </div>
                <div class="contact-channel">
                    <i class="fas fa-clock"></i>
                    <div class="contact-info">
                        <h4>Horario de atención</h4>
                        <p>Lun-Vie: 9:00-21:00 • Sáb: 10:00-18:00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="terms-nav" id="termsNav">
        <a href="#aceptacion" class="nav-link active">Aceptación</a>
        <a href="#servicios" class="nav-link">Servicios</a>
        <a href="#requisitos" class="nav-link">Requisitos</a>
        <a href="#privacidad" class="nav-link">Privacidad</a>
        <a href="#compras" class="nav-link">Compras</a>
        <a href="#devoluciones" class="nav-link">Devoluciones</a>
        <a href="#garantias" class="nav-link">Garantías</a>
    </div>

    <div class="terms-content">
        <div class="term-section" id="aceptacion">
            <h2>1. ACEPTACIÓN DE TÉRMINOS</h2>
            <p>Al acceder o utilizar nuestros servicios, usted acepta estar sujeto a estos Términos y Condiciones, nuestra Política de Privacidad y las políticas específicas de producto.</p>
            
            <div class="highlight">
                <p><strong>IMPORTANTE:</strong> Si eres menor de edad, estos términos deben ser aceptados por tu padre, madre o tutor legal. Nos especializamos en ropa infantil y valoramos la seguridad de los más pequeños.</p>
            </div>
            
            <h3>1.1. Modificaciones</h3>
            <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Las versiones actualizadas se publicarán en nuestro sitio web con fecha de última modificación. Te notificaremos sobre cambios importantes que puedan afectar tus derechos.</p>
        </div>

        <div class="term-section" id="servicios">
            <h2>2. NUESTROS SERVICIOS</h2>
            <p>ANGELOW es una plataforma especializada en <strong>moda infantil premium</strong> que ofrece:</p>
            
            <ul>
                <li><strong>Colecciones exclusivas:</strong> Ropa para bebés, niños y niñas de 0 a 12 años</li>
                <li><strong>Compras seguras:</strong> Proceso de compra con encriptación SSL de 256-bit</li>
                <li><strong>Guía de tallas:</strong> Sistema de medición preciso y asesoramiento personalizado</li>
                <li><strong>Logística confiable:</strong> Envíos a todo Colombia con seguimiento en tiempo real</li>
                <li><strong>Comunidad:</strong> Contenido educativo sobre cuidado infantil y moda</li>
            </ul>
            
            <div class="warning-box">
                <h4>Materiales y Seguridad</h4>
                <p>Todos nuestros productos cumplen con los más altos estándares de seguridad infantil. Utilizamos materiales hipoalergénicos, sin componentes tóxicos y con certificaciones internacionales de calidad textil.</p>
            </div>
        </div>

        <div class="term-section" id="requisitos">
            <h2>3. REQUISITOS DE USO</h2>
            
            <h3>3.1. Edad y Capacidad</h3>
            <p>Para realizar compras en ANGELOW, debes:</p>
            <ul>
                <li>Tener al menos <strong>18 años de edad</strong></li>
                <li>Ser padre, madre o tutor legal del menor para quien compras</li>
                <li>Contar con capacidad legal para celebrar contratos</li>
                <li>Proporcionar información veraz y completa</li>
            </ul>
            
            <h3>3.2. Cuenta de Usuario</h3>
            <p>Al crear una cuenta aceptas:</p>
            <ul>
                <li>Mantener tu información actualizada</li>
                <li>Proteger tus credenciales de acceso</li>
                <li>Notificarnos sobre uso no autorizado</li>
                <li>No crear múltiples cuentas fraudulentas</li>
            </ul>
        </div>

        <div class="term-section" id="privacidad">
            <h2>4. PRIVACIDAD Y PROTECCIÓN DE DATOS</h2>
            <p>En ANGELOW protegemos la privacidad de tus datos y especialmente la de los niños. Nuestra política completa está disponible en <a href="<?= APP_URL ?>/documentos/Politicas_Priv">Políticas de Privacidad</a>.</p>
            
            <h3>4.1. Datos que recopilamos</h3>
            <ul>
                <li><strong>Datos de contacto:</strong> Para envío y facturación</li>
                <li><strong>Medidas del niño/a:</strong> Para recomendaciones de talla</li>
                <li><strong>Preferencias:</strong> Para personalizar tu experiencia</li>
                <li><strong>Datos de pago:</strong> Procesados de forma segura por pasarelas certificadas</li>
            </ul>
            
            <div class="highlight">
                <p><strong>Compromiso con la infancia:</strong> Nunca comercializamos datos de menores. Toda información relacionada con niños se maneja con estrictos protocolos de seguridad y solo con fines de servicio.</p>
            </div>
        </div>

        <div class="term-section" id="compras">
            <h2>5. PROCESO DE COMPRA</h2>
            
            <h3>5.1. Realización del pedido</h3>
            <p>El proceso de compra incluye:</p>
            <ol>
                <li>Selección de productos y tallas</li>
                <li>Verificación de disponibilidad en tiempo real</li>
                <li>Confirmación de dirección de envío</li>
                <li>Selección de método de pago seguro</li>
                <li>Confirmación por correo electrónico</li>
            </ol>
            
            <h3>5.2. Precios y Pagos</h3>
            <ul>
                <li>Los precios incluyen IVA (19%)</li>
                <li>Los costos de envío se calculan según ubicación</li>
                <li>Aceptamos: Tarjetas crédito/débito, PSE, Nequi, Daviplata</li>
                <li>Los pagos se procesan en COP (Pesos Colombianos)</li>
            </ul>
            
            <h3>5.3. Envíos</h3>
            <p>Realizamos envíos a todo Colombia mediante aliados logísticos certificados. Los tiempos estimados son:</p>
            <ul>
                <li><strong>Medellín:</strong> 1-2 días hábiles</li>
                <li><strong>Otras ciudades principales:</strong> 3-5 días hábiles</li>
                <li><strong>Municipios:</strong> 5-10 días hábiles</li>
            </ul>
        </div>

        <div class="term-section" id="devoluciones">
            <h2>6. DEVOLUCIONES Y CAMBIOS</h2>
            
            <h3>6.1. Política de 30 días</h3>
            <p>Aceptamos devoluciones y cambios hasta 30 días después de la recepción, siempre que:</p>
            <ul>
                <li>El producto esté en estado original</li>
                <li>Conserve todas las etiquetas</li>
                <li>No haya sido usado o lavado</li>
                <li>Se presente la factura original</li>
            </ul>
            
            <h3>6.2. Proceso de cambio</h3>
            <ol>
                <li>Solicita el cambio en tu cuenta o contactándonos</li>
                <li>Empaca el producto con cuidado</li>
                <li>Programamos recogida gratuita</li>
                <li>Procesamos el cambio en 3-5 días hábiles</li>
            </ol>
            
            <div class="highlight">
                <p><strong>Productos personalizados:</strong> Los artículos con nombres bordados o personalizaciones especiales no son sujetos a devolución, excepto por defectos de fabricación.</p>
            </div>
        </div>

        <div class="term-section" id="garantias">
            <h2>7. GARANTÍAS Y CALIDAD</h2>
            
            <h3>7.1. Garantía de fabricación</h3>
            <p>Todos nuestros productos tienen garantía contra defectos de fabricación por 90 días a partir de la recepción. Cubrimos:</p>
            <ul>
                <li>Costuras deshechas</li>
                <li>Cremalleras defectuosas</li>
                <li>Teñido irregular</li>
                <li>Materiales no conformes</li>
            </ul>
            
            <h3>7.2. Cuidado de las prendas</h3>
            <p>Para garantizar la durabilidad, recomendamos:</p>
            <ul>
                <li>Lavado a mano o máquina en ciclo suave</li>
                <li>Uso de detergentes neutros</li>
                <li>Secado a la sombra</li>
                <li>Planchado a temperatura media</li>
            </ul>
            
            <div class="warning-box">
                <h4>Excepciones de garantía</h4>
                <p>La garantía no cubre daños por mal uso, lavado incorrecto, alteraciones no autorizadas o desgaste normal por el uso.</p>
            </div>
        </div>
    </div>

    <div class="cta-section">
        <h2>¿TIENES DUDAS SOBRE NUESTROS TÉRMINOS?</h2>
        <p>Nuestro equipo está listo para ayudarte a entender nuestras políticas y resolver cualquier inquietud sobre compras, tallas o garantías.</p>
        
        <div class="cta-buttons">
            <a href="<?= APP_URL ?>/" class="btn btn-primary">Volver a la Tienda</a>
            <a href="<?= APP_URL ?>/contacto" class="btn btn-secondary">Contactar Soporte</a>
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

<script src="<?= APP_URL ?>/assets/js/terminos.js"></script>
</body>
</html>