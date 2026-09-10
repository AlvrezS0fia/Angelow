/**
 * ============================================================
 * ARCHIVO: perfil.js
 * QUÉ HACE: Página de perfil del cliente: datos personales,
 *            direcciones, pedidos/facturas e historial de compras.
 * TIPO: HÍBRIDO (pedidos y facturas desde API; el resto local)
 * ENDPOINTS QUE CONSUME: GET {APP_URL}/api/mis-pedidos,
 *            GET {APP_URL}/api/mis-pedidos/{id}
 * CLÁVES localStorage QUE USA: angelow_cart, angelow_addresses,
 *            angelow_orders, angelow_cards, angelow_profile,
 *            angelow_favorites, angelow_user, angelow_pedidos,
 *            angelow_pagos
 * LIBRERÍAS EXTERNAS: jsPDF + autoTable, html2canvas
 * ============================================================
 */

// ======================== VARIABLES GLOBALES ========================
let isEditing = false;
let cart = JSON.parse(localStorage.getItem("angelow_cart")) || [];
let addresses = JSON.parse(localStorage.getItem("angelow_addresses")) || [];
let orders = JSON.parse(localStorage.getItem("angelow_orders")) || [];
let cards = JSON.parse(localStorage.getItem("angelow_cards")) || [];
let favorites = [];
let profileData = JSON.parse(localStorage.getItem("angelow_profile")) || {};
const currentUser = window.CURRENT_USER || JSON.parse(localStorage.getItem("angelow_user")) || null;
let pendingDeleteId = null;
let pendingDeleteType = null;
let editingAddressId = null;

// STOCK POR PRODUCTO (máximo permitido)
const STOCK_LIMITS = {
    1: 10,  // Conjunto Deportivo
    2: 8,   // Conjunto Size
    3: 15,  // Body Niño
    4: 12,  // Jogger Niño
    5: 6,   // Set Bebé
    6: 10,  // Conjunto Infantil
    7: 20,  // Body Negro
    8: 8    // Set Falda
};

// Devuelve la clave de localStorage para favoritos según el usuario
// actual (usa el email o el id; si no hay sesión usa una clave genérica)
function getFavoritesStorageKey() {
    if (currentUser?.email) return `angelow_favorites_${currentUser.email}`;
    if (currentUser?.id) return `angelow_favorites_${currentUser.id}`;
    return 'angelow_favorites';
}

const products = [
    { id: 1, name: "Conjunto Deportivo", category: "Niños", subcategory: "Edición Especial", price: 89990, imgs: [APP_URL + "/assets/imagenes/ninos/Frente Conjunto Deportivo.png"] },
    { id: 2, name: "Conjunto Size", category: "Niños", subcategory: "Popular", price: 79990, imgs: [APP_URL + "/assets/imagenes/ninos/Frente Conjunto Size.png"] },
    { id: 3, name: "Body Niño", category: "Niños", subcategory: "Niño", price: 34990, imgs: [APP_URL + "/assets/imagenes/ninos/Frente Body Niño.png"] },
    { id: 4, name: "Jogger Niño", category: "Niños", subcategory: "Niño", price: 49990, imgs: [APP_URL + "/assets/imagenes/ninos/Frente Jogger.png"] },
    { id: 5, name: "Set Bebé", category: "Bebés", subcategory: "Edición especial", price: 99990, imgs: [APP_URL + "/assets/imagenes/bebe/Frente Set Bebe.png"] },
    { id: 6, name: "Conjunto Infantil", category: "Niñas", subcategory: "Niña", price: 85990, imgs: [APP_URL + "/assets/imagenes/ninas/Frente Conjunto Infantil.png"] },
    { id: 7, name: "Body Negro", category: "Niños", subcategory: "Popular", price: 32990, imgs: [APP_URL + "/assets/imagenes/ninas/Frente Body Negro.png"] },
    { id: 8, name: "Set Falda", category: "Niñas", subcategory: "Popular", price: 89990, imgs: [APP_URL + "/assets/imagenes/ninas/Frente Set Falda.png"] }
];

