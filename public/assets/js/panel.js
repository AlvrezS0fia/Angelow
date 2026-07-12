// ======================== DATOS GLOBALES ========================
let mainCategories = JSON.parse(localStorage.getItem('angelow_main_categories')) || [
    { id: 1, nombre: "Bebés", enBarra: true, productos: 24 },
    { id: 2, nombre: "Niños", enBarra: true, productos: 32 },
    { id: 3, nombre: "Niñas", enBarra: true, productos: 41 },
    { id: 4, nombre: "Edición especial", enBarra: true, productos: 3 },
    { id: 5, nombre: "Oferta", enBarra: true, productos: 12 },
    { id: 6, nombre: "Todos", enBarra: true, productos: 150 },
    { id: 7, nombre: "Popular", enBarra: true, productos: 25 }
];

let subCategories = JSON.parse(localStorage.getItem('angelow_sub_categories')) || [
    { id: 101, nombre: "Body's", padre: "Bebés", enBarra: true, productos: 10 },
    { id: 102, nombre: "Pijamas", padre: "Bebés", enBarra: true, productos: 8 },
    { id: 103, nombre: "Vestidos", padre: "Niñas", enBarra: true, productos: 15 },
    { id: 104, nombre: "Conjuntos", padre: "Niños", enBarra: true, productos: 22 },
    { id: 105, nombre: "Accesorios", padre: "Niñas", enBarra: false, productos: 7 }
];

let products = JSON.parse(localStorage.getItem('angelow_products')) || [
    { id: 1, name: "Conjunto Deportivo", category: "Niños", subcategory: "Edición Especial", price: 899900, description: "Elegancia casual en su máxima expresión.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninos/Frente Conjunto Deportivo.png"], stock: 15, rating: 4.5, reviews: 28, features: ["Material: 100% Algodón","Lavable a máquina","Ideal para uso diario","Diseño unisex"] },
    { id: 2, name: "Conjunto Size", category: "Niños", subcategory: "Popular", price: 899900, description: "Conjunto moderno y cómodo para niños.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninos/Frente Conjunto Size.png"], stock: 8, rating: 4.2, reviews: 15, features: ["Material: Poliéster y algodón","Lavable a máquina","Secado rápido"] },
    { id: 3, name: "Body Niño", category: "Niños", subcategory: "Niño", price: 899900, description: "Body cómodo y práctico para bebés.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninos/Frente Body Niño.png"], stock: 20, rating: 4.7, reviews: 42, features: ["100% Algodón orgánico","Broches en la entrepierna","Sin etiquetas irritantes"] },
    { id: 4, name: "Jogger Niño", category: "Niños", subcategory: "Niño", price: 899900, description: "Pantalones jogger cómodos y modernos.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninos/Frente Jogger.png"], stock: 5, rating: 4.3, reviews: 19, features: ["Material: Jersey de algodón","Cintura elástica","Bolsillos laterales"] },
    { id: 5, name: "Set Bebé", category: "Bebés", subcategory: "Edición especial", price: 899900, description: "Set completo para bebés recién nacidos.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/bebe/Frente Set Bebe.png"], stock: 0, rating: 4.8, reviews: 36, features: ["Material hipoalergénico","Set de 3 piezas","Ideal para recién nacidos"] },
    { id: 6, name: "Conjunto Infantil", category: "Niñas", subcategory: "Niña", price: 899900, description: "Conjunto elegante y divertido para niñas.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninas/Frente Conjunto Infantil.png"], stock: 12, rating: 4.6, reviews: 31, features: ["Estampado exclusivo","Material: Algodón y elastano","Comodidad máxima"] },
    { id: 7, name: "Body Negro", category: "Niños", subcategory: "Popular", price: 899900, description: "Body negro básico para niños.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninas/Frente Body Negro.png"], stock: 3, rating: 4.4, reviews: 23, features: ["Color negro clásico","100% Algodón","Broches de metal"] },
    { id: 8, name: "Set Falda", category: "Niñas", subcategory: "Popular", price: 899900, description: "Set con falda para niñas.", sizes: ["2","4","6","8"], imgs: ["../assets/imagenes/ninas/Frente Set Falda.png"], stock: 7, rating: 4.9, reviews: 47, features: ["Falda con vuelo","Top a juego","Material ligero"] }
];

// ======================== PEDIDOS EN TIEMPO REAL ========================
let orders = [];

async function cargarPedidos() {
    try {
        const res = await fetch(`${APP_URL}/api/pedidos`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (Array.isArray(data)) {
            orders = data.map(p => ({
                id: p.id,
                numero_pedido: p.numero_pedido,
                orderNumber: String(p.id).padStart(3, '0'),
                customer: p.nombre_cliente || p.nombre_usuario || 'Cliente',
                date: p.fecha_pedido ? new Date(p.fecha_pedido).toISOString() : new Date().toISOString(),
                total: parseFloat(p.total) || 0,
                status: p.estado || 'pendiente',
                address: p.direccion_envio || '',
                city: p.ciudad || '',
                phone: p.telefono_cliente || '',
                products: [],
                productos: [],
                cliente: {
                    nombre: p.nombre_cliente || '',
                    email: p.email_cliente || '',
                    telefono: p.telefono_cliente || '',
                    cedula: p.cedula_cliente || ''
                },
                envio: {
                    direccion: p.direccion_envio || '',
                    destinatario: p.destinatario || '',
                    metodo: p.metodo_envio === 'express' ? 'Envío Express' : 'Envío Normal',
                    costo: parseFloat(p.costo_envio) || 0
                },
                pago: {
                    metodo: p.metodo_pago === 'mercadopago' ? 'Mercado Pago' : 'PSE'
                },
                subtotal: parseFloat(p.subtotal) || 0,
                descuento: parseFloat(p.descuento) || 0,
                total: parseFloat(p.total) || 0,
                estado: p.estado || 'pendiente',
                usuario_id: p.usuario_id,
                id: p.id,
                total_productos: p.total_productos || 0,
                lat: p.latitud_destino,
                lng: p.longitud_destino
            }));
            localStorage.setItem('angelow_orders', JSON.stringify(orders));
            renderOrdersList();
            renderOrdersTable();
            updateMapMarkers();
            updateMetrics();
        }
    } catch (e) {
        console.error('Error al cargar pedidos desde BD:', e);
        const localOrders = JSON.parse(localStorage.getItem('angelow_orders')) || [];
        if (localOrders.length > 0) {
            orders = localOrders;
            renderOrdersList();
            renderOrdersTable();
        }
    }
}

async function cambiarEstadoPedido(pedidoId, nuevoEstado) {
    try {
        const res = await fetch(`${APP_URL}/api/pedidos/estado`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: pedidoId, estado: nuevoEstado })
        });
        const data = await res.json();
        if (data.success) {
            showToast({ title: "Éxito", message: `Estado del pedido actualizado a ${nuevoEstado}`, type: "success" });
            cargarPedidos();
        } else {
            showToast({ title: "Error", message: data.error || 'No se pudo actualizar', type: "error" });
        }
    } catch (e) {
        console.error('Error:', e);
        showToast({ title: "Error", message: 'Error al actualizar estado', type: "error" });
    }
}

let deliveryDrivers = JSON.parse(localStorage.getItem('angelow_delivery_drivers')) || [
    { id: 1, name: "Carlos Martínez", email: "carlos.martinez@angelow.com", phone: "3001112233", idNumber: "1234567890", idType: "CC", address: "Calle 45 #23-12, Medellín", vehicle: "Moto", licensePlate: "ABC-123", licenseNumber: "LIC-2025-001", emergencyContact: "María Martínez - 3001112244", status: "active", currentRoute: "ORD-001", completedDeliveries: 156, rating: 4.8, hireDate: "2024-01-15", birthDate: "1990-05-20", bloodType: "O+", avatar: null, notes: "Repartidor destacado del mes" },
    { id: 2, name: "Andrea López", email: "andrea.lopez@angelow.com", phone: "3004445566", idNumber: "9876543210", idType: "CC", address: "Carrera 32 #67-89, Bogotá", vehicle: "Carro", licensePlate: "XYZ-789", licenseNumber: "LIC-2025-002", emergencyContact: "Pedro López - 3004445577", status: "on-route", currentRoute: "ORD-002, ORD-003", completedDeliveries: 89, rating: 4.9, hireDate: "2024-03-10", birthDate: "1992-08-15", bloodType: "A+", avatar: null, notes: "Prefiere rutas del norte" },
    { id: 3, name: "Javier Rodríguez", email: "javier.rodriguez@angelow.com", phone: "3007778899", idNumber: "4567891230", idType: "CC", address: "Avenida 68 #12-34, Cali", vehicle: "Bicicleta", licensePlate: "N/A", licenseNumber: "LIC-2025-003", emergencyContact: "Ana Rodríguez - 3007778800", status: "inactive", currentRoute: "", completedDeliveries: 45, rating: 4.5, hireDate: "2024-06-20", birthDate: "1988-11-30", bloodType: "B-", avatar: null, notes: "En período de prueba" }
];

// ======================== VARIABLES GLOBALES ========================
let salesChart, productsChart, trafficChart, conversionChart;
let editingProductId = null;
let selectedImages = [];
let map = null;
let mapMarkers = [];
let selectedOrderId = null;
let cart = JSON.parse(localStorage.getItem("angelow_cart")) || [];
let favorites = JSON.parse(localStorage.getItem("angelow_favorites")) || [];
let activeCategory = "all";
let activeStock = "all";
let activeSort = "name";
let selectedSizes = {};
let editingDeliveryId = null;
let selectedDeliveryAvatar = null;
let editingCategoryId = null;
let editingSubcategoryId = null;
let currentModalType = 'categoria';

// ======================== FUNCIONES DE ALMACENAMIENTO ========================
function saveAllData() {
    localStorage.setItem('angelow_main_categories', JSON.stringify(mainCategories));
    localStorage.setItem('angelow_sub_categories', JSON.stringify(subCategories));
    localStorage.setItem('angelow_products', JSON.stringify(products));
    localStorage.setItem('angelow_delivery_drivers', JSON.stringify(deliveryDrivers));
    localStorage.setItem('angelow_orders', JSON.stringify(orders));
    updateClientCategories();
}

