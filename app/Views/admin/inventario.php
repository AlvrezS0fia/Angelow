<?php
/*
 |========================================================================
 | VISTA: admin/inventario.php
 |------------------------------------------------------------------------
 | QUÉ MUESTRA: Panel de administrador para la gestión completa del
 | inventario de productos (consultar stock, filtrar, editar y eliminar).
 |
 | ARCHIVOS EXTERNOS: inventario.css (estilos), back-button.css,
 | jsPDF para exportar el reporte a PDF y la API ficticia /api/inventario.
 |
 | JS QUE LA CONTROLA: el bloque <script> de este mismo archivo, que se
 | encarga de cargar productos, aplicar filtros, actualizar stock,
 | eliminar productos y exportar el listado a PDF.
 |========================================================================
*/
// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . APP_URL . '/auth/login');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - ANGELOW</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Librerías para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>const APP_URL = '<?= APP_URL ?>';</script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/inventario.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/back-button.css">

</head>
<body>

<!-- SECCIÓN: Encabezado de la página con el logo de ANGELOW y botón para volver al inicio -->
<header class="angelow-header">
    <a href="<?= APP_URL ?>/" class="logo">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>INVENTARIO</span>
        </div>
    </a>
    <a href="<?= APP_URL ?>/" class="btn-back-header">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
</header>

<main class="main-container">
    <!-- SECCIÓN: Título de la vista y botones para exportar el inventario a PDF o actualizar los datos -->
    <div class="section-header">
        <h2 class="section-title">Gestion de Inventario</h2>
        <div class="action-buttons">
            <button id="exportInventoryBtn" class="btn btn-secondary">Exportar a PDF</button>
            <button id="refreshInventoryBtn" class="btn btn-primary">Actualizar</button>
        </div>
    </div>

    <!-- SECCIÓN: Filtros para buscar productos por estado del stock, categoría o nombre -->
    <div class="inventory-filters">
        <div class="filter-group">
            <div class="filter-title">Estado del Stock</div>
            <div class="filter-options">
                <button class="filter-option active" data-inventory-filter="all">Todos</button>
                <button class="filter-option" data-inventory-filter="in-stock">En stock</button>
                <button class="filter-option" data-inventory-filter="low-stock">Stock bajo</button>
                <button class="filter-option" data-inventory-filter="out-of-stock">Sin stock</button>
            </div>
        </div>
        <div class="filter-group">
            <div class="filter-title">Categoria</div>
            <select id="inventoryCategoryFilter" class="filter-select">
                <option value="all">Todas las categorias</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-title">Busqueda</div>
            <input type="text" id="inventorySearchInput" class="search-input" placeholder="Buscar por nombre o ID...">
        </div>
    </div>

    <!-- SECCIÓN: Tarjetas resumen con métricas del inventario (totales, unidades, agotados y stock bajo) -->
    <div class="inventory-summary">
        <div class="metric-card">
            <div class="metric-header">
                <div>
                    <div class="metric-value" id="totalProductsInventory">0</div>
                    <div class="metric-label">Total Productos</div>
                </div>
                <div class="metric-icon inv-icon-products"><i class="fas fa-boxes-stacked"></i></div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-header">
                <div>
                    <div class="metric-value" id="totalStockInventory">0</div>
                    <div class="metric-label">Unidades Totales</div>
                </div>
                <div class="metric-icon inv-icon-stock"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-header">
                <div>
                    <div class="metric-value" id="outOfStockInventory">0</div>
                    <div class="metric-label">Productos Agotados</div>
                </div>
                <div class="metric-icon inv-icon-out"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-header">
                <div>
                    <div class="metric-value" id="lowStockInventory">0</div>
                    <div class="metric-label">Stock Bajo</div>
                </div>
                <div class="metric-icon inv-icon-low"><i class="fas fa-bell"></i></div>
            </div>
</div>
    </div>

    <!-- SECCIÓN: Tabla con el listado de productos del inventario que se rellena desde JavaScript -->
    <div class="inventory-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Precio</th>
                    <th>Stock Actual</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <tr><td colspan="8" style="text-align:center;">Cargando productos...</td></tr>
            </tbody>
        </table>
    </div>