// ======================== TOAST ========================
// Muestra una notificación tipo toast con icono según el tipo y cierre automático
function showToast({title, message, type = "info", duration = 4000}) {
    let container = document.getElementById("toastContainer");
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    const icons = { 
        success: '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>', 
        warning: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', 
        error: '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>', 
        info: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' 
    };
    toast.innerHTML = `<div class="toast-icon">${icons[type]}</div><div class="toast-content">${title ? `<div class="toast-title">${title}</div>` : ''}<div class="toast-message">${message}</div></div><button class="toast-close">×</button>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 100);
    toast.querySelector(".toast-close").onclick = () => toast.remove();
    setTimeout(() => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); }, duration);
}

// ================== VALIDACIONES DEL FORMULARIO DE PERFIL ==================
// Los inputs del formulario "Datos personales" (#datosPersonales en
// app/Views/paginas/perfil.php) se validan en este bloque antes de guardar.
// TODAS las funciones devuelven un objeto con el MISMO contrato:
//   { valido: boolean, mensaje: 'texto de error', valor: 'valor normalizado' }
// Si valido=false, saveProfile() pinta el campo en rojo (mostrarErrorEnCampo).

/**
 * Valida la cédula del cliente.
 * Reglas: obligatoria → solo números → entre 6 y 15 dígitos.
 * @param {string} cedula Valor crudo del input #cedula.
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarCedula(cedula) {
    if (!cedula || cedula.trim() === '') return { valido: false, mensaje: "La cédula es requerida" };
    const cedulaLimpia = cedula.trim().replace(/\s/g, '');
    if (!/^\d+$/.test(cedulaLimpia)) return { valido: false, mensaje: "La cédula solo debe contener números" };
    if (cedulaLimpia.length < 6) return { valido: false, mensaje: "La cédula debe tener al menos 6 dígitos" };
    if (cedulaLimpia.length > 15) return { valido: false, mensaje: "La cédula no debe tener más de 15 dígitos" };
    return { valido: true, mensaje: "Cédula válida", valor: cedulaLimpia };
}

/**
 * Valida el teléfono del cliente.
 * Reglas: obligatorio → solo números → entre 7 y 15 dígitos.
 * Se eliminan espacios, guiones y paréntesis antes de contar los dígitos.
 * @param {string} telefono Valor crudo del input #telefono.
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarTelefono(telefono) {
    if (!telefono || telefono.trim() === '') return { valido: false, mensaje: "El teléfono es requerido" };
    const telefonoLimpio = telefono.trim().replace(/[\s\-\(\)]/g, '');
    if (!/^\d+$/.test(telefonoLimpio)) return { valido: false, mensaje: "El teléfono solo debe contener números" };
    if (telefonoLimpio.length < 7) return { valido: false, mensaje: "El teléfono debe tener al menos 7 dígitos" };
    if (telefonoLimpio.length > 15) return { valido: false, mensaje: "El teléfono no debe tener más de 15 dígitos" };
    return { valido: true, mensaje: "Teléfono válido", valor: telefonoLimpio };
}

/**
 * Valida un nombre (compartida: nombre, apellido, destinatario, titular).
 * Reglas: obligatorio → entre 2 y 50 caracteres → solo letras (incluye
 * acentos y ñ) y espacios.
 * @param {string} nombre Valor a validar.
 * @param {string} [campo] Etiqueta que aparece en el mensaje (p. ej. "Nombre").
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarNombre(nombre, campo = "Nombre") {
    if (!nombre || nombre.trim() === '') return { valido: false, mensaje: `${campo} es requerido` };
    const nombreLimpio = nombre.trim();
    if (nombreLimpio.length < 2) return { valido: false, mensaje: `${campo} debe tener al menos 2 caracteres` };
    if (nombreLimpio.length > 50) return { valido: false, mensaje: `${campo} no debe tener más de 50 caracteres` };
    if (!/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/.test(nombreLimpio)) return { valido: false, mensaje: `${campo} solo debe contener letras y espacios` };
    return { valido: true, mensaje: `${campo} válido`, valor: nombreLimpio };
}

/**
 * Valida la fecha de nacimiento del cliente.
 * Reglas: obligatoria → fecha real → no futura → edad entre 1 y 120 años.
 * El cálculo de edad ajusta por mes/día (aún no cumplió años este año).
 * @param {string} fecha Valor crudo del input type="date" #fechaNacimiento.
 * @returns {{valido: boolean, mensaje: string, valor?: string, edad?: number}} Resultado.
 */
function validarFechaNacimiento(fecha) {
    if (!fecha) return { valido: false, mensaje: "La fecha de nacimiento es requerida" };
    const fechaNac = new Date(fecha);
    if (isNaN(fechaNac.getTime())) return { valido: false, mensaje: "Fecha inválida" };
    const hoy = new Date();
    const edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mes = hoy.getMonth() - fechaNac.getMonth();
    const dia = hoy.getDate() - fechaNac.getDate();
    const edadReal = mes < 0 || (mes === 0 && dia < 0) ? edad - 1 : edad;
    if (edadReal < 0) return { valido: false, mensaje: "La fecha no puede ser futura" };
    if (edadReal < 1) return { valido: false, mensaje: "Debes tener al menos 1 año" };
    if (edadReal > 120) return { valido: false, mensaje: "Edad máxima permitida es 120 años" };
    return { valido: true, mensaje: "Fecha válida", valor: fecha, edad: edadReal };
}

/**
 * Valida que un campo no esté vacío y devuelve el valor recortado.
 * Se usa sobre todo para el select #genero (debe elegir una opción).
 * @param {string} valor Valor del campo.
 * @param {string} [nombre] Etiqueta para el mensaje (p. ej. "Género").
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarCampoRequerido(valor, nombre = "Campo") {
    if (!valor || valor.trim() === '') return { valido: false, mensaje: `${nombre} es requerido` };
    return { valido: true, mensaje: `${nombre} válido`, valor: valor.trim() };
}

/**
 * Valida el número de tarjeta (formulario de métodos de pago).
 * Reglas: obligatorio → solo números → entre 13 y 19 dígitos.
 * @param {string} numero Valor con espacios/guiones del input de tarjeta.
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarNumeroTarjeta(numero) {
    if (!numero || numero.trim() === '') return { valido: false, mensaje: "El número de tarjeta es requerido" };
    const numeroLimpio = numero.replace(/\s/g, '');
    if (!/^\d+$/.test(numeroLimpio)) return { valido: false, mensaje: "El número de tarjeta solo debe contener números" };
    if (numeroLimpio.length < 13 || numeroLimpio.length > 19) return { valido: false, mensaje: "El número de tarjeta debe tener entre 13 y 19 dígitos" };
    return { valido: true, mensaje: "Número de tarjeta válido", valor: numeroLimpio };
}

/**
 * Valida el CVV/CVC de la tarjeta.
 * Reglas: obligatorio → solo números → 3 o 4 dígitos.
 * @param {string} cvv Valor crudo del input del CVV.
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarCVV(cvv) {
    if (!cvv || cvv.trim() === '') return { valido: false, mensaje: "El CVV es requerido" };
    const cvvLimpio = cvv.trim();
    if (!/^\d+$/.test(cvvLimpio)) return { valido: false, mensaje: "El CVV solo debe contener números" };
    if (cvvLimpio.length < 3 || cvvLimpio.length > 4) return { valido: false, mensaje: "El CVV debe tener 3 o 4 dígitos" };
    return { valido: true, mensaje: "CVV válido", valor: cvvLimpio };
}

/**
 * Valida la fecha de expiración de la tarjeta en formato MM/AA.
 * Reglas: obligatoria → 4 dígitos → mes 1-12 → no debe estar vencida
 * (compara mes/año contra la fecha actual).
 * @param {string} fecha Valor crudo (p. ej. "09/28").
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarFechaExpiracion(fecha) {
    if (!fecha || fecha.trim() === '') return { valido: false, mensaje: "La fecha de expiración es requerida" };
    const fechaLimpia = fecha.replace(/\//g, '');
    if (!/^\d{4}$/.test(fechaLimpia)) return { valido: false, mensaje: "Formato inválido. Use MM/AA" };
    const mes = parseInt(fechaLimpia.substring(0, 2));
    const año = parseInt(fechaLimpia.substring(2, 4));
    if (mes < 1 || mes > 12) return { valido: false, mensaje: "Mes inválido (1-12)" };
    const hoy = new Date();
    const añoActual = hoy.getFullYear() % 100;
    const mesActual = hoy.getMonth() + 1;
    if (año < añoActual || (año === añoActual && mes < mesActual)) {
        return { valido: false, mensaje: "La tarjeta está vencida" };
    }
    return { valido: true, mensaje: "Fecha válida", valor: fecha };
}

/**
 * Valida el código postal de facturación de la tarjeta.
 * Reglas: obligatorio → solo números → exactamente 5 dígitos.
 * @param {string} codigo Valor crudo del input de código postal.
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarCodigoPostal(codigo) {
    if (!codigo || codigo.trim() === '') return { valido: false, mensaje: "El código postal es requerido" };
    const codigoLimpio = codigo.trim();
    if (!/^\d+$/.test(codigoLimpio)) return { valido: false, mensaje: "El código postal solo debe contener números" };
    if (codigoLimpio.length !== 5) return { valido: false, mensaje: "El código postal debe tener exactamente 5 dígitos" };
    return { valido: true, mensaje: "Código postal válido", valor: codigoLimpio };
}

/**
 * Valida el nombre del titular de la tarjeta.
 * Reglas: obligatorio → entre 3 y 50 caracteres → solo letras (con acentos
 * y ñ) y espacios.
 * @param {string} nombre Valor del input del titular.
 * @returns {{valido: boolean, mensaje: string, valor?: string}} Resultado.
 */
function validarNombreTitular(nombre) {
    if (!nombre || nombre.trim() === '') return { valido: false, mensaje: "El nombre del titular es requerido" };
    const nombreLimpio = nombre.trim();
    if (nombreLimpio.length < 3) return { valido: false, mensaje: "El nombre del titular debe tener al menos 3 caracteres" };
    if (nombreLimpio.length > 50) return { valido: false, mensaje: "El nombre del titular no debe tener más de 50 caracteres" };
    if (!/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/.test(nombreLimpio)) return { valido: false, mensaje: "El nombre del titular solo debe contener letras y espacios" };
    return { valido: true, mensaje: "Nombre válido", valor: nombreLimpio };
}

// ======================== FUNCIÓN DE STOCK ========================
// Devuelve el stock máximo permitido para un producto
function getStockLimit(productId) {
    return STOCK_LIMITS[productId] || 99;
}

// Devuelve la cantidad actual de un producto en el carrito (0 si no está)
function getCurrentQuantityInCart(productId) {
    const item = cart.find(item => item.id === productId);
    return item ? (item.quantity || 1) : 0;
}

// Indica si aún se puede añadir más cantidad de un producto al carrito
function canAddToCart(productId) {
    const stockLimit = getStockLimit(productId);
    const currentQty = getCurrentQuantityInCart(productId);
    return currentQty < stockLimit;
}

// Devuelve las unidades que quedan disponibles de un producto en el carrito
function getRemainingStock(productId) {
    const stockLimit = getStockLimit(productId);
    const currentQty = getCurrentQuantityInCart(productId);
    return Math.max(0, stockLimit - currentQty);
}

// ======================== ERRORES ========================
// Marca un campo con estilo de error y le asocia un mensaje visible
function marcarCampoError(elemento, mensaje) {
    if (!elemento) return;
    elemento.classList.add('input-error');
    elemento.style.borderColor = '#ef4444';
    elemento.style.borderWidth = '2px';
    elemento.style.borderStyle = 'solid';
    elemento.style.backgroundColor = '#fef2f2';
    
    let errorSpan = elemento.parentElement.querySelector('.error-message');
    if (!errorSpan) {
        errorSpan = document.createElement('span');
        errorSpan.className = 'error-message';
        elemento.parentElement.appendChild(errorSpan);
    }
    errorSpan.textContent = mensaje;
    errorSpan.style.display = 'block';
}

// Limpia los estilos de error y los mensajes de todos los campos
function limpiarErroresCampos() {
    document.querySelectorAll('.input-error').forEach(el => {
        el.classList.remove('input-error');
        el.style.borderColor = '';
        el.style.borderWidth = '';
        el.style.borderStyle = '';
        el.style.backgroundColor = '';
    });
    document.querySelectorAll('.error-message').forEach(el => {
        el.style.display = 'none';
    });
}

// Marca un campo con error por su id, le pone el foco y lo centra en pantalla
function mostrarErrorEnCampo(id, mensaje) {
    const campo = document.getElementById(id);
    if (campo) {
        marcarCampoError(campo, mensaje);
        campo.focus();
        campo.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ======================== CERRAR SESIÓN ========================
// Muestra la alerta de confirmación para cerrar sesión
function showLogoutConfirm() {
    const alertOverlay = document.getElementById('alertOverlay');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    
    alertTitle.textContent = 'Cerrar sesión';
    alertMessage.textContent = '¿Estás seguro de que deseas cerrar sesión?';
    alertOverlay.classList.add('active');
    // Guardar referencia para saber que es logout
    pendingDeleteType = 'logout';
}

// Confirma el cierre de sesión: guarda favoritos y redirige al logout
function confirmLogout() {
    showToast({ title: "Cerrando sesión", message: "Por favor espera...", type: "info" });
    closeAlert();
    // Guardar favoritos antes de salir
    localStorage.setItem(getFavoritesStorageKey(), JSON.stringify(favorites));
    setTimeout(function() {
        window.location.href = APP_URL + '/auth/logout';
    }, 500);
}

// ======================== CARRITO EN PERFIL ========================
// Renderiza el carrito del usuario dentro de la sección del perfil,
// con controles de cantidad y stock disponible por producto
function renderCartProfile() {
    const container = document.getElementById('cartItemsProfile');
    if (!container) return;
    
    const total = cart.reduce((sum, i) => sum + (i.price || 0) * (i.quantity || 1), 0);
    const totalItems = cart.reduce((sum, i) => sum + (i.quantity || 1), 0);
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart-state">
                <div class="empty-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <p class="empty-text">¡TU CARRITO ESTÁ VACÍO!</p>
                <p class="empty-subtext">Agrega productos para comenzar tu compra</p>
                <button class="primary-btn" onclick="window.location.href='${APP_URL}/'">EXPLORAR PRODUCTOS</button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="cart-grid">
            ${cart.map((item, index) => {
                const mainImg = item.imgs && item.imgs[0] ? item.imgs[0] : APP_URL + "/assets/imagenes/general/logos.png";
                const stockLimit = getStockLimit(item.id);
                const currentQty = item.quantity || 1;
                const canIncrement = currentQty < stockLimit;
                const remaining = stockLimit - currentQty;
                
                return `
                    <div class="cart-item-card">
                        <div class="cart-item-badge">EN CARRITO</div>
                        <img src="${mainImg}" alt="${item.name}" class="cart-item-image" onerror="this.src='${APP_URL}/assets/imagenes/general/logos.png'">
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-category">${item.category || item.subcategory || 'Producto'}</div>
                            <div class="cart-item-price">COP $${(item.price || 0).toLocaleString()}</div>
                            <div class="cart-item-size">Talla: ${item.selectedSize || 'N/A'}</div>
                            <div class="cart-item-stock ${remaining <= 2 ? 'low-stock' : ''}">
                                Stock disponible: ${remaining} unidad${remaining !== 1 ? 'es' : ''}
                            </div>
                            <div class="cart-item-actions">
                                <div class="qty">
                                    <button onclick="updateQtyProfile(${index}, -1)" ${currentQty <= 1 ? 'disabled' : ''}>−</button>
                                    <span>${currentQty}</span>
                                    <button onclick="updateQtyProfile(${index}, 1)" ${!canIncrement ? 'disabled' : ''}>+</button>
                                </div>
                                <button class="btn-remove-cart" onclick="removeFromCartProfile(${index})">ELIMINAR</button>
                            </div>
                            ${!canIncrement ? '<div class="stock-warning">⚠️ Stock máximo alcanzado</div>' : ''}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
        <div class="cart-footer-summary">
            <div class="total-row">
                <span>TOTAL (${totalItems} artículos)</span>
                <span class="total-amount">COP $${total.toLocaleString()}</span>
            </div>
            <button class="btn-checkout-cart" onclick="window.location.href='${APP_URL}/compra'">FINALIZAR COMPRA</button>
        </div>
    `;
}

// Ajusta la cantidad de un item del carrito respetando el stock máximo
function updateQtyProfile(index, delta) {
    const item = cart[index];
    if (!item) return;
    
    const newQty = (item.quantity || 1) + delta;
    const stockLimit = getStockLimit(item.id);
    
    if (newQty < 1) return;
    if (newQty > stockLimit) {
        showToast({ 
            title: "Stock insuficiente", 
            message: `Solo hay ${stockLimit} unidades disponibles de este producto`, 
            type: "error" 
        });
        return;
    }
    
    cart[index].quantity = newQty;
    localStorage.setItem("angelow_cart", JSON.stringify(cart));
    renderCartProfile();
}

// Elimina un producto del carrito local del perfil
function removeFromCartProfile(index) {
    cart.splice(index, 1);
    localStorage.setItem("angelow_cart", JSON.stringify(cart));
    renderCartProfile();
    showToast({message: "Producto eliminado del carrito", type: "info"});
}

// ======================== PERFIL ========================
// Alterna entre modo edición y modo guardar de los datos personales
function toggleEdit() {
    if (isEditing) {
        const guardadoExitoso = saveProfile();
        if (!guardadoExitoso) return;
    }
    
    isEditing = !isEditing;
    const inputs = document.querySelectorAll('#datosPersonales .form-input:not(.readonly)');
    const editBtn = document.getElementById('editBtn');
    
    inputs.forEach(input => input.disabled = !isEditing);
    
    if (isEditing) { 
        editBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> GUARDAR`; 
        editBtn.classList.add('save'); 
        limpiarErroresCampos();
    } else { 
        editBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> EDITAR`; 
        editBtn.classList.remove('save'); 
    }
}

/**
 * Guarda la sección "Datos personales" del formulario de perfil.
 * Flujo: (1) lee los 6 campos del formulario, (2) los valida uno a uno con
 * los validadores de este bloque, (3) si hay errores muestra un toast y NO
 * guarda (retorna false), (4) si todo valida, persiste en localStorage bajo
 * la clave 'angelow_profile' y retorna true.
 * @returns {boolean} true solo si se validó y guardó correctamente.
 */
function saveProfile() {
    const nombre = document.getElementById('nombre').value;
    const apellido = document.getElementById('apellido').value;
    const cedula = document.getElementById('cedula').value;
    const telefono = document.getElementById('telefono').value;
    const fechaNacimiento = document.getElementById('fechaNacimiento').value;
    const genero = document.getElementById('genero').value;

    // Limpia los errores visuales de una edición anterior.
    limpiarErroresCampos();
    let tieneErrores = false;

    // Bloque de validación campo por campo: si un validador falla, el campo
    // se pinta en rojo (mostrarErrorEnCampo) y se acumula el error.
    const nombreValid = validarNombre(nombre, "Nombre");
    if (!nombreValid.valido) { mostrarErrorEnCampo('nombre', nombreValid.mensaje); tieneErrores = true; }

    const apellidoValid = validarNombre(apellido, "Apellido");
    if (!apellidoValid.valido) { mostrarErrorEnCampo('apellido', apellidoValid.mensaje); tieneErrores = true; }

    const cedulaValid = validarCedula(cedula);
    if (!cedulaValid.valido) { mostrarErrorEnCampo('cedula', cedulaValid.mensaje); tieneErrores = true; }

    const telefonoValid = validarTelefono(telefono);
    if (!telefonoValid.valido) { mostrarErrorEnCampo('telefono', telefonoValid.mensaje); tieneErrores = true; }

    const fechaValid = validarFechaNacimiento(fechaNacimiento);
    if (!fechaValid.valido) { mostrarErrorEnCampo('fechaNacimiento', fechaValid.mensaje); tieneErrores = true; }

    const generoValid = validarCampoRequerido(genero, "Género");
    if (!generoValid.valido) { mostrarErrorEnCampo('genero', generoValid.mensaje); tieneErrores = true; }

    if (tieneErrores) {
        showToast({ title: "Error de validación", message: "Por favor corrige los campos marcados en rojo", type: "error" });
        return false;
    }

    const profileData = {
        nombre: nombreValid.valor,
        apellido: apellidoValid.valor,
        cedula: cedulaValid.valor,
        telefono: telefonoValid.valor,
        fechaNacimiento: fechaValid.valor,
        genero: generoValid.valor
    };
    localStorage.setItem("angelow_profile", JSON.stringify(profileData));
    
    showToast({ title: "¡Cambios guardados!", message: "Tu información ha sido actualizada correctamente", type: "success" });
    return true;
}

// Carga los datos personales guardados y los muestra en el formulario
function loadProfile() {
    const profileData = JSON.parse(localStorage.getItem("angelow_profile")) || {};
    if (profileData.nombre) document.getElementById('nombre').value = profileData.nombre;
    if (profileData.apellido) document.getElementById('apellido').value = profileData.apellido;
    if (profileData.cedula) document.getElementById('cedula').value = profileData.cedula;
    if (profileData.telefono) document.getElementById('telefono').value = profileData.telefono;
    if (profileData.fechaNacimiento) document.getElementById('fechaNacimiento').value = profileData.fechaNacimiento;
    if (profileData.genero) document.getElementById('genero').value = profileData.genero;
}

// ======================== DIRECCIONES ========================
// Carga y renderiza las direcciones guardadas del usuario
function loadAddresses() { renderAddresses(); }

// Pinta la lista de direcciones o el estado vacío según corresponda
function renderAddresses() {
    const addressList = document.getElementById('addressList');
    const emptyState = document.getElementById('emptyAddressState');
    
    if (addresses.length === 0) { 
        if(emptyState) emptyState.style.display = 'block'; 
        if(addressList) addressList.style.display = 'none'; 
        return; 
    }
    
    if(emptyState) emptyState.style.display = 'none'; 
    if(addressList) addressList.style.display = 'flex';
    
    addressList.innerHTML = addresses.map(address => `
        <div class="address-card ${address.isDefault ? 'selected' : ''}">
            <div class="address-actions">
                <button class="address-action-btn" onclick="editAddress(${address.id})" title="Editar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="address-action-btn" onclick="showDeleteAddressAlert(${address.id})" title="Eliminar" style="color:#ef4444;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
            <div class="address-title">
                ${address.title} ${address.isDefault ? '<span style="color:var(--success);">(Predeterminada)</span>' : ''}
            </div>
            <div class="address-text">
                <strong>${address.recipient}</strong><br>
                ${address.street}<br>
                ${address.additionalInfo ? address.additionalInfo + '<br>' : ''}
                ${address.neighborhood}<br>
                ${address.municipality}, ${address.department}<br>
                ${address.country}
            </div>
            <div class="action-buttons" style="margin-top:15px;">
                <button class="secondary-btn" onclick="setDefaultAddress(${address.id})" ${address.isDefault ? 'disabled style="opacity:0.5;"' : ''}>
                    ${address.isDefault ? 'Predeterminada' : 'Establecer como predeterminada'}
                </button>
                <button class="danger-btn" onclick="showDeleteAddressAlert(${address.id})">ELIMINAR</button>
            </div>
        </div>
    `).join('');
}

// Muestra el formulario para agregar/editar una dirección
function showAddressForm() { 
    document.getElementById('addressFormContainer').style.display = 'block'; 
    document.getElementById('addressList').style.display = 'none'; 
    document.getElementById('emptyAddressState').style.display = 'none'; 
    limpiarErroresCampos();
}

// Oculta el formulario de dirección y vuelve a la lista/estado vacío
function cancelAddressForm() { 
    document.getElementById('addressFormContainer').style.display = 'none'; 
    editingAddressId = null;
    document.getElementById('addressForm').reset();
    limpiarErroresCampos();
    if (addresses.length === 0) {
        document.getElementById('emptyAddressState').style.display = 'block'; 
    } else {
        document.getElementById('addressList').style.display = 'flex'; 
    }
}

// Valida y guarda la dirección (nueva o editada) en localStorage
function saveAddress() {
    limpiarErroresCampos();
    
    const calle = document.getElementById('calle').value.trim();
    const destinatario = document.getElementById('destinatario').value.trim();
    const departamento = document.getElementById('departamento').value.trim();
    const municipio = document.getElementById('municipio').value.trim();
    const barrio = document.getElementById('barrio').value.trim();
    const infoAdicional = document.getElementById('infoAdicional').value.trim();

    let tieneErrores = false;

    const destinatarioValid = validarNombre(destinatario, "Destinatario");
    if (!destinatarioValid.valido) { mostrarErrorEnCampo('destinatario', destinatarioValid.mensaje); tieneErrores = true; }

    if (!calle || calle.length < 5) { mostrarErrorEnCampo('calle', 'La dirección debe tener al menos 5 caracteres'); tieneErrores = true; }
    if (!departamento || departamento === '') { mostrarErrorEnCampo('departamento', 'El departamento es requerido'); tieneErrores = true; }
    if (!municipio || municipio === '') { mostrarErrorEnCampo('municipio', 'El municipio es requerido'); tieneErrores = true; }
    if (!barrio || barrio.length < 3) { mostrarErrorEnCampo('barrio', 'El barrio debe tener al menos 3 caracteres'); tieneErrores = true; }

    if (tieneErrores) {
        showToast({ title: "Error de validación", message: "Por favor corrige los campos marcados en rojo", type: "error" });
        return;
    }

    if (editingAddressId) {
        const addressIndex = addresses.findIndex(a => a.id === editingAddressId);
        if (addressIndex !== -1) {
            addresses[addressIndex] = {
                ...addresses[addressIndex],
                recipient: destinatario,
                department: departamento,
                municipality: municipio,
                street: calle,
                additionalInfo: infoAdicional,
                neighborhood: barrio
            };
            
            localStorage.setItem("angelow_addresses", JSON.stringify(addresses));
            loadAddresses();
            cancelAddressForm();
            showToast({title: "¡Dirección actualizada!", message: "Tu dirección ha sido actualizada correctamente", type: "success"});
            editingAddressId = null;
            return;
        }
    }

    const newAddress = { 
        id: Date.now(), 
        title: "Casa", 
        country: "Colombia", 
        department: departamento, 
        municipality: municipio, 
        street: calle, 
        additionalInfo: infoAdicional, 
        neighborhood: barrio, 
        recipient: destinatario, 
        postalCode: "05001", 
        isDefault: addresses.length === 0 
    };
    
    addresses.push(newAddress); 
    localStorage.setItem("angelow_addresses", JSON.stringify(addresses)); 
    loadAddresses(); 
    cancelAddressForm(); 
    showToast({title: "¡Dirección agregada!", message: "Tu dirección ha sido guardada correctamente", type: "success"});
}

// Carga los datos de una dirección en el formulario para editarla
function editAddress(id) { 
    const address = addresses.find(a => a.id === id); 
    if (!address) return; 
    
    editingAddressId = id;
    
    document.getElementById('calle').value = address.street || ''; 
    document.getElementById('infoAdicional').value = address.additionalInfo || ''; 
    document.getElementById('barrio').value = address.neighborhood || ''; 
    document.getElementById('destinatario').value = address.recipient || ''; 
    document.getElementById('departamento').value = address.department || ''; 
    document.getElementById('municipio').value = address.municipality || ''; 
    
    limpiarErroresCampos();
    showAddressForm(); 
}

// Establece una dirección como predeterminada
function setDefaultAddress(id) { 
    addresses.forEach(a => a.isDefault = a.id === id); 
    localStorage.setItem("angelow_addresses", JSON.stringify(addresses)); 
    loadAddresses(); 
    showToast({title: "Dirección predeterminada", message: "La dirección ha sido establecida como predeterminada", type: "success"}); 
}

// Muestra la alerta de confirmación para eliminar una dirección
function showDeleteAddressAlert(id) { 
    pendingDeleteId = id; 
    pendingDeleteType = 'address'; 
    showAlert("¿Eliminar dirección?", "¿Estás seguro de que deseas eliminar esta dirección?"); 
}

// Elimina la dirección confirmada y deja otra como predeterminada si es preciso
function deleteAddressConfirmed(id) { 
    addresses = addresses.filter(a => a.id !== id); 
    if (addresses.length > 0 && !addresses.some(a => a.isDefault)) {
        addresses[0].isDefault = true;
    }
    localStorage.setItem("angelow_addresses", JSON.stringify(addresses)); 
    loadAddresses(); 
    showToast({title: "Dirección eliminada", message: "La dirección ha sido eliminada correctamente", type: "success"}); 
}

// ======================== PEDIDOS ========================
// Carga los pedidos: desde la API si hay sesión, o desde localStorage
function loadOrders() { 
    if (currentUser) {
        fetchOrdersFromAPI();
    } else {
        renderOrders(); 
    }
}

// Solicita al servidor la lista de pedidos del usuario y los renderiza
async function fetchOrdersFromAPI() {
    try {
        const res = await fetch(`${APP_URL}/api/mis-pedidos`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Error al cargar pedidos');
        const data = await res.json();
        if (Array.isArray(data)) {
            orders = data;
            renderOrders();
        }
    } catch (e) {
        console.error('Error fetching orders:', e);
        orders = [];
        renderOrders();
    }
}

// Traduce el estado del pedido servidor a su etiqueta de interfaz
function getStatusLabel(estado) {
    const map = {
        'pendiente': 'Pendiente',
        'confirmada': 'Confirmada',
        'cambio': 'Cambio',
        'devolucion': 'Devolución',
        'rechazada': 'Rechazada',
        'confirmado': 'Confirmada',
        'procesando': 'Pendiente',
        'listo': 'Confirmada',
        'asignado': 'Confirmada',
        'aceptado': 'Confirmada',
        'recogido': 'Confirmada',
        'en_camino': 'Confirmada',
        'entregado': 'Confirmada',
        'cancelado': 'Rechazada',
        'reembolsado': 'Rechazada'
    };
    return map[estado] || estado || 'Pendiente';
}

// Devuelve la clase CSS que representa el estado del pedido
function getStatusClass(estado) {
    const map = {
        'pendiente': 'status-pending',
        'confirmada': 'status-delivered',
        'cambio': 'status-processing',
        'devolucion': 'status-processing',
        'rechazada': 'status-cancelled',
        'confirmado': 'status-delivered',
        'procesando': 'status-pending',
        'listo': 'status-delivered',
        'asignado': 'status-delivered',
        'aceptado': 'status-delivered',
        'recogido': 'status-delivered',
        'en_camino': 'status-delivered',
        'entregado': 'status-delivered',
        'cancelado': 'status-cancelled',
        'reembolsado': 'status-cancelled'
    };
    return map[estado] || 'status-pending';
}

// Devuelve el color asociado al estado del pedido
function getStatusColor(estado) {
    const map = {
        'pendiente': '#f59e0b',
        'confirmada': '#10b981',
        'cambio': '#3b82f6',
        'devolucion': '#8b5cf6',
        'rechazada': '#ef4444',
        'confirmado': '#10b981',
        'procesando': '#f59e0b',
        'listo': '#10b981',
        'asignado': '#10b981',
        'aceptado': '#10b981',
        'recogido': '#10b981',
        'en_camino': '#10b981',
        'entregado': '#10b981',
        'cancelado': '#ef4444',
        'reembolsado': '#ef4444'
    };
    return map[estado] || '#6b7280';
}

// Renderiza la lista de pedidos del usuario con su estado y detalles
function renderOrders() { 
    const ordersList = document.getElementById('ordersList'); 
    const emptyState = document.getElementById('emptyOrdersState'); 
    
    if (!ordersList) return;
    
    if (orders.length === 0) { 
        if(emptyState) emptyState.style.display = 'block'; 
        if(ordersList) ordersList.style.display = 'none'; 
        return; 
    } 
    if(emptyState) emptyState.style.display = 'none'; 
    if(ordersList) ordersList.style.display = 'flex'; 
    
    ordersList.innerHTML = orders.map(order => {
        let orderId = parseInt(order.pedido_id ?? order.id, 10);
        if (Number.isNaN(orderId)) orderId = null;
        const orderNumber = order.numero_pedido || `ORD-${orderId}`;
        const fecha = order.fecha_pedido ? new Date(order.fecha_pedido) : new Date();
        const estado = order.estado || 'pendiente';
        const total = parseFloat(order.total) || 0;
        const totalProductos = order.total_productos || 0;
        
        return `
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id">Factura #${orderNumber}</div>
                        <div class="order-date">${fecha.toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                    </div>
                    <div class="order-status ${getStatusClass(estado)}">
                        ${getStatusLabel(estado)}
                    </div>
                </div>
                <div class="order-details">
                    <div class="order-detail-item">
                        <div class="detail-label">Total</div>
                        <div class="detail-value">COP $${total.toLocaleString()}</div>
                    </div>
                    <div class="order-detail-item">
                        <div class="detail-label">Productos</div>
                        <div class="detail-value">${totalProductos} artículos</div>
                    </div>
                </div>
                ${orderId ? `<div class="order-actions">
                    <button class="primary-btn" onclick="viewInvoice(${orderId})">VER FACTURA</button>
                </div>` : ''}
            </div>
        `;
    }).join(''); 
}

// Consulta la factura de un pedido y abre el modal con su contenido
async function viewInvoice(pedidoId) {
    try {
        const idNum = parseInt(pedidoId, 10);
        if (!pedidoId || Number.isNaN(idNum)) {
            console.error('viewInvoice: id de pedido inválido:', pedidoId);
            showToast({title: "Error", message: "Id de pedido inválido", type: "error"});
            return;
        }
        const url = `${APP_URL}/api/mis-pedidos/${idNum}`;
        console.log('Cargando factura desde:', url);
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status} - Factura no encontrada`);
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('El servidor no devolvió JSON válido:', text.slice(0, 200));
            throw new Error('El servidor no devolvió una respuesta JSON válida');
        }
        
        if (!data.pedido) {
            showToast({title: "Error", message: "Factura no encontrada", type: "error"});
            return;
        }
        
        showInvoiceModal(data.pedido, data.items || []);
    } catch (e) {
        console.error('Error loading invoice:', e);
        showToast({title: "Error", message: e.message || "No se pudo cargar la factura", type: "error"});
    }
}

