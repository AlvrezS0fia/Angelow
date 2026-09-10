/**
 * ============================================================
 * ARCHIVO: seguimiento.js
 * QUÉ HACE: Página de seguimiento de pedido con mapa interactivo
 *            (Leaflet). Simula el seguimiento de una entrega:
 *            geocodifica el destino con Nominatim, traza la ruta,
 *            mueve un marcador de repartidor a lo largo de ella y
 *            actualiza el progreso en pasos y porcentaje.
 * TIPO: DEMO (geocodificación real con Nominatim, resto simulado)
 * ENDPOINTS QUE CONSUME: https://nominatim.openstreetmap.org/search
 *            (servicio externo de geocodificación)
 * CLÁVES localStorage QUE USA: angelow_cart, angelow_favorites,
 *            angelow_user, destinoEntrega, angelow_ruta_guardada
 * LIBRERÍAS EXTERNAS: Leaflet, Leaflet.Routing.Machine,
 *            Leaflet.polylineDecorator, Font Awesome
 * ============================================================
 */

//  VARIABLES GLOBALES DE LA TIENDA  
const products = [
  { id: 1, name: "Conjunto Deportivo", category: "Niños", subcategory: "Edición Especial", price: 899900, imgs: ["/assets/imagenes/ninos/Frente Conjunto Deportivo.png"], stock: 15 },
  { id: 2, name: "Conjunto Size", category: "Niños", subcategory: "Popular", price: 899900, imgs: ["/assets/imagenes/ninos/Frente Conjunto Size.png"], stock: 8 }
];

let cart = JSON.parse(localStorage.getItem("angelow_cart")) || [];
let favorites = JSON.parse(localStorage.getItem("angelow_favorites")) || [];
let currentUser = JSON.parse(localStorage.getItem("angelow_user")) || null;

const saveCart = () => localStorage.setItem("angelow_cart", JSON.stringify(cart));
const saveFavorites = () => localStorage.setItem("angelow_favorites", JSON.stringify(favorites));

//  FUNCIONES DE TOAST 
// Muestra una notificación temporal animada en la esquina superior
function showToast({title, message, type = "info", duration = 4000}) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:100px;right:20px;z-index:3000;display:flex;flex-direction:column;gap:12px;pointer-events:none;';
    document.body.appendChild(container);
  }
 
  const toast = document.createElement('div');
  const iconColor = type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : type === 'error' ? '#ef4444' : '#3b82f6';
 
  toast.style.cssText = `
    min-width:300px;
    max-width:420px;
    background:white;
    border-radius:16px;
    padding:16px 20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
    display:flex;
    align-items:center;
    gap:14px;
    opacity:0;
    transform:translateX(100%);
    transition:all 0.4s;
    pointer-events:auto;
    border-left:5px solid ${iconColor};
  `;
 
  toast.style.position = 'relative';

  toast.innerHTML = `
    <div style="width:36px;height:36px;border-radius:50%;background:${iconColor};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:white;fill:none;stroke-width:3;">
        ${type === 'success' ? '<polyline points="20 6 9 17 4 12"></polyline>' :
          type === 'warning' ? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' :
          type === 'error' ? '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>' :
          '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'}
      </svg>
    </div>
    <div style="flex:1;">
      ${title ? `<div style="font-weight:600;font-size:16px;color:#1E3A8A;margin-bottom:4px;">${title}</div>` : ''}
      <div style="font-size:14px;color:#4B6A9B;">${message}</div>
    </div>
    <button style="background:none;border:none;font-size:24px;cursor:pointer;color:#4B6A9B;opacity:0.6;">×</button>
    <div class="toast-progress" style="background:${iconColor};"></div>
  `;
 
  container.appendChild(toast);
  const progressBar = toast.querySelector('.toast-progress');
  setTimeout(() => {
    toast.style.cssText += 'opacity:1;transform:translateX(0);position:relative;';
    progressBar.style.width = '100%';
    let startTime = Date.now();
    const animateBar = () => {
      const elapsed = Date.now() - startTime;
      const remaining = Math.max(0, 1 - elapsed / duration);
      progressBar.style.width = (remaining * 100) + '%';
      if (remaining > 0) requestAnimationFrame(animateBar);
    };
    requestAnimationFrame(animateBar);
  }, 100);
 
  toast.querySelector('button').onclick = () => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 400);
  };
 
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 400);
  }, duration);
}

//  FUNCIONES DEL CARRITO 
// Pinta los productos del carrito en la vista lateral con sus totales
function renderCart() {
  const container = document.getElementById("cartItems");
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
 
  document.getElementById("total").textContent = `COP $${total.toLocaleString()}`;
  document.getElementById("cartCount").textContent = totalItems;
  document.getElementById("cartCount").style.display = totalItems ? "flex" : "none";
 
  if (cart.length === 0) {
    container.innerHTML = `<div style="text-align:center;padding:100px 20px;color:#888;">Tu carrito está vacío</div>`;
    return;
  }
 
  container.innerHTML = cart.map(item => `
    <div class="cart-item">
      <img src="${item.imgs[0]}" onerror="this.src='https://via.placeholder.com/96x127?text=Producto'">
      <div class="cart-info">
        <h4>${item.name}</h4>
        <span>${item.subcategory}</span>
        <div class="qty">
          <button onclick="updateQty('${item.cartId}', -1)" ${item.quantity <= 1 ? 'disabled' : ''}>−</button>
          <strong>${item.quantity}</strong>
          <button onclick="updateQty('${item.cartId}', 1)">+</button>
        </div>
        <strong>COP $${(item.price * item.quantity).toLocaleString()}</strong>
      </div>
      <button class="remove" onclick="removeFromCart('${item.cartId}')">×</button>
    </div>
  `).join("");
}