</main>

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
            <p>Medellin, Colombia</p>
        </div>
        <div class="footer-section">
            <h3>Ayuda</h3>
            <ul class="footer-links">
                <li><a href="<?= APP_URL ?>/documentos/Pedidos_envios">Pedidos y Envios</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Devoluciones y Cambios</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Preguntas">Preguntas Frecuentes</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Guia_Tallas">Guia de Tallas</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Terminos">Terminos y Condiciones</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Legal</h3>
            <ul class="footer-links">
                <li><a href="<?= APP_URL ?>/documentos/Politicas_Priv">Politicas de Privacidad</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Terminos">Terminos y Condiciones</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Politicas_Env">Politicas de Envio</a></li>
                <li><a href="<?= APP_URL ?>/documentos/Politicas_devolucion">Politicas de Devolucion</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Siguenos</h3>
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

<!-- SECCIÓN: Modal para editar el stock de un producto (establecer, agregar o quitar unidades) -->
<!-- Modal Editar Stock - Profesional -->
<div class="modal-overlay" id="stockEditModal">
    <div class="modal-container modal-stock">
        <div class="modal-header modal-header-stock">
            <div class="modal-header-left">
                <div class="modal-icon-edit"><i class="fas fa-boxes-stacked"></i></div>
                <div>
                    <h3 id="stockEditModalTitle">Editar Stock</h3>
                    <span class="modal-subtitle" id="stockEditModalSubtitle"></span>
                </div>
            </div>
            <button class="modal-close" onclick="closeStockEditModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="stock-product-preview">
                <img id="stockEditProductImage" src="" alt="" class="stock-preview-img">
                <div class="stock-preview-info">
                    <div class="stock-preview-name" id="stockEditProductName">Producto</div>
                    <div class="stock-preview-meta">
                        <span class="stock-preview-price" id="stockEditProductPrice">$0</span>
                        <span class="stock-preview-category" id="stockEditProductCategory"></span>
                    </div>
                </div>
            </div>

            <div class="stock-current-display">
                <div class="stock-current-inner">
                    <span class="stock-current-label">Stock Actual</span>
                    <span class="stock-current-value" id="stockEditCurrent">0</span>
                    <span class="stock-current-unit">unidades</span>
                </div>
                <div class="stock-status-indicator" id="stockStatusIndicator">
                    <i class="fas fa-check-circle"></i>
                    <span id="stockStatusText">En stock</span>
                </div>
            </div>

            <div class="stock-operations">
                <div class="stock-op-tabs">
                    <button class="stock-op-tab active" data-op="set" onclick="setStockOp('set')">
                        <i class="fas fa-sliders"></i> Establecer
                    </button>
                    <button class="stock-op-tab" data-op="add" onclick="setStockOp('add')">
                        <i class="fas fa-plus-circle"></i> Agregar
                    </button>
                    <button class="stock-op-tab" data-op="subtract" onclick="setStockOp('subtract')">
                        <i class="fas fa-minus-circle"></i> Quitar
                    </button>
                </div>

                <div class="stock-input-group">
                    <label class="stock-input-label" id="stockInputLabel">Nuevo valor</label>
                    <div class="stock-qty-control">
                        <button type="button" class="stock-qty-btn" id="decrementQty">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="stockChangeAmount" class="stock-qty-input" value="0" min="0" step="1">
                        <button type="button" class="stock-qty-btn" id="incrementQty">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="stock-input-hint" id="qtyLimitHint">Establece el stock directamente</small>
                </div>

                <div class="stock-preview-result" id="stockPreviewResult" style="display:none;">
                    <span class="stock-preview-label">Resultado:</span>
                    <span class="stock-preview-before" id="stockPreviewBefore">0</span>
                    <i class="fas fa-arrow-right"></i>
                    <span class="stock-preview-after" id="stockPreviewAfter">0</span>
                </div>

                <div class="stock-input-group">
                    <label class="stock-input-label"><i class="fas fa-sticky-note"></i> Observaciones</label>
                    <textarea id="stockChangeReason" class="stock-textarea" rows="2" placeholder="Ej: Nueva mercancía, devolución, ajuste de inventario..."></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer modal-footer-stock">
            <button class="btn-stock-cancel" onclick="closeStockEditModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn-stock-save" id="btnSaveStock" onclick="saveStockUpdate()">
                <i class="fas fa-check"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- SECCIÓN: Modal de confirmación antes de eliminar un producto del inventario -->