function updateClientCategories() {
    const visibleMainCategories = mainCategories.filter(cat => cat.enBarra === true).map(cat => cat.nombre);
    const visibleSubCategories = subCategories.filter(sub => sub.enBarra === true).map(sub => sub.nombre);
    const clientCategories = ["Todos", ...visibleMainCategories, ...visibleSubCategories];
    localStorage.setItem('angelow_client_categories', JSON.stringify(clientCategories));
}

function showToast({ title, message, type = "success", duration = 4000 }) {
    const container = document.getElementById("toastContainer");
    const toast = document.createElement("div");
    toast.className = `toast ${type}`;

    const icons = {
        success: '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        warning: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        error: '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        info: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    toast.innerHTML = `
        <div class="toast-icon">${icons[type]}</div>
        <div class="toast-content">
            ${title ? `<div class="toast-title">${title}</div>` : ''}
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close">×</button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 100);
    toast.querySelector(".toast-close").onclick = () => toast.remove();
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 400);
    }, duration);
}

// ======================== SECCIÓN PRODUCTOS ========================
function setupImageUpload() {
    const imageInput = document.getElementById('productImages');
    if (!imageInput) return;

    imageInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);

        if (selectedImages.length + files.length > 6) {
            showToast({ title: "Error", message: "Solo puedes subir máximo 6 imágenes", type: "error" });
            return;
        }

        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    selectedImages.push({ url: e.target.result, file: file });
                    updateImagesPreview();
                };
                reader.readAsDataURL(file);
            }
        });

        imageInput.value = '';
    });
}

function updateImagesPreview() {
    const container = document.getElementById('imagesPreviewGrid');
    if (!container) return;

    let imagesHTML = '';

    selectedImages.forEach((img, index) => {
        imagesHTML += `
            <div class="image-preview ${index === 0 ? 'main' : ''}">
                <img src="${img.url}" alt="Preview ${index + 1}">
                <button onclick="removeImage(${index})">×</button>
            </div>
        `;
    });

    container.innerHTML = imagesHTML;
}

window.removeImage = function(index) {
    selectedImages.splice(index, 1);
    updateImagesPreview();
};

window.toggleSize = function(element, size) {
    element.classList.toggle('selected');
}

function renderProducts() {
    const grid = document.getElementById("adminProductsGrid");
    if (!grid) return;

    let filteredProducts = [...products];

    if (activeCategory !== "all") {
        filteredProducts = filteredProducts.filter(p => p.category === activeCategory);
    }

    if (activeStock !== "all") {
        if (activeStock === "in-stock") {
            filteredProducts = filteredProducts.filter(p => p.stock > 10);
        } else if (activeStock === "low-stock") {
            filteredProducts = filteredProducts.filter(p => p.stock > 0 && p.stock <= 10);
        } else if (activeStock === "out-of-stock") {
            filteredProducts = filteredProducts.filter(p => p.stock === 0);
        }
    }

    if (activeSort === "name") {
        filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
    } else if (activeSort === "price") {
        filteredProducts.sort((a, b) => b.price - a.price);
    } else if (activeSort === "stock") {
        filteredProducts.sort((a, b) => b.stock - a.stock);
    }

    if (filteredProducts.length === 0) {
        grid.innerHTML = `
            <div class="no-results" style="grid-column:1/-1; text-align:center; padding:100px 20px; color:var(--text-secondary);">
                No se encontraron productos
            </div>
        `;
        return;
    }

    grid.innerHTML = filteredProducts.map(p => {
        let stockClass = p.stock > 10 ? 'stock-ok' : p.stock > 0 ? 'stock-low' : 'stock-out';
        let stockText = p.stock > 10 ? 'En stock' : p.stock > 0 ? `Solo ${p.stock} left` : 'Sin stock';

        let imgSrc = p.imgs && p.imgs[0] ? p.imgs[0] : 'assets/imagenes/general/logos.png';
        if (imgSrc && !imgSrc.startsWith('http') && !imgSrc.startsWith('data:')) {
            imgSrc = APP_URL + '/' + imgSrc.replace(/^\.?\//, '');
        }

        return `
            <div class="card-container">
                <div class="card">
                    <div class="my-logo"><img src="${APP_URL}/assets/imagenes/general/logos.png"></div>
                    <img src="${imgSrc}" class="product-img" onerror="this.src='${APP_URL}/assets/imagenes/general/logos.png'">
                    <h2>${p.name}</h2>
                    <p>${p.subcategory}</p>
                    <div class="stock-info ${stockClass}">${stockText}</div>
                </div>

                <div class="modal">
                    <div class="my-logo"><img src="${APP_URL}/assets/imagenes/general/logos.png"></div>
                    <h3>${p.name}</h3>
                    <p class="subtitle">${p.category} • ${p.subcategory}</p>

                    <div class="sizes">
                        ${p.sizes.map(s => `
                            <div class="size-box ${selectedSizes[p.id] === s ? 'selected' : ''}" 
                                 onclick="event.stopPropagation(); selectSize(${p.id},'${s}')">${s}</div>
                        `).join("")}
                    </div>

                    <div class="price">COP $${p.price.toLocaleString()}</div>
                    <div class="stock-info ${stockClass}" style="margin:8px 0;">${stockText}</div>

                    <div class="buttons">
                        <button class="buy-btn ${p.stock === 0 ? 'disabled' : ''}" 
                                ${p.stock === 0 ? 'disabled' : ''} 
                                onclick="event.stopPropagation(); addToCart(${p.id})">Comprar</button>
                        <button class="fav-btn ${favorites.includes(p.id) ? 'active' : ''}" 
                                onclick="event.stopPropagation(); toggleFavorite(${p.id})">
                            <img src="${APP_URL}/assets/imagenes/general/favoritos.png">
                        </button>
                    </div>

                    <div style="display:flex; gap:8px; margin-top:12px;">
                        <button class="action-btn action-edit" onclick="event.stopPropagation(); editProduct(${p.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                                <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                            </svg>
                        </button>
                        <button class="action-btn action-delete" onclick="event.stopPropagation(); deleteProduct(${p.id}, '${p.name}')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join("");
}

function selectSize(id, size) {
    event.stopPropagation();
    selectedSizes[id] = size;
    renderProducts();
}

window.editProduct = function(id) {
    const product = products.find(p => p.id === id);
    if (product) {
        openProductModal(product);
    }
};

window.deleteProduct = function(id, productName) {
    if (event) {
        event.stopPropagation();
    }

    if (confirm(`¿Estás seguro de que deseas eliminar el producto "${productName}"?\n\nEsta acción no se puede deshacer.`)) {
        const index = products.findIndex(p => p.id === id);

        if (index !== -1) {
            const deletedProductName = products[index].name;
            products.splice(index, 1);

            if (selectedSizes[id]) {
                delete selectedSizes[id];
            }

            if (favorites.includes(id)) {
                favorites = favorites.filter(favId => favId !== id);
                localStorage.setItem("angelow_favorites", JSON.stringify(favorites));
            }

            if (cart.some(item => item.id === id)) {
                cart = cart.filter(item => item.id !== id);
                localStorage.setItem("angelow_cart", JSON.stringify(cart));
            }

            saveAllData();
            renderProducts();

            showToast({
                title: "Producto eliminado",
                message: `"${deletedProductName}" ha sido eliminado correctamente`,
                type: "success"
            });
        }
    }
};

function openProductModal(product = null) {
    const modal = document.getElementById('productModal');
    const modalTitle = document.getElementById('modalTitle');
    const saveBtn = document.getElementById('modalSaveBtn');

    if (!modal || !modalTitle || !saveBtn) return;

    selectedImages = [];
    updateImagesPreview();

    document.getElementById('productName').value = '';
    document.getElementById('productCategory').value = '';
    document.getElementById('productSubcategory').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '10';
    document.getElementById('productDescription').value = '';
    document.getElementById('productFeatures').value = '';

    document.querySelectorAll('#sizesContainer .size-box').forEach(box => {
        box.classList.remove('selected');
    });

    if (product) {
        modalTitle.textContent = 'Editar Producto';
        saveBtn.textContent = 'Guardar Cambios';

        document.getElementById('productName').value = product.name || '';
        document.getElementById('productCategory').value = product.category || '';
        document.getElementById('productSubcategory').value = product.subcategory || '';
        document.getElementById('productPrice').value = product.price || '';
        document.getElementById('productStock').value = product.stock || '10';
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productFeatures').value = (product.features || []).join('\n');

        if (product.sizes && product.sizes.length) {
            document.querySelectorAll('#sizesContainer .size-box').forEach(box => {
                const size = box.textContent.trim();
                if (product.sizes.includes(size)) {
                    box.classList.add('selected');
                }
            });
        }

        editingProductId = product.id;
    } else {
        modalTitle.textContent = 'Agregar Nuevo Producto';
        saveBtn.textContent = 'Agregar Producto';
        editingProductId = null;
    }

    saveBtn.disabled = false;
    saveBtn.style.opacity = '1';
    saveBtn.style.cursor = 'pointer';

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    const saveBtn = document.getElementById('modalSaveBtn');

    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
            saveBtn.innerHTML = editingProductId ? 'Guardar Cambios' : 'Agregar Producto';
        }
    }
}