// Suma o resta cantidad a un producto del carrito (con límite de stock)
function updateQty(cartId, delta) {
  let item = cart.find(i => i.cartId === cartId);
  if (!item) return;
 
  let p = products.find(p => p.id === item.id);
  let newQty = item.quantity + delta;
 
  if (newQty < 1) {
    removeFromCart(cartId);
    return;
  }
 
  if (newQty > p.stock) {
    showToast({ title: "Stock insuficiente", type: "warning" });
    return;
  }
 
  p.stock -= delta;
  item.quantity = newQty;
  saveCart();
  renderCart();
}

// Elimina un producto del carrito y devuelve el stock
function removeFromCart(cartId) {
  let item = cart.find(i => i.cartId === cartId);
  if (!item) return;
 
  let p = products.find(p => p.id === item.id);
  if (p) p.stock += item.quantity;
 
  cart = cart.filter(i => i.cartId !== cartId);
  saveCart();
  renderCart();
  showToast({ message: `${item.name} eliminado`, type: "info" });
}

// Abre el overlay del carrito lateral
function openCart() {
  document.getElementById('cartOverlay').classList.add('active');
  renderCart();
}

// Cierra el overlay del carrito lateral
function closeCart() {
  document.getElementById('cartOverlay').classList.remove('active');
}

// Redirige al checkout o pide iniciar sesión según el tipo de usuario
function proceedToCheckout() {
  if (!currentUser) {
    showToast({ title: "Inicia sesión", message: "Debes iniciar sesión para finalizar tu compra", type: "warning" });
    window.location.href = '/paginas/perfil.html';
  } else {
    window.location.href = '/paginas/compra.html';
  }
}

//(contador del corazón incluido) 
// Actualiza los contadores de favoritos visibles en la interfaz
function updateFavBadges() {
  let count = favorites.length;
  document.getElementById('favBadge').textContent = count;
  document.getElementById('favHeaderBadge').textContent = count;
  document.getElementById('favTotal').textContent = count;
 
  document.getElementById('favBadge').style.display = count ? 'flex' : 'none';
  document.getElementById('favHeaderBadge').style.display = count ? 'flex' : 'none';
}

// Pinta la lista de productos guardados en favoritos
function renderFavorites() {
  let list = document.getElementById('favoritesList');
  let favProducts = products.filter(p => favorites.includes(p.id));
 
  if (favProducts.length === 0) {
    list.innerHTML = `
      <div class="fav-empty-state">
        <div class="fav-empty-icon">❤️</div>
        <div class="fav-empty-text">No tienes productos en favoritos</div>
        <p style="color:var(--text-secondary);margin-bottom:30px;">
          Guarda tus productos favoritos para verlos aquí
        </p>
        <button class="fav-empty-btn" onclick="closeFavorites()">Seguir comprando</button>
      </div>
    `;
    return;
  }
 
  list.innerHTML = favProducts.map(p => `
    <div class="fav-item">
      <img src="${p.imgs[0]}" class="fav-item-img" onerror="this.src='https://via.placeholder.com/96x127?text=Producto'">
      <div class="fav-item-info">
        <h4>${p.name}</h4>
        <span>${p.category} • ${p.subcategory}</span>
        <div style="font-weight:700;font-size:18px;color:#5E9DE6;margin-top:6px;">
          COP $${p.price.toLocaleString()}
        </div>
      </div>
      <div class="fav-actions">
        <button class="fav-remove-btn" onclick="toggleFavorite(${p.id})">×</button>
      </div>
    </div>
  `).join('');
}

// Agrega o quita un producto de favoritos y guarda el estado
function toggleFavorite(id) {
  if (!currentUser) {
    showToast({ title: "Inicia sesión", message: "Debes iniciar sesión para agregar a favoritos", type: "warning" });
    return;
  }
 
  let p = products.find(p => p.id === id);
 
  if (favorites.includes(id)) {
    favorites = favorites.filter(x => x !== id);
    showToast({ title: "Eliminado", message: `${p.name} eliminado de favoritos`, type: "info" });
  } else {
    favorites.push(id);
    showToast({ title: "¡Añadido!", message: `${p.name} agregado a favoritos`, type: "success" });
  }
 
  saveFavorites();
  updateFavBadges();
  renderFavorites();
}

// Abre el overlay de favoritos (exige sesión iniciada)
function openFavorites() {
  if (!currentUser) {
    showToast({ title: "Inicia sesión", message: "Debes iniciar sesión para ver favoritos", type: "warning" });
    return;
  }
  document.getElementById('favoritesOverlay').classList.add('active');
  renderFavorites();
}

// Cierra el overlay de favoritos
function closeFavorites() {
  document.getElementById('favoritesOverlay').classList.remove('active');
}

//  FUNCIONALIDAD AVANZADA DEL MAPA 
// Estado global de la simulación (ruta, viaje y cálculo)
let appState = { routeCalculated: false, journeyStarted: false, journeyCompleted: false, calculating: false };

// Mapa Leaflet centrado en Medellín
let map = L.map('map').setView([6.2442, -75.5812], 14);

// Capas de mapas disponibles (calles, satélite y oscuro)
const tileLayers = {
  street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }),
  satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: '© Esri'
  }),
  dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© CARTO'
  })
};

let currentLayer = 'street';

// Ocultar skeleton cuando cargue el primer tile (ANTES de agregar la capa)
map.on('tileload', function() {
  const sk = document.getElementById('mapSkeleton');
  if (sk && !sk.classList.contains('hidden')) {
    sk.classList.add('hidden');
    setTimeout(() => sk.remove(), 600);
  }
}, { once: true });