// Construye y muestra el modal de la factura con los datos del pedido
function showInvoiceModal(pedido, items) {
    const modal = document.getElementById('orderModal');
    const container = document.getElementById('orderModalContent');
    if (!modal || !container) return;

    const logoUrl = APP_URL + '/assets/imagenes/general/logos.png';
    const fecha = pedido.fecha_pedido ? new Date(pedido.fecha_pedido) : new Date();
    const estado = pedido.estado || 'pendiente';
    const envio = parseFloat(pedido.costo_envio) || 0;
    const descuento = parseFloat(pedido.descuento) || 0;

    const productosHtml = items.length > 0 ? items.map((item, index) => `
        <tr style="border-bottom: 1px solid #e8edf5; ${index % 2 === 0 ? 'background: #fafbfc;' : 'background: #ffffff;'}">
            <td style="padding: 14px 18px; color: #1f2937; font-weight: 600;">${item.nombre_producto || 'Producto'}</td>
            <td style="padding: 14px 18px; text-align: center; color: #4b5563;">${item.talla || 'Única'}</td>
            <td style="padding: 14px 18px; text-align: center; color: #4b5563;">${item.cantidad || 1}</td>
            <td style="padding: 14px 18px; text-align: right; color: #4b5563;">COP $${parseFloat(item.precio_unitario || 0).toLocaleString()}</td>
            <td style="padding: 14px 18px; text-align: right; color: #1e3a8a; font-weight: 700;">COP $${parseFloat(item.subtotal || 0).toLocaleString()}</td>
        </tr>
    `).join('') : '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #9ca3af;">No hay productos</td></tr>';

    const fechaStr = fecha.toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' });
    const horaStr = fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    const nombreCliente = pedido.nombre_cliente || pedido.nombre_usuario || 'Cliente';

    container.innerHTML = `
        <button onclick="closeOrderModal()" style="position: absolute; top: 16px; right: 16px; background: #f3f4f6; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #6b7280; z-index: 1;">&times;</button>

        <!-- HEADER -->
        <div style="text-align: center; border-bottom: 3px solid #1e3a8a; padding-bottom: 25px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap;">
                <img src="${logoUrl}" alt="ANGELOW" style="height: 60px; width: auto;" onerror="this.style.display='none'">
                <div>
                    <h1 style="color: #1e3a8a; font-size: 32px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">ANGELOW</h1>
                    <span style="color: #4a6fa5; font-size: 13px; letter-spacing: 3px; text-transform: uppercase; font-weight: 500;">Moda Infantil &middot; Calidad y Estilo</span>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; align-items: center;">
                <span style="background: ${getStatusColor(estado)}; color: white; font-size: 14px; font-weight: 700; padding: 6px 25px; border-radius: 30px;">${getStatusLabel(estado).toUpperCase()}</span>
            </div>
            <div style="margin-top: 12px; color: #4a6fa5; font-size: 15px; font-weight: 600;">N&deg; ${pedido.numero_pedido || 'N/A'}</div>
            <div style="color: #6b7280; font-size: 14px;">${fechaStr} &middot; ${horaStr}</div>
        </div>
        
        <!-- MENSAJE DE GRACIAS -->
        <div style="text-align: center; background: linear-gradient(135deg, #f0f4fa 0%, #e8edf5 100%); border-radius: 12px; padding: 14px; margin-bottom: 28px;">
            <span style="color: #1e3a8a; font-size: 16px; font-weight: 600;">&iexcl;Gracias por tu compra, ${nombreCliente}!</span>
        </div>
        
        <!-- DATOS CLIENTE Y ENV&Iacute;O -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 28px; background: #f8fafc; padding: 22px 28px; border-radius: 14px; border: 1px solid #e8edf5;">
            <div>
                <h3 style="color: #1e3a8a; font-size: 13px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px;">Datos del Cliente</h3>
                <p style="margin: 5px 0; color: #1f2937; font-weight: 700; font-size: 15px;">${nombreCliente}</p>
                <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">CC: ${pedido.cedula_cliente || pedido.cedula_usuario || 'N/A'}</p>
                <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${pedido.telefono_cliente || pedido.telefono_usuario || 'N/A'}</p>
                <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${pedido.email_cliente || pedido.email_usuario || 'N/A'}</p>
            </div>
            <div>
                <h3 style="color: #1e3a8a; font-size: 13px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px;">Datos de Env&iacute;o</h3>
                <p style="margin: 5px 0; color: #1f2937; font-weight: 700; font-size: 15px;">${pedido.destinatario || nombreCliente}</p>
                <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${pedido.direccion_envio || ''}${pedido.direccion_complementaria ? ', ' + pedido.direccion_complementaria : ''}</p>
                <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${pedido.metodo_envio || ''}</p>
                <p style="margin: 4px 0; color: #4b5563; font-size: 14px; font-weight: 600;">${envio > 0 ? 'COP $' + envio.toLocaleString() : 'Env&iacute;o Gratis'}</p>
            </div>
        </div>
        
        <!-- TABLA DE PRODUCTOS -->
        <div style="margin-bottom: 25px; overflow-x: auto; border-radius: 14px; border: 1px solid #e8edf5;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #1e3a8a 0%, #2a4f9e 100%);">
                        <th style="padding: 14px 18px; text-align: left; color: #ffffff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Producto</th>
                        <th style="padding: 14px 18px; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Talla</th>
                        <th style="padding: 14px 18px; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Cant.</th>
                        <th style="padding: 14px 18px; text-align: right; color: #ffffff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Precio Unit.</th>
                        <th style="padding: 14px 18px; text-align: right; color: #ffffff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${productosHtml}
                </tbody>
            </table>
        </div>
        
        <!-- TOTALES -->
        <div style="display: flex; justify-content: flex-end; background: #f8fafc; border-radius: 14px; padding: 24px 30px; margin-bottom: 25px; border: 1px solid #e8edf5;">
            <div style="width: 320px;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #4b5563; font-size: 15px;">
                    <span>Subtotal</span>
                    <span>COP $${parseFloat(pedido.subtotal || 0).toLocaleString()}</span>
                </div>
                ${descuento > 0 ? `
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #10b981; font-size: 15px; font-weight: 600;">
                        <span>Descuento</span>
                        <span>- COP $${descuento.toLocaleString()}</span>
                    </div>
                ` : ''}
                <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #4b5563; font-size: 15px;">
                    <span>Env&iacute;o</span>
                    <span>${envio > 0 ? 'COP $' + envio.toLocaleString() : 'Gratis'}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 14px 0 6px 0; border-top: 3px solid #1e3a8a; margin-top: 6px;">
                    <span style="color: #1e3a8a; font-size: 20px; font-weight: 800;">TOTAL</span>
                    <span style="color: #1e3a8a; font-size: 24px; font-weight: 900;">COP $${parseFloat(pedido.total || 0).toLocaleString()}</span>
                </div>
                <div style="text-align: right; margin-top: 8px; color: #6b7280; font-size: 13px; font-weight: 500;">${pedido.metodo_pago || 'No especificado'}</div>
            </div>
        </div>

        <!-- FOOTER DE FACTURA -->
        <div style="border-top: 2px solid #e8edf5; margin-top: 20px; padding-top: 20px; text-align: center; color: #6b7280; font-size: 13px;">
            <p style="margin: 0; color: #1e3a8a; font-size: 16px; font-weight: 600;">Gracias por elegir ANGELOW</p>
            <p style="margin: 5px 0 0 0; color: #4a6fa5;">Moda Infantil - Calidad y Estilo para tus peque&ntilde;os</p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">info@angelow.com | +57 3135951664 | Medell&iacute;n, Colombia</p>
            <p style="margin: 5px 0 0 0; font-size: 11px; color: #b0b8c8;">Pedido N&deg; ${pedido.numero_pedido || 'N/A'} | ${fechaStr}</p>
        </div>
        
        <!-- BOT&Oacute;NES -->
        <div style="display: flex; gap: 14px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
            <button onclick="closeOrderModal()" style="background: #6b7280; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(107, 114, 128, 0.35); transition: all 0.3s ease;">
                Cerrar
            </button>
            <button onclick="descargarFacturaPDF('${pedido.numero_pedido || 'N/A'}', this)" style="background: #10b981; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35); transition: all 0.3s ease;">
                Descargar Factura PDF
            </button>
        </div>
    `;

    modal.classList.add('active');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Cierra el modal de la factura y restaura el scroll de la página
function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Genera y descarga la factura en PDF usando html2canvas y jsPDF
function descargarFacturaPDF(numeroPedido, btn) {
    const invoiceContent = document.getElementById('orderModalContent');
    if (!invoiceContent) {
        showToast({title: "Error", message: "No se pudo generar el PDF", type: "error"});
        return;
    }

    showToast({title: "Generando PDF", message: "Por favor espere...", type: "info"});

    btn = btn || event.target;
    btn.disabled = true;
    btn.textContent = 'Generando...';

    const clone = invoiceContent.cloneNode(true);
    clone.style.position = 'fixed';
    clone.style.left = '-99999px';
    clone.style.top = '0';
    clone.style.maxHeight = 'none';
    clone.style.overflow = 'visible';
    clone.style.width = '900px';
    clone.querySelectorAll('button').forEach(b => b.style.display = 'none');
    document.body.appendChild(clone);

    const capturable = clone;

    const waitImages = () => Promise.all(
        Array.from(capturable.querySelectorAll('img')).map(img => {
            if (img.complete && img.naturalWidth > 0) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve;
            });
        })
    );

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 10;
    const contentWidth = pageWidth - (margin * 2);

    waitImages().then(() => {
        return html2canvas(capturable, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            logging: false,
            windowWidth: 900,
            width: capturable.scrollWidth,
            height: capturable.scrollHeight
        });
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const imgWidth = contentWidth;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        let heightLeft = imgHeight;
        let position = margin;

        doc.addImage(imgData, 'PNG', margin, position, imgWidth, imgHeight);
        heightLeft -= (pageHeight - margin * 2);

        while (heightLeft > 0) {
            position = -(pageHeight - margin * 2) + margin;
            doc.addPage();
            doc.addImage(imgData, 'PNG', margin, position, imgWidth, imgHeight);
            heightLeft -= (pageHeight - margin * 2);
        }

        const safeName = (numeroPedido || 'Factura').replace(/[^a-zA-Z0-9\-]/g, '_');
        doc.save('Factura_' + safeName + '.pdf');

        btn.disabled = false;
        btn.textContent = 'Descargar Factura PDF';
        showToast({title: "PDF descargado", message: "Factura_" + safeName + ".pdf", type: "success"});
    }).catch(err => {
        console.error('Error generating PDF:', err);
        btn.disabled = false;
        btn.textContent = 'Descargar Factura PDF';
        showToast({title: "Error", message: "No se pudo generar el PDF", type: "error"});
    }).finally(() => {
        if (capturable && capturable.parentNode) capturable.parentNode.removeChild(capturable);
    });
}

