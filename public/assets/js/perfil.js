// ======================== VARIABLES GLOBALES ========================
let isEditing = false;
let cart = [];
let addresses = [];
let orders = [];
let cards = [];
let favorites = [];
let profileData = {};
let currentUser = window.CURRENT_USER || JSON.parse(localStorage.getItem("angelow_user")) || null;
let pendingDeleteId = null;
let pendingDeleteType = null;
let editingAddressId = null;
let userKey = window.USER_KEY || 'guest';

// ======================== FUNCIONES DE ALMACENAMIENTO POR USUARIO ========================
function getStorageKey(baseKey) {
    return `angelow_${baseKey}_${userKey}`;
}

function loadUserData() {
    try {
        cart = JSON.parse(localStorage.getItem(getStorageKey('cart'))) || [];
    } catch(e) { cart = []; }
    
    try {
        addresses = JSON.parse(localStorage.getItem(getStorageKey('addresses'))) || [];
    } catch(e) { addresses = []; }
    
    try {
        orders = JSON.parse(localStorage.getItem(getStorageKey('orders'))) || [];
    } catch(e) { orders = []; }
    
    try {
        cards = JSON.parse(localStorage.getItem(getStorageKey('cards'))) || [];
    } catch(e) { cards = []; }
    
    try {
        profileData = JSON.parse(localStorage.getItem(getStorageKey('profile'))) || {};
    } catch(e) { profileData = {}; }
    
    try {
        favorites = JSON.parse(localStorage.getItem(getStorageKey('favorites'))) || [];
    } catch(e) { favorites = []; }
    
    console.log('Datos cargados para usuario:', userKey);
}

function saveUserData() {
    localStorage.setItem(getStorageKey('cart'), JSON.stringify(cart));
    localStorage.setItem(getStorageKey('addresses'), JSON.stringify(addresses));
    localStorage.setItem(getStorageKey('orders'), JSON.stringify(orders));
    localStorage.setItem(getStorageKey('cards'), JSON.stringify(cards));
    localStorage.setItem(getStorageKey('profile'), JSON.stringify(profileData));
    localStorage.setItem(getStorageKey('favorites'), JSON.stringify(favorites));
    console.log('Datos guardados para usuario:', userKey);
}

function getFavoritesStorageKey() {
    if (currentUser?.email) return `angelow_favorites_${currentUser.email}`;
    if (currentUser?.id) return `angelow_favorites_${currentUser.id}`;
    return `angelow_favorites_${userKey}`;
}

// ======================== PRODUCTOS ========================
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

// ======================== VALIDACIONES ========================
function validarCedula(cedula) {
    if (!cedula || cedula.trim() === '') return { valido: false, mensaje: "La cédula es requerida" };
    const cedulaLimpia = cedula.trim().replace(/\s/g, '');
    if (!/^\d+$/.test(cedulaLimpia)) return { valido: false, mensaje: "La cédula solo debe contener números" };
    if (cedulaLimpia.length < 6) return { valido: false, mensaje: "La cédula debe tener al menos 6 dígitos" };
    if (cedulaLimpia.length > 15) return { valido: false, mensaje: "La cédula no debe tener más de 15 dígitos" };
    return { valido: true, mensaje: "Cédula válida", valor: cedulaLimpia };
}

function validarTelefono(telefono) {
    if (!telefono || telefono.trim() === '') return { valido: false, mensaje: "El teléfono es requerido" };
    const telefonoLimpio = telefono.trim().replace(/[\s\-\(\)]/g, '');
    if (!/^\d+$/.test(telefonoLimpio)) return { valido: false, mensaje: "El teléfono solo debe contener números" };
    if (telefonoLimpio.length < 7) return { valido: false, mensaje: "El teléfono debe tener al menos 7 dígitos" };
    if (telefonoLimpio.length > 15) return { valido: false, mensaje: "El teléfono no debe tener más de 15 dígitos" };
    return { valido: true, mensaje: "Teléfono válido", valor: telefonoLimpio };
}

function validarNombre(nombre, campo = "Nombre") {
    if (!nombre || nombre.trim() === '') return { valido: false, mensaje: `${campo} es requerido` };
    const nombreLimpio = nombre.trim();
    if (nombreLimpio.length < 2) return { valido: false, mensaje: `${campo} debe tener al menos 2 caracteres` };
    if (nombreLimpio.length > 50) return { valido: false, mensaje: `${campo} no debe tener más de 50 caracteres` };
    if (!/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/.test(nombreLimpio)) return { valido: false, mensaje: `${campo} solo debe contener letras y espacios` };
    return { valido: true, mensaje: `${campo} válido`, valor: nombreLimpio };
}

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