// Fallback: ocultar skeleton tras 5s por si el tile no carga (adblock, etc.)
setTimeout(function() {
  const sk = document.getElementById('mapSkeleton');
  if (sk && !sk.classList.contains('hidden')) {
    sk.classList.add('hidden');
    setTimeout(() => sk.remove(), 600);
  }
}, 5000);

tileLayers.street.addTo(map);

let startPoint = null;
let endPoint = null;
let startMarker = null;
let endMarker = null;
let driverMarker = null;
let pulseMarker = null;
let userMarker = null;
let routingControl = null;
let routeCoordinates = [];
let routeIndex = 0;
let trackingInterval = null;
let currentStep = 0;
const totalSteps = 4;

let routeGlowLine = null;
let routeDecorator = null;
let dashAnimationInterval = null;
let driverHeading = 0;

const geocodeCache = new Map();

// Devuelve la geocodificación en caché de una dirección si existe
function getCachedGeocode(address) {
  const key = address.toLowerCase().trim();
  return geocodeCache.get(key);
}

// Guarda en caché el resultado de una geocodificación
function setCachedGeocode(address, result) {
  const key = address.toLowerCase().trim();
  geocodeCache.set(key, result);
}

// Calcula el ángulo (rumbo) entre dos coordenadas geográficas
function bearing(fromLat, fromLng, toLat, toLng) {
  const dLng = (toLng - fromLng) * Math.PI / 180;
  const y = Math.sin(dLng) * Math.cos(toLat * Math.PI / 180);
  const x = Math.cos(fromLat * Math.PI / 180) * Math.sin(toLat * Math.PI / 180)
          - Math.sin(fromLat * Math.PI / 180) * Math.cos(toLat * Math.PI / 180) * Math.cos(dLng);
  return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
}

// Curva de interpolación suave (ease-in-out)
function easeInOut(t) {
  return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
}

// Genera un marcador tipo "gota" (pin) en SVG con degradado y sombra
function teardropSvg(color) {
  const id = color.replace('#', '');
  return `<svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="g${id}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="${adjustColor(color, 30)}"/>
        <stop offset="100%" stop-color="${color}"/>
      </linearGradient>
      <filter id="s${id}">
        <feDropShadow dx="0" dy="2" stdDeviation="2" flood-opacity="0.3"/>
      </filter>
    </defs>
    <path d="M12.5 0C5.6 0 0 5.6 0 12.5C0 21.9 12.5 41 12.5 41C12.5 41 25 21.9 25 12.5C25 5.6 19.4 0 12.5 0Z"
      fill="url(#g${id})" filter="url(#s${id})" stroke="white" stroke-width="1.2"/>
    <circle cx="12.5" cy="11" r="3.5" fill="rgba(255,255,255,0.25)"/>
  </svg>`;
}

// Genera un marcador circular en SVG, opcionalmente con flecha de dirección
function circleSvg(color, arrow = false, rotation = 0) {
  const id = color.replace('#', '');
  const arrowSvg = arrow
    ? `<g transform="rotate(${rotation}, 18, 18)"><path d="M16 6 L22 16 L16 13 L10 16 Z" fill="white" opacity="0.9"/></g>`
    : '';
  return `<svg width="36" height="36" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="c${id}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="${adjustColor(color, 25)}"/>
        <stop offset="100%" stop-color="${color}"/>
      </linearGradient>
      <filter id="f${id}">
        <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.35"/>
      </filter>
    </defs>
    <circle cx="18" cy="18" r="16" fill="url(#c${id})" filter="url(#f${id})" stroke="white" stroke-width="2"/>
    <circle cx="18" cy="18" r="12" fill="rgba(255,255,255,0.15)"/>
    ${arrowSvg}
  </svg>`;
}

// Aclara u oscurece un color hexadecimal en una cantidad dada
function adjustColor(hex, amount) {
  const num = parseInt(hex.replace('#', ''), 16);
  const r = Math.min(255, (num >> 16) + amount);
  const g = Math.min(255, ((num >> 8) & 0x00FF) + amount);
  const b = Math.min(255, (num & 0x0000FF) + amount);
  return `#${((r << 16) | (g << 8) | b).toString(16).padStart(6, '0')}`;
}

// Convierte un SVG en un divIcon de Leaflet con su tamaño y anclajes
function svgMarker(svg, size, anchor, popupAnchor) {
  return L.divIcon({
    className: 'custom-marker',
    html: svg,
    iconSize: size,
    iconAnchor: anchor,
    popupAnchor: popupAnchor
  });
}

const startIcon = svgMarker(teardropSvg('#22c55e'), [25, 41], [12, 41], [0, -34]);
const endIcon = svgMarker(teardropSvg('#ef4444'), [25, 41], [12, 41], [0, -34]);
const driverIcon = svgMarker(circleSvg('#3b82f6', true), [36, 36], [18, 18], [0, -20]);
const userIcon = svgMarker(circleSvg('#8b5cf6'), [36, 36], [18, 18], [0, -20]);

const startAddressInput = document.getElementById('start-address');
const endAddressInput = document.getElementById('end-address');
const statusIndicator = document.querySelector('.status-indicator');
const statusTitle = document.getElementById('statusTitle');
const statusMessage = document.getElementById('statusMessage');
const statusTime = document.getElementById('statusTime');
const deliveryPerson = document.getElementById('deliveryPerson');
const deliveryContact = document.getElementById('deliveryContact');
const driverName = document.getElementById('driverName');
const routeDistance = document.getElementById('routeDistance');
const routeTime = document.getElementById('routeTime');
const estimatedTime = document.getElementById('estimatedTime');
const estimatedDistance = document.getElementById('estimatedDistance');
const clearRouteBtn = document.getElementById('clearRoute');
const saveRouteBtn = document.getElementById('saveRoute');

