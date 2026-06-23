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
  `;
 
  container.appendChild(toast);
  setTimeout(() => toast.style.cssText += 'opacity:1;transform:translateX(0);', 100);
 
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

function openCart() {
  document.getElementById('cartOverlay').classList.add('active');
  renderCart();
}

function closeCart() {
  document.getElementById('cartOverlay').classList.remove('active');
}

function proceedToCheckout() {
  if (!currentUser) {
    showToast({ title: "Inicia sesión", message: "Debes iniciar sesión para finalizar tu compra", type: "warning" });
    window.location.href = '/paginas/perfil.html';
  } else {
    window.location.href = '/paginas/compra.html';
  }
}

//(contador del corazón incluido) 
function updateFavBadges() {
  let count = favorites.length;
  document.getElementById('favBadge').textContent = count;
  document.getElementById('favHeaderBadge').textContent = count;
  document.getElementById('favTotal').textContent = count;
 
  document.getElementById('favBadge').style.display = count ? 'flex' : 'none';
  document.getElementById('favHeaderBadge').style.display = count ? 'flex' : 'none';
}

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

function openFavorites() {
  if (!currentUser) {
    showToast({ title: "Inicia sesión", message: "Debes iniciar sesión para ver favoritos", type: "warning" });
    return;
  }
  document.getElementById('favoritesOverlay').classList.add('active');
  renderFavorites();
}

function closeFavorites() {
  document.getElementById('favoritesOverlay').classList.remove('active');
}

//  FUNCIONALIDAD AVANZADA DEL MAPA 
let appState = { routeCalculated: false, journeyStarted: false, journeyCompleted: false, calculating: false };

let map = L.map('map').setView([6.2442, -75.5812], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);

let startPoint = null;
let endPoint = null;
let startMarker = null;
let endMarker = null;
let driverMarker = null;
let userMarker = null;
let routingControl = null;
let routeCoordinates = [];
let routeIndex = 0;
let trackingInterval = null;
let currentStep = 0;
const totalSteps = 4;

const startIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
});
const endIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
});
const driverIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
});
const userIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
});

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

function updateStatus(type, title, message) {
  statusIndicator.className = 'status-indicator pulse';
  statusIndicator.classList.add(`status-${type}`);
  statusTitle.textContent = `Estado: ${title}`;
  statusMessage.textContent = message;
  const now = new Date();
  statusTime.textContent = `Actualizado: ${now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`;
}

function updateRouteInfo(distance, time) {
  routeDistance.textContent = `${distance} km`;
  routeTime.textContent = `${time} min`;
  estimatedTime.textContent = time;
  estimatedDistance.textContent = `${distance} km`;
}

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

function updateDriverInfo() {
  deliveryPerson.textContent = 'Carlos Rodríguez';
  deliveryContact.textContent = '+57 300 123 4567';
  driverName.textContent = 'Carlos Rodríguez';
}

function updateRemainingTime(progress, totalTimeMinutes) {
  const remainingMinutes = Math.round((totalTimeMinutes * (100 - progress)) / 100);
  estimatedTime.textContent = remainingMinutes;
}

function resetMapState() {
  appState = { routeCalculated: false, journeyStarted: false, journeyCompleted: false, calculating: false };
  if (routingControl) { map.removeControl(routingControl); routingControl = null; }
  if (endMarker) { map.removeLayer(endMarker); endMarker = null; }
  if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
  if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
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

function startTracking(totalTimeMinutes) {
  if (routeCoordinates.length === 0) return;
  driverMarker = L.marker(startPoint, { icon: driverIcon }).addTo(map).bindPopup('<b>🚛 Repartidor</b><br>Iniciando entrega...').openPopup();
  if (trackingInterval) clearInterval(trackingInterval);
  routeIndex = 0;
  appState.journeyStarted = true;
  setStep(2);
  updateStatus('traveling', 'En Camino', 'Siguiendo la ruta de entrega...');
  updateDriverInfo();
  const totalTimeMs = totalTimeMinutes * 60 * 1000 * 0.2;
  const steps = routeCoordinates.length;
  const intervalMs = Math.max(10, totalTimeMs / steps);
  const increment = Math.max(1, Math.floor(routeCoordinates.length / 50));
  trackingInterval = setInterval(() => {
    if (routeIndex < routeCoordinates.length) {
      const currentPos = routeCoordinates[routeIndex];
      driverMarker.setLatLng(currentPos);
      if (routeIndex % 10 === 0) map.setView(currentPos, Math.max(map.getZoom(), 15), { animate: true, duration: 0.05 });
      const progress = (routeIndex / routeCoordinates.length) * 100;
      driverMarker.setPopupContent(`<b>🚛 Repartidor</b><br>Progreso: ${Math.round(progress)}%`);
      updateTrackingProgress(progress);
      updateRemainingTime(progress, totalTimeMinutes);
      routeIndex += increment;
    } else {
      clearInterval(trackingInterval);
      appState.journeyCompleted = true;
      setStep(3);
      updateStatus('completed', 'Entrega Completada', '¡Pedido entregado exitosamente!');
      driverMarker.setPopupContent('<b>🎉 Entregado</b><br>¡Pedido completado!').openPopup();
      updateTrackingProgress(100);
      document.getElementById('liveNotification').style.display = 'block';
      setTimeout(() => document.getElementById('liveNotification').style.display = 'none', 5000);
      setTimeout(() => { endAddressInput.value = ''; resetMapState(); }, 5000);
    }
  }, intervalMs);
}

function normalizeAddress(address) {
  return address
    .replace(/cl(\.|e)?\s*/gi, 'Calle ')
    .replace(/cr(\.|a)?\s*/gi, 'Carrera ')
    .replace(/av(\.|e)?\s*/gi, 'Avenida ')
    .replace(/#\s*/g, 'No ')
    .replace(/(\d+)-(\d+)/g, '$1 No $2')
    .replace(/\s+/g, ' ').trim();
}

async function geocodeAddress(address) {
  const originalAddress = address.trim();
  if (!originalAddress) throw new Error('Dirección vacía.');
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
    await new Promise(resolve => setTimeout(resolve, 1000));
  }
  if (allResults.length === 0) throw new Error('No se encontró la dirección. Verifica e intenta nuevamente.');
  allResults.sort((a, b) => b.score - a.score);
  const bestResult = allResults[0];
  return {
    lat: parseFloat(bestResult.lat),
    lng: parseFloat(bestResult.lon),
    display_name: bestResult.display_name
  };
}

function calculateRoute(start, end) {
  if (routingControl) map.removeControl(routingControl);
  updateStatus('calculating', 'Trazando Ruta', 'Calculando la mejor ruta...');
  routingControl = L.Routing.control({
    waypoints: [L.latLng(start.lat, start.lng), L.latLng(end.lat, end.lng)],
    routeWhileDragging: false,
    show: false,
    lineOptions: { styles: [{ color: '#7B9FD8', weight: 5, opacity: 0.8 }] },
    createMarker: function() { return null; }
  }).addTo(map);
  routingControl.on('routesfound', function(e) {
    const routes = e.routes;
    const route = routes[0];
    routeCoordinates = route.coordinates;
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
    updateStatus('calculating', 'Calculando Ruta', 'Preparando ruta...');
    try {
      const endLocation = await geocodeAddress(endAddress);
      endPoint = L.latLng(endLocation.lat, endLocation.lng);
      if (endMarker) map.removeLayer(endMarker);
      if (driverMarker) map.removeLayer(driverMarker);
      endMarker = L.marker(endPoint, { icon: endIcon }).addTo(map).bindPopup(`<b>🎯 Destino</b><br>${endLocation.display_name}`);
      calculateRoute(startPoint, endLocation);
    } catch (error) { updateStatus('error', 'Error', error.message); appState.calculating = false; }
  });
  document.getElementById('clearRoute')?.addEventListener('click', resetMapState);

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
  document.getElementById('refreshStatus')?.addEventListener('click', () => {
    updateStatus(statusIndicator.classList[2]?.replace('status-', '') || 'waiting', statusTitle.textContent.replace('Estado: ', ''), statusMessage.textContent);
    showToast({ message: 'Estado actualizado', type: 'success' });
  });

  renderCart();
  renderFavorites();
  updateFavBadges();
});

console.log('Mapa y todos los controles cargados correctamente (contador de favoritos intacto)');