// ======================== TARJETAS ========================
// Carga y renderiza las tarjetas de pago guardadas
function loadCards() { renderCards(); }

// Pinta la lista de tarjetas o el estado vacío según corresponda
function renderCards() { 
    const cardList = document.getElementById('cardList'); 
    const emptyState = document.getElementById('emptyCardState'); 
    if (cards.length === 0) { 
        if(emptyState) emptyState.style.display = 'block'; 
        if(cardList) cardList.style.display = 'none'; 
        return; 
    } 
    if(emptyState) emptyState.style.display = 'none'; 
    if(cardList) cardList.style.display = 'grid'; 
    cardList.innerHTML = cards.map(card => `
        <div class="card-item">
            ${card.isDefault ? '<div class="card-default-badge">PREDETERMINADA</div>' : ''}
            <div class="card-actions">
                <button class="card-action-btn" onclick="setDefaultCard(${card.id})" title="Establecer como predeterminada">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
                <button class="card-action-btn" onclick="showDeleteCardAlert(${card.id})" title="Eliminar" style="color:#ef4444;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
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
                <div class="card-number">${card.number}</div>
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:auto;">
                    <div style="flex:1;">
                        <div class="card-holder">${card.holder}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="card-expiry-label">Válida hasta</div>
                        <div class="card-expiry">${card.expiry}</div>
                    </div>
                </div>
            </div>
            <div class="action-buttons" style="margin-top:15px;">
                <button class="secondary-btn" onclick="setDefaultCard(${card.id})" ${card.isDefault ? 'disabled style="opacity:0.5;"' : ''}>
                    ${card.isDefault ? 'Predeterminada' : 'Establecer como predeterminada'}
                </button>
                <button class="danger-btn" onclick="showDeleteCardAlert(${card.id})">ELIMINAR</button>
            </div>
        </div>
    `).join(''); 
}