<!-- Modal Confirmar Eliminación -->
<div class="modal-overlay" id="deleteConfirmModal">
    <div class="modal-container modal-delete">
        <div class="modal-header modal-header-delete">
            <div class="modal-header-left">
                <div class="modal-icon-delete"><i class="fas fa-trash-alt"></i></div>
                <div>
                    <h3>Eliminar Producto</h3>
                    <span class="modal-subtitle">Esta accion no se puede deshacer</span>
                </div>
            </div>
            <button class="modal-close" onclick="closeDeleteModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="delete-product-preview">
                <img id="deleteProductImage" src="" alt="" class="delete-preview-img">
                <div class="delete-preview-info">
                    <div class="delete-preview-name" id="deleteProductName"></div>
                    <div class="delete-preview-meta">
                        <span id="deleteProductCategory"></span>
                        <span id="deleteProductStock"></span>
                    </div>
                </div>
            </div>
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Se eliminará permanentemente este producto y todos sus datos asociados del inventario.</p>
            </div>
        </div>
        <div class="modal-footer modal-footer-delete">
            <button class="btn-stock-cancel" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn-delete-confirm" id="btnConfirmDelete" onclick="confirmDeleteProduct()">
                <i class="fas fa-trash-alt"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<script>
const APP_URL_BASE = APP_URL;

// Notificaciones emergentes (toast) que se usan en toda la vista para informar al administrador
function showToast({title, message, type = "info", duration = 4000}) {
    let container = document.getElementById("toastContainer");
    if (!container) {
        container = document.createElement("div");
        container.id = "toastContainer";
        container.className = "toast-container";
        document.body.appendChild(container);
    }
    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    let iconSvg = '';
    if(type === 'success') iconSvg = '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    else if(type === 'error') iconSvg = '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    else iconSvg = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    toast.innerHTML = `
        <div class="toast-icon">${iconSvg}</div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close">&times;</button>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 50);
    toast.querySelector(".toast-close").onclick = () => toast.remove();
    setTimeout(() => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); }, duration);
}

let inventoryProducts = [];
let currentFilter = 'all';
let currentCategory = 'all';
let currentSearch = '';
let editingProductId = null;
let deletingProductId = null;
let currentMaxLimit = 9999;
let currentStockOp = 'set';

// Carga inicial de los productos desde la API cuando se abre la vista
async function loadInitialProducts() {
    try {
        const res = await fetch(`${APP_URL_BASE}/api/inventario`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (Array.isArray(data)) {
            inventoryProducts = data.map(p => {
                let img = p.image || p.imagen || (p.imagenes ? (Array.isArray(p.imagenes) ? p.imagenes[0] : p.imagenes) : '');
                if (img && !img.startsWith('http') && !img.startsWith('data:') && !img.startsWith('/')) {
                    img = APP_URL_BASE + '/' + img;
                }
                return {
                    ...p,
                    name: p.name || p.nombre || 'Sin nombre',
                    category: p.category || p.categoria || 'Sin categoría',
                    price: p.price || p.precio || 0,
                    image: img,
                    stock: p.stock || p.stock_total || 0,
                    stock_minimo: p.stock_minimo || 10
                };
            });
        }
    } catch (e) {
        console.error('Error al cargar inventario:', e);
        showToast({ title: "Error", message: "No se pudo cargar el inventario", type: "error" });
    }
    updateCategoryFilter();
    renderInventoryTable();
    updateSummary();
}

function updateCategoryFilter() {
    const categories = [...new Set(inventoryProducts.map(p => p.category))].sort();
    const select = document.getElementById('inventoryCategoryFilter');
    if (select) {
        select.innerHTML = '<option value="all">Todas las categorías</option>';
        categories.forEach(cat => {
            select.innerHTML += `<option value="${cat}">${cat}</option>`;
        });
    }
}

function getFilteredProducts() {
    let filtered = [...inventoryProducts];
    if (currentFilter !== 'all') {
        if (currentFilter === 'in-stock') filtered = filtered.filter(p => p.stock > 10);
        else if (currentFilter === 'low-stock') filtered = filtered.filter(p => p.stock > 0 && p.stock <= 10);
        else if (currentFilter === 'out-of-stock') filtered = filtered.filter(p => p.stock === 0);
    }
    if (currentCategory !== 'all') filtered = filtered.filter(p => p.category === currentCategory);
    if (currentSearch) {
        const term = currentSearch.toLowerCase();
        filtered = filtered.filter(p => p.name.toLowerCase().includes(term) || p.id.toString().includes(term));
    }
    return filtered;
}

function renderInventoryTable() {
    const tbody = document.getElementById('inventoryTableBody');
    if (!tbody) return;
    const filtered = getFilteredProducts();
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:40px; color:var(--text-secondary);"><i class="fas fa-box-open" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>No hay productos que coincidan</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map(p => {
        let statusClass, statusText;
        if (p.stock === 0) { statusClass = 'status-danger'; statusText = 'Sin stock'; }
        else if (p.stock <= 10) { statusClass = 'status-warning'; statusText = 'Stock bajo'; }
        else { statusClass = 'status-success'; statusText = 'En stock'; }
        return `
            <tr>
                <td><span class="inv-id-badge">${p.id}</span></td>
                <td><img src="${p.image}" alt="${p.name}" class="inv-product-thumb" onerror="this.src='${APP_URL_BASE}/assets/imagenes/general/placeholder.png'"></td>
                <td><div class="inv-product-name">${p.name}</div></td>
                <td><span class="inv-category-tag">${p.category}</span></td>
                <td class="inv-price">$${p.price.toLocaleString()}</td>
                <td><span class="inv-stock-value ${p.stock === 0 ? 'inv-stock-zero' : p.stock <= 10 ? 'inv-stock-low' : ''}">${p.stock}</span></td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <div class="inv-actions">
                        <button class="action-btn inv-btn-edit" onclick="openStockEditModal(${p.id})" title="Editar stock">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn inv-btn-delete" onclick="openDeleteModal(${p.id})" title="Eliminar producto">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function updateSummary() {
    const totalProducts = inventoryProducts.length;
    const totalStock = inventoryProducts.reduce((sum, p) => sum + p.stock, 0);
    const outOfStock = inventoryProducts.filter(p => p.stock === 0).length;
    const lowStock = inventoryProducts.filter(p => p.stock > 0 && p.stock <= 10).length;
    document.getElementById('totalProductsInventory').innerText = totalProducts;
    document.getElementById('totalStockInventory').innerText = totalStock;
    document.getElementById('outOfStockInventory').innerText = outOfStock;
    document.getElementById('lowStockInventory').innerText = lowStock;
}

