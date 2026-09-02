<?php
// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
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
    <title>ANGELOW - Panel de Administración</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/tokens.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css">

    <!-- Librerías externas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <!-- Leaflet CSS y JS -->
    <?php require __DIR__ . '/../layouts/leaflet-css.php'; ?>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/seguimiento-perfil.css">
    <?php require __DIR__ . '/../layouts/leaflet-js.php'; ?>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>

    <!-- TOASTS -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Header del Admin -->
    <header class="admin-header">
        <div class="admin-logo">
            <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="admin-logo-img">
            <div class="admin-logo-text">
                <span>ANGELOW</span>
                <span>ADMINISTRACIÓN</span>
            </div>
        </div>
        <div class="admin-nav">
            <div class="admin-user" id="adminUserBtn">
                <div class="admin-user-img"><?= strtoupper(substr($user['nombre'] ?? 'A', 0, 2)) ?></div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['nombre'] ?? 'Administrador') ?></div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Administrador</div>
                </div>
            </div>
            <!-- BOTÓN VOLVER -->
            <a href="<?= APP_URL ?>/" class="btn-back-header">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
            <button class="logout-btn" onclick="window.location.href='<?= APP_URL ?>/auth/logout'">Salir</button>
        </div>
    </header>


    <!-- Contenido Principal -->
    <div class="admin-container">

        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <ul class="admin-menu">
                <li><a href="#dashboard" class="active" data-section="dashboard"><i class="fas fa-gauge-high admin-menu-icon icon-dashboard"></i><span>Dashboard</span></a></li>
                <li><a href="#products" data-section="products"><i class="fas fa-box admin-menu-icon icon-products"></i><span>Productos</span></a></li>
                <li><a href="#categories" data-section="categories"><i class="fas fa-tags admin-menu-icon icon-categories"></i><span>Categorías</span></a></li>
                <li><a href="#orders" data-section="orders"><i class="fas fa-clipboard-list admin-menu-icon icon-orders"></i><span>Pedidos</span></a></li>
                <li><a href="#customers" data-section="customers"><i class="fas fa-users admin-menu-icon icon-customers"></i><span>Clientes</span></a></li>
                <li><a href="#delivery" data-section="delivery"><i class="fas fa-truck-fast admin-menu-icon icon-delivery"></i><span>Repartidores</span></a></li>
                <li><a href="#seguimiento" data-section="seguimiento"><i class="fas fa-map-location-dot admin-menu-icon icon-seguimiento"></i><span>Seguimiento</span></a></li>
                <li><a href="#analytics" data-section="analytics"><i class="fas fa-chart-line admin-menu-icon icon-analytics"></i><span>Analítica</span></a></li>
                <li><a href="#settings" data-section="settings"><i class="fas fa-gear admin-menu-icon icon-settings"></i><span>Configuración</span></a></li>
            </ul>
        </nav>

        <!-- Contenido Dinámico -->
        <main class="admin-content">

            <!-- SECCIÓN DASHBOARD -->
            <section id="dashboard-section" class="admin-section active-section">
                <h1 class="dashboard-title">Panel de Control</h1>

                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalOrders">0</div>
                                <div class="metric-label">Pedidos Totales</div>
                            </div>
                            <div class="metric-icon metric-icon-orders"><i class="fas fa-cart-shopping"></i></div>
                        </div>
                        <span class="metric-change positive" id="totalOrdersChange">+0 este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="pendingOrders">0</div>
                                <div class="metric-label">Pendientes</div>
                            </div>
                            <div class="metric-icon metric-icon-pending"><i class="fas fa-clock"></i></div>
                        </div>
                        <span class="metric-change negative" id="pendingOrdersChange">-0 esta semana</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalFavorites">0</div>
                                <div class="metric-label">Favoritos</div>
                            </div>
                            <div class="metric-icon metric-icon-favorites"><i class="fas fa-heart"></i></div>
                        </div>
                        <span class="metric-change positive" id="favoritesChange">+0 este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalRevenue">$0</div>
                                <div class="metric-label">Ganancias</div>
                            </div>
                            <div class="metric-icon metric-icon-revenue"><i class="fas fa-dollar-sign"></i></div>
                        </div>
                        <span class="metric-change positive" id="revenueChange">+0% este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalUsers">0</div>
                                <div class="metric-label">Usuarios Registrados</div>
                            </div>
                            <div class="metric-icon metric-icon-users"><i class="fas fa-user-group"></i></div>
                        </div>
                        <span class="metric-change positive" id="usersChange">+0 este mes</span>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Ventas Mensuales</h3>
                            <select id="salesYear" class="custom-select">
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                            </select>
                        </div>
                        <div class="chart-container"><canvas id="salesChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header"><h3 class="chart-title">Productos Más Vendidos</h3></div>
                        <div class="chart-container"><canvas id="productsChart"></canvas></div>
                    </div>
                </div>

                <div class="progress-section">
                    <div class="progress-header">
                        <h3 class="chart-title">Progreso General</h3>
                        <span style="font-weight: 700; color: var(--primary);" id="progressPercent">0%</span>
                    </div>
                    <div class="progress-bar-container"><div class="progress-bar" id="progressBar" style="width: 0%;"></div></div>
                    <div class="progress-info">
                        <span>Meta anual de ventas</span>
                        <span id="progressText">$0 / $0</span>
                    </div>
                    <button class="btn btn-primary" style="margin-top: 16px;">Ver Detalles</button>
                </div>
            </section>

            <!-- SECCIÓN PRODUCTOS -->
            <section id="products-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Productos</h2>
                    <button id="addProductBtn" class="btn btn-primary">+ Agregar Producto</button>
                </div>
                <div class="products-management">
                    <div class="product-filters">
                        <div class="filter-group">
                            <div class="filter-title">Categoría</div>
                            <div class="filter-options" id="categoryFilters"></div>
                        </div>
                        <div class="filter-group">
                            <div class="filter-title">Stock</div>
                            <div class="filter-options">
                                <div class="filter-option active" data-stock="all"><div class="filter-checkbox"></div><span>Todos</span></div>
                                <div class="filter-option" data-stock="in-stock"><div class="filter-checkbox"></div><span>En stock</span></div>
                                <div class="filter-option" data-stock="low-stock"><div class="filter-checkbox"></div><span>Stock bajo</span></div>
                                <div class="filter-option" data-stock="out-of-stock"><div class="filter-checkbox"></div><span>Sin stock</span></div>
                            </div>
                        </div>
                        <div class="filter-group">
                            <div class="filter-title">Ordenar por</div>
                            <div class="filter-options">
                                <div class="filter-option active" data-sort="name"><div class="filter-checkbox"></div><span>Nombre</span></div>
                                <div class="filter-option" data-sort="price"><div class="filter-checkbox"></div><span>Precio</span></div>
                                <div class="filter-option" data-sort="stock"><div class="filter-checkbox"></div><span>Stock</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="products-grid" id="adminProductsGrid"></div>
                </div>
            </section>

            <!-- SECCIÓN CATEGORÍAS -->
            <section id="categories-section" class="admin-section" style="display: none;">
                <div class="categories-section">
                    <div class="categories-header">
                        <h2 class="categories-title">Gestión de Categorías Principales</h2>
                        <button class="add-category-btn" onclick="openCategoryModal('categoria')">+ Añadir Categoría</button>
                    </div>
                    <div class="categories-table-container">
                        <table class="categories-table" id="mainCategoriesTable">
                            <thead><tr><th>ID</th><th>Nombre</th><th>En Barra</th><th>Productos</th><th>Acciones</th></tr></thead>
                            <tbody id="mainCategoriesTableBody"></tbody>
                        </table>
                    </div>

                    <div class="categories-header" style="margin-top: 40px;">
                        <h2 class="categories-title">Gestión de Subcategorías (Ofertas/Promociones)</h2>
                        <button class="add-subcategory-btn" onclick="openCategoryModal('subcategoria')">+ Añadir Subcategoría</button>
                    </div>
                    <div class="subcategories-table-container">
                        <table class="subcategories-table" id="subcategoriesTable">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Categoría Principal</th><th>En Barra</th><th>Productos</th><th>Acciones</th></tr></thead>
                            <tbody id="subcategoriesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN PEDIDOS (TIEMPO REAL) -->
            <section id="orders-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Pedidos</h2>
                    <div class="action-buttons" style="gap: 12px;">
                        <button id="exportOrdersBtn" class="btn btn-secondary" onclick="exportOrdersToPDF()">Exportar a PDF</button>
                        <button id="refreshOrdersBtn" class="btn btn-primary" onclick="refreshOrdersRealTime()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                            </svg>
                            Actualizar
                        </button>
                    </div>
                </div>

                <div class="orders-filters">
                    <select id="orderStatusFilter" class="filter-select">
                        <option value="all">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="processing">En proceso</option>
                        <option value="shipped">Enviado</option>
                        <option value="delivered">Entregado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                    <select id="orderCityFilter" class="filter-select">
                        <option value="all">Todas las ciudades</option>
                        <option value="Medellín">Medellín</option>
                        <option value="Bogotá">Bogotá</option>
                        <option value="Cali">Cali</option>
                        <option value="Barranquilla">Barranquilla</option>
                        <option value="Cartagena">Cartagena</option>
                    </select>
                    <input type="text" id="orderSearchInput" class="search-input" placeholder="Buscar por cliente o ID...">
                </div>

                <div class="orders-with-map">
                    <div class="orders-list-container"><div id="ordersList"></div></div>
                    <div class="map-container"><div class="map-title">Ubicación de Pedidos</div><div id="orderMap"></div></div>
                </div>

                <div style="margin-top: 30px;">
                    <h3 class="section-title" style="margin-bottom: 20px;">Listado General de Pedidos</h3>
                    <table class="admin-table">
                        <thead><tr><th>ID Pedido</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Dirección</th><th>Acciones</th></tr></thead>
                        <tbody id="ordersTable"></tbody>
                    </table>
                </div>
            </section>

            <!-- SECCIÓN CLIENTES -->
            <section id="customers-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Clientes</h2>
                    <div class="action-buttons" style="gap: 12px;">
                        <button id="exportCustomersBtn" class="btn btn-secondary" onclick="exportarClientesPDF()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Exportar PDF
                        </button>
                        <button id="addCustomerBtn" class="btn btn-primary">+ Nuevo Cliente</button>
                    </div>
                </div>
                <div style="margin-bottom: 16px; max-width: 300px;">
                    <input type="text" id="customerSearch" class="search-input" placeholder="Buscar por nombre o email...">
                </div>
                <table class="admin-table">
                    <thead><tr><th>Cliente</th><th>Email</th><th>Teléfono</th><th>Fecha Registro</th><th>Rol</th><th>Acciones</th></tr></thead>
                    <tbody id="customersTable"></tbody>
                </table>
            </section>

            <!-- SECCIÓN REPARTIDORES -->
            <section id="delivery-section" class="admin-section" style="display: none;">
                <div class="section-header"><h2 class="section-title">Gestión de Repartidores</h2></div>

                <div class="metrics-grid" id="driverStats">
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="driverPending">0</div><div class="metric-label">Pendientes</div></div>
                            <div class="metric-icon metric-icon-pending"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="driverActive">0</div><div class="metric-label">Activos</div></div>
                            <div class="metric-icon metric-icon-revenue"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="driverApproved">0</div><div class="metric-label">Aprobados</div></div>
                            <div class="metric-icon metric-icon-favorites"><i class="fas fa-user-check"></i></div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="driverRejected">0</div><div class="metric-label">Rechazados</div></div>
                            <div class="metric-icon metric-icon-users"><i class="fas fa-user-xmark"></i></div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title" style="margin: 30px 0 16px; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-file-circle-exclamation" style="color: var(--warning);"></i>
                    Solicitudes Pendientes
                    <span id="pendingBadge" style="background: var(--warning); color: #fff; border-radius: 50px; padding: 2px 12px; font-size: 13px; font-weight: 700;"></span>
                </h3>
                <div id="solicitudesContainer">
                    <div style="text-align:center; padding:40px; color:var(--text-secondary);">Cargando solicitudes...</div>
                </div>

                <h3 class="section-title" style="margin: 30px 0 16px; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-truck-fast" style="color: var(--primary);"></i>
                    Repartidores Registrados
                </h3>
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Vehículo</th><th>Placa</th><th>Estado</th><th>Entregas</th><th>Documentos</th></tr></thead>
                    <tbody id="deliveryTable"></tbody>
                </table>
            </section>

            <!-- SECCIÓN ANALÍTICA -->
            <section id="analytics-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Análisis de Datos</h2>
                    <select id="analyticsPeriod" class="custom-select">
                        <option value="week">Esta semana</option>
                        <option value="month" selected>Este mes</option>
                        <option value="quarter">Este trimestre</option>
                        <option value="year">Este año</option>
                    </select>
                </div>
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header"><h3 class="chart-title">Tráfico del Sitio</h3></div>
                        <div class="chart-container"><canvas id="trafficChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header"><h3 class="chart-title">Conversiones</h3></div>
                        <div class="chart-container"><canvas id="conversionChart"></canvas></div>
                    </div>
                </div>
                <div class="metrics-grid" style="margin-top: 30px;">
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="visitorsCount">0</div><div class="metric-label">Visitantes</div></div>
                            <div class="metric-icon metric-icon-visitors"><i class="fas fa-eye"></i></div>
                        </div>
                        <span class="metric-change positive">+0% este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="conversionRate">0%</div><div class="metric-label">Tasa de Conversión</div></div>
                            <div class="metric-icon metric-icon-conversion"><i class="fas fa-bullseye"></i></div>
                        </div>
                        <span class="metric-change positive">+0% este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="avgOrderValue">$0</div><div class="metric-label">Valor Promedio</div></div>
                            <div class="metric-icon metric-icon-avg"><i class="fas fa-receipt"></i></div>
                        </div>
                        <span class="metric-change positive">+$0 este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="bounceRate">0%</div><div class="metric-label">Tasa de Rebote</div></div>
                            <div class="metric-icon metric-icon-bounce"><i class="fas fa-arrow-right-from-bracket"></i></div>
                        </div>
                        <span class="metric-change negative">+0% este mes</span>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN CONFIGURACIÓN -->
            <section id="settings-section" class="admin-section" style="display: none;">
                <div class="section-header"><h2 class="section-title">Configuración del Sistema</h2></div>
                <div style="display: grid; gap: 24px; max-width: 800px;">
                    <div class="filter-group">
                        <div class="filter-title">Información de la Tienda</div>
                        <div style="display: grid; gap: 16px;">
                            <input type="text" class="form-input" placeholder="Nombre de la tienda" value="ANGELOW PEDIDOS">
                            <input type="email" class="form-input" placeholder="Email de contacto" value="contacto@angelow.com">
                            <input type="tel" class="form-input" placeholder="Teléfono" value="+57 300 123 4567">
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-title">Configuración de Pedidos</div>
                        <div style="display: grid; gap: 16px; padding: 16px; background: var(--bg-soft); border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Enviar notificaciones por email</span>
                                <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Pedidos manuales</span>
                                <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Inventario automático</span>
                                <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                            </div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-title">Configuración de Pagos</div>
                        <div style="display: grid; gap: 16px; padding: 16px; background: var(--bg-soft); border-radius: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="paypalCheck" checked><label for="paypalCheck">PayPal</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="creditCardCheck" checked><label for="creditCardCheck">Tarjeta de Crédito</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="cashCheck" checked><label for="cashCheck">Pago contra entrega</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary" style="width: fit-content;">Guardar Cambios</button>
                </div>
            </section>

            <!-- SECCIÓN SEGUIMIENTO -->
            <section id="seguimiento-section" class="admin-section" style="display: none;">
                <div id="seguimientoSection" class="seg-main-content">
                    <div class="seg-page-header">
                        <h1 class="seg-page-title"><i class="fas fa-truck"></i> Rastreo de Pedidos</h1>
                        <p class="seg-page-subtitle">Visualiza y gestiona la ubicación de las entregas en tiempo real</p>
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
                                    <div class="seg-input-group">
                                        <div class="seg-input-header">
                                            <label class="seg-input-label"><i class="fas fa-map-marker-alt"></i> Punto de Origen</label>
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
                                    <div class="seg-input-group">
                                        <div class="seg-input-header">
                                            <label class="seg-input-label"><i class="fas fa-flag-checkered"></i> Punto de Destino</label>
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
                                <button class="seg-layer-btn seg-active" data-layer="street" title="Mapa callejero"><i class="fas fa-map"></i></button>
                                <button class="seg-layer-btn" data-layer="satellite" title="Vista satélite"><i class="fas fa-globe"></i></button>
                                <button class="seg-layer-btn" data-layer="dark" title="Modo oscuro"><i class="fas fa-moon"></i></button>
                            </div>

                            <!-- Notificación de entrega completada -->
                            <div id="segLiveNotification" class="seg-notification" style="display:none">
                                <div class="seg-notification-content">
                                    <i class="fas fa-check-circle" style="font-size:1.5rem;"></i>
                                    <div>
                                        <strong>¡Pedido Entregado!</strong>
                                        <div style="font-size:0.85rem;opacity:0.9;">El pedido ha sido entregado exitosamente</div>
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
            </section>

        </main>
    </div>

    <!-- MODAL PARA AGREGAR/EDITAR PRODUCTO -->
    <div class="product-modal-overlay" id="productModal">
        <div class="product-modal-container">
            <div class="modal-progress-bar"></div>
            <div class="product-modal-header">
                <h2 id="modalTitle">Agregar Nuevo Producto</h2>
                <button class="product-modal-close" onclick="closeProductModal()">✕</button>
            </div>
            <div class="product-modal-body">
                <div class="modal-section">
                    <div class="modal-section-title">📷 Imágenes del producto *</div>
                    <div class="product-image-upload-area" onclick="document.getElementById('productImages').click()">
                        <input type="file" id="productImages" multiple accept="image/*" style="display: none;">
                        <div class="upload-icon">☁️</div>
                        <p>Seleccionar imágenes (máx. 6)</p>
                        <small>Arrastra y suelta o haz clic para seleccionar</small>
                    </div>
                    <div class="images-preview-grid" id="imagesPreviewGrid"></div>
                    <div style="text-align: center;"><button class="product-add-btn" onclick="document.getElementById('productImages').click()">Añadir imágenes</button></div>
                    <div class="product-image-note">La primera imagen será la principal.</div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">🏷️ Información básica</div>
                    <div class="product-form-group"><label class="required">Nombre del producto *</label><input type="text" id="productName" placeholder="Ej: Conjunto Deportivo Azul"></div>
                    <div class="product-form-row">
                        <div class="product-form-group"><label class="required">Categoría *</label><select id="productCategory"><option value="">Seleccionar categoría</option></select></div>
                        <div class="product-form-group"><label class="required">Subcategoría</label><select id="productSubcategory"><option value="">Sin subcategoría</option></select></div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">💰 Precio y stock</div>
                    <div class="product-form-row">
                        <div class="product-form-group"><label class="required">Precio en COP *</label><input type="number" id="productPrice" placeholder="Ej: 899900"></div>
                        <div class="product-form-group"><label class="required">Stock *</label><input type="number" id="productStock" placeholder="10" value="10" min="0"></div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">📏 Tallas disponibles *</div>
                    <div class="product-sizes-container" id="sizesContainer"></div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">📝 Descripción y características</div>
                    <div class="product-form-group"><label>Descripción del producto</label><textarea id="productDescription" rows="4" placeholder="Descripción detallada del producto..."></textarea></div>
                    <div class="product-form-group"><label>Características (una por línea)</label><textarea id="productFeatures" rows="4" placeholder="Material: 100% Algodón&#10;Lavable a máquina&#10;Ideal para uso diario"></textarea></div>
                </div>
            </div>
            <div class="product-modal-footer">
                <button class="btn btn-secondary" onclick="closeProductModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="saveProduct()" id="modalSaveBtn">Agregar Producto</button>
            </div>
        </div>
    </div>

    <!-- MODAL PARA CATEGORÍAS -->
    <div class="category-modal-overlay" id="categoryModal">
        <div class="category-modal-container">
            <div class="category-modal-header">
                <h2 id="categoryModalTitle">Agregar Categoría</h2>
                <button class="category-modal-close" onclick="closeCategoryModal()">✕</button>
            </div>
            <div class="category-modal-body">
                <div class="category-form-group"><label class="required">ID</label><input type="number" id="categoryId" readonly placeholder="ID automático"></div>
                <div class="category-form-group"><label class="required">Nombre</label><input type="text" id="categoryName" placeholder="Ej: Niños, Bebés, Body's..."></div>
                <div class="category-form-group" id="categoryParentGroup" style="display: none;"><label>Categoría Principal</label><select id="categoryParent"><option value="">Seleccionar categoría principal</option></select></div>
                <div class="checkbox-wrapper"><input type="checkbox" id="categoryInBar" checked><label for="categoryInBar">Mostrar en la barra de categorías</label></div>
                <div class="checkbox-hint">Si está marcado, aparecerá en la navegación superior de la tienda</div>
                <div class="category-form-group"><label>Número de Productos</label><input type="number" id="categoryProducts" value="0" min="0"></div>
            </div>
            <div class="category-modal-footer">
                <button class="btn btn-outline" onclick="closeCategoryModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="saveCategory()" id="categoryModalSaveBtn">Guardar</button>
            </div>
        </div>
    </div>

    <script src="<?= APP_URL ?>/assets/js/panel.js"></script>
    <script src="<?= APP_URL ?>/assets/js/seguimiento-perfil.js"></script>
</body>
</html>