function saveProduct() {
    const saveBtn = document.getElementById('modalSaveBtn');
    if (!saveBtn) return;

    if (saveBtn.disabled) {
        return;
    }

    saveBtn.disabled = true;
    saveBtn.style.opacity = '0.6';
    saveBtn.style.cursor = 'not-allowed';

    const originalText = saveBtn.textContent;
    saveBtn.innerHTML = editingProductId ? 'Guardando...' : 'Agregando...';

    const name = document.getElementById('productName')?.value.trim();
    const category = document.getElementById('productCategory')?.value;
    const subcategory = document.getElementById('productSubcategory')?.value;
    const price = document.getElementById('productPrice')?.value.trim();
    const stock = document.getElementById('productStock')?.value;
    const description = document.getElementById('productDescription')?.value.trim();
    const featuresText = document.getElementById('productFeatures')?.value.trim();

    const selectedSizes = [];
    document.querySelectorAll('#sizesContainer .size-box.selected').forEach(box => {
        selectedSizes.push(box.textContent.trim());
    });

    let hasError = false;

    if (!name) {
        showToast({ title: "Error", message: "Por favor ingresa el nombre del producto", type: "error" });
        hasError = true;
    } else if (!category) {
        showToast({ title: "Error", message: "Por favor selecciona una categoría", type: "error" });
        hasError = true;
    } else if (!price) {
        showToast({ title: "Error", message: "Por favor ingresa el precio", type: "error" });
        hasError = true;
    } else if (selectedSizes.length === 0) {
        showToast({ title: "Error", message: "Por favor selecciona al menos una talla", type: "error" });
        hasError = true;
    }

    if (hasError) {
        saveBtn.disabled = false;
        saveBtn.style.opacity = '1';
        saveBtn.style.cursor = 'pointer';
        saveBtn.innerHTML = originalText;
        return;
    }

    const features = featuresText ? featuresText.split('\n').filter(f => f.trim()) : [];

    setTimeout(() => {
        try {
            if (editingProductId) {
                const index = products.findIndex(p => p.id === editingProductId);
                if (index !== -1) {
                    products[index] = {
                        ...products[index],
                        name,
                        category,
                        subcategory: subcategory || "",
                        price: parseInt(price),
                        stock: parseInt(stock),
                        sizes: selectedSizes,
                        description,
                        features,
                        imgs: selectedImages.length ? selectedImages.map(img => img.url) : products[index].imgs
                    };

                    renderProducts();
                    saveAllData();
                    showToast({ title: "Éxito", message: `Producto "${name}" actualizado correctamente`, type: "success" });
                }
            } else {
                const newProduct = {
                    id: products.length > 0 ? Math.max(...products.map(p => p.id)) + 1 : 1,
                    name,
                    category,
                    subcategory: subcategory || "",
                    price: parseInt(price),
                    stock: parseInt(stock),
                    sizes: selectedSizes,
                    description: description || 'Descripción del producto',
                    features: features.length ? features : ['Material de alta calidad', 'Diseño exclusivo', 'Comodidad garantizada'],
                    imgs: selectedImages.length ? selectedImages.map(img => img.url) : ['assets/imagenes/general/logos.png'],
                    rating: 4.0,
                    reviews: 0
                };

                products.push(newProduct);
                renderProducts();
                saveAllData();
                showToast({ title: "Éxito", message: `Producto "${name}" agregado correctamente`, type: "success" });
            }

            setTimeout(() => {
                closeProductModal();
            }, 300);

        } catch (error) {
            console.error('Error al guardar producto:', error);
            showToast({ title: "Error", message: "Ocurrió un error al guardar el producto", type: "error" });

            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
            saveBtn.innerHTML = originalText;
        }
    }, 100);
}

