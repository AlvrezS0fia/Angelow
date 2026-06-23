 // DATOS DE PRUEBA
    const datosApp = {
      conductor: {
        nombre: "Juan Delgado",
        vehiculo: "Moto",
        placa: "ABC-123",
        calificacion: 5.0,
        totalEntregas: 127,
        totalGanancias: 635000,
        estado: "online"
      },
      
      pedidosDisponibles: [
        {
          id: "ORD-012",
          cliente: "Ana Martínez",
          direccion: "Calle 100 #15-30, Centro",
          telefono: "3007788990",
          productos: ["Conjunto Deportivo", "Body Niño"],
          total: 1799800,
          estado: "ready",
          prioridad: "high",
          zona: "centro",
          horaLimite: "Entrega antes de las 14:00",
          distancia: "2.3 km",
          ganancia: 8000
        },
        {
          id: "ORD-013",
          cliente: "Pedro Ramírez",
          direccion: "Carrera 7 #120-50, Norte",
          telefono: "3004455667",
          productos: ["Set Bebé"],
          total: 899900,
          estado: "ready",
          prioridad: "normal",
          zona: "norte",
          horaLimite: "Entrega antes de las 16:00",
          distancia: "3.5 km",
          ganancia: 6000
        },
        {
          id: "ORD-014",
          cliente: "Laura Sánchez",
          direccion: "Avenida 19 #134-25, Sur",
          telefono: "3001112233",
          productos: ["Conjunto Infantil", "Set Falda", "Body Negro"],
          total: 2699700,
          estado: "pending",
          prioridad: "normal",
          zona: "sur",
          horaLimite: "Entrega antes de las 18:00",
          distancia: "4.2 km",
          ganancia: 10000
        }
      ],
      
      pedidosActivos: [
        {
          id: "ORD-015",
          cliente: "Carlos López",
          direccion: "Transversal 23 #45-89, Norte",
          telefono: "3005566778",
          productos: ["Jogger Niño", "Conjunto Size"],
          total: 1799800,
          estado: "picked",
          prioridad: "normal",
          zona: "norte",
          horaLimite: "Entrega antes de las 15:30",
          distancia: "2.8 km",
          ganancia: 7000,
          recogidoA: "14:15"
        }
      ],
      
      historialEntregas: [
        { id: "ORD-011", cliente: "María Rodríguez", fecha: "Hoy, 10:30", zona: "centro", ganancia: 5000, calificacion: 5 },
        { id: "ORD-010", cliente: "David Torres", fecha: "Ayer, 18:45", zona: "sur", ganancia: 6000, calificacion: 5 },
        { id: "ORD-009", cliente: "Sofía Castro", fecha: "Ayer, 15:20", zona: "norte", ganancia: 7000, calificacion: 4 },
        { id: "ORD-008", cliente: "Andrés Gómez", fecha: "27 Oct, 12:10", zona: "centro", ganancia: 5000, calificacion: 5 }
      ],
      
      notificaciones: [
        { id: 1, titulo: "Nuevo pedido disponible", mensaje: "ORD-012 listo pa recoger en Centro", tiempo: "Hace 5 min", leida: false },
        { id: 2, titulo: "Pago recibido", mensaje: "$5,000 por entrega ORD-011", tiempo: "Hace 30 min", leida: false },
        { id: 3, titulo: "Actualización de zona", mensaje: "Nuevas áreas disponibles en Norte", tiempo: "Hace 2 horas", leida: false }
      ]
    };

    // VARIABLES GLOBALES
    let map;
    let startMarker = null;
    let endMarker = null;
    let driverMarker = null;
    let userMarker = null;
    let routingControl = null;
    let startPoint = null;
    let endPoint = null;
    let routeCoordinates = [];
    let trackingInterval = null;
    let appState = {
      routeCalculated: false,
      journeyStarted: false,
      calculating: false
    };

    // FUNCIONES ÚTILES
    function formatearDinero(monto) {
      return '$' + monto.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function obtenerIniciales(nombre) {
      if (!nombre) return '?';
      return nombre.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }

    // SISTEMA DE TOAST
    function mostrarToast(opciones) {
      const titulo = opciones.titulo || "";
      const mensaje = opciones.mensaje || "";
      const tipo = opciones.tipo || "info";
      const duracion = opciones.duracion || 4000;
      
      const contenedor = document.getElementById("toastContainer");
      const toast = document.createElement("div");
      toast.className = `toast ${tipo}`;
      
      const iconos = {
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-times-circle',
        info: 'fa-info-circle'
      };
      
      toast.innerHTML = `
        <div class="toast-icon">
          <i class="fas ${iconos[tipo]}" aria-hidden="true"></i>
        </div>
        <div class="toast-content">
          ${titulo ? `<div class="toast-title">${titulo}</div>` : ""}
          <div class="toast-message">${mensaje}</div>
        </div>
        <button class="toast-close" aria-label="Cerrar notificación">×</button>
      `;
      
      contenedor.appendChild(toast);
      setTimeout(() => toast.classList.add("show"), 10);
      
      toast.querySelector(".toast-close").onclick = () => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 400);
      };
      
      setTimeout(() => {
        if (toast.parentNode) {
          toast.classList.remove("show");
          setTimeout(() => toast.remove(), 400);
        }
      }, duracion);
    }

    // FUNCIONES DEL MAPA
    function iniciarMapa() {
      // Inicializar mapa centrado en Medellín
      map = L.map('map').setView([6.2442, -75.5812], 14);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
      }).addTo(map);

      // Obtener ubicación del usuario
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            map.setView([lat, lng], 16);
            
            // Crear marcador del usuario
            const userIcon = L.icon({
              iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
              shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
              iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            
            userMarker = L.marker([lat, lng], { icon: userIcon }).addTo(map)
              .bindPopup('📍 Tu ubicación actual').openPopup();
            
            startPoint = { lat, lng };
            document.getElementById('startAddress').value = 'Tu ubicación actual';
            
            mostrarToast({
              titulo: '📍 GPS Activado',
              mensaje: 'Ubicación obtenida correctamente',
              tipo: 'success'
            });
          },
          (error) => {
            let mensaje = 'Error al obtener la ubicación';
            if (error.code === error.PERMISSION_DENIED) mensaje = 'Permiso de ubicación denegado';
            mostrarToast({ titulo: 'Error GPS', mensaje, tipo: 'error' });
          }
        );
      }
    }

    // Función para geocodificar direcciones
    async function geocodificarDireccion(direccion) {
      try {
        const response = await fetch(
          `https://nominatim.openstreetmap.org/search?` +
          `q=${encodeURIComponent(direccion + ', Medellín, Colombia')}&` +
          `format=json&limit=1&countrycodes=co`,
          { headers: { 'User-Agent': 'AngelowApp/1.0' } }
        );
        
        const data = await response.json();
        
        if (data && data.length > 0) {
          return {
            lat: parseFloat(data[0].lat),
            lng: parseFloat(data[0].lon),
            display_name: data[0].display_name
          };
        } else {
          throw new Error('Dirección no encontrada');
        }
      } catch (error) {
        throw new Error('Error al buscar la dirección');
      }
    }

    // Función para calcular ruta
    function calcularRuta() {
      const endAddress = document.getElementById('endAddress').value.trim();
      
      if (!startPoint) {
        mostrarToast({ titulo: 'Error', mensaje: 'Esperando ubicación GPS...', tipo: 'warning' });
        return;
      }
      
      if (!endAddress) {
        mostrarToast({ titulo: 'Error', mensaje: 'Ingresa una dirección de destino', tipo: 'warning' });
        return;
      }
      
      if (appState.calculating) return;
      
      appState.calculating = true;
      actualizarEstadoMapa('calculating', 'Calculando Ruta', 'Buscando la mejor ruta...');
      
      geocodificarDireccion(endAddress)
        .then(endLocation => {
          endPoint = L.latLng(endLocation.lat, endLocation.lng);
          
          // Crear marcador de destino
          const endIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
          });
          
          if (endMarker) map.removeLayer(endMarker);
          endMarker = L.marker(endPoint, { icon: endIcon }).addTo(map)
            .bindPopup(`<b>🎯 Destino</b><br>${endLocation.display_name}`);
          
          // Crear marcador de inicio
          const startIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
          });
          
          if (startMarker) map.removeLayer(startMarker);
          startMarker = L.marker([startPoint.lat, startPoint.lng], { icon: startIcon }).addTo(map)
            .bindPopup('<b>🚚 Inicio</b><br>Punto de partida');
          
          // Calcular ruta con Leaflet Routing Machine
          if (routingControl) map.removeControl(routingControl);
          
          routingControl = L.Routing.control({
            waypoints: [
              L.latLng(startPoint.lat, startPoint.lng),
              endPoint
            ],
            routeWhileDragging: false,
            show: false,
            lineOptions: {
              styles: [{ color: '#5E9DE6', weight: 5, opacity: 0.8 }]
            },
            createMarker: function() { return null; }
          }).addTo(map);
          
          routingControl.on('routesfound', function(e) {
            const route = e.routes[0];
            routeCoordinates = route.coordinates;
            
            // Ajustar vista para mostrar toda la ruta
            const group = new L.featureGroup([startMarker, endMarker]);
            map.fitBounds(group.getBounds().pad(0.1), { animate: true, duration: 1 });
            
            appState.routeCalculated = true;
            
            const distancia = (route.summary.totalDistance / 1000).toFixed(1);
            const tiempo = Math.round(route.summary.totalTime / 60);
            
            document.getElementById('routeDistance').textContent = `${distancia} km`;
            document.getElementById('routeTime').textContent = `${tiempo} min`;
            document.getElementById('totalDistance').textContent = `${distancia} km`;
            document.getElementById('destinoPaso').textContent = endAddress;
            document.getElementById('proximoPaso').textContent = 'En camino...';
            
            actualizarEstadoMapa('ready', 'Ruta Lista', `Distancia: ${distancia} km - Tiempo: ${tiempo} min`);
            document.getElementById('limpiarRuta').disabled = false;
            
            // Simular inicio de viaje después de 3 segundos
            setTimeout(() => iniciarSimulacionViaje(routeCoordinates, tiempo), 3000);
          });
          
          routingControl.on('routingerror', function() {
            actualizarEstadoMapa('error', 'Error', 'No se pudo calcular la ruta');
            appState.calculating = false;
          });
        })
        .catch(error => {
          actualizarEstadoMapa('error', 'Error', error.message);
          appState.calculating = false;
        });
    }

    // Función para simular el viaje del repartidor
    function iniciarSimulacionViaje(coordenadas, tiempoTotal) {
      if (trackingInterval) clearInterval(trackingInterval);
      
      // Crear marcador del repartidor
      const driverIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
      });
      
      if (driverMarker) map.removeLayer(driverMarker);
      driverMarker = L.marker([startPoint.lat, startPoint.lng], { icon: driverIcon }).addTo(map)
        .bindPopup('<b>🚛 Repartidor</b><br>Iniciando viaje...').openPopup();
      
      let index = 0;
      const totalSteps = coordenadas.length;
      const intervalTime = Math.max(50, (tiempoTotal * 60 * 1000) / totalSteps / 10); // Simulación rápida
      
      appState.journeyStarted = true;
      actualizarEstadoMapa('traveling', 'En Camino', 'Repartidor en movimiento...');
      
      trackingInterval = setInterval(() => {
        if (index < totalSteps) {
          const pos = coordenadas[index];
          driverMarker.setLatLng(pos);
          
          // Actualizar vista cada cierto tiempo
          if (index % 10 === 0) {
            map.setView(pos, map.getZoom());
          }
          
          // Actualizar progreso
          const progreso = Math.round((index / totalSteps) * 100);
          driverMarker.setPopupContent(`<b>🚛 Repartidor</b><br>Progreso: ${progreso}%`);
          
          index++;
        } else {
          // Viaje completado
          clearInterval(trackingInterval);
          actualizarEstadoMapa('completed', 'Entregado', '¡Pedido entregado exitosamente!');
          driverMarker.setPopupContent('<b>✅ Entregado</b><br>¡Pedido completado!').openPopup();
          
          mostrarToast({
            titulo: '✅ Entrega Completada',
            mensaje: 'Has llegado al destino',
            tipo: 'success'
          });
          
          // Reset después de 5 segundos
          setTimeout(() => {
            limpiarRuta();
          }, 5000);
        }
      }, intervalTime);
    }

    // Función para actualizar el estado del mapa
    function actualizarEstadoMapa(tipo, titulo, mensaje) {
      const statusPoint = document.getElementById('statusPoint');
      const statusTitle = document.getElementById('statusTitle');
      const statusMessage = document.getElementById('statusMessage');
      
      statusPoint.className = `mapa-status-punto ${tipo}`;
      statusTitle.textContent = titulo;
      statusMessage.textContent = mensaje;
    }

    // Función para limpiar la ruta
    function limpiarRuta() {
      if (trackingInterval) {
        clearInterval(trackingInterval);
        trackingInterval = null;
      }
      
      if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
      }
      
      if (endMarker) {
        map.removeLayer(endMarker);
        endMarker = null;
      }
      
      if (driverMarker) {
        map.removeLayer(driverMarker);
        driverMarker = null;
      }
      
      document.getElementById('endAddress').value = '';
      document.getElementById('routeDistance').textContent = '--';
      document.getElementById('routeTime').textContent = '--';
      document.getElementById('totalDistance').textContent = '8.5 km';
      document.getElementById('destinoPaso').textContent = 'Selecciona un destino';
      document.getElementById('proximoPaso').textContent = 'En espera';
      
      actualizarEstadoMapa('waiting', 'Esperando', 'Ingresa una dirección para comenzar');
      document.getElementById('limpiarRuta').disabled = true;
      
      appState.routeCalculated = false;
      appState.journeyStarted = false;
      appState.calculating = false;
    }

    // FUNCIONES DEL PANEL PRINCIPAL
    function iniciarPanel() {
      document.getElementById('driverName').textContent = datosApp.conductor.nombre;
      document.getElementById('driverAvatar').textContent = obtenerIniciales(datosApp.conductor.nombre);
      
      let estadoTexto = datosApp.conductor.estado === 'online' ? 'En línea' : 'Offline';
      document.getElementById('driverStatus').innerHTML = `
        <span class="punto-verde" aria-hidden="true"></span>
        ${estadoTexto} • ${datosApp.conductor.vehiculo}
      `;
      
      document.getElementById('totalDeliveries').textContent = datosApp.conductor.totalEntregas;
      document.getElementById('totalEarnings').textContent = formatearDinero(datosApp.conductor.totalGanancias);
      document.getElementById('activeDeliveries').textContent = datosApp.pedidosActivos.length;
      document.getElementById('rating').textContent = datosApp.conductor.calificacion.toFixed(1);
      
      document.getElementById('availableBadge').textContent = datosApp.pedidosDisponibles.length;
      document.getElementById('activeCount').textContent = datosApp.pedidosActivos.length;
      
      renderizarPedidosDisponibles();
      renderizarPedidosActivos();
      renderizarHistorial();
      actualizarNotificaciones();
    }

    function renderizarPedidosDisponibles() {
      const contenedor = document.getElementById('availableOrders');
      
      contenedor.innerHTML = datosApp.pedidosDisponibles.map(p => {
        let clasePrioridad = p.prioridad === 'high' ? 'alta-prioridad' : '';
        let claseEstado = p.estado === 'pending' ? 'estado-pendiente' : 'estado-listo';
        let textoEstado = p.estado === 'pending' ? 'Pendiente' : 'Listo';
        let iconoEstado = p.estado === 'pending' ? 'fa-clock' : 'fa-check-circle';
        
        return `
        <div class="pedido-card ${clasePrioridad}">
          <div class="pedido-header">
            <div class="pedido-id">
              <i class="fas fa-hashtag"></i> ${p.id}
              ${p.prioridad === 'high' ? '<span class="pedido-urgente">URGENTE</span>' : ''}
            </div>
            <div class="pedido-estado ${claseEstado}">
              <i class="fas ${iconoEstado}"></i> ${textoEstado}
            </div>
          </div>
          
          <div class="pedido-detalles">
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-user"></i> Cliente</div>
              <div class="detalle-valor">${p.cliente}</div>
            </div>
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-map-marker-alt"></i> Zona</div>
              <div class="detalle-valor">${p.zona.toUpperCase()}</div>
            </div>
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-phone"></i> Teléfono</div>
              <div class="detalle-valor">${p.telefono}</div>
            </div>
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-road"></i> Distancia</div>
              <div class="detalle-valor">${p.distancia}</div>
            </div>
          </div>
          
          <div class="pedido-productos">
            <div class="productos-titulo">
              <i class="fas fa-box"></i> Productos:
            </div>
            <div class="productos-lista">
              ${p.productos.map(item => `<span class="producto-tag">${item}</span>`).join('')}
            </div>
          </div>
          
          <div class="pedido-total">Total: ${formatearDinero(p.total)}</div>
          
          <div class="pedido-acciones">
            ${p.estado === 'pending' ? `
              <button class="btn-accion btn-aceptar" onclick="aceptarPedido('${p.id}')">
                <i class="fas fa-check"></i> Aceptar
              </button>
              <button class="btn-accion btn-rechazar" onclick="rechazarPedido('${p.id}')">
                <i class="fas fa-times"></i> Rechazar
              </button>
            ` : `
              <button class="btn-accion btn-recoger" onclick="recogerPedido('${p.id}')">
                <i class="fas fa-truck"></i> Recoger
              </button>
            `}
          </div>
        </div>
      `}).join('');
    }

    function renderizarPedidosActivos() {
      const contenedor = document.getElementById('activeOrders');
      
      if (datosApp.pedidosActivos.length === 0) {
        contenedor.innerHTML = `
          <div class="estado-vacio">
            <div class="icono-vacio"><i class="fas fa-truck"></i></div>
            <div class="titulo-vacio">No hay pedidos activos</div>
            <div class="mensaje-vacio">Acepta un pedido para empezar</div>
          </div>
        `;
        return;
      }
      
      contenedor.innerHTML = datosApp.pedidosActivos.map(p => `
        <div class="pedido-card">
          <div class="pedido-header">
            <div class="pedido-id"><i class="fas fa-hashtag"></i> ${p.id}</div>
            <div class="pedido-estado estado-recogido">
              <i class="fas fa-truck"></i> En camino
            </div>
          </div>
          
          <div class="pedido-detalles">
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-user"></i> Cliente</div>
              <div class="detalle-valor">${p.cliente}</div>
            </div>
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
              <div class="detalle-valor">${p.direccion}</div>
            </div>
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-phone"></i> Teléfono</div>
              <div class="detalle-valor">${p.telefono}</div>
            </div>
            <div class="detalle-item">
              <div class="detalle-label"><i class="fas fa-clock"></i> Recogido</div>
              <div class="detalle-valor">${p.recogidoA}</div>
            </div>
          </div>
          
          <div class="pedido-acciones">
            <button class="btn-accion btn-entregar" onclick="entregarPedido('${p.id}')">
              <i class="fas fa-check-circle"></i> Entregar
            </button>
            <button class="btn-accion btn-contactar" onclick="contactarCliente('${p.id}')">
              <i class="fas fa-phone"></i> Llamar
            </button>
            <button class="btn-accion btn-contactar" onclick="usarDireccionEnMapa('${p.direccion}')">
              <i class="fas fa-map"></i> Ver ruta
            </button>
          </div>
        </div>
      `).join('');
    }

    function renderizarHistorial() {
      const contenedor = document.getElementById('deliveriesHistory');
      
      contenedor.innerHTML = datosApp.historialEntregas.map(h => {
        let estrellas = '';
        for(let i = 0; i < 5; i++) {
          estrellas += i < h.calificacion ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
        }
        
        return `
        <tr>
          <td><strong>${h.id}</strong></td>
          <td>${h.cliente}</td>
          <td>${h.fecha}</td>
          <td>${h.zona.toUpperCase()}</td>
          <td><span class="badge-ganancia">${formatearDinero(h.ganancia)}</span></td>
          <td><div class="estrellas">${estrellas} <span>(${h.calificacion}.0)</span></div></td>
        </tr>
      `}).join('');
    }

    function actualizarNotificaciones() {
      const noLeidas = datosApp.notificaciones.filter(n => !n.leida).length;
      document.getElementById('notificationCount').textContent = noLeidas;
    }

    // FUNCIONES DE ACCIÓN
    function aceptarPedido(idPedido) {
      const indice = datosApp.pedidosDisponibles.findIndex(p => p.id === idPedido);
      if (indice !== -1) {
        datosApp.pedidosDisponibles[indice].estado = 'ready';
        mostrarToast({ titulo: '✅ Pedido Aceptado', mensaje: `Has aceptado ${idPedido}`, tipo: 'success' });
        renderizarPedidosDisponibles();
        
        datosApp.notificaciones.unshift({
          id: Date.now(), titulo: 'Pedido aceptado', mensaje: `Has aceptado ${idPedido}`,
          tiempo: 'Ahora', leida: false
        });
        actualizarNotificaciones();
      }
    }

    function rechazarPedido(idPedido) {
      datosApp.pedidosDisponibles = datosApp.pedidosDisponibles.filter(p => p.id !== idPedido);
      mostrarToast({ titulo: '❌ Pedido Rechazado', mensaje: `Has rechazado ${idPedido}`, tipo: 'info' });
      renderizarPedidosDisponibles();
    }

    function recogerPedido(idPedido) {
      const indice = datosApp.pedidosDisponibles.findIndex(p => p.id === idPedido);
      if (indice !== -1) {
        const pedido = datosApp.pedidosDisponibles[indice];
        pedido.estado = 'picked';
        const ahora = new Date();
        pedido.recogidoA = ahora.getHours() + ':' + (ahora.getMinutes() < 10 ? '0' : '') + ahora.getMinutes();
        
        datosApp.pedidosActivos.push(pedido);
        datosApp.pedidosDisponibles.splice(indice, 1);
        
        mostrarToast({ titulo: '🚚 Pedido Recogido', mensaje: `Has recogido ${idPedido}`, tipo: 'success' });
        renderizarPedidosDisponibles();
        renderizarPedidosActivos();
      }
    }

    function entregarPedido(idPedido) {
      const indice = datosApp.pedidosActivos.findIndex(p => p.id === idPedido);
      if (indice !== -1) {
        const pedido = datosApp.pedidosActivos[indice];
        
        const ahora = new Date();
        const horaStr = ahora.getHours() + ':' + (ahora.getMinutes() < 10 ? '0' : '') + ahora.getMinutes();
        
        datosApp.historialEntregas.unshift({
          id: pedido.id, cliente: pedido.cliente, fecha: `Hoy, ${horaStr}`,
          zona: pedido.zona, ganancia: pedido.ganancia, calificacion: 5
        });
        
        datosApp.conductor.totalEntregas++;
        datosApp.conductor.totalGanancias += pedido.ganancia;
        datosApp.pedidosActivos.splice(indice, 1);
        
        mostrarToast({ titulo: '✅ Pedido Entregado', mensaje: `¡Completaste ${idPedido}!`, tipo: 'success' });
        iniciarPanel();
      }
    }

    function contactarCliente(idPedido) {
      const pedido = [...datosApp.pedidosDisponibles, ...datosApp.pedidosActivos].find(p => p.id === idPedido);
      if (pedido) {
        mostrarToast({ titulo: '📞 Llamando', mensaje: `Llamando a ${pedido.cliente}`, tipo: 'info' });
      }
    }

    function usarDireccionEnMapa(direccion) {
      document.getElementById('endAddress').value = direccion;
      calcularRuta();
      
      // Scroll suave al mapa
      document.querySelector('.mapa-contenedor').scrollIntoView({ behavior: 'smooth' });
    }

    function filtrarPedidos() {
      renderizarPedidosDisponibles();
    }

    function limpiarFiltros() {
      document.getElementById('filterStatus').value = 'all';
      document.getElementById('filterPriority').value = 'all';
      document.getElementById('filterZone').value = 'all';
      renderizarPedidosDisponibles();
      mostrarToast({ titulo: '🗑️ Filtros limpiados', mensaje: 'Mostrando todos los pedidos', tipo: 'info' });
    }

    function refrescarDatos() {
      mostrarToast({ titulo: '🔄 Actualizando', mensaje: 'Buscando nuevos pedidos...', tipo: 'info' });
    }

    function verNotificaciones() {
      datosApp.notificaciones.forEach(n => n.leida = true);
      actualizarNotificaciones();
      mostrarToast({ titulo: '🔔 Notificaciones', mensaje: 'Todas leídas', tipo: 'info' });
    }

    function verTodoHistorial() {
      mostrarToast({ titulo: '📊 Historial', mensaje: 'Mostrando todas las entregas', tipo: 'info' });
    }

    function salir() {
      if (confirm('¿Seguro que quieres cerrar sesión?')) {
        mostrarToast({ titulo: '👋 Sesión cerrada', mensaje: 'Hasta pronto', tipo: 'info' });
        setTimeout(() => window.location.href = 'index.html', 2000);
      }
    }

    // EVENT LISTENERS
    document.addEventListener('DOMContentLoaded', function() {
      iniciarMapa();
      iniciarPanel();
      
      // Controles del mapa
      document.getElementById('toggleMapControls').addEventListener('click', function() {
        const controls = document.getElementById('mapControls');
        controls.classList.toggle('collapsed');
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-chevron-left');
        icon.classList.toggle('fa-chevron-right');
      });
      
      document.getElementById('calcularRuta').addEventListener('click', calcularRuta);
      document.getElementById('limpiarRuta').addEventListener('click', limpiarRuta);
      
      document.getElementById('zoomIn').addEventListener('click', () => map.zoomIn());
      document.getElementById('zoomOut').addEventListener('click', () => map.zoomOut());
      document.getElementById('resetView').addEventListener('click', () => {
        if (userMarker) map.setView(userMarker.getLatLng(), 16);
        else map.setView([6.2442, -75.5812], 14);
      });
      
      document.getElementById('refreshStatus').addEventListener('click', function() {
        mostrarToast({ mensaje: 'Estado actualizado', tipo: 'success' });
      });
      
      // Sugerencias de direcciones
      document.querySelectorAll('.mapa-sugerencia').forEach(sug => {
        sug.addEventListener('click', function() {
          document.getElementById('endAddress').value = this.getAttribute('data-address');
        });
      });
      
      // Enter en el campo de dirección
      document.getElementById('endAddress').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calcularRuta();
      });
    });