function validarCampoRequerido(valor, nombre = "Campo") {
    if (!valor || valor.trim() === '') return { valido: false, mensaje: `${nombre} es requerido` };
    return { valido: true, mensaje: `${nombre} válido`, valor: valor.trim() };
}

function validarNumeroTarjeta(numero) {
    if (!numero || numero.trim() === '') return { valido: false, mensaje: "El número de tarjeta es requerido" };
    const numeroLimpio = numero.replace(/\s/g, '');
    if (!/^\d+$/.test(numeroLimpio)) return { valido: false, mensaje: "El número de tarjeta solo debe contener números" };
    if (numeroLimpio.length < 13 || numeroLimpio.length > 19) return { valido: false, mensaje: "El número de tarjeta debe tener entre 13 y 19 dígitos" };
    return { valido: true, mensaje: "Número de tarjeta válido", valor: numeroLimpio };
}

function validarCVV(cvv) {
    if (!cvv || cvv.trim() === '') return { valido: false, mensaje: "El CVV es requerido" };
    const cvvLimpio = cvv.trim();
    if (!/^\d+$/.test(cvvLimpio)) return { valido: false, mensaje: "El CVV solo debe contener números" };
    if (cvvLimpio.length < 3 || cvvLimpio.length > 4) return { valido: false, mensaje: "El CVV debe tener 3 o 4 dígitos" };
    return { valido: true, mensaje: "CVV válido", valor: cvvLimpio };
}

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

function validarCodigoPostal(codigo) {
    if (!codigo || codigo.trim() === '') return { valido: false, mensaje: "El código postal es requerido" };
    const codigoLimpio = codigo.trim();
    if (!/^\d+$/.test(codigoLimpio)) return { valido: false, mensaje: "El código postal solo debe contener números" };
    if (codigoLimpio.length !== 5) return { valido: false, mensaje: "El código postal debe tener exactamente 5 dígitos" };
    return { valido: true, mensaje: "Código postal válido", valor: codigoLimpio };
}

function validarNombreTitular(nombre) {
    if (!nombre || nombre.trim() === '') return { valido: false, mensaje: "El nombre del titular es requerido" };
    const nombreLimpio = nombre.trim();
    if (nombreLimpio.length < 3) return { valido: false, mensaje: "El nombre del titular debe tener al menos 3 caracteres" };
    if (nombreLimpio.length > 50) return { valido: false, mensaje: "El nombre del titular no debe tener más de 50 caracteres" };
    if (!/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/.test(nombreLimpio)) return { valido: false, mensaje: "El nombre del titular solo debe contener letras y espacios" };
    return { valido: true, mensaje: "Nombre válido", valor: nombreLimpio };
}

// ======================== ERRORES ========================
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

