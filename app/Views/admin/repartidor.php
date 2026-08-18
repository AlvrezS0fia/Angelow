<?php
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . APP_URL . '/auth/login');
    exit();
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANGELOW - Gestión de Repartidores</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>const APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <header class="admin-header">
        <div class="admin-logo">
            <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="admin-logo-img">
            <div class="admin-logo-text">
                <span>ANGELOW</span>
                <span>REPARTIDORES</span>
            </div>
        </div>
        <div class="admin-nav">
            <div class="admin-user">
                <div class="admin-user-img"><?= strtoupper(substr($user['nombre'] ?? 'A', 0, 2)) ?></div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['nombre'] ?? 'Administrador') ?></div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Administrador</div>
                </div>
            </div>
            <a href="<?= APP_URL ?>/admin" class="btn-back-header">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
            <a href="http://localhost:3000" class="btn-back-header" target="_blank" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none;">
                <i class="fas fa-external-link-alt" width="18" height="18"></i>
                Abrir App Repartidor
            </a>
            <button class="logout-btn" onclick="window.location.href='<?= APP_URL ?>/auth/logout'">Salir</button>
        </div>
    </header>

    <div class="admin-container">
        <nav class="admin-sidebar">
            <ul class="admin-menu">
                <li><a href="<?= APP_URL ?>/admin" data-section="dashboard"><i class="fas fa-gauge-high admin-menu-icon icon-dashboard"></i><span>Dashboard</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/pedidos" data-section="orders"><i class="fas fa-clipboard-list admin-menu-icon icon-orders"></i><span>Pedidos</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/usuarios" data-section="customers"><i class="fas fa-users admin-menu-icon icon-customers"></i><span>Usuarios</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/repartidores" class="active" data-section="delivery"><i class="fas fa-truck-fast admin-menu-icon icon-delivery"></i><span>Repartidores</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/inventario" data-section="products"><i class="fas fa-box admin-menu-icon icon-products"></i><span>Inventario</span></a></li>
            </ul>
        </nav>

        <main class="admin-content">
            <section id="delivery-section" class="admin-section active-section">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Repartidores</h2>
                    <button class="btn btn-primary" onclick="openDeliveryModal()">
                        <i class="fas fa-plus"></i> Nuevo Repartidor
                    </button>
                </div>

                <div class="metrics-grid" style="margin-bottom: 30px;">
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalRepartidores">0</div>
                                <div class="metric-label">Total Repartidores</div>
                            </div>
                            <div class="metric-icon metric-icon-orders"><i class="fas fa-truck"></i></div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="repartidoresActivos">0</div>
                                <div class="metric-label">Activos</div>
                            </div>
                            <div class="metric-icon metric-icon-pending" style="background: rgba(16,185,129,0.15); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="repartidoresEnRuta">0</div>
                                <div class="metric-label">En Ruta</div>
                            </div>
                            <div class="metric-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;"><i class="fas fa-road"></i></div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div>
                                <div class="metric-value" id="totalEntregasRepartidores">0</div>
                                <div class="metric-label">Entregas Totales</div>
                            </div>
                            <div class="metric-icon metric-icon-revenue"><i class="fas fa-box-open"></i></div>
                        </div>
                    </div>
                </div>

                <div class="delivery-grid" id="deliveryGrid"></div>

                <h3 class="section-title" style="margin: 30px 0 20px;">Solicitudes Pendientes</h3>
                <div id="solicitudesPendientes" style="overflow-x: auto; margin-bottom: 30px;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Vehículo</th>
                                <th>Placa</th>
                                <th>Fecha Solicitud</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="solicitudesTable">
                            <tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--texto-secundario);">Cargando solicitudes...</td></tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="section-title" style="margin: 30px 0 20px;">Listado General de Repartidores</h3>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Vehículo</th>
                                <th>Estado</th>
                                <th>Pedidos</th>
                                <th>Calificación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="deliveryTable"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="delivery-modal-overlay" id="deliveryModal">
        <div class="delivery-modal-container">
            <div class="delivery-modal-header">
                <h2 id="deliveryModalTitle">Agregar Nuevo Repartidor</h2>
                <button class="delivery-modal-close" onclick="closeDeliveryModal()">×</button>
            </div>
            <div class="delivery-modal-body">
                <div class="delivery-avatar-upload">
                    <div class="delivery-avatar-preview" id="deliveryAvatarPreview">👤</div>
                    <label class="delivery-avatar-upload-btn">
                        <input type="file" id="deliveryAvatar" accept="image/*" style="display: none;">
                        📷 Subir foto
                    </label>
                </div>

                <h3 class="modal-section-title">👤 Información Personal</h3>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Nombre Completo</label><input type="text" id="deliveryName" placeholder="Ej: Juan Pérez"></div>
                    <div class="delivery-form-group"><label class="required">Email</label><input type="email" id="deliveryEmail" placeholder="ejemplo@angelow.com"></div>
                </div>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Teléfono</label><input type="tel" id="deliveryPhone" placeholder="3001234567"></div>
                    <div class="delivery-form-group"><label class="required">Tipo Documento</label>
                        <select id="deliveryIdType">
                            <option value="CC">Cédula de Ciudadanía</option>
                            <option value="CE">Cédula de Extranjería</option>
                            <option value="TI">Tarjeta de Identidad</option>
                            <option value="PAS">Pasaporte</option>
                        </select>
                    </div>
                </div>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Número Documento</label><input type="text" id="deliveryIdNumber" placeholder="1234567890"></div>
                    <div class="delivery-form-group"><label>Fecha Nacimiento</label><input type="date" id="deliveryBirthDate"></div>
                </div>
                <div class="delivery-form-group"><label>Dirección</label><input type="text" id="deliveryAddress" placeholder="Calle 123 #45-67, Ciudad"></div>
                <div class="delivery-form-group"><label>Tipo de Sangre</label>
                    <select id="deliveryBloodType">
                        <option value="">Seleccionar</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>

                <h3 class="modal-section-title">🚚 Información Laboral</h3>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label class="required">Vehículo</label>
                        <select id="deliveryVehicle">
                            <option value="">Seleccionar</option>
                            <option value="Moto">Moto</option>
                            <option value="Carro">Carro</option>
                            <option value="Bicicleta">Bicicleta</option>
                            <option value="Camión">Camión</option>
                        </select>
                    </div>
                    <div class="delivery-form-group"><label>Placa</label><input type="text" id="deliveryLicensePlate" placeholder="ABC-123"></div>
                </div>
                <div class="delivery-form-row">
                    <div class="delivery-form-group"><label>Número de Licencia</label><input type="text" id="deliveryLicenseNumber" placeholder="LIC-2025-001"></div>
                    <div class="delivery-form-group"><label class="required">Estado</label>
                        <select id="deliveryStatus">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="on-route">En ruta</option>
                        </select>
                    </div>
                </div>

                <h3 class="modal-section-title">📞 Contacto de Emergencia</h3>
                <div class="delivery-form-group"><label>Contacto de Emergencia</label><input type="text" id="deliveryEmergencyContact" placeholder="Nombre y teléfono"></div>
                <div class="delivery-form-group"><label>Notas adicionales</label><textarea id="deliveryNotes" rows="3" placeholder="Información relevante..."></textarea></div>
            </div>
            <div class="delivery-modal-footer">
                <button class="btn btn-secondary" onclick="closeDeliveryModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="saveDeliveryDriver()" id="deliveryModalSaveBtn">Agregar Repartidor</button>
            </div>
        </div>
    </div>

    <script src="<?= APP_URL ?>/assets/js/panel.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDeliveryDrivers();
        });

        async function loadDeliveryDrivers() {
            try {
                const res = await fetch(`${APP_URL}/api/clientes`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (Array.isArray(data)) {
                    const drivers = data.filter(u => (u.rol || '').toLowerCase() === 'repartidor');
                    renderDeliveryTable(drivers);
                    renderDeliveryGrid(drivers);
                    updateDeliveryMetrics(drivers);
                }
            } catch (e) {
                console.error('Error al cargar repartidores:', e);
            }
        }

        function renderDeliveryTable(drivers) {
            const tbody = document.getElementById('deliveryTable');
            if (!tbody) return;

            if (drivers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">No hay repartidores registrados</td></tr>';
                return;
            }

            tbody.innerHTML = drivers.map(d => {
                const statusClass = d.estado === 'activo' ? 'status-delivered' : d.estado === 'inactivo' ? 'status-cancelled' : d.estado === 'pendiente' ? 'status-pending' : 'status-pending';
                const statusText = d.estado === 'activo' ? 'Activo' : d.estado === 'inactivo' ? 'Inactivo' : d.estado === 'pendiente' ? 'Pendiente' : 'En ruta';
                return `
                    <tr>
                        <td style="font-weight: 600; color: var(--primary);">${d.id}</td>
                        <td>${d.nombre} ${d.apellido || ''}</td>
                        <td>${d.email}</td>
                        <td>${d.telefono || '-'}</td>
                        <td>${d.tipo_vehiculo || '-'} ${d.placa_vehiculo ? '(' + d.placa_vehiculo + ')' : ''}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${d.total_entregas || 0}</td>
                        <td>⭐ ${(d.calificacion_promedio || 5.00).toFixed(1)}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn action-edit" title="Editar" onclick="editDeliveryDriver(${d.id})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/></svg>
                                </button>
                                <button class="action-btn action-delete" title="Eliminar" onclick="deleteDeliveryDriver(${d.id}, '${d.nombre}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderDeliveryGrid(drivers) {
            const grid = document.getElementById('deliveryGrid');
            if (!grid) return;

            const activeDrivers = drivers.filter(d => d.estado === 'activo' || d.estado === 'on-route');
            if (activeDrivers.length === 0) {
                grid.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">No hay repartidores activos en este momento</div>';
                return;
            }

            grid.innerHTML = activeDrivers.map(d => {
                const initials = (d.nombre || 'R').charAt(0).toUpperCase();
                const statusColor = d.estado === 'activo' ? '#10b981' : d.estado === 'on-route' ? '#f59e0b' : '#6b7280';
                return `
                    <div class="delivery-card">
                        <div class="delivery-card-header">
                            <div class="delivery-avatar" style="background: ${statusColor}20; color: ${statusColor};">${initials}</div>
                            <div>
                                <div style="font-weight: 700; font-size: 16px;">${d.nombre} ${d.apellido || ''}</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">${d.email}</div>
                            </div>
                        </div>
                        <div class="delivery-card-body">
                            <div class="delivery-stat">
                                <span class="delivery-stat-label">📞 Teléfono</span>
                                <span class="delivery-stat-value">${d.telefono || '-'}</span>
                            </div>
                            <div class="delivery-stat">
                                <span class="delivery-stat-label">🚚 Vehículo</span>
                                <span class="delivery-stat-value">${d.tipo_vehiculo || '-'} ${d.placa_vehiculo ? '(' + d.placa_vehiculo + ')' : ''}</span>
                            </div>
                            <div class="delivery-stat">
                                <span class="delivery-stat-label">📦 Entregas</span>
                                <span class="delivery-stat-value">${d.total_entregas || 0}</span>
                            </div>
                            <div class="delivery-stat">
                                <span class="delivery-stat-label">⭐ Calificación</span>
                                <span class="delivery-stat-value">${(d.calificacion_promedio || 5.00).toFixed(1)}</span>
                            </div>
                        </div>
                        <div class="delivery-card-footer">
                            <span class="delivery-status-badge" style="background: ${statusColor}20; color: ${statusColor};">
                                <span class="punto-verde" style="background: ${statusColor};"></span>
                                ${d.estado === 'activo' ? 'Activo' : d.estado === 'on-route' ? 'En ruta' : 'Inactivo'}
                            </span>
                            <a href="http://localhost:3000" target="_blank" class="btn btn-sm" style="background: var(--primary); color: white; text-decoration: none;">Ver Panel</a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateDeliveryMetrics(drivers) {
            const total = drivers.length;
            const activos = drivers.filter(d => d.estado === 'activo').length;
            const enRuta = drivers.filter(d => d.estado === 'on-route').length;
            const entregas = drivers.reduce((sum, d) => sum + (d.total_entregas || 0), 0);

            const totalEl = document.getElementById('totalRepartidores');
            const activosEl = document.getElementById('repartidoresActivos');
            const enRutaEl = document.getElementById('repartidoresEnRuta');
            const entregasEl = document.getElementById('totalEntregasRepartidores');

            if (totalEl) totalEl.textContent = total;
            if (activosEl) activosEl.textContent = activos;
            if (enRutaEl) enRutaEl.textContent = enRuta;
            if (entregasEl) entregasEl.textContent = entregas;
        }

        window.openDeliveryModal = function() {
            document.getElementById('deliveryModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.closeDeliveryModal = function() {
            document.getElementById('deliveryModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        };

        window.saveDeliveryDriver = function() {
            const name = document.getElementById('deliveryName').value.trim();
            const email = document.getElementById('deliveryEmail').value.trim();
            const phone = document.getElementById('deliveryPhone').value.trim();
            const vehicle = document.getElementById('deliveryVehicle').value;
            const status = document.getElementById('deliveryStatus').value;

            if (!name || !email || !phone || !vehicle) {
                showToast({ title: 'Error', message: 'Completa los campos obligatorios', type: 'error' });
                return;
            }

            const btn = document.getElementById('deliveryModalSaveBtn');
            btn.disabled = true;
            btn.textContent = 'Guardando...';

            fetch(`${APP_URL}/api/usuarios`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    nombre: name,
                    email: email,
                    telefono: phone,
                    tipo_vehiculo: vehicle,
                    placa_vehiculo: document.getElementById('deliveryLicensePlate').value,
                    rol: 'repartidor',
                    estado: status === 'active' ? 'activo' : status === 'on-route' ? 'en_ruta' : 'inactivo',
                    password_hash: '$2y$10$' + btoa(Math.random().toString()).substring(0, 60)
                })
            })
            .then(res => res.json())
            .then(data => {
                showToast({ title: 'Éxito', message: 'Repartidor agregado correctamente', type: 'success' });
                closeDeliveryModal();
                loadDeliveryDrivers();
            })
            .catch(err => {
                console.error(err);
                showToast({ title: 'Error', message: 'No se pudo guardar el repartidor', type: 'error' });
                btn.disabled = false;
                btn.textContent = 'Agregar Repartidor';
            });
        };

        window.editDeliveryDriver = function(id) {
            showToast({ title: 'Info', message: 'Función de edición en desarrollo', type: 'info' });
        };

        window.deleteDeliveryDriver = function(id, name) {
            if (!confirm(`¿Eliminar al repartidor "${name}"?`)) return;
            fetch(`${APP_URL}/api/usuarios/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(() => {
                    showToast({ title: 'Éxito', message: 'Repartidor eliminado', type: 'success' });
                    loadDeliveryDrivers();
                })
                .catch(() => showToast({ title: 'Error', message: 'No se pudo eliminar', type: 'error' }));
        };

        window.loadSolicitudes = function() {
            fetch(`${APP_URL}/api/admin/repartidores/solicitudes?estado=pendiente`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('solicitudesTable');
                if (!data.success || !data.solicitudes || data.solicitudes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--texto-secundario);">No hay solicitudes pendientes</td></tr>';
                    return;
                }
                tbody.innerHTML = data.solicitudes.map(s => `
                    <tr>
                        <td>${s.id}</td>
                        <td>${htmlspecialchars(s.nombres + ' ' + (s.apellidos || ''))}</td>
                        <td>${htmlspecialchars(s.email)}</td>
                        <td>${htmlspecialchars(s.telefono || '-')}</td>
                        <td>${htmlspecialchars(s.tipo_vehiculo || '-')}</td>
                        <td>${htmlspecialchars(s.placa_vehiculo || '-')}</td>
                        <td>${new Date(s.fecha_solicitud).toLocaleDateString('es-CO')}</td>
                        <td>
                            <button class="btn btn-sm" style="background: #10b981; color: white; border: none; margin-right: 8px; cursor: pointer;" onclick="aprobarSolicitud(${s.id})">Aceptar</button>
                            <button class="btn btn-sm" style="background: #ef4444; color: white; border: none; cursor: pointer;" onclick="rechazarSolicitud(${s.id})">Rechazar</button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(() => {
                document.getElementById('solicitudesTable').innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">Error al cargar solicitudes</td></tr>';
            });
        };

        window.aprobarSolicitud = function(id) {
            if (!confirm('¿Aprobar esta solicitud de repartidor?')) return;
            fetch(`${APP_URL}/api/admin/repartidores/solicitudes/aprobar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast({ title: 'Éxito', message: 'Solicitud aprobada', type: 'success' });
                    loadSolicitudes();
                    loadDeliveryDrivers();
                } else {
                    showToast({ title: 'Error', message: data.message || 'No se pudo aprobar', type: 'error' });
                }
            })
            .catch(() => showToast({ title: 'Error', message: 'Error de conexión', type: 'error' }));
        };

        window.rechazarSolicitud = function(id) {
            const obs = prompt('Motivo del rechazo (opcional):');
            if (obs === null) return;
            fetch(`${APP_URL}/api/admin/repartidores/solicitudes/rechazar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id: id, observaciones: obs })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast({ title: 'Info', message: 'Solicitud rechazada', type: 'info' });
                    loadSolicitudes();
                } else {
                    showToast({ title: 'Error', message: data.message || 'No se pudo rechazar', type: 'error' });
                }
            })
            .catch(() => showToast({ title: 'Error', message: 'Error de conexión', type: 'error' }));
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadSolicitudes();
        });
    </script>
</body>
</html>
