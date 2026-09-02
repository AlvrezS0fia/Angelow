/* ============================================================
   ANGELOW - PANEL REPARTIDOR
   Lógica conectada a la base de datos vía API PHP (proxy Node.js)
   ============================================================ */

const socket = io();

// ===== VARIABLES GLOBALES =====
let usuarioActual = null;
let vistaActual = 'dashboard';
let filtroActual = null;
let contadorNotificaciones = 0;
let mapaSeguimiento = null;
let controlRuta = null;
let marcadorInicio = null;
let marcadorDestino = null;
let marcadorConductor = null;
let capasMapa = {};
let capaActiva = 'calle';
let puntoInicio = null;
let pedidosActivos = [];
let pedidoSeleccionado = null;
let intervaloActualizacion = null;
let watchGps = null;
let userLocation = null;

// ===== TOKEN MANAGEMENT =====
function getToken() { return localStorage.getItem('repartidor_token'); }
function setToken(t) { localStorage.setItem('repartidor_token', t); }
function clearToken() { localStorage.removeItem('repartidor_token'); usuarioActual = null; }

// ===== UTILIDADES =====
function escaparHtml(str) {
  const div = document.createElement('div');
  div.textContent = str == null ? '' : String(str);
  return div.innerHTML;
}

function formatearFecha(fechaStr) {
  if (!fechaStr) return '—';
  const d = new Date(fechaStr);
  if (isNaN(d.getTime())) return fechaStr;
  return d.toLocaleString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatearMoneda(n) {
  const num = Number(n || 0);
  return '$' + num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const ETIQUETAS_ESTADO = {
  pendiente: 'Pendiente',
  asignado: 'Asignado',
  confirmado: 'Confirmado',
  procesando: 'Procesando',
  listo: 'Listo',
  en_camino: 'En Camino',
  entregado: 'Entregado',
  cancelado: 'Cancelado'
};

function getEstado(pedido) {
  return pedido.status || pedido.estado;
}

function badgeEstado(estado) {
  return `<span class="status-badge estado-${escaparHtml(estado)}">${ETIQUETAS_ESTADO[estado] || escaparHtml(estado) || estado}</span>`;
}

function obtenerIniciales(nombre) {
  if (!nombre) return 'RA';
  return nombre.split(' ').filter(Boolean).map(n => n[0]).join('').toUpperCase().substring(0, 2);
}

// ===== TOAST =====
function mostrarToast(mensaje, tipo) {
  const contenedor = document.getElementById('toast');
  if (!contenedor) return;
  const toast = document.createElement('div');
  toast.className = `toast-item toast-${tipo || 'info'}`;
  toast.innerHTML = `${escaparHtml(mensaje)}`;
  contenedor.appendChild(toast);
  setTimeout(() => toast.classList.add('mostrar'), 10);
  setTimeout(() => {
    toast.classList.remove('mostrar');
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

// ===== API HELPERS CON AUTH =====
function authHeaders() {
  const t = getToken();
  const h = { 'Content-Type': 'application/json' };
  if (t) h['Authorization'] = 'Bearer ' + t;
  return h;
}

async function apiGet(url) {
  const res = await fetch(url, { headers: authHeaders() });
  if (res.status === 401) { manejarNoAutorizado(); throw new Error('No autorizado'); }
  if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.error || 'Error ' + res.status); }
  return res.json();
}

async function apiEnviar(url, data) {
  const res = await fetch(url, { method: 'POST', headers: authHeaders(), body: JSON.stringify(data) });
  if (res.status === 401) { manejarNoAutorizado(); throw new Error('No autorizado'); }
  if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.error || 'Error'); }
  return res.json();
}

async function apiActualizar(url, data) {
  const res = await fetch(url, { method: 'PUT', headers: authHeaders(), body: JSON.stringify(data) });
  if (res.status === 401) { manejarNoAutorizado(); throw new Error('No autorizado'); }
  if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.error || 'Error'); }
  return res.json();
}

async function apiEliminar(url) {
  const res = await fetch(url, { method: 'DELETE', headers: authHeaders() });
  if (res.status === 401) { manejarNoAutorizado(); throw new Error('No autorizado'); }
  if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.error || 'Error'); }
  return res.json();
}

async function apiSubir(url, formData) {
  const headers = {};
  const t = getToken();
  if (t) headers['Authorization'] = 'Bearer ' + t;
  const res = await fetch(url, { method: 'POST', headers, body: formData });
  if (res.status === 401) { manejarNoAutorizado(); throw new Error('No autorizado'); }
  if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.error || 'Error'); }
  return res.json();
}

function manejarNoAutorizado() {
  clearToken();
  mostrarToast('Sesión expirada', 'error');
  mostrarLogin();
}

// ===== LOGIN / LOGOUT =====
function mostrarLogin() {
  const pantalla = document.getElementById('loginScreen');
  const app = document.getElementById('appContainer');
  if (pantalla) pantalla.classList.remove('oculto');
  if (app) app.style.display = 'none';
}

function mostrarApp() {
  const pantalla = document.getElementById('loginScreen');
  const app = document.getElementById('appContainer');
  if (pantalla) pantalla.classList.add('oculto');
  if (app) app.style.display = 'block';
  actualizarAvatar();
}

async function verificarAuth() {
  const token = getToken();
  if (!token) { mostrarLogin(); return; }
  try {
    const res = await apiGet('/api/auth/me');
    if (res.success && res.user) {
      usuarioActual = res.user;
      mostrarApp();
      renderVista('dashboard');
    } else {
      clearToken();
      mostrarLogin();
    }
  } catch (e) {
    clearToken();
    mostrarLogin();
  }
}

document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;
  const errorEl = document.getElementById('loginError');
  if (errorEl) errorEl.textContent = '';

  if (!email || !password) {
    if (errorEl) errorEl.textContent = 'Ingresa tu correo y contraseña';
    return;
  }

  const btn = document.getElementById('loginBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ingresando...'; }

  try {
    const res = await apiEnviar('/api/auth/login', { email, password });
    if (!res.success) {
      if (errorEl) errorEl.textContent = res.message || res.error || 'Credenciales inválidas';
      if (btn) { btn.disabled = false; btn.innerHTML = 'Ingresar'; }
      return;
    }
    setToken(res.token);
    usuarioActual = res.user || null;
    mostrarApp();
    renderVista('dashboard');
    mostrarToast('Bienvenido, ' + (usuarioActual ? (usuarioActual.nombres || usuarioActual.email || '') : ''), 'success');
    if (res.documentos && res.documentos.length > 0) {
      mostrarToast('Tienes documentos pendientes por revisar', 'info');
    }
  } catch (err) {
    if (errorEl) errorEl.textContent = err.message || 'Error de conexión';
    if (btn) { btn.disabled = false; btn.innerHTML = 'Ingresar'; }
  }
});

async function cerrarSesion() {
  if (!confirm('¿Seguro que quieres cerrar sesión?')) return;
  try { await fetch('/api/auth/logout', { method: 'POST', headers: authHeaders() }); } catch (e) {}
  clearToken();
  limpiarVistaSeguimiento();
  mostrarLogin();
}

function actualizarAvatar() {
  const avatar = document.getElementById('avatarText');
  if (avatar) {
    const nombre = (usuarioActual && (usuarioActual.nombres + ' ' + (usuarioActual.apellidos || ''))) || 'Repartidor';
    avatar.textContent = obtenerIniciales(nombre);
  }
}