// Muestra el formulario para registrar una nueva tarjeta
function showCardForm() { 
    document.getElementById('emptyCardState').style.display = 'none'; 
    document.getElementById('cardList').style.display = 'none'; 
    document.getElementById('cardFormContainer').style.display = 'block'; 
    document.getElementById('cardForm').reset();
    document.getElementById('displayCardNumber').textContent = '•••• •••• •••• ••••';
    document.getElementById('displayCardHolder').textContent = 'NOMBRE';
    document.getElementById('displayCardExpiry').textContent = '••/••';
    limpiarErroresCampos();
    initCardPreview(); 
}

// Cancela el registro de tarjeta y restaura la vista de la lista
function cancelCardForm() { 
    document.getElementById('cardFormContainer').style.display = 'none'; 
    limpiarErroresCampos();
    if (cards.length === 0) {
        document.getElementById('emptyCardState').style.display = 'block'; 
    } else {
        document.getElementById('cardList').style.display = 'grid'; 
    }
    document.getElementById('cardForm').reset(); 
    document.getElementById('displayCardNumber').textContent = '•••• •••• •••• ••••'; 
    document.getElementById('displayCardHolder').textContent = 'NOMBRE'; 
    document.getElementById('displayCardExpiry').textContent = '••/••'; 
}

