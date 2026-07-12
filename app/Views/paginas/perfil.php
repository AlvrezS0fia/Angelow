<?php

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
    <title>ANGELOW - Mi Perfil</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>const APP_URL = '<?= APP_URL ?>';</script>
    <script>const CURRENT_USER = <?= json_encode($user) ?>;</script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/perfil.css">
</head>
<body>

<!-- ALERTA PERSONALIZADA -->
<div class="alert-overlay" id="alertOverlay">
    <div class="alert-modal">
        <div class="alert-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 class="alert-title" id="alertTitle">¿Estás seguro?</h3>
        <p class="alert-message" id="alertMessage">Esta acción no se puede deshacer.</p>
        <div class="alert-buttons">
            <button class="btn-secondary" onclick="closeAlert()">CANCELAR</button>
            <button class="btn-danger" onclick="confirmDelete()">ELIMINAR</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<header>
    <div class="logo">
        <div class="header-left">
            <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
            <div class="logo-text">
                <span class="brand-name">ANGELOW</span>
                <span class="brand-sub">PERFIL</span>
            </div>
        </div>
        <div class="header-right">
            <a href="<?= APP_URL ?>/" class="back-btn-header">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
        </div>
    </div>
</header>

<div class="main-container">
    <aside class="sidebar-menu">
        <div class="menu-item active" data-section="datosPersonales">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </div>
        <div class="menu-item" data-section="direcciones">
            <i class="fas fa-map-marker-alt"></i>
            <span>Direcciones</span>
        </div>
        <div class="menu-item" data-section="pedidos">
            <i class="fas fa-box-open"></i>
            <span>Pedidos</span>
        </div>
        <div class="menu-item" data-section="metodosPago">
            <i class="fas fa-credit-card"></i>
            <span>Tarjetas de crédito</span>
        </div>
        <div class="menu-item" data-section="favoritos">
            <i class="fas fa-heart"></i>
            <span>Mis Favoritos</span>
        </div>
        <div class="menu-item" data-section="carrito">
            <i class="fas fa-shopping-cart"></i>
            <span>Mi Carrito</span>
        </div>
        <div class="menu-item" data-section="autenticacion">
            <i class="fas fa-shield-halved"></i>
            <span>Autenticación</span>
        </div>
    </aside>

    <div class="content-area">

        <!-- DATOS PERSONALES -->
        <div class="profile-section active" id="datosPersonales">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-user-circle profile-title-icon"></i> Datos personales</h1>
                <button class="edit-btn" id="editBtn" onclick="toggleEdit()">
                    <i class="fas fa-pen"></i>
                    EDITAR
                </button>
            </div>
            <div class="profile-card">
                <form id="profileForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-input" id="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-input" id="apellido" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input readonly" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cédula *</label>
                            <input type="text" class="form-input" id="cedula" value="<?= htmlspecialchars($user['cedula'] ?? '') ?>" disabled>
                            <small>Mínimo 6 dígitos, máximo 15</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Género *</label>
                            <select class="form-input" id="genero" disabled>
                                <option value="">Seleccionar</option>
                                <option value="femenino" <?= ($user['genero'] ?? '') == 'femenino' ? 'selected' : '' ?>>Femenino</option>
                                <option value="masculino" <?= ($user['genero'] ?? '') == 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                <option value="otro" <?= ($user['genero'] ?? '') == 'otro' ? 'selected' : '' ?>>Otro</option>
                                <option value="prefiero_no_decirlo" <?= ($user['genero'] ?? '') == 'prefiero_no_decirlo' ? 'selected' : '' ?>>Prefiero no decir</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha nacimiento *</label>
                            <input type="date" class="form-input" id="fechaNacimiento" value="<?= $user['fecha_nacimiento'] ?? '' ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono *</label>
                            <input type="tel" class="form-input" id="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" disabled>
                            <small>Mínimo 7 dígitos, máximo 15</small>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- DIRECCIONES -->
        <div class="profile-section" id="direcciones">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-map-marker-alt profile-title-icon"></i> Direcciones</h1>
                <button class="primary-btn" onclick="showAddressForm()">
                    <i class="fas fa-plus"></i>
                    AGREGAR DIRECCIÓN
                </button>
            </div>
            <div class="profile-card">
                <div class="address-list" id="addressList"></div>
                <div class="empty-state" id="emptyAddressState" style="display: none;">
                    <div class="empty-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <p class="empty-text">¡AÚN NO TIENES NINGUNA DIRECCIÓN!</p>
                    <p class="empty-subtext">Agrega una dirección para recibir tus pedidos</p>
                    <button class="primary-btn" onclick="showAddressForm()"><i class="fas fa-plus"></i> AGREGAR DIRECCIÓN</button>
                </div>
                <div class="address-form-container" id="addressFormContainer" style="display: none; margin-top: 30px;">
                    <h2>NUEVA DIRECCIÓN</h2>
                    <form id="addressForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">País</label>
                                <select class="form-input" id="pais"><option value="Colombia">Colombia</option></select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Departamento *</label>
                                <select class="form-input" id="departamento">
                                    <option value="Antioquia" selected>Antioquia</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Municipio *</label>
                                <select class="form-input" id="municipio">
                                    <option value="">Seleccionar municipio</option>
                                    <option value="Medellín">Medellín</option>
                                    <option value="Bello">Bello</option>
                                    <option value="Itagüí">Itagüí</option>
                                    <option value="Envigado">Envigado</option>
                                    <option value="Sabaneta">Sabaneta</option>
                                    <option value="La Estrella">La Estrella</option>
                                    <option value="Caldas">Caldas</option>
                                    <option value="Rionegro">Rionegro</option>
                                    <option value="El Carmen de Viboral">El Carmen de Viboral</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Calle *</label>
                                <input type="text" class="form-input" id="calle" placeholder="Ej: Calle 50 #45-32">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Información adicional</label>
                                <input type="text" class="form-input" id="infoAdicional" placeholder="Apartamento, torre, piso, etc.">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Barrio *</label>
                                <input type="text" class="form-input" id="barrio" placeholder="Nombre del barrio">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Destinatario *</label>
                                <input type="text" class="form-input" id="destinatario" placeholder="Nombre completo" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button type="button" class="primary-btn" onclick="saveAddress()">GUARDAR DIRECCIÓN</button>
                            <button type="button" class="secondary-btn" onclick="cancelAddressForm()">CANCELAR</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- PEDIDOS -->
        <div class="profile-section" id="pedidos">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-box-open profile-title-icon"></i> Mis Pedidos</h1>
            </div>
            <div class="profile-card">
                <div class="orders-list" id="ordersList"></div>
                <div class="empty-state" id="emptyOrdersState">
                    <div class="empty-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <p class="empty-text">¡AÚN NO HAS REALIZADO NINGÚN PEDIDO!</p>
                    <p class="empty-subtext">Cuando realices un pedido, aparecerá aquí</p>
                    <button class="primary-btn" onclick="window.location.href='<?= APP_URL ?>/'"><i class="fas fa-compass"></i> EXPLORAR PRODUCTOS</button>
                </div>
            </div>
        </div>

        <!-- TARJETAS DE CRÉDITO -->
        <div class="profile-section" id="metodosPago">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-credit-card profile-title-icon"></i> Tarjetas de crédito</h1>
                <button class="primary-btn" onclick="showCardForm()"><i class="fas fa-plus"></i> AÑADIR TARJETA</button>
            </div>
            <div class="profile-card">
                <div class="card-list" id="cardList"></div>
                <div class="empty-state" id="emptyCardState">
                    <div class="empty-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <p class="empty-text">¡AÚN NO TIENES NINGÚN MÉTODO DE PAGO!</p>
                    <p class="empty-subtext">Agrega una tarjeta para pagar tus compras más rápido</p>
                    <button class="primary-btn" onclick="showCardForm()"><i class="fas fa-plus"></i> AÑADIR TARJETA</button>
                </div>
                <div class="card-form-container" id="cardFormContainer" style="display:none; margin-top: 30px;">
                    <h2>NUEVA TARJETA</h2>
                    <p style="color:var(--text-secondary); margin-bottom:30px;">INGRESA LOS DATOS DE TU TARJETA:</p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:start;">
                        <div>
                            <form id="cardForm">
                                <div style="display:flex; flex-direction:column; gap:20px;">
                                    <div class="form-group">
                                        <label class="form-label">Número de la tarjeta *</label>
                                        <input type="text" class="form-input" id="cardNumber" placeholder="4111 1111 1111 1111" maxlength="19" style="background:white;">
                                        <small>13-19 dígitos</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Nombre en la tarjeta *</label>
                                        <input type="text" class="form-input" id="cardName" placeholder="Como aparece en la tarjeta" style="background:white;">
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                        <div class="form-group">
                                            <label class="form-label">Válido hasta *</label>
                                            <input type="text" class="form-input" id="cardExpiry" placeholder="MM/AA" maxlength="5" style="background:white;">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Código seguridad *</label>
                                            <input type="text" class="form-input" id="cardCVV" placeholder="CVV" maxlength="4" style="background:white;">
                                            <small>3-4 dígitos</small>
                                        </div>
                                    </div>
                                </div>
                                <h3 style="font-size:18px; font-weight:600; color:var(--text-dark); margin:40px 0 24px;">DIRECCIÓN DE FACTURACIÓN *</h3>
                                <div style="display:flex; flex-direction:column; gap:20px;">
                                    <div class="form-group">
                                        <label class="form-label">País</label>
                                        <select class="form-input" id="billingCountry" style="background:white;">
                                            <option value="Colombia">Colombia</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Departamento *</label>
                                        <select class="form-input" id="billingState" style="background:white;">
                                            <option value="Antioquia" selected>Antioquia</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Municipio *</label>
                                        <select class="form-input" id="billingCity" style="background:white;">
                                            <option value="">Seleccionar municipio</option>
                                            <option value="Medellín">Medellín</option>
                                            <option value="Bello">Bello</option>
                                            <option value="Itagüí">Itagüí</option>
                                            <option value="Envigado">Envigado</option>
                                            <option value="Sabaneta">Sabaneta</option>
                                            <option value="La Estrella">La Estrella</option>
                                            <option value="Caldas">Caldas</option>
                                            <option value="Rionegro">Rionegro</option>
                                            <option value="El Carmen de Viboral">El Carmen de Viboral</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Código postal *</label>
                                        <input type="text" class="form-input" id="cardPostalCode" placeholder="05001" maxlength="5" style="background:white;">
                                        <small>5 dígitos</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Calle *</label>
                                        <input type="text" class="form-input" id="billingStreet" placeholder="Calle 50 #45-32" style="background:white;">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Barrio *</label>
                                        <input type="text" class="form-input" id="billingNeighborhood" placeholder="Nombre del barrio" style="background:white;">
                                    </div>
                                    <div class="info-box">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#856404" stroke-width="2" style="flex-shrink:0;">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="16" x2="12" y2="12"></line>
                                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                        </svg>
                                        <p>Puede que se le cargue una pequeña cantidad para autorizar la tarjeta. El monto se revertirá en 3-5 días hábiles.</p>
                                    </div>
                                    <div class="action-buttons">
                                        <button type="button" class="primary-btn" onclick="saveCard()">GUARDAR TARJETA</button>
                                        <button type="button" class="secondary-btn" onclick="cancelCardForm()">CANCELAR</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div style="position:sticky; top:140px;">
                            <div class="credit-card">
                                <div class="card-chip">
                                    <svg width="50" height="42" viewBox="0 0 50 42">
                                        <rect x="2" y="2" width="46" height="38" rx="4" fill="none" stroke="#FFD700" stroke-width="2"/>
                                        <line x1="8" y1="12" x2="42" y2="12" stroke="#FFD700" stroke-width="2"/>
                                        <line x1="8" y1="18" x2="42" y2="18" stroke="#FFD700" stroke-width="2"/>
                                        <line x1="8" y1="24" x2="42" y2="24" stroke="#FFD700" stroke-width="2"/>
                                        <line x1="8" y1="30" x2="30" y2="30" stroke="#FFD700" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="card-number" id="displayCardNumber">•••• •••• •••• ••••</div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:auto;">
                                    <div style="flex:1;">
                                        <div class="card-holder" id="displayCardHolder">NOMBRE</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div class="card-expiry-label">Válida hasta</div>
                                        <div class="card-expiry" id="displayCardExpiry">••/••</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAVORITOS -->
        <div class="profile-section" id="favoritos">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-heart profile-title-icon"></i> Mis Favoritos</h1>
            </div>
            <div class="profile-card">
                <div class="favorites-grid" id="favoritesGrid"></div>
                <div class="empty-state" id="emptyFavoritesState">
                    <div class="empty-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <p class="empty-text">¡AÚN NO TIENES PRODUCTOS FAVORITOS!</p>
                    <p class="empty-subtext">Guarda tus productos favoritos para comprarlos más tarde</p>
                    <button class="primary-btn" onclick="window.location.href='<?= APP_URL ?>/'"><i class="fas fa-compass"></i> EXPLORAR PRODUCTOS</button>
                </div>
            </div>
        </div>

        <!-- CARRITO -->
        <div class="profile-section" id="carrito">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-shopping-cart profile-title-icon"></i> Mi Carrito</h1>
            </div>
            <div class="profile-card">
                <div id="cartItemsProfile">
                    <!-- Renderizado por JavaScript -->
                </div>
            </div>
        </div>

        <!-- AUTENTICACIÓN (AL FINAL) -->
        <div class="profile-section" id="autenticacion">
            <div class="profile-header">
                <h1 class="profile-title"><i class="fas fa-shield-halved profile-title-icon"></i> Autenticación</h1>
            </div>
            <div class="profile-card">
                <div class="security-section">
                    <h3 class="security-title"><i class="fas fa-lock security-title-icon"></i> Contraseña</h3>
                    <p class="security-description">Usted todavía no tiene una contraseña definida.</p>
                    <div class="action-buttons">
                        <button class="primary-btn" onclick="definePassword()"><i class="fas fa-key"></i> DEFINIR CONTRASEÑA</button>
                        <button class="secondary-btn" onclick="recoverPassword()"><i class="fas fa-rotate-right"></i> RECUPERAR CONTRASEÑA</button>
                    </div>
                </div>
                <div class="security-section">
                    <h3 class="security-title"><i class="fas fa-desktop security-title-icon"></i> Gestión de sesiones</h3>
                    <p class="security-description">Usted tiene <span id="sessionCount">1</span> sesiones activas</p>
                    <button class="primary-btn" onclick="viewSessions()"><i class="fas fa-list"></i> VER SESIONES</button>
                </div>
                <div class="security-section">
                    <h3 class="security-title"><i class="fas fa-fingerprint security-title-icon"></i> Autenticación de dos factores</h3>
                    <p class="security-description">Protege tu cuenta con un código adicional</p>
                    <button class="success-btn" onclick="enableTwoFactor()"><i class="fas fa-check-double"></i> VERIFICACIÓN EN DOS PASOS</button>
                </div>
                <div class="security-section" style="border-top:1px solid var(--border-light); padding-top:40px;">
                    <h3 class="security-title" style="color:var(--error);"><i class="fas fa-right-from-bracket security-title-icon"></i> Cerrar sesión</h3>
                    <p class="security-description">CIERRA SESIÓN ACTUAL EN ANGELOW</p>
                    <button class="danger-btn" onclick="showLogoutConfirm()"><i class="fas fa-arrow-right-from-bracket"></i> CERRAR SESIÓN</button>
                </div>
            </div>
        </div>

    </div>
</div>

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
            </ul>
        </div>
        <div class="footer-section">
            <h3>Legal</h3>
            <ul class="footer-links">
                <li><a href="<?= APP_URL ?>/documentos/Politicas_Priv">Políticas de Privacidad</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Terminos">Términos y Condiciones</a></li>
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

<script src="<?= APP_URL ?>/assets/js/perfil.js"></script>
</body>
</html>