// ===== NAVEGACIÓN =====
function navegarA(vista, filtro) {
  vistaActual = vista;
  filtroActual = filtro || null;
  limpiarVistaSeguimiento();
  document.querySelectorAll('.sidebar-menu li[data-view]').forEach(li => li.classList.remove('active'));
  const objetivo = document.querySelector(`.sidebar-menu li[data-view="${vista}"]`);
  if (objetivo) objetivo.classList.add('active');
  const buscar = document.getElementById('searchInput');
  if (buscar) buscar.value = '';
  renderVista(vista, filtro);
  if (window.innerWidth <= 992) document.getElementById('sidebar').classList.remove('open');
}

async function renderVista(vista, filtro) {
  const contenedor = document.getElementById('viewContainer');
  if (!contenedor) return;
  contenedor.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
  try {
    switch (vista) {
      case 'dashboard': await renderPanel(contenedor); break;
      case 'orders': await renderPedidos(contenedor, filtro); break;
      case 'tracking': renderSeguimiento(contenedor); break;
      case 'order-search': renderBuscarPedido(contenedor); break;
      case 'clients': await renderClientes(contenedor); break;
      case 'documents': await renderDocumentos(contenedor); break;
      case 'admin': renderPerfil(contenedor); break;
      default: await renderPanel(contenedor);
    }
  } catch (e) {
    contenedor.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${escaparHtml(e.message)}</p></div>`;
  }
}

// ===== MODAL =====
function abrirModal(titulo, cuerpoHtml) {
  document.getElementById('modalTitle').textContent = titulo;
  document.getElementById('modalBody').innerHTML = cuerpoHtml;
  document.getElementById('modalOverlay').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

// =============================================
// VISTA PANEL (DASHBOARD)
// =============================================
async function renderPanel(contenedor) {
  let stats, recientes;
  try {
    [stats, recientes] = await Promise.all([
      apiGet('/api/dashboard/stats'),
      apiGet('/api/dashboard/recent-orders'),
    ]);
  } catch (e) {
    contenedor.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar datos</p></div>`;
    return;
  }

  const tarjetas = [
    { icono: 'fa-shopping-cart', clase: 'azul', valor: stats.today_orders, etiqueta: 'Pedidos hoy' },
    { icono: 'fa-dollar-sign', clase: 'verde', valor: formatearMoneda(stats.today_earnings), etiqueta: 'Ganancias hoy' },
    { icono: 'fa-clock', clase: 'amarillo', valor: stats.pending_orders, etiqueta: 'Pendientes' },
    { icono: 'fa-truck', clase: 'celeste', valor: stats.in_transit, etiqueta: 'En tránsito' },
    { icono: 'fa-check-circle', clase: 'verde', valor: stats.total_deliveries, etiqueta: 'Entregas totales' },
    { icono: 'fa-users', clase: 'azul', valor: stats.total_clients, etiqueta: 'Clientes' },
    { icono: 'fa-star', clase: 'amarillo', valor: (stats.rating || 5.0).toFixed(1), etiqueta: 'Calificación' },
    { icono: 'fa-money-bill-wave', clase: 'verde', valor: formatearMoneda(stats.total_sales), etiqueta: 'Ganancias totales' }
  ];

  contenedor.innerHTML = `
    <div class="stats-grid">
      ${tarjetas.map(t => `
        <div class="stat-card">
          <div class="stat-icon ${t.clase}"><i class="fas ${t.icono}"></i></div>
          <div class="stat-number">${t.valor}</div>
          <div class="stat-label">${t.etiqueta}</div>
        </div>`).join('')}
    </div>

    <div class="section-header">
      <h3><i class="fas fa-history"></i> Pedidos recientes</h3>
      <button class="btn btn-primary" onclick="navegarA('orders', 'all')"><i class="fas fa-list"></i> Ver todos</button>
    </div>
    <div class="table-container">
      <table>
        <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
          ${recientes.length === 0 ? '<tr><td colspan="5" class="celda-vacia">Sin pedidos recientes</td></tr>' :
            recientes.map(o => `<tr>
              <td><strong>#${escaparHtml(o.numero_pedido || o.id)}</strong></td>
              <td>${escaparHtml(o.client_name || '—')}</td>
              <td>${formatearMoneda(o.total)}</td>
              <td>${badgeEstado(getEstado(o))}</td>
              <td style="font-size:0.8rem;">${formatearFecha(o.created_at)}</td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

// =============================================
// VISTA PEDIDOS
// =============================================
async function renderPedidos(contenedor, filtro, busqueda) {
  const params = [];
  if (filtro === 'today') params.push('date=' + new Date().toISOString().slice(0, 10));
  else if (filtro === 'entregado') params.push('status=entregado');
  else if (filtro === 'asignado') params.push('status=asignado');
  else if (filtro === 'en_camino') params.push('status=en_camino');
  if (busqueda) params.push('search=' + encodeURIComponent(busqueda));

  const qs = params.length ? '?' + params.join('&') : '';
  let pedidos;
  try {
    pedidos = await apiGet('/api/orders' + qs);
  } catch (e) {
    contenedor.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar pedidos</p></div>';
    return;
  }

  const titulo = filtro === 'today' ? 'Pedidos del Día' :
    filtro === 'entregado' ? 'Pedidos Entregados' :
    filtro === 'asignado' ? 'Pedidos Asignados' : 'Todos los Pedidos';

  contenedor.innerHTML = `
    <div class="section-header">
      <h3><i class="fas fa-shopping-cart"></i> ${titulo} <span class="contador-pedidos">${pedidos.length}</span></h3>
      <button class="btn btn-primary" onclick="abrirFormularioPedido()"><i class="fas fa-plus"></i> Nuevo Pedido</button>
    </div>
    <div class="table-container">
      <table>
        <thead><tr><th>#</th><th>Cliente</th><th>Productos</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
        <tbody>
          ${pedidos.length === 0 ? '<tr><td colspan="7" class="celda-vacia">No hay pedidos</td></tr>' :
            pedidos.map(o => `<tr>
              <td><strong>#${escaparHtml(o.numero_pedido || o.id)}</strong></td>
              <td><strong>${escaparHtml(o.client_name || '—')}</strong></td>
              <td style="font-size:0.78rem;">${o.items ? o.items.map(i => `${i.quantity}x ${escaparHtml(i.product_name)}`).join(', ') : '—'}</td>
              <td>${formatearMoneda(o.total)}</td>
              <td>${badgeEstado(getEstado(o))}</td>
              <td style="font-size:0.78rem;">${formatearFecha(o.created_at)}</td>
              <td>
                <div class="table-actions">
                  <button class="btn-edit" onclick="verDetallePedido(${o.id})"><i class="fas fa-eye"></i></button>
                  <button class="btn-delete" onclick="eliminarPedido(${o.id})"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function verDetallePedido(id) {
  let pedido;
  try {
    pedido = await apiGet('/api/orders/' + id);
  } catch (e) {
    mostrarToast(e.message || 'Error al cargar pedido', 'error');
    return;
  }
  const estado = getEstado(pedido);
  abrirModal(`Pedido #${escaparHtml(pedido.numero_pedido || pedido.id)}`, `
    <div class="detail-grid">
      <div class="detail-item"><span class="label">Cliente</span><span class="value">${escaparHtml(pedido.client_name || '—')}</span></div>
      <div class="detail-item"><span class="label">Teléfono</span><span class="value">${escaparHtml(pedido.client_phone || '—')}</span></div>
      <div class="detail-item"><span class="label">Dirección</span><span class="value">${escaparHtml(pedido.delivery_address || '—')}</span></div>
      <div class="detail-item"><span class="label">Estado</span><span class="value">${badgeEstado(estado)}</span></div>
      <div class="detail-item"><span class="label">Total</span><span class="value">${formatearMoneda(pedido.total)}</span></div>
      <div class="detail-item"><span class="label">Fecha</span><span class="value">${formatearFecha(pedido.created_at)}</span></div>
    </div>
    ${pedido.notes ? `<p style="font-size:0.85rem;margin-bottom:12px;"><strong>Notas:</strong> ${escaparHtml(pedido.notes)}</p>` : ''}
    <h4 style="font-size:0.9rem;margin-bottom:8px;"><i class="fas fa-box"></i> Productos</h4>
    <div class="table-container" style="margin-bottom:14px;">
      <table class="items-table">
        <thead><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr></thead>
        <tbody>
          ${(pedido.items || []).map(i => `<tr>
            <td>${escaparHtml(i.product_name)}</td>
            <td>${i.quantity}</td>
            <td>${formatearMoneda(i.price)}</td>
            <td>${formatearMoneda(i.price * i.quantity)}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>
    <div class="form-group">
      <label>Cambiar estado</label>
      <select class="status-select entrada" id="selectEstado" onchange="actualizarEstadoPedido(${pedido.id}, this.value)">
        ${Object.keys(ETIQUETAS_ESTADO).map(s => `<option value="${s}" ${estado === s ? 'selected' : ''}>${ETIQUETAS_ESTADO[s]}</option>`).join('')}
      </select>
    </div>
  `);
}

async function actualizarEstadoPedido(id, estado) {
  try {
    await apiActualizar('/api/orders/' + id, { status: estado });
    mostrarToast('Estado actualizado a ' + (ETIQUETAS_ESTADO[estado] || estado), 'success');
    cerrarModal();
    renderVista(vistaActual, filtroActual);
  } catch (e) {
    mostrarToast(e.message, 'error');
  }
}

async function eliminarPedido(id) {
  if (!confirm('¿Eliminar este pedido?')) return;
  try {
    await apiEliminar('/api/orders/' + id);
    mostrarToast('Pedido eliminado', 'success');
    renderVista(vistaActual, filtroActual);
  } catch (e) {
    mostrarToast(e.message, 'error');
  }
}

async function abrirFormularioPedido() {
  let clientes = [];
  try { clientes = await apiGet('/api/clients'); } catch (e) {}
  abrirModal('Nuevo Pedido', `
    <form id="formularioPedido">
      <div class="form-group">
        <label>Cliente *</label>
        <select id="clientePedido" class="entrada" required>
          <option value="">Seleccionar cliente</option>
          ${clientes.map(c => `<option value="${c.id}">${escaparHtml(c.name)}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="notasPedido" class="entrada" rows="3"></textarea>
      </div>
      <h4 style="font-size:0.9rem;margin-bottom:10px;">Productos</h4>
      <div id="itemsPedido">
        <div class="form-row" style="margin-bottom:10px;gap:8px;">
          <div class="form-group"><input type="text" class="entrada" placeholder="Nombre del producto" id="prodNombre"></div>
          <div class="form-group"><input type="number" class="entrada" placeholder="Precio" id="prodPrecio" min="0" step="0.01"></div>
          <div class="form-group"><input type="number" class="entrada" placeholder="Cantidad" id="prodCantidad" value="1" min="1"></div>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Crear Pedido</button>
      </div>
    </form>
  `);

  document.getElementById('formularioPedido').addEventListener('submit', async (e) => {
    e.preventDefault();
    const client_id = parseInt(document.getElementById('clientePedido').value);
    const nombre = document.getElementById('prodNombre').value.trim();
    const precio = parseFloat(document.getElementById('prodPrecio').value);
    const cantidad = parseInt(document.getElementById('prodCantidad').value);

    if (!client_id || !nombre || !precio || !cantidad) {
      mostrarToast('Completa cliente, producto, precio y cantidad', 'warning');
      return;
    }
    try {
      const total = precio * cantidad;
      await apiEnviar('/api/orders', { client_id, items: [{ product_name: nombre, price: precio, quantity: cantidad }], total, notes: document.getElementById('notasPedido').value });
      mostrarToast('Pedido creado correctamente', 'success');
      cerrarModal();
      renderVista('orders', filtroActual);
    } catch (err) {
      mostrarToast(err.message, 'error');
    }
  });
}

// =============================================
// VISTA SEGUIMIENTO (mapa completo como seguimiento.php)
// =============================================
function renderSeguimiento(contenedor) {
  contenedor.innerHTML = `
    <div class="section-header">
      <h3><i class="fas fa-map-marked-alt"></i> Rastreo tu Pedido</h3>
      <span class="etiqueta-vivo"><span class="punto"></span> En vivo</span>
    </div>
    <div class="tracking-layout">
      <!-- CONTENEDOR DEL MAPA -->
      <div class="tracking-map-container">
        <div id="trackingMap"></div>

        <!-- PLANIFICADOR DE RUTA -->
        <div class="panel-controles colapsado" id="panelControles">
          <button class="boton-toggle-panel" id="botonTogglePanel" title="Planificador de ruta">
            <i class="fas fa-route"></i>
          </button>
          <div class="cabecera-panel">
            <div class="titulo-panel"><i class="fas fa-route"></i> Planificador de Ruta</div>
            <div class="acciones-panel">
              <button class="boton-mini-panel" id="botonUbicar" title="Ubicar mi posición"><i class="fas fa-crosshairs"></i></button>
              <button class="boton-mini-panel" id="botonCerrarPanel" title="Cerrar panel"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div class="contenido-panel">
            <div class="grupo-entrada">
              <div class="cabecera-entrada">
                <label class="etiqueta-entrada"><i class="fas fa-map-marker-alt"></i> Punto de Origen</label>
                <button class="accion-entrada" id="usarUbicacion"><i class="fas fa-crosshairs"></i> Usar mi ubicación</button>
              </div>
              <div class="envoltura-entrada">
                <i class="icono-entrada fas fa-circle"></i>
                <input type="text" id="direccionOrigen" class="entrada-direccion" placeholder="Ubicación actual" readonly>
              </div>
            </div>
            <div class="grupo-entrada">
              <div class="cabecera-entrada">
                <label class="etiqueta-entrada"><i class="fas fa-flag-checkered"></i> Punto de Destino</label>
                <span style="font-size:0.65rem;color:var(--texto-secundario);">*Requerido</span>
              </div>
              <div class="envoltura-entrada">
                <i class="icono-entrada fas fa-map-pin"></i>
                <input type="text" id="direccionDestino" class="entrada-direccion" placeholder="Ingresa dirección de entrega">
              </div>
              <div class="sugerencias">
                <div class="sugerencia" data-direccion="Carrera 15 #88-64, Medellín"><i class="fas fa-home"></i><span>Oficina Principal</span></div>
                <div class="sugerencia" data-direccion="Calle 100 #15-20, Medellín"><i class="fas fa-store"></i><span>Tienda Angelow</span></div>
              </div>
            </div>
            <div class="acciones-ruta">
              <button id="botonCalcularRuta" class="boton boton-primario boton-ancho"><i class="fas fa-route"></i> Calcular Ruta</button>
              <div class="acciones-secundarias">
                <button id="botonLimpiarRuta" class="boton boton-secundario" disabled><i class="fas fa-times"></i> Limpiar</button>
                <button id="botonGuardarRuta" class="boton boton-contorno" disabled><i class="fas fa-bookmark"></i> Guardar</button>
              </div>
            </div>
            <div class="tarjeta-info-ruta">
              <div class="titulo-info"><i class="fas fa-info-circle"></i> Información de Ruta</div>
              <div class="grid-info">
                <div class="info-item resaltado">
                  <div class="info-icono"><i class="fas fa-road"></i></div>
                  <div><span class="info-etiqueta">Distancia</span><span class="info-valor" id="infoDistancia">--</span></div>
                </div>
                <div class="info-item resaltado">
                  <div class="info-icono"><i class="fas fa-clock"></i></div>
                  <div><span class="info-etiqueta">Tiempo</span><span class="info-valor" id="infoTiempo">--</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PANEL DE ESTADO -->
        <div class="panel-estado">
          <div class="cabecera-estado">
            <div class="titulo-estado">
              <div class="indicador-estado">
                <div class="punto-estado esperando" id="puntoEstado"></div>
                <span id="tituloEstado">Estado: Esperando</span>
              </div>
              <button class="boton-refrescar" id="botonRefrescarEstado" title="Actualizar"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="estado-meta"><span id="metaEstado">Actualizado: ahora</span></div>
          </div>
          <div class="contenido-estado">
            <div class="mensaje-estado"><i class="fas fa-info-circle"></i><p id="mensajeEstado">Selecciona un pedido para comenzar</p></div>
            <div class="detalle-grid-seguimiento">
              <div class="detalle-item-seguimiento"><i class="fas fa-user"></i><div><span class="etiqueta">Repartidor</span><span class="valor" id="detalleRepartidor">—</span></div></div>
              <div class="detalle-item-seguimiento"><i class="fas fa-phone"></i><div><span class="etiqueta">Contacto</span><span class="valor" id="detalleContacto">—</span></div></div>
            </div>
          </div>
        </div>

        <!-- INDICADOR GPS -->
        <div class="indicador-gps">
          <i class="fas fa-satellite"></i>
          <div class="texto-gps">
            <span class="estado-gps">GPS Activo</span>
            <span class="precision-gps" id="precisionGps">Precisión: --</span>
          </div>
        </div>

        <!-- SELECTOR DE CAPAS -->
        <div class="selector-capas" id="selectorCapas">
          <button class="boton-capa activo" data-capa="calle" title="Mapa callejero"><i class="fas fa-map"></i></button>
          <button class="boton-capa" data-capa="satelite" title="Vista satélite"><i class="fas fa-globe"></i></button>
          <button class="boton-capa" data-capa="oscuro" title="Modo oscuro"><i class="fas fa-moon"></i></button>
        </div>
      </div>

      <!-- PANEL DE SEGUIMIENTO (derecha) -->
      <div class="tracking-sidebar">
        <div class="tracking-sidebar-header">
          <div class="principal-seguimiento">
            <h2><i class="fas fa-box-open"></i> Seguimiento</h2>
            <span class="badge-estado-pedido activo"><i class="fas fa-circle"></i> Activo</span>
          </div>
          <div class="secundario-seguimiento">
            <div class="meta-pedido">
              <div class="meta-item"><span class="etiqueta">Pedido</span><span class="valor" id="metaPedido">—</span></div>
              <div class="meta-item"><span class="etiqueta">Fecha</span><span class="valor" id="metaFecha">—</span></div>
            </div>
            <button class="btn btn-secondary" style="padding:6px 12px;" onclick="cargarPedidosSeguimiento()"><i class="fas fa-sync-alt"></i></button>
          </div>
        </div>
        <div class="contenido-seguimiento">
          <!-- LÍNEA DE TIEMPO -->
          <div class="linea-progreso">
            <div class="cabecera-progreso">
              <h3><i class="fas fa-history"></i> Progreso</h3>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="barra-progreso"><div class="relleno-progreso" id="rellenoProgreso" style="width:0%"></div></div>
                <span class="texto-progreso" id="textoProgreso">0%</span>
              </div>
            </div>
            <div class="pasos-timeline" id="pasosTimeline">
              <div class="paso"><div class="icono-paso"><i class="fas fa-clipboard-check"></i></div><div class="contenido-paso"><h4>Confirmado</h4></div><span class="hora-paso" id="horaConfirmado">--:--</span></div>
              <div class="paso"><div class="icono-paso"><i class="fas fa-warehouse"></i></div><div class="contenido-paso"><h4>Preparado</h4></div><span class="hora-paso" id="horaPreparado">--:--</span></div>
              <div class="paso"><div class="icono-paso"><i class="fas fa-shipping-fast"></i></div><div class="contenido-paso"><h4>En Camino</h4></div><span class="hora-paso" id="horaEnCamino">--:--</span></div>
              <div class="paso"><div class="icono-paso"><i class="fas fa-home"></i></div><div class="contenido-paso"><h4>Entregado</h4></div><span class="hora-paso" id="horaEntregado">--:--</span></div>
            </div>
          </div>

          <!-- TARJETA REPARTIDOR -->
          <div class="tarjeta-repartidor">
            <div class="cabecera-tarjeta"><h3><i class="fas fa-user-circle"></i> Repartidor</h3></div>
            <div class="contenido-tarjeta">
              <div class="perfil-repartidor">
                <div class="avatar-repartidor" id="avatarRepartidor">—</div>
                <div class="info-repartidor">
                  <h4 id="nombreRepartidor">-</h4>
                  <div class="estrellas"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                </div>
              </div>
              <div class="contacto-repartidor">
                <button class="boton-contacto llamar" id="botonLlamar"><i class="fas fa-phone"></i> Llamar</button>
                <button class="boton-contacto mensaje" id="botonMensaje"><i class="fas fa-comment"></i> Mensaje</button>
                <button class="boton-contacto ubicar" id="botonUbicarRepartidor"><i class="fas fa-map-marker-alt"></i> Ubicar</button>
              </div>
            </div>
          </div>

          <!-- TARJETA ESTIMACIÓN -->
          <div class="tarjeta-estimacion">
            <div class="cabecera-tarjeta"><h3><i class="fas fa-clock"></i> Estimación</h3></div>
            <div class="contenido-tarjeta">
              <div class="principal-estimacion">
                <span class="tiempo-valor" id="tiempoEstimado">--</span> <span class="tiempo-unidad">min</span>
              </div>
              <div class="detalles-estimacion">
                <div class="detalle"><i class="fas fa-road"></i><span><strong id="distanciaEstimada">--</strong></span></div>
                <div class="detalle"><i class="fas fa-traffic-light"></i><span>Normal</span></div>
              </div>
            </div>
          </div>

          <!-- LISTA DE PEDIDOS ACTIVOS -->
          <div id="listaPedidosSeguimiento" class="lista-pedidos-seguimiento"></div>
        </div>
      </div>
    </div>
  `;

  setTimeout(() => {
    inicializarMapaSeguimiento();
    inicializarPanelControles();
    inicializarSelectorCapas();
    cargarPedidosSeguimiento();
    activarGeolocalizacion();
    conectarSocketSeguimiento();
    intervaloActualizacion = setInterval(() => {
      if (document.getElementById('trackingMap')) cargarPedidosSeguimiento(true);
    }, 15000);
  }, 120);
}

function inicializarMapaSeguimiento() {
  const el = document.getElementById('trackingMap');
  if (!el || mapaSeguimiento) return;

  mapaSeguimiento = L.map('trackingMap').setView([6.2442, -75.5812], 13);
  capasMapa.calle = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(mapaSeguimiento);
  capasMapa.satelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Esri' });
  capasMapa.oscuro = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '© OSM © CARTO' });

  mapaSeguimiento.on('click', (e) => {
    document.getElementById('direccionDestino').value = e.latlng.lat.toFixed(5) + ', ' + e.latlng.lng.toFixed(5);
  });
}

function inicializarPanelControles() {
  const panel = document.getElementById('panelControles');
  document.getElementById('botonTogglePanel').addEventListener('click', () => {
    panel.classList.remove('colapsado');
    panel.classList.add('expandido');
  });
  document.getElementById('botonCerrarPanel').addEventListener('click', () => {
    panel.classList.add('colapsado');
    panel.classList.remove('expandido');
  });
  document.getElementById('usarUbicacion').addEventListener('click', activarGeolocalizacion);
  document.getElementById('botonUbicar').addEventListener('click', activarGeolocalizacion);
  document.getElementById('botonCalcularRuta').addEventListener('click', calcularRuta);
  document.getElementById('botonLimpiarRuta').addEventListener('click', limpiarRuta);
  document.getElementById('botonGuardarRuta').addEventListener('click', () => mostrarToast('Ruta guardada (demo)', 'info'));
  document.querySelectorAll('.sugerencia').forEach(sug => {
    sug.addEventListener('click', () => {
      document.getElementById('direccionDestino').value = sug.getAttribute('data-direccion');
    });
  });
  document.getElementById('direccionDestino').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') calcularRuta();
  });
  document.getElementById('botonRefrescarEstado').addEventListener('click', () => {
    cargarPedidosSeguimiento(true);
    actualizarEstadoMapa('listado', 'Actualizado', 'Datos refrescados');
  });
  document.getElementById('botonUbicarRepartidor').addEventListener('click', () => {
    if (marcadorConductor) {
      mapaSeguimiento.setView(marcadorConductor.getLatLng(), 16);
    } else {
      mostrarToast('No hay posición del repartidor disponible', 'info');
    }
  });
  document.getElementById('botonMensaje').addEventListener('click', () => {
    mostrarToast('El chat se abrirá próximamente', 'info');
  });
}