function mostrarErrorEnCampo(id, mensaje) {
    const campo = document.getElementById(id);
    if (campo) {
        marcarCampoError(campo, mensaje);
        campo.focus();
        campo.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ======================== CERRAR SESIÓN ========================
function showLogoutConfirm() {
    const alertOverlay = document.getElementById('alertOverlay');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    
    alertTitle.textContent = 'Cerrar sesión';
    alertMessage.textContent = '¿Estás seguro de que deseas cerrar sesión?';
    alertOverlay.classList.add('active');
}

function confirmLogout() {
    showToast({ title: "Cerrando sesión", message: "Por favor espera...", type: "info" });
    closeAlert();
    saveUserData();
    setTimeout(function() {
        window.location.href = APP_URL + '/auth/logout';
    }, 500);
}

// ======================== CARRITO EN PERFIL ========================
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
                return `
                    <div class="cart-item-card">
                        <div class="cart-item-badge">EN CARRITO</div>
                        <img src="${mainImg}" alt="${item.name}" class="cart-item-image" onerror="this.src='${APP_URL}/assets/imagenes/general/logos.png'">
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-category">${item.category || item.subcategory || 'Producto'}</div>
                            <div class="cart-item-price">COP $${(item.price || 0).toLocaleString()}</div>
                            <div class="cart-item-size">Talla: ${item.selectedSize || 'N/A'}</div>
                            <div class="cart-item-actions">
                                <div class="qty">
                                    <button onclick="updateQtyProfile(${index}, -1)" ${(item.quantity || 1) <= 1 ? 'disabled' : ''}>−</button>
                                    <span>${item.quantity || 1}</span>
                                    <button onclick="updateQtyProfile(${index}, 1)">+</button>
                                </div>
                                <button class="btn-remove-cart" onclick="removeFromCartProfile(${index})">ELIMINAR</button>
                            </div>
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

function updateQtyProfile(index, delta) {
    const newQty = (cart[index].quantity || 1) + delta;
    if (newQty < 1) return;
    cart[index].quantity = newQty;
    saveUserData();
    renderCartProfile();
}

function removeFromCartProfile(index) {
    cart.splice(index, 1);
    saveUserData();
    renderCartProfile();
    showToast({message: "Producto eliminado del carrito", type: "info"});
}

// ======================== PERFIL ========================
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

function saveProfile() {
    const nombre = document.getElementById('nombre').value;
    const apellido = document.getElementById('apellido').value;
    const cedula = document.getElementById('cedula').value;
    const telefono = document.getElementById('telefono').value;
    const fechaNacimiento = document.getElementById('fechaNacimiento').value;
    const genero = document.getElementById('genero').value;

    limpiarErroresCampos();
    let tieneErrores = false;

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

    profileData = {
        nombre: nombreValid.valor,
        apellido: apellidoValid.valor,
        cedula: cedulaValid.valor,
        telefono: telefonoValid.valor,
        fechaNacimiento: fechaValid.valor,
        genero: generoValid.valor
    };
    saveUserData();
    
    showToast({ title: "¡Cambios guardados!", message: "Tu información ha sido actualizada correctamente", type: "success" });
    return true;
}

function loadProfile() {
    if (profileData.nombre) document.getElementById('nombre').value = profileData.nombre;
    if (profileData.apellido) document.getElementById('apellido').value = profileData.apellido;
    if (profileData.cedula) document.getElementById('cedula').value = profileData.cedula;
    if (profileData.telefono) document.getElementById('telefono').value = profileData.telefono;
    if (profileData.fechaNacimiento) document.getElementById('fechaNacimiento').value = profileData.fechaNacimiento;
    if (profileData.genero) document.getElementById('genero').value = profileData.genero;
}

// ======================== DIRECCIONES ========================
function loadAddresses() { renderAddresses(); }

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

function showAddressForm() { 
    document.getElementById('addressFormContainer').style.display = 'block'; 
    document.getElementById('addressList').style.display = 'none'; 
    document.getElementById('emptyAddressState').style.display = 'none'; 
    limpiarErroresCampos();
}

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
            
            saveUserData();
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
    saveUserData();
    loadAddresses(); 
    cancelAddressForm(); 
    showToast({title: "¡Dirección agregada!", message: "Tu dirección ha sido guardada correctamente", type: "success"});
}

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

function setDefaultAddress(id) { 
    addresses.forEach(a => a.isDefault = a.id === id); 
    saveUserData();
    loadAddresses(); 
    showToast({title: "Dirección predeterminada", message: "La dirección ha sido establecida como predeterminada", type: "success"}); 
}

function showDeleteAddressAlert(id) { 
    pendingDeleteId = id; 
    pendingDeleteType = 'address'; 
    showAlert("¿Eliminar dirección?", "¿Estás seguro de que deseas eliminar esta dirección?"); 
}

function deleteAddressConfirmed(id) { 
    addresses = addresses.filter(a => a.id !== id); 
    if (addresses.length > 0 && !addresses.some(a => a.isDefault)) {
        addresses[0].isDefault = true;
    }
    saveUserData();
    loadAddresses(); 
    showToast({title: "Dirección eliminada", message: "La dirección ha sido eliminada correctamente", type: "success"}); 
}

// ======================== PEDIDOS ========================
function loadOrders() { renderOrders(); }

function renderOrders() { 
    const ordersList = document.getElementById('ordersList'); 
    const emptyState = document.getElementById('emptyOrdersState'); 
    if (orders.length === 0) { 
        if(emptyState) emptyState.style.display = 'block'; 
        if(ordersList) ordersList.style.display = 'none'; 
        return; 
    } 
    if(emptyState) emptyState.style.display = 'none'; 
    if(ordersList) ordersList.style.display = 'flex'; 
    ordersList.innerHTML = orders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-id">Pedido #${order.id || order.orderNumber}</div>
                    <div class="order-date">${new Date(order.date).toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                </div>
                <div class="order-status ${order.status === 'delivered' ? 'status-delivered' : 'status-processing'}">
                    ${order.status === 'delivered' ? 'Entregado' : 'En proceso'}
                </div>
            </div>
            <div class="order-details">
                <div class="order-detail-item">
                    <div class="detail-label">Total</div>
                    <div class="detail-value">COP $${order.total.toLocaleString()}</div>
                </div>
                <div class="order-detail-item">
                    <div class="detail-label">Productos</div>
                    <div class="detail-value">${order.items || order.products?.length || 0} artículos</div>
                </div>
            </div>
            <div class="order-products">
                ${order.products && order.products.length > 0 ? order.products.map(p => `
                    <div class="order-product-item">
                        <span>${p.nombre || p.name}</span>
                        <span>${p.cantidad || p.quantity || 1} x COP $${(p.precioUnitario || p.precio || 0).toLocaleString()}</span>
                    </div>
                `).join('') : ''}
            </div>
            <div class="order-actions">
                <button class="primary-btn" onclick="viewOrderDetails('${order.id || order.orderNumber}')">VER DETALLES</button>
            </div>
        </div>
    `).join(''); 
}

