<?php
/*
 |========================================================================
 | VISTA: repartidor/perfil.php
 |------------------------------------------------------------------------
 | QUÉ MUESTRA: Perfil del repartidor con tarjeta de identidad (avatar,
 | estado y estadísticas), información personal, datos del vehículo y
 | licencia, y los documentos cargados.
 |
 | ARCHIVOS EXTERNOS: Font Awesome, fuentes Google, tokens.css y estilos
 | propios en un <style> del <head>.
 |
 | JS QUE LA CONTROLA: <script> inline al final (carga del perfil vía
 | API, refresco de token y gestión de documentos).
 |========================================================================
*/
if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
    header('Location: ' . APP_URL . '/repartidor/login');
    exit();
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>ANGELOW | Mi Perfil</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/tokens.css">
    <style>
        :root {
            --primary: #5E9DE6;
            --primary-dark: #4A7FC4;
            --primary-light: #E8F2FC;
            --success: #10b981;
            --success-dark: #0d9668;
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

        /* Header */
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
            font-family: 'Inter', sans-serif;
        }
        .btn-header:hover { background: rgba(255,255,255,0.25); }

        /* Back button */
        .btn-back-header {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: var(--primary);
            font-weight: 700;
            font-size: 14px;
            padding: 8px 18px;
            border: 2px solid #E0E7F5;
            border-radius: 40px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(94, 157, 230, 0.12);
            cursor: pointer;
            white-space: nowrap;
            font-family: 'Inter', sans-serif;
        }
        .btn-back-header:hover {
            border-color: var(--primary);
            background: #EDF4FC;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(94, 157, 230, 0.25);
        }
        .btn-back-header svg {
            stroke: var(--primary);
            fill: none;
            stroke-width: 2.5;
            width: 18px;
            height: 18px;
        }

        /* Layout */
        .contenedor-principal {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }
        .back-area {
            margin-bottom: 24px;
        }

        /* Profile Card */
        .perfil-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .perfil-banner {
            height: 120px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 60%, #3B82F6 100%);
            position: relative;
        }
        .perfil-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to top, #fff, transparent);
        }
        .perfil-info-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 0 32px 32px;
            margin-top: -50px;
            position: relative;
            z-index: 1;
        }
        .perfil-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #3B82F6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 36px;
            font-weight: 800;
            border: 4px solid #fff;
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
        }
        .perfil-detalles {
            flex: 1;
            padding-top: 56px;
        }
        .perfil-nombre {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        .perfil-email {
            font-size: 14px;
            color: var(--gray-500);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .perfil-telefono {
            font-size: 14px;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
        }
        .perfil-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .badge-estado {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }
        .badge-estado.activo { background: #DCFCE7; color: #166534; }
        .badge-estado.pendiente { background: #FEF3C7; color: #92400E; }
        .badge-estado.inactivo { background: #FEE2E2; color: #991B1B; }
        .badge-estado.suspendido { background: #FEE2E2; color: #991B1B; }

        /* Stats Grid */
        .perfil-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            padding: 0 32px;
            margin-top: 8px;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-100);
        }
        .stat-icono {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .stat-icono.azul { background: var(--primary-light); color: var(--primary); }
        .stat-icono.verde { background: #DCFCE7; color: var(--success); }
        .stat-icono.amarillo { background: #FEF3C7; color: var(--warning); }
        .stat-icono.morado { background: #EDE9FE; color: #8B5CF6; }
        .stat-texto .stat-valor {
            font-size: 20px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1;
        }
        .stat-texto .stat-label {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Info Section */
        .info-seccion {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .info-seccion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
        }
        .info-seccion-titulo {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-seccion-icono {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .info-item {
            padding: 14px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .info-item:nth-child(odd) {
            border-right: 1px solid var(--gray-100);
        }
        .info-item:last-child, .info-item:nth-last-child(2):nth-child(odd) {
            border-bottom: none;
        }
        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .info-valor {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        /* Stars */
        .stars-display {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .star { color: var(--gray-300); font-size: 14px; }
        .star.filled { color: var(--warning); }
        .star.half { color: var(--warning); }
        .stars-number {
            margin-left: 6px;
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-600);
        }

        /* Documents */
        .docs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            padding: 20px;
        }
        .doc-card {
            border: 2px dashed var(--gray-200);
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            text-align: center;
            position: relative;
            background: var(--gray-50);
            min-height: 180px;
            justify-content: center;
        }
        .doc-card:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .doc-card.tiene-documento {
            border-style: solid;
            border-color: var(--success);
            background: #F0FDF4;
        }
        .doc-card.rechazado {
            border-style: solid;
            border-color: var(--danger);
            background: #FEF2F2;
        }
        .doc-card.pendiente {
            border-style: solid;
            border-color: var(--warning);
            background: #FFFBEB;
        }
        .doc-icono {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .doc-icono.azul { background: var(--primary-light); color: var(--primary); }
        .doc-icono.verde { background: #DCFCE7; color: var(--success); }
        .doc-icono.rojo { background: #FEE2E2; color: var(--danger); }
        .doc-icono.amarillo { background: #FEF3C7; color: var(--warning); }
        .doc-tipo {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
        }
        .doc-estado {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 50px;
            text-transform: capitalize;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .doc-estado.aprobado { background: #DCFCE7; color: #166534; }
        .doc-estado.pendiente-estado { background: #FEF3C7; color: #92400E; }
        .doc-estado.rechazado-estado { background: #FEE2E2; color: #991B1B; }
        .doc-estado.sin-documento { background: var(--gray-100); color: var(--gray-500); }
        .doc-fecha {
            font-size: 11px;
            color: var(--gray-400);
        }
        .doc-observaciones {
            font-size: 11px;
            color: var(--danger);
            font-style: italic;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .doc-btn-upload {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        .doc-btn-upload:hover { background: var(--primary-dark); }
        .doc-btn-upload:disabled { opacity: 0.5; cursor: not-allowed; }
        .doc-btn-upload.reemplazar { background: var(--warning); }
        .doc-btn-upload.reemplazar:hover { background: #D97706; }
        .doc-file-input { display: none; }
        .doc-loading {
            display: none;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
        }
        .doc-loading.visible { display: flex; }
        .doc-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Toast */
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

        /* Responsive */
        @media (max-width: 768px) {
            .header-contenido { flex-wrap: wrap; height: auto; padding: 12px 0; gap: 10px; }
            .info-conductor { display: none; }
            .contenedor-principal { padding: 16px; }
            .perfil-info-wrapper {
                flex-direction: column;
                align-items: center;
                padding: 0 20px 24px;
                text-align: center;
            }
            .perfil-detalles { padding-top: 12px; }
            .perfil-email, .perfil-telefono { justify-content: center; }
            .perfil-meta { justify-content: center; }
            .perfil-stats { grid-template-columns: 1fr; padding: 0 20px; }
            .info-grid { grid-template-columns: 1fr; }
            .info-item:nth-child(odd) { border-right: none; }
            .docs-grid { grid-template-columns: 1fr; }
            .perfil-nombre { font-size: 20px; }
            .perfil-avatar { width: 80px; height: 80px; font-size: 28px; }
        }
        @media (max-width: 480px) {
            .acciones-header { gap: 4px; }
            .btn-header { padding: 6px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <!-- SECCIÓN: Encabezado con logo, datos del conductor y acciones (Dashboard, Tienda, Cerrar sesión) -->
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
                <a href="<?= APP_URL ?>/repartidor/dashboard" class="btn-header">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="<?= APP_URL ?>/" class="btn-header">
                    <i class="fas fa-store"></i> Tienda
                </a>
                <button class="btn-header cerrar-sesion" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </button>
            </div>
        </div>
    </header>

    <!-- SECCIÓN: Contenedor del perfil con tarjeta de datos y secciones de información -->
    <div class="contenedor-principal">
        <div class="back-area">
            <a href="<?= APP_URL ?>/repartidor/dashboard" class="btn-back-header">
                <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver al Dashboard
            </a>
        </div>

        <!-- SECCIÓN: Tarjeta del perfil (avatar, nombre, contacto y estadísticas) -->
        <div class="perfil-card">
            <div class="perfil-banner"></div>
            <div class="perfil-info-wrapper">
                <div class="perfil-avatar" id="perfilAvatar">
                    ?
                </div>
                <div class="perfil-detalles">
                    <div class="perfil-nombre" id="perfilNombre">Cargando...</div>
                    <div class="perfil-email" id="perfilEmail">
                        <i class="fas fa-envelope"></i> <span>...</span>
                    </div>
                    <div class="perfil-telefono" id="perfilTelefono">
                        <i class="fas fa-phone"></i> <span>...</span>
                    </div>
                    <div class="perfil-meta">
                        <span class="badge-estado" id="perfilEstado">...</span>
                    </div>
                </div>
            </div>
            <div class="perfil-stats">
                <div class="stat-item">
                    <div class="stat-icono azul"><i class="fas fa-box-open"></i></div>
                    <div class="stat-texto">
                        <div class="stat-valor" id="statEntregas">0</div>
                        <div class="stat-label">Entregas Totales</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icono amarillo"><i class="fas fa-star"></i></div>
                    <div class="stat-texto">
                        <div class="stat-valor" id="statCalificacion">0.0</div>
                        <div class="stat-label">Calificación Promedio</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Información personal del repartidor -->
        <div class="info-seccion">
            <div class="info-seccion-header">
                <div class="info-seccion-titulo">
                    <div class="info-seccion-icono"><i class="fas fa-id-card"></i></div>
                    Información Personal
                </div>
            </div>
            <div class="info-grid" id="infoPersonalGrid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user"></i> Nombre</div>
                    <div class="info-valor" id="infoNombre">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user"></i> Apellido</div>
                    <div class="info-valor" id="infoApellido">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="info-valor" id="infoEmail">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone"></i> Teléfono</div>
                    <div class="info-valor" id="infoTelefono">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-file-alt"></i> Tipo de Documento</div>
                    <div class="info-valor" id="infoTipoDoc">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-hashtag"></i> Número de Documento</div>
                    <div class="info-valor" id="infoNumDoc">...</div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Información del vehículo y licencia -->
        <div class="info-seccion">
            <div class="info-seccion-header">
                <div class="info-seccion-titulo">
                    <div class="info-seccion-icono"><i class="fas fa-motorcycle"></i></div>
                    Información del Vehículo y Licencia
                </div>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-car"></i> Tipo de Vehículo</div>
                    <div class="info-valor" id="infoTipoVehiculo">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-credit-card"></i> Placa Vehículo</div>
                    <div class="info-valor" id="infoPlaca">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-id-badge"></i> Número de Licencia</div>
                    <div class="info-valor" id="infoNumLicencia">...</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-layer-group"></i> Categoría Licencia</div>
                    <div class="info-valor" id="infoCatLicencia">...</div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Documentos cargados (generados por JS) -->
        <div class="info-seccion">
            <div class="info-seccion-header">
                <div class="info-seccion-titulo">
                    <div class="info-seccion-icono"><i class="fas fa-file-upload"></i></div>
                    Documentos
                </div>
                <span style="font-size:12px; color:var(--gray-400);">PDF, JPG o PNG</span>
            </div>
            <div class="docs-grid" id="docsGrid">
                <!-- Rendered by JS -->
            </div>
        </div>
    </div>

    <script>
        // Lógica del perfil del repartidor: autenticación con token, carga de datos y documentos
        const APP_URL = '<?= APP_URL ?>';
        const userData = <?= json_encode($user) ?>;
        let token = localStorage.getItem('repartidor_token') || '';

        async function refreshToken() {
            try {
                const res = await fetch(APP_URL + '/repartidor/refresh-token', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data && data.token) {
                    token = data.token;
                    localStorage.setItem('repartidor_token', data.token);
                    return true;
                }
            } catch (e) {
            }
            return false;
        }

        async function authHeaders() {
            if (!token) {
                await refreshToken();
            }
            const h = { 'Accept': 'application/json' };
            if (token) h['Authorization'] = 'Bearer ' + token;
            return h;
        }

        function handleNoAutorizado() {
            localStorage.removeItem('repartidor_token');
            window.location.href = APP_URL + '/repartidor/login';
        }

        const DOC_TYPES = [
            { key: 'licencia_conduccion', label: 'Licencia de Conducci\u00f3n', icon: 'fa-id-card', color: 'azul' },
            { key: 'cedula', label: 'C\u00e9dula', icon: 'fa-file-alt', color: 'verde' },
            { key: 'tarjeta_profesional', label: 'Tarjeta Profesional', icon: 'fa-address-card', color: 'morado' },
            { key: 'soat', label: 'SOAT', icon: 'fa-shield-halved', color: 'amarillo' },
            { key: 'tecnomecanica', label: 'Tecnomec\u00e1nica', icon: 'fa-gear', color: 'azul' }
        ];

        const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
        const ALLOWED_EXTENSIONS = ['.pdf', '.jpg', '.jpeg', '.png'];

        let documentosMap = {};

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
                <div class="toast-icon"><i class="fas ${iconos[tipo]}" aria-hidden="true"></i></div>
                <div class="toast-content">
                    ${titulo ? `<div class="toast-title">${titulo}</div>` : ""}
                    <div class="toast-message">${mensaje}</div>
                </div>
                <button class="toast-close" aria-label="Cerrar notificaci\u00f3n">&times;</button>
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

        function htmlspecialchars(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function obtenerIniciales(nombre, apellido) {
            const n = (nombre || '').trim();
            const a = (apellido || '').trim();
            if (n && a) return (n[0] + a[0]).toUpperCase();
            if (n) return n.substring(0, 2).toUpperCase();
            return '?';
        }

        function renderStars(rating) {
            const r = Number(rating) || 0;
            let html = '<div class="stars-display">';
            for (let i = 1; i <= 5; i++) {
                if (r >= i) {
                    html += '<i class="fas fa-star star filled"></i>';
                } else if (r >= i - 0.5) {
                    html += '<i class="fas fa-star-half-alt star filled"></i>';
                } else {
                    html += '<i class="far fa-star star"></i>';
                }
            }
            html += `<span class="stars-number">${r.toFixed(1)}</span></div>`;
            return html;
        }

        function populateProfile(u) {
            const nombre = u.nombre || '';
            const apellido = u.apellido || '';

            document.getElementById('perfilAvatar').textContent = obtenerIniciales(nombre, apellido);
            document.getElementById('perfilNombre').textContent = `${nombre} ${apellido}`.trim() || 'Sin nombre';
            document.getElementById('perfilEmail').querySelector('span').textContent = u.email || 'Sin email';
            document.getElementById('perfilTelefono').querySelector('span').textContent = u.telefono || 'Sin tel\u00e9fono';

            const estado = (u.estado || 'activo').toLowerCase();
            const estadoEl = document.getElementById('perfilEstado');
            estadoEl.textContent = estado;
            estadoEl.className = 'badge-estado ' + estado;

            document.getElementById('statEntregas').textContent = u.total_entregas || 0;
            document.getElementById('statCalificacion').textContent = Number(u.calificacion_promedio || 0).toFixed(1);

            document.getElementById('infoNombre').textContent = nombre || '-';
            document.getElementById('infoApellido').textContent = apellido || '-';
            document.getElementById('infoEmail').textContent = u.email || '-';
            document.getElementById('infoTelefono').textContent = u.telefono || '-';
            document.getElementById('infoTipoDoc').textContent = u.tipo_documento ? u.tipo_documento.replace('_', ' ').toUpperCase() : '-';
            document.getElementById('infoNumDoc').textContent = u.numero_documento || '-';
            document.getElementById('infoTipoVehiculo').textContent = u.tipo_vehiculo ? u.tipo_vehiculo.charAt(0).toUpperCase() + u.tipo_vehiculo.slice(1) : '-';
            document.getElementById('infoPlaca').textContent = u.placa_vehiculo || '-';
            document.getElementById('infoNumLicencia').textContent = u.numero_licencia || '-';
            document.getElementById('infoCatLicencia').textContent = u.categoria_licencia || '-';
        }

        function renderDocs() {
            const grid = document.getElementById('docsGrid');
            grid.innerHTML = DOC_TYPES.map(doc => {
                const existing = documentosMap[doc.key];
                let estadoTexto = 'Sin documento';
                let estadoClase = 'sin-documento';
                let iconoColor = doc.color;
                let fecha = '';
                let observaciones = '';
                let btnHtml = '';

                if (existing) {
                    const est = (existing.estado || '').toLowerCase();
                    if (est === 'aprobado' || est === 'aprobada') {
                        estadoTexto = 'Aprobado';
                        estadoClase = 'aprobado';
                        iconoColor = 'verde';
                    } else if (est === 'rechazado' || est === 'rechazada') {
                        estadoTexto = 'Rechazado';
                        estadoClase = 'rechazado-estado';
                        iconoColor = 'rojo';
                    } else {
                        estadoTexto = 'Pendiente';
                        estadoClase = 'pendiente-estado';
                        iconoColor = 'amarillo';
                    }
                    if (existing.fecha_subida) {
                        try {
                            const d = new Date(existing.fecha_subida);
                            fecha = d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
                        } catch(e) {
                            fecha = existing.fecha_subida;
                        }
                    }
                    observaciones = existing.observaciones || '';
                    btnHtml = `
                        <input type="file" class="doc-file-input" id="file_${doc.key}" accept=".pdf,.jpg,.jpeg,.png" onchange="handleUpload('${doc.key}', this)">
                        <button class="doc-btn-upload reemplazar" onclick="document.getElementById('file_${doc.key}').click()">
                            <i class="fas fa-upload"></i> Reemplazar
                        </button>
                    `;
                } else {
                    btnHtml = `
                        <input type="file" class="doc-file-input" id="file_${doc.key}" accept=".pdf,.jpg,.jpeg,.png" onchange="handleUpload('${doc.key}', this)">
                        <button class="doc-btn-upload" onclick="document.getElementById('file_${doc.key}').click()">
                            <i class="fas fa-cloud-arrow-up"></i> Subir Documento
                        </button>
                    `;
                }

                return `
                    <div class="doc-card ${existing ? (estadoClase === 'aprobado' ? 'tiene-documento' : estadoClase === 'rechazado-estado' ? 'rechazado' : 'pendiente') : ''}" id="docCard_${doc.key}">
                        <div class="doc-icono ${iconoColor}"><i class="fas ${doc.icon}"></i></div>
                        <div class="doc-tipo">${doc.label}</div>
                        <span class="doc-estado ${estadoClase}"><i class="fas ${existing ? (estadoClase === 'aprobado' ? 'fa-check-circle' : estadoClase === 'rechazado-estado' ? 'fa-times-circle' : 'fa-clock') : 'fa-minus-circle'}"></i> ${estadoTexto}</span>
                        ${fecha ? `<div class="doc-fecha"><i class="fas fa-calendar-alt"></i> ${fecha}</div>` : ''}
                        ${observaciones ? `<div class="doc-observaciones" title="${htmlspecialchars(observaciones)}"><i class="fas fa-comment-exclamation"></i> ${htmlspecialchars(observaciones)}</div>` : ''}
                        <div class="doc-loading" id="loading_${doc.key}"><div class="doc-spinner"></div> Subiendo...</div>
                        <div id="btnArea_${doc.key}">${btnHtml}</div>
                    </div>
                `;
            }).join('');
        }

        async function loadDocuments() {
            try {
                const res = await fetch(APP_URL + '/api/repartidor/documentos', {
                    headers: await authHeaders()
                });
                if (res.status === 401 || res.status === 403) {
                    handleNoAutorizado();
                    return;
                }
                if (!res.ok) return;
                const data = await res.json();
                if (data.success && Array.isArray(data.documentos)) {
                    documentosMap = {};
                    data.documentos.forEach(doc => {
                        documentosMap[doc.tipo] = doc;
                    });
                }
            } catch (e) {
                console.warn('No se pudieron cargar documentos:', e);
            }
            renderDocs();
        }

        async function handleUpload(tipo, input) {
            const file = input.files[0];
            if (!file) return;

            if (!ALLOWED_TYPES.includes(file.type)) {
                mostrarToast({ titulo: 'Archivo inv\u00e1lido', mensaje: 'Solo se aceptan archivos PDF, JPG o PNG', tipo: 'error' });
                input.value = '';
                return;
            }

            const ext = '.' + file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_EXTENSIONS.includes(ext)) {
                mostrarToast({ titulo: 'Archivo inv\u00e1lido', mensaje: 'Solo se aceptan archivos PDF, JPG o PNG', tipo: 'error' });
                input.value = '';
                return;
            }

            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                mostrarToast({ titulo: 'Archivo muy grande', mensaje: 'El archivo no debe superar 10 MB', tipo: 'error' });
                input.value = '';
                return;
            }

            const loadingEl = document.getElementById('loading_' + tipo);
            const btnAreaEl = document.getElementById('btnArea_' + tipo);
            loadingEl.classList.add('visible');
            btnAreaEl.style.display = 'none';

            const formData = new FormData();
            formData.append('tipo', tipo);
            formData.append('archivo', file);

            try {
                const res = await fetch(APP_URL + '/api/repartidor/documentos/subir', {
                    method: 'POST',
                    headers: await authHeaders(),
                    body: formData
                });
                const data = await res.json();

                if (res.status === 401 || res.status === 403) {
                    handleNoAutorizado();
                    return;
                }

                if (data.success) {
                    mostrarToast({
                        titulo: 'Documento subido',
                        mensaje: 'Tu documento ha sido enviado para revisi\u00f3n',
                        tipo: 'success'
                    });
                    documentosMap[tipo] = {
                        tipo: tipo,
                        estado: 'pendiente',
                        fecha_subida: new Date().toISOString(),
                        observaciones: ''
                    };
                    renderDocs();
                } else {
                    mostrarToast({ titulo: 'Error', mensaje: data.message || 'No se pudo subir el documento', tipo: 'error' });
                    loadingEl.classList.remove('visible');
                    btnAreaEl.style.display = '';
                }
            } catch (e) {
                mostrarToast({ titulo: 'Error', mensaje: 'Error de conexi\u00f3n al subir documento', tipo: 'error' });
                loadingEl.classList.remove('visible');
                btnAreaEl.style.display = '';
            }
            input.value = '';
        }

        async function loadProfileData() {
            try {
                const res = await fetch(APP_URL + '/api/repartidor/auth/me', {
                    headers: await authHeaders()
                });
                if (res.status === 401 || res.status === 403) {
                    handleNoAutorizado();
                    return;
                }
                if (!res.ok) return;
                const data = await res.json();
                if (data.success && data.user) {
                    populateProfile(data.user);
                    return;
                }
            } catch (e) {
                console.warn('No se pudo cargar perfil desde API:', e);
            }
            populateProfile(userData);
        }

        async function logout() {
            try {
                await fetch(APP_URL + '/repartidor/logout', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
            } catch (e) {
                console.warn('Error al cerrar sesi\u00f3n:', e);
            }
            localStorage.removeItem('repartidor_token');
            window.location.href = APP_URL + '/repartidor/login';
        }

        document.addEventListener('DOMContentLoaded', async () => {
            populateProfile(userData);

            await loadProfileData();
            await loadDocuments();
        });
    </script>
    <!-- Fin de la lógica del perfil de repartidor -->
</body>
</html>