function conectarSocketSeguimiento() {
  socket.off('order:statusChanged');
  socket.on('order:statusChanged', (data) => {
    mostrarToast('Pedido #' + (data.id || '') + ': ' + (ETIQUETAS_ESTADO[data.status] || data.status || ''), 'info');
    agregarNotificacion('Pedido #' + (data.id || '') + ' cambió de estado');
    cargarPedidosSeguimiento(true);
  });

  socket.off('order:created');
  socket.on('order:created', () => {
    cargarPedidosSeguimiento(true);
  });

  socket.off('tracking:update');
  socket.on('tracking:update', (data) => {
    if (data && data.latitud && data.longitud) {
      if (!marcadorConductor) {
        marcadorConductor = L.marker([data.latitud, data.longitud]).addTo(mapaSeguimiento).bindPopup('🚚 Repartidor');
      } else {
        marcadorConductor.setLatLng([data.latitud, data.longitud]);
      }
    }
  });
}

function activarGeolocalizacion() {
  if (!navigator.geolocation) return;
  if (watchGps !== null) { navigator.geolocation.clearWatch(watchGps); watchGps = null; }
  watchGps = navigator.geolocation.watchPosition(
    (pos) => {
      puntoInicio = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      document.getElementById('direccionOrigen').value = 'Tu ubicación actual';
      document.getElementById('precisionGps').textContent = 'Precisión: ' + Math.round(pos.coords.accuracy) + 'm';
      if (mapaSeguimiento) {
        if (!marcadorInicio) {
          marcadorInicio = L.marker([puntoInicio.lat, puntoInicio.lng]).addTo(mapaSeguimiento).bindPopup('📍 Tu ubicación');
        } else {
          marcadorInicio.setLatLng([puntoInicio.lat, puntoInicio.lng]);
        }
      }
      // Enviar ubicación al servidor si hay pedido seleccionado
      if (pedidoSeleccionado && usuarioActual) {
        apiEnviar('/api/tracking/ubicacion', {
          repartidor_id: usuarioActual.id,
          pedido_id: pedidoSeleccionado.id,
          latitud: pos.coords.latitude,
          longitud: pos.coords.longitude,
          velocidad_kmh: pos.coords.speed || 0,
          bateria_porcentaje: 100
        }).catch(() => {});
      }
    },
    () => mostrarToast('No se pudo obtener la ubicación', 'error'),
    { enableHighAccuracy: true, timeout: 8000, maximumAge: 10000 }
  );
}

