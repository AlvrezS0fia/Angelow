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
    <script>const CURRENT_USER = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/tokens.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/perfil.css">
    <?php require __DIR__ . '/../layouts/leaflet-css.php'; ?>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/seguimiento-perfil.css">
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
        <div class="menu-item" data-section="seguimientoSection">
            <i class="fas fa-truck"></i>
            <span>Rastrea tu pedido</span>
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
                            <input type="date" class="form-input" id="fechaNacimiento" value="<?= htmlspecialchars($user['fecha_nacimiento'] ?? '', ENT_QUOTES) ?>" disabled>
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

        <!-- RASTREA TU PEDIDO (integrado desde seguimiento.php) -->
        <div class="profile-section" id="seguimientoSection">
            <div class="seg-main-content">
                <div class="seg-page-header">
                    <h1 class="seg-page-title"><i class="fas fa-truck"></i> Rastrea tu Pedido</h1>
                    <p class="seg-page-subtitle">Sigue en tiempo real la ubicación de tu entrega y conoce el estado exacto de tu pedido</p>
                </div>
                <div class="seg-tracking-wrapper">
                    <!-- MAP CONTAINER -->
                    <div class="seg-map-container">
                        <div id="segMap"></div>
                        <div id="segMapSkeleton" class="seg-map-skeleton">
                            <div class="seg-skeleton-shimmer"></div>
                            <div class="seg-skeleton-content">
                                <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="seg-skeleton-logo">
                                <div class="seg-skeleton-text">Cargando mapa...</div>
                            </div>
                        </div>

                        <!-- VENTANA FLOTANTE: Planificador de Ruta -->
                        <div class="seg-controls-panel seg-collapsed" id="segControlsPanel">
                            <button class="seg-controls-toggle-btn" id="segControlsToggle" title="Planificador de ruta">
                                <i class="fas fa-route"></i>
                            </button>
                            <div class="seg-panel-header">
                                <div class="seg-panel-title">
                                    <i class="fas fa-route"></i>
                                    <h3>Planificador de Ruta</h3>
                                </div>
                                <div class="seg-panel-actions">
                                    <button class="seg-panel-action-btn" id="segLocateMe" title="Ubicar mi posición">
                                        <i class="fas fa-crosshairs"></i>
                                    </button>
                                    <button class="seg-panel-close-btn" id="segClosePanel" title="Cerrar panel">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="seg-panel-content">
                                <!-- Punto de Origen -->
                                <div class="seg-input-group">
                                    <div class="seg-input-header">
                                        <label class="seg-input-label">
                                            <i class="fas fa-map-marker-alt"></i> Punto de Origen
                                        </label>
                                        <button class="seg-input-action" id="segUseCurrentLocation">
                                            <img src="<?= APP_URL ?>/assets/imagenes/general/flechas.png" alt="Ubicación" style="width:16px; height:16px; margin-right:4px;">
                                            Usar mi ubicación
                                        </button>
                                    </div>
                                    <div class="seg-input-wrapper">
                                        <i class="seg-input-icon fas fa-circle"></i>
                                        <input type="text" id="segStartAddress" class="seg-address-input" placeholder="Ubicación actual" readonly>
                                    </div>
                                </div>
                                <!-- Punto de Destino -->
                                <div class="seg-input-group">
                                    <div class="seg-input-header">
                                        <label class="seg-input-label">
                                            <i class="fas fa-flag-checkered"></i> Punto de Destino
                                        </label>
                                        <span class="seg-input-label" style="color:var(--error);font-size:0.7rem;">*Requerido</span>
                                    </div>
                                    <div class="seg-input-wrapper" style="position:relative;">
                                        <i class="seg-input-icon fas fa-map-pin"></i>
                                        <input type="text" id="segEndAddress" class="seg-address-input" placeholder="Ingresa dirección de entrega">
                                        <div id="segAddressSuggestions" class="seg-autocomplete-dropdown" style="display:none;"></div>
                                    </div>
                                    <div class="seg-address-suggestions">
                                        <div class="seg-suggestion" data-address="Carrera 15 #88-64, Medellín">
                                            <i class="fas fa-home"></i><span>Oficina Principal</span>
                                        </div>
                                        <div class="seg-suggestion" data-address="Calle 100 #15-20, Medellín">
                                            <i class="fas fa-store"></i><span>Tienda Angelow</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="seg-route-actions">
                                    <button id="segCalculateRoute" class="seg-btn seg-btn-primary">
                                        <i class="fas fa-route"></i> Calcular Ruta
                                    </button>
                                    <div class="seg-secondary-actions">
                                        <button id="segClearRoute" class="seg-btn seg-btn-secondary" disabled>
                                            <i class="fas fa-times"></i> Limpiar
                                        </button>
                                        <button id="segSaveRoute" class="seg-btn seg-btn-outline" disabled>
                                            <i class="fas fa-bookmark"></i> Guardar
                                        </button>
                                    </div>
                                </div>
                                <!-- Información de Ruta -->
                                <div class="seg-route-info-card">
                                    <div class="seg-info-card-header">
                                        <h4><i class="fas fa-info-circle"></i> Información de Ruta</h4>
                                    </div>
                                    <div class="seg-info-grid">
                                        <div class="seg-info-item seg-highlighted">
                                            <div class="seg-info-icon"><i class="fas fa-road"></i></div>
                                            <div class="seg-info-details">
                                                <span class="seg-info-label">Distancia</span>
                                                <span class="seg-info-value" id="segRouteDistance">--</span>
                                            </div>
                                        </div>
                                        <div class="seg-info-item seg-highlighted">
                                            <div class="seg-info-icon"><i class="fas fa-clock"></i></div>
                                            <div class="seg-info-details">
                                                <span class="seg-info-label">Tiempo</span>
                                                <span class="seg-info-value" id="segRouteTime">--</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Panel -->
                        <div class="seg-status-panel">
                            <div class="seg-status-header">
                                <div class="seg-status-title">
                                    <div class="seg-status-indicator-container">
                                        <div class="seg-status-indicator seg-pulse seg-status-waiting"></div>
                                        <span id="segStatusTitle">Estado: Esperando</span>
                                    </div>
                                    <button class="seg-status-refresh" id="segRefreshStatus"><i class="fas fa-sync-alt"></i></button>
                                </div>
                                <div class="seg-status-meta"><span class="seg-status-time" id="segStatusTime">Actualizado: ahora</span></div>
                            </div>
                            <div class="seg-status-content">
                                <div class="seg-status-message"><i class="fas fa-info-circle"></i><p id="segStatusMessage">Ingresa una dirección para comenzar</p></div>
                                <div class="seg-detail-grid">
                                    <div class="seg-detail-item"><i class="fas fa-user"></i><div><span class="seg-detail-label">Repartidor</span><span class="seg-detail-value" id="segDeliveryPerson">Asignando...</span></div></div>
                                    <div class="seg-detail-item"><i class="fas fa-phone"></i><div><span class="seg-detail-label">Contacto</span><span class="seg-detail-value" id="segDeliveryContact">---</span></div></div>
                                </div>
                            </div>
                        </div>

                        <!-- GPS Indicator -->
                        <div class="seg-gps-indicator">
                            <div class="seg-gps-icon"><i class="fas fa-satellite"></i></div>
                            <div class="seg-gps-text"><span class="seg-gps-status">GPS Activo</span><span class="seg-gps-accuracy">Precisión: 15m</span></div>
                        </div>

                        <!-- Layer Switcher -->
                        <div class="seg-layer-switcher" id="segLayerSwitcher">
                            <button class="seg-layer-btn seg-active" data-layer="street" title="Mapa callejero">
                                <i class="fas fa-map"></i>
                            </button>
                            <button class="seg-layer-btn" data-layer="satellite" title="Vista satélite">
                                <i class="fas fa-globe"></i>
                            </button>
                            <button class="seg-layer-btn" data-layer="dark" title="Modo oscuro">
                                <i class="fas fa-moon"></i>
                            </button>
                        </div>

                        <!-- Notificación de entrega completada -->
                        <div id="segLiveNotification" class="seg-notification" style="display:none">
                            <div class="seg-notification-content">
                                <i class="fas fa-check-circle" style="font-size:1.5rem;"></i>
                                <div>
                                    <strong>¡Pedido Entregado!</strong>
                                    <div style="font-size:0.85rem;opacity:0.9;">Tu pedido ha sido entregado exitosamente</div>
                                </div>
                                <button class="seg-notification-close" onclick="this.parentElement.parentElement.style.display='none'">×</button>
                            </div>
                        </div>
                    </div>

                    <!-- TRACKING PANEL (derecha) -->
                    <div class="seg-tracking-container">
                        <div class="seg-tracking-header">
                            <div class="seg-header-main"><h2><i class="fas fa-box-open"></i> Seguimiento</h2><div class="seg-order-status-badge seg-status-active"><i class="fas fa-circle"></i> Activo</div></div>
                            <div class="seg-header-secondary"><div class="seg-order-meta"><div class="seg-meta-item"><span class="seg-meta-label">Pedido</span><span class="seg-meta-value" id="segOrderNumber">001234</span></div><div class="seg-meta-item"><span class="seg-meta-label">Fecha</span><span class="seg-meta-value" id="segOrderDate">Hoy, 14:30</span></div></div><button class="seg-header-action" id="segShareTracking"><i class="fas fa-share-alt"></i></button></div>
                        </div>
                        <div class="seg-tracking-content">
                            <div class="seg-progress-timeline"><div class="seg-timeline-header"><h3><i class="fas fa-history"></i> Progreso</h3><div class="seg-timeline-progress"><div class="seg-progress-bar"><div class="seg-progress-fill" style="width: 0%"></div></div><span class="seg-progress-text">0%</span></div></div><div class="seg-timeline-steps"><div class="seg-step"><div class="seg-step-icon"><i class="fas fa-clipboard-check"></i></div><div class="seg-step-content"><h4>Confirmado</h4><span class="seg-step-time">--:--</span></div></div><div class="seg-step"><div class="seg-step-icon"><i class="fas fa-warehouse"></i></div><div class="seg-step-content"><h4>Preparado</h4><span class="seg-step-time">--:--</span></div></div><div class="seg-step"><div class="seg-step-icon"><i class="fas fa-shipping-fast"></i></div><div class="seg-step-content"><h4>En Camino</h4><span class="seg-step-time">--:--</span></div></div><div class="seg-step"><div class="seg-step-icon"><i class="fas fa-home"></i></div><div class="seg-step-content"><h4>Entregado</h4><span class="seg-step-time">--:--</span></div></div></div></div>
                            <div class="seg-driver-card"><div class="seg-card-header"><h3><i class="fas fa-user-circle"></i> Repartidor</h3></div><div class="seg-card-content"><div class="seg-driver-profile"><div class="seg-driver-avatar"><img src="https://ui-avatars.com/api/?name=Carlos+Rodriguez&background=5E9DE6&color=fff"></div><div class="seg-driver-info"><h4 id="segDriverName">-</h4><div class="seg-driver-rating"><div class="seg-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div></div></div></div><div class="seg-driver-contact"><button class="seg-contact-btn seg-call-btn"><i class="fas fa-phone"></i></button><button class="seg-contact-btn seg-message-btn"><i class="fas fa-comment"></i></button><button class="seg-contact-btn seg-location-btn"><i class="fas fa-map-marker-alt"></i></button></div></div></div>
                            <div class="seg-estimate-card"><div class="seg-card-header"><h3><i class="fas fa-clock"></i> Estimación</h3></div><div class="seg-card-content"><div class="seg-estimate-main"><div class="seg-estimate-time"><span class="seg-time-value" id="segEstimatedTime">--</span><span class="seg-time-unit" style="font-size:1rem;font-weight:600;opacity:0.8;">min</span></div></div><div class="seg-estimate-details"><div class="seg-est-detail"><i class="fas fa-road"></i><span><strong id="segEstimatedDistance">--</strong></span></div><div class="seg-est-detail"><i class="fas fa-traffic-light"></i><span>Normal</span></div></div></div></div>
                        </div>
                    </div>
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
<?php require __DIR__ . '/../layouts/leaflet-js.php'; ?>
<script src="<?= APP_URL ?>/assets/js/seguimiento-perfil.js"></script>
</body>
</html>