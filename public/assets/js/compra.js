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
let ultimaFactura = null;

// ======================== VALIDACIONES EN TIEMPO REAL ========================
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

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validarTelefono(telefono) {
    const regex = /^[0-9]{7,15}$/;
    return regex.test(telefono);
}

function validarCedula(cedula) {
    const regex = /^[0-9]{6,15}$/;
    return regex.test(cedula);
}

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
    const tabla = {
        'BIENVENIDA10': subtotal * 0.10,
        'ANGELOW20': subtotal * 0.20,
        'ANGELOW25': subtotal * 0.25,
        'DESCUENTO5': subtotal * 0.05,
        'ANGELOW50': subtotal * 0.50
    };
    return tabla[codigo] || 0;
}

// ======================== CARGAR CARRITO ========================
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
function renderizarResumen() {
    const contenedor = document.getElementById('summaryItems');
    if (!contenedor) return;
    
    if (!carrito || carrito.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-cart" style="text-align: center; padding: 40px 20px; color: #6b7280;">
                <p style="font-size: 18px; margin-bottom: 10px;">No hay productos en el carrito</p>
                <p style="font-size: 14px;">Agrega productos desde la tienda</p>
                <a href="${window.APP_URL || '/'}" style="display: inline-block; margin-top: 15px; padding: 10px 24px; background: #1e3a8a; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">Ir a la tienda</a>
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
            } else if (window.APP_URL) {
                imgSrc = window.APP_URL + '/' + imagen.replace(/^\.?\//, '');
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
window.applyPromo = function() {
    const input = document.getElementById('promoInput');
    if (!input) return;
    const code = input.value.trim().toUpperCase();
    if (!code) {
        showToast('Ingresa un codigo', 'warning');
        return;
    }
    let subtotal = carrito.reduce((sum, p) => sum + (p.price || p.precio || 0) * (p.quantity || p.cantidad || 1), 0);
    const desc = calcularDescuento(code, subtotal);
    if (desc > 0) {
        descuentoAplicado = desc;
        codigoDescuento = code;
        localStorage.setItem('promoCode', code);
        showToast(`Cupon ${code} aplicado: -$${desc.toLocaleString()}`, 'success');
    } else {
        showToast('Codigo invalido', 'error');
        descuentoAplicado = 0;
        codigoDescuento = "";
        localStorage.removeItem('promoCode');
    }
    actualizarTotales();
};

function aplicarPromoGuardado() {
    const input = document.getElementById('promoInput');
    if (input && input.value) {
        const code = input.value.trim().toUpperCase();
        let subtotal = carrito.reduce((sum, p) => sum + (p.price || p.precio || 0) * (p.quantity || p.cantidad || 1), 0);
        const desc = calcularDescuento(code, subtotal);
        if (desc > 0) {
            descuentoAplicado = desc;
            codigoDescuento = code;
            actualizarTotales();
        }
    }
}

// ======================== ENVÍO Y PAGO ========================
window.selectShipping = function(element) {
    document.querySelectorAll('.shipping-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    const tipo = element.getAttribute('data-shipping') || element.querySelector('input').value;
    costoEnvio = (tipo === 'express') ? 15000 : 0;
    actualizarTotales();
};

window.selectPayment = function(element) {
    document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
};

function seleccionarOpcionesPorDefecto() {
    const normal = document.querySelector('.shipping-option[data-shipping="normal"]');
    if (normal) window.selectShipping(normal);
    const pse = document.querySelector('.payment-option[data-payment="pse"]');
    if (pse) window.selectPayment(pse);
}

// ======================== PASOS ========================
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

// ======================== GENERAR NUMERO DE FACTURA ========================
function generarNumeroFactura() {
    const anio = new Date().getFullYear();
    const mes = String(new Date().getMonth() + 1).padStart(2, '0');
    const dia = String(new Date().getDate()).padStart(2, '0');
    const aleatorio = Math.floor(Math.random() * 9000 + 1000);
    return `A-${anio}${mes}${dia}-${aleatorio}`;
}

// ======================== GUARDAR PEDIDO ========================
function guardarPedido(facturaData) {
    let orders = JSON.parse(localStorage.getItem('angelow_orders')) || [];
    
    const nuevoPedido = {
        id: facturaData.numero,
        orderNumber: facturaData.numero,
        date: new Date().toISOString(),
        total: facturaData.total,
        status: 'processing',
        items: facturaData.productos.length,
        products: facturaData.productos.map(p => ({
            nombre: p.nombre,
            cantidad: p.cantidad,
            precioUnitario: p.precioUnitario,
            talla: p.talla,
            color: p.color || 'N/A'
        })),
        cliente: facturaData.cliente,
        envio: facturaData.envio,
        pago: facturaData.pago,
        subtotal: facturaData.subtotal,
        descuento: facturaData.descuento,
        codigoDescuento: facturaData.codigoDescuento,
        infoAdicional: facturaData.infoAdicional,
        fechaFormateada: facturaData.fecha
    };
    
    orders.unshift(nuevoPedido);
    localStorage.setItem('angelow_orders', JSON.stringify(orders));
    sessionStorage.setItem('ultimaFactura', JSON.stringify(facturaData));
    sessionStorage.setItem('nuevoPedido', JSON.stringify(nuevoPedido));
    ultimaFactura = facturaData;
    
    return nuevoPedido;
}

// ======================== COMPLETAR COMPRA ========================
window.completePurchase = function() {
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

    showToast('Generando factura...', 'info');

    let subtotal = 0;
    const productos = carrito.map(item => {
        const precio = item.price || item.precio || 0;
        const cant = item.quantity || item.cantidad || 1;
        subtotal += precio * cant;
        return {
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

    const numeroFactura = generarNumeroFactura();
    const fechaActual = new Date().toLocaleDateString('es-CO', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const facturaData = {
        numero: numeroFactura,
        fecha: fechaActual,
        cliente: {
            nombre: `${document.getElementById('nombre').value} ${document.getElementById('apellidos').value}`,
            cedula: document.getElementById('cedula').value,
            telefono: document.getElementById('telefono').value,
            email: document.getElementById('email').value
        },
        envio: {
            direccion: `${document.getElementById('direccion').value}, ${document.getElementById('barrio').value}, ${document.getElementById('municipio').value}, ${document.getElementById('departamento').value}`,
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
    
    guardarPedido(facturaData);
    sessionStorage.setItem('facturaData', JSON.stringify(facturaData));

    carrito = [];
    localStorage.removeItem('angelow_cart');
    localStorage.removeItem('cart');
    localStorage.removeItem('angelow_cart_guest');
    localStorage.removeItem('promoCode');
    descuentoAplicado = 0;
    codigoDescuento = "";

    mostrarFactura(facturaData);
    showToast(`Compra completada. Pedido #${numeroFactura}`, 'success');
};

// ======================== MOSTRAR FACTURA ========================
function mostrarFactura(data) {
    document.querySelectorAll('.step-content').forEach(c => c.classList.remove('active'));
    const progress = document.querySelector('.progress-container');
    if (progress) progress.style.display = 'none';
    
    const container = document.querySelector('.checkout-container');
    if (!container) return;
    
    const logoUrl = window.APP_URL + '/assets/imagenes/general/logos.png';
    
    const facturaHTML = `
        <div id="facturaContainer" style="max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 20px; box-shadow: 0 20px 60px rgba(30, 58, 138, 0.15); overflow: hidden; padding: 50px; border: 1px solid #e8edf5;">
            
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
                    <span style="background: #1e3a8a; color: white; font-size: 14px; font-weight: 700; letter-spacing: 2px; padding: 6px 30px; border-radius: 30px;">FACTURA</span>
                    <span style="background: #10b981; color: white; font-size: 14px; font-weight: 700; padding: 6px 25px; border-radius: 30px;">PAGADA</span>
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
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #b0b8c8;">Factura N° ${data.numero} | ${data.fecha} | Este documento es un comprobante de compra valido</p>
            </div>
            
            <!-- BOTONES DE ACCIÓN -->
            <div style="display: flex; gap: 14px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
                <button onclick="generarPDFFactura()" style="background: linear-gradient(135deg, #1e3a8a 0%, #2a4f9e 100%); color: white; border: none; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(30, 58, 138, 0.35); transition: all 0.3s ease; display: flex; align-items: center; gap: 10px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                    Descargar PDF
                </button>
                <button onclick="window.location.href='${window.APP_URL || '/'}/perfil'" style="background: #e8edf5; color: #1e3a8a; border: 2px solid #1e3a8a; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                    Ver Mis Pedidos
                </button>
                <button onclick="window.location.href='${window.APP_URL || '/'}'" style="background: #10b981; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35); transition: all 0.3s ease;">
                    Volver a la Tienda
                </button>
            </div>
        </div>
    `;
    
    container.innerHTML = facturaHTML;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    ultimaFactura = data;
}

// ======================== GENERAR PDF ========================
function generarPDFFactura() {
    const facturaData = sessionStorage.getItem('facturaData');
    if (facturaData) {
        const data = JSON.parse(facturaData);
        generarPDF(data);
    } else if (ultimaFactura) {
        generarPDF(ultimaFactura);
    } else {
        showToast('No hay datos de factura para generar PDF', 'error');
    }
}

function generarPDF(data) {
    showToast('Generando PDF...', 'info');
    
    if (typeof html2pdf === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.onload = function() {
            generarPDFConBiblioteca(data);
        };
        script.onerror = function() {
            showToast('Error al cargar la biblioteca PDF', 'error');
        };
        document.head.appendChild(script);
    } else {
        generarPDFConBiblioteca(data);
    }
}

function generarPDFConBiblioteca(data) {
    const element = document.getElementById('facturaContainer');
    if (!element) {
        showToast('No se encontró la factura para generar PDF', 'error');
        return;
    }
    
    const opt = {
        margin: [10, 10, 10, 10],
        filename: `Factura_ANGELOW_${data.numero}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            useCORS: true, 
            logging: false,
            width: 850,
            letterRendering: true
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait' 
        },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };
    
    html2pdf()
        .set(opt)
        .from(element)
        .save()
        .then(() => {
            showToast('PDF generado correctamente', 'success');
        })
        .catch(err => {
            console.error('Error al generar PDF:', err);
            showToast('Error al generar PDF. Intenta con otra herramienta.', 'error');
            window.print();
        });
}

function actualizarAnioFooter() {
    const yearSpan = document.getElementById('currentYear');
    if (yearSpan) yearSpan.textContent = new Date().getFullYear();
}