function viewOrderDetails(orderId) { showToast({title: "Detalles del pedido", message: `Mostrando detalles del pedido #${orderId}`, type: "info"}); }

// ======================== TARJETAS ========================
function loadCards() { renderCards(); }

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
    saveUserData();
    loadCards(); 
    cancelCardForm(); 
    showToast({title: "¡Tarjeta guardada!", message: "Tu método de pago ha sido registrado correctamente", type: "success"}); 
}

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

function showDeleteCardAlert(id) { 
    pendingDeleteId = id; 
    pendingDeleteType = 'card'; 
    showAlert("¿Eliminar tarjeta?", "¿Estás seguro de que deseas eliminar esta tarjeta?"); 
}

function deleteCardConfirmed(id) { 
    cards = cards.filter(c => c.id !== id); 
    if (cards.length > 0 && !cards.some(c => c.isDefault)) {
        cards[0].isDefault = true;
    }
    saveUserData();
    loadCards(); 
    showToast({title: "Tarjeta eliminada", message: "La tarjeta ha sido eliminada correctamente", type: "success"}); 
}

function setDefaultCard(id) { 
    cards.forEach(c => c.isDefault = c.id === id); 
    saveUserData();
    loadCards(); 
    showToast({title: "Tarjeta predeterminada", message: "La tarjeta ha sido establecida como predeterminada", type: "success"}); 
}

// ======================== FAVORITOS ========================
function loadFavorites() { renderFavorites(); }

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

function showDeleteFavoriteAlert(id) { 
    pendingDeleteId = id; 
    pendingDeleteType = 'favorite'; 
    showAlert("¿Eliminar de favoritos?", "¿Estás seguro de que deseas eliminar este producto de tus favoritos?"); 
}

function deleteFavoriteConfirmed(id) { 
    favorites = favorites.filter(f => f.id !== id);
    saveUserData();
    loadFavorites(); 
    showToast({title: "Eliminado de favoritos", message: "El producto ha sido eliminado de tus favoritos", type: "success"}); 
}

function addToCartFromFavorites(id) { 
    const fav = favorites.find(f => f.id === id); 
    if (!fav) return; 
    
    const existing = cart.find(item => item.id === fav.id && item.selectedSize === (fav.sizes?.[0] || 'M'));
    if (existing) {
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
    
    saveUserData();
    renderCartProfile(); 
    showToast({title: "¡Añadido al carrito!", message: `${fav.name} ha sido añadido a tu carrito`, type: "success"}); 
}

// ======================== ALERTA ========================
function showAlert(title, message) { 
    document.getElementById('alertTitle').textContent = title; 
    document.getElementById('alertMessage').textContent = message; 
    document.getElementById('alertOverlay').classList.add('active'); 
}

function closeAlert() { 
    document.getElementById('alertOverlay').classList.remove('active'); 
    pendingDeleteId = null; 
    pendingDeleteType = null; 
}

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
function refreshSecurity() { showToast({title: "Actualizado", message: "La información de seguridad se ha actualizado", type: "success"}); }
function definePassword() { showToast({title: "Definir contraseña", message: "Funcionalidad en desarrollo", type: "info"}); }
function recoverPassword() { showToast({title: "Recuperar contraseña", message: "Funcionalidad en desarrollo", type: "info"}); }
function viewSessions() { showToast({title: "Sesiones activas", message: "Actualmente tienes 1 sesión activa", type: "info"}); }
function closeAllSessions() { showToast({title: "Sesiones cerradas", message: "Todas las sesiones han sido cerradas", type: "success"}); }
function enableTwoFactor() { showToast({title: "Verificación en dos pasos", message: "Funcionalidad en desarrollo", type: "info"}); }

// ======================== MENÚ LATERAL ========================
function initSidebar() { 
    document.querySelectorAll('.menu-item[data-section]').forEach(item => { 
        item.addEventListener('click', function() { 
            const section = this.getAttribute('data-section'); 
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active')); 
            this.classList.add('active'); 
            document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active')); 
            const target = document.getElementById(section); 
            if (target) target.classList.add('active'); 
            else { 
                showToast({message: "Sección en desarrollo", type: "info"}); 
                document.getElementById('datosPersonales').classList.add('active'); 
                document.querySelector('.menu-item[data-section="datosPersonales"]').classList.add('active'); 
            } 
        }); 
    }); 
}

// ======================== INICIALIZACIÓN ========================
document.addEventListener('DOMContentLoaded', function() { 
    loadUserData();
    loadProfile();
    loadAddresses(); 
    loadOrders(); 
    loadCards(); 
    loadFavorites(); 
    initSidebar(); 
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    renderCartProfile();
});