function inicializarSelectorCapas() {
  document.querySelectorAll('.boton-capa').forEach(btn => {
    btn.addEventListener('click', () => {
      const capa = btn.dataset.capa;
      if (capa === capaActiva) return;
      document.querySelectorAll('.boton-capa').forEach(b => b.classList.remove('activo'));
      btn.classList.add('activo');
      if (capasMapa[capaActiva] && mapaSeguimiento) mapaSeguimiento.removeLayer(capasMapa[capaActiva]);
      capaActiva = capa;
      if (capasMapa[capa] && mapaSeguimiento) capasMapa[capa].addTo(mapaSeguimiento);
    });
  });
}

function actualizarEstadoMapa(tipo, titulo, mensaje) {
  const punto = document.getElementById('puntoEstado');
  if (punto) punto.className = 'punto-estado ' + (tipo || 'esperando');
  const t = document.getElementById('tituloEstado');
  if (t) t.textContent = 'Estado: ' + (titulo || 'Esperando');
  const m = document.getElementById('mensajeEstado');
  if (m) m.textContent = mensaje || '';
  const meta = document.getElementById('metaEstado');
  if (meta) meta.textContent = 'Actualizado: ' + new Date().toLocaleTimeString('es-CO');
}

async function geocodificarDireccion(direccion) {
  const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=co&q=' + encodeURIComponent(direccion), { headers: { 'User-Agent': 'AngelowApp/1.0' } });
  const data = await res.json();
  if (!data || !data.length) throw new Error('Dirección no encontrada');
  return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon), nombre: data[0].display_name };
}

