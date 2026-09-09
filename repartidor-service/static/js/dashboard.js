/* ============================================================
   ANGELOW — Dashboard de Repartidor (Microservicio)
   Lógica: auth, pedidos, aceptar/rechazar, mapa, OSRM routing,
   ubicación real-time, observaciones.
   ============================================================ */

const API = '/api/dashboard';
const OSRM_URL = 'https://router.project-osrm.org/route/v1/driving';
const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
const GEOCODE_URL = 'https://nominatim.openstreetmap.org/reverse';

let token = localStorage.getItem('repartidor_token');
let user = null;
let resumenData = null;
let dashMap = null;
let dashMarkers = [];
let modalMap = null;
let modalMarkers = [];
let modalRouteLine = null;
let trackInterval = null;
let pendingObsPedido = null;
let pendingObsEstado = null;
let gpsWatchId = null;

// ── helpers ──────────────────────────────────────────────────────
function fmt$(n) { return '$' + Number(n || 0).toLocaleString('es-CO'); }
function htmlspecialchars(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function getInitials(name) {
  if (!name) return '?';
  var parts = name.trim().split(/\s+/);
  return (parts[0]||'').charAt(0).toUpperCase() + (parts.length > 1 ? (parts[parts.length-1]||'').charAt(0).toUpperCase() : '');
}
function fmtDate(s) {
  if (!s || s === 'None') return '-';
  try { const d = new Date(s.replace(' ', 'T')); return d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' }); } catch(e) { return s; }
}
function fmtDateTime(s) {
  if (!s || s === 'None') return '-';
  try { const d = new Date(s.replace(' ', 'T')); return d.toLocaleString('es-CO', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' }); } catch(e) { return s; }
}

const ESTADO_LABEL = {
  pendiente:'Pendiente', confirmado:'Confirmado', procesando:'Procesando', listo:'Listo',
  asignado:'Asignado', aceptado:'Aceptado', recogido:'Recogido', en_camino:'En camino',
  entregado:'Entregado', cancelado:'Cancelado', rechazado:'Rechazado', reembolsado:'Reembolsado',
};

function toast(msg, tipo='info', titulo='') {
  const c = document.getElementById('toastContainer');
  const iconMap = { success:'fa-check-circle', error:'fa-times-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
  const div = document.createElement('div');
  div.className = 'toast ' + tipo;
  div.innerHTML = '<i class="fas ' + (iconMap[tipo]||iconMap.info) + ' toast-icon"></i>' +
    '<div class="toast-body"><div class="toast-title">' + htmlspecialchars(titulo||tipo.toUpperCase()) + '</div>' +
    '<div class="toast-msg">' + htmlspecialchars(msg) + '</div></div>' +
    '<button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
  c.appendChild(div);
  requestAnimationFrame(function(){ div.classList.add('show'); });
  setTimeout(function(){ div.classList.remove('show'); setTimeout(function(){ div.remove(); }, 400); }, 4000);
}

function authHeaders() {
  const h = { 'Accept':'application/json' };
  if (token) h['Authorization'] = 'Bearer ' + token;
  return h;
}

// ── auth ─────────────────────────────────────────────────────────
async function checkAuth() {
  if (!token) { window.location.href = '/'; return false; }
  try {
    const res = await fetch(API + '/me', { headers: authHeaders() });
    if (res.status === 401) { localStorage.removeItem('repartidor_token'); window.location.href = '/'; return false; }
    const data = await res.json();
    if (!data.success) { localStorage.removeItem('repartidor_token'); window.location.href = '/'; return false; }
    user = data.user;
    document.getElementById('userName').textContent = (user.nombre||'') + ' ' + (user.apellido||'');
    document.getElementById('userAvatar').textContent = (user.nombre||'R').charAt(0).toUpperCase();
    document.getElementById('userVehicle').textContent = (user.tipo_vehiculo||'Repartidor').charAt(0).toUpperCase() + (user.tipo_vehiculo||'repartidor').slice(1) + (user.placa_vehiculo ? ' • ' + user.placa_vehiculo : '');
    return true;
  } catch(e) {
    toast('Error de conexion con el servidor.', 'error');
    return false;
  }
}

async function logout() {
  try {
    await fetch(API + '/logout', {
      method: 'POST',
      headers: Object.assign(authHeaders(), {'Content-Type':'application/json'}),
      body: JSON.stringify({ token: token || '' })
    });
  } catch(e) {}
  localStorage.removeItem('repartidor_token');
  window.location.href = '/';
}

// ── resumen ──────────────────────────────────────────────────────
async function loadResumen() {
  try {
    const res = await fetch(API + '/resumen', { headers: authHeaders() });
    if (res.status === 401) { localStorage.removeItem('repartidor_token'); window.location.href = '/'; return; }
    resumenData = await res.json();
    renderStats(resumenData.stats);
    renderPedidos(resumenData.todos_los_pedidos || []);
    renderRastreo(resumenData.mis_activos || []);
    renderTerminados(resumenData.terminados || []);
  } catch(e) {
    toast('No se pudo cargar el resumen.', 'error');
  }
}

function renderStats(s) {
  document.getElementById('statGanancias').textContent = fmt$(s.hoy_ganancias);
  document.getElementById('statPedidos').textContent = s.hoy_pedidos;
  document.getElementById('statTransito').textContent = s.en_transito;
  document.getElementById('statEntregas').textContent = s.total_entregas;
}

// ── tabs ─────────────────────────────────────────────────────────
function switchTab(name) {
  document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.toggle('active', b.dataset.tab===name); });
  document.querySelectorAll('.tab-content').forEach(function(c){ c.classList.remove('active'); });
  document.getElementById('tab'+name.charAt(0).toUpperCase()+name.slice(1)).classList.add('active');
}

// ── pedidos grid ─────────────────────────────────────────────────
var ESTADO_COLORS = {
  pendiente:'#F59E0B', confirmado:'#5E9DE6', procesando:'#6366F1', listo:'#10b981',
  asignado:'#5E9DE6', aceptado:'#8B5CF6', recogido:'#0EA5E9', en_camino:'#F59E0B',
  entregado:'#10b981', cancelado:'#EF4444', rechazado:'#6B7280', reembolsado:'#374151'
};
var PAGO_ICONS = { pse:'fa-university', tarjeta_credito:'fa-credit-card', tarjeta_debito:'fa-credit-card', mercadopago:'fa-wallet', efectivo:'fa-money-bill-wave', contra_entrega:'fa-money-bill-wave' };
var ENVIO_LABELS = { normal:'Normal', express:'Express' };

function renderPedidos(pedidos) {
  var grid = document.getElementById('pedidosGrid');
  document.getElementById('badgeAll').textContent = pedidos.length;
  if (!pedidos.length) {
    grid.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><h3>Sin pedidos</h3><p>No hay pedidos registrados en el sistema.</p></div>';
    return;
  }
  grid.innerHTML = pedidos.map(function(p, i) {
    var isMine = p.repartidor_id && user && p.repartidor_id === user.id;
    var isAvailable = !p.repartidor_id && ['pendiente','confirmado','procesando','listo'].indexOf(p.estado) !== -1;
    var estadoClass = 'estado-' + p.estado.replace('_','-');
    var accent = ESTADO_COLORS[p.estado] || '#6B7280';
    var initials = getInitials(p.cliente);

    var actions = '';
    if (isAvailable) {
      actions = '<button class="btn-action btn-aceptar" onclick="aceptarPedido('+p.id+')"><i class="fas fa-check"></i> Aceptar</button>' +
                '<button class="btn-action btn-rechazar" onclick="rechazarPedido('+p.id+')"><i class="fas fa-times"></i> Rechazar</button>';
    } else if (isMine) {
      actions = '<button class="btn-action btn-mapa" onclick="abrirMapaPedido('+p.id+')"><i class="fas fa-map-marker-alt"></i> Ver Ubicaci\u00f3n</button>';
      var trans = botonesTransicion(p);
      if (trans) actions += ' ' + trans;
    }

    var h = '<div class="pedido-card" style="--accent:'+accent+';animation-delay:'+((i||0)*0.05).toFixed(2)+'s">';

    h += '<div class="pedido-card-head">' +
      '<span class="pedido-estado ' + estadoClass + '">' + (ESTADO_LABEL[p.estado]||p.estado) + '</span>' +
      '<div class="pedido-num"><i class="fas fa-hashtag"></i> ' + htmlspecialchars(p.numero_pedido||('ID-'+p.id)) + '</div>' +
      (p.prioridad === 'alta' ? '<span class="pedido-prioridad"><i class="fas fa-exclamation"></i> Alta</span>' : '') +
    '</div>';

    h += '<div class="pedido-cliente">' +
      '<div class="pedido-avatar" style="background:'+accent+'">'+htmlspecialchars(initials)+'</div>' +
      '<div class="pedido-cliente-info">' +
        '<div class="pedido-cliente-name">' + htmlspecialchars(p.cliente) + '</div>' +
        (p.email ? '<div class="pedido-cliente-email"><i class="fas fa-envelope"></i> '+htmlspecialchars(p.email)+'</div>' : '') +
      '</div>' +
    '</div>';

    h += '<div class="pedido-ubicacion"><i class="fas fa-map-marker-alt"></i><span>' +
      htmlspecialchars(p.direccion) + (p.ciudad ? ', ' + htmlspecialchars(p.ciudad) : '') +
      (p.barrio ? ' (' + htmlspecialchars(p.barrio) + ')' : '') + '</span></div>';

    h += '<div class="pedido-meta-grid">';
    if (p.telefono) h += '<div class="pedido-meta-item"><i class="fas fa-phone"></i><span>' + htmlspecialchars(p.telefono) + '</span></div>';
    h += '<div class="pedido-meta-item"><i class="fas fa-calendar-alt"></i><span>' + fmtDate(p.fecha_pedido) + '</span></div>';
    if (p.metodo_pago) {
      var payLabel = p.metodo_pago.replace(/_/g,' ');
      var payIcon = PAGO_ICONS[p.metodo_pago] || 'fa-money-bill';
      h += '<div class="pedido-meta-item"><i class="fas '+payIcon+'"></i><span>' + htmlspecialchars(payLabel) + '</span></div>';
    }
    if (p.metodo_envio && ENVIO_LABELS[p.metodo_envio]) {
      h += '<div class="pedido-meta-item"><i class="fas fa-shipping-fast"></i><span>' + ENVIO_LABELS[p.metodo_envio] + '</span></div>';
    }
    if (p.total_productos > 0) {
      h += '<div class="pedido-meta-item"><i class="fas fa-box"></i><span>' + p.total_productos + ' producto' + (p.total_productos !== 1 ? 's' : '') + '</span></div>';
    }
    h += '</div>';

    if (p.notas_cliente) {
      h += '<div class="pedido-notas"><i class="fas fa-comment-dots"></i><span>' + htmlspecialchars(p.notas_cliente) + '</span></div>';
    }

    h += '<div class="pedido-footer">' +
      '<div class="pedido-envio">' + (p.costo_envio > 0 ? 'Env\u00edo ' + fmt$(p.costo_envio) : 'Env\u00edo gratis') + '</div>' +
      '<div class="pedido-footer-total">' + fmt$(p.total) + '</div>' +
    '</div>';

    if (actions) h += '<div class="pedido-card-actions">' + actions + '</div>';
    h += '</div>';
    return h;
  }).join('');
}

function botonesTransicion(p) {
  var s = p.estado;
  var botones = [];
  if (s === 'asignado') botones.push({ estado:'aceptado', label:'Aceptar', cls:'btn-aceptar', icon:'fa-check' });
  if (s === 'asignado' || s === 'aceptado') botones.push({ estado:'recogido', label:'Recogido', cls:'btn-recogido', icon:'fa-box' });
  if (s === 'asignado' || s === 'aceptado' || s === 'recogido') botones.push({ estado:'en_camino', label:'En camino', cls:'btn-camino', icon:'fa-truck' });
  if (s === 'en_camino') botones.push({ estado:'entregado', label:'Entregado', cls:'btn-entregar', icon:'fa-check-double' });
  return botones.map(function(b) {
    return '<button class="btn-action ' + b.cls + '" onclick="transicionPedido(' + p.id + ',\'' + b.estado + '\')"><i class="fas ' + b.icon + '"></i> ' + b.label + '</button>';
  }).join(' ');
}

// ── aceptar/rechazar/transicion ──────────────────────────────────
async function aceptarPedido(id) {
  try {
    var res = await fetch(API + '/pedidos/' + id + '/aceptar', { method:'POST', headers: authHeaders() });
    var data = await res.json();
    if (data.success) { toast(data.message||'Pedido aceptado', 'success', 'Aceptado'); loadResumen(); iniciarSeguimientoGPS(id); }
    else toast(data.error||'Error', 'error');
  } catch(e) { toast('Error de conexion', 'error'); }
}

var pendingRechazoId = null;

function rechazarPedido(id) {
  pendingRechazoId = id;
  document.getElementById('rechazoTextarea').value = '';
  document.getElementById('rechazoModal').classList.add('open');
  setTimeout(() => document.getElementById('rechazoTextarea').focus(), 100);
}

function closeRechazoModal() {
  document.getElementById('rechazoModal').classList.remove('open');
  pendingRechazoId = null;
}

function confirmRechazo() {
  var motivo = document.getElementById('rechazoTextarea').value.trim();
  var id = pendingRechazoId;
  closeRechazoModal();
  doRechazar(id, motivo);
}

document.getElementById('rechazoModal').addEventListener('click', function(e) {
  if (e.target === this) closeRechazoModal();
});
async function doRechazar(id, motivo) {
  try {
    var res = await fetch(API + '/pedidos/' + id + '/rechazar', {
      method:'POST', headers: Object.assign(authHeaders(), {'Content-Type':'application/json'}),
      body: JSON.stringify({motivo:motivo||''})
    });
    var data = await res.json();
    if (data.success) { toast(data.message||'Pedido rechazado', 'warning', 'Rechazado'); loadResumen(); }
    else toast(data.error||'Error', 'error');
  } catch(e) { toast('Error de conexion', 'error'); }
}

function transicionPedido(id, estado) {
  if (estado === 'entregado') {
    pendingObsPedido = id;
    pendingObsEstado = estado;
    document.getElementById('obsTextarea').value = '';
    document.getElementById('obsModal').classList.add('open');
    return;
  }
  doTransicion(id, estado, '');
}
async function doTransicion(id, estado, observacion) {
  try {
    var res = await fetch(API + '/pedidos/' + id + '/estado', {
      method:'PUT', headers: Object.assign(authHeaders(), {'Content-Type':'application/json'}),
      body: JSON.stringify({estado:estado, observacion:observacion})
    });
    var data = await res.json();
    if (data.success) { toast('Estado actualizado a: ' + (ESTADO_LABEL[estado]||estado), 'success', 'Actualizado'); loadResumen(); if (estado==='entregado') detenerSeguimientoGPS(); else iniciarSeguimientoGPS(id); }
    else toast(data.error||'Error', 'error');
  } catch(e) { toast('Error de conexion', 'error'); }
}
function confirmObs() {
  var obs = document.getElementById('obsTextarea').value;
  closeObsModal();
  if (pendingObsPedido) doTransicion(pendingObsPedido, pendingObsEstado, obs);
}
function closeObsModal() { document.getElementById('obsModal').classList.remove('open'); pendingObsPedido = null; }

// ── rastreo map ──────────────────────────────────────────────────
function renderRastreo(pedidos) {
  document.getElementById('badgeActivos').textContent = pedidos.length;
  var list = document.getElementById('mapList');
  if (!pedidos.length) {
    list.innerHTML = '<div style="text-align:center;padding:16px;color:var(--gray-400);font-size:13px;">No tienes pedidos activos para rastrear.</div>';
    document.getElementById('mapContainer').innerHTML = '<div class="map-empty"><i class="fas fa-map-marked-alt"></i><span>Sin pedidos activos</span></div>';
    return;
  }
  list.innerHTML = pedidos.map(function(p) {
    var tieneUbic = p.repartidor_ubicacion && p.repartidor_ubicacion.latitud;
    var tieneDestino = p.destino && p.destino.latitud;
    return '<div class="map-item" onclick="seleccionarPedidoRastreo('+p.id+')">' +
      '<div class="map-item-info"><div class="map-item-num"><i class="fas fa-hashtag"></i> ' + htmlspecialchars(p.numero_pedido) + ' <span class="pedido-estado estado-'+p.estado+'">'+(ESTADO_LABEL[p.estado]||p.estado)+'</span></div>' +
      '<div class="map-item-dire">' + htmlspecialchars(p.direccion) + (tieneUbic?'':' • Sin ubicación GPS') + (tieneDestino?'':' • Sin coordenadas destino') + '</div></div>' +
      '<button class="map-item-btn" onclick="event.stopPropagation();seleccionarPedidoRastreo('+p.id+')"><i class="fas fa-eye"></i></button></div>';
  }).join('');
  if (pedidos.length) seleccionarPedidoRastreo(pedidos[0].id);
}

function seleccionarPedidoRastreo(id) {
  if (!resumenData) return;
  var p = (resumenData.mis_activos||[]).find(function(x){ return x.id===id; });
  if (!p) return;
  dibujarMapaDash(p);
}

function dibujarMapaDash(p) {
  var div = document.getElementById('mapContainer');
  if (!dashMap) {
    dashMap = L.map(div, {scrollWheelZoom:false}).setView([6.25,-75.56],12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(dashMap);
    dashMarkers.forEach(function(m){ dashMap.removeLayer(m); });
    dashMarkers = [];
  }
  dashMarkers.forEach(function(m){ dashMap.removeLayer(m); });
  dashMarkers = [];
  var bounds = [];
  var rep = p.repartidor_ubicacion;
  var dst = p.destino;
  if (rep && rep.latitud) {
    var m = L.marker([rep.latitud, rep.longitud], {icon:L.divIcon({html:'<div style="font-size:22px;">🛵</div>',className:''})}).addTo(dashMap).bindPopup('<strong>Tu ubicación</strong><br>'+htmlspecialchars(p.numero_pedido||''));
    dashMarkers.push(m);
    bounds.push([rep.latitud, rep.longitud]);
  }
  if (dst && dst.latitud) {
    var m2 = L.marker([dst.latitud, dst.longitud], {icon:L.divIcon({html:'<div style="font-size:22px;">📍</div>',className:''})}).addTo(dashMap).bindPopup('<strong>Destino</strong><br>'+htmlspecialchars(dst.direccion||''));
    dashMarkers.push(m2);
    bounds.push([dst.latitud, dst.longitud]);
  }
  if (bounds.length) dashMap.fitBounds(bounds, {padding:[40,40]});
  if (rep && rep.latitud && dst && dst.latitud) drawRouteDash(rep.latitud, rep.longitud, dst.latitud, dst.longitud);
}

async function drawRouteDash(lat1, lng1, lat2, lng2) {
  try {
    var url = OSRM_URL+'/'+lng1+','+lat1+';'+lng2+','+lat2+'?overview=full&geometries=geojson';
    var res = await fetch(url);
    var data = await res.json();
    if (data.routes && data.routes[0] && data.routes[0].geometry) {
      var coords = data.routes[0].geometry.coordinates.map(function(c){ return [c[1], c[0]]; });
      if (modalRouteLine) { try { dashMap.removeLayer(modalRouteLine); } catch(e){} }
      modalRouteLine = L.polyline(coords, {color:'#3B82F6', weight:4, opacity:0.8}).addTo(dashMap);
    }
  } catch(e) { /* OSRM offline, no route */ }
}

// ── mapa modal (ver ubicacion de pedido) ─────────────────────────
function abrirMapaPedido(id) {
  if (!resumenData) return;
  var p = (resumenData.mis_activos||[]).find(function(x){ return x.id===id; });
  if (!p) {
    var all = (resumenData.todos_los_pedidos||[]).concat(resumenData.terminados||[]);
    p = all.find(function(x){ return x.id===id; });
  }
  if (!p) return;
  document.getElementById('mapModalTitle').textContent = 'Ubicación — ' + (p.numero_pedido||'ID-'+p.id);
  document.getElementById('mapModalInfo').innerHTML =
    '<div class="modal-info-item"><div class="modal-info-label">Pedido</div><div class="modal-info-value">' + htmlspecialchars(p.numero_pedido||'') + '</div></div>' +
    '<div class="modal-info-item"><div class="modal-info-label">Cliente</div><div class="modal-info-value">' + htmlspecialchars(p.cliente) + '</div></div>' +
    '<div class="modal-info-item"><div class="modal-info-label">Telefono</div><div class="modal-info-value">' + htmlspecialchars(p.telefono) + '</div></div>' +
    '<div class="modal-info-item"><div class="modal-info-label">Direccion</div><div class="modal-info-value">' + htmlspecialchars(p.direccion) + '</div></div>';
  var trans = botonesTransicion(p);
  document.getElementById('mapModalActions').innerHTML = trans || '<span style="font-size:12px;color:var(--gray-400);">Sin acciones disponibles.</span>';
  document.getElementById('mapModal').classList.add('open');
  setTimeout(function(){ initModalMap(p); }, 100);
  if (trackInterval) clearInterval(trackInterval);
  trackInterval = setInterval(function(){ refrescarMapaModal(p.id); }, 10000);
}

function closeMapModal() {
  document.getElementById('mapModal').classList.remove('open');
  if (trackInterval) { clearInterval(trackInterval); trackInterval = null; }
  if (modalMap) { modalMap.remove(); modalMap = null; }
  modalMarkers = [];
  modalRouteLine = null;
}

function initModalMap(p) {
  var div = document.getElementById('mapModalMap');
  if (modalMap) { modalMap.remove(); modalMap = null; }
  modalMap = L.map(div, {scrollWheelZoom:false}).setView([6.25,-75.56],12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(modalMap);
  modalMarkers.forEach(function(m){ modalMap.removeLayer(m); });
  modalMarkers = [];
  modalRouteLine = null;
  dibujarPedidoModal(p);
}

function dibujarPedidoModal(p) {
  if (!modalMap) return;
  modalMarkers.forEach(function(m){ modalMap.removeLayer(m); });
  modalMarkers = [];
  modalRouteLine = null;
  var bounds = [];
  var rep = p.repartidor_ubicacion;
  var dst = p.destino;
  if (rep && rep.latitud) {
    var m = L.marker([rep.latitud, rep.longitud], {icon:L.divIcon({html:'<div style="font-size:24px;">🛵</div>',className:''})}).addTo(modalMap).bindPopup('<strong>Tu ubicación</strong>');
    modalMarkers.push(m);
    bounds.push([rep.latitud, rep.longitud]);
  }
  if (dst && dst.latitud) {
    var m2 = L.marker([dst.latitud, dst.longitud], {icon:L.divIcon({html:'<div style="font-size:24px;">📍</div>',className:''})}).addTo(modalMap).bindPopup('<strong>Destino</strong><br>'+htmlspecialchars(dst.direccion||''));
    modalMarkers.push(m2);
    bounds.push([dst.latitud, dst.longitud]);
  }
  if (bounds.length) modalMap.fitBounds(bounds, {padding:[50,50]});
  if (rep && rep.latitud && dst && dst.latitud) drawRouteModal(rep.latitud, rep.longitud, dst.latitud, dst.longitud);
}

async function drawRouteModal(lat1, lng1, lat2, lng2) {
  try {
    var url = OSRM_URL+'/'+lng1+','+lat1+';'+lng2+','+lat2+'?overview=full&geometries=geojson';
    var res = await fetch(url);
    var data = await res.json();
    if (data.routes && data.routes[0] && data.routes[0].geometry) {
      var coords = data.routes[0].geometry.coordinates.map(function(c){ return [c[1], c[0]]; });
      modalRouteLine = L.polyline(coords, {color:'#3B82F6', weight:5, opacity:0.85}).addTo(modalMap);
    }
  } catch(e) {}
}

async function refrescarMapaModal(pedidoId) {
  try {
    var res = await fetch(API + '/rastreo', { headers: authHeaders() });
    var data = await res.json();
    if (!data.success) return;
    var p = (data.pedidos||[]).find(function(x){ return x.id===pedidoId; });
    if (p) dibujarPedidoModal(p);
  } catch(e) {}
}

// ── terminados ───────────────────────────────────────────────────
function renderTerminados(pedidos) {
  var wrap = document.getElementById('termTableWrap');
  if (!pedidos.length) {
    wrap.innerHTML = '<div class="empty-state"><i class="fas fa-clipboard-check"></i><h3>Sin pedidos terminados</h3><p>Aun no has completado pedidos.</p></div>';
    return;
  }
  var rows = pedidos.map(function(p) {
    var obs = p.obs_repartidor || p.comentario_cliente || p.notas_internas || '';
    return '<tr>' +
      '<td>' + htmlspecialchars(p.numero_pedido||'ID-'+p.id) + '</td>' +
      '<td>' + (ESTADO_LABEL[p.estado]||p.estado) + '</td>' +
      '<td>' + htmlspecialchars(p.cliente) + '</td>' +
      '<td>' + fmt$(p.total) + '</td>' +
      '<td>' + fmtDateTime(p.fecha_entrega_real || p.fecha_pedido) + '</td>' +
      '<td><div class="obs-text" title="' + htmlspecialchars(obs) + '">' + htmlspecialchars(obs) + '</div></td>' +
    '</tr>';
  }).join('');
  wrap.innerHTML = '<table class="term-table"><thead><tr>' +
    '<th>Pedido</th><th>Estado</th><th>Cliente</th><th>Total</th><th>Fecha</th><th>Observaciones</th>' +
    '</tr></thead><tbody>' + rows + '</tbody></table>';
}

// ── ubicacion real-time (navigator) ──────────────────────────────
function iniciarSeguimientoGPS(pedidoId) {
  detenerSeguimientoGPS();
  if (!navigator.geolocation) return;
  gpsWatchId = navigator.geolocation.watchPosition(function(pos) {
    fetch(API + '/ubicacion', {
      method:'POST',
      headers: Object.assign(authHeaders(), {'Content-Type':'application/json'}),
      body: JSON.stringify({
        pedido_id: pedidoId,
        latitud: pos.coords.latitude,
        longitud: pos.coords.longitude,
        velocidad_kmh: pos.coords.speed ? Math.round(pos.coords.speed * 3.6) : null,
        bateria_porcentaje: null
      })
    }).catch(function(){});
  }, function(){}, {enableHighAccuracy:true, maximumAge:15000});
}

function detenerSeguimientoGPS() {
  if (gpsWatchId !== null && navigator.geolocation) {
    try { navigator.geolocation.clearWatch(gpsWatchId); } catch(e) {}
    gpsWatchId = null;
  }
}

// ── init ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async function() {
  var ok = await checkAuth();
  if (!ok) return;
  await loadResumen();
  setInterval(loadResumen, 30000);
  if (resumenData && resumenData.mis_activos && resumenData.mis_activos.length) {
    iniciarSeguimientoGPS(resumenData.mis_activos[0].id);
  }
});
