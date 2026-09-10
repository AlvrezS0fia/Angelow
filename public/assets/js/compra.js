/**
 * ============================================================
 * ARCHIVO: compra.js
 * QUÉ HACE: Implementa el checkout de 3 pasos (datos personales,
 *            envío y pago), valida los campos en tiempo real,
 *            aplica cupones de descuento contra el backend,
 *            calcula totales y guarda el pedido en el servidor.
 * TIPO: HÍBRIDO (carrito local + APIs reales)
 * ENDPOINTS QUE CONSUME: POST {APP_URL}/api/cupones/validar,
 *            POST {APP_URL}/procesar-compra,
 *            POST {APP_URL}/api/carrito/vaciar
 * CLÁVES localStorage QUE USA: angelow_cart, cart,
 *            angelow_cart_guest, promoCode, angelow_orders,
 *            nuevoPedido (sessionStorage)
 * LIBRERÍAS EXTERNAS: Font Awesome (iconos)
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarCarrito();
    seleccionarOpcionesPorDefecto();
    actualizarAnioFooter();
    cargarDatosUsuario();
    inicializarValidaciones();
    
    const promoGuardado = localStorage.getItem('promoCode');
    if (promoGuardado) {
        document.getElementById('promoInput').value = promoGuardado;
        aplicarPromoGuardado();
    }
});

let carrito = [];
let descuentoAplicado = 0;
let codigoDescuento = "";
let costoEnvio = 0;
let datosUsuario = {};
let ultimoPedido = null;

// ======================== VALIDACIONES EN TIEMPO REAL ========================
// Asocia las validaciones blurear/escribir a los campos del paso 1 y 2
function inicializarValidaciones() {
    const camposPaso1 = ['nombre', 'apellidos', 'email', 'cedula', 'telefono'];
    camposPaso1.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('blur', function() {
                validarCampo(this);
            });
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validarCampo(this);
                }
            });
        }
    });
    
    const camposPaso2 = ['departamento', 'municipio', 'direccion', 'barrio'];
    camposPaso2.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('blur', function() {
                validarCampo(this);
            });
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validarCampo(this);
                }
            });
        }
    });
}

// Marca un campo como válido o inválido según tenga contenido
function validarCampo(input) {
    if (!input.value.trim()) {
        input.classList.add('error');
        input.classList.remove('success');
        return false;
    } else {
        input.classList.remove('error');
        input.classList.add('success');
        return true;
    }
}

// Verifica que el texto tenga formato de correo electrónico
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Valida que el teléfono tenga entre 7 y 15 dígitos
function validarTelefono(telefono) {
    const regex = /^[0-9]{7,15}$/;
    return regex.test(telefono);
}

// Valida que la cédula tenga entre 6 y 15 dígitos
function validarCedula(cedula) {
    const regex = /^[0-9]{6,15}$/;
    return regex.test(cedula);
}

// Devuelve la geolocalización del usuario (lat/lng) o null si no la permite
function obtenerUbicacionUsuario() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve(null);
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            () => resolve(null),
            { timeout: 10000, enableHighAccuracy: false, maximumAge: 300000 }
        );
    });
}

// Valida todos los campos del paso 1 (datos del cliente y términos)
function validarPaso1() {
    let valido = true;
    const nombre = document.getElementById('nombre');
    const apellidos = document.getElementById('apellidos');
    const email = document.getElementById('email');
    const cedula = document.getElementById('cedula');
    const telefono = document.getElementById('telefono');
    const terms = document.getElementById('acceptTerms');
    
    if (!nombre.value.trim()) { nombre.classList.add('error'); valido = false; } 
    else { nombre.classList.remove('error'); }
    
    if (!apellidos.value.trim()) { apellidos.classList.add('error'); valido = false; } 
    else { apellidos.classList.remove('error'); }
    
    if (!email.value.trim() || !validarEmail(email.value)) { email.classList.add('error'); valido = false; } 
    else { email.classList.remove('error'); }
    
    if (!cedula.value.trim() || !validarCedula(cedula.value)) { cedula.classList.add('error'); valido = false; } 
    else { cedula.classList.remove('error'); }
    
    if (!telefono.value.trim() || !validarTelefono(telefono.value)) { telefono.classList.add('error'); valido = false; } 
    else { telefono.classList.remove('error'); }
    
    if (!terms.checked) { terms.classList.add('error'); valido = false; } 
    else { terms.classList.remove('error'); }
    
    if (!valido) {
        showToast('Complete todos los campos correctamente', 'error');
    }
    
    return valido;
}

// Valida todos los campos del paso 2 (dirección de envío)
function validarPaso2() {
    let valido = true;
    const depto = document.getElementById('departamento');
    const city = document.getElementById('municipio');
    const dir = document.getElementById('direccion');
    const barrio = document.getElementById('barrio');
    
    if (!depto.value.trim()) { depto.classList.add('error'); valido = false; } 
    else { depto.classList.remove('error'); }
    
    if (!city.value.trim()) { city.classList.add('error'); valido = false; } 
    else { city.classList.remove('error'); }
    
    if (!dir.value.trim()) { dir.classList.add('error'); valido = false; } 
    else { dir.classList.remove('error'); }
    
    if (!barrio.value.trim()) { barrio.classList.add('error'); valido = false; } 
    else { barrio.classList.remove('error'); }
    
    if (!valido) {
        showToast('Complete todos los datos de envio', 'error');
    }
    
    return valido;
}

// Precarga los datos del usuario logueado en los campos del paso 1
function cargarDatosUsuario() {
    if (window.CURRENT_USER && window.CURRENT_USER.nombre) {
        datosUsuario = window.CURRENT_USER;
        document.getElementById('nombre').value = datosUsuario.nombre || '';
        document.getElementById('apellidos').value = datosUsuario.apellido || '';
        document.getElementById('email').value = datosUsuario.email || '';
        document.getElementById('cedula').value = datosUsuario.cedula || '';
        document.getElementById('telefono').value = datosUsuario.telefono || '';
    }
}

// Muestra una notificación toast temporal en la esquina de la pantalla
function showToast(mensaje, tipo = 'info') {
    const contenedor = document.getElementById('toastContainer');
    if (!contenedor) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${tipo}`;
    
    toast.innerHTML = `
        <span style="font-size:18px;">${tipo === 'success' ? '✓' : tipo === 'error' ? '✕' : tipo === 'warning' ? '⚠' : 'ℹ'}</span>
        <span style="flex:1;">${mensaje}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:inherit;">×</button>
    `;
    
    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentElement) toast.remove();
    }, 4000);
}

function calcularDescuento(codigo, subtotal) {
    // Esta función ya no se usa para validar cupones.
    // La validación ahora se hace contra el backend.
    return 0;
}

// ======================== CARGAR CARRITO ========================
// Lee el carrito desde las claves almacenadas (por prioridad) y lo
// carga en la variable global para renderizar el resumen
function cargarCarrito() {
    let cartData = null;
    
    const angelowCart = localStorage.getItem('angelow_cart');
    if (angelowCart) {
        try {
            cartData = JSON.parse(angelowCart);
        } catch(e) {
            console.error('Error parsing angelow_cart:', e);
        }
    }
    
    if (!cartData || cartData.length === 0) {
        const cart = localStorage.getItem('cart');
        if (cart) {
            try {
                cartData = JSON.parse(cart);
            } catch(e) {
                console.error('Error parsing cart:', e);
            }
        }
    }
    
    if (!cartData || cartData.length === 0) {
        const guestCart = localStorage.getItem('angelow_cart_guest');
        if (guestCart) {
            try {
                cartData = JSON.parse(guestCart);
            } catch(e) {
                console.error('Error parsing angelow_cart_guest:', e);
            }
        }
    }
    
    if (cartData && Array.isArray(cartData) && cartData.length > 0) {
        carrito = cartData;
    } else {
        carrito = [];
    }
    
    renderizarResumen();
}

// ======================== RENDERIZAR RESUMEN ========================
// Dibuja los productos del carrito en la vista de resumen de la compra
function renderizarResumen() {
    const contenedor = document.getElementById('summaryItems');
    if (!contenedor) return;
    
    if (!carrito || carrito.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-cart" style="text-align: center; padding: 40px 20px; color: #6b7280;">
                <p style="font-size: 18px; margin-bottom: 10px;">No hay productos en el carrito</p>
                <p style="font-size: 14px;">Agrega productos desde la tienda</p>
                <a href="${APP_URL || '/'}" style="display: inline-block; margin-top: 15px; padding: 10px 24px; background: #1e3a8a; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">Ir a la tienda</a>
            </div>
        `;
        actualizarTotales();
        return;
    }
    
    let html = '';
    carrito.forEach((item, index) => {
        const precio = item.price || item.precio || 0;
        const cantidad = item.quantity || item.cantidad || 1;
        const nombre = item.name || item.nombre || 'Producto';
        const imagen = item.imgs?.[0] || item.imagen || item.image || '';
        const talla = item.selectedSize || item.talla || 'Unica';
        
        let imgSrc = '';
        if (imagen) {
            if (imagen.startsWith('http://') || imagen.startsWith('https://')) {
                imgSrc = imagen;
            } else if (APP_URL) {
                imgSrc = APP_URL + '/' + imagen.replace(/^\.?\//, '');
            } else {
                imgSrc = '/' + imagen.replace(/^\.?\//, '');
            }
        }
        
        html += `
            <div class="summary-item" style="display: flex; gap: 16px; padding: 16px; border-bottom: 1px solid #e5e7eb; align-items: center;">
                ${imgSrc ? `<img src="${imgSrc}" alt="${nombre}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;" onerror="this.style.display='none'">` : '<div class="no-image" style="width: 70px; height: 70px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px;">Sin imagen</div>'}
                <div class="summary-item-info" style="flex: 1;">
                    <h4 style="margin: 0 0 4px 0; color: #1f2937; font-size: 16px; font-weight: 600;">${nombre}</h4>
                    <p style="margin: 0 0 4px 0; color: #6b7280; font-size: 14px;">Cantidad: ${cantidad} | Talla: ${talla}</p>
                    <div class="summary-item-price" style="color: #1e3a8a; font-weight: 700; font-size: 16px;">COP $${(precio * cantidad).toLocaleString()}</div>
                </div>
            </div>
        `;
    });
    
    contenedor.innerHTML = html;
    actualizarTotales();
}

// Calcula subtotal, descuento, envío y total; actualiza la interfaz
function actualizarTotales() {
    let subtotal = carrito.reduce((sum, p) => sum + (p.price || p.precio || 0) * (p.quantity || p.cantidad || 1), 0);
    let total = subtotal - descuentoAplicado + costoEnvio;
    if (total < 0) total = 0;
    
    const subtotalElem = document.getElementById('summarySubtotal');
    const shippingElem = document.getElementById('summaryShipping');
    const totalElem = document.getElementById('summaryTotal');
    
    if (subtotalElem) subtotalElem.innerText = `COP $${subtotal.toLocaleString()}`;
    if (shippingElem) shippingElem.innerText = costoEnvio === 0 ? "Gratis" : `COP $${costoEnvio.toLocaleString()}`;
    if (totalElem) totalElem.innerText = `COP $${total.toLocaleString()}`;
    
    const descRow = document.getElementById('discountRow');
    if (descuentoAplicado > 0) {
        if (descRow) descRow.style.display = 'flex';
        const discountCode = document.getElementById('discountCode');
        const discountAmount = document.getElementById('summaryDiscount');
        if (discountCode) discountCode.innerText = codigoDescuento;
        if (discountAmount) discountAmount.innerText = `- COP $${descuentoAplicado.toLocaleString()}`;
    } else {
        if (descRow) descRow.style.display = 'none';
    }
}

// ======================== CUPONES ========================
// Valida el cupón contra el backend y, si es válido, aplica el descuento
// y guarda el código en localStorage para reutilizarlo en el próximo paso
window.applyPromo = async function() {
    const input = document.getElementById('promoInput');
    if (!input) return;
    const code = input.value.trim().toUpperCase();
    if (!code) {
        showToast('Ingresa un codigo', 'warning');
        return;
    }

    let subtotal = carrito.reduce((sum, p) => sum + (p.price || p.precio || 0) * (p.quantity || p.cantidad || 1), 0);

    try {
        const res = await fetch(`${APP_URL}/api/cupones/validar`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo: code, subtotal: subtotal })
        });
        const result = await res.json();

        if (result.success && result.descuento > 0) {
            descuentoAplicado = result.descuento;
            codigoDescuento = code;
            localStorage.setItem('promoCode', code);
            showToast(`Cupon ${code} aplicado: -$${result.descuento.toLocaleString()}`, 'success');
        } else {
            showToast(result.message || 'Codigo invalido', 'error');
            descuentoAplicado = 0;
            codigoDescuento = "";
            localStorage.removeItem('promoCode');
        }
    } catch (e) {
        showToast('Error al validar el cupon', 'error');
        descuentoAplicado = 0;
        codigoDescuento = "";
        localStorage.removeItem('promoCode');
    }
    actualizarTotales();
};

// Reaplica automáticamente un cupón guardado al cargar la página
async function aplicarPromoGuardado() {
    const input = document.getElementById('promoInput');
    if (input && input.value) {
        const code = input.value.trim().toUpperCase();
        let subtotal = carrito.reduce((sum, p) => sum + (p.price || p.precio || 0) * (p.quantity || p.cantidad || 1), 0);
        try {
            const res = await fetch(`${APP_URL}/api/cupones/validar`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo: code, subtotal: subtotal })
            });
            const result = await res.json();
            if (result.success && result.descuento > 0) {
                descuentoAplicado = result.descuento;
                codigoDescuento = code;
                actualizarTotales();
            }
        } catch (e) {
            // Silently fail for saved promo
        }
    }
}

// ======================== ENVÍO Y PAGO ========================
// Selecciona una opción de envío (normal o express) y fija su costo
window.selectShipping = function(element) {
    document.querySelectorAll('.shipping-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    const tipo = element.getAttribute('data-shipping') || element.querySelector('input').value;
    costoEnvio = (tipo === 'express') ? 15000 : 0;
    actualizarTotales();
};

// Selecciona un método de pago entre las opciones mostradas
window.selectPayment = function(element) {
    document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
};

// Preselecciona opciones de envío y pago si no hay una elegida aún
function seleccionarOpcionesPorDefecto() {
    const normal = document.querySelector('.shipping-option[data-shipping="normal"]');
    if (normal) window.selectShipping(normal);
    const pse = document.querySelector('.payment-option[data-payment="pse"]');
    if (pse) window.selectPayment(pse);
}

// ======================== PASOS ========================
// Cambia entre los pasos del checkout validando el paso anterior,
// actualizando la barra de progreso y los estilos de los pasos
window.goToStep = function(step) {
    if (step === 2) {
        if (!validarPaso1()) return;
    }
    
    if (step === 3) {
        if (!validarPaso2()) return;
    }

    document.querySelectorAll('.step-content').forEach(c => c.classList.remove('active'));
    const targetContent = document.getElementById(`content${step}`);
    if (targetContent) targetContent.classList.add('active');

    for (let i = 1; i <= 3; i++) {
        const stepDiv = document.getElementById(`step${i}`);
        if (stepDiv) {
            if (i <= step) {
                stepDiv.classList.add('completed');
                if (i === step) stepDiv.classList.add('active');
                else stepDiv.classList.remove('active');
            } else {
                stepDiv.classList.remove('active', 'completed');
            }
        }
    }
    
    const line = document.getElementById('progressLine');
    if (line) {
        const width = ((step - 1) / 2) * 100;
        line.style.width = `${width}%`;
    }

    if (step === 3) actualizarTotales();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ======================== GENERAR NUMERO DE PEDIDO (temporal solo para UI local) ========================
// Número de pedido temporal usado solo por la UI local
// (el servidor asigna el número definitivo)
function generarNumeroPedidoLocal() {
    return 'PENDIENTE';
}

// ======================== GUARDAR PEDIDO ========================
// Envía el pedido completo al backend y, si es exitoso, lo registra
// en localStorage/sessionStorage y devuelve el pedido creado
async function guardarPedido(pedidoInfo) {
    const usuarioId = window.CURRENT_USER?.id || window.APP_USER_ID || 0;

    let ubicacion = null;
    try {
        ubicacion = await obtenerUbicacionUsuario();
    } catch (e) {
        ubicacion = null;
    }

    const pedidoData = {
        usuario_id: usuarioId,
        numero_pedido: pedidoInfo.numero,
        nombre_cliente: pedidoInfo.cliente.nombre,
        email_cliente: pedidoInfo.cliente.email,
        telefono_cliente: pedidoInfo.cliente.telefono,
        cedula_cliente: pedidoInfo.cliente.cedula,
        direccion_envio: pedidoInfo.envio.direccion,
        direccion_complementaria: pedidoInfo.envio.direccionComplementaria || null,
        barrio: pedidoInfo.envio.barrio || '',
        ciudad: pedidoInfo.envio.ciudad || '',
        departamento: pedidoInfo.envio.departamento || '',
        destinatario: pedidoInfo.envio.destinatario,
        informacion_adicional: pedidoInfo.infoAdicional,
        metodo_pago: pedidoInfo.pago.metodo === 'Mercado Pago' ? 'mercadopago' : 'pse',
        metodo_envio: pedidoInfo.envio.metodo.includes('Express') ? 'express' : 'normal',
        costo_envio: pedidoInfo.envio.costo,
        subtotal: pedidoInfo.subtotal,
        descuento: pedidoInfo.descuento,
        total: pedidoInfo.total,
        latitud_destino: ubicacion?.lat || null,
        longitud_destino: ubicacion?.lng || null,
        productos: pedidoInfo.productos.map(p => ({
            producto_id: p.id,
            nombre: p.nombre,
            cantidad: p.cantidad,
            precioUnitario: p.precioUnitario,
            talla: p.talla,
            color: p.color || 'N/A',
            imagen: p.imagen || ''
        }))
    };

    try {
        const res = await fetch(`${APP_URL}/procesar-compra`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(pedidoData)
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
            const errorMsg = result.error || result.message || 'Error desconocido del servidor';
            console.error('Error del servidor:', errorMsg);
            showToast(`Error al procesar pedido: ${errorMsg}`, 'error');
            return { success: false, error: errorMsg };
        }

        const nuevoPedido = {
            id: result.numero_pedido || pedidoInfo.numero,
            pedido_id: result.pedido_id,
            numero_pedido: result.numero_pedido,
            orderNumber: result.numero_pedido,
            date: new Date().toISOString(),
            total: pedidoInfo.total,
            status: 'processing',
            items: pedidoInfo.productos.length,
            products: pedidoInfo.productos.map(p => ({
                nombre: p.nombre,
                cantidad: p.cantidad,
                precioUnitario: p.precioUnitario,
                talla: p.talla,
                color: p.color || 'N/A'
            })),
            cliente: pedidoInfo.cliente,
            envio: pedidoInfo.envio,
            pago: pedidoInfo.pago,
            subtotal: pedidoInfo.subtotal,
            descuento: pedidoInfo.descuento,
            codigoDescuento: pedidoInfo.codigoDescuento,
            infoAdicional: pedidoInfo.infoAdicional,
            fechaFormateada: pedidoInfo.fecha
        };

        let orders = JSON.parse(localStorage.getItem('angelow_orders')) || [];
        orders.unshift(nuevoPedido);
        localStorage.setItem('angelow_orders', JSON.stringify(orders));
        sessionStorage.setItem('nuevoPedido', JSON.stringify(nuevoPedido));
        ultimoPedido = pedidoInfo;

        return { success: true, pedido: nuevoPedido };

    } catch (e) {
        console.error('Error de conexion al guardar pedido:', e);
        showToast('Error de conexion. Verifica tu internet e intenta de nuevo.', 'error');
        return { success: false, error: 'Sin conexion' };
    }
}

// ======================== COMPLETAR COMPRA ========================
// Valida todos los pasos, arma el pedido, lo guarda en el servidor y
// limpia el carrito; al final muestra la confirmación del pedido
window.completePurchase = async function() {
    if (!document.getElementById('acceptTerms')?.checked) {
        showToast('Debes aceptar los terminos y condiciones', 'error');
        document.getElementById('acceptTerms').classList.add('error');
        return;
    }
    
    if (!validarPaso1()) {
        window.goToStep(1);
        return;
    }
    
    if (!validarPaso2()) {
        window.goToStep(2);
        return;
    }

    if (carrito.length === 0) {
        showToast('El carrito esta vacio', 'error');
        return;
    }

    showToast('Procesando pedido...', 'info');

    let subtotal = 0;
    const productos = carrito.map(item => {
        const precio = item.price || item.precio || 0;
        const cant = item.quantity || item.cantidad || 1;
        subtotal += precio * cant;
        return {
            id: item.id || item.producto_id,
            nombre: item.name || item.nombre || 'Producto',
            precioUnitario: precio,
            cantidad: cant,
            talla: item.selectedSize || item.talla || 'Unica',
            color: item.color || 'N/A',
            imagen: item.imgs?.[0] || item.imagen || item.image || ''
        };
    });
    
    const metodoEnvio = document.querySelector('input[name="shipping"]:checked')?.value || 'normal';
    const metodoPago = document.querySelector('input[name="payment"]:checked')?.value || 'mercadopago';
    const infoAdicional = document.getElementById('infoAdicional').value || '';
    const destinatario = document.getElementById('destinatario').value || `${document.getElementById('nombre').value} ${document.getElementById('apellidos').value}`;
    const total = subtotal - descuentoAplicado + costoEnvio;

    const numeroPedido = generarNumeroPedidoLocal();
    const fechaActual = new Date().toLocaleDateString('es-CO', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const pedidoInfo = {
        numero: numeroPedido,
        fecha: fechaActual,
        cliente: {
            nombre: `${document.getElementById('nombre').value} ${document.getElementById('apellidos').value}`,
            cedula: document.getElementById('cedula').value,
            telefono: document.getElementById('telefono').value,
            email: document.getElementById('email').value
        },
        envio: {
            direccion: document.getElementById('direccion').value,
            direccionComplementaria: document.getElementById('direccionComplementaria')?.value || '',
            barrio: document.getElementById('barrio').value,
            ciudad: document.getElementById('municipio').value,
            departamento: document.getElementById('departamento').value,
            destinatario: destinatario,
            metodo: metodoEnvio === 'express' ? 'Envio Express - 1 dia habil' : 'Envio Normal - 2-5 dias',
            costo: costoEnvio
        },
        pago: {
            metodo: metodoPago === 'mercadopago' ? 'Mercado Pago' : 'PSE / Boton de Pago'
        },
        productos: productos,
        subtotal: subtotal,
        descuento: descuentoAplicado,
        codigoDescuento: codigoDescuento || 'N/A',
        total: total,
        infoAdicional: infoAdicional
    };

    const resultado = await guardarPedido(pedidoInfo);

    if (!resultado.success) {
        showToast('No se pudo completar la compra. Intenta de nuevo.', 'error');
        return;
    }

    const nuevoPedido = resultado.pedido;
    sessionStorage.setItem('nuevoPedido', JSON.stringify(nuevoPedido));
    pedidoInfo.numero = nuevoPedido.numero_pedido || pedidoInfo.numero;
    pedidoInfo.pedido_id = nuevoPedido.pedido_id;

    carrito = [];
    localStorage.removeItem('angelow_cart');
    localStorage.removeItem('cart');
    localStorage.removeItem('angelow_cart_guest');
    localStorage.removeItem('promoCode');
    descuentoAplicado = 0;
    codigoDescuento = "";

    try {
      await fetch(`${APP_URL}/api/carrito/vaciar`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
      });
    } catch (e) {
      console.error('Error al vaciar carrito en BD:', e);
    }

    mostrarConfirmacionPedido(pedidoInfo);
    showToast(`Compra completada. Pedido #${pedidoInfo.numero}`, 'success');
};

// ======================== MOSTRAR CONFIRMACION DE PEDIDO ========================
// Sustituye el contenido del contenedor por la vista de confirmación
// del pedido con los datos del cliente, envío, productos y totales
function mostrarConfirmacionPedido(data) {
    document.querySelectorAll('.step-content').forEach(c => c.classList.remove('active'));
    const progress = document.querySelector('.progress-container');
    if (progress) progress.style.display = 'none';
    
    const container = document.querySelector('.checkout-container');
    if (!container) return;
    
    const logoUrl = APP_URL + '/assets/imagenes/general/logos.png';
    
    const confirmacionHTML = `
        <div id="pedidoContainer" style="max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 20px; box-shadow: 0 20px 60px rgba(30, 58, 138, 0.15); overflow: hidden; padding: 50px; border: 1px solid #e8edf5;">
            
            <!-- HEADER -->
            <div style="text-align: center; border-bottom: 3px solid #1e3a8a; padding-bottom: 25px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <img src="${logoUrl}" alt="ANGELOW" style="height: 60px; width: auto;" onerror="this.style.display='none'">
                    <div>
                        <h1 style="color: #1e3a8a; font-size: 32px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">ANGELOW</h1>
                        <span style="color: #4a6fa5; font-size: 13px; letter-spacing: 3px; text-transform: uppercase; font-weight: 500;">Moda Infantil · Calidad y Estilo</span>
                    </div>
                </div>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <span style="background: #10b981; color: white; font-size: 14px; font-weight: 700; padding: 6px 25px; border-radius: 30px;">PEDIDO CONFIRMADO</span>
                </div>
                <div style="margin-top: 12px; color: #4a6fa5; font-size: 15px; font-weight: 600;">N° ${data.numero}</div>
                <div style="color: #6b7280; font-size: 14px;">${data.fecha}</div>
            </div>
            
            <!-- MENSAJE DE GRACIAS -->
            <div style="text-align: center; background: linear-gradient(135deg, #f0f4fa 0%, #e8edf5 100%); border-radius: 12px; padding: 14px; margin-bottom: 28px;">
                <span style="color: #1e3a8a; font-size: 16px; font-weight: 600;">¡Gracias por tu compra, ${data.cliente.nombre}!</span>
            </div>
            
            <!-- DATOS CLIENTE Y ENVÍO -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 28px; background: #f8fafc; padding: 22px 28px; border-radius: 14px; border: 1px solid #e8edf5;">
                <div>
                    <h3 style="color: #1e3a8a; font-size: 13px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px;">Datos del Cliente</h3>
                    <p style="margin: 5px 0; color: #1f2937; font-weight: 700; font-size: 15px;">${data.cliente.nombre}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">CC: ${data.cliente.cedula}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${data.cliente.telefono}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${data.cliente.email}</p>
                </div>
                <div>
                    <h3 style="color: #1e3a8a; font-size: 13px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px;">Datos de Envio</h3>
                    <p style="margin: 5px 0; color: #1f2937; font-weight: 700; font-size: 15px;">${data.envio.destinatario}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${data.envio.direccion}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 14px;">${data.envio.metodo}</p>
                    <p style="margin: 4px 0; color: #4b5563; font-size: 14px; font-weight: 600;">${data.envio.costo > 0 ? `COP $${data.envio.costo.toLocaleString()}` : 'Envio Gratis'}</p>
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
                        ${data.productos.map((p, index) => `
                            <tr style="border-bottom: 1px solid #e8edf5; ${index % 2 === 0 ? 'background: #fafbfc;' : 'background: #ffffff;'}">
                                <td style="padding: 14px 18px; color: #1f2937; font-weight: 600;">${p.nombre}</td>
                                <td style="padding: 14px 18px; text-align: center; color: #4b5563;">${p.talla}</td>
                                <td style="padding: 14px 18px; text-align: center; color: #4b5563;">${p.cantidad}</td>
                                <td style="padding: 14px 18px; text-align: right; color: #4b5563;">COP $${p.precioUnitario.toLocaleString()}</td>
                                <td style="padding: 14px 18px; text-align: right; color: #1e3a8a; font-weight: 700;">COP $${(p.precioUnitario * p.cantidad).toLocaleString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <!-- TOTALES -->
            <div style="display: flex; justify-content: flex-end; background: #f8fafc; border-radius: 14px; padding: 24px 30px; margin-bottom: 25px; border: 1px solid #e8edf5;">
                <div style="width: 320px;">
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #4b5563; font-size: 15px;">
                        <span>Subtotal</span>
                        <span>COP $${data.subtotal.toLocaleString()}</span>
                    </div>
                    ${data.descuento > 0 ? `
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #10b981; font-size: 15px; font-weight: 600;">
                            <span>Descuento (${data.codigoDescuento})</span>
                            <span>- COP $${data.descuento.toLocaleString()}</span>
                        </div>
                    ` : ''}
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #4b5563; font-size: 15px;">
                        <span>Envio</span>
                        <span>${data.envio.costo > 0 ? `COP $${data.envio.costo.toLocaleString()}` : 'Gratis'}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 14px 0 6px 0; border-top: 3px solid #1e3a8a; margin-top: 6px;">
                        <span style="color: #1e3a8a; font-size: 20px; font-weight: 800;">TOTAL</span>
                        <span style="color: #1e3a8a; font-size: 24px; font-weight: 900;">COP $${data.total.toLocaleString()}</span>
                    </div>
                    <div style="text-align: right; margin-top: 8px; color: #6b7280; font-size: 13px; font-weight: 500;">${data.pago.metodo}</div>
                    ${data.infoAdicional ? `<div style="text-align: right; margin-top: 4px; color: #6b7280; font-size: 13px;">${data.infoAdicional}</div>` : ''}
                </div>
            </div>
            
            <!-- FOOTER -->
            <div style="border-top: 2px solid #e8edf5; margin-top: 20px; padding-top: 20px; text-align: center; color: #6b7280; font-size: 13px;">
                <p style="margin: 0; color: #1e3a8a; font-size: 16px; font-weight: 600;">Gracias por elegir ANGELOW</p>
                <p style="margin: 5px 0 0 0; color: #4a6fa5;">Moda Infantil - Calidad y Estilo para tus pequeños</p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">info@angelow.com | +57 3135951664 | Medellin, Colombia</p>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #b0b8c8;">Pedido N° ${data.numero} | ${data.fecha}</p>
            </div>
            
            <!-- BOTONES DE ACCIÓN -->
            <div style="display: flex; gap: 14px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
                <button onclick="window.location.href='${APP_URL || '/'}/perfil'" style="background: #1e3a8a; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(30, 58, 138, 0.35); transition: all 0.3s ease;">
                    Ver Mis Pedidos
                </button>
                <button onclick="window.location.href='${APP_URL || '/'}'" style="background: #10b981; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35); transition: all 0.3s ease;">
                    Volver a la Tienda
                </button>
            </div>
        </div>
    `;
    
    container.innerHTML = confirmacionHTML;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    ultimoPedido = data;
}

// Actualiza el año actual en el footer
function actualizarAnioFooter() {
    const yearSpan = document.getElementById('currentYear');
    if (yearSpan) yearSpan.textContent = new Date().getFullYear();
}