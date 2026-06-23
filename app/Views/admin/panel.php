<?php
// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . APP_URL . '/auth/login');
    exit();
}
$user = $_SESSION['user'];
error_log(print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANGELOW - Panel de Administración</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css">

    <!-- Librerías externas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <!-- Leaflet CSS y JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
            <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
            <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">  
            </div>
            <button class="logout-btn" onclick="window.location.href='<?= APP_URL ?>/auth/logout'">Salir</button>
        </div>
    </header>

    <!-- Contenido Principal -->
    <div class="admin-container">

        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <ul class="admin-menu">
                <li><a href="#dashboard" class="active" data-section="dashboard"><img src="<?= APP_URL ?>/assets/imagenes/general/panel.png" class="admin-menu-icon" alt="Dashboard"><span>Dashboard</span></a></li>
                <li><a href="#products" data-section="products"><img src="<?= APP_URL ?>/assets/imagenes/general/producto.png" class="admin-menu-icon" alt="Productos"><span>Productos</span></a></li>
                <li><a href="#inventory" data-section="inventory"><img src="<?= APP_URL ?>/assets/imagenes/general/inventarios.png" class="admin-menu-icon" alt="Inventario"><span>Inventario</span></a></li>
                <li><a href="#categories" data-section="categories"><img src="<?= APP_URL ?>/assets/imagenes/general/categoria.png" class="admin-menu-icon" alt="Categorías"><span>Categorías</span></a></li>
                <li><a href="#orders" data-section="orders"><img src="<?= APP_URL ?>/assets/imagenes/general/pedir.png" class="admin-menu-icon" alt="Pedidos"><span>Pedidos</span></a></li>
                <li><a href="#customers" data-section="customers"><img src="<?= APP_URL ?>/assets/imagenes/general/cliente.png" class="admin-menu-icon" alt="Clientes"><span>Clientes</span></a></li>
                <li><a href="#delivery" data-section="delivery"><img src="<?= APP_URL ?>/assets/imagenes/general/reparto.png" class="admin-menu-icon" alt="Repartidores"><span>Repartidores</span></a></li>
                <li><a href="#analytics" data-section="analytics"><img src="<?= APP_URL ?>/assets/imagenes/general/analitica.png" class="admin-menu-icon" alt="Analítica"><span>Analítica</span></a></li>
                <li><a href="#settings" data-section="settings"><img src="<?= APP_URL ?>/assets/imagenes/general/ajuste.png" class="admin-menu-icon" alt="Configuración"><span>Configuración</span></a></li>
            </ul>
        </nav>

        <!-- Contenido Dinámico -->
        <main class="admin-content">

            <!--  SECCIÓN DASHBOARD  -->
            <section id="dashboard-section" class="admin-section active-section">
                <h1 class="dashboard-title">Panel de Control</h1>

                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalOrders">12</div>
                                <div class="metric-label">Pedidos Totales</div>
                            </div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/logistica.png" alt="Pedidos"></div>
                        </div>
                        <span class="metric-change positive">+2 este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="pendingOrders">3</div>
                                <div class="metric-label">Pendientes</div>
                            </div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/reloj.png" alt="Pendientes"></div>
                        </div>
                        <span class="metric-change negative">-1 esta semana</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalFavorites">0</div>
                                <div class="metric-label">Favoritos</div>
                            </div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/favoritos.png" alt="Favoritos"></div>
                        </div>
                        <span class="metric-change positive">+5 este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalRevenue">$628</div>
                                <div class="metric-label">Ganancias</div>
                            </div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/ganancia.png" alt="Ganancias"></div>
                        </div>
                        <span class="metric-change positive">+12% este mes</span>
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
                        <span style="font-weight: 700; color: var(--primary);" id="progressPercent">45%</span>
                    </div>
                    <div class="progress-bar-container"><div class="progress-bar" id="progressBar" style="width: 45%;"></div></div>
                    <div class="progress-info">
                        <span>Meta anual de ventas</span>
                        <span id="progressText">$15,000 / $35,000</span>
                    </div>
                    <button class="btn btn-primary" style="margin-top: 16px;">Ver Detalles</button>
                </div>
            </section>

            <!--  SECCIÓN PRODUCTOS  -->
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
                    <!-- Sara #44: La edición de productos solo es accesible para administradores (verificación al inicio del archivo).
                         Los botones de edición (action-edit) en cada tarjeta de producto abren el modal con los datos actuales.El formulario valida campos obligatorios antes de guardar. -->
                    <div class="products-grid" id="adminProductsGrid"></div>
                </div>
            </section>

            <!--  SECCIÓN INVENTARIO  -->
            <section id="inventory-section" class="admin-section" style="display: none;">
                <div class="section-header"><h2 class="section-title">Inventario</h2></div>
                <table class="admin-table" id="inventoryTable">
                    <thead>
                        <tr><th>ID</th><th>Producto</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </section>

            <!--  SECCIÓN CATEGORÍAS  -->
            <!-- Sofia #9: Panel de gestión de categorías (principales y subcategorías) -->
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

            <!--  SECCIÓN PEDIDOS  -->
            <section id="orders-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Pedidos</h2>
                    <div class="action-buttons" style="gap: 12px;">
                        <button id="exportOrdersBtn" class="btn btn-secondary">Exportar a PDF</button>
                        <button id="filterOrdersBtn" class="btn btn-primary">Filtrar</button>
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

            <!--  SECCIÓN CLIENTES  -->
            <section id="customers-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Clientes</h2>
                    <button id="addCustomerBtn" class="btn btn-primary">+ Nuevo Cliente</button>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Cliente</th><th>Email</th><th>Teléfono</th><th>Pedidos</th><th>Total Gastado</th><th>Acciones</th></tr></thead>
                    <tbody id="customersTable"></tbody>
                </table>
            </section>

            <!--  SECCIÓN REPARTIDORES -->
            <section id="delivery-section" class="admin-section" style="display: none;">
                <div class="section-header"><h2 class="section-title">Gestión de Repartidores</h2></div>
                <div class="delivery-grid" id="deliveryGrid"></div>
                <h3 class="section-title" style="margin: 30px 0 20px;">Listado General de Repartidores</h3>
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Foto</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Vehículo</th><th>Estado</th><th>Pedidos</th><th>Acciones</th></tr></thead>
                    <tbody id="deliveryTable"></tbody>
                </table>
            </section>

            <!--  SECCIÓN ANALÍTICA  -->
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
                            <div><div class="metric-value" id="visitorsCount">1,254</div><div class="metric-label">Visitantes</div></div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/visitante.png" alt="Visitantes"></div>
                        </div>
                        <span class="metric-change positive">+18% este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="conversionRate">3.2%</div><div class="metric-label">Tasa de Conversión</div></div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/conversion.png" alt="Conversión"></div>
                        </div>
                        <span class="metric-change positive">+0.4% este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="avgOrderValue">$124</div><div class="metric-label">Valor Promedio</div></div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/promedio.png" alt="Promedio"></div>
                        </div>
                        <span class="metric-change positive">+$12 este mes</span>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div><div class="metric-value" id="bounceRate">42%</div><div class="metric-label">Tasa de Rebote</div></div>
                            <div class="metric-icon"><img src="<?= APP_URL ?>/assets/imagenes/general/rebote.png" alt="Rebote"></div>
                        </div>
                        <span class="metric-change negative">+3% este mes</span>
                    </div>
                </div>
            </section>

            <!--  SECCIÓN CONFIGURACIÓN  -->
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

        </main>
    </div>

    <!--  MODAL PARA AGREGAR/EDITAR PRODUCTO  -->
      <!-- Sara #43: Formulario emergente modal- para creación de productos. Solo visible para administradores.
     Campos obligatorios: nombre, categoría, precio, stock, tallas.
     No guarda si algún campo obligatorio está vacío o contiene datos inválidos. -->
    <div class="product-modal-overlay" id="productModal">
        <div class="product-modal-container">
            <div class="modal-progress-bar"></div>
            <div class="product-modal-header">
                <h2 id="modalTitle">Agregar Nuevo Producto</h2>
                <button class="product-modal-close" onclick="closeProductModal()">✕</button>
            </div>
            <div class="product-modal-body">
                <div class="modal-section">
                    <div class="modal-section-title"><i class="fas fa-image"></i> Imágenes del producto *</div>
                    <div class="product-image-upload-area" onclick="document.getElementById('productImages').click()">
                        <input type="file" id="productImages" multiple accept="image/*" style="display: none;">
                        <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <p>Seleccionar imágenes (máx. 6)</p>
                        <small>Arrastra y suelta o haz clic para seleccionar</small>
                    </div>
                    <div class="images-preview-grid" id="imagesPreviewGrid"></div>
                    <div style="text-align: center;"><button class="product-add-btn" onclick="document.getElementById('productImages').click()">Añadir imágenes</button></div>
                    <div class="product-image-note">La primera imagen será la principal.</div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title"><i class="fas fa-tag"></i> Información básica</div>
                    <div class="product-form-group"><label class="required">Nombre del producto *</label><input type="text" id="productName" placeholder="Ej: Conjunto Deportivo Azul"></div>
                    <div class="product-form-row">
                        <div class="product-form-group"><label class="required">Categoría *</label><select id="productCategory"><option value="">Seleccionar categoría</option></select></div>
                        <div class="product-form-group"><label class="required">Subcategoría</label><select id="productSubcategory"><option value="">Sin subcategoría</option></select></div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title"><i class="fas fa-dollar-sign"></i> Precio y stock</div>
                    <div class="product-form-row">
                        <div class="product-form-group"><label class="required">Precio en COP *</label><input type="number" id="productPrice" placeholder="Ej: 899900"></div>
                        <!-- Sara #45: Gestión de niveles de stock. Solo administradores pueden modificar el stock desde el panel. -->
                        <div class="product-form-group"><label class="required">Stock *</label><input type="number" id="productStock" placeholder="10" value="10"></div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title"><i class="fas fa-ruler-combined"></i> Tallas disponibles *</div>
                    <div class="product-sizes-container" id="sizesContainer"></div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title"><i class="fas fa-align-left"></i> Descripción y características</div>
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

    <!--  MODAL PARA REPARTIDORES  -->
    <div class="delivery-modal-overlay" id="deliveryModal">
        <div class="delivery-modal-container">
            <div class="delivery-modal-header">
                <h2 id="deliveryModalTitle">Agregar Nuevo Repartidor</h2>
                <button class="delivery-modal-close">×</button>
            </div>
            <div class="delivery-modal-body">
                <div class="delivery-avatar-upload">
                    <div class="delivery-avatar-preview" id="deliveryAvatarPreview"><i class="fas fa-user"></i></div>
                    <label class="delivery-avatar-upload-btn"><input type="file" id="deliveryAvatar" accept="image/*" style="display: none;"><i class="fas fa-camera"></i> Subir foto</label>
                </div>

                <h3 class="modal-section-title"><i class="fas fa-user-circle"></i> Información Personal</h3>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Nombre Completo</label><input type="text" id="deliveryName" placeholder="Ej: Juan Pérez"></div>
                    <div class="delivery-form-group"><label class="required">Email</label><input type="email" id="deliveryEmail" placeholder="ejemplo@angelow.com"></div>
                </div>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Teléfono</label><input type="tel" id="deliveryPhone" placeholder="3001234567"></div>
                    <div class="delivery-form-group"><label class="required">Tipo Documento</label><select id="deliveryIdType"><option value="CC">Cédula de Ciudadanía</option><option value="CE">Cédula de Extranjería</option><option value="TI">Tarjeta de Identidad</option><option value="PAS">Pasaporte</option></select></div>
                </div>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Número Documento</label><input type="text" id="deliveryIdNumber" placeholder="1234567890"></div>
                    <div class="delivery-form-group"><label>Fecha Nacimiento</label><input type="date" id="deliveryBirthDate"></div>
                </div>
                <div class="delivery-form-group"><label>Dirección</label><input type="text" id="deliveryAddress" placeholder="Calle 123 #45-67, Ciudad"></div>
                <div class="delivery-form-group"><label>Tipo de Sangre</label><select id="deliveryBloodType"><option value="">Seleccionar</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="O+">O+</option><option value="O-">O-</option><option value="AB+">AB+</option><option value="AB-">AB-</option></select></div>

                <h3 class="modal-section-title"><i class="fas fa-truck"></i> Información Laboral</h3>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Vehículo</label><select id="deliveryVehicle"><option value="">Seleccionar</option><option value="Moto">Moto</option><option value="Carro">Carro</option><option value="Bicicleta">Bicicleta</option><option value="Camión">Camión</option></select></div>
                    <div class="delivery-form-group"><label>Placa</label><input type="text" id="deliveryLicensePlate" placeholder="ABC-123"></div>
                </div>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label>Número de Licencia</label><input type="text" id="deliveryLicenseNumber" placeholder="LIC-2025-001"></div>
                    <div class="delivery-form-group"><label class="required">Estado</label><select id="deliveryStatus"><option value="active">Activo</option><option value="inactive">Inactivo</option><option value="on-route">En ruta</option></select></div>
                </div>

                <h3 class="modal-section-title"><i class="fas fa-phone-alt"></i> Contacto de Emergencia</h3>
                <div class="delivery-form-group"><label>Contacto de Emergencia</label><input type="text" id="deliveryEmergencyContact" placeholder="Nombre y teléfono"></div>
                <div class="delivery-form-group"><label>Notas adicionales</label><textarea id="deliveryNotes" rows="3" placeholder="Información relevante..."></textarea></div>
            </div>
            <div class="delivery-modal-footer">
                <button class="btn btn-secondary">Cancelar</button>
                <button class="btn btn-primary" id="deliveryModalSaveBtn">Agregar Repartidor</button>
            </div>
        </div>
    </div>

    <!--  MODAL PARA CATEGORÍAS - Sofia #9 -->
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
</body>
</html>