// Actualiza el indicador de estado de la entrega
function updateStatus(type, title, message) {
  statusIndicator.className = 'status-indicator pulse';
  statusIndicator.classList.add(`status-${type}`);
  statusTitle.textContent = `Estado: ${title}`;
  statusMessage.textContent = message;
  const now = new Date();
  statusTime.textContent = `Actualizado: ${now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`;
}

// Muestra la distancia y el tiempo estimado de la ruta
function updateRouteInfo(distance, time) {
  routeDistance.textContent = `${distance} km`;
  routeTime.textContent = `${time} min`;
  estimatedTime.textContent = time;
  estimatedDistance.textContent = `${distance} km`;
}

// Posiciona la barra de pasos en un paso específico del proceso
function setStep(step) {
  currentStep = Math.min(Math.max(step, 0), totalSteps - 1);
  const steps = document.querySelectorAll('.step');
  steps.forEach((stepEl, index) => {
    if (index <= currentStep) {
      stepEl.classList.add('completed');
      if (index === currentStep) stepEl.classList.add('active');
      else stepEl.classList.remove('active');
    } else {
      stepEl.classList.remove('completed', 'active');
    }
  });
  const progressFill = document.querySelector('.progress-fill');
  const progressText = document.querySelector('.progress-text');
  const progressPercentage = (currentStep / (totalSteps - 1)) * 100;
  if (progressFill) progressFill.style.width = `${progressPercentage}%`;
  if (progressText) progressText.textContent = `${Math.round(progressPercentage)}%`;
}

// Actualiza la barra de progreso y marca los pasos completados
function updateTrackingProgress(progress) {
  const progressFill = document.querySelector('.progress-fill');
  const progressText = document.querySelector('.progress-text');
  if (progressFill) progressFill.style.width = `${progress}%`;
  if (progressText) progressText.textContent = `${Math.round(progress)}%`;
  const steps = document.querySelectorAll('.step');
  steps.forEach((step, index) => {
    const stepProgress = (index / (steps.length - 1)) * 100;
    if (progress >= stepProgress) {
      step.classList.add('completed');
      if (index === 2 && progress >= 25 && progress < 75) step.classList.add('active');
      else if (index === 2) step.classList.remove('active');
    } else {
      step.classList.remove('completed', 'active');
    }
  });
}

// Llena los datos (simulados) del repartidor en la interfaz
function updateDriverInfo() {
  deliveryPerson.textContent = 'Carlos Rodríguez';
  deliveryContact.textContent = '+57 300 123 4567';
  driverName.textContent = 'Carlos Rodríguez';
}

