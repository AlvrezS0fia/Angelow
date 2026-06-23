<?php
// La variable APP_URL está disponible globalmente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas de Envío - ANGELOW</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/politicas_env.css">
    <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>

<header>
    <a href="<?= APP_URL ?>/" class="logo">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>POLÍTICAS DE ENVÍO</span>
        </div>
    </a>

    <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
    </div>
</header>

<div class="terms-container">

    <div class="welcome-banner">
        <h2>POLÍTICAS DE ENVÍO</h2>
        <p>Información detallada sobre costos, tiempos y procedimientos de envío a todo Colombia</p>
    </div>

    <div class="terms-header">
        <h1>ENVÍOS A TODO EL PAÍS</h1>
        <p>Conoce nuestros métodos de envío, tiempos de entrega y condiciones de despacho</p>
    </div>

    <div class="terms-nav">
        <a href="#cobertura" class="nav-link active">Cobertura</a>
        <a href="#costos" class="nav-link">Costos</a>
        <a href="#tiempos" class="nav-link">Tiempos</a>
        <a href="#proceso" class="nav-link">Proceso</a>
        <a href="#seguimiento" class="nav-link">Seguimiento</a>
        <a href="#condiciones" class="nav-link">Condiciones</a>
        <a href="#problemas" class="nav-link">Problemas</a>
    </div>

    <div class="terms-content">

        <div class="success-box">
            <h4>Envío Gratis en Compras Superiores a $150.000</h4>
            <p>Disfruta de envío gratuito a cualquier destino en Colombia cuando tu compra supere los ciento cincuenta mil pesos. Esta promoción aplica automáticamente al finalizar tu compra.</p>
        </div>

        <div class="term-section" id="cobertura">
            <h2>1. COBERTURA NACIONAL</h2>
            <p>ANGELOW realiza envíos a todo el territorio nacional colombiano, garantizando que nuestros productos lleguen a cada rincón del país.</p>

            <div class="highlight-box">
                <h3>Zonas de Cobertura</h3>
                <ul>
                    <li><strong>Ciudades principales:</strong> Bogotá, Medellín, Cali, Barranquilla, Cartagena, Bucaramanga, Pereira, Cúcuta, Manizales, Santa Marta, Ibagué, Villavicencio, Pasto, Armenia, entre otras.</li>
                    <li><strong>Ciudades intermedias:</strong> Todas las capitales de departamento y ciudades con población superior a 50.000 habitantes.</li>
                    <li><strong>Municipios pequeños:</strong> Localidades de menor tamaño con cobertura de nuestros operadores logísticos.</li>
                    <li><strong>Zonas rurales:</strong> Veredas y corregimientos con acceso logístico disponible.</li>
                </ul>
            </div>

            <div class="important-box">
                <h4>Zonas con Restricciones</h4>
                <p>Algunas zonas muy apartadas o de difícil acceso pueden tener limitaciones en tiempos de entrega o requerir costos adicionales. Esto incluye algunas áreas de la Amazonía, Orinoquía y zonas de alta montaña. Te informaremos durante el proceso de compra si tu dirección tiene alguna restricción.</p>
            </div>

            <div class="carrier-grid">
                <div class="carrier-card">
                    <div class="carrier-logo">SERVIENTREGA</div>
                    <div class="carrier-name">Servientrega</div>
                    <div class="carrier-info">Cobertura nacional completa con tiempos de entrega competitivos</div>
                </div>
                <div class="carrier-card">
                    <div class="carrier-logo">COORDINADORA</div>
                    <div class="carrier-name">Coordinadora</div>
                    <div class="carrier-info">Amplia red de distribución en todo el país</div>
                </div>
                <div class="carrier-card">
                    <div class="carrier-logo">INTERRAPIDÍSIMO</div>
                    <div class="carrier-name">Interrapidísimo</div>
                    <div class="carrier-info">Especialistas en envíos rápidos y seguros</div>
                </div>
                <div class="carrier-card">
                    <div class="carrier-logo">DEPRISA</div>
                    <div class="carrier-name">Deprisa</div>
                    <div class="carrier-info">Envíos confiables a nivel nacional</div>
                </div>
            </div>
        </div>

        <div class="term-section" id="costos">
            <h2>2. COSTOS DE ENVÍO</h2>
            <p>Los costos de envío varían según el destino de entrega y el valor total de la compra. A continuación, detallamos nuestra estructura de precios:</p>

            <div class="price-table">
                <h3>Tarifas por Destino</h3>
                <div class="price-row"><span class="price-location">Compras superiores a $150.000</span><span class="price-amount price-free">ENVÍO GRATIS</span></div>
                <div class="price-row"><span class="price-location">Bogotá D.C.</span><span class="price-amount">$10.000 COP</span></div>
                <div class="price-row"><span class="price-location">Medellín</span><span class="price-amount">$12.000 COP</span></div>
                <div class="price-row"><span class="price-location">Cali</span><span class="price-amount">$13.000 COP</span></div>
                <div class="price-row"><span class="price-location">Barranquilla</span><span class="price-amount">$14.000 COP</span></div>
                <div class="price-row"><span class="price-location">Cartagena</span><span class="price-amount">$15.000 COP</span></div>
                <div class="price-row"><span class="price-location">Bucaramanga, Pereira, Cúcuta, Manizales</span><span class="price-amount">$13.000 - $15.000 COP</span></div>
                <div class="price-row"><span class="price-location">Otras ciudades principales</span><span class="price-amount">$15.000 - $18.000 COP</span></div>
                <div class="price-row"><span class="price-location">Ciudades intermedias</span><span class="price-amount">$18.000 - $22.000 COP</span></div>
                <div class="price-row"><span class="price-location">Municipios pequeños</span><span class="price-amount">$22.000 - $28.000 COP</span></div>
                <div class="price-row"><span class="price-location">Zonas rurales y apartadas</span><span class="price-amount">$28.000 - $40.000 COP</span></div>
            </div>

            <div class="tip-box">
                <h4>Cálculo Automático</h4>
                <p>El costo exacto de envío se calculará automáticamente al ingresar tu dirección de destino durante el proceso de compra. Podrás ver el monto final antes de confirmar tu pedido.</p>
            </div>
        </div>

        <div class="term-section" id="tiempos">
            <h2>3. TIEMPOS DE ENTREGA</h2>
            <p>Los tiempos de entrega son estimados y comienzan a contar desde el momento en que el pedido es despachado (no desde la confirmación de compra). Estos tiempos son calculados en días hábiles, excluyendo sábados, domingos y festivos.</p>

            <div class="time-table">
                <div class="time-card"><h4>Ciudades Principales</h4><span class="time-days">2-5</span><div class="time-info">días hábiles<br>Bogotá, Medellín, Cali, Barranquilla, Cartagena, Bucaramanga</div></div>
                <div class="time-card"><h4>Ciudades Intermedias</h4><span class="time-days">4-7</span><div class="time-info">días hábiles<br>Capitales de departamento y ciudades medianas</div></div>
                <div class="time-card"><h4>Municipios Pequeños</h4><span class="time-days">5-10</span><div class="time-info">días hábiles<br>Localidades de menor tamaño con cobertura logística</div></div>
                <div class="time-card"><h4>Zonas Rurales</h4><span class="time-days">7-15</span><div class="time-info">días hábiles<br>Áreas apartadas y de difícil acceso</div></div>
            </div>

            <div class="important-box">
                <h4>Factores que Pueden Afectar los Tiempos</h4>
                <p>Los tiempos de entrega son estimados y pueden verse afectados por condiciones climáticas adversas, temporadas de alta demanda, paros o restricciones de movilidad, festividades, situaciones de orden público, o restricciones viales.</p>
            </div>
        </div>

        <div class="term-section" id="proceso">
            <h2>4. PROCESO DE ENVÍO</h2>
            <p>Nuestro proceso de envío está diseñado para ser transparente y eficiente. Conoce cada etapa:</p>
            <div class="process-steps">
                <div class="step-item"><div class="step-number">1</div><div class="step-content"><h4>Confirmación del Pedido</h4><p>Recibirás un correo de confirmación con el número de pedido.</p></div></div>
                <div class="step-item"><div class="step-number">2</div><div class="step-content"><h4>Verificación de Pago</h4><p>El sistema verifica automáticamente el pago.</p></div></div>
                <div class="step-item"><div class="step-number">3</div><div class="step-content"><h4>Preparación del Pedido</h4><p>Recogemos y verificamos los productos.</p></div></div>
                <div class="step-item"><div class="step-number">4</div><div class="step-content"><h4>Empaque Seguro</h4><p>Empacamos cuidadosamente con materiales de calidad.</p></div></div>
                <div class="step-item"><div class="step-number">5</div><div class="step-content"><h4>Despacho y Guía</h4><p>Entregamos al transportador y generamos número de guía.</p></div></div>
                <div class="step-item"><div class="step-number">6</div><div class="step-content"><h4>Tránsito</h4><p>El paquete viaja a través de la red de distribución.</p></div></div>
                <div class="step-item"><div class="step-number">7</div><div class="step-content"><h4>Última Milla</h4><p>El mensajero local realiza la entrega.</p></div></div>
                <div class="step-item"><div class="step-number">8</div><div class="step-content"><h4>Entrega Final</h4><p>Entrega en la dirección registrada.</p></div></div>
            </div>
        </div>

        <div class="term-section" id="seguimiento">
            <h2>5. SEGUIMIENTO DE ENVÍOS</h2>
            <p>Ofrecemos seguimiento completo de tu pedido a través de varios canales:</p>
            <div class="highlight-box">
                <h3>Cómo Hacer Seguimiento</h3>
                <ul>
                    <li>Correo electrónico con número de guía.</li>
                    <li>Página web de ANGELOW en "Mis Pedidos".</li>
                    <li>Sitio web del transportador.</li>
                    <li>WhatsApp y chat en línea.</li>
                </ul>
            </div>
        </div>

        <div class="term-section" id="condiciones">
            <h2>6. CONDICIONES DE ENTREGA</h2>
            <p>Para garantizar una entrega exitosa, ten en cuenta:</p>
            <ul>
                <li>Dirección completa y correcta.</li>
                <li>Teléfono de contacto actualizado.</li>
                <li>Receptor mayor de edad con documento de identidad.</li>
                <li>Verificar el paquete antes de firmar.</li>
            </ul>
            <div class="important-box">
                <h4>Intentos de Entrega</h4>
                <p>Se realizan hasta tres intentos. Si no hay respuesta, el paquete queda disponible en la oficina del transportador por 5 días hábiles antes de ser devuelto.</p>
            </div>
        </div>

        <div class="term-section" id="problemas">
            <h2>7. PROBLEMAS CON EL ENVÍO</h2>
            <p>Si tienes algún problema, contáctanos a través de:</p>
            <ul>
                <li>Email: envios@angelow.com</li>
                <li>WhatsApp: +57 (xxx) xxx-xxxx</li>
                <li>Teléfono: +57 (xxx) xxx-xxxx</li>
            </ul>
            <div class="success-box">
                <h4>Garantía de Satisfacción</h4>
                <p>Trabajaremos contigo para resolver cualquier inconveniente.</p>
            </div>
        </div>

        <div class="term-section" id="adicional">
            <h2>8. INFORMACIÓN ADICIONAL</h2>
            <div class="highlight-box">
                <h3>Días y Horarios de Despacho</h3>
                <p>Pedidos antes de las 2:00 PM se despachan el mismo día (lunes a viernes). Fines de semana y festivos se procesan al siguiente día hábil.</p>
            </div>
            <p><strong>Última actualización:</strong> <span id="lastUpdate"></span></p>
        </div>

    </div>
</div>

<footer>
    <p>&copy; <span id="currentYear"></span> ANGELOW - Tienda Online de Ropa Infantil</p>
    <p>Todos los derechos reservados</p>
    <div class="footer-links">
        <a href="<?= APP_URL ?>/documentos/Politicas_Priv">Políticas de Privacidad</a>
        <a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a>
        <a href="<?= APP_URL ?>/documentos/Politicas_Env">Políticas de Envío</a>
        <a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Políticas de Devolución</a>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/politicas_env.js"></script>
</body>
</html>