async function calcularRuta() {
  if (!puntoInicio) { mostrarToast('Primero usa "Usar mi ubicación"', 'warning'); return; }
  const destino = document.getElementById('direccionDestino').value.trim();
  if (!destino) { mostrarToast('Ingresa una dirección de destino', 'warning'); return; }

  actualizarEstadoMapa('listado', 'Calculando', 'Buscando la mejor ruta...');
  let puntoFin;
  try {
    puntoFin = await geocodificarDireccion(destino);
  } catch (e) {
    const partes = destino.split(',').map(Number);
    if (partes.length === 2 && !isNaN(partes[0]) && !isNaN(partes[1])) {
      puntoFin = { lat: partes[0], lng: partes[1], nombre: destino };
    } else {
      actualizarEstadoMapa('error', 'Error', e.message);
      mostrarToast(e.message, 'error');
      return;
    }
  }

  if (marcadorDestino) mapaSeguimiento.removeLayer(marcadorDestino);
  marcadorDestino = L.marker([puntoFin.lat, puntoFin.lng]).addTo(mapaSeguimiento).bindPopup('<b>🎯 Destino</b><br>' + escaparHtml(puntoFin.nombre));

  if (controlRuta) mapaSeguimiento.removeControl(controlRuta);
  controlRuta = L.Routing.control({
    waypoints: [L.latLng(puntoInicio.lat, puntoInicio.lng), L.latLng(puntoFin.lat, puntoFin.lng)],
    routeWhileDragging: false,
    show: false,
    lineOptions: { styles: [{ color: '#5E9DE6', weight: 5, opacity: 0.8 }] },
    createMarker: () => null
  }).addTo(mapaSeguimiento);

  controlRuta.on('routesfound', (e) => {
    const ruta = e.routes[0];
    const distancia = (ruta.summary.totalDistance / 1000).toFixed(1);
    const tiempo = Math.round(ruta.summary.totalTime / 60);
    document.getElementById('infoDistancia').textContent = distancia + ' km';
    document.getElementById('infoTiempo').textContent = tiempo + ' min';
    document.getElementById('tiempoEstimado').textContent = tiempo;
    document.getElementById('distanciaEstimada').textContent = distancia + ' km';
    actualizarEstadoMapa('viajando', 'Ruta Lista', 'Distancia: ' + distancia + ' km - Tiempo: ' + tiempo + ' min');
    document.getElementById('botonLimpiarRuta').disabled = false;
    document.getElementById('botonGuardarRuta').disabled = false;
    const grupo = new L.featureGroup([marcadorInicio, marcadorDestino]);
    mapaSeguimiento.fitBounds(grupo.getBounds().pad(0.15));
  });

  controlRuta.on('routingerror', () => {
    actualizarEstadoMapa('error', 'Error', 'No se pudo calcular la ruta');
  });
}

