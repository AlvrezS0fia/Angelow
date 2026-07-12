<?php
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

    
</head>
<body>

<header class="angelow-header">
    <a href="<?= APP_URL ?>/" class="logo">
        <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
        <div class="logo-text">
            <span>ANGELOW</span>
            <span>INVENTARIO</span>
        </div>
    </a>
    <div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
        <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">  
    </div>
</header>

<main class="main-container">
    <div class="section-header">
        <h2 class="section-title">Gestion de Inventario</h2>
        <div class="action-buttons">
            <button id="exportInventoryBtn" class="btn btn-secondary">Exportar a PDF</button>
            <button id="refreshInventoryBtn" class="btn btn-primary">Actualizar</button>
        </div>
    </div>

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

<!-- Modal Editar Stock - Version Compacta -->
<div class="modal-overlay" id="stockEditModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Stock</h3>
            <button class="modal-close" onclick="closeStockEditModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="product-info-compact">
                <div class="product-name-compact" id="stockEditProductName">Producto</div>
                <div class="stock-current-compact">
                    <span class="stock-current-label">Stock actual:</span>
                    <span class="stock-current-value" id="stockEditCurrent">0</span>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-exchange-alt"></i> Tipo de Cambio</label>
                <select id="stockChangeType" class="form-input">
                    <option value="set">Establecer nuevo valor</option>
                    <option value="add">Agregar unidades</option>
                    <option value="subtract">Quitar unidades</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-sort-amount-up"></i> Cantidad</label>
                <div class="quantity-control">
                    <button type="button" class="qty-btn" id="decrementQty">−</button>
                    <input type="number" id="stockChangeAmount" class="quantity-input" value="0" min="0" step="1">
                    <button type="button" class="qty-btn" id="incrementQty">+</button>
                </div>
                <small class="limit-hint" id="qtyLimitHint"></small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Motivo</label>
                <textarea id="stockChangeReason" class="form-input" rows="2" placeholder="Ej: Nueva mercancia, devolucion, ajuste..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeStockEditModal()">Cancelar</button>
            <button class="btn-save" onclick="updateStock()">Guardar</button>
        </div>
    </div>
</div>

<script>
// Toast notifications system
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
        <button class="toast-close">×</button>
    `;
    
    container.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 50);
    toast.querySelector(".toast-close").onclick = () => toast.remove();
    setTimeout(() => { 
        toast.classList.remove("show"); 
        setTimeout(() => toast.remove(), 400); 
    }, duration);
}

// Inventory Products Data
let inventoryProducts = [];
let currentFilter = 'all';
let currentCategory = 'all';
let currentSearch = '';
let editingProductId = null;
let currentMaxLimit = 9999;

async function loadInitialProducts() {
    try {
        const res = await fetch(`${APP_URL}/api/inventario`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (Array.isArray(data)) {
            inventoryProducts = data.map(p => ({
                ...p,
                image: p.imagen || (p.imagenes ? (Array.isArray(p.imagenes) ? p.imagenes[0] : p.imagenes) : ''),
                stock: p.stock || p.stock_total || 0
            }));
        }
    } catch (e) {
        console.error('Error al cargar inventario:', e);
        showToast({ title: "Error", message: "No se pudo cargar el inventario desde la base de datos", type: "error" });
    }
    updateCategoryFilter();
    renderInventoryTable();
    updateSummary();
}

async function updateStock() {
    const product = inventoryProducts.find(p => p.id === editingProductId);
    if (!product) return;
    const changeType = document.getElementById('stockChangeType').value;
    let amount = parseInt(document.getElementById('stockChangeAmount').value);
    if (isNaN(amount) || amount < 0) {
        showToast({ title: "Error", message: "Ingresa una cantidad valida", type: "error" });
        return;
    }

    try {
        const res = await fetch(`${APP_URL}/api/inventario/ajustar`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: editingProductId,
                cantidad: amount,
                tipo: changeType
            })
        });
        const data = await res.json();
        
        if (data.success) {
            product.stock = data.nuevo_stock;
            renderInventoryTable();
            updateSummary();
            closeStockEditModal();
            const tipoTexto = changeType === 'add' ? 'agregado' : changeType === 'subtract' ? 'quitado' : 'establecido';
            showToast({ 
                title: "Stock actualizado", 
                message: `${product.nombre} ahora tiene ${data.nuevo_stock} unidades. (${tipoTexto} ${amount})`, 
                type: "success" 
            });
        } else {
            showToast({ title: "Error", message: data.error || "No se pudo actualizar el stock", type: "error" });
        }
    } catch (e) {
        showToast({ title: "Error", message: "Error al conectar con el servidor", type: "error" });
    }
}

function updateCategoryFilter() {
    const categories = [...new Set(inventoryProducts.map(p => p.category))];
    const select = document.getElementById('inventoryCategoryFilter');
    if (select) {
        select.innerHTML = '<option value="all">Todas las categorias</option>';
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
    if (currentCategory !== 'all') {
        filtered = filtered.filter(p => p.category === currentCategory);
    }
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
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No hay productos que coincidan con los filtros</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map(p => {
        let statusClass, statusText;
        if (p.stock === 0) { statusClass = 'status-danger'; statusText = 'Sin stock'; }
        else if (p.stock <= 10) { statusClass = 'status-warning'; statusText = 'Stock bajo'; }
        else { statusClass = 'status-success'; statusText = 'En stock'; }
        return `
            <tr>
                <td style="font-weight:600;">${p.id}</td>
                <td><img src="${p.image}" alt="${p.name}" style="width:50px; height:50px; object-fit:cover; border-radius:12px;" onerror="this.src='https://placehold.co/300x300/EDF4FC/5E9DE6?text=Producto'"></td>
                <td><strong>${p.name}</strong></td>
                <td>${p.category}</td>
                <td>$${p.price.toLocaleString()}</td>
                <td style="font-weight:700;">${p.stock}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td><button class="action-btn" onclick="openStockEditModal(${p.id})" title="Editar stock"><i class="fas fa-edit"></i></button></td>
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
        hint = `Maximo permitido: ${max} unidades (no puedes quitar mas del stock actual)`;
    } else if (changeType === 'set') {
        max = 9999;
        hint = `Puedes establecer cualquier valor (max. 9999)`;
    } else if (changeType === 'add') {
        max = 9999;
        hint = `Puedes agregar hasta 9999 unidades`;
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
    const hintSpan = document.getElementById('qtyLimitHint');
    if (hintSpan) hintSpan.innerText = hint;
}

