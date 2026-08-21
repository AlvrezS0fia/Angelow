/* ============================================================
   SEGUIMIENTO DENTRO DE PERFIL - JS ESCOPADO
   Funcionalidad de mapa, ruta y seguimiento en tiempo real.
   Todo envuelto en initSeguimiento() para evitar conflictos.
   ============================================================ */

let segMapInitialized = false;
let segMap = null;
let segState = { routeCalculated: false, journeyStarted: false, journeyCompleted: false, calculating: false };
let segStartPoint = null;
let segEndPoint = null;
let segStartMarker = null;
let segEndMarker = null;
let segDriverMarker = null;
let segPulseMarker = null;
let segUserMarker = null;
let segRoutingControl = null;
let segRouteCoordinates = [];
let segRouteIndex = 0;
let segTrackingInterval = null;
let segCurrentStep = 0;
const segTotalSteps = 4;
let segRouteGlowLine = null;
let segRouteDecorator = null;
let segDashAnimationInterval = null;
let segDriverHeading = 0;
const segGeocodeCache = new Map();

function initSeguimiento() {
  if (segMapInitialized) {
    if (segMap) {
      setTimeout(() => segMap.invalidateSize(), 100);
    }
    return;
  }
  segMapInitialized = true;
  initSegMap();
  initSegEventListeners();
}