function updateMaxLimit(changeType, currentStock) {
    let max = 9999;
    let hint = '';
    if (changeType === 'subtract') {
        max = currentStock;
        hint = `Máximo: ${max} unidades (stock actual)`;
    } else if (changeType === 'set') {
        hint = 'Establece el stock directamente (0 - 9,999)';
    } else if (changeType === 'add') {
        hint = 'Agrega unidades al stock actual';
    }
    currentMaxLimit = max;
    const amountInput = document.getElementById('stockChangeAmount');
    if (amountInput) {
        amountInput.max = max;
        let val = parseInt(amountInput.value);
        if (isNaN(val)) val = 0;
        if (val > max) amountInput.value = max;
        if (val < 0) amountInput.value = 0;
    }
    document.getElementById('qtyLimitHint').innerText = hint;
}

function setStockOp(op) {
    currentStockOp = op;
    document.querySelectorAll('.stock-op-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.stock-op-tab[data-op="${op}"]`).classList.add('active');
    const product = inventoryProducts.find(p => p.id === editingProductId);
    if (!product) return;
    updateMaxLimit(op, product.stock);
    const labels = { set: 'Nuevo valor', add: 'Unidades a agregar', subtract: 'Unidades a quitar' };
    document.getElementById('stockInputLabel').textContent = labels[op];
    document.getElementById('stockChangeAmount').value = op === 'set' ? product.stock : 0;
    updateStockPreview();
}

function updateStockPreview() {
    const product = inventoryProducts.find(p => p.id === editingProductId);
    if (!product) return;
    let amount = parseInt(document.getElementById('stockChangeAmount').value) || 0;
    let newStock = product.stock;
    if (currentStockOp === 'set') newStock = amount;
    else if (currentStockOp === 'add') newStock = product.stock + amount;
    else if (currentStockOp === 'subtract') newStock = Math.max(0, product.stock - amount);
    const previewEl = document.getElementById('stockPreviewResult');
    if (newStock !== product.stock || currentStockOp !== 'set') {
        previewEl.style.display = 'flex';
        document.getElementById('stockPreviewBefore').textContent = product.stock;
        document.getElementById('stockPreviewAfter').textContent = newStock;
        const afterEl = document.getElementById('stockPreviewAfter');
        afterEl.className = 'stock-preview-after';
        if (newStock === 0) afterEl.classList.add('preview-danger');
        else if (newStock <= 10) afterEl.classList.add('preview-warning');
        else afterEl.classList.add('preview-success');
    } else {
        previewEl.style.display = 'none';
    }
}

function openStockEditModal(productId) {
    const product = inventoryProducts.find(p => p.id === productId);
    if (!product) return;
    editingProductId = productId;
    document.getElementById('stockEditProductName').textContent = product.name;
    document.getElementById('stockEditProductPrice').textContent = '$' + product.price.toLocaleString();
    document.getElementById('stockEditProductCategory').textContent = product.category;
    document.getElementById('stockEditCurrent').textContent = product.stock;
    const img = document.getElementById('stockEditProductImage');
    img.src = product.image;
    img.alt = product.name;
    document.getElementById('stockChangeAmount').value = product.stock;
    document.getElementById('stockChangeReason').value = '';
    const statusEl = document.getElementById('stockStatusIndicator');
    const statusTextEl = document.getElementById('stockStatusText');
    if (product.stock === 0) { statusEl.className = 'stock-status-indicator status-empty'; statusTextEl.textContent = 'Agotado'; }
    else if (product.stock <= 10) { statusEl.className = 'stock-status-indicator status-low'; statusTextEl.textContent = 'Stock bajo'; }
    else { statusEl.className = 'stock-status-indicator status-ok'; statusTextEl.textContent = 'Disponible'; }
    setStockOp('set');
    document.getElementById('stockEditModal').classList.add('active');
}

function closeStockEditModal() {
    document.getElementById('stockEditModal').classList.remove('active');
    editingProductId = null;
    currentStockOp = 'set';
}

async function saveStockUpdate() {
    const product = inventoryProducts.find(p => p.id === editingProductId);
    if (!product) return;
    let amount = parseInt(document.getElementById('stockChangeAmount').value);
    if (isNaN(amount) || amount < 0) {
        showToast({ title: "Error", message: "Ingresa una cantidad válida", type: "error" });
        return;
    }
    const btn = document.getElementById('btnSaveStock');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    try {
        const res = await fetch(`${APP_URL_BASE}/api/inventario/ajustar`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: editingProductId, cantidad: amount, tipo: currentStockOp })
        });
        const data = await res.json();
        if (data.success) {
            product.stock = data.nuevo_stock;
            renderInventoryTable();
            updateSummary();
            closeStockEditModal();
            const tipoTexto = currentStockOp === 'add' ? 'agregadas' : currentStockOp === 'subtract' ? 'quitadas' : 'establecidas';
            showToast({ title: "Stock actualizado", message: `${product.name}: ${data.nuevo_stock} unidades (${tipoTexto} ${amount})`, type: "success" });
        } else {
            showToast({ title: "Error", message: data.error || "No se pudo actualizar el stock", type: "error" });
        }
    } catch (e) {
        showToast({ title: "Error", message: "Error de conexión con el servidor", type: "error" });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Guardar Cambios';
    }
}

