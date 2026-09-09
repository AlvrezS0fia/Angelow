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
    <title>ANGELOW — Gestión de Repartidores</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>const APP_URL = '<?= APP_URL ?>';</script>
    <style>
        .rp-wrap{padding:24px;max-width:1400px;margin:0 auto;}
        .rp-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
        .rp-metric{background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--border-light);display:flex;align-items:center;gap:16px;transition:all .25s;cursor:default;}
        .rp-metric:hover{transform:translateY(-2px);box-shadow:0 8px 24px var(--shadow);}
        .rp-metric-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
        .rp-metric-icon.blue{background:var(--bg-soft);color:var(--primary);}
        .rp-metric-icon.green{background:#ECFDF5;color:#10b981;}
        .rp-metric-icon.amber{background:#FFFBEB;color:#f59e0b;}
        .rp-metric-icon.purple{background:#F5F3FF;color:#8b5cf6;}
        .rp-metric-val{font-size:28px;font-weight:800;color:var(--text-dark);line-height:1;}
        .rp-metric-lbl{font-size:13px;color:var(--text-secondary);font-weight:500;margin-top:2px;}

        .rp-tabs{display:flex;gap:4px;background:#fff;border-radius:14px;padding:5px;border:1px solid var(--border-light);margin-bottom:24px;width:fit-content;}
        .rp-tab{padding:10px 22px;border-radius:10px;font-size:14px;font-weight:600;color:var(--text-secondary);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;border:none;background:none;font-family:'Inter',sans-serif;}
        .rp-tab:hover{color:var(--text-dark);background:var(--bg-soft);}
        .rp-tab.active{background:var(--primary);color:#fff;box-shadow:0 4px 12px var(--shadow);}
        .rp-tab .rp-badge{background:rgba(255,255,255,.25);padding:1px 8px;border-radius:50px;font-size:11px;font-weight:700;}
        .rp-tab.active .rp-badge{background:rgba(255,255,255,.3);}

        .rp-panel{display:none;}
        .rp-panel.active{display:block;}

        .rp-empty{text-align:center;padding:60px 24px;background:#fff;border-radius:16px;border:1px solid var(--border-light);}
        .rp-empty-icon{width:72px;height:72px;border-radius:50%;background:var(--bg-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:var(--success);}
        .rp-empty-title{font-size:17px;font-weight:700;color:var(--text-dark);margin-bottom:4px;}
        .rp-empty-msg{font-size:13px;color:var(--text-secondary);}

        .rp-sol{background:#fff;border-radius:16px;border:1px solid var(--border-light);margin-bottom:20px;overflow:hidden;transition:all .25s;}
        .rp-sol:hover{box-shadow:0 8px 28px var(--shadow);}
        .rp-sol-header{display:flex;align-items:center;gap:16px;padding:20px 24px;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border-bottom:1px solid #FDE68A;}
        .rp-sol-avatar{width:48px;height:48px;border-radius:50%;background:#F59E0B;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;flex-shrink:0;}
        .rp-sol-name{font-size:18px;font-weight:700;color:#92400E;}
        .rp-sol-email{font-size:13px;color:#A16207;margin-top:2px;}
        .rp-sol-date{margin-left:auto;text-align:right;flex-shrink:0;}
        .rp-sol-date-val{font-size:13px;font-weight:600;color:#92400E;}
        .rp-sol-date-time{font-size:12px;color:#A16207;}

        .rp-sol-body{padding:24px;}
        .rp-sol-section{margin-bottom:20px;}
        .rp-sol-section:last-child{margin-bottom:0;}
        .rp-sol-section-title{font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;display:flex;align-items:center;gap:6px;}
        .rp-sol-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;}
        .rp-info-card{padding:12px 16px;background:var(--bg-soft);border-radius:10px;}
        .rp-info-label{font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;}
        .rp-info-value{font-size:14px;font-weight:600;color:var(--text-dark);margin-top:3px;}

        .rp-doc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
        .rp-doc{border-radius:12px;overflow:hidden;border:1px solid var(--border-light);cursor:pointer;transition:all .2s;position:relative;}
        .rp-doc:hover{border-color:var(--primary);box-shadow:0 4px 12px var(--shadow);transform:translateY(-2px);}
        .rp-doc-img{width:100%;height:160px;object-fit:cover;display:block;}
        .rp-doc-icon{width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:var(--bg-soft);font-size:36px;color:var(--text-secondary);}
        .rp-doc-badge{position:absolute;top:8px;right:8px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
        .rp-doc-badge.pendiente{background:#f59e0b;color:#fff;}
        .rp-doc-badge.aprobado{background:#10b981;color:#fff;}
        .rp-doc-badge.rechazado{background:#ef4444;color:#fff;}
        .rp-doc-badge.missing{background:#6b7280;color:#fff;}
        .rp-doc-label{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));color:#fff;font-size:11px;padding:8px 12px;font-weight:500;}
        .rp-doc-hover{position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;}
        .rp-doc:hover .rp-doc-hover{opacity:1;}
        .rp-doc-hover span{background:#fff;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;color:var(--text-dark);display:flex;align-items:center;gap:6px;}
        .rp-doc.missing{opacity:.6;cursor:default;}
        .rp-doc.missing:hover{border-color:var(--border-light);box-shadow:none;transform:none;}

        .rp-sol-actions{display:flex;gap:12px;justify-content:flex-end;padding-top:20px;border-top:1px solid var(--border-light);margin-top:20px;}
        .rp-btn{padding:11px 28px;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;border:none;font-family:'Inter',sans-serif;}
        .rp-btn:hover{transform:translateY(-1px);}
        .rp-btn-approve{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 4px 16px rgba(16,185,129,.3);}
        .rp-btn-approve:hover{box-shadow:0 6px 20px rgba(16,185,129,.4);}
        .rp-btn-reject{border:2px solid #fecaca;background:#fff;color:#dc2626;}
        .rp-btn-reject:hover{background:#fef2f2;}
        .rp-btn-suspend{border:2px solid #fde68a;background:#fff;color:#d97706;}
        .rp-btn-suspend:hover{background:#fffbeb;}
        .rp-btn-activate{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;box-shadow:0 4px 16px rgba(59,130,246,.3);}
        .rp-btn-activate:hover{box-shadow:0 6px 20px rgba(59,130,246,.4);}

        .rp-drivers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;}
        .rp-driver{background:#fff;border-radius:16px;border:1px solid var(--border-light);padding:20px;transition:all .25s;}
        .rp-driver:hover{box-shadow:0 8px 28px var(--shadow);transform:translateY(-2px);}
        .rp-driver-top{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
        .rp-driver-avatar{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:17px;flex-shrink:0;}
        .rp-driver-avatar.activo{background:#ECFDF5;color:#10b981;}
        .rp-driver-avatar.suspendido{background:#FEF2F2;color:#ef4444;}
        .rp-driver-avatar.pendiente{background:#FFFBEB;color:#f59e0b;}
        .rp-driver-avatar.inactivo{background:#F3F4F6;color:#6b7280;}
        .rp-driver-name{font-size:16px;font-weight:700;color:var(--text-dark);}
        .rp-driver-email{font-size:12px;color:var(--text-secondary);}
        .rp-driver-status{margin-left:auto;}
        .rp-status-dot{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:600;}
        .rp-status-dot .dot{width:7px;height:7px;border-radius:50%;}
        .rp-status-dot.activo{background:#ECFDF5;color:#059669;}
        .rp-status-dot.activo .dot{background:#10b981;}
        .rp-status-dot.suspendido{background:#FEF2F2;color:#dc2626;}
        .rp-status-dot.suspendido .dot{background:#ef4444;}
        .rp-status-dot.pendiente{background:#FFFBEB;color:#d97706;}
        .rp-status-dot.pendiente .dot{background:#f59e0b;}
        .rp-status-dot.inactivo{background:#F3F4F6;color:#6b7280;}
        .rp-status-dot.inactivo .dot{background:#9ca3af;}

        .rp-driver-info{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
        .rp-driver-info-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);}
        .rp-driver-info-item i{color:var(--primary);font-size:12px;width:16px;text-align:center;}
        .rp-driver-info-item span{color:var(--text-dark);font-weight:500;}

        .rp-driver-stats{display:flex;gap:12px;padding-top:14px;border-top:1px solid var(--border-light);}
        .rp-driver-stat{flex:1;text-align:center;padding:8px;background:var(--bg-soft);border-radius:8px;}
        .rp-driver-stat-val{font-size:16px;font-weight:700;color:var(--text-dark);}
        .rp-driver-stat-lbl{font-size:10px;color:var(--text-secondary);text-transform:uppercase;font-weight:600;letter-spacing:.5px;}

        .rp-driver-actions{display:flex;gap:8px;margin-top:14px;}
        .rp-driver-btn{flex:1;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;border:none;display:flex;align-items:center;justify-content:center;gap:5px;font-family:'Inter',sans-serif;}
        .rp-driver-btn.suspend{background:#FEF2F2;color:#dc2626;}
        .rp-driver-btn.suspend:hover{background:#FEE2E2;}
        .rp-driver-btn.activate{background:#ECFDF5;color:#059669;}
        .rp-driver-btn.activate:hover{background:#D1FAE5;}
        .rp-driver-btn.delete{background:#F3F4F6;color:#6b7280;}
        .rp-driver-btn.delete:hover{background:#FEE2E2;color:#dc2626;}

        .rp-sol-rejected .rp-sol-header{background:linear-gradient(135deg,#FEF2F2,#FECACA);border-bottom-color:#FECACA;}
        .rp-sol-rejected .rp-sol-avatar{background:#ef4444;}
        .rp-sol-rejected .rp-sol-name{color:#991B1B;}
        .rp-sol-rejected .rp-sol-email{color:#B91C1C;}

        .rp-timeline{display:flex;align-items:center;gap:0;margin:16px 0;}
        .rp-timeline-step{display:flex;align-items:center;gap:8px;flex:1;}
        .rp-timeline-dot{width:10px;height:10px;border-radius:50%;background:var(--border-light);flex-shrink:0;transition:all .3s;}
        .rp-timeline-dot.done{background:#10b981;}
        .rp-timeline-dot.active{background:#f59e0b;box-shadow:0 0 0 4px rgba(245,158,11,.2);}
        .rp-timeline-line{flex:1;height:2px;background:var(--border-light);margin:0 4px;}
        .rp-timeline-line.done{background:#10b981;}
        .rp-timeline-label{font-size:11px;color:var(--text-secondary);font-weight:500;white-space:nowrap;}

        .rp-table-wrap{overflow-x:auto;background:#fff;border-radius:16px;border:1px solid var(--border-light);}
        .rp-table{width:100%;border-collapse:collapse;}
        .rp-table th{text-align:left;padding:14px 18px;font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;background:var(--bg-soft);border-bottom:1px solid var(--border-light);}
        .rp-table td{padding:14px 18px;font-size:13px;color:var(--text-dark);border-bottom:1px solid var(--border-light);}
        .rp-table tr:last-child td{border-bottom:none;}
        .rp-table tr:hover td{background:var(--bg-soft);}

        @media(max-width:1100px){.rp-metrics{grid-template-columns:repeat(2,1fr);}.rp-doc-grid{grid-template-columns:repeat(2,1fr);}}
        @media(max-width:768px){.rp-metrics{grid-template-columns:1fr;}.rp-doc-grid{grid-template-columns:1fr 1fr;}.rp-drivers-grid{grid-template-columns:1fr;}.rp-sol-header{flex-wrap:wrap;}.rp-tabs{width:100%;overflow-x:auto;}.rp-sol-grid{grid-template-columns:1fr;}.rp-sol-actions{flex-wrap:wrap;}}
    </style>
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
                <li><a href="<?= APP_URL ?>/admin"><i class="fas fa-gauge-high admin-menu-icon icon-dashboard"></i><span>Dashboard</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/pedidos"><i class="fas fa-clipboard-list admin-menu-icon icon-orders"></i><span>Pedidos</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/usuarios"><i class="fas fa-users admin-menu-icon icon-customers"></i><span>Usuarios</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/repartidores" class="active"><i class="fas fa-truck-fast admin-menu-icon icon-delivery"></i><span>Repartidores</span></a></li>
                <li><a href="<?= APP_URL ?>/admin"><i class="fas fa-shield-halved admin-menu-icon icon-settings"></i><span>Administración</span></a></li>
                <li><a href="<?= APP_URL ?>/admin/inventario"><i class="fas fa-box admin-menu-icon icon-products"></i><span>Inventario</span></a></li>
            </ul>
        </nav>

        <main class="admin-content">
            <section class="admin-section active-section" style="padding:0;">
                <div class="rp-wrap">
                    <div class="section-header" style="margin-bottom:24px;">
                        <h2 class="section-title">Gestión de Repartidores</h2>
                    </div>

                    <div class="rp-metrics">
                        <div class="rp-metric">
                            <div class="rp-metric-icon blue"><i class="fas fa-truck"></i></div>
                            <div><div class="rp-metric-val" id="totalRepartidores">0</div><div class="rp-metric-lbl">Total Repartidores</div></div>
                        </div>
                        <div class="rp-metric">
                            <div class="rp-metric-icon green"><i class="fas fa-check-circle"></i></div>
                            <div><div class="rp-metric-val" id="repartidoresActivos">0</div><div class="rp-metric-lbl">Activos</div></div>
                        </div>
                        <div class="rp-metric">
                            <div class="rp-metric-icon amber"><i class="fas fa-clock"></i></div>
                            <div><div class="rp-metric-val" id="repartidoresPendientes">0</div><div class="rp-metric-lbl">Pendientes</div></div>
                        </div>
                        <div class="rp-metric">
                            <div class="rp-metric-icon purple"><i class="fas fa-box-open"></i></div>
                            <div><div class="rp-metric-val" id="totalEntregasRepartidores">0</div><div class="rp-metric-lbl">Entregas Totales</div></div>
                        </div>
                    </div>

                    <div class="rp-tabs">
                        <button class="rp-tab active" onclick="switchTab('solicitudes')" id="tab-solicitudes">
                            <i class="fas fa-clipboard-check"></i> Solicitudes
                            <span class="rp-badge" id="tabBadgeSol">0</span>
                        </button>
                        <button class="rp-tab" onclick="switchTab('activos')" id="tab-activos">
                            <i class="fas fa-truck"></i> Repartidores
                        </button>
                        <button class="rp-tab" onclick="switchTab('todos')" id="tab-todos">
                            <i class="fas fa-list"></i> Todos
                        </button>
                    </div>

                    <div class="rp-panel active" id="panel-solicitudes">
                        <div id="solicitudesContainer"></div>
                    </div>

                    <div class="rp-panel" id="panel-activos">
                        <div class="rp-drivers-grid" id="driversGrid"></div>
                    </div>

                    <div class="rp-panel" id="panel-todos">
                        <div class="rp-table-wrap">
                            <table class="rp-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Vehículo</th>
                                        <th>Estado</th>
                                        <th>Entregas</th>
                                        <th>Calificación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="driversTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function escapeHtml(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}

        function showToast(opts) {
            const c = document.getElementById('toastContainer');
            if (!c) return;
            const t = document.createElement('div');
            t.className = 'toast ' + (opts.type || 'info');
            const icons = {success:'fa-check-circle',error:'fa-times-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
            t.innerHTML = '<i class="fas '+(icons[opts.type]||icons.info)+'" style="font-size:18px;flex-shrink:0;color:var(--'+(opts.type==='success'?'success':opts.type==='error'?'error':opts.type==='warning'?'warning':'primary')+')"></i><div style="flex:1;"><div style="font-weight:700;font-size:14px;color:var(--text-dark);margin-bottom:2px;">'+( opts.title||'')+'</div><div style="font-size:13px;color:var(--text-secondary);">'+(opts.message||'')+'</div></div>';
            c.appendChild(t);
            setTimeout(()=>t.classList.add('show'),10);
            setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),400);},4000);
        }

        function switchTab(tab){
            document.querySelectorAll('.rp-tab').forEach(t=>t.classList.remove('active'));
            document.querySelectorAll('.rp-panel').forEach(p=>p.classList.remove('active'));
            document.getElementById('tab-'+tab).classList.add('active');
            document.getElementById('panel-'+tab).classList.add('active');
        }

        function showModal(title, body, buttons){
            const o=document.createElement('div');
            o.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;animation:fadeIn .2s;';
            o.innerHTML='<div style="background:#fff;border-radius:20px;padding:0;max-width:520px;width:92%;box-shadow:0 25px 80px rgba(0,0,0,.3);overflow:hidden;animation:slideUp .3s ease;"><div style="padding:24px 28px 0;"><h3 style="font-size:20px;font-weight:800;color:var(--text-dark);margin-bottom:4px;">'+title+'</h3></div><div style="padding:16px 28px 24px;">'+body+'</div><div style="display:flex;gap:10px;justify-content:flex-end;padding:16px 28px;border-top:1px solid var(--border-light);background:var(--bg-soft);">'+buttons.map(b=>'<button onclick="this.closest(\'div[style]\').parentElement.remove()" class="rp-btn '+(b.cls||'')+'" id="'+(b.id||'')+'">'+b.label+'</button>').join('')+'</div></div>';
            document.body.appendChild(o);
            o.addEventListener('click',e=>{if(e.target===o)o.remove();});
            return o;
        }

        function showObservacionesModal(title, callback){
            const o=showModal(title,'<p style="font-size:13px;color:var(--text-secondary);margin-bottom:12px;">Opcionalmente agrega un comentario para el repartidor.</p><textarea id="modalObs" rows="3" placeholder="Escribe observaciones..." style="width:100%;padding:12px;border:2px solid var(--border-light);border-radius:12px;font-family:Inter,sans-serif;font-size:14px;resize:vertical;box-sizing:border-box;transition:border-color .2s;" onfocus="this.style.borderColor=\'var(--primary)\'" onblur="this.style.borderColor=\'var(--border-light)\'"></textarea>',[
                {label:'Cancelar',cls:'rp-btn-reject',id:'modalCancelBtn'},
                {label:'Confirmar',cls:'rp-btn-approve',id:'modalConfirmBtn'}
            ]);
            o.querySelector('#modalConfirmBtn').onclick=()=>{const v=o.querySelector('#modalObs').value.trim();o.remove();callback(v);};
        }

        function showSuspendModal(callback){
            const o=showModal('Suspender Repartidor','<p style="font-size:13px;color:var(--text-secondary);margin-bottom:12px;">El repartidor no podrá gestionar pedidos mientras esté suspendido.</p><textarea id="modalMotivo" rows="3" placeholder="Motivo de la suspensión (recomendado)..." style="width:100%;padding:12px;border:2px solid var(--border-light);border-radius:12px;font-family:Inter,sans-serif;font-size:14px;resize:vertical;box-sizing:border-box;transition:border-color .2s;" onfocus="this.style.borderColor=\'var(--primary)\'" onblur="this.style.borderColor=\'var(--border-light)\'"></textarea>',[
                {label:'Cancelar',cls:'rp-btn-reject',id:'modalCancelBtn'},
                {label:'Suspender',cls:'rp-btn-approve',id:'modalConfirmBtn'}
            ]);
            o.querySelector('#modalConfirmBtn').style.background='linear-gradient(135deg,#ef4444,#dc2626)';
            o.querySelector('#modalConfirmBtn').onclick=()=>{const v=o.querySelector('#modalMotivo').value.trim();o.remove();callback(v);};
        }

        function showDocReviewModal(docId, docTipo, docUrl, archivoUrl){
            const isImg = /\.(jpg|jpeg|png)$/i.test(archivoUrl || docUrl || '');
            const preview = isImg
                ? '<img src="'+docUrl+'" style="max-width:100%;max-height:380px;border-radius:12px;object-fit:contain;">'
                : '<iframe src="'+docUrl+'" style="width:100%;height:380px;border:none;border-radius:12px;"></iframe>';
            const o=showModal('Revisar Documento','<p style="font-size:13px;color:var(--text-secondary);margin-bottom:4px;text-transform:capitalize;">'+escapeHtml(docTipo.replace(/_/g,' '))+'</p><div style="text-align:center;margin-bottom:16px;background:var(--bg-soft);border-radius:12px;padding:16px;">'+preview+'</div><textarea id="docReviewObs" rows="2" placeholder="Observaciones (opcional)..." style="width:100%;padding:10px;border:2px solid var(--border-light);border-radius:10px;font-family:Inter,sans-serif;font-size:13px;resize:vertical;box-sizing:border-box;"></textarea>',[
                {label:'Cerrar',cls:'rp-btn-reject',id:'docCloseBtn'},
                {label:'<i class="fas fa-times"></i> Rechazar',cls:'rp-btn-reject',id:'docRejectBtn'},
                {label:'<i class="fas fa-check"></i> Aprobar',cls:'rp-btn-approve',id:'docApproveBtn'}
            ]);
            o.querySelector('#docCloseBtn').onclick=()=>o.remove();
            o.querySelector('#docApproveBtn').onclick=()=>{const obs=o.querySelector('#docReviewObs').value.trim();o.remove();revisarDocumento(docId,'aprobar',obs);};
            o.querySelector('#docRejectBtn').onclick=()=>{const obs=o.querySelector('#docReviewObs').value.trim();o.remove();revisarDocumento(docId,'rechazar',obs);};
        }

        async function revisarDocumento(docId, accion, observaciones){
            try{
                const res=await fetch(APP_URL+'/api/admin/repartidores/documentos/revisar',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({documento_id:docId,accion:accion,observaciones:observaciones})});
                const data=await res.json();
                if(data.success){showToast({title:accion==='aprobar'?'Documento Aprobado':'Documento Rechazado',message:data.message,type:accion==='aprobar'?'success':'info'});loadSolicitudes();}else{showToast({title:'Error',message:data.message||'No se pudo procesar',type:'error'});}
            }catch(e){showToast({title:'Error',message:'Error de conexión',type:'error'});}
        }

        let allDrivers=[];

        function docPreview(doc, label){
            if(!doc) return '<div class="rp-doc missing" style="height:200px;"><div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--bg-soft);"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;font-size:22px;margin-bottom:6px;"></i><div style="font-size:12px;color:var(--text-secondary);font-weight:500;">'+label+'</div><div style="font-size:11px;color:#9ca3af;">No subido</div></div></div>';
            const estado=doc.estado||'pendiente';
            const docSrc = doc.url || (APP_URL+'/'+doc.archivo_url);
            const isImg=/\.(jpg|jpeg|png)$/i.test(doc.archivo_url || docSrc);
            const preview=isImg
                ? '<img class="rp-doc-img" src="'+docSrc+'" alt="'+label+'" onerror="this.outerHTML=\'<div class=\\'rp-doc-icon\\'><i class=\\'fas fa-file\\'></i></div>\'">'
                : '<div class="rp-doc-icon"><i class="fas fa-file-pdf" style="color:#ef4444;"></i></div>';
            return '<div class="rp-doc" onclick="showDocReviewModal('+doc.id+', \''+doc.tipo+'\', \''+escapeHtml(docSrc)+'\', \''+escapeHtml(doc.archivo_url||'')+'\')">'
                +preview
                +'<div class="rp-doc-badge '+estado+'">'+estado.toUpperCase()+'</div>'
                +'<div class="rp-doc-label">'+label+'</div>'
                +'<div class="rp-doc-hover"><span><i class="fas fa-eye"></i> Revisar</span></div>'
                +'</div>';
        }

        function renderSolicitudes(solicitudes){
            const c=document.getElementById('solicitudesContainer');
            const badge=document.getElementById('tabBadgeSol');
            if(!solicitudes||solicitudes.length===0){
                c.innerHTML='<div class="rp-empty"><div class="rp-empty-icon"><i class="fas fa-check-circle"></i></div><div class="rp-empty-title">No hay solicitudes pendientes</div><div class="rp-empty-msg">Todas las solicitudes han sido procesadas</div></div>';
                if(badge)badge.textContent='0';
                return;
            }
            if(badge)badge.textContent=solicitudes.length;

            c.innerHTML=solicitudes.map(s=>{
                const docs=s.documentos||[];
                const tarjeta=docs.find(d=>d.tipo==='tarjeta_propiedad');
                const licencia=docs.find(d=>d.tipo==='licencia_conduccion');
                const soat=docs.find(d=>d.tipo==='soat');
                const tecno=docs.find(d=>d.tipo==='tecnomecanica');
                const f=new Date(s.fecha_solicitud);
                const fStr=f.toLocaleDateString('es-CO',{day:'numeric',month:'long',year:'numeric'});
                const hStr=f.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'});

                return '<div class="rp-sol">'
                +'<div class="rp-sol-header">'
                    +'<div class="rp-sol-avatar"><i class="fas fa-user-clock"></i></div>'
                    +'<div>'
                        +'<div class="rp-sol-name">'+escapeHtml(s.nombres)+' '+escapeHtml(s.apellidos||'')+'</div>'
                        +'<div class="rp-sol-email"><i class="fas fa-envelope" style="margin-right:4px;font-size:11px;"></i>'+escapeHtml(s.email)+' &middot; <i class="fas fa-phone" style="margin-right:2px;font-size:11px;"></i>'+escapeHtml(s.telefono||'-')+'</div>'
                    +'</div>'
                    +'<div class="rp-sol-date">'
                        +'<div class="rp-sol-date-val">'+fStr+'</div>'
                        +'<div class="rp-sol-date-time">'+hStr+'</div>'
                    +'</div>'
                +'</div>'
                +'<div class="rp-sol-body">'
                    +'<div class="rp-sol-section">'
                        +'<div class="rp-sol-section-title"><i class="fas fa-id-card"></i> Información Personal</div>'
                        +'<div class="rp-sol-grid">'
                            +'<div class="rp-info-card"><div class="rp-info-label">Documento</div><div class="rp-info-value">'+escapeHtml(s.tipo_documento||'CC')+' '+escapeHtml(s.numero_documento||'-')+'</div></div>'
                            +'<div class="rp-info-card"><div class="rp-info-label">Vehículo</div><div class="rp-info-value">'+escapeHtml(s.tipo_vehiculo||'-')+'</div></div>'
                            +'<div class="rp-info-card"><div class="rp-info-label">Placa</div><div class="rp-info-value">'+escapeHtml(s.placa_vehiculo||'-')+'</div></div>'
                            +'<div class="rp-info-card"><div class="rp-info-label">Licencia</div><div class="rp-info-value">'+escapeHtml(s.numero_licencia||'-')+(s.categoria_licencia?' ('+escapeHtml(s.categoria_licencia)+')':'')+'</div></div>'
                            +'<div class="rp-info-card"><div class="rp-info-label">Ciudad</div><div class="rp-info-value">'+escapeHtml(s.ciudad||'-')+'</div></div>'
                            +'<div class="rp-info-card"><div class="rp-info-label">Dirección</div><div class="rp-info-value" style="font-size:13px;">'+escapeHtml(s.direccion||'-')+'</div></div>'
                        +'</div>'
                    +'</div>'
                    +'<div class="rp-sol-section">'
                        +'<div class="rp-sol-section-title"><i class="fas fa-id-card"></i> Documentos <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text-secondary);">&mdash; Haz clic para revisar</span></div>'
                        +'<div class="rp-doc-grid">'
                            +docPreview(tarjeta,'Tarjeta de Propiedad')
                            +docPreview(licencia,'Licencia de Conducción')
                            +docPreview(soat,'SOAT')
                            +docPreview(tecno,'Tecnomecánica')
                        +'</div>'
                    +'</div>'
                    +'<div class="rp-sol-actions">'
                        +'<button onclick="rechazarSolicitud('+s.id+')" class="rp-btn rp-btn-reject"><i class="fas fa-times"></i> Rechazar</button>'
                        +'<button onclick="aprobarSolicitud('+s.id+')" class="rp-btn rp-btn-approve"><i class="fas fa-check"></i> Aprobar Solicitud</button>'
                    +'</div>'
                +'</div>'
                +'</div>';
            }).join('');
        }

        function renderDriversGrid(drivers){
            const g=document.getElementById('driversGrid');
            if(drivers.length===0){g.innerHTML='<div class="rp-empty" style="grid-column:1/-1;"><div class="rp-empty-icon" style="background:#F3F4F6;color:#6b7280;"><i class="fas fa-truck"></i></div><div class="rp-empty-title">No hay repartidores</div><div class="rp-empty-msg">Los repartidores aprobados aparecerán aquí</div></div>';return;}
            g.innerHTML=drivers.map(d=>{
                const ini=(d.nombre||'R').charAt(0).toUpperCase();
                return '<div class="rp-driver">'
                    +'<div class="rp-driver-top">'
                        +'<div class="rp-driver-avatar '+d.estado+'">'+ini+'</div>'
                        +'<div><div class="rp-driver-name">'+escapeHtml(d.nombre)+' '+escapeHtml(d.apellido||'')+'</div><div class="rp-driver-email">'+escapeHtml(d.email)+'</div></div>'
                        +'<div class="rp-driver-status"><span class="rp-status-dot '+d.estado+'"><span class="dot"></span>'+(d.estado==='activo'?'Activo':d.estado==='suspendido'?'Suspendido':d.estado==='pendiente'?'Pendiente':'Inactivo')+'</span></div>'
                    +'</div>'
                    +'<div class="rp-driver-info">'
                        +'<div class="rp-driver-info-item"><i class="fas fa-phone"></i> <span>'+escapeHtml(d.telefono||'-')+'</span></div>'
                        +'<div class="rp-driver-info-item"><i class="fas fa-motorcycle"></i> <span>'+escapeHtml(d.tipo_vehiculo||'-')+(d.placa_vehiculo?' ('+escapeHtml(d.placa_vehiculo)+')':'')+'</span></div>'
                        +'<div class="rp-driver-info-item"><i class="fas fa-box"></i> <span>'+(d.total_entregas||0)+' entregas</span></div>'
                        +'<div class="rp-driver-info-item"><i class="fas fa-star"></i> <span>'+(d.calificacion_promedio||5).toFixed(1)+' ⭐</span></div>'
                    +'</div>'
                    +(d.estado==='suspendido'&&d.motivo_suspension?'<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:12px;color:#991B1B;"><i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> <strong>Suspensión:</strong> '+escapeHtml(d.motivo_suspension)+'</div>':'')
                    +'<div class="rp-driver-actions">'
                        +(d.estado==='activo'?'<button class="rp-driver-btn suspend" onclick="suspenderRepartidor('+d.id+', \''+escapeHtml(d.nombre)+'\')"><i class="fas fa-ban"></i> Suspender</button>':'')
                        +(d.estado==='suspendido'?'<button class="rp-driver-btn activate" onclick="activarRepartidor('+d.id+', \''+escapeHtml(d.nombre)+'\')"><i class="fas fa-check-circle"></i> Reactivar</button>':'')
                        +'<button class="rp-driver-btn delete" onclick="deleteDeliveryDriver('+d.id+', \''+escapeHtml(d.nombre)+'\')"><i class="fas fa-trash"></i></button>'
                    +'</div>'
                +'</div>';
            }).join('');
        }

        function renderDriversTable(drivers){
            const tb=document.getElementById('driversTable');
            if(drivers.length===0){tb.innerHTML='<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-secondary);">No hay repartidores registrados</td></tr>';return;}
            tb.innerHTML=drivers.map(d=>{
                const sc=d.estado==='activo'?'status-delivered':d.estado==='suspendido'?'status-cancelled':'status-pending';
                const sl=d.estado==='activo'?'Activo':d.estado==='suspendido'?'Suspendido':d.estado==='pendiente'?'Pendiente':'Inactivo';
                return '<tr>'
                    +'<td style="font-weight:700;color:var(--primary);">'+d.id+'</td>'
                    +'<td>'+escapeHtml(d.nombre)+' '+escapeHtml(d.apellido||'')+'</td>'
                    +'<td>'+escapeHtml(d.email)+'</td>'
                    +'<td>'+escapeHtml(d.telefono||'-')+'</td>'
                    +'<td>'+escapeHtml(d.tipo_vehiculo||'-')+(d.placa_vehiculo?' ('+escapeHtml(d.placa_vehiculo)+')':'')+'</td>'
                    +'<td><span class="status-badge '+sc+'">'+sl+'</span></td>'
                    +'<td>'+(d.total_entregas||0)+'</td>'
                    +'<td>⭐ '+(d.calificacion_promedio||5).toFixed(1)+'</td>'
                    +'<td><div class="action-buttons">'
                        +(d.estado==='activo'?'<button class="action-btn" title="Suspender" onclick="suspenderRepartidor('+d.id+',\''+escapeHtml(d.nombre)+'\')" style="color:#f59e0b;border:1px solid #f59e0b;background:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;"><i class="fas fa-ban"></i></button>':'')
                        +(d.estado==='suspendido'?'<button class="action-btn" title="Reactivar" onclick="activarRepartidor('+d.id+',\''+escapeHtml(d.nombre)+'\')" style="color:#10b981;border:1px solid #10b981;background:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;"><i class="fas fa-check-circle"></i></button>':'')
                        +'<button class="action-btn action-delete" title="Eliminar" onclick="deleteDeliveryDriver('+d.id+',\''+escapeHtml(d.nombre)+'\')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>'
                    +'</div></td>'
                +'</tr>';
            }).join('');
        }

        function updateMetrics(drivers){
            document.getElementById('totalRepartidores').textContent=drivers.length;
            document.getElementById('repartidoresActivos').textContent=drivers.filter(d=>d.estado==='activo').length;
            document.getElementById('totalEntregasRepartidores').textContent=drivers.reduce((s,d)=>s+(d.total_entregas||0),0);
        }

        async function loadAll(){
            try{
                const res=await fetch(APP_URL+'/api/clientes',{headers:{'Accept':'application/json'}});
                const data=await res.json();
                if(Array.isArray(data)){
                    allDrivers=data.filter(u=>(u.rol||'').toLowerCase()==='repartidor');
                    renderDriversGrid(allDrivers.filter(d=>d.estado==='activo'||d.estado==='suspendido'));
                    renderDriversTable(allDrivers);
                    updateMetrics(allDrivers);
                }
            }catch(e){console.error('Error al cargar repartidores:',e);}

            try{
                const sRes=await fetch(APP_URL+'/api/admin/repartidores/estadisticas',{headers:{'Accept':'application/json'}});
                const stats=await sRes.json();
                if(stats.success){document.getElementById('repartidoresPendientes').textContent=stats.pendientes||0;}
            }catch(e){}

            loadSolicitudes();
        }

        async function loadSolicitudes(){
            try{
                const res=await fetch(APP_URL+'/api/admin/repartidores/solicitudes?estado=pendiente',{headers:{'Accept':'application/json'}});
                const data=await res.json();
                renderSolicitudes(data.success?data.solicitudes:[]);
            }catch(e){
                document.getElementById('solicitudesContainer').innerHTML='<div class="rp-empty"><div class="rp-empty-icon" style="background:#FEF2F2;color:#ef4444;"><i class="fas fa-exclamation-circle"></i></div><div class="rp-empty-title">Error al cargar solicitudes</div><div class="rp-empty-msg">Intenta recargar la página</div></div>';
            }
        }

        function aprobarSolicitud(id){
            showObservacionesModal('Observaciones para la aprobación',async(obs)=>{
                try{
                    const res=await fetch(APP_URL+'/api/admin/repartidores/solicitudes/aprobar',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({id:id,observaciones:obs})});
                    const data=await res.json();
                    if(data.success){showToast({title:'Solicitud Aprobada',message:'El repartidor ya puede iniciar sesión. Se asignaron permisos y se creó la notificación.',type:'success'});loadSolicitudes();loadAll();}else{showToast({title:'Error',message:data.message||'No se pudo aprobar',type:'error'});}
                }catch(e){showToast({title:'Error',message:'Error de conexión',type:'error'});}
            });
        }

        function rechazarSolicitud(id){
            showObservacionesModal('Motivo del rechazo',async(obs)=>{
                try{
                    const res=await fetch(APP_URL+'/api/admin/repartidores/solicitudes/rechazar',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({id:id,observaciones:obs,motivo:obs})});
                    const data=await res.json();
                    if(data.success){showToast({title:'Solicitud Rechazada',message:'El usuario ha sido notificado del rechazo.',type:'info'});loadSolicitudes();loadAll();}else{showToast({title:'Error',message:data.message||'No se pudo rechazar',type:'error'});}
                }catch(e){showToast({title:'Error',message:'Error de conexión',type:'error'});}
            });
        }

        function suspenderRepartidor(id,name){
            showSuspendModal(async(motivo)=>{
                try{
                    const res=await fetch(APP_URL+'/api/admin/repartidores/suspender',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({id:id,motivo:motivo})});
                    const data=await res.json();
                    if(data.success){showToast({title:'Repartidor Suspendido',message:name+' ha sido suspendido',type:'success'});loadAll();}else{showToast({title:'Error',message:data.message||'No se pudo suspender',type:'error'});}
                }catch(e){showToast({title:'Error',message:'Error de conexión',type:'error'});}
            });
        }

        function activarRepartidor(id,name){
            showModal('Reactivar Repartidor','<p style="font-size:14px;color:var(--text-secondary);">¿Deseas reactivar al repartidor <strong>'+escapeHtml(name)+'</strong>? Se restaurarán sus permisos y podrá gestionar pedidos nuevamente.</p>',[
                {label:'Cancelar',cls:'rp-btn-reject',id:'modalCancelBtn'},
                {label:'Reactivar',cls:'rp-btn-approve',id:'modalConfirmBtn'}
            ]).querySelector('#modalConfirmBtn').onclick=async function(){
                this.closest('div[style]').parentElement.remove();
                try{
                    const res=await fetch(APP_URL+'/api/admin/repartidores/activar',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({id:id})});
                    const data=await res.json();
                    if(data.success){showToast({title:'Repartidor Activado',message:name+' ha sido reactivado',type:'success'});loadAll();}else{showToast({title:'Error',message:data.message||'No se pudo activar',type:'error'});}
                }catch(e){showToast({title:'Error',message:'Error de conexión',type:'error'});}
            };
        }

        function deleteDeliveryDriver(id,name){
            showModal('Eliminar Repartidor','<p style="font-size:14px;color:var(--text-secondary);">¿Estás seguro de eliminar a <strong>'+escapeHtml(name)+'?</strong> Esta acción no se puede deshacer.</p>',[
                {label:'Cancelar',cls:'rp-btn-reject',id:'modalCancelBtn'},
                {label:'Eliminar',cls:'rp-btn-approve',id:'modalConfirmBtn'}
            ]).querySelector('#modalConfirmBtn').onclick=async function(){
                this.closest('div[style]').parentElement.remove();
                try{
                    await fetch(APP_URL+'/api/clientes/'+id,{method:'DELETE'});
                    showToast({title:'Eliminado',message:'Repartidor eliminado correctamente',type:'success'});
                    loadAll();
                }catch(e){showToast({title:'Error',message:'No se pudo eliminar',type:'error'});}
            };
        }

        document.addEventListener('DOMContentLoaded',()=>loadAll());

        const style=document.createElement('style');
        style.textContent='@keyframes fadeIn{from{opacity:0}to{opacity:1}}@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}';
        document.head.appendChild(style);
    </script>
</body>
</html>
