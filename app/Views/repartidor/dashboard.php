<?php
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
    header('Location: ' . APP_URL . '/repartidor/login');
    exit();
}
$user = $_SESSION['user'];
$estado = $user['estado'] ?? 'activo';
$solicitudData = $solicitud ?? null;
$notificacionesData = $notificaciones ?? [];
$isPending = ($estado === 'pendiente');
$isRejected = ($estado === 'inactivo' && $solicitudData && ($solicitudData['estado'] ?? '') === 'rechazada');
$isSuspended = ($estado === 'suspendido');
$isInactiveNoRejection = ($estado === 'inactivo' && (!$solicitudData || ($solicitudData['estado'] ?? '') !== 'rechazada'));
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
    <style>
        :root {
            --primary: #5E9DE6;
            --primary-dark: #4A8AD4;
            --primary-light: #E8F2FC;
            --success: #22C55E;
            --success-dark: #16A34A;
            --warning: #F59E0B;
            --danger: #EF4444;
            --danger-dark: #DC2626;
            --info: #3B82F6;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --radius: 12px;
            --radius-sm: 8px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
            --transition: all 0.2s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-800); min-height: 100vh; }

        .header-repartidor {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 0 24px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-contenido {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 68px;
            gap: 16px;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-circulo {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-icono { color: #fff; font-size: 18px; }
        .logo-texto h1 { color: #fff; font-size: 18px; font-weight: 800; letter-spacing: -0.5px; }
        .logo-texto p { color: rgba(255,255,255,0.8); font-size: 11px; font-weight: 500; }
        .info-conductor {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.15);
            padding: 6px 14px 6px 6px;
            border-radius: 50px;
        }
        .avatar-conductor {
            width: 34px; height: 34px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }
        .detalles-conductor h3 { color: #fff; font-size: 13px; font-weight: 600; }
        .detalles-conductor p { color: rgba(255,255,255,0.8); font-size: 11px; display: flex; align-items: center; gap: 5px; }
        .punto-verde { width: 6px; height: 6px; background: #4ADE80; border-radius: 50%; display: inline-block; }
        .acciones-header { display: flex; align-items: center; gap: 8px; }
        .btn-header {
            background: rgba(255,255,255,0.15);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-header:hover { background: rgba(255,255,255,0.25); }

        .contenedor-principal {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .grid-estadisticas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .tarjeta-estadistica {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .tarjeta-estadistica::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .tarjeta-estadistica:nth-child(1)::before { background: linear-gradient(90deg, var(--primary), #93C5FD); }
        .tarjeta-estadistica:nth-child(2)::before { background: linear-gradient(90deg, var(--success), #86EFAC); }
        .tarjeta-estadistica:nth-child(3)::before { background: linear-gradient(90deg, var(--warning), #FDE68A); }
        .tarjeta-estadistica:nth-child(4)::before { background: linear-gradient(90deg, #8B5CF6, #C4B5FD); }
        .tarjeta-estadistica:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .icono-estadistica {
            width: 42px; height: 42px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }
        .icono-estadistica.azul { background: var(--primary-light); color: var(--primary); }
        .icono-estadistica.verde { background: #DCFCE7; color: var(--success); }
        .icono-estadistica.amarillo { background: #FEF3C7; color: var(--warning); }
        .icono-estadistica.morado { background: #EDE9FE; color: #8B5CF6; }
        .valor-estadistica { font-size: 28px; font-weight: 800; color: var(--gray-900); margin-bottom: 2px; }
        .etiqueta-estadistica { font-size: 13px; color: var(--gray-500); font-weight: 500; }
        .tendencia { font-size: 11px; font-weight: 600; margin-top: 8px; display: flex; align-items: center; gap: 4px; }
        .tendencia.subida { color: var(--success); }
        .tendencia.bajada { color: var(--warning); }

        .seccion-filtros {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        .filtros-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .filtros-titulo { font-size: 15px; font-weight: 600; color: var(--gray-700); display: flex; align-items: center; gap: 8px; }
        .contador-pedidos { font-size: 13px; color: var(--gray-500); }
        .filtros-grid { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .grupo-filtro { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 160px; }
        .grupo-filtro label { font-size: 12px; font-weight: 600; color: var(--gray-600); display: flex; align-items: center; gap: 5px; }
        .select-filtro {
            padding: 9px 12px;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: var(--gray-700);
            background: #fff;
            transition: var(--transition);
            width: 100%;
        }
        .select-filtro:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(94,157,230,0.15); }
        .btn-filtro {
            padding: 9px 18px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            font-family: 'Inter', sans-serif;
        }
        .btn-filtro:hover { background: var(--primary-dark); }

        .map-placeholder {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            height: 200px;
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--gray-400);
            position: relative;
            overflow: hidden;
        }
        .map-placeholder::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 20px,
                var(--gray-100) 20px,
                var(--gray-100) 21px
            );
            opacity: 0.5;
        }
        .map-placeholder i { font-size: 32px; position: relative; z-index: 1; }
        .map-placeholder span { font-size: 13px; font-weight: 500; position: relative; z-index: 1; }

        .grid-secciones {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .tarjeta-seccion {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .seccion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
        }
        .seccion-titulo { font-size: 15px; font-weight: 600; color: var(--gray-700); display: flex; align-items: center; gap: 10px; }
        .seccion-icono {
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .seccion-badge {
            background: var(--primary);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            min-width: 24px;
            text-align: center;
        }
        .lista-pedidos {
            padding: 12px;
            max-height: 520px;
            overflow-y: auto;
        }
        .lista-pedidos::-webkit-scrollbar { width: 4px; }
        .lista-pedidos::-webkit-scrollbar-track { background: transparent; }
        .lista-pedidos::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 4px; }

        .pedido-card {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 14px;
            margin-bottom: 10px;
            transition: var(--transition);
        }
        .pedido-card:hover { border-color: var(--primary); box-shadow: 0 2px 8px rgba(94,157,230,0.1); }
        .pedido-card:last-child { margin-bottom: 0; }
        .pedido-card.alta-prioridad { border-left: 3px solid var(--danger); }
        .pedido-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .pedido-id { font-size: 14px; font-weight: 700; color: var(--gray-800); display: flex; align-items: center; gap: 6px; }
        .pedido-urgente {
            font-size: 10px;
            font-weight: 700;
            color: var(--danger);
            background: #FEE2E2;
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .pedido-estado {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .estado-pendiente { background: #FEF3C7; color: #92400E; }
        .estado-listo { background: #DCFCE7; color: #166534; }
        .estado-asignado { background: var(--primary-light); color: var(--primary-dark); }
        .estado-en-camino { background: #E0F2FE; color: #0369A1; }
        .estado-entregado { background: #DCFCE7; color: #166534; }
        .estado-cancelado { background: #FEE2E2; color: #991B1B; }

        .pedido-detalles { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 16px; margin-bottom: 10px; }
        .detalle-item { display: flex; flex-direction: column; gap: 1px; }
        .detalle-label { font-size: 10px; color: var(--gray-400); text-transform: uppercase; font-weight: 600; letter-spacing: 0.3px; display: flex; align-items: center; gap: 4px; }
        .detalle-valor { font-size: 13px; color: var(--gray-700); font-weight: 500; }

        .pedido-productos { margin-bottom: 10px; }
        .productos-titulo { font-size: 11px; color: var(--gray-400); font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 5px; display: flex; align-items: center; gap: 4px; }
        .productos-lista { display: flex; flex-wrap: wrap; gap: 4px; }
        .producto-tag {
            font-size: 11px;
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .pedido-total { font-size: 14px; font-weight: 700; color: var(--gray-800); margin-bottom: 10px; padding-top: 8px; border-top: 1px solid var(--gray-100); }

        .pedido-acciones { display: flex; gap: 8px; }
        .btn-accion {
            flex: 1;
            padding: 9px 12px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: 'Inter', sans-serif;
        }
        .btn-accion:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-aceptar { background: var(--primary); color: #fff; }
        .btn-aceptar:hover:not(:disabled) { background: var(--primary-dark); }
        .btn-recoger { background: var(--success); color: #fff; }
        .btn-recoger:hover:not(:disabled) { background: var(--success-dark); }
        .btn-entregar { background: var(--success); color: #fff; }
        .btn-entregar:hover:not(:disabled) { background: var(--success-dark); }
        .btn-cancelar { background: var(--gray-200); color: var(--gray-600); }
        .btn-cancelar:hover:not(:disabled) { background: #FEE2E2; color: var(--danger); }
        .btn-contactar { background: var(--gray-200); color: var(--gray-600); }
        .btn-contactar:hover:not(:disabled) { background: var(--primary-light); color: var(--primary); }
        .btn-ver-mapa { background: var(--gray-200); color: var(--gray-600); }
        .btn-ver-mapa:hover:not(:disabled) { background: var(--primary-light); color: var(--primary); }

        .estado-vacio {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-400);
        }
        .icono-vacio { font-size: 36px; margin-bottom: 10px; opacity: 0.5; }
        .titulo-vacio { font-size: 14px; font-weight: 600; color: var(--gray-500); margin-bottom: 4px; }
        .mensaje-vacio { font-size: 12px; color: var(--gray-400); }

        .tabla-contenedor { overflow-x: auto; }
        .tabla-historial { width: 100%; border-collapse: collapse; }
        .tabla-historial th {
            text-align: left;
            padding: 10px 16px;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        .tabla-historial td {
            padding: 10px 16px;
            font-size: 13px;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }
        .tabla-historial tr:hover td { background: var(--gray-50); }
        .badge-ganancia {
            background: #DCFCE7;
            color: var(--success-dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        .estrellas { color: var(--warning); font-size: 12px; display: flex; align-items: center; gap: 2px; }
        .estrellas span { color: var(--gray-400); margin-left: 4px; }

        .panel-notificaciones {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }
        .campana {
            width: 48px; height: 48px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
        }
        .campana:hover { transform: scale(1.1); background: var(--primary-dark); }
        .badge-notificaciones {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--danger);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            width: 18px; height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }
        .badge-notificaciones:empty, .badge-notificaciones[data-count="0"] { display: none; }

        .toast-container { position: fixed; top: 80px; right: 24px; z-index: 10000; display: flex; flex-direction: column; gap: 8px; }
        .toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            background: #fff;
            box-shadow: var(--shadow-lg);
            border-left: 4px solid var(--gray-300);
            min-width: 300px;
            max-width: 400px;
            transform: translateX(120%);
            transition: transform 0.3s ease;
        }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left-color: var(--success); }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }
        .toast.info { border-left-color: var(--info); }
        .toast-icon { font-size: 18px; flex-shrink: 0; padding-top: 1px; }
        .toast.success .toast-icon { color: var(--success); }
        .toast.error .toast-icon { color: var(--danger); }
        .toast.warning .toast-icon { color: var(--warning); }
        .toast.info .toast-icon { color: var(--info); }
        .toast-content { flex: 1; }
        .toast-title { font-size: 13px; font-weight: 700; color: var(--gray-800); margin-bottom: 2px; }
        .toast-message { font-size: 12px; color: var(--gray-500); }
        .toast-close {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 0;
            flex-shrink: 0;
        }

        @media (max-width: 1024px) {
            .grid-secciones { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .grid-estadisticas { grid-template-columns: repeat(2, 1fr); }
            .header-contenido { flex-wrap: wrap; height: auto; padding: 12px 0; gap: 10px; }
            .info-conductor { display: none; }
            .contenedor-principal { padding: 16px; }
            .filtros-grid { flex-direction: column; }
            .grupo-filtro { min-width: 100%; }
        }
    </style>
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
                    <p><span class="punto-verde"></span> En línea • <?= htmlspecialchars($user['tipo_vehiculo'] ?? 'Repartidor') ?></p>
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

    <?php if ($isPending || $isRejected || $isSuspended || $isInactiveNoRejection): ?>
    <style>
        @keyframes fadeSlideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
        .st-wrap{max-width:640px;margin:48px auto;padding:0 20px;}
        .st-card{background:#fff;border-radius:20px;border:1px solid var(--gray-200);box-shadow:0 12px 40px rgba(0,0,0,.08);overflow:hidden;animation:fadeSlideUp .5s ease;}
        .st-hero{padding:40px 32px;text-align:center;position:relative;}
        .st-hero::after{content:'';position:absolute;bottom:0;left:24px;right:24px;height:1px;background:var(--gray-200);}
        .st-icon{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:36px;position:relative;}
        .st-icon::before{content:'';position:absolute;inset:-6px;border-radius:50%;border:2px solid currentColor;opacity:.15;}
        .st-icon.pending{background:#FFFBEB;color:#f59e0b;}
        .st-icon.pending::before{border-color:#f59e0b;}
        .st-icon.rejected{background:#FEF2F2;color:#ef4444;}
        .st-icon.rejected::before{border-color:#ef4444;}
        .st-icon.suspended{background:#EFF6FF;color:#3b82f6;}
        .st-icon.suspended::before{border-color:#3b82f6;}
        .st-icon.inactive{background:#F3F4F6;color:#6b7280;}
        .st-icon.inactive::before{border-color:#6b7280;}
        .st-icon i{color:#fff;font-size:36px;}
        .st-title{font-size:24px;font-weight:800;margin-bottom:6px;letter-spacing:-.5px;}
        .st-title.pending{color:#92400E;}.st-title.rejected{color:#991B1B;}.st-title.suspended{color:#1E3A8A;}.st-title.inactive{color:#374151;}
        .st-subtitle{font-size:15px;color:var(--gray-500);font-weight:500;}
        .st-body{padding:28px 32px;}

        .st-timeline{display:flex;align-items:center;gap:0;margin-bottom:24px;padding:16px 0;}
        .st-tl-step{display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;flex:1;}
        .st-tl-dot{width:14px;height:14px;border-radius:50%;background:var(--gray-200);border:3px solid var(--gray-300);position:relative;z-index:1;transition:all .3s;}
        .st-tl-dot.done{background:#10b981;border-color:#10b981;box-shadow:0 0 0 4px rgba(16,185,129,.15);}
        .st-tl-dot.active{background:#f59e0b;border-color:#f59e0b;box-shadow:0 0 0 4px rgba(245,158,11,.2);animation:pulse 2s ease infinite;}
        .st-tl-dot.error{background:#ef4444;border-color:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.15);}
        .st-tl-label{font-size:11px;font-weight:600;color:var(--gray-400);text-align:center;max-width:80px;}
        .st-tl-label.done{color:#10b981;}.st-tl-label.active{color:#f59e0b;font-weight:700;}.st-tl-label.error{color:#ef4444;}
        .st-tl-line{flex:1;height:3px;background:var(--gray-200);margin:0 -8px;margin-bottom:24px;}
        .st-tl-line.done{background:linear-gradient(90deg,#10b981,#10b981);}

        .st-alert{border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;}
        .st-alert.info{background:#FFFBEB;border:1px solid #FDE68A;}
        .st-alert.danger{background:#FEF2F2;border:1px solid #FECACA;}
        .st-alert.info2{background:#EFF6FF;border:1px solid #BFDBFE;}
        .st-alert.gray{background:#F3F4F6;border:1px solid var(--gray-200);}
        .st-alert i{font-size:18px;margin-top:2px;flex-shrink:0;}
        .st-alert.info i{color:#f59e0b;}.st-alert.danger i{color:#ef4444;}.st-alert.info2 i{color:#3b82f6;}.st-alert.gray i{color:#6b7280;}
        .st-alert-title{font-size:14px;font-weight:700;margin-bottom:3px;}
        .st-alert.info .st-alert-title{color:#92400E;}.st-alert.danger .st-alert-title{color:#991B1B;}.st-alert.info2 .st-alert-title{color:#1E3A8A;}.st-alert.gray .st-alert-title{color:#374151;}
        .st-alert-msg{font-size:13px;line-height:1.5;}
        .st-alert.info .st-alert-msg{color:#A16207;}.st-alert.danger .st-alert-msg{color:#B91C1C;}.st-alert.info2 .st-alert-msg{color:#1E40AF;}.st-alert.gray .st-alert-msg{color:#6B7280;}

        .st-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;}
        .st-info-item{padding:12px 16px;background:var(--gray-50);border-radius:10px;border:1px solid var(--gray-100);}
        .st-info-label{font-size:10px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.5px;}
        .st-info-value{font-size:14px;font-weight:600;color:var(--gray-700);margin-top:3px;}

        .st-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:8px;}
        .st-btn{padding:11px 28px;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:8px;border:none;text-decoration:none;font-family:'Inter',sans-serif;}
        .st-btn:hover{transform:translateY(-2px);}
        .st-btn.primary{background:var(--primary);color:#fff;box-shadow:0 4px 16px rgba(94,157,230,.3);}
        .st-btn.primary:hover{background:var(--primary-dark);box-shadow:0 6px 20px rgba(94,157,230,.4);}
        .st-btn.outline{background:#fff;color:var(--gray-600);border:2px solid var(--gray-200);}
        .st-btn.outline:hover{border-color:var(--primary);color:var(--primary);background:var(--accent-light);}
        .st-btn.success{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 4px 16px rgba(16,185,129,.3);}
        .st-btn.success:hover{box-shadow:0 6px 20px rgba(16,185,129,.4);}

        @media(max-width:640px){.st-body{padding:24px 20px;}.st-hero{padding:32px 20px;}.st-info-grid{grid-template-columns:1fr;}.st-title{font-size:20px;}}
    </style>
    <div class="contenedor-principal">
        <div class="st-wrap">
            <?php if ($isPending): ?>
            <div class="st-card">
                <div class="st-hero">
                    <div class="st-icon pending"><i class="fas fa-clock"></i></div>
                    <h2 class="st-title pending">Solicitud Pendiente de Revisión</h2>
                    <p class="st-subtitle">Tu solicitud está siendo procesada</p>
                </div>
                <div class="st-body">
                    <div class="st-timeline">
                        <div class="st-tl-step"><div class="st-tl-dot done"></div><div class="st-tl-label done">Registro</div></div>
                        <div class="st-tl-line done"></div>
                        <div class="st-tl-step"><div class="st-tl-dot active"></div><div class="st-tl-label active">En Revisión</div></div>
                        <div class="st-tl-line"></div>
                        <div class="st-tl-step"><div class="st-tl-dot"></div><div class="st-tl-label">Aprobado</div></div>
                    </div>

                    <div class="st-alert info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <div class="st-alert-title">¿Qué sigue?</div>
                            <div class="st-alert-msg">Un administrador revisará tu documentación y datos personales. Recibirás una notificación cuando tu solicitud sea aprobada o rechazada.</div>
                        </div>
                    </div>

                    <?php if ($solicitudData): ?>
                    <div class="st-info-grid">
                        <div class="st-info-item">
                            <div class="st-info-label">Fecha de solicitud</div>
                            <div class="st-info-value"><?= date('d/m/Y H:i', strtotime($solicitudData['fecha_solicitud'])) ?></div>
                        </div>
                        <div class="st-info-item">
                            <div class="st-info-label">Estado</div>
                            <div class="st-info-value" style="color:#D97706;"><i class="fas fa-circle" style="font-size:8px;margin-right:4px;"></i>Pendiente</div>
                        </div>
                        <div class="st-info-item">
                            <div class="st-info-label">Vehículo</div>
                            <div class="st-info-value"><?= htmlspecialchars(ucfirst($solicitudData['tipo_vehiculo'] ?? '')) ?></div>
                        </div>
                        <div class="st-info-item">
                            <div class="st-info-label">Placa</div>
                            <div class="st-info-value"><?= htmlspecialchars($solicitudData['placa_vehiculo'] ?? '') ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="st-actions">
                        <a href="<?= APP_URL ?>/" class="st-btn primary"><i class="fas fa-store"></i> Volver a la Tienda</a>
                    </div>
                </div>
            </div>

            <?php elseif ($isRejected): ?>
            <div class="st-card">
                <div class="st-hero">
                    <div class="st-icon rejected"><i class="fas fa-times-circle"></i></div>
                    <h2 class="st-title rejected">Solicitud Rechazada</h2>
                    <p class="st-subtitle">Tu solicitud para ser repartidor no fue aprobada</p>
                </div>
                <div class="st-body">
                    <div class="st-timeline">
                        <div class="st-tl-step"><div class="st-tl-dot done"></div><div class="st-tl-label done">Registro</div></div>
                        <div class="st-tl-line done"></div>
                        <div class="st-tl-step"><div class="st-tl-dot done"></div><div class="st-tl-label done">En Revisión</div></div>
                        <div class="st-tl-line"></div>
                        <div class="st-tl-step"><div class="st-tl-dot error"></div><div class="st-tl-label error">Rechazado</div></div>
                    </div>

                    <?php if (!empty($solicitudData['motivo_rechazo'])): ?>
                    <div class="st-alert danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <div class="st-alert-title">Motivo del rechazo</div>
                            <div class="st-alert-msg"><?= nl2br(htmlspecialchars($solicitudData['motivo_rechazo'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($solicitudData['observaciones'])): ?>
                    <div class="st-alert danger">
                        <i class="fas fa-comment-dots"></i>
                        <div>
                            <div class="st-alert-title">Observaciones del administrador</div>
                            <div class="st-alert-msg"><?= nl2br(htmlspecialchars($solicitudData['observaciones'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="st-actions">
                        <a href="<?= APP_URL ?>/repartidor/registro" class="st-btn success"><i class="fas fa-redo"></i> Enviar nueva solicitud</a>
                        <a href="<?= APP_URL ?>/" class="st-btn outline"><i class="fas fa-store"></i> Volver a la Tienda</a>
                    </div>
                </div>
            </div>

            <?php elseif ($isSuspended): ?>
            <div class="st-card">
                <div class="st-hero">
                    <div class="st-icon suspended"><i class="fas fa-ban"></i></div>
                    <h2 class="st-title suspended">Cuenta Suspendida</h2>
                    <p class="st-subtitle">Tu acceso como repartidor ha sido suspendido</p>
                </div>
                <div class="st-body">
                    <div class="st-timeline">
                        <div class="st-tl-step"><div class="st-tl-dot done"></div><div class="st-tl-label done">Registro</div></div>
                        <div class="st-tl-line done"></div>
                        <div class="st-tl-step"><div class="st-tl-dot done"></div><div class="st-tl-label done">Aprobado</div></div>
                        <div class="st-tl-line"></div>
                        <div class="st-tl-step"><div class="st-tl-dot error"></div><div class="st-tl-label error">Suspendido</div></div>
                    </div>

                    <?php if (!empty($user['motivo_suspension'])): ?>
                    <div class="st-alert info2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <div class="st-alert-title">Motivo de suspensión</div>
                            <div class="st-alert-msg"><?= nl2br(htmlspecialchars($user['motivo_suspension'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="st-alert info2">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <div class="st-alert-title">Información</div>
                            <div class="st-alert-msg">No puedes gestionar pedidos mientras tu cuenta esté suspendida. Contacta al administrador para más información.</div>
                        </div>
                    </div>

                    <div class="st-actions">
                        <a href="<?= APP_URL ?>/" class="st-btn primary"><i class="fas fa-store"></i> Volver a la Tienda</a>
                    </div>
                </div>
            </div>

            <?php elseif ($isInactiveNoRejection): ?>
            <div class="st-card">
                <div class="st-hero">
                    <div class="st-icon inactive"><i class="fas fa-user-slash"></i></div>
                    <h2 class="st-title inactive">Cuenta Inactiva</h2>
                    <p class="st-subtitle">Tu cuenta no está activa actualmente</p>
                </div>
                <div class="st-body">
                    <div class="st-alert gray">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <div class="st-alert-title">Contacta al administrador</div>
                            <div class="st-alert-msg">Si crees que esto es un error, ponte en contacto con el equipo de soporte de ANGELOW.</div>
                        </div>
                    </div>
                    <div class="st-actions">
                        <a href="<?= APP_URL ?>/" class="st-btn primary"><i class="fas fa-store"></i> Volver a la Tienda</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($notificacionesData)): ?>
            <div style="margin-top:24px;background:#fff;border-radius:16px;border:1px solid var(--gray-200);box-shadow:0 4px 16px rgba(0,0,0,.04);overflow:hidden;animation:fadeSlideUp .6s ease;">
                <div style="padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:14px;"><i class="fas fa-bell"></i></div>
                    <span style="font-size:15px;font-weight:700;color:var(--gray-700);">Notificaciones</span>
                </div>
                <div style="max-height:300px;overflow-y:auto;">
                    <?php foreach ($notificacionesData as $notif): ?>
                    <div style="padding:14px 20px;border-bottom:1px solid var(--gray-100);<?= $notif['leida'] ? '' : 'background:#F0F9FF;' ?>transition:background .2s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background='<?= $notif['leida'] ? '' : '#F0F9FF' ?>'">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <span style="font-size:13px;font-weight:600;color:var(--gray-800);"><?= htmlspecialchars($notif['titulo']) ?></span>
                            <?php if (!$notif['leida']): ?><span style="background:var(--primary);color:#fff;font-size:9px;padding:2px 8px;border-radius:10px;font-weight:700;letter-spacing:.3px;">NUEVA</span><?php endif; ?>
                        </div>
                        <div style="font-size:12px;color:var(--gray-500);line-height:1.5;"><?= htmlspecialchars($notif['mensaje']) ?></div>
                        <div style="font-size:11px;color:var(--gray-400);margin-top:6px;display:flex;align-items:center;gap:4px;"><i class="fas fa-clock" style="font-size:10px;"></i> <?= date('d/m/Y H:i', strtotime($notif['fecha_envio'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
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
                <div class="icono-estadistica morado">
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
                        <option value="pendiente">Pendiente</option>
                        <option value="listo">Listo para recoger</option>
                        <option value="asignado">Asignado</option>
                        <option value="en_camino">En camino</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <button class="btn-filtro" onclick="applyFilters()">
                    <i class="fas fa-check"></i> Aplicar Filtros
                </button>
            </div>
        </div>

        <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <span>Mapa de entregas (próximamente)</span>
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

        <div class="tarjeta-seccion" style="margin-bottom: 24px;">
            <div class="seccion-header">
                <div class="seccion-titulo">
                    <div class="seccion-icono">
                        <i class="fas fa-history"></i>
                    </div>
                    Historial de Entregas
                </div>
            </div>
            <div class="tabla-contenedor">
                <table class="tabla-historial">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Dirección</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="deliveriesHistory">
                        <tr>
                            <td colspan="5" class="estado-vacio" style="text-align:center; padding:40px;">
                                Cargando historial...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <div class="panel-notificaciones">
        <div class="campana" onclick="verNotificaciones()">
            <i class="fas fa-bell"></i>
            <span class="badge-notificaciones" id="notificationCount">0</span>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        const userData = <?= json_encode($user) ?>;
        const userEstado = userData.estado || 'activo';
        let token = localStorage.getItem('repartidor_token') || '';

        if (userEstado !== 'activo') {
            document.addEventListener('DOMContentLoaded', () => {
                const bell = document.querySelector('.campana');
                if (bell) bell.style.display = 'none';
            });
        }

        const datosApp = {
            pedidosDisponibles: [],
            pedidosActivos: [],
            historialEntregas: [],
            todosLosPedidos: []
        };

        const STATUS_CONFIG = {
            pendiente:    { text: 'Pendiente',       clase: 'estado-pendiente',    icono: 'fa-clock' },
            listo:        { text: 'Listo',            clase: 'estado-listo',        icono: 'fa-check-circle' },
            asignado:     { text: 'Asignado',         clase: 'estado-asignado',     icono: 'fa-hand-paper' },
            en_camino:    { text: 'En camino',        clase: 'estado-en-camino',    icono: 'fa-truck' },
            entregado:    { text: 'Entregado',        clase: 'estado-entregado',    icono: 'fa-check-double' },
            cancelado:    { text: 'Cancelado',        clase: 'estado-cancelado',    icono: 'fa-times-circle' }
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
                <button class="toast-close" aria-label="Cerrar notificación">&times;</button>
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
            return '$' + Number(monto || 0).toLocaleString('es-CO');
        }

        function htmlspecialchars(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function obtenerIniciales(nombre) {
            if (!nombre) return '?';
            return nombre.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }

        function getStatusBadge(status) {
            const cfg = STATUS_CONFIG[status] || { text: status, clase: '', icono: 'fa-question' };
            return `<span class="pedido-estado ${cfg.clase}"><i class="fas ${cfg.icono}"></i> ${cfg.text}</span>`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch {
                return dateStr;
            }
        }

        async function loadStats() {
            if (!token) return;
            try {
                const res = await fetch(APP_URL + '/api/repartidor/dashboard/stats', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.today_earnings !== undefined) {
                    document.getElementById('statEarnings').textContent = formatearDinero(data.today_earnings);
                }
                if (data.today_orders !== undefined) {
                    document.getElementById('statOrders').textContent = data.today_orders;
                } else if (data.total_deliveries !== undefined) {
                    document.getElementById('statOrders').textContent = data.total_deliveries;
                }
                if (data.pending_orders !== undefined) {
                    document.getElementById('statPending').textContent = data.pending_orders;
                }
                if (data.in_transit !== undefined) {
                    const pendingEl = document.getElementById('statPending');
                    pendingEl.textContent = data.in_transit;
                }
                if (data.rating !== undefined) {
                    document.getElementById('statRating').textContent = Number(data.rating).toFixed(1);
                }
            } catch (e) {
                console.warn('No se pudieron cargar estadísticas:', e);
            }
        }

        async function loadOrders() {
            if (!token) return;
            try {
                const res = await fetch(APP_URL + '/api/repartidor/pedidos', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) return;
                const data = await res.json();
                const allOrders = Array.isArray(data) ? data : [];
                datosApp.todosLosPedidos = allOrders;

                const availableStatuses = ['pendiente', 'listo'];
                const activeStatuses = ['asignado', 'en_camino'];

                datosApp.pedidosDisponibles = allOrders.filter(p => availableStatuses.includes(p.status || p.estado));
                datosApp.pedidosActivos = allOrders.filter(p => activeStatuses.includes(p.status || p.estado));
                datosApp.historialEntregas = allOrders.filter(p => (p.status || p.estado) === 'entregado').slice(-10).reverse();

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

            let pedidos = [...datosApp.pedidosDisponibles];

            const search = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;

            if (search) {
                pedidos = pedidos.filter(p =>
                    (p.client_name || '').toLowerCase().includes(search) ||
                    (p.delivery_address || '').toLowerCase().includes(search) ||
                    (p.numero_pedido || '').toLowerCase().includes(search)
                );
            }
            if (statusFilter !== 'all') {
                pedidos = pedidos.filter(p => (p.status || p.estado) === statusFilter);
            }

            badge.textContent = datosApp.pedidosDisponibles.length;
            countEl.textContent = pedidos.length;

            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="estado-vacio">
                        <div class="icono-vacio"><i class="fas fa-inbox"></i></div>
                        <div class="titulo-vacio">No hay pedidos disponibles</div>
                        <div class="mensaje-vacio">Los nuevos pedidos aparecerán aquí automáticamente</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = pedidos.map(p => {
                const status = p.status || p.estado;
                const esListo = status === 'listo';
                const esAlta = (p.prioridad || 'normal') === 'high';

                const itemNames = (p.items || []).map(i => i.product_name || i.nombre || '').filter(Boolean);
                const displayItems = itemNames.length > 0
                    ? itemNames.map(n => `<span class="producto-tag">${htmlspecialchars(n)}</span>`).join('')
                    : '<span class="producto-tag">Sin detalles</span>';

                return `
                <div class="pedido-card ${esAlta ? 'alta-prioridad' : ''}">
                    <div class="pedido-header">
                        <div class="pedido-id">
                            <i class="fas fa-hashtag"></i> ${htmlspecialchars(p.numero_pedido || String(p.id))}
                            ${esAlta ? '<span class="pedido-urgente">URGENTE</span>' : ''}
                        </div>
                        ${getStatusBadge(status)}
                    </div>

                    <div class="pedido-detalles">
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-user"></i> Cliente</div>
                            <div class="detalle-valor">${htmlspecialchars(p.client_name || 'Cliente')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-phone"></i> Teléfono</div>
                            <div class="detalle-valor">${htmlspecialchars(p.client_phone || '-')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                            <div class="detalle-valor">${htmlspecialchars(p.delivery_address || '-')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-clock"></i> Creado</div>
                            <div class="detalle-valor">${formatDate(p.created_at)}</div>
                        </div>
                    </div>

                    <div class="pedido-productos">
                        <div class="productos-titulo">
                            <i class="fas fa-box"></i> Productos (${itemNames.length}):
                        </div>
                        <div class="productos-lista">${displayItems}</div>
                    </div>

                    <div class="pedido-total">Total: ${formatearDinero(p.total)}</div>

                    ${p.notes ? `<div style="font-size:12px; color: var(--gray-500); margin-bottom:10px;"><i class="fas fa-sticky-note"></i> ${htmlspecialchars(p.notes)}</div>` : ''}

                    <div class="pedido-acciones">
                        <button class="btn-accion btn-aceptar" onclick="aceptarPedido(${p.id})">
                            <i class="fas fa-check"></i> Aceptar Pedido
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
                        <div class="mensaje-vacio">Acepta un pedido disponible para comenzar</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = datosApp.pedidosActivos.map(p => {
                const status = p.status || p.estado;

                let acciones = '';
                if (status === 'asignado') {
                    acciones = `
                        <button class="btn-accion btn-recoger" onclick="recogerPedido(${p.id})">
                            <i class="fas fa-hand-holding"></i> Recoger
                        </button>
                        <button class="btn-accion btn-cancelar" onclick="cancelarPedido(${p.id})">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    `;
                } else if (status === 'en_camino') {
                    acciones = `
                        <button class="btn-accion btn-entregar" onclick="entregarPedido(${p.id})">
                            <i class="fas fa-check-double"></i> Entregar
                        </button>
                        <button class="btn-accion btn-contactar" onclick="contactarCliente('${htmlspecialchars(p.client_phone || '')}')">
                            <i class="fas fa-phone"></i> Llamar
                        </button>
                    `;
                }

                const itemNames = (p.items || []).map(i => i.product_name || i.nombre || '').filter(Boolean);
                const displayItems = itemNames.length > 0
                    ? itemNames.map(n => `<span class="producto-tag">${htmlspecialchars(n)}</span>`).join('')
                    : '';

                return `
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-id"><i class="fas fa-hashtag"></i> ${htmlspecialchars(p.numero_pedido || String(p.id))}</div>
                        ${getStatusBadge(status)}
                    </div>

                    <div class="pedido-detalles">
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-user"></i> Cliente</div>
                            <div class="detalle-valor">${htmlspecialchars(p.client_name || 'Cliente')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-phone"></i> Teléfono</div>
                            <div class="detalle-valor">${htmlspecialchars(p.client_phone || '-')}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                            <div class="detalle-valor">${htmlspecialchars(p.delivery_address || '-')}</div>
                        </div>
                    </div>

                    ${displayItems ? `<div class="pedido-productos"><div class="productos-lista">${displayItems}</div></div>` : ''}

                    <div class="pedido-total">Total: ${formatearDinero(p.total)}</div>

                    <div class="pedido-acciones">
                        ${acciones}
                    </div>
                </div>
            `}).join('');
        }

        function renderHistorial() {
            const tbody = document.getElementById('deliveriesHistory');
            if (datosApp.historialEntregas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--gray-400);">
                            <div class="estado-vacio">
                                <div class="icono-vacio"><i class="fas fa-history"></i></div>
                                <div class="titulo-vacio">No hay entregas registradas</div>
                                <div class="mensaje-vacio">Tus entregas completadas aparecerán aquí</div>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = datosApp.historialEntregas.map(h => `
                <tr>
                    <td><strong>${htmlspecialchars(h.numero_pedido || '#' + h.id)}</strong></td>
                    <td>${htmlspecialchars(h.client_name || '-')}</td>
                    <td>${formatDate(h.created_at)}</td>
                    <td>${htmlspecialchars(h.delivery_address || '-')}</td>
                    <td><span class="badge-ganancia">${formatearDinero(h.total)}</span></td>
                </tr>
            `).join('');
        }

        async function updateOrderStatus(id, newStatus, successTitle, successMsg) {
            if (!token) return;
            try {
                const res = await fetch(APP_URL + '/api/repartidor/pedidos/' + id + '/estado', {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await res.json();
                if (data.success) {
                    mostrarToast({ titulo: successTitle, mensaje: successMsg, tipo: 'success' });
                    await loadOrders();
                    await loadStats();
                } else {
                    mostrarToast({ titulo: 'Error', mensaje: data.message || 'No se pudo actualizar', tipo: 'error' });
                }
            } catch (e) {
                mostrarToast({ titulo: 'Error', mensaje: 'Error de conexión', tipo: 'error' });
            }
        }

        async function aceptarPedido(id) {
            await updateOrderStatus(id, 'asignado', '✅ Pedido Aceptado', `Pedido #${id} asignado a tu ruta`);
        }

        async function recogerPedido(id) {
            await updateOrderStatus(id, 'en_camino', '📦 Pedido Recogido', `Pedido #${id} en camino al cliente`);
        }

        async function entregarPedido(id) {
            await updateOrderStatus(id, 'entregado', '✅ Entrega Completada', `Pedido #${id} entregado exitosamente`);
        }

        async function cancelarPedido(id) {
            await updateOrderStatus(id, 'cancelado', '❌ Pedido Cancelado', `Pedido #${id} ha sido cancelado`);
        }

        function contactarCliente(telefono) {
            if (telefono && telefono !== '-') {
                window.location.href = `tel:${telefono}`;
            } else {
                mostrarToast({ titulo: 'Sin teléfono', mensaje: 'No hay número de teléfono disponible', tipo: 'warning' });
            }
        }

        function applyFilters() {
            renderPedidosDisponibles();
            mostrarToast({ titulo: 'Filtros aplicados', mensaje: 'Lista actualizada', tipo: 'info', duracion: 2000 });
        }

        async function verNotificaciones() {
            if (!token) return;
            try {
                const res = await fetch(APP_URL + '/api/repartidor/notificaciones', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) {
                    mostrarToast({ titulo: 'Notificaciones', mensaje: 'No hay notificaciones nuevas', tipo: 'info' });
                    return;
                }
                const data = await res.json();
                if (data.success && data.notificaciones && data.notificaciones.length > 0) {
                    data.notificaciones.forEach(n => {
                        mostrarToast({ titulo: n.titulo, mensaje: n.mensaje, tipo: n.tipo === 'aprobacion_repartidor' ? 'success' : n.tipo === 'rechazo_repartidor' ? 'error' : 'info', duracion: 6000 });
                    });
                } else {
                    mostrarToast({ titulo: 'Notificaciones', mensaje: 'No hay notificaciones nuevas', tipo: 'info' });
                }
            } catch (e) {
                mostrarToast({ titulo: 'Notificaciones', mensaje: 'No hay notificaciones nuevas', tipo: 'info' });
            }
        }

        async function logout() {
            try {
                await fetch(APP_URL + '/repartidor/logout', {
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

        let autoRefreshInterval = null;

        document.addEventListener('DOMContentLoaded', async () => {
            if (userEstado !== 'activo') return;

            if (token) {
                await loadStats();
                await loadOrders();
                autoRefreshInterval = setInterval(async () => {
                    if (localStorage.getItem('repartidor_token')) {
                        token = localStorage.getItem('repartidor_token');
                        await loadStats();
                        await loadOrders();
                    } else {
                        clearInterval(autoRefreshInterval);
                        window.location.href = APP_URL + '/repartidor/login';
                    }
                }, 30000);
            } else {
                window.location.href = APP_URL + '/repartidor/login';
            }
        });
    </script>
</body>
</html>
