<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
  <title>Aliados Delivery | Panel Repartidor</title>
  <!-- Tailwind CSS + Fonts + Leaflet + Lucide -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    * {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    body {
      background-color: #000000;
      overflow-x: hidden;
    }
    /* scroll suave para órdenes */
    .orders-scroll {
      scroll-behavior: smooth;
    }
    /* botones grandes táctiles */
    .tap-btn {
      min-height: 48px;
      touch-action: manipulation;
    }
    /* transiciones suaves */
    .transition-smooth {
      transition: all 0.2s ease-in-out;
    }
    /* contenedor mapa */
    .map-container {
      background: #1a1a1a;
      border-radius: 24px;
      overflow: hidden;
      aspect-ratio: 16 / 9;
      width: 100%;
      border: 1px solid #2a2a2a;
    }
    /* burbujas chat */
    .chat-bubble-driver {
      background-color: #22c55e;
      color: white;
      border-radius: 20px 20px 4px 20px;
      padding: 10px 14px;
      max-width: 80%;
      align-self: flex-end;
    }
    .chat-bubble-client {
      background-color: #2d2d2d;
      color: #e5e5e5;
      border-radius: 20px 20px 20px 4px;
      padding: 10px 14px;
      max-width: 80%;
      align-self: flex-start;
    }
    /* animación aceptar orden */
    @keyframes pulse-accept {
      0% { transform: scale(1); background-color: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,0.7); }
      70% { transform: scale(0.98); background-color: #16a34a; box-shadow: 0 0 0 10px rgba(34,197,94,0); }
      100% { transform: scale(1); background-color: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }
    .animate-accept {
      animation: pulse-accept 0.4s ease-out;
    }
    /* scrollbar personalizada */
    .custom-scroll::-webkit-scrollbar {
      width: 4px;
    }
    .custom-scroll::-webkit-scrollbar-track {
      background: #1e1e1e;
      border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
      background: #3a3a3a;
      border-radius: 10px;
    }
    /* helpers */
    .hide-scrollbar {
      scrollbar-width: thin;
    }
  </style>
</head>
<body class="bg-black text-gray-100 antialiased">

  <!-- PANTALLA LOGIN (SIMULADA CON OTP) -->
  <div id="loginScreen" class="min-h-screen flex items-center justify-center px-5 bg-black">
    <div class="w-full max-w-md bg-[#0f0f0f] rounded-2xl p-6 shadow-xl border border-gray-800">
      <div class="flex justify-center mb-5">
        <div class="bg-green-500/20 p-3 rounded-full">
          <i data-lucide="bike" class="w-10 h-10 text-green-500"></i>
        </div>
      </div>
      <h2 class="text-2xl font-bold text-center text-white mb-1">Aliados Repartidor</h2>
      <p class="text-center text-gray-400 text-sm mb-6">Ingresa con tu número y código</p>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1">Teléfono</label>
          <input type="tel" id="phoneNumber" class="w-full bg-[#1c1c1c] border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="+57 300 123 4567" value="+57 310 555 1234">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1">Código OTP</label>
          <input type="text" id="otpCode" class="w-full bg-[#1c1c1c] border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="123456">
          <p class="text-xs text-gray-500 mt-1">Demo: código <span class="text-green-400">123456</span></p>
        </div>
        <button id="loginBtn" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl transition-smooth tap-btn mt-2">Ingresar →</button>
      </div>
    </div>
  </div>

  <!-- APP PRINCIPAL (DASHBOARD) -->
  <div id="appContainer" class="hidden max-w-lg mx-auto bg-black min-h-screen shadow-2xl relative">
    <!-- Header superior (solo visible en vistas normales) -->
    <div id="dynamicHeader" class="bg-[#0c0c0c] border-b border-gray-800 px-4 pt-5 pb-3 sticky top-0 z-20">
      <div class="flex justify-between items-center">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center overflow-hidden border-2 border-green-500">
            <img id="profileAvatar" src="https://randomuser.me/api/portraits/men/32.jpg" alt="avatar" class="w-full h-full object-cover">
          </div>
          <div>
            <h2 id="driverName" class="font-bold text-white text-lg">Carlos Pérez</h2>
            <div class="flex items-center gap-1">
              <i data-lucide="star" class="w-3.5 h-3.5 fill-yellow-400 text-yellow-400"></i>
              <span id="driverRating" class="text-gray-200 text-sm">4.8</span>
              <span class="text-gray-500 text-xs ml-1">(124)</span>
            </div>
          </div>
        </div>
        <button id="logoutBtnHeader" class="bg-gray-800 hover:bg-gray-700 text-red-400 px-3 py-2 rounded-full text-sm font-medium flex items-center gap-1">
          <i data-lucide="log-out" class="w-4 h-4"></i> Salir
        </button>
      </div>
    </div>

    <!-- Contenedor de vistas dinámicas (Inicio, Órdenes, Ganancias, Perfil) -->
    <main id="mainViewContainer" class="pb-24 px-4 pt-4"></main>

    <!-- Menú inferior (bottom navigation) -->
    <div id="bottomNav" class="fixed bottom-0 left-0 right-0 bg-[#0a0a0a] border-t border-gray-800 py-2 px-4 flex justify-around items-center z-30 max-w-lg mx-auto">
      <button data-nav="home" class="nav-btn flex flex-col items-center gap-1 text-gray-400 hover:text-green-500 transition-colors py-1">
        <i data-lucide="home" class="w-6 h-6"></i>
        <span class="text-[11px] font-medium">Inicio</span>
      </button>
      <button data-nav="orders" class="nav-btn flex flex-col items-center gap-1 text-gray-400 hover:text-green-500 transition-colors py-1">
        <i data-lucide="clipboard-list" class="w-6 h-6"></i>
        <span class="text-[11px] font-medium">Órdenes</span>
      </button>
      <button data-nav="earnings" class="nav-btn flex flex-col items-center gap-1 text-gray-400 hover:text-green-500 transition-colors py-1">
        <i data-lucide="wallet" class="w-6 h-6"></i>
        <span class="text-[11px] font-medium">Ganancias</span>
      </button>
      <button data-nav="profile" class="nav-btn flex flex-col items-center gap-1 text-gray-400 hover:text-green-500 transition-colors py-1">
        <i data-lucide="user" class="w-6 h-6"></i>
        <span class="text-[11px] font-medium">Perfil</span>
      </button>
    </div>

    <!-- Pantalla de Orden Activa (flotante, sobre todo) -->
    <div id="activeOrderScreen" class="hidden fixed inset-0 z-50 bg-black overflow-y-auto pb-6">
      <div class="sticky top-0 bg-black/95 backdrop-blur-sm p-4 flex justify-between items-center border-b border-gray-800 z-10">
        <button id="closeActiveOrderBtn" class="bg-gray-800 p-2 rounded-full">
          <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </button>
        <span class="font-bold text-white">Pedido en curso</span>
        <div class="w-7"></div>
      </div>
      <div class="p-4 space-y-5">
        <!-- Mapa de ruta -->
        <div id="activeOrderMap" class="map-container h-52 w-full rounded-xl"></div>
        <!-- Estado actual + botones de flujo -->
        <div class="bg-[#121212] rounded-xl p-4 border border-gray-800">
          <p class="text-gray-400 text-xs mb-1">Estado del pedido</p>
          <div class="flex items-center gap-2">
            <span id="orderStatusBadge" class="bg-yellow-600/30 text-yellow-300 px-3 py-1 rounded-full text-sm font-semibold">🛵 Recogiendo</span>
          </div>
          <div class="flex gap-3 mt-4 flex-wrap">
            <button id="btnPickup" class="flex-1 bg-blue-600 hover:bg-blue-500 py-3 rounded-xl font-semibold tap-btn text-sm">📦 Ya recogí</button>
            <button id="btnEnRoute" class="flex-1 bg-indigo-600 hover:bg-indigo-500 py-3 rounded-xl font-semibold tap-btn text-sm">🚚 En camino</button>
            <button id="btnDeliver" class="flex-1 bg-green-600 hover:bg-green-500 py-3 rounded-xl font-semibold tap-btn text-sm">✅ Entregado</button>
          </div>
        </div>
        <!-- Información cliente + notas -->
        <div class="bg-[#121212] rounded-xl p-4 border border-gray-800">
          <h3 class="font-bold flex items-center gap-2 mb-2"><i data-lucide="user-round" class="w-4 h-4"></i> Cliente</h3>
          <p id="clientName" class="text-white font-medium">-</p>
          <p id="clientPhone" class="text-gray-400 text-sm">-</p>
          <p id="clientNotes" class="text-gray-300 text-sm bg-gray-800/50 p-2 rounded-md mt-2">📝 Nota: -</p>
        </div>
        <!-- Chat con cliente -->
        <div class="bg-[#121212] rounded-xl p-4 border border-gray-800">
          <h3 class="font-bold flex items-center gap-2 mb-2"><i data-lucide="message-circle" class="w-4 h-4"></i> Chat con el cliente</h3>
          <div id="chatMessagesContainer" class="h-48 overflow-y-auto flex flex-col gap-2 pr-1 mb-3 custom-scroll"></div>
          <div class="flex gap-2">
            <input type="text" id="chatInput" placeholder="Escribe un mensaje..." class="flex-1 bg-gray-800 border-none rounded-xl px-4 py-2 text-white text-sm focus:ring-1 focus:ring-green-500">
            <button id="sendChatBtn" class="bg-green-600 px-4 rounded-xl tap-btn">Enviar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // -------------------- ESTADO GLOBAL --------------------
    let isLoggedIn = false;
    let currentDriver = {
      name: "Carlos Pérez",
      rating: 4.8,
      profileImg: "https://randomuser.me/api/portraits/men/32.jpg",
      phone: "+57 310 555 1234"
    };
    let dailyEarnings = 48750;      // COP
    let dailyDeliveries = 4;
    let isAvailable = true;         // verde (disponible) / rojo (ocupado)
    
    // Órdenes mock cercanas
    let availableOrders = [
      { id: 101, distance: "0.9 km", pay: 6200, tip: 2300, totalPay: 8500, time: "12 min", pickup: "Cra 45 #23-45, Laureles", delivery: "Cll 10 #34-12, Belén", clientName: "Ana García", clientPhone: "311 222 3344", notes: "Casa reja blanca, segundo piso", latPickup: 6.2476, lngPickup: -75.5658, latDelivery: 6.2306, lngDelivery: -75.5856 },
      { id: 102, distance: "1.2 km", pay: 7500, tip: 1500, totalPay: 9000, time: "18 min", pickup: "Cl 50 #67-89, Estadio", delivery: "Cra 70 #34-12, La América", clientName: "Miguel Torres", clientPhone: "300 987 6543", notes: "Portón verde, timbre 2", latPickup: 6.2592, lngPickup: -75.5907, latDelivery: 6.2453, lngDelivery: -75.6152 },
      { id: 103, distance: "2.1 km", pay: 8200, tip: 2000, totalPay: 10200, time: "22 min", pickup: "Transversal 39A #56-34, El Poblado", delivery: "Cll 5 Sur #25-12, Envigado", clientName: "Laura Mejía", clientPhone: "312 456 7890", notes: "Apartamento 304, marcar portería", latPickup: 6.2012, lngPickup: -75.5711, latDelivery: 6.1703, lngDelivery: -75.5902 },
      { id: 104, distance: "0.6 km", pay: 5400, tip: 1600, totalPay: 7000, time: "9 min", pickup: "Cra 82 #44-12, Floresta", delivery: "Cll 33 #78-21, Belén", clientName: "Julián Ríos", clientPhone: "305 111 2233", notes: "Entregar en recepción", latPickup: 6.2489, lngPickup: -75.6043, latDelivery: 6.2399, lngDelivery: -75.5991 }
    ];
    
    let activeOrder = null;           // orden aceptada
    let currentOrderStatus = "recogiendo";  // recogiendo, en_camino, entregado
    let currentChatMessages = [];      // {text, sender}
    
    // Mapas
    let homeMap = null;
    let activeMapInstance = null;
    let userLocation = { lat: 6.2525, lng: -75.5718 };  // Medellín por defecto
    
    // Navegación actual
    let currentNav = "home";
    
    // DOM elements
    const loginScreen = document.getElementById('loginScreen');
    const appContainer = document.getElementById('appContainer');
    const mainView = document.getElementById('mainViewContainer');
    const activeOrderScreenDiv = document.getElementById('activeOrderScreen');
    const bottomNav = document.getElementById('bottomNav');
    const dynamicHeader = document.getElementById('dynamicHeader');
    
    // Helper para refrescar íconos Lucide
    function refreshIcons() {
      if (window.lucide) lucide.createIcons();
    }
    
    // Actualizar Header con datos del conductor
    function updateHeaderUI() {
      document.getElementById('driverName').innerText = currentDriver.name;
      document.getElementById('driverRating').innerText = currentDriver.rating;
      document.getElementById('profileAvatar').src = currentDriver.profileImg;
      refreshIcons();
    }
    
    // -------------------- VISTA INICIO (dashboard principal) --------------------
    function renderHomeView() {
      mainView.innerHTML = `
        <!-- Botón Disponible / Ocupado -->
        <div class="mb-5">
          <button id="availabilityBtn" class="w-full tap-btn ${isAvailable ? 'bg-green-600 hover:bg-green-500' : 'bg-red-600 hover:bg-red-500'} py-4 rounded-2xl shadow-lg flex items-center justify-center gap-2 text-white font-bold text-xl transition-smooth">
            <i data-lucide="circle" class="w-5 h-5 fill-current"></i> ${isAvailable ? 'Estoy Disponible' : 'Estoy Ocupado'}
          </button>
        </div>
        <!-- Tarjeta Ganancias del día -->
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-4 mb-5 flex justify-between items-center border border-gray-700 shadow-md">
          <div>
            <p class="text-gray-400 text-xs">Ganancias hoy</p>
            <p class="text-2xl font-bold text-white">$${dailyEarnings.toLocaleString()}</p>
            <p class="text-green-400 text-sm">${dailyDeliveries} entregas</p>
          </div>
          <i data-lucide="trending-up" class="w-10 h-10 text-green-500 opacity-80"></i>
        </div>
        <!-- Mapa ubicación actual -->
        <div class="mb-5">
          <div id="homeMapContainer" class="map-container w-full h-48 rounded-xl"></div>
        </div>
        <!-- Lista de Órdenes disponibles -->
        <div>
          <div class="flex justify-between items-center mb-2">
            <h3 class="font-semibold text-white flex items-center gap-1"><i data-lucide="shopping-bag" class="w-4 h-4"></i> Órdenes cercanas</h3>
            <span class="text-xs text-gray-400">${availableOrders.length} disponibles</span>
          </div>
          <div class="orders-scroll max-h-80 overflow-y-auto space-y-3 pr-1 custom-scroll">
            ${availableOrders.length === 0 ? '<div class="text-center text-gray-500 py-10">✨ No hay órdenes cercanas</div>' : 
              availableOrders.map(order => `
                <div class="bg-[#131313] rounded-xl p-4 border border-gray-800 order-card transition-all" data-order-id="${order.id}">
                  <div class="flex justify-between items-start">
                    <div><span class="text-green-400 font-bold">$${order.totalPay.toLocaleString()}</span> <span class="text-gray-400 text-xs">(+$${order.tip} propina)</span></div>
                    <span class="bg-gray-800 text-xs px-2 py-1 rounded-full">⏱️ ${order.time}</span>
                  </div>
                  <div class="mt-2 text-sm flex items-center gap-1 text-gray-300"><i data-lucide="map-pin" class="w-3 h-3"></i> ${order.distance}</div>
                  <div class="text-xs text-gray-400 mt-1"><span class="font-medium text-gray-300">Recoger:</span> ${order.pickup}</div>
                  <div class="text-xs text-gray-400"><span class="font-medium text-gray-300">Entregar:</span> ${order.delivery}</div>
                  <div class="flex gap-3 mt-4">
                    <button class="accept-order-btn bg-green-600 hover:bg-green-500 flex-1 py-2.5 rounded-xl font-semibold tap-btn transition" data-id="${order.id}">✅ Aceptar</button>
                    <button class="reject-order-btn bg-gray-700 hover:bg-gray-600 flex-1 py-2.5 rounded-xl font-semibold tap-btn transition" data-id="${order.id}">❌ Rechazar</button>
                  </div>
                </div>
              `).join('')}
          </div>
        </div>
      `;
      
      // Mapa en home
      setTimeout(() => {
        const homeMapDiv = document.getElementById('homeMapContainer');
        if (homeMapDiv && !homeMap) {
          homeMap = L.map('homeMapContainer').setView([userLocation.lat, userLocation.lng], 14);
          L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
          }).addTo(homeMap);
          L.marker([userLocation.lat, userLocation.lng]).addTo(homeMap).bindPopup('📍 Tu ubicación').openPopup();
        } else if (homeMap) {
          homeMap.setView([userLocation.lat, userLocation.lng]);
        }
        refreshIcons();
      }, 100);
      
      // Eventos dinámicos
      const availabilityBtn = document.getElementById('availabilityBtn');
      if (availabilityBtn) {
        availabilityBtn.addEventListener('click', () => {
          isAvailable = !isAvailable;
          renderHomeView();
        });
      }
      
      document.querySelectorAll('.accept-order-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const id = parseInt(btn.dataset.id);
          const order = availableOrders.find(o => o.id === id);
          if (order) {
            const card = btn.closest('.order-card');
            card.classList.add('animate-accept');
            setTimeout(() => {
              acceptOrder(order);
            }, 180);
          }
        });
      });
      
      document.querySelectorAll('.reject-order-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = parseInt(btn.dataset.id);
          availableOrders = availableOrders.filter(o => o.id !== id);
          renderHomeView();
        });
      });
      refreshIcons();
    }
    
    // Aceptar orden: pasa a activa
    function acceptOrder(order) {
      availableOrders = availableOrders.filter(o => o.id !== order.id);
      activeOrder = { ...order, status: "recogiendo" };
      currentOrderStatus = "recogiendo";
      // Inicializar chat
      currentChatMessages = [
        { text: `Hola, soy ${currentDriver.name}, voy en camino a recoger tu pedido.`, sender: "driver" },
        { text: "Perfecto, cualquier cambio avísame", sender: "client" }
      ];
      showActiveOrderScreen();
      if (currentNav === "home") renderHomeView();
      else if (currentNav === "orders") renderOrdersView();
    }
    
    // Mostrar pantalla orden activa + mapa ruta
    function showActiveOrderScreen() {
      if (!activeOrder) return;
      activeOrderScreenDiv.classList.remove('hidden');
      dynamicHeader.classList.add('hidden');
      bottomNav.classList.add('hidden');
      renderActiveOrderUI();
    }
    
    function hideActiveOrderScreen() {
      activeOrderScreenDiv.classList.add('hidden');
      dynamicHeader.classList.remove('hidden');
      bottomNav.classList.remove('hidden');
      if (activeMapInstance) { activeMapInstance.remove(); activeMapInstance = null; }
      if (currentNav === "home") renderHomeView();
      else if (currentNav === "orders") renderOrdersView();
      else if (currentNav === "earnings") renderEarningsView();
      else if (currentNav === "profile") renderProfileView();
    }
    
    function renderActiveOrderUI() {
      if (!activeOrder) return;
      document.getElementById('clientName').innerText = activeOrder.clientName;
      document.getElementById('clientPhone').innerText = activeOrder.clientPhone;
      document.getElementById('clientNotes').innerHTML = `📝 Nota: ${activeOrder.notes}`;
      updateOrderStatusBadge();
      renderChatMessages();
      
      setTimeout(() => {
        const mapDiv = document.getElementById('activeOrderMap');
        if (mapDiv && !activeMapInstance) {
          activeMapInstance = L.map('activeOrderMap').setView([activeOrder.latPickup, activeOrder.lngPickup], 13);
          L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(activeMapInstance);
          L.marker([activeOrder.latPickup, activeOrder.lngPickup]).addTo(activeMapInstance).bindPopup('📍 Recogida').openPopup();
          L.marker([activeOrder.latDelivery, activeOrder.lngDelivery]).addTo(activeMapInstance).bindPopup('🏠 Entrega');
          L.polyline([[activeOrder.latPickup, activeOrder.lngPickup], [activeOrder.latDelivery, activeOrder.lngDelivery]], { color: '#22c55e', weight: 5, opacity: 0.8 }).addTo(activeMapInstance);
          activeMapInstance.fitBounds([[activeOrder.latPickup, activeOrder.lngPickup], [activeOrder.latDelivery, activeOrder.lngDelivery]]);
        }
        refreshIcons();
      }, 80);
      
      // Botones de flujo
      document.getElementById('btnPickup').onclick = () => { if (currentOrderStatus === "recogiendo") changeOrderStatus("en_camino"); };
      document.getElementById('btnEnRoute').onclick = () => { if (currentOrderStatus !== "entregado") changeOrderStatus("en_camino"); };
      document.getElementById('btnDeliver').onclick = () => { if (currentOrderStatus === "en_camino") changeOrderStatus("entregado"); };
      refreshIcons();
    }
    
    function changeOrderStatus(newStatus) {
      if (newStatus === "en_camino" && currentOrderStatus === "recogiendo") {
        currentOrderStatus = "en_camino";
        updateOrderStatusBadge();
      } else if (newStatus === "entregado" && currentOrderStatus === "en_camino") {
        currentOrderStatus = "entregado";
        // completar orden: sumar ganancias
        dailyEarnings += activeOrder.totalPay;
        dailyDeliveries += 1;
        activeOrder = null;
        hideActiveOrderScreen();
        if (currentNav === "home") renderHomeView();
        else if (currentNav === "earnings") renderEarningsView();
        else if (currentNav === "orders") renderOrdersView();
        return;
      }
      updateOrderStatusBadge();
    }
    
    function updateOrderStatusBadge() {
      const badge = document.getElementById('orderStatusBadge');
      if (currentOrderStatus === "recogiendo") badge.innerHTML = "🛵 Recogiendo pedido";
      else if (currentOrderStatus === "en_camino") badge.innerHTML = "🚚 En camino a la entrega";
      else badge.innerHTML = "✅ Entregado";
    }
    
    function renderChatMessages() {
      const container = document.getElementById('chatMessagesContainer');
      if (!container) return;
      container.innerHTML = currentChatMessages.map(msg => `
        <div class="${msg.sender === 'driver' ? 'chat-bubble-driver' : 'chat-bubble-client'}">
          ${msg.text}
        </div>
      `).join('');
      container.scrollTop = container.scrollHeight;
    }
    
    // Enviar mensaje (driver) + respuesta simulada cliente
    function setupChatEvents() {
      const sendBtn = document.getElementById('sendChatBtn');
      const chatInput = document.getElementById('chatInput');
      if (!sendBtn) return;
      sendBtn.onclick = () => {
        const text = chatInput.value.trim();
        if (text === "") return;
        currentChatMessages.push({ text: text, sender: "driver" });
        renderChatMessages();
        chatInput.value = '';
        // respuesta mock cliente después de 1s
        setTimeout(() => {
          currentChatMessages.push({ text: "Listo, gracias por la info 👍", sender: "client" });
          renderChatMessages();
        }, 900);
      };
    }
    
    // -------------------- OTRAS VISTAS (Órdenes, Ganancias, Perfil) --------------------
    function renderOrdersView() {
      mainView.innerHTML = `
        <div class="space-y-5">
          <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="clipboard-list"></i> Mis Órdenes</h2>
          ${activeOrder ? `
            <div class="bg-green-900/30 border border-green-500/50 rounded-xl p-4">
              <p class="text-green-400 font-bold">🟢 Orden en curso</p>
              <p class="text-sm text-gray-300">Cliente: ${activeOrder.clientName} | ${activeOrder.distance}</p>
              <button id="goToActiveOrderBtn" class="mt-3 bg-green-600 w-full py-2.5 rounded-xl font-semibold tap-btn">Ver orden activa →</button>
            </div>
          ` : '<div class="bg-gray-800/30 p-5 rounded-xl text-center text-gray-400">No tienes órdenes activas</div>'}
          <div class="bg-[#131313] rounded-xl p-4 mt-2">
            <h3 class="font-semibold mb-2">📋 Historial hoy</h3>
            <p class="text-gray-300 text-sm">Has completado <span class="text-green-400 font-bold">${dailyDeliveries}</span> entregas hoy por <span class="text-green-400">$${dailyEarnings.toLocaleString()}</span></p>
          </div>
        </div>
      `;
      const goBtn = document.getElementById('goToActiveOrderBtn');
      if (goBtn) goBtn.addEventListener('click', () => { if (activeOrder) showActiveOrderScreen(); });
      refreshIcons();
    }
    
    function renderEarningsView() {
      mainView.innerHTML = `
        <div class="space-y-5">
          <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="wallet"></i> Ganancias</h2>
          <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 text-center">
            <p class="text-gray-300 text-sm">Total acumulado hoy</p>
            <p class="text-4xl font-black text-green-400">$${dailyEarnings.toLocaleString()}</p>
            <p class="text-gray-400 mt-1">${dailyDeliveries} entregas completadas</p>
          </div>
          <div class="bg-[#131313] rounded-xl p-4">
            <p class="font-medium">💰 Desglose aproximado</p>
            <div class="flex justify-between mt-2"><span>Base por entregas</span><span>$${(dailyEarnings - (dailyDeliveries * 2000)).toLocaleString()}</span></div>
            <div class="flex justify-between"><span>Propinas estimadas</span><span>$${(dailyDeliveries * 1800).toLocaleString()}</span></div>
          </div>
        </div>
      `;
      refreshIcons();
    }
    
    function renderProfileView() {
      mainView.innerHTML = `
        <div class="space-y-6">
          <div class="flex flex-col items-center">
            <img src="${currentDriver.profileImg}" class="w-24 h-24 rounded-full border-4 border-green-500">
            <h2 class="text-xl font-bold mt-2">${currentDriver.name}</h2>
            <div class="flex items-center gap-1 text-yellow-400"><i data-lucide="star" class="fill-yellow-400 w-4 h-4"></i> ${currentDriver.rating}</div>
            <p class="text-gray-400 text-sm">${currentDriver.phone}</p>
          </div>
          <div class="bg-[#131313] rounded-xl p-4">
            <p class="flex justify-between"><span class="text-gray-300">📦 Entregas totales</span><span class="font-medium">${dailyDeliveries + 28}</span></p>
            <p class="flex justify-between mt-2"><span class="text-gray-300">⏱️ Tiempo activo</span><span>2 semanas</span></p>
            <button id="logoutProfileBtn" class="w-full mt-6 bg-red-800/40 text-red-400 py-3 rounded-xl tap-btn">Cerrar sesión</button>
          </div>
        </div>
      `;
      document.getElementById('logoutProfileBtn')?.addEventListener('click', logout);
      refreshIcons();
    }
    
    // Navegación
    function setActiveNav(nav) {
      currentNav = nav;
      if (nav === 'home') renderHomeView();
      else if (nav === 'orders') renderOrdersView();
      else if (nav === 'earnings') renderEarningsView();
      else if (nav === 'profile') renderProfileView();
      document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('text-green-500'));
      const activeBtn = document.querySelector(`[data-nav="${nav}"]`);
      if (activeBtn) activeBtn.classList.add('text-green-500');
      refreshIcons();
    }
    
    // Cierre de sesión
    function logout() {
      isLoggedIn = false;
      activeOrder = null;
      loginScreen.classList.remove('hidden');
      appContainer.classList.add('hidden');
      if (homeMap) { homeMap.remove(); homeMap = null; }
      if (activeMapInstance) { activeMapInstance.remove(); activeMapInstance = null; }
    }
    
    // Login con OTP
    document.getElementById('loginBtn').addEventListener('click', () => {
      const otp = document.getElementById('otpCode').value.trim();
      if (otp === "123456") {
        isLoggedIn = true;
        loginScreen.classList.add('hidden');
        appContainer.classList.remove('hidden');
        updateHeaderUI();
        setActiveNav("home");
        // Geolocalización real si se permite
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(pos => {
            userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            if (homeMap) homeMap.setView([userLocation.lat, userLocation.lng]);
          }, () => {});
        }
      } else {
        alert("Código incorrecto. Usa 123456");
      }
    });
    
    document.getElementById('logoutBtnHeader').addEventListener('click', logout);
    document.getElementById('closeActiveOrderBtn').addEventListener('click', hideActiveOrderScreen);
    
    // Inicializar eventos de chat cada vez que se abre la pantalla activa (delegación)
    document.addEventListener('click', (e) => {
      if (e.target.id === 'sendChatBtn' || e.target.parentElement?.id === 'sendChatBtn') {
        if (activeOrderScreenDiv.classList.contains('hidden')) return;
        const input = document.getElementById('chatInput');
        if (input && input.value.trim()) {
          currentChatMessages.push({ text: input.value, sender: "driver" });
          renderChatMessages();
          input.value = '';
          setTimeout(() => {
            currentChatMessages.push({ text: "Gracias por la actualización 👍", sender: "client" });
            renderChatMessages();
          }, 1000);
        }
      }
    });
    
    // Bottom navigation listeners
    document.querySelectorAll('.nav-btn').forEach(btn => {
      btn.addEventListener('click', () => setActiveNav(btn.dataset.nav));
    });
    
    window.addEventListener('load', () => {
      refreshIcons();
    });
  </script>
</body>
</html>