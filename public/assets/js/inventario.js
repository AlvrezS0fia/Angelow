/**
 * ============================================================
 * ARCHIVO: inventario.js
 * QUÉ HACE: Gestiona el inventario de productos del panel admin
 *            con CRUD en memoria: filtros (todos/en stock/stock
 *            bajo/sin stock), búsqueda, edición de stock y
 *            exportación del reporte a PDF.
 * TIPO: DEMO (usa datos en memoria con APP_URL en imágenes)
 * ENDPOINTS QUE CONSUME: Ninguno (datos forzados en el navegador)
 * CLÁVES localStorage QUE USA: Ninguna
 * LIBRERÍAS EXTERNAS: jsPDF + jspdf-autotable (exportar PDF)
 * ============================================================
 */

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
// Listado de productos de ejemplo con el que se inicializa la tabla
let inventoryProducts = [];
let currentFilter = 'all';
let currentCategory = 'all';
let currentSearch = '';
let editingProductId = null;
let currentMaxLimit = 9999;

// Devuelve la lista inicial de productos de demostración
function loadInitialProducts() {
    return [
        { id: 1, name: "Conjunto Deportivo", category: "Ninos", price: 89990, stock: 15, image: APP_URL + "/assets/imagenes/ninos/Frente Conjunto Deportivo.png" },
        { id: 2, name: "Conjunto Size", category: "Ninos", price: 79990, stock: 8, image: APP_URL + "/assets/imagenes/ninos/Frente Conjunto Size.png" },
        { id: 3, name: "Body Nino", category: "Ninos", price: 34990, stock: 20, image: APP_URL + "/assets/imagenes/ninos/Frente Body Nino.png" },
        { id: 4, name: "Jogger Nino", category: "Ninos", price: 49990, stock: 5, image: APP_URL + "/assets/imagenes/ninos/Frente Jogger.png" },
        { id: 5, name: "Set Bebe", category: "Bebes", price: 99990, stock: 0, image: APP_URL + "/assets/imagenes/bebe/Frente Set Bebe.png" },
        { id: 6, name: "Conjunto Infantil", category: "Ninas", price: 85990, stock: 12, image: APP_URL + "/assets/imagenes/ninas/Frente Conjunto Infantil.png" },
        { id: 7, name: "Body Negro", category: "Ninos", price: 32990, stock: 3, image: APP_URL + "/assets/imagenes/ninas/Frente Body Negro.png" },
        { id: 8, name: "Set Falda", category: "Ninas", price: 89990, stock: 7, image: APP_URL + "/assets/imagenes/ninas/Frente Set Falda.png" }
    ];
}

// Llena el select de categorías con las categorías únicas de los productos
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

// Aplica los filtros activos (estado de stock, categoría y búsqueda)
// y devuelve los productos que coinciden
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

// Pinta las filas de la tabla de inventario según los filtros activos
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

// Actualiza las tarjetas de resumen (total productos, unidades, sin stock, stock bajo)
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

// Ajusta el límite máximo de unidades según el tipo de cambio elegido
// (aumentar, disminuir o fijar stock) y muestra la pista al usuario
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

// Abre el modal de edición de stock para el producto indicado
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

// Cierra el modal de edición de stock y limpia el producto en edición
function closeStockEditModal() {
    document.getElementById('stockEditModal').classList.remove('active');
    editingProductId = null;
}

// Aplica el cambio de stock según el tipo (aumentar/disminuir/fijar)
// y refresca la tabla, el resumen y el modal
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

// Genera y descarga un PDF con el reporte del inventario filtrado
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

// Conecta los botones de filtro, el select de categorías, el buscador
// y los botones de refrescar y exportar a sus respectivas acciones
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

// Vincula los botones +/-, el campo de cantidad y el selector de
// tipo de cambio para limitar la cantidad según el máximo permitido
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
document.addEventListener('DOMContentLoaded', function() {
    inventoryProducts = loadInitialProducts();
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