// Valida los datos y guarda la nueva tarjeta en localStorage
function saveCard() { 
    limpiarErroresCampos();
    
    const cardNumber = document.getElementById('cardNumber').value; 
    const cardName = document.getElementById('cardName').value; 
    const cardExpiry = document.getElementById('cardExpiry').value;
    const cardCVV = document.getElementById('cardCVV').value;
    const cardPostalCode = document.getElementById('cardPostalCode')?.value || '';

    let tieneErrores = false;

    const numeroValid = validarNumeroTarjeta(cardNumber);
    if (!numeroValid.valido) { mostrarErrorEnCampo('cardNumber', numeroValid.mensaje); tieneErrores = true; }
    
    const nombreValid = validarNombreTitular(cardName);
    if (!nombreValid.valido) { mostrarErrorEnCampo('cardName', nombreValid.mensaje); tieneErrores = true; }
    
    const expiryValid = validarFechaExpiracion(cardExpiry);
    if (!expiryValid.valido) { mostrarErrorEnCampo('cardExpiry', expiryValid.mensaje); tieneErrores = true; }
    
    const cvvValid = validarCVV(cardCVV);
    if (!cvvValid.valido) { mostrarErrorEnCampo('cardCVV', cvvValid.mensaje); tieneErrores = true; }
    
    const postalValid = validarCodigoPostal(cardPostalCode);
    if (!postalValid.valido) { mostrarErrorEnCampo('cardPostalCode', postalValid.mensaje); tieneErrores = true; }

    if (tieneErrores) {
        showToast({ title: "Error de validación", message: "Por favor corrige los campos marcados en rojo", type: "error" });
        return;
    }
    
    const newCard = { 
        id: Date.now(), 
        number: numeroValid.valor.replace(/(.{4})/g, '$1 ').trim(), 
        holder: nombreValid.valor.toUpperCase(), 
        expiry: expiryValid.valor, 
        cvv: cvvValid.valor,
        postalCode: postalValid.valor,
        type: 'visa', 
        isDefault: cards.length === 0 
    }; 
    
    cards.push(newCard); 
    localStorage.setItem("angelow_cards", JSON.stringify(cards)); 
    loadCards(); 
    cancelCardForm(); 
    showToast({title: "¡Tarjeta guardada!", message: "Tu método de pago ha sido registrado correctamente", type: "success"}); 
}