// Anima un número en pantalla desde `start` hasta `end` con suavizado
function animateValue(el, start, end, duration = 400) {
  const range = start - end;
  if (range === 0) return;
  const startTime = performance.now();
  const isIncreasing = end > start;
  function step(currentTime) {
    const elapsed = currentTime - startTime;
    const t = Math.min(elapsed / duration, 1);
    const eased = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
    const current = Math.round(start + (end - start) * eased);
    el.textContent = current;
    if (t < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

// Actualiza el tiempo restante estimado según el progreso del viaje
function updateRemainingTime(progress, totalTimeMinutes) {
  const newValue = Math.round((totalTimeMinutes * (100 - progress)) / 100);
  const oldValue = parseInt(estimatedTime.textContent) || newValue;
  if (oldValue !== newValue) {
    animateValue(estimatedTime, oldValue, newValue, 300);
  }
}

// Reinicia el estado del mapa, elimina marcadores/rutas y restaura la UI
function resetMapState() {
  appState = { routeCalculated: false, journeyStarted: false, journeyCompleted: false, calculating: false };
  if (routingControl) { map.removeControl(routingControl); routingControl = null; }
  if (endMarker) { map.removeLayer(endMarker); endMarker = null; }
  if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
  if (pulseMarker) { map.removeLayer(pulseMarker); pulseMarker = null; }
  if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
  if (routeGlowLine) { map.removeLayer(routeGlowLine); routeGlowLine = null; }
  if (routeDecorator) { map.removeLayer(routeDecorator); routeDecorator = null; }
  if (dashAnimationInterval) { clearInterval(dashAnimationInterval); dashAnimationInterval = null; }
  endPoint = null;
  routeCoordinates = [];
  routeIndex = 0;
  setStep(0);
  updateStatus('waiting', 'Esperando', 'Ingresa una dirección de destino.');
  routeDistance.textContent = '--';
  routeTime.textContent = '--';
  estimatedTime.textContent = '--';
  estimatedDistance.textContent = '--';
  deliveryPerson.textContent = 'Asignando...';
  deliveryContact.textContent = '---';
  driverName.textContent = '-';
  clearRouteBtn.disabled = true;
  saveRouteBtn.disabled = true;
}

// Simula el avance del repartidor por la ruta, moviendo el marcador
// en intervalos, dibujando la estela y actualizando el progreso
function startTracking(totalTimeMinutes) {
  if (routeCoordinates.length === 0) return;
  const driverDivIcon = svgMarker(circleSvg('#3b82f6', true), [36, 36], [18, 18], [0, -20]);
  driverMarker = L.marker(startPoint, { icon: driverDivIcon, zIndexOffset: 1000 }).addTo(map)
    .bindPopup('<b>🚛 Repartidor</b><br>Iniciando entrega...').openPopup();
  const pulseEl = L.divIcon({ className: 'marker-pulse-ring', iconSize: [44, 44], iconAnchor: [22, 22] });
  const pulseMarker = L.marker(startPoint, { icon: pulseEl, interactive: false }).addTo(map);
  driverHeading = 0;
  if (trackingInterval) clearInterval(trackingInterval);
  routeIndex = 0;
  appState.journeyStarted = true;
  setStep(2);
  updateStatus('traveling', 'En Camino', 'Siguiendo la ruta de entrega...');
  updateDriverInfo();

  const simSpeed = 0.15;
  const totalTimeMs = totalTimeMinutes * 60 * 1000 * simSpeed;
  const steps = routeCoordinates.length;
  const intervalMs = Math.max(10, totalTimeMs / steps);
  const stepIncrement = Math.max(1, Math.floor(routeCoordinates.length / 60));
  let subStep = 0;
  const SUB_STEPS = 5;

  let trailCoords = [];
  let trailLine = null;

  trackingInterval = setInterval(() => {
    if (routeIndex < routeCoordinates.length - 1) {
      const idx = routeIndex;
      const nextIdx = Math.min(idx + 1, routeCoordinates.length - 1);
      const current = routeCoordinates[idx];
      const next = routeCoordinates[nextIdx];

      const t = easeInOut(Math.min(subStep / SUB_STEPS, 1));
      const lat = current.lat + (next.lat - current.lat) * t;
      const lng = current.lng + (next.lng - current.lng) * t;
      const pos = [lat, lng];

      const heading = bearing(current.lat, current.lng, next.lat, next.lng);
      if (!isNaN(heading) && Math.abs(heading - driverHeading) > 2) {
        driverHeading = heading;
        driverMarker.setIcon(svgMarker(circleSvg('#3b82f6', true, heading), [36, 36], [18, 18], [0, -20]));
      }

      driverMarker.setLatLng(pos);
      if (pulseMarker) pulseMarker.setLatLng(pos);

      trailCoords.push(L.latLng(lat, lng));
      if (trailCoords.length > 1) {
        if (trailLine) map.removeLayer(trailLine);
        trailLine = L.polyline(trailCoords, {
          color: '#3b82f6', weight: 3, opacity: 0.35, className: 'trail-line'
        }).addTo(map);
      }

      if (routeIndex % 8 === 0) {
        map.setView(pos, Math.max(map.getZoom(), 15), { animate: true, duration: 0.1 });
      }

      const progress = (routeIndex / routeCoordinates.length) * 100;
      driverMarker.setPopupContent(`<b>🚛 Repartidor</b><br>Progreso: ${Math.round(progress)}%`);
      updateTrackingProgress(progress);
      updateRemainingTime(progress, totalTimeMinutes);

      subStep++;
      if (subStep >= SUB_STEPS) {
        subStep = 0;
        routeIndex += stepIncrement;
      }
    } else {
      clearInterval(trackingInterval);
      if (dashAnimationInterval) { clearInterval(dashAnimationInterval); dashAnimationInterval = null; }
      appState.journeyCompleted = true;
      setStep(3);
      updateStatus('completed', 'Entrega Completada', '¡Pedido entregado exitosamente!');
      const finalIcon = svgMarker(circleSvg('#22c55e'), [36, 36], [18, 18], [0, -20]);
      driverMarker.setIcon(finalIcon);
      driverMarker.setPopupContent('<b>🎉 Entregado</b><br>¡Pedido completado!').openPopup();
      updateTrackingProgress(100);
      document.getElementById('liveNotification').style.display = 'block';
      setTimeout(() => document.getElementById('liveNotification').style.display = 'none', 5000);
      setTimeout(() => { endAddressInput.value = ''; resetMapState(); }, 5000);
    }
  }, intervalMs);
}

// Estandariza abreviaturas comunes de direcciones colombianas (Cl, Cr, Av...)
function normalizeAddress(address) {
  return address
    .replace(/cl(\.|e)?\s*/gi, 'Calle ')
    .replace(/cr(\.|a)?\s*/gi, 'Carrera ')
    .replace(/av(\.|e)?\s*/gi, 'Avenida ')
    .replace(/#\s*/g, 'No ')
    .replace(/(\d+)-(\d+)/g, '$1 No $2')
    .replace(/\s+/g, ' ').trim();
}

// Convierte una dirección en coordenadas (lat/lng) consultando
// Nominatim con varias variantes y devolviendo el mejor resultado
async function geocodeAddress(address) {
  const originalAddress = address.trim();
  if (!originalAddress) throw new Error('Dirección vacía.');
  const cached = getCachedGeocode(originalAddress);
  if (cached) return cached;

  const addressVariations = [
    `${normalizeAddress(originalAddress)}, Medellín, Antioquia, Colombia`,
    `${originalAddress}, Medellín, Antioquia, Colombia`,
    `${originalAddress}, Medellín, Colombia`,
    normalizeAddress(originalAddress),
    originalAddress
  ];
  const medellinBounds = '-75.7,6.0,-75.4,6.4';
  async function searchNominatim(query, useBounds = true) {
    try {
      let url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=5&countrycodes=co&addressdetails=1&dedupe=0`;
      if (useBounds) url += `&bounded=1&viewbox=${medellinBounds}`;
      const response = await fetch(url, { headers: { 'User-Agent': 'DeliveryMapApp/1.0' } });
      if (!response.ok) throw new Error('Error de red');
      return await response.json() || [];
    } catch (error) {
      console.log('Error en Nominatim:', error);
      return [];
    }
  }
  function isValidResult(result) {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    if (isNaN(lat) || isNaN(lng)) return false;
    return lat >= 4.5 && lat <= 7.5 && lng >= -76.5 && lng <= -75.0;
  }
  function scoreResult(result, query) {
    let score = parseFloat(result.importance || 0);
    const displayName = (result.display_name || '').toLowerCase();
    const queryLower = query.toLowerCase();
    if (displayName.includes('medellín') || displayName.includes('medellin')) score += 1.0;
    if (displayName.includes('antioquia')) score += 0.5;
    if (displayName.includes(queryLower)) score += 0.8;
    return score;
  }
  let allResults = [];
  for (const variation of addressVariations) {
    let results = await searchNominatim(variation, true);
    if (results.length === 0) results = await searchNominatim(variation, false);
    const validResults = results.filter(isValidResult).map(result => ({ ...result, score: scoreResult(result, variation) }));
    allResults = allResults.concat(validResults);
    if (validResults.length > 0 && validResults[0].score > 0.5) break;
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  if (allResults.length === 0) throw new Error('No se encontró la dirección. Verifica e intenta nuevamente.');
  allResults.sort((a, b) => b.score - a.score);
  const bestResult = allResults[0];
  const result = {
    lat: parseFloat(bestResult.lat),
    lng: parseFloat(bestResult.lon),
    display_name: bestResult.display_name
  };
  setCachedGeocode(originalAddress, result);
  return result;
}

// Dibuja la línea de ruta resaltada con flechas direccionales animadas
function drawEnhancedRoute(coords) {
  if (routeGlowLine) map.removeLayer(routeGlowLine);
  if (routeDecorator) map.removeLayer(routeDecorator);
  if (dashAnimationInterval) { clearInterval(dashAnimationInterval); dashAnimationInterval = null; }

  const latlngs = coords.map(c => L.latLng(c.lat ?? c[0], c.lng ?? c[1]));

  routeGlowLine = L.polyline(latlngs, {
    color: '#3b82f6',
    weight: 9,
    opacity: 0.2,
    className: 'route-glow'
  }).addTo(map);

  const mainLine = L.polyline(latlngs, {
    color: '#5E9DE6',
    weight: 4,
    opacity: 0.95,
    dashArray: '12, 8'
  }).addTo(map);

  let dashOffset = 0;
  dashAnimationInterval = setInterval(() => {
    dashOffset -= 1;
    mainLine.setStyle({ dashOffset: String(dashOffset) });
  }, 100);

  if (typeof L.polylineDecorator !== 'undefined' && latlngs.length > 1) {
    routeDecorator = L.polylineDecorator(latlngs, {
      patterns: [
        { offset: 15, repeat: 50, symbol: L.Symbol.arrowHead({ pixelSize: 8, polygon: false, pathOptions: { color: '#5E9DE6', weight: 2, opacity: 0.7 } }) }
      ]
    }).addTo(map);
  }
}

// Traza la ruta entre origen y destino usando el control de routing;
// al encontrar la ruta, dibuja la línea y arranca la simulación
function calculateRoute(start, end) {
  if (routingControl) map.removeControl(routingControl);
  const btn = document.getElementById('calculateRoute');
  if (btn) { btn.innerHTML = '<span class="spinner"></span> Calculando...'; btn.classList.add('btn-loading'); }
  updateStatus('calculating', 'Trazando Ruta', 'Calculando la mejor ruta...');
  routingControl = L.Routing.control({
    waypoints: [L.latLng(start.lat, start.lng), L.latLng(end.lat, end.lng)],
    routeWhileDragging: false,
    show: false,
    lineOptions: { styles: [{ color: '#5E9DE6', weight: 3, opacity: 0.3, dashArray: '1, 0' }] },
    createMarker: function() { return null; }
  }).addTo(map);
  routingControl.on('routesfound', function(e) {
    if (btn) { btn.innerHTML = '<i class="fas fa-route"></i> Calcular Ruta'; btn.classList.remove('btn-loading'); }
    const routes = e.routes;
    const route = routes[0];
    routeCoordinates = route.coordinates;
    drawEnhancedRoute(routeCoordinates);
    const group = new L.featureGroup([startMarker, endMarker]);
    map.fitBounds(group.getBounds().pad(0.1), { animate: true, duration: 1, maxZoom: 15 });
    appState.routeCalculated = true;
    const distance = (route.summary.totalDistance / 1000).toFixed(1);
    const time = Math.round(route.summary.totalTime / 60);
    updateRouteInfo(distance, time);
    updateStatus('ready', 'Ruta Calculada', `Distancia: ${distance} km - Tiempo: ${time} min`);
    clearRouteBtn.disabled = false;
    saveRouteBtn.disabled = false;
    setTimeout(() => startTracking(time), 3000);
  });
  routingControl.on('routingerror', function(e) {
    if (btn) { btn.innerHTML = '<i class="fas fa-route"></i> Calcular Ruta'; btn.classList.remove('btn-loading'); }
    updateStatus('error', 'Error en Ruta', 'No se pudo calcular la ruta.');
    appState.calculating = false;
  });
}

// Inicialización con ubicación del usuario 
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      map.setView([lat, lng], 16);
      if (userMarker) map.removeLayer(userMarker);
      userMarker = L.marker([lat, lng], { icon: userIcon }).addTo(map).bindPopup('📍 Tu ubicación').openPopup();
      startPoint = { lat, lng };
      startMarker = userMarker;
      startAddressInput.value = 'Tu ubicación actual';
      const destinoGuardado = localStorage.getItem('destinoEntrega');
      if (destinoGuardado) {
        endAddressInput.value = destinoGuardado;
        localStorage.removeItem('destinoEntrega');
        setTimeout(() => {
          const endAddress = endAddressInput.value;
          if (endAddress) {
            updateStatus('calculating', 'Calculando Ruta', 'Preparando ruta...');
            geocodeAddress(endAddress).then(endLocation => {
              endPoint = L.latLng(endLocation.lat, endLocation.lng);
              if (endMarker) map.removeLayer(endMarker);
              endMarker = L.marker(endPoint, { icon: endIcon }).addTo(map).bindPopup(`<b>🎯 Destino</b><br>${endLocation.display_name}`);
              setTimeout(() => calculateRoute(startPoint, endLocation), 2000);
            }).catch(error => { updateStatus('error', 'Error', error.message); });
          }
        }, 500);
      }
    },
    (error) => {
      let message = 'Error al obtener la ubicación.';
      if (error.code === error.PERMISSION_DENIED) message = 'Permiso de ubicación denegado.';
      else if (error.code === error.POSITION_UNAVAILABLE) message = 'Ubicación no disponible.';
      else if (error.code === error.TIMEOUT) message = 'Tiempo de espera agotado.';
      showToast({ title: 'Error de ubicación', message, type: 'error' });
      updateStatus('error', 'Error de Ubicación', message);
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
} else {
  showToast({ title: 'Error', message: 'Geolocalización no soportada', type: 'error' });
}

//  EVENT LISTENERS
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('currentYear').textContent = new Date().getFullYear();

  // Cargar ruta guardada
  const rutaGuardada = localStorage.getItem('angelow_ruta_guardada');
  if (rutaGuardada) {
    try {
      const r = JSON.parse(rutaGuardada);
      if (r.destino && confirm(`Tienes una ruta guardada a "${r.destino}". ¿Cargarla?`)) {
        endAddressInput.value = r.destino;
        setTimeout(() => document.getElementById('calculateRoute')?.click(), 1000);
      }
    } catch (e) { /* ignorar */ }
  }

  const profileBtn = document.getElementById('profileBtn');
  const dropdownMenu = document.getElementById('dropdownMenu');
  if (profileBtn && dropdownMenu) {
    profileBtn.addEventListener('click', (e) => { e.stopPropagation(); dropdownMenu.classList.toggle('show'); });
    document.addEventListener('click', () => { dropdownMenu.classList.remove('show'); });
  }

  document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    localStorage.removeItem('angelow_user');
    localStorage.removeItem('angelow_cart');
    localStorage.removeItem('angelow_favorites');
    window.location.href = '/index.html';
  });

  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && searchInput.value.trim()) {
        window.location.href = `/index.html?search=${encodeURIComponent(searchInput.value.trim())}`;
      }
    });
  }

  document.getElementById('cartBtnHeader').onclick = openCart;
  document.getElementById('favBtnHeader').onclick = openFavorites;
  const favMenu = document.getElementById('openFavoritesFromMenu');
  if (favMenu) favMenu.onclick = (e) => { e.preventDefault(); if (dropdownMenu) dropdownMenu.classList.remove('show'); openFavorites(); };
  document.getElementById('closeFavorites').onclick = closeFavorites;

  document.querySelectorAll('.overlay').forEach(o => {
    o.onclick = e => { if (e.target === o) o.classList.remove('active'); };
  });

  const controlsPanel = document.getElementById('controlsPanel');
  const controlsToggle = document.getElementById('controlsToggle');
  const closePanel = document.getElementById('closePanel');
  if (controlsToggle) controlsToggle.addEventListener('click', () => { controlsPanel.classList.remove('collapsed'); controlsPanel.classList.add('expanded'); });
  if (closePanel) closePanel.addEventListener('click', () => { controlsPanel.classList.remove('expanded'); controlsPanel.classList.add('collapsed'); });

  document.querySelectorAll('.suggestion').forEach(suggestion => {
    suggestion.addEventListener('click', function() { endAddressInput.value = this.getAttribute('data-address'); });
  });

  // BOTÓN "USAR MI UBICACIÓN" (AHORA SÍ FUNCIONAL Y SIN AFECTAR FAVORITOS)
  document.getElementById('useCurrentLocation')?.addEventListener('click', () => {
    if (navigator.geolocation) {
      showToast({ message: 'Obteniendo tu ubicación...', type: 'info' });
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          map.setView([lat, lng], 16);
          if (userMarker) map.removeLayer(userMarker);
          userMarker = L.marker([lat, lng], { icon: userIcon }).addTo(map).bindPopup('📍 Tu ubicación').openPopup();
          startPoint = { lat, lng };
          startMarker = userMarker;
          startAddressInput.value = 'Ubicación actual (GPS)';
          showToast({ message: 'Ubicación actualizada como punto de origen', type: 'success' });
        },
        (error) => {
          let msg = 'No se pudo obtener tu ubicación.';
          if (error.code === error.PERMISSION_DENIED) msg = 'Permiso denegado. Habilita la geolocalización.';
          showToast({ title: 'Error', message: msg, type: 'error' });
        }
      );
    } else {
      showToast({ title: 'Error', message: 'Geolocalización no soportada', type: 'error' });
    }
  });

  document.getElementById('locateMe')?.addEventListener('click', () => { if (userMarker) map.setView(userMarker.getLatLng(), 16); });
  document.getElementById('calculateRoute')?.addEventListener('click', async function() {
    const endAddress = endAddressInput.value.trim();
    if (!startPoint) { updateStatus('error', 'Esperando GPS', 'Define origen con "Usar mi ubicación" o espera GPS.'); return; }
    if (!endAddress) { updateStatus('error', 'Error', 'Por favor ingresa la dirección de destino.'); return; }
    if (appState.calculating || appState.journeyStarted) return;
    appState.calculating = true;
    const panel = document.getElementById('controlsPanel');
    if (panel && panel.classList.contains('expanded')) {
      panel.classList.remove('expanded');
      panel.classList.add('collapsed');
    }
    updateStatus('calculating', 'Calculando Ruta', 'Preparando ruta...');
    try {
      const endLocation = await geocodeAddress(endAddress);
      endPoint = L.latLng(endLocation.lat, endLocation.lng);
      if (endMarker) map.removeLayer(endMarker);
      if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
      if (pulseMarker) { map.removeLayer(pulseMarker); pulseMarker = null; }
      endMarker = L.marker(endPoint, { icon: endIcon }).addTo(map).bindPopup(`<b>🎯 Destino</b><br>${endLocation.display_name}`);
      calculateRoute(startPoint, endLocation);
    } catch (error) { updateStatus('error', 'Error', error.message); appState.calculating = false; }
  });
  document.getElementById('clearRoute')?.addEventListener('click', () => {
    endAddressInput.value = '';
    resetMapState();
  });

  // GUARDAR RUTA
  document.getElementById('saveRoute')?.addEventListener('click', () => {
    const dest = endAddressInput.value.trim();
    const dist = routeDistance.textContent;
    const t = routeTime.textContent;
    if (!dest || dist === '--') {
      showToast({ title: 'Sin ruta', message: 'Calcula una ruta primero', type: 'warning' });
      return;
    }
    localStorage.setItem('angelow_ruta_guardada', JSON.stringify({ destino: dest, distancia: dist, tiempo: t }));
    showToast({ title: 'Ruta guardada', message: 'Podrás retomarla al recargar la página', type: 'success' });
  });

  // AUTOCOMPLETE DE DIRECCIONES
  let debounceTimer = null;
  endAddressInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const q = this.value.trim();
    const dropdown = document.getElementById('addressSuggestions');
    if (q.length < 3) { dropdown.style.display = 'none'; return; }
    debounceTimer = setTimeout(async () => {
      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5&countrycodes=co&bounded=1&viewbox=-75.7,6.0,-75.4,6.4`,
          { headers: { 'User-Agent': 'DeliveryMapApp/1.0' } }
        );
        const data = await res.json();
        if (!data.length) { dropdown.style.display = 'none'; return; }
        dropdown.innerHTML = data.map(d => `
          <div class="autocomplete-item" data-address="${d.display_name}">
            <i class="fas fa-map-pin"></i>
            <span>${d.display_name}</span>
          </div>
        `).join('');
        dropdown.style.display = 'block';
        dropdown.querySelectorAll('.autocomplete-item').forEach(el => {
          el.addEventListener('click', () => {
            endAddressInput.value = el.getAttribute('data-address');
            dropdown.style.display = 'none';
          });
        });
      } catch (e) { document.getElementById('addressSuggestions').style.display = 'none'; }
    }, 300);
  });

  // Cerrar autocomplete al hacer clic fuera
  document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('addressSuggestions');
    if (!e.target.closest('#end-address') && !e.target.closest('#addressSuggestions')) {
      if (dropdown) dropdown.style.display = 'none';
    }
  });

  document.querySelectorAll('.call-btn').forEach(btn => btn.addEventListener('click', () => showToast({ title: 'Llamando', message: 'Llamando al repartidor...', type: 'info' })));
  document.querySelectorAll('.message-btn').forEach(btn => btn.addEventListener('click', () => showToast({ title: 'Mensaje', message: 'Abriendo chat...', type: 'info' })));
  document.querySelectorAll('.location-btn').forEach(btn => btn.addEventListener('click', () => { if (driverMarker) map.setView(driverMarker.getLatLng(), 16); }));
  document.getElementById('shareTracking')?.addEventListener('click', () => {
    if (navigator.share) navigator.share({ title: 'Mi pedido Angelow', text: 'Sigue mi pedido en tiempo real', url: window.location.href });
    else { navigator.clipboard.writeText(window.location.href); showToast({ message: 'Enlace copiado al portapapeles', type: 'success' }); }
  });
  document.getElementById('zoomIn')?.addEventListener('click', () => map.zoomIn());
  document.getElementById('zoomOut')?.addEventListener('click', () => map.zoomOut());
  document.getElementById('resetView')?.addEventListener('click', () => { if (userMarker) map.setView(userMarker.getLatLng(), 16); else map.setView([6.2442, -75.5812], 14); });

  // LAYER SWITCHER
  document.querySelectorAll('.layer-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const layer = this.getAttribute('data-layer');
      if (layer === currentLayer) return;
      document.querySelectorAll('.layer-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      Object.keys(tileLayers).forEach(k => {
        if (k === layer) tileLayers[k].addTo(map);
        else if (map.hasLayer(tileLayers[k])) map.removeLayer(tileLayers[k]);
      });
      currentLayer = layer;
    });
  });

  document.getElementById('refreshStatus')?.addEventListener('click', () => {
    updateStatus(statusIndicator.classList[2]?.replace('status-', '') || 'waiting', statusTitle.textContent.replace('Estado: ', ''), statusMessage.textContent);
    showToast({ message: 'Estado actualizado', type: 'success' });
  });

  renderCart();
  renderFavorites();
  updateFavBadges();
});

console.log('Mapa y todos los controles cargados correctamente (contador de favoritos intacto)');