function limpiarRuta() {
  if (controlRuta) { mapaSeguimiento.removeControl(controlRuta); controlRuta = null; }
  if (marcadorDestino) { mapaSeguimiento.removeLayer(marcadorDestino); marcadorDestino = null; }
  document.getElementById('direccionDestino').value = '';
  document.getElementById('infoDistancia').textContent = '--';
  document.getElementById('infoTiempo').textContent = '--';
  actualizarEstadoMapa('esperando', 'Esperando', 'Selecciona un pedido para comenzar');
  document.getElementById('botonLimpiarRuta').disabled = true;
  document.getElementById('botonGuardarRuta').disabled = true;
}

function limpiarVistaSeguimiento() {
  if (intervaloActualizacion) { clearInterval(intervaloActualizacion); intervaloActualizacion = null; }
  if (watchGps !== null) { navigator.geolocation.clearWatch(watchGps); watchGps = null; }
  socket.off('order:statusChanged');
  socket.off('order:created');
  socket.off('tracking:update');
  pedidoSeleccionado = null;
  pedidosActivos = [];
  if (mapaSeguimiento) { mapaSeguimiento.remove(); mapaSeguimiento = null; }
  marcadorInicio = null; marcadorDestino = null; marcadorConductor = null;
  controlRuta = null;
  capasMapa = {};
}

async function cargarPedidosSeguimiento(silencioso) {
  const lista = document.getElementById('listaPedidosSeguimiento');
  if (!lista || !mapaSeguimiento) return;

  try {
    const todos = await apiGet('/api/orders');
    pedidosActivos = todos.filter(o => getEstado(o) !== 'entregado' && getEstado(o) !== 'cancelado');

    if (!pedidoSeleccionado && pedidosActivos.length > 0) pedidoSeleccionado = pedidosActivos[0];

    lista.innerHTML = pedidosActivos.length === 0
      ? '<div class="empty-state"><i class="fas fa-check-circle" style="color:var(--verde);"></i><p>Todos los pedidos han sido entregados</p></div>'
      : pedidosActivos.map(o => `
          <div class="tracking-card status-${getEstado(o)} ${pedidoSeleccionado && pedidoSeleccionado.id === o.id ? 'seleccionado' : ''}" data-id="${o.id}" onclick="seleccionarPedidoSeguimiento(${o.id})">
            <div class="tracking-card-header">
              <span class="tracking-id">#${escaparHtml(o.numero_pedido || o.id)}</span>
              ${badgeEstado(getEstado(o))}
            </div>
            <div class="tracking-client"><i class="fas fa-user"></i> ${escaparHtml(o.client_name || '—')}</div>
            <div class="tracking-address"><i class="fas fa-map-pin"></i> ${escaparHtml(o.delivery_address || '—')}</div>
            <div class="tracking-items"><i class="fas fa-box"></i> ${o.items ? o.items.map(i => `${i.quantity}x ${escaparHtml(i.product_name)}`).join(', ') : '—'}</div>
            <div class="tracking-card-footer">
              <span class="tracking-time">${formatearFecha(o.created_at)}</span>
              <button class="boton-avanzar" onclick="event.stopPropagation(); avanzarEstadoSeguimiento(${o.id}, '${getEstado(o)}')">
                <i class="fas fa-arrow-right"></i> ${getEstado(o) === 'en_camino' ? 'Entregar' : 'Avanzar'}
              </button>
            </div>
          </div>`).join('');

    // Agregar marcadores de destino de cada pedido
    pedidosActivos.forEach(o => {
      if (o.client_lat && o.client_lng) {
        const icono = L.divIcon({
          className: 'custom-marker',
          html: `<div style="background:#ef4444;color:#fff;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,0.2);">#${escaparHtml(o.numero_pedido || o.id)}</div>`,
          iconSize: [0, 0]
        });
        L.marker([o.client_lat, o.client_lng], { icon: icono }).addTo(mapaSeguimiento).bindPopup('<b>' + escaparHtml(o.client_name || '') + '</b><br>' + escaparHtml(o.delivery_address || ''));
      }
    });

    // Cargar seguimiento del pedido seleccionado
    if (pedidoSeleccionado) {
      try {
        const datos = await apiGet('/api/tracking/' + pedidoSeleccionado.id);
        actualizarPanelSeguimiento(pedidoSeleccionado, datos);
      } catch (e) {
        actualizarPanelSeguimiento(pedidoSeleccionado, null);
      }
    }

    if (!silencioso && pedidosActivos.length > 0) {
      const bounds = [];
      if (puntoInicio) bounds.push([puntoInicio.lat, puntoInicio.lng]);
      pedidosActivos.forEach(o => { if (o.client_lat && o.client_lng) bounds.push([o.client_lat, o.client_lng]); });
      if (bounds.length > 0) mapaSeguimiento.fitBounds(bounds, { padding: [40, 40] });
    }
  } catch (e) {
    if (!silencioso) lista.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar seguimiento</p></div>';
  }
}

async function seleccionarPedidoSeguimiento(id) {
  pedidoSeleccionado = pedidosActivos.find(o => o.id === id) || null;
  cargarPedidosSeguimiento(true);
}

async function avanzarEstadoSeguimiento(id, estadoActual) {
  const siguiente = { pendiente: 'asignado', asignado: 'en_camino', confirmado: 'en_camino', procesando: 'en_camino', listo: 'en_camino', en_camino: 'entregado' };
  const nuevo = siguiente[estadoActual];
  if (!nuevo) { mostrarToast('No hay siguiente estado', 'info'); return; }
  try {
    await apiActualizar('/api/orders/' + id, { status: nuevo });
    mostrarToast('Pedido #' + id + ' → ' + (ETIQUETAS_ESTADO[nuevo] || nuevo), 'success');
    cargarPedidosSeguimiento(true);
  } catch (e) {
    mostrarToast(e.message, 'error');
  }
}