// ======================== SECCIÓN CATEGORÍAS ========================
function renderMainCategories() {
    const tbody = document.getElementById('mainCategoriesTableBody');
    if (!tbody) return;

    if (mainCategories.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    No hay categorías principales. Haz clic en "Añadir Categoría" para comenzar.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = mainCategories.map(cat => `
        <tr>
            <td style="font-weight: 600; color: var(--primary);">${cat.id}</td>
            <td class="category-name">${cat.nombre}</td>
            <td>
                <span class="badge-bar ${cat.enBarra ? 'active' : 'inactive'}">
                    ${cat.enBarra ? '✓' : '✕'}
                </span>
            </td>
            <td><span class="product-count">${cat.productos}</span></td>
            <td class="action-cell">
                <button class="action-btn action-edit" onclick="editMainCategory(${cat.id})" title="Editar categoría">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                        <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                    </svg>
                </button>
                <button class="action-btn action-delete" onclick="deleteMainCategory(${cat.id})" title="Eliminar categoría">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderSubCategories() {
    const tbody = document.getElementById('subcategoriesTableBody');
    if (!tbody) return;

    if (subCategories.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    No hay subcategorías. Haz clic en "Añadir Subcategoría" para comenzar.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = subCategories.map(sub => `
        <tr>
            <td style="font-weight: 600; color: var(--primary);">${sub.id}</td>
            <td class="category-name">${sub.nombre}</td>
            <td><span style="color: var(--primary); font-weight: 500;">${sub.padre}</span></td>
            <td>
                <span class="badge-bar ${sub.enBarra ? 'active' : 'inactive'}">
                    ${sub.enBarra ? '✓' : '✕'}
                </span>
            </td>
            <td><span class="product-count">${sub.productos}</span></td>
            <td class="action-cell">
                <button class="action-btn action-edit" onclick="editSubCategory(${sub.id})" title="Editar subcategoría">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                        <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                    </svg>
                </button>
                <button class="action-btn action-delete" onclick="deleteSubCategory(${sub.id})" title="Eliminar subcategoría">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
}

function updateCategorySelects() {
    const categorySelect = document.getElementById('productCategory');
    const subcategorySelect = document.getElementById('productSubcategory');
    const parentSelect = document.getElementById('categoryParent');
    const filterContainer = document.getElementById('categoryFilters');

    if (categorySelect) {
        categorySelect.innerHTML = '<option value="">Seleccionar categoría</option>';
        mainCategories.forEach(cat => {
            categorySelect.innerHTML += `<option value="${cat.nombre}">${cat.nombre}</option>`;
        });
    }

    if (subcategorySelect) {
        subcategorySelect.innerHTML = '<option value="">Sin subcategoría</option>';
        subCategories.forEach(sub => {
            subcategorySelect.innerHTML += `<option value="${sub.nombre}">${sub.nombre} (${sub.padre})</option>`;
        });
    }

    if (parentSelect) {
        parentSelect.innerHTML = '<option value="">Seleccionar categoría principal</option>';
        mainCategories.forEach(cat => {
            parentSelect.innerHTML += `<option value="${cat.nombre}">${cat.nombre}</option>`;
        });
    }

    if (filterContainer) {
        let filterHtml = '<div class="filter-option active" data-category="all"><div class="filter-checkbox"></div><span>Todos</span></div>';
        mainCategories.forEach(cat => {
            filterHtml += `<div class="filter-option" data-category="${cat.nombre}"><div class="filter-checkbox"></div><span>${cat.nombre}</span></div>`;
        });
        filterContainer.innerHTML = filterHtml;
    }
}

function openCategoryModal(tipo, item = null) {
    const modal = document.getElementById('categoryModal');
    const modalTitle = document.getElementById('categoryModalTitle');
    const saveBtn = document.getElementById('categoryModalSaveBtn');
    const parentGroup = document.getElementById('categoryParentGroup');
    const parentSelect = document.getElementById('categoryParent');

    if (!modal || !modalTitle || !saveBtn) return;

    currentModalType = tipo;

    if (tipo === 'subcategoria') {
        modalTitle.textContent = item ? 'Editar Subcategoría' : 'Agregar Subcategoría';
        parentGroup.style.display = 'block';
        updateCategorySelects();
    } else {
        modalTitle.textContent = item ? 'Editar Categoría' : 'Agregar Categoría';
        parentGroup.style.display = 'none';
    }

    if (item) {
        saveBtn.textContent = 'Guardar Cambios';

        document.getElementById('categoryId').value = item.id || '';
        document.getElementById('categoryName').value = item.nombre || '';
        if (tipo === 'subcategoria' && item.padre) {
            document.getElementById('categoryParent').value = item.padre;
        }
        document.getElementById('categoryInBar').checked = item.enBarra !== false;
        document.getElementById('categoryProducts').value = item.productos || 0;

        if (tipo === 'subcategoria') {
            editingSubcategoryId = item.id;
            editingCategoryId = null;
        } else {
            editingCategoryId = item.id;
            editingSubcategoryId = null;
        }
    } else {
        saveBtn.textContent = 'Guardar';

        let newId;
        if (tipo === 'subcategoria') {
            newId = subCategories.length > 0 ? Math.max(...subCategories.map(c => c.id)) + 1 : 101;
        } else {
            newId = mainCategories.length > 0 ? Math.max(...mainCategories.map(c => c.id)) + 1 : 1;
        }

        document.getElementById('categoryId').value = newId;
        document.getElementById('categoryName').value = '';
        if (tipo === 'subcategoria') {
            document.getElementById('categoryParent').value = '';
        }
        document.getElementById('categoryInBar').checked = true;
        document.getElementById('categoryProducts').value = 0;

        editingCategoryId = null;
        editingSubcategoryId = null;
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function saveCategory() {
    const id = parseInt(document.getElementById('categoryId').value);
    const nombre = document.getElementById('categoryName').value.trim();
    const enBarra = document.getElementById('categoryInBar').checked;
    const productos = parseInt(document.getElementById('categoryProducts').value) || 0;

    if (!nombre) {
        showToast({ title: "Error", message: "El nombre es obligatorio", type: "error" });
        return;
    }

    if (currentModalType === 'subcategoria') {
        const padre = document.getElementById('categoryParent').value;

        if (!padre) {
            showToast({ title: "Error", message: "Debes seleccionar una categoría principal", type: "error" });
            return;
        }

        if (editingSubcategoryId) {
            const index = subCategories.findIndex(s => s.id === editingSubcategoryId);
            if (index !== -1) {
                subCategories[index] = {
                    ...subCategories[index],
                    nombre,
                    padre,
                    enBarra,
                    productos
                };
                showToast({ title: "Éxito", message: `Subcategoría "${nombre}" actualizada`, type: "success" });
            }
        } else {
            subCategories.push({ id, nombre, padre, enBarra, productos });
            showToast({ title: "Éxito", message: `Subcategoría "${nombre}" agregada`, type: "success" });
        }
    } else {
        if (editingCategoryId) {
            const index = mainCategories.findIndex(c => c.id === editingCategoryId);
            if (index !== -1) {
                const oldNombre = mainCategories[index].nombre;

                mainCategories[index] = {
                    ...mainCategories[index],
                    nombre,
                    enBarra,
                    productos
                };

                if (oldNombre !== nombre) {
                    subCategories.forEach(s => {
                        if (s.padre === oldNombre) {
                            s.padre = nombre;
                        }
                    });
                }

                showToast({ title: "Éxito", message: `Categoría "${nombre}" actualizada`, type: "success" });
            }
        } else {
            mainCategories.push({ id, nombre, enBarra, productos });
            showToast({ title: "Éxito", message: `Categoría "${nombre}" agregada`, type: "success" });
        }
    }

    renderMainCategories();
    renderSubCategories();
    updateCategorySelects();
    saveAllData();
    closeCategoryModal();
}

window.editMainCategory = function(id) {
    const category = mainCategories.find(c => c.id === id);
    if (category) {
        openCategoryModal('categoria', category);
    }
};

window.editSubCategory = function(id) {
    const subcategory = subCategories.find(s => s.id === id);
    if (subcategory) {
        openCategoryModal('subcategoria', subcategory);
    }
};

window.deleteMainCategory = function(id) {
    const category = mainCategories.find(c => c.id === id);
    if (!category) return;

    if (confirm(`¿Estás seguro de eliminar la categoría "${category.nombre}"?`)) {
        const hasSubcategories = subCategories.some(s => s.padre === category.nombre);

        if (hasSubcategories) {
            if (!confirm("Esta categoría tiene subcategorías. ¿Continuar?")) {
                return;
            }
            subCategories = subCategories.filter(s => s.padre !== category.nombre);
        }

        mainCategories = mainCategories.filter(c => c.id !== id);
        renderMainCategories();
        renderSubCategories();
        updateCategorySelects();
        saveAllData();
        showToast({ title: "Éxito", message: `Categoría eliminada`, type: "success" });
    }
};

window.deleteSubCategory = function(id) {
    const subcategory = subCategories.find(s => s.id === id);
    if (!subcategory) return;

    if (confirm(`¿Estás seguro de eliminar la subcategoría "${subcategory.nombre}"?`)) {
        subCategories = subCategories.filter(s => s.id !== id);
        renderSubCategories();
        updateCategorySelects();
        saveAllData();
        showToast({ title: "Éxito", message: `Subcategoría eliminada`, type: "success" });
    }
};

window.openCategoryModal = openCategoryModal;
window.closeCategoryModal = closeCategoryModal;
window.saveCategory = saveCategory;

// ======================== SECCIÓN PEDIDOS (TIEMPO REAL) ========================
function initMap() {
    const colombiaCenter = [4.5709, -74.2973];

    map = L.map('orderMap').setView(colombiaCenter, 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    updateMapMarkers();
}

function updateMapMarkers(filteredOrders = orders) {
    if (!map) return;

    mapMarkers.forEach(marker => map.removeLayer(marker));
    mapMarkers = [];

    filteredOrders.forEach(order => {
        if (order.lat && order.lng) {
            const marker = L.marker([order.lat, order.lng]).addTo(map);

            marker.bindPopup(`
                <div class="marker-popup">
                    <h4>${order.customer}</h4>
                    <p>Pedido: ${order.id}</p>
                    <p>${order.address}</p>
                    <p>Total: $${order.total.toLocaleString()}</p>
                    <span class="status-badge ${getStatusClass(order.status)}">${getStatusText(order.status)}</span>
                    <br>
                    <button onclick="selectOrder('${order.id}')" style="margin-top:8px; padding:4px 8px; background:var(--primary); color:white; border:none; border-radius:4px; cursor:pointer;">
                        Ver detalles
                    </button>
                </div>
            `);

            marker.orderId = order.id;
            mapMarkers.push(marker);
        }
    });

    if (mapMarkers.length > 0) {
        const group = L.featureGroup(mapMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

window.selectOrder = function(orderId) {
    selectedOrderId = orderId;

    document.querySelectorAll('.order-item').forEach(item => {
        item.classList.remove('selected');
        if (item.dataset.id === orderId) {
            item.classList.add('selected');

            const order = orders.find(o => o.id == orderId || o.numero_pedido == orderId);
            if (order && map && order.lat && order.lng) {
                map.setView([order.lat, order.lng], 13);
                map.eachLayer(layer => {
                    if (layer instanceof L.Marker && layer.orderId == orderId) {
                        layer.openPopup();
                    }
                });
            }
        }
    });
};

function getStatusText(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'processing': 'En proceso',
        'shipped': 'Enviado',
        'delivered': 'Entregado',
        'cancelled': 'Cancelado'
    };
    return statusMap[status] || status;
}

function getStatusClass(status) {
    const classMap = {
        'pending': 'status-pending',
        'processing': 'status-pending',
        'shipped': 'status-shipped',
        'delivered': 'status-delivered',
        'cancelled': 'status-cancelled'
    };
    return classMap[status] || 'status-pending';
}

function renderOrdersList(ordersToRender = orders) {
    const container = document.getElementById('ordersList');
    if (!container) return;

    if (ordersToRender.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                No se encontraron pedidos
            </div>
        `;
        return;
    }

    container.innerHTML = ordersToRender.map(order => {
        const orderId = order.id || order.numero_pedido || 'N/A';
        const orderNumber = order.orderNumber || order.numero_pedido || String(order.id || '');
        
        return `
            <div class="order-item ${orderId === selectedOrderId ? 'selected' : ''}" data-id="${orderId}" onclick="selectOrder('${orderId}')">
                <div class="order-item-header">
                    <span class="order-item-id">#${orderNumber}</span>
                    <span class="order-item-status ${getStatusClass(order.status)}">${getStatusText(order.status)}</span>
                </div>
                <div class="order-item-customer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    ${order.customer || order.cliente?.nombre || 'Cliente'}
                </div>
                <div class="order-item-address">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    ${order.address || order.envio?.direccion || 'Sin dirección'}
                </div>
                <div class="order-item-details">
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        ${order.date ? new Date(order.date).toLocaleDateString('es-CO') : '-'}
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5M17 5v14M9.5 5H7M9.5 5v14"></path>
                        </svg>
                        COP $${(order.total || 0).toLocaleString()}
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2.18"></rect>
                            <line x1="8" y1="2" x2="8" y2="22"></line>
                            <line x1="16" y1="2" x2="16" y2="22"></line>
                            <line x1="2" y1="8" x2="22" y2="8"></line>
                            <line x1="2" y1="16" x2="22" y2="16"></line>
                        </svg>
                        ${order.products?.length || order.total_productos || 0} items
                    </span>
                </div>
            </div>
        `;
    }).join('');
}

function renderOrdersTable(ordersToRender = orders) {
    const tbody = document.getElementById('ordersTable');
    if (!tbody) return;

    if (ordersToRender.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    No hay pedidos registrados
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = ordersToRender.map(order => {
        const orderId = order.id || order.numero_pedido || 'N/A';
        const orderNumber = order.orderNumber || order.numero_pedido || String(order.id || '');
        const estadoSelect = `
            <select class="estado-select" data-order-id="${order.id}" onchange="cambiarEstadoPedido(${order.id}, this.value)">
                <option value="pendiente" ${order.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                <option value="confirmado" ${order.estado === 'confirmado' ? 'selected' : ''}>Confirmado</option>
                <option value="procesando" ${order.estado === 'procesando' ? 'selected' : ''}>En proceso</option>
                <option value="listo" ${order.estado === 'listo' ? 'selected' : ''}>Listo</option>
                <option value="en_camino" ${order.estado === 'en_camino' ? 'selected' : ''}>En camino</option>
                <option value="entregado" ${order.estado === 'entregado' ? 'selected' : ''}>Entregado</option>
                <option value="cancelado" ${order.estado === 'cancelado' ? 'selected' : ''}>Cancelado</option>
            </select>
        `;

        return `
            <tr>
                <td style="font-weight: 600; color: var(--primary);">#${orderNumber}</td>
                <td>${order.customer || order.cliente?.nombre || 'Cliente'}</td>
                <td>${order.date ? new Date(order.date).toLocaleDateString('es-CO') : '-'}</td>
                <td>COP $${(order.total || 0).toLocaleString()}</td>
                <td>${estadoSelect}</td>
                <td>${order.address || order.envio?.direccion || 'Sin dirección'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn action-view" title="Ver detalles" onclick="verDetallesPedido(${order.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

window.editOrder = function(id) {
    const order = orders.find(o => o.id == id);
    if (!order) return;
    
    const statuses = ['pendiente', 'confirmado', 'procesando', 'listo', 'en_camino', 'entregado', 'cancelado'];
    const currentIndex = statuses.indexOf(order.estado || order.status);
    const nextStatus = statuses[(currentIndex + 1) % statuses.length];
    
    cambiarEstadoPedido(id, nextStatus);
};

window.verDetallesPedido = function(pedidoId) {
    const order = orders.find(o => o.id == pedidoId || o.numero_pedido == pedidoId);
    if (!order) {
        showToast({ title: "Error", message: "Pedido no encontrado", type: "error" });
        return;
    }

    const productos = order.products || order.productos || [];
    const productosHtml = productos.length > 0 
        ? productos.map(p => `
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">${p.nombre || p.name || 'Producto'}</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: center;">${p.talla || 'Única'}</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: center;">${p.cantidad || p.quantity || 1}</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: right;">COP $${(p.precioUnitario || p.price || 0).toLocaleString()}</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">COP $${((p.precioUnitario || p.price || 0) * (p.cantidad || p.quantity || 1)).toLocaleString()}</td>
            </tr>
        `).join('')
        : '<tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-secondary);">Sin productos registrados</td></tr>';

    const detalleHtml = `
        <div style="max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 20px 60px rgba(30, 58, 138, 0.15); overflow: hidden; padding: 30px; border: 1px solid #e8edf5;">
            <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 24px;">
                <h2 style="color: #1e3a8a; font-size: 24px; margin: 0 0 8px 0; font-weight: 800;">Pedido #${order.orderNumber || order.numero_pedido || order.id}</h2>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; color: #4b5563; font-size: 14px;">
                    <span><strong>Fecha:</strong> ${order.date ? new Date(order.date).toLocaleDateString('es-CO') : '-'}</span>
                    <span><strong>Estado:</strong> <span class="status-badge ${getStatusClass(order.estado || order.status)}">${getStatusText(order.estado || order.status)}</span></span>
                    <span><strong>Total:</strong> <strong style="color: #1e3a8a;">COP $${(order.total || 0).toLocaleString()}</strong></span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e8edf5;">
                <div>
                    <h3 style="color: #1e3a8a; font-size: 12px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Datos del Cliente</h3>
                    <p style="margin: 4px 0; color: #1f2937; font-weight: 600;">${order.cliente?.nombre || order.customer || 'Cliente'}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 13px;">${order.cliente?.email || order.email_cliente || ''}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 13px;">${order.cliente?.telefono || order.phone || ''}</p>
                </div>
                <div>
                    <h3 style="color: #1e3a8a; font-size: 12px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Datos de Envío</h3>
                    <p style="margin: 4px 0; color: #1f2937; font-weight: 600;">${order.envio?.destinatario || order.cliente?.nombre || 'Cliente'}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 13px;">${order.envio?.direccion || order.address || 'Sin dirección'}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 13px;">${order.envio?.metodo || 'Envío Normal'}</p>
                </div>
            </div>

            <div style="margin-bottom: 20px; border-radius: 12px; border: 1px solid #e8edf5; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #1e3a8a 0%, #2a4f9e 100%);">
                            <th style="padding: 12px 16px; text-align: left; color: #ffffff; font-weight: 700; font-size: 12px; text-transform: uppercase;">Producto</th>
                            <th style="padding: 12px 16px; text-align: center; color: #ffffff; font-weight: 700; font-size: 12px; text-transform: uppercase;">Talla</th>
                            <th style="padding: 12px 16px; text-align: center; color: #ffffff; font-weight: 700; font-size: 12px; text-transform: uppercase;">Cant.</th>
                            <th style="padding: 12px 16px; text-align: right; color: #ffffff; font-weight: 700; font-size: 12px; text-transform: uppercase;">Precio Unit.</th>
                            <th style="padding: 12px 16px; text-align: right; color: #ffffff; font-weight: 700; font-size: 12px; text-transform: uppercase;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${productosHtml}
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e8edf5;">
                <div style="width: 280px;">
                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                        <span style="color: #4b5563;">Subtotal</span>
                        <span style="font-weight: 600;">COP $${(order.subtotal || order.total || 0).toLocaleString()}</span>
                    </div>
                    ${(order.descuento || 0) > 0 ? `
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #10b981;">
                            <span>Descuento</span>
                            <span style="font-weight: 600;">- COP $${(order.descuento || 0).toLocaleString()}</span>
                        </div>
                    ` : ''}
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-top: 2px solid #1e3a8a; margin-top: 8px;">
                        <span style="color: #1e3a8a; font-weight: 800; font-size: 16px;">TOTAL</span>
                        <span style="color: #1e3a8a; font-weight: 900; font-size: 18px;">COP $${(order.total || 0).toLocaleString()}</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <button onclick="this.closest('.modal-overlay').remove()" style="background: #e8edf5; color: #1e3a8a; border: none; padding: 12px 30px; border-radius: 50px; font-size: 14px; font-weight: 700; cursor: pointer;">
                    Cerrar
                </button>
            </div>
        </div>
    `;

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;';
    overlay.innerHTML = detalleHtml;
    document.body.appendChild(overlay);
};

function setupOrderFilters() {
    const statusFilter = document.getElementById('orderStatusFilter');
    const cityFilter = document.getElementById('orderCityFilter');
    const searchInput = document.getElementById('orderSearchInput');

    if (!statusFilter || !cityFilter || !searchInput) return;

    function applyFilters() {
        const status = statusFilter.value;
        const city = cityFilter.value;
        const search = searchInput.value.toLowerCase().trim();

        let filtered = orders.filter(order => {
            if (status !== 'all' && order.status !== status) return false;
            if (city !== 'all' && order.city !== city) return false;
            if (search) {
                return order.customer.toLowerCase().includes(search) ||
                    order.id.toLowerCase().includes(search) ||
                    order.address.toLowerCase().includes(search);
            }
            return true;
        });

        renderOrdersList(filtered);
        renderOrdersTable(filtered);
        updateMapMarkers(filtered);
    }

    statusFilter.addEventListener('change', applyFilters);
    cityFilter.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', applyFilters);
}

// ======================== REFRESCAR PEDIDOS EN TIEMPO REAL ========================
async function refreshOrdersRealTime() {
    await cargarPedidos();
}

// ======================== SECCIÓN CLIENTES ========================
let usuarios = [];

function cargarUsuarios() {
    fetch(`${APP_URL}/api/clientes`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (Array.isArray(data)) {
            usuarios = data;
            renderCustomersTable();
            const totalUsersEl = document.getElementById('totalUsers');
            if (totalUsersEl) {
                totalUsersEl.textContent = usuarios.length;
            }
            const usersChangeEl = document.getElementById('usersChange');
            if (usersChangeEl) {
                usersChangeEl.textContent = `+${usuarios.length} registrados`;
            }
        }
    })
    .catch(err => console.error('Error al cargar usuarios:', err));
}

function buscarUsuarios(termino) {
    fetch(`${APP_URL}/api/clientes/buscar`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ nombre: termino })
    })
    .then(res => res.json())
    .then(data => {
        if (Array.isArray(data)) {
            usuarios = data;
            renderCustomersTable();
        }
    })
    .catch(err => console.error('Error al buscar usuarios:', err));
}

function renderCustomersTable() {
    const tbody = document.getElementById('customersTable');
    if (!tbody) return;

    if (usuarios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    No se encontraron usuarios registrados
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = usuarios.map(usuario => {
        const rolClass = usuario.rol === 'administrador' ? 'role-admin' : usuario.rol === 'repartidor' ? 'role-delivery' : 'role-client';
        const iniciales = (usuario.nombre || 'U').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
        const avatarColor = usuario.rol === 'administrador' ? '#EF4444' : usuario.rol === 'repartidor' ? '#F59E0B' : '#5E9DE6';

        return `
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: ${avatarColor}; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; flex-shrink: 0;">
                            ${iniciales}
                        </div>
                        <span style="font-weight: 600;">${usuario.nombre}</span>
                    </div>
                </td>
                <td>${usuario.email}</td>
                <td>${usuario.telefono || '-'}</td>
                <td>${usuario.fecha_registro ? new Date(usuario.fecha_registro).toLocaleDateString('es-CO') : '-'}</td>
                <td><span class="badge-rol ${rolClass}">${usuario.rol}</span></td>
                <td>
                    <div class="action-buttons">
                        <select class="rol-select" data-user-id="${usuario.id}" data-user-name="${usuario.nombre.replace(/'/g, "\\'")}">
                            <option value="cliente" ${usuario.rol === 'cliente' ? 'selected' : ''}>Cliente</option>
                            <option value="repartidor" ${usuario.rol === 'repartidor' ? 'selected' : ''}>Repartidor</option>
                            <option value="administrador" ${usuario.rol === 'administrador' ? 'selected' : ''}>Administrador</option>
                        </select>
                        <button class="btn btn-primary btn-sm btn-rol" onclick="cambiarRol(${usuario.id}, '${usuario.nombre.replace(/'/g, "\\'")}')" title="Actualizar rol">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                                <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

window.cambiarRol = function(id, nombre) {
    const select = document.querySelector(`.rol-select[data-user-id="${id}"]`);
    if (!select) return;

    const nuevoRol = select.value;
    if (!confirm(`¿Estás seguro de que deseas cambiar el rol de "${nombre}" a ${nuevoRol}?`)) {
        return;
    }

    fetch(`${APP_URL}/api/clientes/rol`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id, rol: nuevoRol })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const rolLabel = nuevoRol === 'administrador' ? 'Administrador' : nuevoRol === 'repartidor' ? 'Repartidor' : 'Cliente';
            const rolIcon = nuevoRol === 'administrador' ? '🔐' : nuevoRol === 'repartidor' ? '🚚' : '👤';
            showToast({ 
                title: "Rol actualizado correctamente", 
                message: `${rolIcon} ${nombre} ahora tiene el rol de ${rolLabel}`, 
                type: "success",
                duration: 5000
            });
            cargarUsuarios();
        } else {
            showToast({ title: "Error", message: data.error || 'No se pudo actualizar el rol', type: "error" });
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast({ title: "Error", message: 'Error al actualizar el rol', type: "error" });
    });
};

function setupCustomerSearch() {
    const searchInput = document.getElementById('customerSearch');
    if (!searchInput) return;

    let debounceTimer = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const termino = this.value.trim();
            if (termino === '') {
                cargarUsuarios();
            } else {
                buscarUsuarios(termino);
            }
        }, 400);
    });
}

// ======================== SECCIÓN REPARTIDORES ========================
function renderDeliveryGrid() {
    const grid = document.getElementById('deliveryGrid');
    if (!grid) return;

    if (deliveryDrivers.length === 0) {
        grid.innerHTML = `
            <div class="no-results" style="grid-column:1/-1; text-align:center; padding:100px 20px; color:var(--text-secondary);">
                No hay repartidores registrados
            </div>
        `;
        return;
    }

    grid.innerHTML = deliveryDrivers.map(driver => {
        const statusText = {
            'active': 'Activo',
            'inactive': 'Inactivo',
            'on-route': 'En ruta'
        }[driver.status] || driver.status;

        const statusClass = {
            'active': 'active',
            'inactive': 'inactive',
            'on-route': 'on-route'
        }[driver.status] || 'inactive';

        return `
            <div class="delivery-card">
                <div class="delivery-header">
                    <div class="delivery-avatar">
                        ${driver.avatar ? `<img src="${driver.avatar}" alt="${driver.name}">` : driver.name.split(' ').map(n => n[0]).join('').substring(0,2)}
                    </div>
                    <div class="delivery-info">
                        <div class="delivery-name">${driver.name}</div>
                        <span class="delivery-role">Repartidor</span>
                    </div>
                </div>

                <div class="delivery-details">
                    <div class="delivery-detail-item">
                        <span class="delivery-detail-label">Contacto</span>
                        <span class="delivery-detail-value">
                            ${driver.phone}
                        </span>
                    </div>

                    <div class="delivery-detail-item">
                        <span class="delivery-detail-label">Email</span>
                        <span class="delivery-detail-value">
                            ${driver.email}
                        </span>
                    </div>

                    <div class="delivery-detail-item">
                        <span class="delivery-detail-label">Vehículo</span>
                        <span class="delivery-detail-value">
                            ${driver.vehicle}
                        </span>
                    </div>

                    <div class="delivery-detail-item">
                        <span class="delivery-detail-label">Documento</span>
                        <span class="delivery-detail-value">
                            ${driver.idType} ${driver.idNumber}
                        </span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0;">
                    <span class="delivery-badge ${statusClass}">${statusText}</span>
                    <span style="font-size: 13px; color: var(--text-secondary);">
                        ${driver.completedDeliveries} entregas
                    </span>
                </div>

                ${driver.currentRoute ? `
                    <div style="background: var(--bg-soft); padding: 10px; border-radius: 8px; margin: 10px 0;">
                        <small style="color: var(--text-secondary); display: block;">Ruta actual:</small>
                        <span style="font-weight: 600; color: var(--primary);">${driver.currentRoute}</span>
                    </div>
                ` : ''}

                <div class="delivery-actions">
                    <button class="action-btn action-view" onclick="viewDeliveryDetails(${driver.id})" title="Ver detalles">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                        </svg>
                    </button>
                    <button class="action-btn action-edit" onclick="editDeliveryDriver(${driver.id})" title="Editar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                            <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                        </svg>
                    </button>
                    <button class="action-btn action-delete" onclick="deleteDeliveryDriver(${driver.id})" title="Eliminar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function renderDeliveryTable() {
    const tbody = document.getElementById('deliveryTable');
    if (!tbody) return;

    tbody.innerHTML = deliveryDrivers.map(driver => {
        const statusText = {
            'active': 'Activo',
            'inactive': 'Inactivo',
            'on-route': 'En ruta'
        }[driver.status] || driver.status;

        const statusClass = {
            'active': 'status-delivered',
            'inactive': 'status-cancelled',
            'on-route': 'status-shipped'
        }[driver.status] || 'status-pending';

        return `
            <tr>
                <td style="font-weight: 600; color: var(--primary);">${driver.id}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="delivery-avatar" style="width: 40px; height: 40px; font-size: 16px;">
                            ${driver.avatar ? `<img src="${driver.avatar}" alt="${driver.name}">` : driver.name.split(' ').map(n => n[0]).join('').substring(0,2)}
                        </div>
                    </div>
                </td>
                <td>${driver.name}</td>
                <td>${driver.email}</td>
                <td>${driver.phone}</td>
                <td>${driver.vehicle}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${driver.completedDeliveries}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn action-view" title="Ver detalles" onclick="viewDeliveryDetails(${driver.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                            </svg>
                        </button>
                        <button class="action-btn action-edit" title="Editar" onclick="editDeliveryDriver(${driver.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                                <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                            </svg>
                        </button>
                        <button class="action-btn action-delete" title="Eliminar" onclick="deleteDeliveryDriver(${driver.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function setupDeliveryAvatarUpload() {
    const input = document.getElementById('deliveryAvatar');
    if (!input) return;

    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                selectedDeliveryAvatar = e.target.result;
                document.getElementById('deliveryAvatarPreview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(file);
        } else {
            showToast({ title: "Error", message: "Por favor selecciona una imagen válida", type: "error" });
        }

        input.value = '';
    });
}

function openDeliveryModal(driver = null) {
    const modal = document.getElementById('deliveryModal');
    const modalTitle = document.getElementById('deliveryModalTitle');
    const saveBtn = document.getElementById('deliveryModalSaveBtn');

    if (!modal || !modalTitle || !saveBtn) return;

    selectedDeliveryAvatar = null;

    if (driver) {
        modalTitle.textContent = 'Editar Repartidor';
        saveBtn.textContent = 'Guardar Cambios';

        document.getElementById('deliveryName').value = driver.name || '';
        document.getElementById('deliveryEmail').value = driver.email || '';
        document.getElementById('deliveryPhone').value = driver.phone || '';
        document.getElementById('deliveryIdType').value = driver.idType || 'CC';
        document.getElementById('deliveryIdNumber').value = driver.idNumber || '';
        document.getElementById('deliveryAddress').value = driver.address || '';
        document.getElementById('deliveryVehicle').value = driver.vehicle || '';
        document.getElementById('deliveryLicensePlate').value = driver.licensePlate || '';
        document.getElementById('deliveryLicenseNumber').value = driver.licenseNumber || '';
        document.getElementById('deliveryStatus').value = driver.status || 'active';
        document.getElementById('deliveryEmergencyContact').value = driver.emergencyContact || '';
        document.getElementById('deliveryBirthDate').value = driver.birthDate || '';
        document.getElementById('deliveryBloodType').value = driver.bloodType || '';
        document.getElementById('deliveryNotes').value = driver.notes || '';

        if (driver.avatar) {
            selectedDeliveryAvatar = driver.avatar;
            document.getElementById('deliveryAvatarPreview').innerHTML = `<img src="${driver.avatar}" alt="Preview">`;
        } else {
            document.getElementById('deliveryAvatarPreview').innerHTML = driver.name.split(' ').map(n => n[0]).join('').substring(0, 2);
        }

        editingDeliveryId = driver.id;
    } else {
        modalTitle.textContent = 'Agregar Nuevo Repartidor';
        saveBtn.textContent = 'Agregar Repartidor';
        editingDeliveryId = null;

        document.getElementById('deliveryName').value = '';
        document.getElementById('deliveryEmail').value = '';
        document.getElementById('deliveryPhone').value = '';
        document.getElementById('deliveryIdType').value = 'CC';
        document.getElementById('deliveryIdNumber').value = '';
        document.getElementById('deliveryAddress').value = '';
        document.getElementById('deliveryVehicle').value = '';
        document.getElementById('deliveryLicensePlate').value = '';
        document.getElementById('deliveryLicenseNumber').value = '';
        document.getElementById('deliveryStatus').value = 'active';
        document.getElementById('deliveryEmergencyContact').value = '';
        document.getElementById('deliveryBirthDate').value = '';
        document.getElementById('deliveryBloodType').value = '';
        document.getElementById('deliveryNotes').value = '';

        document.getElementById('deliveryAvatarPreview').innerHTML = '👤';
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDeliveryModal() {
    const modal = document.getElementById('deliveryModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function saveDeliveryDriver() {
    const name = document.getElementById('deliveryName')?.value.trim();
    const email = document.getElementById('deliveryEmail')?.value.trim();
    const phone = document.getElementById('deliveryPhone')?.value.trim();
    const idType = document.getElementById('deliveryIdType')?.value;
    const idNumber = document.getElementById('deliveryIdNumber')?.value.trim();
    const address = document.getElementById('deliveryAddress')?.value.trim();
    const vehicle = document.getElementById('deliveryVehicle')?.value;
    const licensePlate = document.getElementById('deliveryLicensePlate')?.value.trim();
    const licenseNumber = document.getElementById('deliveryLicenseNumber')?.value.trim();
    const status = document.getElementById('deliveryStatus')?.value;
    const emergencyContact = document.getElementById('deliveryEmergencyContact')?.value.trim();
    const birthDate = document.getElementById('deliveryBirthDate')?.value;
    const bloodType = document.getElementById('deliveryBloodType')?.value;
    const notes = document.getElementById('deliveryNotes')?.value.trim();

    if (!name) {
        showToast({ title: "Error", message: "El nombre es obligatorio", type: "error" });
        return;
    }
    if (!email) {
        showToast({ title: "Error", message: "El email es obligatorio", type: "error" });
        return;
    }
    if (!phone) {
        showToast({ title: "Error", message: "El teléfono es obligatorio", type: "error" });
        return;
    }
    if (!idNumber) {
        showToast({ title: "Error", message: "El número de documento es obligatorio", type: "error" });
        return;
    }
    if (!vehicle) {
        showToast({ title: "Error", message: "El tipo de vehículo es obligatorio", type: "error" });
        return;
    }

    if (editingDeliveryId) {
        const index = deliveryDrivers.findIndex(d => d.id === editingDeliveryId);
        if (index !== -1) {
            deliveryDrivers[index] = {
                ...deliveryDrivers[index],
                name,
                email,
                phone,
                idType,
                idNumber,
                address,
                vehicle,
                licensePlate,
                licenseNumber,
                status,
                emergencyContact,
                birthDate,
                bloodType,
                notes,
                avatar: selectedDeliveryAvatar || deliveryDrivers[index].avatar
            };

            localStorage.setItem('angelow_delivery_drivers', JSON.stringify(deliveryDrivers));
            renderDeliveryGrid();
            renderDeliveryTable();
            showToast({ title: "Éxito", message: `Repartidor "${name}" actualizado correctamente`, type: "success" });
        }
    } else {
        const newDriver = {
            id: deliveryDrivers.length > 0 ? Math.max(...deliveryDrivers.map(d => d.id)) + 1 : 1,
            name,
            email,
            phone,
            idType,
            idNumber,
            address,
            vehicle,
            licensePlate,
            licenseNumber,
            status,
            emergencyContact,
            birthDate,
            bloodType,
            notes,
            avatar: selectedDeliveryAvatar,
            completedDeliveries: 0,
            rating: 0,
            hireDate: new Date().toISOString().split('T')[0],
            currentRoute: ""
        };

        deliveryDrivers.push(newDriver);
        localStorage.setItem('angelow_delivery_drivers', JSON.stringify(deliveryDrivers));
        renderDeliveryGrid();
        renderDeliveryTable();
        showToast({ title: "Éxito", message: `Repartidor "${name}" agregado correctamente`, type: "success" });
    }

    closeDeliveryModal();
}

function editDeliveryDriver(id) {
    const driver = deliveryDrivers.find(d => d.id === id);
    if (driver) {
        openDeliveryModal(driver);
    }
}

function deleteDeliveryDriver(id) {
    const driver = deliveryDrivers.find(d => d.id === id);
    if (!driver) return;

    if (confirm(`¿Estás seguro de que deseas eliminar al repartidor "${driver.name}"?`)) {
        deliveryDrivers = deliveryDrivers.filter(d => d.id !== id);
        localStorage.setItem('angelow_delivery_drivers', JSON.stringify(deliveryDrivers));
        renderDeliveryGrid();
        renderDeliveryTable();
        showToast({ title: "Éxito", message: `Repartidor "${driver.name}" eliminado correctamente`, type: "success" });
    }
}

function viewDeliveryDetails(id) {
    const driver = deliveryDrivers.find(d => d.id === id);
    if (!driver) return;

    showToast({
        title: "Detalles del Repartidor",
        message: `${driver.name}\nEmail: ${driver.email}\nTeléfono: ${driver.phone}\nDocumento: ${driver.idType} ${driver.idNumber}\nVehículo: ${driver.vehicle}\nEstado: ${driver.status}\nEntregas: ${driver.completedDeliveries}`,
        type: "info",
        duration: 8000
    });
}

// ======================== SECCIÓN CARRITO/FAVORITOS ========================
function addToCart(pid) {
    event.stopPropagation();
    let p = products.find(p => p.id === pid);

    if (p.stock <= 0) {
        showToast({ title: "Sin stock", message: "Producto no disponible", type: "error" });
        return;
    }

    let size = selectedSizes[pid];
    if (!size) {
        showToast({ title: "Selecciona talla", message: "Elige una talla", type: "warning" });
        return;
    }

    let cartId = `${pid}-${size}`;
    let existing = cart.find(i => i.cartId === cartId);

    if (existing && existing.quantity + 1 > p.stock) {
        showToast({ title: "Stock insuficiente", message: "No hay suficientes unidades", type: "warning" });
        return;
    }

    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ ...p, selectedSize: size, cartId, quantity: 1 });
    }

    p.stock -= 1;
    localStorage.setItem("angelow_cart", JSON.stringify(cart));
    renderProducts();
    showToast({ title: "¡Agregado!", message: `${p.name} (Talla ${size})`, type: "success" });
}

function toggleFavorite(id) {
    let p = products.find(p => p.id === id);

    if (favorites.includes(id)) {
        favorites = favorites.filter(x => x !== id);
        showToast({ title: "Eliminado", message: `${p.name} eliminado de favoritos`, type: "info" });
    } else {
        favorites.push(id);
        showToast({ title: "¡Añadido!", message: `${p.name} agregado a favoritos`, type: "success" });
    }

    localStorage.setItem("angelow_favorites", JSON.stringify(favorites));
    renderProducts();
}

// ======================== GRÁFICOS ========================
function initCharts() {
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Ventas 2025',
                data: [400, 520, 480, 580, 520, 600, 700, 650, 720, 680, 750, 800],
                borderColor: '#5E9DE6',
                backgroundColor: 'rgba(94, 157, 230, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { callback: value => '$' + value }
                },
                x: { grid: { color: 'rgba(0,0,0,0.05)' } }
            }
        }
    });

    const productsCtx = document.getElementById('productsChart').getContext('2d');
    const gradient = productsCtx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, '#60A5FA');
    gradient.addColorStop(1, '#3B82F6');

    productsChart = new Chart(productsCtx, {
        type: 'bar',
        data: {
            labels: ['Conjunto Deportivo', 'Body Negro', 'Set Falda', 'Jogger Niño', 'Conjunto Infantil'],
            datasets: [{
                label: 'Unidades Vendidas',
                data: [45, 32, 28, 22, 18],
                backgroundColor: gradient,
                borderColor: '#2563EB',
                borderWidth: 1,
                borderRadius: 10,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                hoverBackgroundColor: '#3B82F6',
                hoverBorderColor: '#1E3A8A',
                hoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1F2937',
                    titleColor: '#F9FAFB',
                    bodyColor: '#D1D5DB',
                    borderColor: '#60A5FA',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: (context) => `Vendidos: ${context.raw} unidades`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: { stepSize: 10, color: '#4B5563', font: { size: 12 } },
                    title: { display: true, text: 'Unidades vendidas', color: '#6B7280', font: { size: 12, weight: '500' } }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '500' },
                        maxRotation: 0,
                        minRotation: 0
                    }
                }
            },
            layout: {
                padding: { top: 20, bottom: 20, left: 10, right: 10 }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            },
            elements: {
                bar: {
                    borderRadius: 10,
                    borderWidth: 1
                }
            }
        }
    });

    const trafficCtx = document.getElementById('trafficChart').getContext('2d');
    trafficChart = new Chart(trafficCtx, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Visitantes',
                data: [320, 380, 350, 420, 410, 280, 310],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { color: 'rgba(0,0,0,0.05)' } }
            }
        }
    });

    const conversionCtx = document.getElementById('conversionChart').getContext('2d');
    conversionChart = new Chart(conversionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completadas', 'Pendientes', 'Canceladas'],
            datasets: [{
                data: [65, 20, 15],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    document.getElementById('salesYear').addEventListener('change', function() {
        const year = this.value;
        let data;

        if (year === '2024') {
            data = [350, 420, 380, 450, 500, 480, 550, 600, 580, 620, 650, 700];
        } else if (year === '2023') {
            data = [300, 350, 320, 380, 400, 420, 480, 520, 500, 550, 580, 620];
        } else {
            data = [400, 520, 480, 580, 520, 600, 700, 650, 720, 680, 750, 800];
        }

        salesChart.data.datasets[0].data = data;
        salesChart.update();
    });
}

// ======================== NAVEGACIÓN ========================
function initNavigation() {
    const menuLinks = document.querySelectorAll('.admin-menu a');
    const sections = document.querySelectorAll('.admin-section');

    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            menuLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.style.display = 'none');

            this.classList.add('active');

            const sectionId = this.getAttribute('data-section');
            const section = document.getElementById(`${sectionId}-section`);
            if (section) {
                section.style.display = 'block';

                if (sectionId === 'analytics') {
                    setTimeout(() => {
                        if (trafficChart) trafficChart.resize();
                        if (conversionChart) conversionChart.resize();
                    }, 100);
                }

                if (sectionId === 'orders' && map) {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                }

                if (sectionId === 'delivery') {
                    renderDeliveryGrid();
                    renderDeliveryTable();
                }

                if (sectionId === 'categories') {
                    renderMainCategories();
                    renderSubCategories();
                }
            }
        });
    });
}

// ======================== FILTROS ========================
function setupFilters() {
    const filterOptions = document.querySelectorAll('.filter-option');
    filterOptions.forEach(option => {
        option.addEventListener('click', function() {
            const parent = this.parentElement;
            const options = parent.querySelectorAll('.filter-option');

            options.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');

            if (this.dataset.category !== undefined) {
                activeCategory = this.dataset.category;
            }
            if (this.dataset.stock !== undefined) {
                activeStock = this.dataset.stock;
            }
            if (this.dataset.sort !== undefined) {
                activeSort = this.dataset.sort;
            }

            renderProducts();
        });
    });
}

// ======================== BOTONES ========================
function setupButtons() {
    document.getElementById('addProductBtn')?.addEventListener('click', function() {
        openProductModal();
    });

    document.getElementById('exportOrdersBtn')?.addEventListener('click', exportOrdersToPDF);

    document.getElementById('exportCustomersBtn')?.addEventListener('click', exportarClientesPDF);

    document.getElementById('addCustomerBtn')?.addEventListener('click', function() {
        showToast({ title: "Información", message: "Funcionalidad para agregar cliente próximamente", type: "info" });
    });

    document.getElementById('adminUserBtn')?.addEventListener('click', function() {
        showToast({ title: "Perfil", message: "Administrador Principal", type: "info" });
    });
}

// ======================== MODALES ========================
function setupModal() {
    const modal = document.getElementById('productModal');
    const closeBtn = document.querySelector('.product-modal-close');
    const cancelBtn = document.querySelector('#productModal .btn-secondary');
    const saveBtn = document.getElementById('modalSaveBtn');

    if (!modal || !closeBtn || !cancelBtn || !saveBtn) return;

    closeBtn.addEventListener('click', closeProductModal);
    cancelBtn.addEventListener('click', closeProductModal);

    saveBtn.removeEventListener('click', saveProduct);
    saveBtn.addEventListener('click', saveProduct);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeProductModal();
        }
    });

    const sizesContainer = document.getElementById('sizesContainer');
    if (sizesContainer) {
        const sizes = ['2', '4', '6', '8', '10', '12'];
        sizesContainer.innerHTML = sizes.map(size => `
            <div class="size-box" data-size="${size}" onclick="toggleSize(this, '${size}')">
                ${size}
            </div>
        `).join('');
    }
}

function setupDeliveryModal() {
    const modal = document.getElementById('deliveryModal');
    const closeBtn = document.querySelector('.delivery-modal-close');
    const cancelBtn = document.querySelector('#deliveryModal .btn-secondary');
    const saveBtn = document.getElementById('deliveryModalSaveBtn');

    if (!modal || !closeBtn || !cancelBtn || !saveBtn) return;

    closeBtn.addEventListener('click', closeDeliveryModal);
    cancelBtn.addEventListener('click', closeDeliveryModal);
    saveBtn.addEventListener('click', saveDeliveryDriver);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeDeliveryModal();
        }
    });
}

function setupCategoryModal() {
    const modal = document.getElementById('categoryModal');
    const closeBtn = document.querySelector('.category-modal-close');
    const cancelBtn = document.querySelector('#categoryModal .btn-outline');
    const saveBtn = document.getElementById('categoryModalSaveBtn');

    if (!modal || !closeBtn || !cancelBtn || !saveBtn) return;

    closeBtn.addEventListener('click', closeCategoryModal);
    cancelBtn.addEventListener('click', closeCategoryModal);
    saveBtn.addEventListener('click', saveCategory);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeCategoryModal();
        }
    });
}

// ======================== EXPORTAR A PDF ========================
function exportOrdersToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFont("helvetica", "bold");
    doc.setFontSize(18);
    doc.text("ANGELOW - Lista de Pedidos", 14, 20);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.text(`Fecha de generación: ${new Date().toLocaleDateString('es-CO')}`, 14, 28);
    doc.text("Administración ANGELOW", 14, 34);

    doc.setLineWidth(0.5);
    doc.line(14, 38, 196, 38);

    const tableColumn = ["N° Pedido", "Cliente", "Fecha", "Total", "Estado", "Dirección"];
    const tableRows = [];

    orders.forEach(order => {
        const row = [
            order.orderNumber || order.numero_pedido || String(order.id),
            order.customer || order.cliente?.nombre || 'Cliente',
            order.date ? new Date(order.date).toLocaleDateString('es-CO') : '-',
            "COP $" + (order.total || 0).toLocaleString('es-CO'),
            getStatusText(order.status || order.estado || 'pendiente'),
            order.address || order.envio?.direccion || 'Sin dirección'
        ];
        tableRows.push(row);
    });

    doc.autoTable({
        head: [tableColumn],
        body: tableRows,
        startY: 42,
        styles: {
            fontSize: 8,
            cellPadding: 3,
            textColor: [40, 40, 40],
            lineColor: [200, 200, 200],
            lineWidth: 0.1,
        },
        headStyles: {
            fillColor: [94, 157, 230],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            halign: 'center'
        },
        alternateRowStyles: {
            fillColor: [245, 247, 255]
        },
        margin: { top: 42, left: 14, right: 14 },
        columnStyles: {
            5: { cellWidth: 50 }
        }
    });

    const finalY = doc.lastAutoTable.finalY + 15;
    doc.setFontSize(9);
    doc.setTextColor(100);
    doc.text("Gracias por usar el sistema de administración ANGELOW", 14, finalY);
    doc.text("Sistema confidencial - Uso exclusivo de la tienda", 14, finalY + 6);

    const fileName = `Pedidos_ANGELOW_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);

    showToast({ title: "Éxito", message: "Pedidos exportados a PDF correctamente", type: "success" });
}

function exportarClientesPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFont("helvetica", "bold");
    doc.setFontSize(18);
    doc.text("ANGELOW - Lista de Clientes", 14, 20);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.text(`Fecha de generación: ${new Date().toLocaleDateString('es-CO')}`, 14, 28);
    doc.text("Administración ANGELOW", 14, 34);
    doc.text("Total de clientes: " + (usuarios ? usuarios.length : 0), 14, 40);

    doc.setLineWidth(0.5);
    doc.line(14, 45, 196, 45);

    const tableColumn = ["Nombre", "Email", "Teléfono", "Fecha Registro", "Rol"];
    const tableRows = [];

    if (usuarios && usuarios.length > 0) {
        usuarios.forEach(usuario => {
            const row = [
                usuario.nombre || '-',
                usuario.email || '-',
                usuario.telefono || '-',
                usuario.fecha_registro ? new Date(usuario.fecha_registro).toLocaleDateString('es-CO') : '-',
                usuario.rol || 'cliente'
            ];
            tableRows.push(row);
        });
    }

    doc.autoTable({
        head: [tableColumn],
        body: tableRows,
        startY: 52,
        styles: {
            fontSize: 8,
            cellPadding: 3,
            textColor: [40, 40, 40],
            lineColor: [200, 200, 200],
            lineWidth: 0.1,
        },
        headStyles: {
            fillColor: [94, 157, 230],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            halign: 'center'
        },
        alternateRowStyles: {
            fillColor: [245, 247, 255]
        },
        margin: { top: 52, left: 14, right: 14 }
    });

    const finalY = doc.lastAutoTable.finalY + 15;
    doc.setFontSize(9);
    doc.setTextColor(100);
    doc.text("Gracias por usar el sistema de administración ANGELOW", 14, finalY);
    doc.text("Sistema confidencial - Uso exclusivo de la tienda", 14, finalY + 6);

    const fileName = `Clientes_ANGELOW_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);

    showToast({ title: "Éxito", message: "Clientes exportados a PDF correctamente", type: "success" });
}

// ======================== MÉTRICAS ========================
function updateMetrics() {
    const totalOrders = orders.length;
    const pendingOrders = orders.filter(o => o.status === 'pending' || o.status === 'processing').length;
    const totalRevenue = orders
        .filter(o => o.status === 'delivered')
        .reduce((sum, o) => sum + o.total, 0);
    const totalFavorites = favorites.length;

    document.getElementById('totalOrders').textContent = totalOrders;
    document.getElementById('pendingOrders').textContent = pendingOrders;
    document.getElementById('totalFavorites').textContent = totalFavorites;
    document.getElementById('totalRevenue').textContent = `$${(totalRevenue / 1000000).toFixed(1)}M`;

    document.getElementById('totalOrdersChange').textContent = `+${totalOrders} este mes`;
    document.getElementById('pendingOrdersChange').textContent = `-${pendingOrders} esta semana`;
    document.getElementById('favoritesChange').textContent = `+${totalFavorites} este mes`;
    document.getElementById('revenueChange').textContent = `+${((totalRevenue / 1000000)).toFixed(1)}% este mes`;

    const totalUsersEl = document.getElementById('totalUsers');
    const usersChangeEl = document.getElementById('usersChange');
    if (totalUsersEl && usuarios.length > 0) {
        totalUsersEl.textContent = usuarios.length;
        if (usersChangeEl) {
            usersChangeEl.textContent = `+${usuarios.length} registrados`;
        }
    }

    const progress = totalRevenue > 0 ? Math.min((totalRevenue / 35000000) * 100, 100) : 0;
    document.getElementById('progressBar').style.width = `${progress}%`;
    document.getElementById('progressPercent').textContent = `${progress.toFixed(0)}%`;
    document.getElementById('progressText').textContent = `$${(totalRevenue / 1000000).toFixed(1)}M / $35M`;

    document.getElementById('visitorsCount').textContent = '1,254';
    document.getElementById('conversionRate').textContent = '3.2%';
    document.getElementById('avgOrderValue').textContent = '$124';
    document.getElementById('bounceRate').textContent = '42%';
}

// ======================== ESCUCHAR CAMBIOS EN LOCALSTORAGE ========================
window.addEventListener('storage', function(e) {
    if (e.key === 'angelow_orders') {
        orders = JSON.parse(e.newValue) || [];
        renderOrdersList();
        renderOrdersTable();
        updateMapMarkers();
        updateMetrics();
        showToast({ title: "Actualización", message: "Nuevos pedidos recibidos", type: "success" });
    }

    if (e.key === 'angelow_products') {
        products = JSON.parse(e.newValue) || [];
        renderProducts();
    }

    if (e.key === 'angelow_cart') {
        cart = JSON.parse(e.newValue) || [];
    }

    if (e.key === 'angelow_favorites') {
        favorites = JSON.parse(e.newValue) || [];
        renderProducts();
        updateMetrics();
    }
});

// ======================== INICIALIZACIÓN ========================
document.addEventListener('DOMContentLoaded', function() {
    mainCategories = JSON.parse(localStorage.getItem('angelow_main_categories')) || mainCategories;
    subCategories = JSON.parse(localStorage.getItem('angelow_sub_categories')) || subCategories;
    products = JSON.parse(localStorage.getItem('angelow_products')) || products;
    deliveryDrivers = JSON.parse(localStorage.getItem('angelow_delivery_drivers')) || deliveryDrivers;
    cart = JSON.parse(localStorage.getItem("angelow_cart")) || [];
    favorites = JSON.parse(localStorage.getItem("angelow_favorites")) || [];

    renderProducts();
    renderMainCategories();
    renderSubCategories();
    updateCategorySelects();
    cargarPedidos();
    cargarUsuarios();
    renderDeliveryGrid();
    renderDeliveryTable();

    initCharts();
    initNavigation();
    setupFilters();
    setupOrderFilters();
    setupButtons();
    setupModal();
    setupDeliveryModal();
    setupCategoryModal();
    setupDeliveryAvatarUpload();
    setupImageUpload();
    setupCustomerSearch();

    updateMetrics();
    updateClientCategories();

    setTimeout(() => {
        initMap();
    }, 500);

    setInterval(async function() {
        await cargarPedidos();
        updateMetrics();
    }, 30000);
});

// ======================== EXPONER FUNCIONES GLOBALES ========================
window.selectSize = selectSize;
window.addToCart = addToCart;
window.toggleFavorite = toggleFavorite;
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.editOrder = editOrder;
window.selectOrder = selectOrder;
window.cambiarRol = window.cambiarRol;
window.closeProductModal = closeProductModal;
window.saveProduct = saveProduct;
window.removeImage = removeImage;
window.toggleSize = toggleSize;
window.openCategoryModal = openCategoryModal;
window.closeCategoryModal = closeCategoryModal;
window.saveCategory = saveCategory;
window.editMainCategory = editMainCategory;
window.editSubCategory = editSubCategory;
window.deleteMainCategory = deleteMainCategory;
window.deleteSubCategory = deleteSubCategory;
window.openDeliveryModal = openDeliveryModal;
window.closeDeliveryModal = closeDeliveryModal;
window.saveDeliveryDriver = saveDeliveryDriver;
window.editDeliveryDriver = editDeliveryDriver;
window.deleteDeliveryDriver = deleteDeliveryDriver;
window.viewDeliveryDetails = viewDeliveryDetails;
window.exportOrdersToPDF = exportOrdersToPDF;
window.exportarClientesPDF = exportarClientesPDF;
window.refreshOrdersRealTime = refreshOrdersRealTime;
window.cambiarEstadoPedido = cambiarEstadoPedido;
window.verDetallesPedido = verDetallesPedido;