// Previsualiza en vivo la tarjeta mientras se escriben sus datos
function initCardPreview() { 
    const cardNumber = document.getElementById('cardNumber'); 
    const cardExpiry = document.getElementById('cardExpiry'); 
    const cardName = document.getElementById('cardName'); 
    
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) { 
            let v = e.target.value.replace(/\D/g, ''); 
            let formatted = v.match(/.{1,4}/g)?.join(' ') || v; 
            e.target.value = formatted; 
            document.getElementById('displayCardNumber').textContent = formatted.padEnd(19, '•'); 
        }); 
    }
    
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function(e) { 
            let v = e.target.value.replace(/\D/g, ''); 
            if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4); 
            e.target.value = v; 
            document.getElementById('displayCardExpiry').textContent = v.padEnd(5, '•'); 
        }); 
    }
    
    if (cardName) {
        cardName.addEventListener('input', function(e) { 
            document.getElementById('displayCardHolder').textContent = e.target.value.toUpperCase() || 'NOMBRE'; 
        }); 
    }
}

// Muestra la alerta de confirmación para eliminar una tarjeta
function showDeleteCardAlert(id) { 
    pendingDeleteId = id; 
    pendingDeleteType = 'card'; 
    showAlert("¿Eliminar tarjeta?", "¿Estás seguro de que deseas eliminar esta tarjeta?"); 
}

