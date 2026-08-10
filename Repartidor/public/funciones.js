const socket = io();
let currentView = 'dashboard';
let currentFilter = null;
let notifCount = 0;
let currentUser = null;

// ===== TOKEN MANAGEMENT =====
function getToken() { return localStorage.getItem('repartidor_token'); }
function setToken(t) { localStorage.setItem('repartidor_token', t); }
function clearToken() { localStorage.removeItem('repartidor_token'); currentUser = null; }

// ===== HELPERS =====
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatCurrency(n) {
  return '$' + Number(n).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const STATUS_LABELS = {
  pendiente: 'Pendiente',
  asignado: 'Asignado',
  en_camino: 'En Camino',
  entregado: 'Entregado',
  cancelado: 'Cancelado',
};

function statusBadge(status) {
  return `<span class="status-badge status-${status}">${STATUS_LABELS[status] || status}</span>`;
}

function showToast(msg, type) {
  const el = document.createElement('div');
  el.className = `toast-item toast-${type || 'info'}`;
  el.textContent = msg;
  const container = document.getElementById('toast');
  container.appendChild(el);
  setTimeout(() => { el.remove(); }, 3500);
}

// ===== API HELPERS WITH AUTH =====
function authHeaders() {
  const t = getToken();
  return t ? { 'Authorization': 'Bearer ' + t, 'Content-Type': 'application/json' } : { 'Content-Type': 'application/json' };
}

async function apiGet(url) {
  const res = await fetch(url, { headers: authHeaders() });
  if (res.status === 401) { handleUnauth(); throw new Error('No autorizado'); }
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}

async function apiPost(url, data) {
  const res = await fetch(url, { method: 'POST', headers: authHeaders(), body: JSON.stringify(data) });
  if (res.status === 401) { handleUnauth(); throw new Error('No autorizado'); }
  if (!res.ok) { const err = await res.json(); throw new Error(err.error || 'Error'); }
  return res.json();
}

async function apiPut(url, data) {
  const res = await fetch(url, { method: 'PUT', headers: authHeaders(), body: JSON.stringify(data) });
  if (res.status === 401) { handleUnauth(); throw new Error('No autorizado'); }
  if (!res.ok) { const err = await res.json(); throw new Error(err.error || 'Error'); }
  return res.json();
}

async function apiDelete(url) {
  const res = await fetch(url, { method: 'DELETE', headers: authHeaders() });
  if (res.status === 401) { handleUnauth(); throw new Error('No autorizado'); }
  if (!res.ok) { const err = await res.json(); throw new Error(err.error || 'Error'); }
  return res.json();
}

async function apiUpload(url, formData) {
  const headers = {};
  const t = getToken();
  if (t) headers['Authorization'] = 'Bearer ' + t;
  const res = await fetch(url, { method: 'POST', headers, body: formData });
  if (res.status === 401) { handleUnauth(); throw new Error('No autorizado'); }
  if (!res.ok) { const err = await res.json(); throw new Error(err.error || 'Error'); }
  return res.json();
}

function handleUnauth() {
  clearToken();
  showToast('Sesión expirada', 'error');
  showLogin();
}

// ===== LOGIN / LOGOUT =====
function showLogin() {
  document.getElementById('loginScreen').style.display = 'flex';
  document.getElementById('appContainer').style.display = 'none';
}

function showApp() {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('appContainer').style.display = 'block';
}

async function checkAuth() {
  const token = getToken();
  if (!token) { showLogin(); return false; }
  try {
    const res = await apiGet('/api/auth/me');
    if (res.success) {
      currentUser = res.user;
      document.getElementById('avatarText').textContent = (res.user.nombres || 'R').charAt(0).toUpperCase();
      showApp();
      renderView('dashboard');
      return true;
    }
    clearToken();
    showLogin();
    return false;
  } catch (e) {
    clearToken();
    showLogin();
    return false;
  }
}

document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('loginEmail').value;
  const password = document.getElementById('loginPassword').value;
  const errorEl = document.getElementById('loginError');
  errorEl.textContent = '';

  try {
    const res = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (!data.success) {
      errorEl.textContent = data.error || 'Credenciales inválidas';
      return;
    }
    setToken(data.token);
    currentUser = data.user;
    document.getElementById('avatarText').textContent = (data.user.nombres || 'R').charAt(0).toUpperCase();
    showApp();
    renderView('dashboard');
    if (data.documentos && data.documentos.length > 0) {
      showToast('Tienes documentos pendientes por revisar', 'info');
    }
  } catch (err) {
    errorEl.textContent = 'Error de conexión';
  }
});