function actualizarPanelSeguimiento(pedido, datos) {
  document.getElementById('metaPedido').textContent = '#' + (pedido.numero_pedido || pedido.id);
  document.getElementById('metaFecha').textContent = formatearFecha(pedido.created_at).split(',')[0] || '—';

  const estado = getEstado(pedido);
  const ordenEstados = ['pendiente', 'asignado', 'confirmado', 'procesando', 'listo', 'en_camino', 'entregado'];
  const indice = ordenEstados.indexOf(estado);
  const totalPasos = 4;
  const progreso = indice < 0 ? 0 : Math.round(Math.min(indice, 3) / 3 * 100);
  document.getElementById('rellenoProgreso').style.width = progreso + '%';
  document.getElementById('textoProgreso').textContent = progreso + '%';

  const pasos = document.querySelectorAll('#pasosTimeline .paso');
  const pasoActivo = estado === 'en_camino' ? 2 : estado === 'entregado' ? 3 : (estado === 'confirmado' || estado === 'procesando') ? 1 : 0;
  pasos.forEach((paso, idx) => {
    paso.classList.remove('completado', 'activo');
    if (idx < pasoActivo) paso.classList.add('completado');
    else if (idx === pasoActivo) paso.classList.add('activo');
  });

  const ahora = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
  document.getElementById('horaConfirmado').textContent = pedido.created_at ? formatearFecha(pedido.created_at).split(',')[1] || '--:--' : '--:--';
  if (pasoActivo >= 1) document.getElementById('horaPreparado').textContent = ahora;
  if (pasoActivo >= 2) document.getElementById('horaEnCamino').textContent = ahora;
  if (pasoActivo >= 3) document.getElementById('horaEntregado').textContent = ahora;

  // Repartidor
  const repartidor = (datos && datos.repartidor) || {};
  document.getElementById('nombreRepartidor').textContent = repartidor.nombre || 'Repartidor Angelow';
  document.getElementById('avatarRepartidor').textContent = obtenerIniciales(repartidor.nombre || 'RA');
  document.getElementById('detalleRepartidor').textContent = repartidor.nombre || '—';
  document.getElementById('detalleContacto').textContent = repartidor.telefono || '---';
  document.getElementById('botonLlamar').onclick = () => {
    if (repartidor.telefono) window.location.href = 'tel:' + repartidor.telefono;
  };

  // Ubicación GPS del repartidor si existe
  if (datos && datos.ubicacion && datos.ubicacion.latitud) {
    if (!marcadorConductor) {
      marcadorConductor = L.marker([datos.ubicacion.latitud, datos.ubicacion.longitud]).addTo(mapaSeguimiento).bindPopup('🚚 Repartidor');
    } else {
      marcadorConductor.setLatLng([datos.ubicacion.latitud, datos.ubicacion.longitud]);
    }
  }

  // Estimación
  document.getElementById('distanciaEstimada').textContent = '--';
  if (!document.getElementById('tiempoEstimado').textContent || document.getElementById('tiempoEstimado').textContent === '--') {
    document.getElementById('tiempoEstimado').textContent = '15';
  }

  // Estado del mapa
  const tipos = { pendiente: 'esperando', asignado: 'esperando', en_camino: 'viajando', entregado: 'completado', cancelado: 'error' };
  actualizarEstadoMapa(tipos[estado] || 'listado', ETIQUETAS_ESTADO[estado] || estado, 'Pedido #' + (pedido.numero_pedido || pedido.id) + ' - ' + (pedido.client_name || ''));
}

// =============================================
// VISTA BUSCAR PEDIDO
// =============================================
function renderBuscarPedido(contenedor) {
  contenedor.innerHTML = `
    <div class="section-header">
      <h3><i class="fas fa-search"></i> Estado del Pedido</h3>
    </div>
    <p style="color:var(--texto-secundario);font-size:0.85rem;margin-bottom:16px;">Ingresa el número de pedido para consultar su estado</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
      <input type="number" class="entrada" id="inputBuscarPedido" placeholder="Ej: 1, 2, 3..." style="flex:1;min-width:200px;">
      <button class="boton boton-primario" onclick="buscarPedido()"><i class="fas fa-search"></i> Buscar</button>
    </div>
    <div id="resultadoBusqueda"></div>
  `;
  document.getElementById('inputBuscarPedido').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') buscarPedido();
  });
}

async function buscarPedido() {
  const id = document.getElementById('inputBuscarPedido').value;
  const resultado = document.getElementById('resultadoBusqueda');
  if (!id) { mostrarToast('Ingresa un número de pedido', 'warning'); return; }
  resultado.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Buscando...</p></div>';
  try {
    const pedido = await apiGet('/api/orders/' + id);
    const estado = getEstado(pedido);
    resultado.innerHTML = `
      <div class="table-container">
        <div style="padding:20px;">
          <div class="detail-grid">
            <div class="detail-item"><span class="label">Pedido</span><span class="value">#${escaparHtml(pedido.numero_pedido || pedido.id)}</span></div>
            <div class="detail-item"><span class="label">Cliente</span><span class="value">${escaparHtml(pedido.client_name || '—')}</span></div>
            <div class="detail-item"><span class="label">Teléfono</span><span class="value">${escaparHtml(pedido.client_phone || '—')}</span></div>
            <div class="detail-item"><span class="label">Dirección</span><span class="value">${escaparHtml(pedido.delivery_address || '—')}</span></div>
            <div class="detail-item"><span class="label">Total</span><span class="value">${formatearMoneda(pedido.total)}</span></div>
            <div class="detail-item"><span class="label">Estado</span><span class="value">${badgeEstado(estado)}</span></div>
            <div class="detail-item"><span class="label">Fecha</span><span class="value">${formatearFecha(pedido.created_at)}</span></div>
          </div>
          <h4 style="font-size:0.9rem;margin:14px 0 8px;"><i class="fas fa-box"></i> Productos</h4>
          <div class="table-container">
            <table class="items-table">
              <thead><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr></thead>
              <tbody>
                ${(pedido.items || []).map(i => `<tr>
                  <td>${escaparHtml(i.product_name)}</td>
                  <td>${i.quantity}</td>
                  <td>${formatearMoneda(i.price)}</td>
                  <td>${formatearMoneda(i.price * i.quantity)}</td>
                </tr>`).join('')}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    `;
  } catch (e) {
    resultado.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Pedido #${escaparHtml(id)} no encontrado</p></div>`;
  }
}