// Elimina la tarjeta confirmada y deja otra como predeterminada si es preciso
function deleteCardConfirmed(id) { 
    cards = cards.filter(c => c.id !== id); 
    if (cards.length > 0 && !cards.some(c => c.isDefault)) {
        cards[0].isDefault = true;
    }
    localStorage.setItem("angelow_cards", JSON.stringify(cards)); 
    loadCards(); 
    showToast({title: "Tarjeta eliminada", message: "La tarjeta ha sido eliminada correctamente", type: "success"}); 
}

// Establece una tarjeta como método de pago predeterminado
function setDefaultCard(id) { 
    cards.forEach(c => c.isDefault = c.id === id); 
    localStorage.setItem("angelow_cards", JSON.stringify(cards)); 
    loadCards(); 
    showToast({title: "Tarjeta predeterminada", message: "La tarjeta ha sido establecida como predeterminada", type: "success"}); 
}

// ======================== FAVORITOS ========================
// Carga los favoritos guardados del usuario y los pinta en el perfil
function loadFavorites() { 
    const savedIds = JSON.parse(localStorage.getItem(getFavoritesStorageKey()) || localStorage.getItem("angelow_favorites") || "[]"); 
    favorites = products.filter(p => savedIds.includes(p.id)); 
    renderFavorites(); 
}

// Renderiza los productos favoritos del usuario en su cuadrícula
function renderFavorites() { 
    const grid = document.getElementById('favoritesGrid'); 
    const emptyState = document.getElementById('emptyFavoritesState'); 
    if (favorites.length === 0) { 
        if(emptyState) emptyState.style.display = 'block'; 
        if(grid) grid.style.display = 'none'; 
        return; 
    } 
    if(emptyState) emptyState.style.display = 'none'; 
    if(grid) grid.style.display = 'grid'; 
    grid.innerHTML = favorites.map(fav => `
        <div class="favorite-item">
            <div class="favorite-badge">FAVORITO</div>
            <img src="${fav.imgs[0]}" alt="${fav.name}" class="favorite-image">
            <div class="favorite-info">
                <div class="favorite-title">${fav.name}</div>
                <div class="favorite-price">COP $${fav.price.toLocaleString()}</div>
                <div class="favorite-actions">
                    <button class="favorite-action-btn add-to-cart" onclick="addToCartFromFavorites(${fav.id})">AÑADIR AL CARRITO</button>
                    <button class="favorite-action-btn remove" onclick="showDeleteFavoriteAlert(${fav.id})">QUITAR</button>
                </div>
            </div>
        </div>
    `).join(''); 
}

// Muestra la alerta de confirmación para quitar un favorito
function showDeleteFavoriteAlert(id) { 
    pendingDeleteId = id; 
    pendingDeleteType = 'favorite'; 
    showAlert("¿Eliminar de favoritos?", "¿Estás seguro de que deseas eliminar este producto de tus favoritos?"); 
}

// Elimina el favorito confirmado y guarda el cambio en localStorage
function deleteFavoriteConfirmed(id) { 
    const favIds = favorites.filter(f => f.id !== id).map(f => f.id); 
    localStorage.setItem(getFavoritesStorageKey(), JSON.stringify(favIds)); 
    loadFavorites(); 
    showToast({title: "Eliminado de favoritos", message: "El producto ha sido eliminado de tus favoritos", type: "success"}); 
}

// Añade un producto favorito al carrito respetando el stock máximo
function addToCartFromFavorites(id) { 
    const fav = favorites.find(f => f.id === id); 
    if (!fav) return; 
    
    const stockLimit = getStockLimit(id);
    const currentQty = getCurrentQuantityInCart(id);
    
    if (currentQty >= stockLimit) {
        showToast({ 
            title: "Stock insuficiente", 
            message: `Solo hay ${stockLimit} unidades disponibles de este producto`, 
            type: "error" 
        });
        return;
    }
    
    const existing = cart.find(item => item.id === fav.id && item.selectedSize === (fav.sizes?.[0] || 'M'));
    if (existing) {
        if (existing.quantity >= stockLimit) {
            showToast({ 
                title: "Stock insuficiente", 
                message: `Solo hay ${stockLimit} unidades disponibles de este producto`, 
                type: "error" 
            });
            return;
        }
        existing.quantity = (existing.quantity || 1) + 1;
    } else {
        const cartItem = { 
            ...fav, 
            cartId: 'fav_' + Date.now(), 
            quantity: 1, 
            selectedSize: fav.sizes?.[0] || 'M' 
        }; 
        cart.push(cartItem);
    }
    
    localStorage.setItem("angelow_cart", JSON.stringify(cart)); 
    renderCartProfile(); 
    showToast({title: "¡Añadido al carrito!", message: `${fav.name} ha sido añadido a tu carrito`, type: "success"}); 
}

// ======================== ALERTA ========================
// Muestra la alerta modal genérica con título y mensaje
function showAlert(title, message) { 
    document.getElementById('alertTitle').textContent = title; 
    document.getElementById('alertMessage').textContent = message; 
    document.getElementById('alertOverlay').classList.add('active'); 
}

// Cierra la alerta modal y limpia el id pendiente de eliminación
function closeAlert() { 
    document.getElementById('alertOverlay').classList.remove('active'); 
    pendingDeleteId = null; 
    // No resetear pendingDeleteType aquí para permitir logout
}

// Ejecuta la eliminación según el tipo pendiente (logout, dirección,
// tarjeta o favorito) al confirmar la alerta
function confirmDelete() { 
    if (pendingDeleteType === 'logout') {
        confirmLogout();
        return;
    }
    
    if (pendingDeleteId && pendingDeleteType) { 
        switch(pendingDeleteType) { 
            case 'address': deleteAddressConfirmed(pendingDeleteId); break; 
            case 'card': deleteCardConfirmed(pendingDeleteId); break; 
            case 'favorite': deleteFavoriteConfirmed(pendingDeleteId); break; 
        } 
    } 
    closeAlert(); 
}

// ======================== AUTENTICACIÓN ========================
// Funcionalidad de definir contraseña (en desarrollo)
function definePassword() { 
    showToast({title: "Definir contraseña", message: "Funcionalidad en desarrollo", type: "info"}); 
}

// Funcionalidad de recuperar contraseña (en desarrollo)
function recoverPassword() { 
    showToast({title: "Recuperar contraseña", message: "Funcionalidad en desarrollo", type: "info"}); 
}

// Muestra el número de sesiones activas actuales
function viewSessions() { 
    showToast({title: "Sesiones activas", message: "Actualmente tienes 1 sesión activa", type: "info"}); 
}

// Funcionalidad de verificación en dos pasos (en desarrollo)
function enableTwoFactor() { 
    showToast({title: "Verificación en dos pasos", message: "Funcionalidad en desarrollo", type: "info"}); 
}

// ======================== MENÚ LATERAL ========================
// Enlaza los ítems del menú lateral con sus secciones del perfil
function initSidebar() { 
    document.querySelectorAll('.menu-item[data-section]').forEach(item => { 
        item.addEventListener('click', function() { 
            const section = this.getAttribute('data-section'); 
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active')); 
            this.classList.add('active'); 
            document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active')); 
            const target = document.getElementById(section); 
            if (target) {
                target.classList.add('active');
                if (section === 'seguimientoSection' && typeof initSeguimiento === 'function') {
                    setTimeout(() => initSeguimiento(), 100);
                }
            } else { 
                showToast({message: "Sección en desarrollo", type: "info"}); 
                document.getElementById('datosPersonales').classList.add('active'); 
                document.querySelector('.menu-item[data-section="datosPersonales"]').classList.add('active'); 
            } 
        }); 
    }); 
}

// ======================== INICIALIZACIÓN ========================
// Carga todos los módulos del perfil al cargar el DOM
document.addEventListener('DOMContentLoaded', function() { 
    loadProfile();
    loadAddresses(); 
    loadOrders(); 
    loadCards(); 
    loadFavorites(); 
    initSidebar(); 
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    
    // Renderizar carrito en la sección
    renderCartProfile();
});