document.getElementById('logoutBtn').addEventListener('click', async () => {
  if (!confirm('¿Cerrar sesión?')) return;
  try {
    await apiPost('/api/auth/logout', {});
  } catch (e) {}
  clearToken();
  showLogin();
});

document.getElementById('avatarBtn').addEventListener('click', () => {
  navigateTo('admin');
});

// ===== SIDEBAR =====
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');

menuToggle.addEventListener('click', () => {
  sidebar.classList.toggle('open');
});

document.addEventListener('click', (e) => {
  if (window.innerWidth <= 992) {
    if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    sidebar.classList.remove('open');
    closeModal();
  }
});

// ===== NAVIGATION =====
document.querySelectorAll('.sidebar-menu li[data-view]').forEach(item => {
  item.addEventListener('click', () => {
    const view = item.dataset.view;
    const filter = item.dataset.filter;
    document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
    item.classList.add('active');
    navigateTo(view, filter);
    if (window.innerWidth <= 992) sidebar.classList.remove('open');
  });
});

function navigateTo(view, filter) {
  currentView = view;
  currentFilter = filter || null;
  document.getElementById('searchInput').value = '';
  document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
  const target = document.querySelector(`.sidebar-menu li[data-view="${view}"]`);
  if (target) target.classList.add('active');
  renderView(view, filter);
}

async function renderView(view, filter) {
  const container = document.getElementById('viewContainer');
  container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';

  switch (view) {
    case 'dashboard': await renderDashboard(container); break;
    case 'orders': await renderOrders(container, filter); break;
    case 'clients': await renderClients(container); break;
    case 'tracking': await renderTracking(container); break;
    case 'order-search': renderOrderSearch(container); break;
    case 'admin': renderAdmin(container); break;
    case 'documents': await renderDocuments(container); break;
    default: await renderDashboard(container);
  }
}

// ===== SEARCH =====
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', (e) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    const term = e.target.value.trim();
    if (currentView === 'orders') renderOrders(document.getElementById('viewContainer'), currentFilter, term);
    else if (currentView === 'clients') renderClients(document.getElementById('viewContainer'), term);
  }, 300);
});

// ===== MODAL =====
function openModal(title, bodyHtml) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = bodyHtml;
  document.getElementById('modalOverlay').classList.add('open');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

document.getElementById('modalClose').addEventListener('click', closeModal);
document.getElementById('modalOverlay').addEventListener('click', (e) => {
  if (e.target === e.currentTarget) closeModal();
});

