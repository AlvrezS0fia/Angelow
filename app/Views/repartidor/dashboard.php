<?php
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
    header('Location: ' . APP_URL . '/repartidor/login');
    exit();
}
$user = $_SESSION['user'];

if (($user['estado'] ?? 'activo') === 'pendiente') {
    header('Location: ' . APP_URL . '/repartidor/login?pending=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>ANGELOW | Panel Repartidor</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/repartidor.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/repartidor-dashboard.css">
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <header class="header-repartidor">
        <div class="header-contenido">
            <div class="logo-area">
                <div class="logo-circulo">
                    <i class="fas fa-truck-fast logo-icono"></i>
                </div>
                <div class="logo-texto">
                    <h1>ANGELOW</h1>
                    <p>Panel de Repartidor</p>
                </div>
            </div>
            <div class="info-conductor">
                <div class="avatar-conductor">
                    <?= strtoupper(substr($user['nombre'] ?? 'R', 0, 1)) ?>
                </div>
                <div class="detalles-conductor">
                    <h3><?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></h3>
                    <p><span class="punto-verde"></span>En línea • <?= htmlspecialchars($user['tipo_vehiculo'] ?? 'Repartidor') ?></p>
                </div>
            </div>
            <div class="acciones-header">
                <a href="<?= APP_URL ?>/" class="btn-header">
                    <i class="fas fa-store"></i> Tienda
                </a>
                <button class="btn-header cerrar-sesion" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </button>
            </div>
        </div>
    </header>

    <div class="contenedor-principal">
        <div class="grid-estadisticas">
            <div class="tarjeta-estadistica">
                <div class="icono-estadistica azul">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="valor-estadistica" id="statOrders">0</div>
                <div class="etiqueta-estadistica">Pedidos Hoy</div>
                <div class="tendencia subida">
                    <i class="fas fa-arrow-up"></i> Activo
                </div>
            </div>
            <div class="tarjeta-estadistica">
                <div class="icono-estadistica verde">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="valor-estadistica" id="statEarnings">$0</div>
                <div class="etiqueta-estadistica">Ganancias Hoy</div>
                <div class="tendencia subida">
                    <i class="fas fa-arrow-up"></i> +12%
                </div>
            </div>
            <div class="tarjeta-estadistica">
                <div class="icono-estadistica amarillo">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="valor-estadistica" id="statPending">0</div>
                <div class="etiqueta-estadistica">Pendientes</div>
                <div class="tendencia bajada">
                    <i class="fas fa-exclamation-circle"></i> Atención
                </div>
            </div>
            <div class="tarjeta-estadistica">
                <div class="icono-estadistica celeste">
                    <i class="fas fa-star"></i>
                </div>
                <div class="valor-estadistica" id="statRating">0.0</div>
                <div class="etiqueta-estadistica">Calificación</div>
                <div class="tendencia subida">
                    <i class="fas fa-smile"></i> Excelente
                </div>
            </div>
        </div>

        <div class="seccion-filtros">
            <div class="filtros-header">
                <div class="filtros-titulo">
                    <i class="fas fa-filter"></i>
                    Filtros de Pedidos
                </div>
                <div class="contador-pedidos">
                    <span id="filteredCount">0</span> pedidos encontrados
                </div>
            </div>
            <div class="filtros-grid">
                <div class="grupo-filtro">
                    <label><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="searchInput" class="select-filtro" placeholder="Buscar por cliente o dirección...">
                </div>
                <div class="grupo-filtro">
                    <label><i class="fas fa-tag"></i> Estado</label>
                    <select id="filterStatus" class="select-filtro">
                        <option value="all">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="ready">Listo para recoger</option>
                        <option value="picked">Recogido</option>
                        <option value="delivered">Entregado</option>
                    </select>
                </div>
                <div class="grupo-filtro">
                    <label><i class="fas fa-sort-amount-down"></i> Prioridad</label>
                    <select id="filterPriority" class="select-filtro">
                        <option value="all">Todas</option>
                        <option value="high">Alta</option>
                        <option value="normal">Normal</option>
                    </select>
                </div>
                <button class="btn-filtro" onclick="applyFilters()">
                    <i class="fas fa-check"></i> Aplicar Filtros
                </button>
            </div>
        </div>

        <div class="grid-secciones">
            <div class="tarjeta-seccion">
                <div class="seccion-header">
                    <div class="seccion-titulo">
                        <div class="seccion-icono">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        Pedidos Disponibles
                    </div>
                    <span class="seccion-badge" id="availableBadge">0</span>
                </div>
                <div class="lista-pedidos" id="availableOrders">
                    <div class="estado-vacio">
                        <div class="icono-vacio"><i class="fas fa-box-open"></i></div>
                        <div class="titulo-vacio">Cargando pedidos...</div>
                        <div class="mensaje-vacio">Por favor espera un momento</div>
                    </div>
                </div>
            </div>

            <div class="tarjeta-seccion">
                <div class="seccion-header">
                    <div class="seccion-titulo">
                        <div class="seccion-icono">
                            <i class="fas fa-truck"></i>
                        </div>
                        Pedidos Activos
                    </div>
                    <span class="seccion-badge" id="activeCount">0</span>
                </div>
                <div class="lista-pedidos" id="activeOrders">
                    <div class="estado-vacio">
                        <div class="icono-vacio"><i class="fas fa-truck"></i></div>
                        <div class="titulo-vacio">No hay pedidos activos</div>
                        <div class="mensaje-vacio">Acepta un pedido para empezar</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tarjeta-seccion" style="margin-bottom: var(--espaciado-xxl);">
            <div class="seccion-header">
                <div class="seccion-titulo">
                    <div class="seccion-icono">
                        <i class="fas fa-history"></i>
                    </div>
                    Historial de Entregas
                </div>
                <button class="btn-filtro" onclick="verTodoHistorial()" style="width: auto; padding: 8px 16px; font-size: 14px;">
                    <i class="fas fa-eye"></i> Ver Todo
                </button>
            </div>
            <div class="tabla-contenedor">
                <table class="tabla-historial">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Zona</th>
                            <th>Ganancia</th>
                            <th>Calificación</th>
                        </tr>
                    </thead>
                    <tbody id="deliveriesHistory">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--texto-secundario);">
                                Cargando historial...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel-notificaciones">
        <div class="campana" onclick="verNotificaciones()">
            <i class="fas fa-bell"></i>
            <span class="badge-notificaciones" id="notificationCount">0</span>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        const userData = <?php echo json_encode($user); ?>;
        let token = localStorage.getItem('repartidor_token') || '';
        
        const datosApp = {
            pedidosDisponibles: [],
            pedidosActivos: [],
            historialEntregas: [],
            notificaciones: []
        };

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

        function formatearDinero(monto) {
            return '$' + Number(monto).toLocaleString('es-CO');
        }

        function obtenerIniciales(nombre) {
            if (!nombre) return '?';
            return nombre.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }

        async function loadStats() {
            if (!token) return;
            try {
                const res = await fetch('/api/repartidor/dashboard/stats', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.today_earnings) {
                    document.getElementById('statEarnings').textContent = formatearDinero(data.today_earnings);
                }
                if (data.total_deliveries) {
                    document.getElementById('statOrders').textContent = data.total_deliveries;
                }
                if (data.pending_orders) {
                    document.getElementById('statPending').textContent = data.pending_orders;
                }
                if (data.rating) {
                    document.getElementById('statRating').textContent = Number(data.rating).toFixed(1);
                }
            } catch (e) {
                console.warn('No se pudieron cargar estadísticas:', e);
            }
        }

        async function loadOrders() {
            if (!token) return;
            try {
                const res = await fetch('/api/repartidor/pedidos', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) return;
                const data = await res.json();
                datosApp.pedidosDisponibles = Array.isArray(data) ? data.filter(p => ['pending', 'ready'].includes(p.estado)) : [];
                datosApp.pedidosActivos = Array.isArray(data) ? data.filter(p => ['picked', 'recogido'].includes(p.estado)) : [];
                datosApp.historialEntregas = Array.isArray(data) ? data.filter(p => p.estado === 'delivered').slice(0, 10) : [];
                renderPedidosDisponibles();
                renderPedidosActivos();
                renderHistorial();
            } catch (e) {
                console.warn('No se pudieron cargar pedidos:', e);
            }
        }

        function renderPedidosDisponibles() {
            const container = document.getElementById('availableOrders');
            const badge = document.getElementById('availableBadge');
            const countEl = document.getElementById('filteredCount');
            
            let pedidos = datosApp.pedidosDisponibles;
            
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const priority = document.getElementById('filterPriority').value;
            
            if (search) {
                pedidos = pedidos.filter(p => 
                    (p.cliente || p.client_name || '').toLowerCase().includes(search) ||
                    (p.direccion || p.delivery_address || '').toLowerCase().includes(search)
                );
            }
            if (status !== 'all') {
                pedidos = pedidos.filter(p => p.estado === status);
            }
            if (priority !== 'all') {
                pedidos = pedidos.filter(p => (p.prioridad || 'normal') === priority);
            }
            
            badge.textContent = datosApp.pedidosDisponibles.length;
            countEl.textContent = pedidos.length;
            
            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="estado-vacio">
                        <div class="icono-vacio"><i class="fas fa-box-open"></i></div>
                        <div class="titulo-vacio">No hay pedidos disponibles</div>
                        <div class="mensaje-vacio">Los nuevos pedidos aparecerán aquí</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = pedidos.map(p => {
                const esAlta = (p.prioridad || 'normal') === 'high';
                const estado = p.estado || 'pending';
                const estadoTexto = estado === 'ready' ? 'Listo' : 'Pendiente';
                const estadoClase = estado === 'ready' ? 'estado-listo' : 'estado-pendiente';
                const iconoEstado = estado === 'ready' ? 'fa-check-circle' : 'fa-clock';
                
                return `
                <div class="pedido-card ${esAlta ? 'alta-prioridad' : ''}">
                    <div class="pedido-header">
                        <div class="pedido-id">
                            <i class="fas fa-hashtag"></i> ${p.numero_pedido || '#' + p.id}
                            ${esAlta ? '<span class="pedido-urgente">URGENTE</span>' : ''}
                        </div>
                        <div class="pedido-estado ${estadoClase}">
                            <i class="fas ${iconoEstado}"></i> ${estadoTexto}
                        </div>
                    </div>
                    
                    <div class="pedido-detalles">
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-user"></i> Cliente</div>
                            <div class="detalle-valor">${htmlspecialchars(p.cliente || p.client_name || 'Cliente')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-phone"></i> Teléfono</div>
                            <div class="detalle-valor">${htmlspecialchars(p.telefono || p.client_phone || '-')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                            <div class="detalle-valor">${htmlspecialchars(p.direccion || p.delivery_address || '-')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-road"></i> Distancia</div>
                            <div class="detalle-valor">${p.distancia || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div class="pedido-productos">
                        <div class="productos-titulo">
                            <i class="fas fa-box"></i> Productos:
                        </div>
                        <div class="productos-lista">
                            ${(p.productos || []).map(prod => `<span class="producto-tag">${htmlspecialchars(prod)}</span>`).join('')}
                        </div>
                    </div>
                    
                    <div class="pedido-total">Total: ${formatearDinero(p.total || 0)}</div>
                    
                    <div class="pedido-acciones">
                        <button class="btn-accion btn-aceptar" onclick="aceptarPedido(${p.id})">
                            <i class="fas fa-check"></i> Aceptar
                        </button>
                        <button class="btn-accion btn-rechazar" onclick="rechazarPedido(${p.id})">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    </div>
                </div>
            `}).join('');
        }

        function renderPedidosActivos() {
            const container = document.getElementById('activeOrders');
            const countEl = document.getElementById('activeCount');
            countEl.textContent = datosApp.pedidosActivos.length;
            
            if (datosApp.pedidosActivos.length === 0) {
                container.innerHTML = `
                    <div class="estado-vacio">
                        <div class="icono-vacio"><i class="fas fa-truck"></i></div>
                        <div class="titulo-vacio">No hay pedidos activos</div>
                        <div class="mensaje-vacio">Acepta un pedido para empezar</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = datosApp.pedidosActivos.map(p => `
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-id"><i class="fas fa-hashtag"></i> ${p.numero_pedido || '#' + p.id}</div>
                        <div class="pedido-estado estado-recogido">
                            <i class="fas fa-truck"></i> En camino
                        </div>
                    </div>
                    
                    <div class="pedido-detalles">
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-user"></i> Cliente</div>
                            <div class="detalle-valor">${htmlspecialchars(p.cliente || p.client_name || 'Cliente')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                            <div class="detalle-valor">${htmlspecialchars(p.direccion || p.delivery_address || '-')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-phone"></i> Teléfono</div>
                            <div class="detalle-valor">${htmlspecialchars(p.telefono || p.client_phone || '-')}</div>
                        </div>
                    </div>
                    
                    <div class="pedido-acciones">
                        <button class="btn-accion btn-entregar" onclick="entregarPedido(${p.id})">
                            <i class="fas fa-check-circle"></i> Entregar
                        </button>
                        <button class="btn-accion btn-contactar" onclick="contactarCliente('${p.telefono || p.client_phone || ''}')">
                            <i class="fas fa-phone"></i> Llamar
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function renderHistorial() {
            const tbody = document.getElementById('deliveriesHistory');
            if (datosApp.historialEntregas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--texto-secundario);">
                            No hay entregas registradas hoy
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = datosApp.historialEntregas.map(h => {
                let estrellas = '';
                const cal = Number(h.calificacion || 5);
                for(let i = 0; i < 5; i++) {
                    estrellas += i < cal ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                }
                
                return `
                <tr>
                    <td><strong>${h.numero_pedido || '#' + h.id}</strong></td>
                    <td>${htmlspecialchars(h.cliente || h.client_name || '-')}</td>
                    <td>${htmlspecialchars(h.fecha_entrega || h.created_at || '-')}</td>
                    <td>${htmlspecialchars((h.zona || 'N/A').toUpperCase())}</td>
                    <td><span class="badge-ganancia">${formatearDinero(h.ganancia || 0)}</span></td>
                    <td><div class="estrellas">${estrellas} <span>(${cal}.0)</span></div></td>
                </tr>
            `}).join('');
        }

        function htmlspecialchars(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        async function aceptarPedido(id) {
            if (!token) return;
            try {
                const res = await fetch(`/api/repartidor/pedidos/${id}/estado`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ estado: 'picked' })
                });
                const data = await res.json();
                if (data.success) {
                    mostrarToast({ titulo: '✅ Pedido Aceptado', mensaje: `Pedido #${id} aceptado`, tipo: 'success' });
                    await loadOrders();
                    await loadStats();
                } else {
                    mostrarToast({ titulo: 'Error', mensaje: data.message || 'No se pudo aceptar', tipo: 'error' });
                }
            } catch (e) {
                mostrarToast({ titulo: 'Error', mensaje: 'Error de conexión', tipo: 'error' });
            }
        }

        async function rechazarPedido(id) {
            if (!token) return;
            try {
                const res = await fetch(`/api/repartidor/pedidos/${id}/estado`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ estado: 'rechazado' })
                });
                const data = await res.json();
                if (data.success) {
                    mostrarToast({ titulo: '❌ Pedido Rechazado', mensaje: `Pedido #${id} rechazado`, tipo: 'info' });
                    await loadOrders();
                    await loadStats();
                } else {
                    mostrarToast({ titulo: 'Error', mensaje: data.message || 'No se pudo rechazar', tipo: 'error' });
                }
            } catch (e) {
                mostrarToast({ titulo: 'Error', mensaje: 'Error de conexión', tipo: 'error' });
            }
        }

        async function entregarPedido(id) {
            if (!token) return;
            try {
                const res = await fetch(`/api/repartidor/pedidos/${id}/estado`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ estado: 'delivered' })
                });
                const data = await res.json();
                if (data.success) {
                    mostrarToast({ titulo: '✅ Entrega Completada', mensaje: `Pedido #${id} entregado`, tipo: 'success' });
                    await loadOrders();
                    await loadStats();
                } else {
                    mostrarToast({ titulo: 'Error', mensaje: data.message || 'No se pudo entregar', tipo: 'error' });
                }
            } catch (e) {
                mostrarToast({ titulo: 'Error', mensaje: 'Error de conexión', tipo: 'error' });
            }
        }

        function contactarCliente(telefono) {
            if (telefono) {
                window.location.href = `tel:${telefono}`;
            } else {
                mostrarToast({ titulo: 'Info', mensaje: 'No hay teléfono disponible', tipo: 'warning' });
            }
        }

        function applyFilters() {
            renderPedidosDisponibles();
            mostrarToast({ titulo: 'Filtros aplicados', mensaje: 'Lista actualizada', tipo: 'info' });
        }

        function verNotificaciones() {
            mostrarToast({ titulo: 'Notificaciones', mensaje: 'No hay notificaciones nuevas', tipo: 'info' });
        }

        function verTodoHistorial() {
            mostrarToast({ titulo: 'Historial', mensaje: 'Mostrando últimas 10 entregas', tipo: 'info' });
        }

        async function logout() {
            try {
                await fetch('/repartidor/logout', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
            } catch (e) {
                console.warn('Error al cerrar sesión:', e);
            }
            localStorage.removeItem('repartidor_token');
            window.location.href = APP_URL + '/repartidor/login';
        }

        document.getElementById('searchInput').addEventListener('input', () => {
            renderPedidosDisponibles();
        });

        document.getElementById('filterStatus').addEventListener('change', () => {
            renderPedidosDisponibles();
        });

        document.getElementById('filterPriority').addEventListener('change', () => {
            renderPedidosDisponibles();
        });

        document.addEventListener('DOMContentLoaded', async () => {
            if (token) {
                await loadStats();
                await loadOrders();
            } else {
                window.location.href = APP_URL + '/repartidor/login';
            }
        });
    </script>
</body>
</html>