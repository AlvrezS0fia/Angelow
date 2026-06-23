<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas de Devoluciones y Cambios - ANGELOW</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/politicas_devolucion.css">
</head>
<body>

<header>
    <a href="<?= APP_URL ?>/" class="logo">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>DEVOLUCIONES Y CAMBIOS</span>
        </div>
    </a>

    <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
    </div>
</header>

<div class="container">
    <div class="banner">
        <h2>POLÍTICAS DE DEVOLUCIONES Y CAMBIOS</h2>
        <p>Lineamientos aplicables a todos los productos adquiridos a través de nuestros canales oficiales.</p>
    </div>

    <div class="header-section">
        <h1>GESTIÓN DE CAMBIOS Y DEVOLUCIONES</h1>
        <p>Vigente para compras realizadas en nuestra tienda online.</p>
    </div>

    <div class="nav">
        <a href="#plazo" class="active">Plazo</a>
        <a href="#condiciones">Condiciones</a>
        <a href="#procedimiento">Procedimiento</a>
        <a href="#reembolsos">Reembolsos</a>
        <a href="#excepciones">Excepciones</a>
        <a href="#garantia">Garantía</a>
    </div>

    <div class="content">
        <div class="section" id="plazo">
            <h2>1. PLAZO PARA SOLICITAR CAMBIO O DEVOLUCIÓN</h2>
            <p>El cliente podrá solicitar cambio o devolución dentro de los treinta (30) días calendario siguientes a la fecha de recepción del producto.</p>
            <p>Transcurrido este plazo, no se aceptarán solicitudes salvo en casos de garantía por defecto de fabricación.</p>
        </div>

        <div class="section" id="condiciones">
            <h2>2. CONDICIONES GENERALES</h2>
            <ul>
                <li>El producto debe encontrarse en estado original.</li>
                <li>No debe presentar señales de uso, lavado o alteración.</li>
                <li>Debe conservar etiquetas y empaques originales.</li>
                <li>Se debe presentar comprobante de compra.</li>
            </ul>
        </div>

        <div class="section" id="procedimiento">
            <h2>3. PROCEDIMIENTO</h2>
            <ol>
                <li>Solicitar el cambio o devolución a través de los canales oficiales.</li>
                <li>Indicar número de pedido y motivo de la solicitud.</li>
                <li>Enviar el producto según las instrucciones proporcionadas.</li>
                <li>Esperar validación del área de calidad.</li>
            </ol>
            <p>La validación podrá tardar entre tres (3) y cinco (5) días hábiles desde la recepción del producto.</p>
        </div>

        <div class="section" id="reembolsos">
            <h2>4. REEMBOLSOS</h2>
            <p>En caso de proceder la devolución, el reembolso se realizará por el mismo medio de pago utilizado en la compra.</p>
            <p>El tiempo de procesamiento dependerá de la entidad financiera y podrá oscilar entre cinco (5) y quince (15) días hábiles.</p>
        </div>

        <div class="section" id="excepciones">
            <h2>5. EXCEPCIONES</h2>
            <ul>
                <li>Productos personalizados no admiten devolución.</li>
                <li>Artículos en promoción pueden estar sujetos a condiciones especiales.</li>
                <li>No se aceptarán devoluciones por desgaste natural.</li>
            </ul>
        </div>

        <div class="section" id="garantia">
            <h2>6. GARANTÍA POR DEFECTO DE FABRICACIÓN</h2>
            <p>Los productos cuentan con garantía de noventa (90) días por defectos de fabricación.</p>
            <p>La garantía cubre fallas estructurales del producto y no daños ocasionados por uso inadecuado.</p>
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

<script>
    document.getElementById('currentYear').textContent = new Date().getFullYear();
</script>
</body>
</html>