function openDeleteModal(productId) {
    const product = inventoryProducts.find(p => p.id === productId);
    if (!product) return;
    deletingProductId = productId;
    document.getElementById('deleteProductName').textContent = product.name;
    document.getElementById('deleteProductCategory').textContent = product.category;
    document.getElementById('deleteProductStock').textContent = product.stock + ' unidades';
    const img = document.getElementById('deleteProductImage');
    img.src = product.image;
    img.alt = product.name;
    document.getElementById('deleteConfirmModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.remove('active');
    deletingProductId = null;
}

async function confirmDeleteProduct() {
    const product = inventoryProducts.find(p => p.id === deletingProductId);
    if (!product) return;
    const btn = document.getElementById('btnConfirmDelete');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
    try {
        const res = await fetch(`${APP_URL_BASE}/api/inventario/eliminar`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: deletingProductId })
        });
        const data = await res.json();
        if (data.success) {
            inventoryProducts = inventoryProducts.filter(p => p.id !== deletingProductId);
            renderInventoryTable();
            updateSummary();
            closeDeleteModal();
            showToast({ title: "Producto eliminado", message: `"${product.name}" fue eliminado correctamente`, type: "success" });
        } else {
            showToast({ title: "Error", message: data.error || "No se pudo eliminar el producto", type: "error" });
        }
    } catch (e) {
        showToast({ title: "Error", message: "Error de conexión con el servidor", type: "error" });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash-alt"></i> Eliminar';
    }
}