function initSegMap() {
  const mapEl = document.getElementById('segMap');
  if (!mapEl || typeof L === 'undefined') return;

  segMap = L.map('segMap').setView([6.2442, -75.5812], 14);

  const tileLayers = {
    street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '© Esri' }),
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '© CARTO' })
  };

  let segCurrentLayer = 'street';

  segMap.on('tileload', function() {
    const sk = document.getElementById('segMapSkeleton');
    if (sk && !sk.classList.contains('seg-hidden')) {
      sk.classList.add('seg-hidden');
      setTimeout(() => sk.remove(), 600);
    }
  }, { once: true });

  setTimeout(function() {
    const sk = document.getElementById('segMapSkeleton');
    if (sk && !sk.classList.contains('seg-hidden')) {
      sk.classList.add('seg-hidden');
      setTimeout(() => sk.remove(), 600);
    }
  }, 5000);

  tileLayers.street.addTo(segMap);

  /* --- SVG Markers --- */
  function segAdjustColor(hex, amount) {
    const num = parseInt(hex.replace('#', ''), 16);
    const r = Math.min(255, (num >> 16) + amount);
    const g = Math.min(255, ((num >> 8) & 0x00FF) + amount);
    const b = Math.min(255, (num & 0x0000FF) + amount);
    return `#${((r << 16) | (g << 8) | b).toString(16).padStart(6, '0')}`;
  }

  function segTeardropSvg(color) {
    const id = color.replace('#', '');
    return `<svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="sg${id}" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="${segAdjustColor(color, 30)}"/>
          <stop offset="100%" stop-color="${color}"/>
        </linearGradient>
        <filter id="ss${id}">
          <feDropShadow dx="0" dy="2" stdDeviation="2" flood-opacity="0.3"/>
        </filter>
      </defs>
      <path d="M12.5 0C5.6 0 0 5.6 0 12.5C0 21.9 12.5 41 12.5 41C12.5 41 25 21.9 25 12.5C25 5.6 19.4 0 12.5 0Z"
        fill="url(#sg${id})" filter="url(#ss${id})" stroke="white" stroke-width="1.2"/>
      <circle cx="12.5" cy="11" r="3.5" fill="rgba(255,255,255,0.25)"/>
    </svg>`;
  }

  function segCircleSvg(color, arrow = false, rotation = 0) {
    const id = color.replace('#', '');
    const arrowSvg = arrow
      ? `<g transform="rotate(${rotation}, 18, 18)"><path d="M16 6 L22 16 L16 13 L10 16 Z" fill="white" opacity="0.9"/></g>`
      : '';
    return `<svg width="36" height="36" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="sc${id}" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="${segAdjustColor(color, 25)}"/>
          <stop offset="100%" stop-color="${color}"/>
        </linearGradient>
        <filter id="sf${id}">
          <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.35"/>
        </filter>
      </defs>
      <circle cx="18" cy="18" r="16" fill="url(#sc${id})" filter="url(#sf${id})" stroke="white" stroke-width="2"/>
      <circle cx="18" cy="18" r="12" fill="rgba(255,255,255,0.15)"/>
      ${arrowSvg}
    </svg>`;
  }

  function segSvgMarker(svg, size, anchor, popupAnchor) {
    return L.divIcon({
      className: 'custom-marker',
      html: svg,
      iconSize: size,
      iconAnchor: anchor,
      popupAnchor: popupAnchor
    });
  }

  const segStartIcon = segSvgMarker(segTeardropSvg('#22c55e'), [25, 41], [12, 41], [0, -34]);
  const segEndIcon = segSvgMarker(segTeardropSvg('#ef4444'), [25, 41], [12, 41], [0, -34]);
  const segUserIcon = segSvgMarker(segCircleSvg('#8b5cf6'), [36, 36], [18, 18], [0, -20]);

  /* --- DOM Elements (scoped) --- */
  const sec = document.getElementById('seguimientoSection');
  const segStartAddressInput = sec.querySelector('#segStartAddress');
  const segEndAddressInput = sec.querySelector('#segEndAddress');
  const segStatusIndicator = sec.querySelector('.seg-status-indicator');
  const segStatusTitle = sec.querySelector('#segStatusTitle');
  const segStatusMessage = sec.querySelector('#segStatusMessage');
  const segStatusTime = sec.querySelector('#segStatusTime');
  const segDeliveryPerson = sec.querySelector('#segDeliveryPerson');
  const segDeliveryContact = sec.querySelector('#segDeliveryContact');
  const segDriverName = sec.querySelector('#segDriverName');
  const segRouteDistance = sec.querySelector('#segRouteDistance');
  const segRouteTime = sec.querySelector('#segRouteTime');
  const segEstimatedTime = sec.querySelector('#segEstimatedTime');
  const segEstimatedDistance = sec.querySelector('#segEstimatedDistance');
  const segClearRouteBtn = sec.querySelector('#segClearRoute');
  const segSaveRouteBtn = sec.querySelector('#segSaveRoute');

  /* --- Helper functions --- */
  function segUpdateStatus(type, title, message) {
    segStatusIndicator.className = 'seg-status-indicator seg-pulse';
    segStatusIndicator.classList.add(`seg-status-${type}`);
    segStatusTitle.textContent = `Estado: ${title}`;
    segStatusMessage.textContent = message;
    const now = new Date();
    segStatusTime.textContent = `Actualizado: ${now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`;
  }

  function segUpdateRouteInfo(distance, time) {
    segRouteDistance.textContent = `${distance} km`;
    segRouteTime.textContent = `${time} min`;
    segEstimatedTime.textContent = time;
    segEstimatedDistance.textContent = `${distance} km`;
  }

  function segSetStep(step) {
    segCurrentStep = Math.min(Math.max(step, 0), segTotalSteps - 1);
    const steps = sec.querySelectorAll('.seg-step');
    steps.forEach((stepEl, index) => {
      if (index <= segCurrentStep) {
        stepEl.classList.add('seg-completed');
        if (index === segCurrentStep) stepEl.classList.add('seg-active');
        else stepEl.classList.remove('seg-active');
      } else {
        stepEl.classList.remove('seg-completed', 'seg-active');
      }
    });
    const progressFill = sec.querySelector('.seg-progress-fill');
    const progressText = sec.querySelector('.seg-progress-text');
    const progressPercentage = (segCurrentStep / (segTotalSteps - 1)) * 100;
    if (progressFill) progressFill.style.width = `${progressPercentage}%`;
    if (progressText) progressText.textContent = `${Math.round(progressPercentage)}%`;
  }

  function segUpdateTrackingProgress(progress) {
    const progressFill = sec.querySelector('.seg-progress-fill');
    const progressText = sec.querySelector('.seg-progress-text');
    if (progressFill) progressFill.style.width = `${progress}%`;
    if (progressText) progressText.textContent = `${Math.round(progress)}%`;
    const steps = sec.querySelectorAll('.seg-step');
    steps.forEach((step, index) => {
      const stepProgress = (index / (steps.length - 1)) * 100;
      if (progress >= stepProgress) {
        step.classList.add('seg-completed');
        if (index === 2 && progress >= 25 && progress < 75) step.classList.add('seg-active');
        else if (index === 2) step.classList.remove('seg-active');
      } else {
        step.classList.remove('seg-completed', 'seg-active');
      }
    });
  }

  function segUpdateDriverInfo() {
    segDeliveryPerson.textContent = 'Carlos Rodríguez';
    segDeliveryContact.textContent = '+57 300 123 4567';
    segDriverName.textContent = 'Carlos Rodríguez';
  }

  function segAnimateValue(el, start, end, duration = 400) {
    const range = start - end;
    if (range === 0) return;
    const startTime = performance.now();
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

  function segUpdateRemainingTime(progress, totalTimeMinutes) {
    const newValue = Math.round((totalTimeMinutes * (100 - progress)) / 100);
    const oldValue = parseInt(segEstimatedTime.textContent) || newValue;
    if (oldValue !== newValue) {
      segAnimateValue(segEstimatedTime, oldValue, newValue, 300);
    }
  }

  function segResetMapState() {
    segState = { routeCalculated: false, journeyStarted: false, journeyCompleted: false, calculating: false };
    if (segRoutingControl) { segMap.removeControl(segRoutingControl); segRoutingControl = null; }
    if (segEndMarker) { segMap.removeLayer(segEndMarker); segEndMarker = null; }
    if (segDriverMarker) { segMap.removeLayer(segDriverMarker); segDriverMarker = null; }
    if (segPulseMarker) { segMap.removeLayer(segPulseMarker); segPulseMarker = null; }
    if (segTrackingInterval) { clearInterval(segTrackingInterval); segTrackingInterval = null; }
    if (segRouteGlowLine) { segMap.removeLayer(segRouteGlowLine); segRouteGlowLine = null; }
    if (segRouteDecorator) { segMap.removeLayer(segRouteDecorator); segRouteDecorator = null; }
    if (segDashAnimationInterval) { clearInterval(segDashAnimationInterval); segDashAnimationInterval = null; }
    segEndPoint = null;
    segRouteCoordinates = [];
    segRouteIndex = 0;
    segSetStep(0);
    segUpdateStatus('waiting', 'Esperando', 'Ingresa una dirección de destino.');
    segRouteDistance.textContent = '--';
    segRouteTime.textContent = '--';
    segEstimatedTime.textContent = '--';
    segEstimatedDistance.textContent = '--';
    segDeliveryPerson.textContent = 'Asignando...';
    segDeliveryContact.textContent = '---';
    segDriverName.textContent = '-';
    segClearRouteBtn.disabled = true;
    segSaveRouteBtn.disabled = true;
  }

  function segStartTracking(totalTimeMinutes) {
    if (segRouteCoordinates.length === 0) return;
    const driverDivIcon = segSvgMarker(segCircleSvg('#3b82f6', true), [36, 36], [18, 18], [0, -20]);
    segDriverMarker = L.marker(segStartPoint, { icon: driverDivIcon, zIndexOffset: 1000 }).addTo(segMap)
      .bindPopup('<b>\ud83d\ude9b Repartidor</b><br>Iniciando entrega...').openPopup();
    const pulseEl = L.divIcon({ className: 'marker-pulse-ring', iconSize: [44, 44], iconAnchor: [22, 22] });
    segPulseMarker = L.marker(segStartPoint, { icon: pulseEl, interactive: false }).addTo(segMap);
    segDriverHeading = 0;
    if (segTrackingInterval) clearInterval(segTrackingInterval);
    segRouteIndex = 0;
    segState.journeyStarted = true;
    segSetStep(2);
    segUpdateStatus('traveling', 'En Camino', 'Siguiendo la ruta de entrega...');
    segUpdateDriverInfo();

    const simSpeed = 0.15;
    const totalTimeMs = totalTimeMinutes * 60 * 1000 * simSpeed;
    const steps = segRouteCoordinates.length;
    const intervalMs = Math.max(10, totalTimeMs / steps);
    const stepIncrement = Math.max(1, Math.floor(segRouteCoordinates.length / 60));
    let subStep = 0;
    const SUB_STEPS = 5;
    let trailCoords = [];
    let trailLine = null;

    segTrackingInterval = setInterval(() => {
      if (segRouteIndex < segRouteCoordinates.length - 1) {
        const idx = segRouteIndex;
        const nextIdx = Math.min(idx + 1, segRouteCoordinates.length - 1);
        const current = segRouteCoordinates[idx];
        const next = segRouteCoordinates[nextIdx];
        const t = Math.min(subStep / SUB_STEPS, 1);
        const eased = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        const lat = current.lat + (next.lat - current.lat) * eased;
        const lng = current.lng + (next.lng - current.lng) * eased;
        const pos = [lat, lng];

        function segBearing(fromLat, fromLng, toLat, toLng) {
          const dLng = (toLng - fromLng) * Math.PI / 180;
          const y = Math.sin(dLng) * Math.cos(toLat * Math.PI / 180);
          const x = Math.cos(fromLat * Math.PI / 180) * Math.sin(toLat * Math.PI / 180)
                  - Math.sin(fromLat * Math.PI / 180) * Math.cos(toLat * Math.PI / 180) * Math.cos(dLng);
          return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
        }

        const heading = segBearing(current.lat, current.lng, next.lat, next.lng);
        if (!isNaN(heading) && Math.abs(heading - segDriverHeading) > 2) {
          segDriverHeading = heading;
          segDriverMarker.setIcon(segSvgMarker(segCircleSvg('#3b82f6', true, heading), [36, 36], [18, 18], [0, -20]));
        }

        segDriverMarker.setLatLng(pos);
        if (segPulseMarker) segPulseMarker.setLatLng(pos);

        trailCoords.push(L.latLng(lat, lng));
        if (trailCoords.length > 1) {
          if (trailLine) segMap.removeLayer(trailLine);
          trailLine = L.polyline(trailCoords, {
            color: '#3b82f6', weight: 3, opacity: 0.35, className: 'trail-line'
          }).addTo(segMap);
        }

        if (segRouteIndex % 8 === 0) {
          segMap.setView(pos, Math.max(segMap.getZoom(), 15), { animate: true, duration: 0.1 });
        }

        const progress = (segRouteIndex / segRouteCoordinates.length) * 100;
        segDriverMarker.setPopupContent(`<b>\ud83d\ude9b Repartidor</b><br>Progreso: ${Math.round(progress)}%`);
        segUpdateTrackingProgress(progress);
        segUpdateRemainingTime(progress, totalTimeMinutes);

        subStep++;
        if (subStep >= SUB_STEPS) {
          subStep = 0;
          segRouteIndex += stepIncrement;
        }
      } else {
        clearInterval(segTrackingInterval);
        if (segDashAnimationInterval) { clearInterval(segDashAnimationInterval); segDashAnimationInterval = null; }
        segState.journeyCompleted = true;
        segSetStep(3);
        segUpdateStatus('completed', 'Entrega Completada', '\u00a1Pedido entregado exitosamente!');
        const finalIcon = segSvgMarker(segCircleSvg('#22c55e'), [36, 36], [18, 18], [0, -20]);
        segDriverMarker.setIcon(finalIcon);
        segDriverMarker.setPopupContent('<b>\ud83c\udf89 Entregado</b><br>\u00a1Pedido completado!').openPopup();
        segUpdateTrackingProgress(100);
        const notif = document.getElementById('segLiveNotification');
        if (notif) {
          notif.style.display = 'block';
          setTimeout(() => notif.style.display = 'none', 5000);
        }
        setTimeout(() => { segEndAddressInput.value = ''; segResetMapState(); }, 5000);
      }
    }, intervalMs);
  }

  function segNormalizeAddress(address) {
    return address
      .replace(/cl(\.|e)?\s*/gi, 'Calle ')
      .replace(/cr(\.|a)?\s*/gi, 'Carrera ')
      .replace(/av(\.|e)?\s*/gi, 'Avenida ')
      .replace(/#\s*/g, 'No ')
      .replace(/(\d+)-(\d+)/g, '$1 No $2')
      .replace(/\s+/g, ' ').trim();
  }

  async function segGeocodeAddress(address) {
    const originalAddress = address.trim();
    if (!originalAddress) throw new Error('Direcci\u00f3n vac\u00eda.');
    const cached = segGeocodeCache.get(originalAddress.toLowerCase().trim());
    if (cached) return cached;

    const addressVariations = [
      `${segNormalizeAddress(originalAddress)}, Medell\u00edn, Antioquia, Colombia`,
      `${originalAddress}, Medell\u00edn, Antioquia, Colombia`,
      `${originalAddress}, Medell\u00edn, Colombia`,
      segNormalizeAddress(originalAddress),
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
      if (displayName.includes('medell\u00edn') || displayName.includes('medellin')) score += 1.0;
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
    if (allResults.length === 0) throw new Error('No se encontr\u00f3 la direcci\u00f3n. Verifica e intenta nuevamente.');
    allResults.sort((a, b) => b.score - a.score);
    const bestResult = allResults[0];
    const result = { lat: parseFloat(bestResult.lat), lng: parseFloat(bestResult.lon), display_name: bestResult.display_name };
    segGeocodeCache.set(originalAddress.toLowerCase().trim(), result);
    return result;
  }

  function segDrawEnhancedRoute(coords) {
    if (segRouteGlowLine) segMap.removeLayer(segRouteGlowLine);
    if (segRouteDecorator) segMap.removeLayer(segRouteDecorator);
    if (segDashAnimationInterval) { clearInterval(segDashAnimationInterval); segDashAnimationInterval = null; }

    const latlngs = coords.map(c => L.latLng(c.lat ?? c[0], c.lng ?? c[1]));

    segRouteGlowLine = L.polyline(latlngs, {
      color: '#3b82f6', weight: 9, opacity: 0.2, className: 'route-glow'
    }).addTo(segMap);

    const mainLine = L.polyline(latlngs, {
      color: '#5E9DE6', weight: 4, opacity: 0.95, dashArray: '12, 8'
    }).addTo(segMap);

    let dashOffset = 0;
    segDashAnimationInterval = setInterval(() => {
      dashOffset -= 1;
      mainLine.setStyle({ dashOffset: String(dashOffset) });
    }, 100);

    if (typeof L.polylineDecorator !== 'undefined' && latlngs.length > 1) {
      segRouteDecorator = L.polylineDecorator(latlngs, {
        patterns: [
          { offset: 15, repeat: 50, symbol: L.Symbol.arrowHead({ pixelSize: 8, polygon: false, pathOptions: { color: '#5E9DE6', weight: 2, opacity: 0.7 } }) }
        ]
      }).addTo(segMap);
    }
  }

  function segCalculateRoute(start, end) {
    if (segRoutingControl) segMap.removeControl(segRoutingControl);
    const btn = sec.querySelector('#segCalculateRoute');
    if (btn) { btn.innerHTML = '<span class="seg-spinner"></span> Calculando...'; btn.classList.add('seg-btn-loading'); }
    segUpdateStatus('calculating', 'Trazando Ruta', 'Calculando la mejor ruta...');
    segRoutingControl = L.Routing.control({
      waypoints: [L.latLng(start.lat, start.lng), L.latLng(end.lat, end.lng)],
      routeWhileDragging: false,
      show: false,
      lineOptions: { styles: [{ color: '#5E9DE6', weight: 3, opacity: 0.3, dashArray: '1, 0' }] },
      createMarker: function() { return null; }
    }).addTo(segMap);

    segRoutingControl.on('routesfound', function(e) {
      if (btn) { btn.innerHTML = '<i class="fas fa-route"></i> Calcular Ruta'; btn.classList.remove('seg-btn-loading'); }
      const route = e.routes[0];
      segRouteCoordinates = route.coordinates;
      segDrawEnhancedRoute(segRouteCoordinates);
      const group = new L.featureGroup([segStartMarker, segEndMarker]);
      segMap.fitBounds(group.getBounds().pad(0.1), { animate: true, duration: 1, maxZoom: 15 });
      segState.routeCalculated = true;
      const distance = (route.summary.totalDistance / 1000).toFixed(1);
      const time = Math.round(route.summary.totalTime / 60);
      segUpdateRouteInfo(distance, time);
      segUpdateStatus('ready', 'Ruta Calculada', `Distancia: ${distance} km - Tiempo: ${time} min`);
      segClearRouteBtn.disabled = false;
      segSaveRouteBtn.disabled = false;
      setTimeout(() => segStartTracking(time), 3000);
    });

    segRoutingControl.on('routingerror', function() {
      if (btn) { btn.innerHTML = '<i class="fas fa-route"></i> Calcular Ruta'; btn.classList.remove('seg-btn-loading'); }
      segUpdateStatus('error', 'Error en Ruta', 'No se pudo calcular la ruta.');
      segState.calculating = false;
    });
  }

  /* --- Geolocation --- */
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        segMap.setView([lat, lng], 16);
        if (segUserMarker) segMap.removeLayer(segUserMarker);
        segUserMarker = L.marker([lat, lng], { icon: segUserIcon }).addTo(segMap).bindPopup('\ud83d\udccd Tu ubicaci\u00f3n').openPopup();
        segStartPoint = { lat, lng };
        segStartMarker = segUserMarker;
        segStartAddressInput.value = 'Tu ubicaci\u00f3n actual';
      },
      (error) => {
        let message = 'Error al obtener la ubicaci\u00f3n.';
        if (error.code === error.PERMISSION_DENIED) message = 'Permiso de ubicaci\u00f3n denegado.';
        else if (error.code === error.POSITION_UNAVAILABLE) message = 'Ubicaci\u00f3n no disponible.';
        else if (error.code === error.TIMEOUT) message = 'Tiempo de espera agotado.';
        segUpdateStatus('error', 'Error de Ubicaci\u00f3n', message);
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  }

  /* --- Event Listeners --- */
  function initSegEventListeners() {
    const sec = document.getElementById('seguimientoSection');

    /* Controls panel toggle */
    const segControlsToggle = sec.querySelector('#segControlsToggle');
    const segControlsPanel = sec.querySelector('#segControlsPanel');
    const segClosePanel = sec.querySelector('#segClosePanel');

    if (segControlsToggle) segControlsToggle.addEventListener('click', () => {
      segControlsPanel.classList.remove('seg-collapsed');
      segControlsPanel.classList.add('seg-expanded');
    });
    if (segClosePanel) segClosePanel.addEventListener('click', () => {
      segControlsPanel.classList.remove('seg-expanded');
      segControlsPanel.classList.add('seg-collapsed');
    });

    /* Use current location */
    sec.querySelector('#segUseCurrentLocation')?.addEventListener('click', () => {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            segMap.setView([lat, lng], 16);
            if (segUserMarker) segMap.removeLayer(segUserMarker);
            segUserMarker = L.marker([lat, lng], { icon: segUserIcon }).addTo(segMap).bindPopup('\ud83d\udccd Tu ubicaci\u00f3n').openPopup();
            segStartPoint = { lat, lng };
            segStartMarker = segUserMarker;
            segStartAddressInput.value = 'Ubicaci\u00f3n actual (GPS)';
          },
          (error) => {
            let msg = 'No se pudo obtener tu ubicaci\u00f3n.';
            if (error.code === error.PERMISSION_DENIED) msg = 'Permiso denegado. Habilita la geolocalizaci\u00f3n.';
            alert(msg);
          }
        );
      }
    });

    /* Locate me */
    sec.querySelector('#segLocateMe')?.addEventListener('click', () => {
      if (segUserMarker) segMap.setView(segUserMarker.getLatLng(), 16);
    });

    /* Calculate route */
    sec.querySelector('#segCalculateRoute')?.addEventListener('click', async function() {
      const endAddress = segEndAddressInput.value.trim();
      if (!segStartPoint) { segUpdateStatus('error', 'Esperando GPS', 'Define origen con "Usar mi ubicaci\u00f3n" o espera GPS.'); return; }
      if (!endAddress) { segUpdateStatus('error', 'Error', 'Por favor ingresa la direcci\u00f3n de destino.'); return; }
      if (segState.calculating || segState.journeyStarted) return;
      segState.calculating = true;
      if (segControlsPanel.classList.contains('seg-expanded')) {
        segControlsPanel.classList.remove('seg-expanded');
        segControlsPanel.classList.add('seg-collapsed');
      }
      segUpdateStatus('calculating', 'Calculando Ruta', 'Preparando ruta...');
      try {
        const endLocation = await segGeocodeAddress(endAddress);
        segEndPoint = L.latLng(endLocation.lat, endLocation.lng);
        if (segEndMarker) segMap.removeLayer(segEndMarker);
        if (segDriverMarker) { segMap.removeLayer(segDriverMarker); segDriverMarker = null; }
        if (segPulseMarker) { segMap.removeLayer(segPulseMarker); segPulseMarker = null; }
        segEndMarker = L.marker(segEndPoint, { icon: segEndIcon }).addTo(segMap).bindPopup(`<b>\ud83c\udfaf Destino</b><br>${endLocation.display_name}`);
        segCalculateRoute(segStartPoint, endLocation);
      } catch (error) { segUpdateStatus('error', 'Error', error.message); segState.calculating = false; }
    });

    /* Clear route */
    sec.querySelector('#segClearRoute')?.addEventListener('click', () => {
      segEndAddressInput.value = '';
      segResetMapState();
    });

    /* Save route */
    sec.querySelector('#segSaveRoute')?.addEventListener('click', () => {
      const dest = segEndAddressInput.value.trim();
      const dist = segRouteDistance.textContent;
      const t = segRouteTime.textContent;
      if (!dest || dist === '--') return;
      localStorage.setItem('angelow_ruta_guardada', JSON.stringify({ destino: dest, distancia: dist, tiempo: t }));
    });

    /* Address autocomplete */
    let segDebounceTimer = null;
    segEndAddressInput.addEventListener('input', function() {
      clearTimeout(segDebounceTimer);
      const q = this.value.trim();
      const dropdown = sec.querySelector('#segAddressSuggestions');
      if (q.length < 3) { dropdown.style.display = 'none'; return; }
      segDebounceTimer = setTimeout(async () => {
        try {
          const res = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5&countrycodes=co&bounded=1&viewbox=-75.7,6.0,-75.4,6.4`,
            { headers: { 'User-Agent': 'DeliveryMapApp/1.0' } }
          );
          const data = await res.json();
          if (!data.length) { dropdown.style.display = 'none'; return; }
          dropdown.innerHTML = data.map(d => `
            <div class="seg-autocomplete-item" data-address="${d.display_name}">
              <i class="fas fa-map-pin"></i>
              <span>${d.display_name}</span>
            </div>
          `).join('');
          dropdown.style.display = 'block';
          dropdown.querySelectorAll('.seg-autocomplete-item').forEach(el => {
            el.addEventListener('click', () => {
              segEndAddressInput.value = el.getAttribute('data-address');
              dropdown.style.display = 'none';
            });
          });
        } catch (e) { sec.querySelector('#segAddressSuggestions').style.display = 'none'; }
      }, 300);
    });

    /* Close autocomplete on outside click */
    document.addEventListener('click', (e) => {
      const dropdown = sec.querySelector('#segAddressSuggestions');
      if (!e.target.closest('#segEndAddress') && !e.target.closest('#segAddressSuggestions')) {
        if (dropdown) dropdown.style.display = 'none';
      }
    });

    /* Suggestions (hardcoded) */
    sec.querySelectorAll('.seg-suggestion').forEach(suggestion => {
      suggestion.addEventListener('click', function() { segEndAddressInput.value = this.getAttribute('data-address'); });
    });

    /* Contact buttons */
    sec.querySelectorAll('.seg-call-btn').forEach(btn => btn.addEventListener('click', () => alert('Llamando al repartidor...')));
    sec.querySelectorAll('.seg-message-btn').forEach(btn => btn.addEventListener('click', () => alert('Abriendo chat...')));
    sec.querySelectorAll('.seg-location-btn').forEach(btn => btn.addEventListener('click', () => { if (segDriverMarker) segMap.setView(segDriverMarker.getLatLng(), 16); }));

    /* Share */
    sec.querySelector('#segShareTracking')?.addEventListener('click', () => {
      if (navigator.share) navigator.share({ title: 'Mi pedido Angelow', text: 'Sigue mi pedido en tiempo real', url: window.location.href });
      else { navigator.clipboard.writeText(window.location.href); }
    });

    /* Layer switcher */
    const segTileLayers = {
      street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }),
      satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '© Esri' }),
      dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '© CARTO' })
    };
    let segActiveLayer = 'street';
    sec.querySelectorAll('.seg-layer-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const layer = this.getAttribute('data-layer');
        if (layer === segActiveLayer) return;
        sec.querySelectorAll('.seg-layer-btn').forEach(b => b.classList.remove('seg-active'));
        this.classList.add('seg-active');
        Object.keys(segTileLayers).forEach(k => {
          if (k === layer) segTileLayers[k].addTo(segMap);
          else if (segMap.hasLayer(segTileLayers[k])) segMap.removeLayer(segTileLayers[k]);
        });
        segActiveLayer = layer;
      });
    });

    /* Refresh status */
    sec.querySelector('#segRefreshStatus')?.addEventListener('click', () => {
      segUpdateStatus(
        segStatusIndicator.classList[2]?.replace('seg-status-', '') || 'waiting',
        segStatusTitle.textContent.replace('Estado: ', ''),
        segStatusMessage.textContent
      );
    });
  }
}