// =============================================
// VISTA CLIENTES
// =============================================
async function renderClientes(contenedor, busqueda) {
  const q = busqueda ? '?search=' + encodeURIComponent(busqueda) : '';
  let clientes;
  try {
    clientes = await apiGet('/api/clients' + q);
  } catch (e) {
    contenedor.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>No hay clientes asociados</p></div>';
    return;
  }

  contenedor.innerHTML = `
    <div class="section-header">
      <h3><i class="fas fa-users"></i> Clientes <span class="contador-pedidos">${clientes.length}</span></h3>
    </div>
    <div class="table-container">
      <table>
        <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Dirección</th></tr></thead>
        <tbody>
          ${clientes.length === 0 ? '<tr><td colspan="5" class="celda-vacia">No hay clientes</td></tr>' :
            clientes.map(c => `<tr>
              <td>${c.id}</td>
              <td><strong>${escaparHtml(c.name || '—')}</strong></td>
              <td>${escaparHtml(c.email || '—')}</td>
              <td>${escaparHtml(c.phone || '—')}</td>
              <td style="font-size:0.8rem;">${escaparHtml(c.address || '—')}</td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

// =============================================
// VISTA DOCUMENTOS
// =============================================
async function renderDocumentos(contenedor) {
  contenedor.innerHTML = `
    <div class="section-header">
      <h3><i class="fas fa-file-alt"></i> Mis Documentos</h3>
      <button class="boton boton-primario" onclick="subirDocumento()"><i class="fas fa-upload"></i> Subir Documento</button>
    </div>
    <div id="listaDocumentos"><div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div></div>
  `;
  cargarDocumentos();
}

async function cargarDocumentos() {
  const lista = document.getElementById('listaDocumentos');
  if (!lista) return;
  try {
    const res = await apiGet('/api/documents');
    const docs = res.documentos || [];
    lista.innerHTML = docs.length === 0
      ? '<div class="empty-state"><i class="fas fa-file-alt"></i><p>No has subido documentos</p></div>'
      : `<div class="table-container"><table>
          <thead><tr><th>Tipo</th><th>Estado</th><th>Fecha</th><th>Observaciones</th></tr></thead>
          <tbody>
            ${docs.map(d => `<tr>
              <td><strong>${escaparHtml((d.tipo || '').toUpperCase())}</strong></td>
              <td><span class="estado-documento ${escaparHtml(d.estado || 'pendiente')}">${escaparHtml((d.estado || 'pendiente').toUpperCase())}</span></td>
              <td>${formatearFecha(d.fecha_subida)}</td>
              <td style="font-size:0.8rem;">${escaparHtml(d.observaciones || '—')}</td>
            </tr>`).join('')}
          </tbody>
        </table></div>`;
  } catch (e) {
    if (lista) lista.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar documentos</p></div>';
  }
}

function subirDocumento() {
  abrirModal('Subir Documento', `
    <form id="formularioDocumento">
      <div class="form-group">
        <label>Tipo de documento *</label>
        <select class="entrada" name="tipo" required>
          <option value="">Seleccionar</option>
          <option value="pase">Pase</option>
          <option value="soat">SOAT</option>
          <option value="tecnomecanica">Tecnomecánica</option>
          <option value="cedula">Cédula</option>
        </select>
      </div>
      <div class="form-group">
        <label>Archivo (PDF, JPG, PNG) *</label>
        <input type="file" class="entrada" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Subir</button>
      </div>
    </form>
  `);

  document.getElementById('formularioDocumento').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await apiSubir('/api/documents/subir', fd);
      mostrarToast('Documento subido correctamente', 'success');
      cerrarModal();
      renderVista('documents');
    } catch (err) {
      mostrarToast(err.message, 'error');
    }
  });
}

// =============================================
// VISTA PERFIL
// =============================================
function renderPerfil(contenedor) {
  const u = usuarioActual || {};
  const nombre = (u.nombres || '') + ' ' + (u.apellidos || '');
  const docEstado = u.documentos_completos
    ? '<span style="color:var(--verde);"><i class="fas fa-check-circle"></i> Completos</span>'
    : '<span style="color:var(--rojo);"><i class="fas fa-exclamation-circle"></i> Pendientes</span>';

  contenedor.innerHTML = `
    <div class="section-header">
      <h3><i class="fas fa-user-circle"></i> Mi Perfil</h3>
    </div>
    <div class="profile-card">
      <div class="profile-avatar">${obtenerIniciales(nombre)}</div>
      <div class="profile-info">
        <h2>${escaparHtml(nombre || 'Repartidor')}</h2>
        <p class="profile-email">${escaparHtml(u.email || '')}</p>
        <p class="profile-rol">${escaparHtml(u.rol || 'Repartidor')}</p>
      </div>
    </div>
    <div class="profile-details">
      <div class="detail-item"><span class="label">Documentos</span><span class="value">${docEstado}</span></div>
      <div class="detail-item"><span class="label">Teléfono</span><span class="value">${escaparHtml(u.telefono || '—')}</span></div>
      <div class="detail-item"><span class="label">Dirección</span><span class="value">${escaparHtml(u.direccion || '—')}</span></div>
      <div class="detail-item"><span class="label">Vehículo</span><span class="value">${escaparHtml(u.tipo_vehiculo || '—')} ${u.placa_vehiculo ? '(' + escaparHtml(u.placa_vehiculo) + ')' : ''}</span></div>
      <div class="detail-item"><span class="label">Entregas totales</span><span class="value">${u.total_entregas || 0}</span></div>
      <div class="detail-item"><span class="label">Calificación</span><span class="value">${(u.calificacion_promedio || 5.0).toFixed(1)} ⭐</span></div>
    </div>
    <div style="margin-top:20px;text-align:right;">
      <button class="boton boton-rojo" onclick="cerrarSesion()"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
    </div>
  `;
}

// =============================================
// NOTIFICACIONES
// =============================================
function agregarNotificacion(mensaje) {
  contadorNotificaciones++;
  const badge = document.getElementById('notifCount');
  if (badge) badge.textContent = contadorNotificaciones;
  mostrarToast(mensaje, 'info');
}

// =============================================
// BÚSQUEDA GLOBAL
// =============================================
let temporizadorBusqueda;
function manejarBusqueda(term) {
  if (vistaActual === 'orders') renderPedidos(document.getElementById('viewContainer'), filtroActual, term);
  else if (vistaActual === 'clients') renderClientes(document.getElementById('viewContainer'), term);
}

// =============================================
// INICIALIZACIÓN
// =============================================
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('logoutBtn').addEventListener('click', cerrarSesion);
  document.getElementById('avatarBtn').addEventListener('click', () => navegarA('admin'));
  document.getElementById('modalClose').addEventListener('click', cerrarModal);
  document.getElementById('modalOverlay').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) cerrarModal();
  });
  document.getElementById('menuToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
  });
  document.getElementById('notifBtn').addEventListener('click', () => {
    if (contadorNotificaciones === 0) mostrarToast('No tienes notificaciones nuevas', 'info');
    else mostrarToast(contadorNotificaciones + ' notificación(es) pendientes', 'info');
  });

  // Navegación del sidebar
  document.querySelectorAll('.sidebar-menu li[data-view]').forEach(item => {
    item.addEventListener('click', () => {
      navegarA(item.dataset.view, item.dataset.filter || null);
    });
  });

  // Búsqueda con debounce
  const buscar = document.getElementById('searchInput');
  if (buscar) {
    buscar.addEventListener('input', (e) => {
      clearTimeout(temporizadorBusqueda);
      temporizadorBusqueda = setTimeout(() => {
        const term = e.target.value.trim();
        if (vistaActual === 'orders') renderPedidos(document.getElementById('viewContainer'), filtroActual, term);
        else if (vistaActual === 'clients') renderClientes(document.getElementById('viewContainer'), term);
      }, 300);
    });
  }

  // Cerrar sidebar con ESC y al hacer clic fuera
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.getElementById('sidebar').classList.remove('open');
      cerrarModal();
    }
  });
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 992) {
      const sidebar = document.getElementById('sidebar');
      const toggle = document.getElementById('menuToggle');
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    }
  });

  verificarAuth();
});