async function exportToPDF() {
    // Exporta el listado filtrado a un PDF con reporte de inventario
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(18);
    doc.text('ANGELOW - Reporte de Inventario', 14, 20);
    doc.setFontSize(10);
    doc.text(`Generado: ${new Date().toLocaleString()}`, 14, 30);
    const filtered = getFilteredProducts();
    const tableData = filtered.map(p => [p.id, p.name, p.category, '$' + p.price.toLocaleString(), p.stock.toString()]);
    doc.autoTable({
        head: [['ID', 'Producto', 'Categoría', 'Precio', 'Stock']],
        body: tableData,
        startY: 40,
        theme: 'striped',
        headStyles: { fillColor: [94, 157, 230] },
        margin: { left: 14, right: 14 }
    });
    doc.save(`inventario_${new Date().toISOString().slice(0,10)}.pdf`);
    showToast({ title: "Éxito", message: "Inventario exportado a PDF", type: "success" });
}

function initFilters() {
    document.querySelectorAll('[data-inventory-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-inventory-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-inventory-filter');
            renderInventoryTable();
        });
    });
    const catSelect = document.getElementById('inventoryCategoryFilter');
    if (catSelect) catSelect.addEventListener('change', function() { currentCategory = this.value; renderInventoryTable(); });
    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) searchInput.addEventListener('input', function() { currentSearch = this.value; renderInventoryTable(); });
    document.getElementById('refreshInventoryBtn')?.addEventListener('click', async () => {
        await loadInitialProducts();
        showToast({ title: "Inventario", message: "Datos recargados correctamente", type: "info" });
    });
    document.getElementById('exportInventoryBtn')?.addEventListener('click', exportToPDF);
}

function initQuantityButtons() {
    const decrementBtn = document.getElementById('decrementQty');
    const incrementBtn = document.getElementById('incrementQty');
    const amountInput = document.getElementById('stockChangeAmount');
    if (decrementBtn) decrementBtn.addEventListener('click', () => {
        let val = parseInt(amountInput.value) || 0;
        if (val > 0) { amountInput.value = val - 1; amountInput.dispatchEvent(new Event('input')); }
    });
    if (incrementBtn) incrementBtn.addEventListener('click', () => {
        let val = parseInt(amountInput.value) || 0;
        if (val < currentMaxLimit) { amountInput.value = val + 1; amountInput.dispatchEvent(new Event('input')); }
        else showToast({ title: "Límite", message: `Máximo ${currentMaxLimit} unidades`, type: "warning" });
    });
    if (amountInput) amountInput.addEventListener('input', function() {
        let val = parseInt(this.value);
        if (isNaN(val)) this.value = 0;
        if (val > currentMaxLimit) { this.value = currentMaxLimit; showToast({ title: "Límite", message: `Máximo: ${currentMaxLimit}`, type: "warning" }); }
        if (val < 0) this.value = 0;
        updateStockPreview();
    });
}

document.addEventListener('DOMContentLoaded', async function() {
    // Al cargar la página se piden los productos, se asignan los filtros y botones, y se muestra el año actual
    await loadInitialProducts();
    initFilters();
    initQuantityButtons();
    document.getElementById('currentYear').textContent = new Date().getFullYear();

    window.onclick = function(e) {
        if (e.target === document.getElementById('stockEditModal')) closeStockEditModal();
        if (e.target === document.getElementById('deleteConfirmModal')) closeDeleteModal();
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStockEditModal();
            closeDeleteModal();
        }
    });
});

window.openStockEditModal = openStockEditModal;
window.closeStockEditModal = closeStockEditModal;
window.saveStockUpdate = saveStockUpdate;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.confirmDeleteProduct = confirmDeleteProduct;
window.setStockOp = setStockOp;
</script>
<!-- Fin del bloque <script> que controla el inventario: está alimentado únicamente por este archivo (no hay JS externo) -->

</body>
</html>