function openStockEditModal(productId) {
    const product = inventoryProducts.find(p => p.id === productId);
    if (!product) return;
    editingProductId = productId;
    document.getElementById('stockEditProductName').innerText = product.name;
    document.getElementById('stockEditCurrent').innerText = product.stock;
    document.getElementById('stockChangeAmount').value = 0;
    document.getElementById('stockChangeReason').value = '';
    const changeType = document.getElementById('stockChangeType').value;
    updateMaxLimit(changeType, product.stock);
    document.getElementById('stockEditModal').classList.add('active');
}

function closeStockEditModal() {
    document.getElementById('stockEditModal').classList.remove('active');
    editingProductId = null;
}

function updateStock() {
    const product = inventoryProducts.find(p => p.id === editingProductId);
    if (!product) return;
    const changeType = document.getElementById('stockChangeType').value;
    let amount = parseInt(document.getElementById('stockChangeAmount').value);
    if (isNaN(amount) || amount < 0) {
        showToast({ title: "Error", message: "Ingresa una cantidad valida", type: "error" });
        return;
    }
    let newStock = product.stock;
    if (changeType === 'set') newStock = amount;
    else if (changeType === 'add') newStock += amount;
    else if (changeType === 'subtract') newStock = Math.max(0, product.stock - amount);
    
    product.stock = newStock;
    renderInventoryTable();
    updateSummary();
    closeStockEditModal();
    showToast({ title: "Stock actualizado", message: `${product.name} ahora tiene ${newStock} unidades.`, type: "success" });
}

async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(18);
    doc.text('ANGELOW - Reporte de Inventario', 14, 20);
    doc.setFontSize(10);
    doc.text(`Generado: ${new Date().toLocaleString()}`, 14, 30);
    const filtered = getFilteredProducts();
    const tableData = filtered.map(p => [p.id, p.name, p.category, `$${p.price.toLocaleString()}`, p.stock]);
    doc.autoTable({
        head: [['ID', 'Producto', 'Categoria', 'Precio', 'Stock']],
        body: tableData,
        startY: 40,
        theme: 'striped',
        headStyles: { fillColor: [94, 157, 230] },
        margin: { left: 14, right: 14 }
    });
    doc.save(`inventario_${new Date().toISOString().slice(0,10)}.pdf`);
    showToast({ title: "Exito", message: "Inventario exportado a PDF", type: "success" });
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
    const categorySelect = document.getElementById('inventoryCategoryFilter');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            currentCategory = this.value;
            renderInventoryTable();
        });
    }
    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearch = this.value;
            renderInventoryTable();
        });
    }
    document.getElementById('refreshInventoryBtn')?.addEventListener('click', () => {
        inventoryProducts = loadInitialProducts();
        updateCategoryFilter();
        renderInventoryTable();
        updateSummary();
        showToast({ title: "Inventario", message: "Datos recargados correctamente", type: "info" });
    });
    document.getElementById('exportInventoryBtn')?.addEventListener('click', exportToPDF);
}

function initQuantityButtons() {
    const decrementBtn = document.getElementById('decrementQty');
    const incrementBtn = document.getElementById('incrementQty');
    const amountInput = document.getElementById('stockChangeAmount');
    if (decrementBtn && incrementBtn && amountInput) {
        decrementBtn.addEventListener('click', () => {
            let val = parseInt(amountInput.value) || 0;
            if (val > 0) {
                amountInput.value = val - 1;
                amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        incrementBtn.addEventListener('click', () => {
            let val = parseInt(amountInput.value) || 0;
            if (val < currentMaxLimit) {
                amountInput.value = val + 1;
            } else {
                showToast({ title: "Limite alcanzado", message: `No puedes superar ${currentMaxLimit} unidades`, type: "warning" });
            }
        });
        amountInput.addEventListener('input', function() {
            let val = parseInt(this.value);
            if (isNaN(val)) this.value = 0;
            if (val > currentMaxLimit) {
                this.value = currentMaxLimit;
                showToast({ title: "Limite alcanzado", message: `Maximo permitido: ${currentMaxLimit}`, type: "warning" });
            }
            if (val < 0) this.value = 0;
        });
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', async function() {
    await loadInitialProducts();
    updateCategoryFilter();
    renderInventoryTable();
    updateSummary();
    initFilters();
    initQuantityButtons();
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    
    // Event listener for modal close on overlay click
    window.onclick = function(e) {
        if (e.target === document.getElementById('stockEditModal')) {
            closeStockEditModal();
        }
    };
    
    // Event listener for stock change type
    document.getElementById('stockChangeType')?.addEventListener('change', function() {
        const product = inventoryProducts.find(p => p.id === editingProductId);
        if (product) {
            updateMaxLimit(this.value, product.stock);
        }
    });
});

// Make functions globally available for inline onclick handlers
window.openStockEditModal = openStockEditModal;
window.closeStockEditModal = closeStockEditModal;
window.updateStock = updateStock;
</script>

</body>
</html>