// =============================================
// DASHBOARD
// =============================================
async function renderDashboard(container) {
  let stats, recentOrders;
  try {
    [stats, recentOrders] = await Promise.all([
      apiGet('/api/dashboard/stats'),
      apiGet('/api/dashboard/recent-orders'),
    ]);
  } catch (e) {
    container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar datos</p></div>`;
    return;
  }

  container.innerHTML = `
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-number">${stats.today_orders}</div>
        <div class="stat-label">Pedidos hoy</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-number">${formatCurrency(stats.today_earnings)}</div>
        <div class="stat-label">Ganancias hoy</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-number">${stats.pending_orders}</div>
        <div class="stat-label">Pendientes</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-truck"></i></div>
        <div class="stat-number">${stats.in_transit}</div>
        <div class="stat-label">En tránsito</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-number">${stats.total_deliveries}</div>
        <div class="stat-label">Entregas totales</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-number">${stats.total_clients}</div>
        <div class="stat-label">Clientes</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-number">${stats.rating ? stats.rating.toFixed(1) : '5.0'}</div>
        <div class="stat-label">Calificación</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-number">${formatCurrency(stats.total_sales)}</div>
        <div class="stat-label">Ganancias totales</div>
      </div>
    </div>

    <div class="section-header">
      <h3>Pedidos recientes</h3>
      <button class="btn" onclick="navigateTo('orders', 'all')">Ver todos →</button>
    </div>
    <div class="table-container">
      <table>
        <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
          ${recentOrders.length === 0 ? '<tr><td colspan="5" style="text-align:center;color:#999;">Sin pedidos recientes</td></tr>' :
            recentOrders.map(o => `<tr>
              <td>#${o.id}</td>
              <td>${escapeHtml(o.client_name || '—')}</td>
              <td>${formatCurrency(o.total)}</td>
              <td>${statusBadge(o.status)}</td>
              <td>${formatDate(o.created_at)}</td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

// =============================================
// CLIENTS
// =============================================
async function renderClients(container, search) {
  let clients;
  try {
    const q = search ? `?search=${encodeURIComponent(search)}` : '';
    clients = await apiGet('/api/clients' + q);
  } catch (e) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar clientes</p></div>';
    return;
  }

  container.innerHTML = `
    <div class="section-header">
      <h3>Clientes (${clients.length})</h3>
      <button class="btn" onclick="showClientForm(null)"><i class="fas fa-plus"></i> Nuevo Cliente</button>
    </div>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          ${clients.length === 0 ? '<tr><td colspan="6" style="text-align:center;color:#999;">No hay clientes</td></tr>' :
            clients.map(c => `<tr>
              <td>${c.id}</td>
              <td><strong>${escapeHtml(c.name)}</strong></td>
              <td>${escapeHtml(c.email || '—')}</td>
              <td>${escapeHtml(c.phone || '—')}</td>
              <td>${escapeHtml(c.address || '—')}</td>
              <td>
                <div class="table-actions">
                  <button class="btn-edit" onclick="showClientForm(${c.id})"><i class="fas fa-edit"></i></button>
                  <button class="btn-delete" onclick="deleteClient(${c.id})"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function showClientForm(id) {
  let client = null;
  if (id) {
    try { client = await apiGet(`/api/clients/${id}`); } catch (e) { showToast('Error al cargar cliente', 'error'); return; }
  }

  const isEdit = !!client;
  openModal(isEdit ? 'Editar Cliente' : 'Nuevo Cliente', `
    <form id="clientForm">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" name="name" value="${escapeHtml(client?.name || '')}" required />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="${escapeHtml(client?.email || '')}" />
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" name="phone" value="${escapeHtml(client?.phone || '')}" />
        </div>
      </div>
      <div class="form-group">
        <label>Dirección</label>
        <input type="text" name="address" value="${escapeHtml(client?.address || '')}" />
      </div>
      <div class="form-actions">
        <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn-primary">${isEdit ? 'Guardar Cambios' : 'Crear Cliente'}</button>
      </div>
    </form>
  `);

  document.getElementById('clientForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());

    try {
      if (isEdit) {
        await apiPut(`/api/clients/${id}`, data);
        showToast('Cliente actualizado', 'success');
      } else {
        await apiPost('/api/clients', data);
        showToast('Cliente creado', 'success');
      }
      closeModal();
      renderView('clients');
    } catch (err) {
      showToast(err.message, 'error');
    }
  });
}

async function deleteClient(id) {
  if (!confirm('¿Eliminar este cliente?')) return;
  try {
    await apiDelete(`/api/clients/${id}`);
    showToast('Cliente eliminado', 'success');
    renderView('clients');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// =============================================
// ORDERS
// =============================================
async function renderOrders(container, filter, search) {
  let params = [];
  if (filter === 'today') params.push('date=' + new Date().toISOString().slice(0, 10));
  else if (filter === 'entregado') params.push('status=entregado');
  else if (filter === 'asignado') params.push('status=asignado');
  if (search) params.push('search=' + encodeURIComponent(search));

  const qs = params.length > 0 ? '?' + params.join('&') : '';
  let orders;
  try {
    orders = await apiGet('/api/orders' + qs);
  } catch (e) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar pedidos</p></div>';
    return;
  }

  const title = filter === 'today' ? 'Pedidos Del Día' :
                filter === 'entregado' ? 'Pedidos Entregados' :
                filter === 'asignado' ? 'Pedidos Asignados' : 'Pedidos';

  container.innerHTML = `
    <div class="section-header">
      <h3>${title} (${orders.length})</h3>
      <button class="btn" onclick="showOrderForm(null)"><i class="fas fa-plus"></i> Nuevo Pedido</button>
    </div>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Productos</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          ${orders.length === 0 ? '<tr><td colspan="7" style="text-align:center;color:#999;">No hay pedidos</td></tr>' :
            orders.map(o => `<tr>
              <td>#${o.id}</td>
              <td><strong>${escapeHtml(o.client_name || '—')}</strong></td>
              <td style="font-size:0.8rem;">${o.items ? o.items.map(i => `${i.quantity}x ${escapeHtml(i.product_name)}`).join(', ') : '—'}</td>
              <td>${formatCurrency(o.total)}</td>
              <td>${statusBadge(o.status)}</td>
              <td style="font-size:0.8rem;">${formatDate(o.created_at)}</td>
              <td>
                <div class="table-actions">
                  <button class="btn-edit" onclick="showOrderDetail(${o.id})"><i class="fas fa-eye"></i></button>
                  <button class="btn-delete" onclick="deleteOrder(${o.id})"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function showOrderDetail(orderId) {
  let order;
  try {
    order = await apiGet(`/api/orders/${orderId}`);
  } catch (e) {
    showToast('Error al cargar pedido', 'error');
    return;
  }

  openModal(`Pedido #${order.id}`, `
    <div class="detail-grid">
      <div class="detail-item"><span class="label">Cliente:</span> <span class="value">${escapeHtml(order.client_name)}</span></div>
      <div class="detail-item"><span class="label">Teléfono:</span> <span class="value">${escapeHtml(order.client_phone || '—')}</span></div>
      <div class="detail-item"><span class="label">Dirección:</span> <span class="value">${escapeHtml(order.delivery_address || '—')}</span></div>
      <div class="detail-item"><span class="label">Estado:</span> <span class="value">${statusBadge(order.status)}</span></div>
      <div class="detail-item"><span class="label">Total:</span> <span class="value">${formatCurrency(order.total)}</span></div>
      <div class="detail-item"><span class="label">Fecha:</span> <span class="value">${formatDate(order.created_at)}</span></div>
    </div>
    ${order.notes ? `<div style="margin-bottom:12px;font-size:0.85rem;"><span class="label">Notas:</span> ${escapeHtml(order.notes)}</div>` : ''}
    <h4 style="margin-bottom:8px;font-size:0.9rem;">Productos</h4>
    <div class="table-container">
      <table class="items-table">
        <thead><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr></thead>
        <tbody>
          ${order.items.map(i => `<tr>
            <td>${escapeHtml(i.product_name)}</td>
            <td>${i.quantity}</td>
            <td>${formatCurrency(i.price)}</td>
            <td>${formatCurrency(i.price * i.quantity)}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>
    <div style="margin-top:16px;">
      <label style="font-size:0.85rem;font-weight:600;display:block;margin-bottom:6px;">Cambiar estado</label>
      <select class="status-select" id="statusSelect" onchange="updateOrderStatus(${order.id}, this.value)">
        <option value="pendiente" ${order.status === 'pendiente' ? 'selected' : ''}>Pendiente</option>
        <option value="asignado" ${order.status === 'asignado' ? 'selected' : ''}>Asignado</option>
        <option value="en_camino" ${order.status === 'en_camino' ? 'selected' : ''}>En Camino</option>
        <option value="entregado" ${order.status === 'entregado' ? 'selected' : ''}>Entregado</option>
        <option value="cancelado" ${order.status === 'cancelado' ? 'selected' : ''}>Cancelado</option>
      </select>
    </div>
  `);
}

async function updateOrderStatus(id, status) {
  try {
    await apiPut(`/api/orders/${id}`, { status });
    showToast('Estado actualizado', 'success');
    closeModal();
    renderView(currentView, currentFilter);
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function showOrderForm(id) {
  let order = null;
  let clients = [];

  try {
    clients = await apiGet('/api/clients');
    if (id) order = await apiGet(`/api/orders/${id}`);
  } catch (e) {
    showToast('Error al cargar datos', 'error');
    return;
  }

  const isEdit = !!order;

  openModal(isEdit ? 'Editar Pedido' : 'Nuevo Pedido', `
    <form id="orderForm">
      <div class="form-group">
        <label>Cliente *</label>
        <select name="client_id" required>
          <option value="">Seleccionar cliente</option>
          ${clients.map(c => `<option value="${c.id}" ${order?.client_id === c.id ? 'selected' : ''}>${escapeHtml(c.name)}</option>`).join('')}
        </select>
      </div>

      <div class="form-group">
        <label>Notas</label>
        <textarea name="notes">${escapeHtml(order?.notes || '')}</textarea>
      </div>

      <h4 style="font-size:0.9rem;margin-bottom:10px;">Productos</h4>
      <div id="orderItems">
        ${isEdit && order.items ? order.items.map((item, idx) => `
          <div class="form-row order-item" style="margin-bottom:10px;gap:6px;">
            <div class="form-group" style="flex:2;">
              <input type="text" name="product_name" value="${escapeHtml(item.product_name)}" placeholder="Nombre del producto" required />
            </div>
            <div class="form-group" style="flex:0 0 80px;">
              <input type="number" name="price" value="${item.price}" step="0.01" min="0" placeholder="Precio" required />
            </div>
            <div class="form-group" style="flex:0 0 70px;">
              <input type="number" name="quantity" value="${item.quantity}" min="1" required />
            </div>
            <button type="button" class="btn-delete" onclick="this.parentElement.remove()" style="height:38px;align-self:flex-end;padding:0 10px;"><i class="fas fa-times"></i></button>
          </div>
        `).join('') : `
        <div class="form-row order-item" style="margin-bottom:10px;gap:6px;">
          <div class="form-group" style="flex:2;">
            <input type="text" name="product_name" placeholder="Nombre del producto" required />
          </div>
          <div class="form-group" style="flex:0 0 80px;">
            <input type="number" name="price" step="0.01" min="0" placeholder="Precio" required />
          </div>
          <div class="form-group" style="flex:0 0 70px;">
            <input type="number" name="quantity" value="1" min="1" required />
          </div>
          <button type="button" class="btn-delete" onclick="this.parentElement.remove()" style="height:38px;align-self:flex-end;padding:0 10px;"><i class="fas fa-times"></i></button>
        </div>`}
      </div>
      <button type="button" class="btn" onclick="addOrderItem()" style="margin-bottom:16px;font-size:0.8rem;"><i class="fas fa-plus"></i> Agregar producto</button>

      <div class="form-actions">
        <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn-primary">${isEdit ? 'Guardar Cambios' : 'Crear Pedido'}</button>
      </div>
    </form>
  `);

  if (isEdit) {
    document.getElementById('orderForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      showToast('Usa el panel de detalle para editar el estado', 'info');
    });
  } else {
    document.getElementById('orderForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const client_id = parseInt(fd.get('client_id'));
      const notes = fd.get('notes');

      const items = [];
      const itemRows = document.querySelectorAll('.order-item');

      for (const row of itemRows) {
        const name = row.querySelector('input[name="product_name"]')?.value?.trim();
        const price = parseFloat(row.querySelector('input[name="price"]')?.value);
        const qty = parseInt(row.querySelector('input[name="quantity"]')?.value);
        if (name && price > 0 && qty > 0) items.push({ product_name: name, price, quantity: qty });
      }

      if (items.length === 0) {
        showToast('Agrega al menos un producto', 'error');
        return;
      }

      try {
        const total = items.reduce((sum, i) => sum + i.price * i.quantity, 0);
        await apiPost('/api/orders', { client_id, items, notes, total });
        showToast('Pedido creado', 'success');
        closeModal();
        renderView(currentView, currentFilter);
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }
}

function addOrderItem() {
  const container = document.getElementById('orderItems');
  if (!container) return;

  const div = document.createElement('div');
  div.className = 'form-row order-item';
  div.style.marginBottom = '10px';
  div.style.gap = '6px';
  div.innerHTML = `
    <div class="form-group" style="flex:2;">
      <input type="text" name="product_name" placeholder="Nombre del producto" required />
    </div>
    <div class="form-group" style="flex:0 0 80px;">
      <input type="number" name="price" step="0.01" min="0" placeholder="Precio" required />
    </div>
    <div class="form-group" style="flex:0 0 70px;">
      <input type="number" name="quantity" value="1" min="1" required />
    </div>
    <button type="button" class="btn-delete" onclick="this.parentElement.remove()" style="height:38px;align-self:flex-end;padding:0 10px;"><i class="fas fa-times"></i></button>
  `;
  container.appendChild(div);
}

async function deleteOrder(id) {
  if (!confirm('¿Eliminar este pedido?')) return;
  try {
    await apiDelete(`/api/orders/${id}`);
    showToast('Pedido eliminado', 'success');
    renderView(currentView, currentFilter);
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// =============================================
// TRACKING (Real-time)
// =============================================
let trackingMap = null;
let trackingMarkers = [];
let trackingInterval = null;
let userLocation = null;

async function renderTracking(container) {
  container.innerHTML = `
    <div class="section-header">
      <h3>Seguimiento en Tiempo Real</h3>
      <span style="font-size:0.8rem;color:#4caf50;"><i class="fas fa-circle" style="font-size:0.5rem;vertical-align:middle;"></i> En vivo</span>
    </div>
    <div class="tracking-layout">
      <div class="tracking-map-container">
        <div id="trackingMap"></div>
      </div>
      <div class="tracking-sidebar">
        <div class="tracking-sidebar-header">
          <h4>Pedidos Activos</h4>
          <span id="activeCount" class="badge-count">0</span>
        </div>
        <div id="trackingOrderList" class="tracking-order-list"></div>
      </div>
    </div>
  `;

  setTimeout(() => initTrackingMap(), 100);
  loadTrackingOrders();

  socket.off('order:statusChanged');
  socket.on('order:statusChanged', (data) => {
    showToast(`Pedido #${data.id}: ${STATUS_LABELS[data.status] || data.status}`, 'info');
    addNotification(`Pedido #${data.id} cambió a ${STATUS_LABELS[data.status] || data.status}`);
    loadTrackingOrders();
  });

  socket.off('order:created');
  socket.on('order:created', () => {
    loadTrackingOrders();
  });

  socket.off('tracking:update');
  socket.on('tracking:update', (data) => {
    updateDriverPosition(data);
  });

  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
      (pos) => {
        userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        if (trackingMap) {
          trackingMap.setView([pos.coords.latitude, pos.coords.longitude], 14);
        }
      },
      () => {},
      { enableHighAccuracy: true, timeout: 5000 }
    );
  }
}

function initTrackingMap() {
  const mapEl = document.getElementById('trackingMap');
  if (!mapEl || trackingMap) return;

  trackingMap = L.map('trackingMap').setView([6.2442, -75.5812], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19,
  }).addTo(trackingMap);

  trackingMap.on('tileload', () => {
    document.querySelectorAll('.map-skeleton').forEach(el => el.remove());
  });
}

function clearTrackingMarkers() {
  trackingMarkers.forEach(m => trackingMap?.removeLayer(m));
  trackingMarkers = [];
}

function addTrackingMarker(lat, lng, label, color = '#3b82f6') {
  const icon = L.divIcon({
    className: 'tracking-marker',
    html: `<div style="background:${color};color:white;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,0.2);">${label}</div>`,
    iconSize: [0, 0],
    iconAnchor: [0, 0],
  });
  const marker = L.marker([lat, lng], { icon }).addTo(trackingMap);
  trackingMarkers.push(marker);
  return marker;
}

function addUserMarker(lat, lng) {
  const icon = L.divIcon({
    className: 'user-location-marker',
    html: '<div style="width:16px;height:16px;background:#3b82f6;border:3px solid white;border-radius:50%;box-shadow:0 0 0 4px rgba(59,130,246,0.3);"></div>',
    iconSize: [16, 16],
    iconAnchor: [8, 8],
  });
  const marker = L.marker([lat, lng], { icon, zIndexOffset: 1000 }).addTo(trackingMap);
  trackingMarkers.push(marker);
  return marker;
}

function updateDriverPosition(data) {
  if (!data.latitud || !data.longitud) return;
  const markerKey = `driver_${data.pedido_id}`;
  const existing = trackingMarkers.find(m => m.options?.markerKey === markerKey);
  if (existing) {
    existing.setLatLng([data.latitud, data.longitud]);
  }
}

async function loadTrackingOrders() {
  const list = document.getElementById('trackingOrderList');
  if (!list) return;

  try {
    const allOrders = await apiGet('/api/orders');
    const active = allOrders.filter(o => o.status !== 'entregado' && o.status !== 'cancelado');

    const countEl = document.getElementById('activeCount');
    if (countEl) countEl.textContent = active.length;

    clearTrackingMarkers();

    if (userLocation) {
      addUserMarker(userLocation.lat, userLocation.lng);
    }

    if (active.length === 0) {
      list.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="color:#4caf50;font-size:2rem;"></i><p>Todos los pedidos han sido entregados</p></div>';
      return;
    }

    const bounds = [];
    if (userLocation) bounds.push([userLocation.lat, userLocation.lng]);

    list.innerHTML = active.map(o => {
      if (o.client_lat && o.client_lng) {
        bounds.push([o.client_lat, o.client_lng]);
      }
      return `
        <div class="tracking-card status-${o.status}" data-order-id="${o.id}">
          <div class="tracking-card-header">
            <span class="tracking-id">#${o.id}</span>
            ${statusBadge(o.status)}
          </div>
          <div class="tracking-card-body">
            <div class="tracking-client"><i class="fas fa-user"></i> ${escapeHtml(o.client_name || '—')}</div>
            <div class="tracking-address"><i class="fas fa-map-pin"></i> ${escapeHtml(o.delivery_address || '—')}</div>
            <div class="tracking-items"><i class="fas fa-box"></i> ${o.items ? o.items.map(i => `${i.quantity}x ${escapeHtml(i.product_name || i.product)}`).join(', ') : '—'}</div>
          </div>
          <div class="tracking-card-footer">
            <span class="tracking-time">${formatDate(o.created_at)}</span>
            <button class="tracking-update-btn" onclick="quickUpdateStatus(${o.id}, '${o.status}')">
              <i class="fas fa-arrow-right"></i> ${o.status === 'en_camino' ? 'Entregar' : 'Avanzar'}
            </button>
          </div>
        </div>
      `;
    }).join('');

    if (bounds.length > 1 && trackingMap) {
      trackingMap.fitBounds(bounds, { padding: [50, 50] });
    }

    active.forEach(o => {
      if (o.client_lat && o.client_lng) {
        addTrackingMarker(o.client_lat, o.client_lng, `#${o.id} - ${o.client_name?.split(' ')[0] || ''}`, '#ef4444');
      }
    });

  } catch (e) {
    if (list) list.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar</p></div>';
  }
}

async function quickUpdateStatus(id, currentStatus) {
  const next = { pendiente: 'asignado', asignado: 'en_camino', en_camino: 'entregado' };
  const newStatus = next[currentStatus];
  if (!newStatus) { showToast('No hay siguiente estado', 'info'); return; }
  try {
    await apiPut(`/api/orders/${id}`, { status: newStatus });
    showToast(`Pedido #${id} → ${STATUS_LABELS[newStatus]}`, 'success');
    loadTrackingOrders();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// =============================================
// ORDER SEARCH
// =============================================
function renderOrderSearch(container) {
  container.innerHTML = `
    <div class="section-header">
      <h3>Estado Del Pedido</h3>
    </div>
    <p style="color:#888;font-size:0.85rem;margin-bottom:16px;">Ingresa el número de pedido para consultar su estado</p>
    <div class="search-order-box">
      <input type="number" id="orderSearchInput" placeholder="Ej: 1, 2, 3..." />
      <button onclick="searchOrder()"><i class="fas fa-search"></i> Buscar</button>
    </div>
    <div id="orderSearchResult"></div>
  `;

  document.getElementById('orderSearchInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') searchOrder();
  });
}

async function searchOrder() {
  const id = document.getElementById('orderSearchInput')?.value;
  if (!id) { showToast('Ingresa un número de pedido', 'error'); return; }

  const result = document.getElementById('orderSearchResult');
  result.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Buscando...</p></div>';

  try {
    const order = await apiGet(`/api/orders/${id}`);
    result.innerHTML = `
      <div class="table-container" style="margin-top:16px;">
        <div style="padding:20px;">
          <div class="detail-grid">
            <div class="detail-item"><span class="label">Pedido:</span> <span class="value">#${order.id}</span></div>
            <div class="detail-item"><span class="label">Cliente:</span> <span class="value">${escapeHtml(order.client_name)}</span></div>
            <div class="detail-item"><span class="label">Teléfono:</span> <span class="value">${escapeHtml(order.client_phone || '—')}</span></div>
            <div class="detail-item"><span class="label">Dirección:</span> <span class="value">${escapeHtml(order.delivery_address || '—')}</span></div>
            <div class="detail-item"><span class="label">Total:</span> <span class="value">${formatCurrency(order.total)}</span></div>
            <div class="detail-item"><span class="label">Estado:</span> <span class="value">${statusBadge(order.status)}</span></div>
            <div class="detail-item"><span class="label">Fecha:</span> <span class="value">${formatDate(order.created_at)}</span></div>
          </div>
          ${order.notes ? `<p style="font-size:0.85rem;margin-bottom:10px;"><strong>Notas:</strong> ${escapeHtml(order.notes)}</p>` : ''}
          <h4 style="font-size:0.9rem;margin-bottom:8px;">Productos</h4>
          <table class="items-table">
            <thead><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr></thead>
            <tbody>
              ${order.items.map(i => `<tr>
                <td>${escapeHtml(i.product_name)}</td>
                <td>${i.quantity}</td>
                <td>${formatCurrency(i.price)}</td>
                <td>${formatCurrency(i.price * i.quantity)}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;
  } catch (e) {
    result.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Pedido #${id} no encontrado</p></div>`;
  }
}

// =============================================
// ADMIN (Perfil del repartidor)
// =============================================
function renderAdmin(container) {
  const u = currentUser;
  if (!u) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-user-slash"></i><p>No hay información de usuario</p></div>';
    return;
  }

  const docStatus = u.documentos_completos
    ? '<span style="color:#4caf50;"><i class="fas fa-check-circle"></i> Completos</span>'
    : '<span style="color:#ff6b6b;"><i class="fas fa-exclamation-circle"></i> Pendientes</span>';

  container.innerHTML = `
    <div class="section-header">
      <h3>Mi Perfil</h3>
    </div>
    <div class="profile-card">
      <div class="profile-avatar">
        <span>${(u.nombres || 'R').charAt(0).toUpperCase()}${(u.apellidos || 'A').charAt(0).toUpperCase()}</span>
      </div>
      <div class="profile-info">
        <h2>${escapeHtml(u.nombres || '')} ${escapeHtml(u.apellidos || '')}</h2>
        <p class="profile-email">${escapeHtml(u.email || '')}</p>
        <p class="profile-rol">${escapeHtml(u.rol || 'Repartidor')}</p>
      </div>
    </div>
    <div class="profile-details">
      <div class="detail-item"><span class="label">Documentos:</span> <span class="value">${docStatus}</span></div>
      <div class="detail-item"><span class="label">Teléfono:</span> <span class="value">${escapeHtml(u.telefono || '—')}</span></div>
      <div class="detail-item"><span class="label">Dirección:</span> <span class="value">${escapeHtml(u.direccion || '—')}</span></div>
    </div>
  `;
}

// =============================================
// DOCUMENTS
// =============================================
async function renderDocuments(container) {
  container.innerHTML = `
    <div class="section-header">
      <h3>Mis Documentos</h3>
      <button class="btn" onclick="showSubirDocumento()"><i class="fas fa-upload"></i> Subir Documento</button>
    </div>
    <div id="docsList"><div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div></div>
  `;
  loadDocumentos();
}

async function loadDocumentos() {
  const list = document.getElementById('docsList');
  if (!list) return;
  try {
    const res = await apiGet('/api/documents');
    const docs = res.documentos || [];
    list.innerHTML = docs.length === 0
      ? '<div class="empty-state"><i class="fas fa-file-alt"></i><p>No has subido documentos</p></div>'
      : `<div class="table-container"><table>
          <thead><tr><th>Tipo</th><th>Estado</th><th>Fecha</th><th>Observaciones</th></tr></thead>
          <tbody>
            ${docs.map(d => `<tr>
              <td><strong>${(d.tipo || '').toUpperCase()}</strong></td>
              <td>${statusBadge(d.estado)}</td>
              <td>${formatDate(d.fecha_subida)}</td>
              <td style="font-size:0.8rem;">${escapeHtml(d.observaciones || '—')}</td>
            </tr>`).join('')}
          </tbody>
        </table></div>`;
  } catch (e) {
    if (list) list.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar documentos</p></div>';
  }
}

function showSubirDocumento() {
  openModal('Subir Documento', `
    <form id="documentForm">
      <div class="form-group">
        <label>Tipo de documento *</label>
        <select name="tipo" required>
          <option value="">Seleccionar</option>
          <option value="pase">Pase</option>
          <option value="soat">SOAT</option>
          <option value="tecnomecanica">Tecnomecánica</option>
        </select>
      </div>
      <div class="form-group">
        <label>Archivo (PDF, JPG, PNG) *</label>
        <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required />
      </div>
      <div class="form-actions">
        <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Subir</button>
      </div>
    </form>
  `);

  document.getElementById('documentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await apiUpload('/api/documents/subir', fd);
      showToast('Documento subido correctamente', 'success');
      closeModal();
      renderView('documents');
    } catch (err) {
      showToast(err.message, 'error');
    }
  });
}

// =============================================
// NOTIFICATIONS
// =============================================
function addNotification(msg) {
  notifCount++;
  document.getElementById('notifCount').textContent = notifCount;
}

document.getElementById('notifBtn').addEventListener('click', () => {
  showToast(`${notifCount} notificaciones pendientes`, 'info');
});

// =============================================
// INIT
// =============================================
document.addEventListener('DOMContentLoaded', () => {
  checkAuth();
});
