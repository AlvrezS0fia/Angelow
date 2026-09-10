<!--
  ==========================================================================
  VISTA: repartidor/registro_repartidor.php
  --------------------------------------------------------------------------
  QUÉ MUESTRA: Registro de repartidores en 4 pasos (datos personales,
  vehículo, documentos y confirmación). Incluye validación en vivo, carga
  de documentos y envío final del formulario por fetch.

  ARCHIVOS EXTERNOS: back-button.css, Font Awesome y fuentes Google;
  estilos propios en un <style> del <head>.

  JS QUE LA CONTROLA: <script> inline al final (pasos, validación, subida
  de archivos y envío del formulario).
  ==========================================================================
-->
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro Repartidor — ANGELOW</title>
<link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/back-button.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{
  --primary:#5E9DE6;--primary-dark:#3A7BC8;--accent:#7FBBF2;--accent-light:#EFF4FF;
  --bg-light:#F8FBFE;--bg-soft:#EDF4FC;--text-dark:#1E3A8A;--text-secondary:#4B6A9B;
  --white:#ffffff;--shadow:rgba(94,157,230,0.12);--shadow-hover:rgba(94,157,230,0.25);
  --border-light:#E0E7F5;--success:#10b981;--error:#ef4444;--warning:#f59e0b;
}
body{font-family:'Inter',sans-serif;background:var(--bg-light);color:var(--text-dark);min-height:100vh;display:flex;flex-direction:column;line-height:1.5;}
.toast-container{position:fixed;top:100px;right:20px;z-index:3000;display:flex;flex-direction:column;gap:12px;pointer-events:none;}
.toast{min-width:300px;max-width:420px;background:var(--white);border-radius:16px;padding:16px 20px;box-shadow:0 10px 30px rgba(0,0,0,0.15);display:flex;align-items:center;gap:14px;opacity:0;transform:translateX(100%);transition:all 0.4s;pointer-events:auto;border-left:5px solid var(--primary);}
.toast.show{opacity:1;transform:translateX(0);}
.toast.success{border-left-color:var(--success);}.toast.warning{border-left-color:var(--warning);}.toast.error{border-left-color:var(--error);}.toast.info{border-left-color:var(--primary);}
.toast-icon{width:36px;height:36px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.toast.success .toast-icon{background:var(--success);}.toast.warning .toast-icon{background:var(--warning);}.toast.error .toast-icon{background:var(--error);}
.toast-icon svg{width:20px;height:20px;stroke:white;fill:none;stroke-width:3;}
.toast-title{font-weight:600;font-size:16px;color:var(--text-dark);margin-bottom:4px;}
.toast-message{font-size:14px;color:var(--text-secondary);}
.toast-close{background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-secondary);opacity:0.6;margin-left:auto;padding:0 4px;}
.toast-close:hover{opacity:1;}
.topbar{position:sticky;top:0;z-index:100;background:var(--white);border-bottom:1px solid var(--border-light);padding:12px 30px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 2px 12px var(--shadow);}
.topbar-logo{width:48px;height:48px;border-radius:50%;background:var(--bg-soft);display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer;position:relative;border:2px solid var(--border-light);flex-shrink:0;transition:all 0.3s;}
.topbar-logo:hover{border-color:var(--primary);box-shadow:0 0 0 4px var(--shadow);}
.topbar-logo svg{opacity:0.5;pointer-events:none;}
.topbar-brand{display:flex;flex-direction:column;line-height:1.1;}
.topbar-name{font-weight:900;font-size:22px;color:var(--primary);letter-spacing:-0.5px;}
.topbar-sub{font-size:11px;font-weight:600;color:var(--text-secondary);letter-spacing:1.5px;text-transform:uppercase;}
.btn-back-home{display:flex;align-items:center;gap:8px;padding:8px 18px;background:var(--bg-soft);border:1px solid var(--border-light);border-radius:50px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;color:var(--text-secondary);cursor:pointer;transition:all 0.25s;text-decoration:none;margin-left:auto;}
.btn-back-home:hover{background:var(--accent-light);border-color:var(--primary);color:var(--primary);transform:translateX(-2px);}
.page-body{width:100%;padding:40px 16px;display:flex;align-items:center;justify-content:center;flex:1;}
.wrapper{display:flex;width:100%;max-width:1000px;background:var(--white);border-radius:24px;box-shadow:0 8px 40px var(--shadow);overflow:hidden;border:1px solid var(--border-light);}
.sidebar{width:280px;flex-shrink:0;background:var(--text-dark);padding:36px 24px 28px;display:flex;flex-direction:column;gap:28px;position:relative;overflow:hidden;}
.sidebar::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(94,157,230,0.12);top:-120px;right:-100px;}
.sidebar::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(127,187,242,0.08);bottom:-60px;left:-50px;}
.logo-area{display:flex;flex-direction:column;align-items:center;gap:12px;position:relative;z-index:1;}
.app-name{font-weight:900;font-size:22px;color:#fff;text-align:center;letter-spacing:-0.5px;}
.app-sub{font-size:10px;font-weight:700;color:rgba(255,255,255,0.35);text-align:center;letter-spacing:1.5px;text-transform:uppercase;margin-top:-4px;}
.steps-nav{display:flex;flex-direction:column;gap:4px;position:relative;z-index:1;}
.step-item{display:flex;align-items:center;gap:14px;padding:10px 14px;border-radius:12px;transition:all 0.3s;cursor:pointer;}
.step-item:hover{background:rgba(255,255,255,0.05);}
.step-item.active{background:rgba(94,157,230,0.2);}
.step-dot{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:rgba(255,255,255,0.3);flex-shrink:0;transition:all 0.4s;}
.step-item.active .step-dot{background:var(--primary);border-color:var(--accent);color:#fff;box-shadow:0 0 20px rgba(94,157,230,0.4);}
.step-item.done .step-dot{background:var(--success);border-color:var(--success);color:#fff;}
.step-label strong{display:block;font-size:14px;font-weight:700;color:rgba(255,255,255,0.3);transition:color 0.3s;}
.step-label small{font-size:11px;color:rgba(255,255,255,0.2);}
.step-item.active .step-label strong,.step-item.done .step-label strong{color:#fff;}
.main{flex:1;padding:36px 36px 28px;display:flex;flex-direction:column;min-height:560px;background:var(--white);}
.step-heading{margin-bottom:24px;}
.step-heading h2{font-size:24px;font-weight:800;color:var(--text-dark);letter-spacing:-0.5px;}
.step-heading p{font-size:14px;color:var(--text-secondary);margin-top:4px;font-weight:500;}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px;}
.full{grid-column:1/-1;}
.field{display:flex;flex-direction:column;gap:5px;}
.field-label{font-size:11px;font-weight:700;color:var(--text-secondary);letter-spacing:0.8px;text-transform:uppercase;}
.error-msg{font-size:11px;font-weight:600;color:var(--error);margin-top:2px;display:none;animation:fadeUp 0.2s ease;}
.field.invalid input,.field.invalid select{border-color:var(--error)!important;background:#fff5f5;box-shadow:0 0 0 4px rgba(239,68,68,0.08);}
.field.invalid .error-msg{display:block;}
.field.valid input,.field.valid select{border-color:var(--success)!important;background:#f0fdf4;}
input,select{width:100%;padding:12px 16px;border:2px solid var(--border-light);border-radius:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;color:var(--text-dark);background:var(--bg-light);outline:none;transition:all 0.25s;appearance:none;}
input:focus,select:focus{border-color:var(--primary);background:var(--white);box-shadow:0 0 0 4px var(--shadow);}
input::placeholder{color:var(--text-secondary);font-weight:400;}
.nav-btns{display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:24px;border-top:2px solid var(--border-light);}
.btn{display:flex;align-items:center;gap:8px;padding:12px 28px;border:none;border-radius:50px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:all 0.25s;letter-spacing:0.3px;}
.btn-back{background:var(--bg-soft);color:var(--text-secondary);}
.btn-back:hover{background:var(--accent-light);color:var(--text-dark);}
.btn-next{background:var(--primary);color:#fff;box-shadow:0 4px 16px var(--shadow);}
.btn-next:hover{background:var(--primary-dark);box-shadow:0 6px 24px var(--shadow-hover);transform:translateY(-2px);}
.btn-next:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.step-count{font-size:13px;font-weight:600;color:var(--text-secondary);}
.step-panel{display:none;}
.step-panel.active{display:block;}
.login-link{text-align:center;font-size:14px;color:var(--text-secondary);margin-top:16px;}
.login-link a{color:var(--primary);font-weight:600;text-decoration:none;}
.login-link a:hover{text-decoration:underline;}
.upload-area{border:2px dashed var(--border-light);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:all 0.25s;background:var(--bg-light);}
.upload-area:hover{border-color:var(--primary);background:var(--accent-light);}
.upload-area.has-file{border-color:var(--success);background:#f0fdf4;}
.upload-area input[type=file]{display:none;}
.upload-area .upload-icon{font-size:28px;margin-bottom:6px;color:var(--text-secondary);}
.upload-area.has-file .upload-icon{color:var(--success);}
.upload-area .upload-text{font-size:13px;color:var(--text-secondary);font-weight:500;}
.upload-area .upload-text strong{color:var(--text-dark);}
.pass-confirm-wrap{position:relative;}
.pass-confirm-wrap .pass-confirm-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:18px;pointer-events:none;}
.pass-confirm-wrap .pass-confirm-icon.valid{color:var(--success);}
.pass-confirm-wrap .pass-confirm-icon.invalid{color:var(--error);}
.pass-confirm-wrap input{padding-right:40px;}
.field.valid .pass-confirm-icon.valid,.field.invalid .pass-confirm-icon.invalid{display:block;}
.pass-confirm-icon{display:none;}
.checkbox-field{display:flex;align-items:flex-start;gap:10px;cursor:pointer;}
.checkbox-field input[type=checkbox]{display:none;}
.checkbox-box{width:22px;height:22px;border:2px solid var(--border-light);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.25s;background:var(--bg-light);margin-top:1px;}
.checkbox-box i{font-size:12px;color:#fff;opacity:0;transition:opacity 0.2s;}
.checkbox-field input:checked + .checkbox-box{background:var(--primary);border-color:var(--primary);}
.checkbox-field input:checked + .checkbox-box i{opacity:1;}
.checkbox-text{font-size:13px;color:var(--text-secondary);font-weight:500;line-height:1.4;}
.checkbox-text a{color:var(--primary);text-decoration:none;font-weight:600;}
.checkbox-text a:hover{text-decoration:underline;}
.terms-group{display:flex;flex-direction:column;gap:14px;}
.summary-card{background:var(--bg-light);border:1px solid var(--border-light);border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:16px;}
.summary-section{display:flex;flex-direction:column;gap:8px;}
.summary-section-title{font-size:12px;font-weight:800;color:var(--primary);letter-spacing:1px;text-transform:uppercase;}
.summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;}
.summary-item{display:flex;flex-direction:column;gap:2px;}
.summary-item-label{font-size:10px;font-weight:700;color:var(--text-secondary);letter-spacing:0.5px;text-transform:uppercase;}
.summary-item-value{font-size:14px;font-weight:600;color:var(--text-dark);}
.summary-divider{height:1px;background:var(--border-light);}
.summary-doc-item{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:var(--text-dark);}
.summary-doc-item i{color:var(--success);font-size:14px;}
.btn-submit{background:var(--success);color:#fff;box-shadow:0 4px 16px rgba(16,185,129,0.3);justify-content:center;}
.btn-submit:hover{background:#059669;box-shadow:0 6px 24px rgba(16,185,129,0.4);transform:translateY(-2px);}
.btn-submit:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.btn-submit .spinner{display:none;width:18px;height:18px;border:2.5px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;}
.btn-submit.loading .spinner{display:inline-block;}
.btn-submit.loading .btn-text{display:none;}
@keyframes spin{to{transform:rotate(360deg)}}
.footer{background:var(--text-dark);color:rgba(255,255,255,0.4);text-align:center;font-size:12px;font-weight:600;padding:18px;letter-spacing:0.5px;border-top:1px solid rgba(255,255,255,0.05);}
@media(max-width:820px){.sidebar{display:none;}.main{padding:24px 20px 20px;}.grid{grid-template-columns:1fr;}.topbar{padding:10px 16px;}.wrapper{border-radius:16px;}.summary-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="toast-container" id="toastContainer"></div>
<!-- SECCIÓN: Barra superior con logo ANGELOW y botón Volver al login -->
<header class="topbar">
  <div class="topbar-logo" title="ANGELOW">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="#5E9DE6" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#5E9DE6" stroke-width="2" stroke-linecap="round"/></svg>
  </div>
  <div class="topbar-brand">
    <span class="topbar-name">ANGELOW</span>
    <span class="topbar-sub">Repartidores</span>
  </div>
  <a href="<?= APP_URL ?>/repartidor/login" class="btn-back-header">
    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
      <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
    Volver
  </a>
</header>

<!-- SECCIÓN: Wrapper con panel lateral (navegación de los 4 pasos) y formulario por pasos -->
<div class="page-body">
  <div class="wrapper">
    <aside class="sidebar">
      <div class="logo-area">
        <div class="app-name">ANGELOW</div>
        <div class="app-sub">Repartidores</div>
      </div>
      <nav class="steps-nav">
        <div class="step-item active" onclick="goStep(1)">
          <div class="step-dot" id="dot1"><svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M5 13L9 17L19 7" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="step-label"><strong>Datos Personales</strong><small>Nombre y contacto</small></div>
        </div>
        <div class="step-item" onclick="goStep(2)">
          <div class="step-dot" id="dot2">2</div>
          <div class="step-label"><strong>Vehiculo</strong><small>Tipo y placa</small></div>
        </div>
        <div class="step-item" onclick="goStep(3)">
          <div class="step-dot" id="dot3">3</div>
          <div class="step-label"><strong>Documentos</strong><small>SOAT, licencia, tarjeta</small></div>
        </div>
        <div class="step-item" onclick="goStep(4)">
          <div class="step-dot" id="dot4">4</div>
          <div class="step-label"><strong>Confirmacion</strong><small>Terminos y envio</small></div>
        </div>
      </nav>
    </aside>

    <!-- SECCIÓN: Formulario de registro en 4 pasos (Datos Personales, Vehiculo, Documentos, Confirmacion) -->
    <main class="main">
      <!-- STEP 1: Datos Personales -->
      <!-- STEP 1: Datos Personales -->
      <div class="step-panel active" id="step1">
        <div class="step-heading">
          <h2>Datos Personales</h2>
          <p>Cuentanos sobre ti para comenzar.</p>
        </div>
        <div class="grid">
          <div class="field" id="f-nombres">
            <label class="field-label">Nombres *</label>
            <input id="nombres" type="text" placeholder="Tus nombres" oninput="liveValidate('nombres')">
            <span class="error-msg">Ingresa tus nombres (min. 2 caracteres, solo letras).</span>
          </div>
          <div class="field" id="f-apellidos">
            <label class="field-label">Apellidos *</label>
            <input id="apellidos" type="text" placeholder="Tus apellidos" oninput="liveValidate('apellidos')">
            <span class="error-msg">Ingresa tus apellidos (min. 2 caracteres, solo letras).</span>
          </div>
          <div class="field" id="f-correo">
            <label class="field-label">Correo Electronico *</label>
            <input id="correo" type="email" placeholder="correo@ejemplo.com" oninput="liveValidate('correo')">
            <span class="error-msg">Ingresa un correo valido.</span>
          </div>
          <div class="field" id="f-celular">
            <label class="field-label">Celular *</label>
            <input id="celular" type="tel" placeholder="3001234567" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'');liveValidate('celular')">
            <span class="error-msg">Celular: 10 digitos, debe empezar con 3.</span>
          </div>
          <div class="field" id="f-tipodoc">
            <label class="field-label">Tipo de Documento *</label>
            <select id="tipodoc" onchange="liveValidate('tipodoc')">
              <option value="">Seleccionar</option>
              <option value="CC">CC - Cedula de Ciudadania</option>
              <option value="CE">CE - Cedula de Extranjeria</option>
              <option value="TI">TI - Tarjeta de Identidad</option>
              <option value="PAS">PAS - Pasaporte</option>
            </select>
            <span class="error-msg">Selecciona un tipo de documento.</span>
          </div>
          <div class="field" id="f-numdoc">
            <label class="field-label">Numero de Documento *</label>
            <input id="numdoc" type="text" placeholder="1234567890" oninput="this.value=this.value.replace(/[^0-9]/g,'');liveValidate('numdoc')">
            <span class="error-msg">Entre 5 y 12 digitos.</span>
          </div>
          <div class="field" id="f-fecha_nacimiento">
            <label class="field-label">Fecha de Nacimiento *</label>
            <input id="fecha_nacimiento" type="date" onchange="liveValidate('fecha_nacimiento')">
            <span class="error-msg">Debes tener al menos 18 anos.</span>
          </div>
          <div class="field" id="f-direccion">
            <label class="field-label">Direccion *</label>
            <input id="direccion" type="text" placeholder="Calle, numero, barrio" oninput="liveValidate('direccion')">
            <span class="error-msg">Ingresa tu direccion.</span>
          </div>
          <div class="field" id="f-ciudad">
            <label class="field-label">Ciudad *</label>
            <input id="ciudad" type="text" placeholder="Tu ciudad" oninput="liveValidate('ciudad')">
            <span class="error-msg">Ingresa tu ciudad.</span>
          </div>
          <div class="field" id="f-pass">
            <label class="field-label">Contrasena *</label>
            <input id="pass" type="password" placeholder="Min. 8 caracteres" oninput="checkPassStrength();liveValidate('pass');validatePassConfirm();">
            <div id="passStrength" style="display:flex;gap:4px;margin-top:4px;">
              <div id="ps1" style="flex:1;height:4px;border-radius:2px;background:#e5e7eb;transition:background 0.3s;"></div>
              <div id="ps2" style="flex:1;height:4px;border-radius:2px;background:#e5e7eb;transition:background 0.3s;"></div>
              <div id="ps3" style="flex:1;height:4px;border-radius:2px;background:#e5e7eb;transition:background 0.3s;"></div>
              <div id="ps4" style="flex:1;height:4px;border-radius:2px;background:#e5e7eb;transition:background 0.3s;"></div>
            </div>
            <div id="passHint" style="font-size:11px;color:var(--text-secondary);margin-top:2px;"></div>
            <span class="error-msg">Min. 8 caracteres, mayuscula, minuscula, numero y caracter especial.</span>
          </div>
          <div class="field" id="f-pass_confirm">
            <label class="field-label">Confirmar Contrasena *</label>
            <div class="pass-confirm-wrap">
              <input id="pass_confirm" type="password" placeholder="Repite tu contrasena" oninput="validatePassConfirm()">
              <i class="fa-solid fa-check pass-confirm-icon valid" id="passConfirmOk"></i>
              <i class="fa-solid fa-xmark pass-confirm-icon invalid" id="passConfirmFail"></i>
            </div>
            <span class="error-msg">Las contrasenas no coinciden.</span>
          </div>
        </div>
        <div class="nav-btns">
          <span class="step-count">Paso 1 de 4</span>
          <button type="button" class="btn btn-next" onclick="nextStep(1)">Siguiente <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
      </div>

      <!-- STEP 2: Vehiculo -->
      <div class="step-panel" id="step2">
        <div class="step-heading">
          <h2>Informacion del Vehiculo</h2>
          <p>Datos de tu medio de transporte.</p>
        </div>
        <div class="grid">
          <div class="field" id="f-vehiculo">
            <label class="field-label">Tipo de Vehiculo *</label>
            <select id="vehiculo" onchange="liveValidate('vehiculo')">
              <option value="">Seleccionar</option>
              <option value="moto">Moto</option>
              <option value="carro">Carro</option>
              <option value="bicicleta">Bicicleta</option>
              <option value="camioneta">Camioneta</option>
            </select>
            <span class="error-msg">Selecciona un tipo de vehiculo.</span>
          </div>
          <div class="field" id="f-placa">
            <label class="field-label">Placa *</label>
            <input id="placa" type="text" placeholder="ABC-123" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase();liveValidate('placa')">
            <span class="error-msg">Formato: ABC123 o ABC-123 (3 letras + 3-4 digitos).</span>
          </div>
          <div class="field" id="f-licencia">
            <label class="field-label">Numero de Licencia *</label>
            <input id="licencia" type="text" placeholder="Ej: LIC-2025-001" oninput="liveValidate('licencia')">
            <span class="error-msg">Min. 3 caracteres.</span>
          </div>
          <div class="field" id="f-catlicencia">
            <label class="field-label">Categoria de Licencia *</label>
            <select id="catlicencia" onchange="liveValidate('catlicencia')">
              <option value="">Seleccionar</option>
              <option value="A1">A1 - Motocicleta</option>
              <option value="A2">A2 - Motocicleta pesada</option>
              <option value="B1">B1 - Vehiculos particulares</option>
              <option value="B2">B2 - Vehiculos de servicio</option>
              <option value="B3">B3 - Camiones</option>
              <option value="C1">C1 - Transporte publico</option>
            </select>
            <span class="error-msg">Selecciona la categoria.</span>
          </div>
        </div>
        <div class="nav-btns">
          <button type="button" class="btn btn-back" onclick="goStep(1)"><svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg> Anterior</button>
          <span class="step-count">Paso 2 de 4</span>
          <button type="button" class="btn btn-next" onclick="nextStep(2)">Siguiente <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
      </div>

      <!-- STEP 3: Documentos -->
      <div class="step-panel" id="step3">
        <div class="step-heading">
          <h2>Documentos</h2>
          <p>Sube tus documentos para verificacion.</p>
        </div>
        <div class="grid">
          <div class="field full">
            <label class="field-label">Tarjeta de Propiedad *</label>
            <div class="upload-area" id="tarjeta-area" onclick="document.getElementById('tarjeta-file').click()">
              <input type="file" id="tarjeta-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFile('tarjeta', this)">
              <div class="upload-icon"><i class="fa-solid fa-car"></i></div>
              <div class="upload-text" id="tarjeta-text"><strong>Haz clic para subir</strong> tu tarjeta de propiedad (PDF o imagen)</div>
            </div>
          </div>
          <div class="field full">
            <label class="field-label">Licencia de Conduccion *</label>
            <div class="upload-area" id="licencia-area" onclick="document.getElementById('licencia-file').click()">
              <input type="file" id="licencia-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFile('licencia_doc', this)">
              <div class="upload-icon"><i class="fa-solid fa-id-card"></i></div>
              <div class="upload-text" id="licencia_doc-text"><strong>Haz clic para subir</strong> tu licencia de conduccion (PDF o imagen)</div>
            </div>
          </div>
          <div class="field">
            <label class="field-label">SOAT *</label>
            <div class="upload-area" id="soat-area" onclick="document.getElementById('soat-file').click()">
              <input type="file" id="soat-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFile('soat', this)">
              <div class="upload-icon"><i class="fa-solid fa-shield-halved"></i></div>
              <div class="upload-text" id="soat-text"><strong>Haz clic para subir</strong> tu SOAT (PDF o imagen)</div>
            </div>
          </div>
          <div class="field" id="f-soat_vencimiento">
            <label class="field-label">Fecha de Vencimiento SOAT *</label>
            <input id="soat_vencimiento" type="date" onchange="liveValidate('soat_vencimiento')">
            <span class="error-msg">La fecha debe ser futura.</span>
          </div>
          <div class="field">
            <label class="field-label">Tecnomecanica *</label>
            <div class="upload-area" id="tecnomecanica-area" onclick="document.getElementById('tecnomecanica-file').click()">
              <input type="file" id="tecnomecanica-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFile('tecnomecanica', this)">
              <div class="upload-icon"><i class="fa-solid fa-gear"></i></div>
              <div class="upload-text" id="tecnomecanica-text"><strong>Haz clic para subir</strong> tu tecnomecanica (PDF o imagen)</div>
            </div>
          </div>
          <div class="field" id="f-tecnomecanica_vencimiento">
            <label class="field-label">Fecha de Vencimiento Tecnomecanica *</label>
            <input id="tecnomecanica_vencimiento" type="date" onchange="liveValidate('tecnomecanica_vencimiento')">
            <span class="error-msg">La fecha debe ser futura.</span>
          </div>
        </div>
        <div class="nav-btns">
          <button type="button" class="btn btn-back" onclick="goStep(2)"><svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg> Anterior</button>
          <span class="step-count">Paso 3 de 4</span>
          <button type="button" class="btn btn-next" onclick="nextStep(3)">Siguiente <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
      </div>

      <!-- STEP 4: Terminos y Confirmacion -->
      <div class="step-panel" id="step4">
        <div class="step-heading">
          <h2>Terminos y Confirmacion</h2>
          <p>Revisa tu informacion y acepta los terminos.</p>
        </div>
        <div class="summary-card" id="summaryCard"></div>
        <div style="margin-top:20px;">
          <div class="terms-group">
            <label class="checkbox-field" id="f-acepta_terminos">
              <input type="checkbox" id="acepta_terminos" onchange="validateTerms()">
              <span class="checkbox-box"><i class="fa-solid fa-check"></i></span>
              <span class="checkbox-text">Acepto los <a href="#" onclick="event.stopPropagation()">Terminos y Condiciones</a> *</span>
            </label>
            <label class="checkbox-field" id="f-acepta_privacidad">
              <input type="checkbox" id="acepta_privacidad" onchange="validateTerms()">
              <span class="checkbox-box"><i class="fa-solid fa-check"></i></span>
              <span class="checkbox-text">Acepto la <a href="#" onclick="event.stopPropagation()">Politica de Privacidad</a> *</span>
            </label>
          </div>
          <span class="error-msg" id="terms-error" style="display:none;margin-top:8px;">Debes aceptar ambos campos.</span>
        </div>
        <div class="nav-btns">
          <button type="button" class="btn btn-back" onclick="goStep(3)"><svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg> Anterior</button>
          <span class="step-count">Paso 4 de 4</span>
          <button type="button" class="btn btn-next btn-submit" id="btnSubmit" onclick="submitForm()">
            <span class="spinner"></span>
            <span class="btn-text"><i class="fa-solid fa-paper-plane" style="font-size:13px;"></i> Enviar Solicitud</span>
          </button>
        </div>
      </div>

      <div class="login-link" style="margin-top:20px;">
        Ya tienes cuenta? <a href="<?= APP_URL ?>/repartidor/login">Inicia sesion aqui</a>
      </div>
    </main>
  </div>
</div>

<!-- SECCIÓN: Pie de página -->
<footer class="footer">&copy; 2026 ANGELOW. Todos los derechos reservados.</footer>

<script>
// Lógica del registro de repartidor: toasts, navegación por pasos, validación en vivo y envío por fetch (submitForm)
function showToast({title, message, type = "info", duration = 4000}) {
  const container = document.getElementById("toastContainer");
  if (!container) return;
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  const icons = { success: '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>', warning: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', error: '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>', info: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' };
  toast.innerHTML = `<div class="toast-icon">${icons[type] || icons.info}</div><div class="toast-content">${title ? `<div class="toast-title">${title}</div>` : ''}<div class="toast-message">${message}</div></div><button class="toast-close">&times;</button>`;
  container.appendChild(toast);
  setTimeout(() => toast.classList.add("show"), 100);
  toast.querySelector(".toast-close").onclick = () => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); };
  setTimeout(() => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); }, duration);
}

let currentStep = 1;

function goStep(step) {
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.step-item').forEach((s, i) => {
    s.classList.remove('active');
    if (i < step - 1) s.classList.add('done');
    if (i === step - 1) { s.classList.add('active'); s.classList.remove('done'); }
  });
  document.getElementById('step' + step).classList.add('active');
  currentStep = step;
  if (step === 4) buildSummary();
}

function val(id) { return (document.getElementById(id)?.value || '').trim(); }

function setField(id, valid, msg) {
  const f = document.getElementById('f-' + id);
  if (!f) return valid;
  f.classList.toggle('valid', valid);
  f.classList.toggle('invalid', !valid);
  if (!valid && msg) f.querySelector('.error-msg').textContent = msg;
  return valid;
}

function validateNames(id) {
  const v = val(id);
  return v.length >= 2 && /^[a-zA-Z\u00C0-\u017F\s]+$/.test(v);
}

function validateEmail() {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('correo'));
}

function validatePhone() {
  const v = val('celular');
  return /^\d{10}$/.test(v) && v.startsWith('3');
}

function validateNumDoc() {
  const v = val('numdoc');
  return /^\d{5,12}$/.test(v);
}

function validateFechaNacimiento() {
  const v = val('fecha_nacimiento');
  if (!v) return false;
  const dob = new Date(v);
  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
  return age >= 18;
}

function validatePassword() {
  const pw = val('pass');
  if (pw.length < 8) return false;
  if (!/[A-Z]/.test(pw)) return false;
  if (!/[a-z]/.test(pw)) return false;
  if (!/[0-9]/.test(pw)) return false;
  if (!/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\/\\|`~]/.test(pw)) return false;
  return true;
}

function validatePassConfirm() {
  const pw = val('pass');
  const pc = val('pass_confirm');
  const f = document.getElementById('f-pass_confirm');
  const okIcon = document.getElementById('passConfirmOk');
  const failIcon = document.getElementById('passConfirmFail');
  if (!pc) {
    f.classList.remove('valid', 'invalid');
    okIcon.style.display = 'none';
    failIcon.style.display = 'none';
    return false;
  }
  const match = pw === pc && pc.length > 0;
  f.classList.toggle('valid', match);
  f.classList.toggle('invalid', !match);
  okIcon.style.display = match ? 'block' : 'none';
  failIcon.style.display = match ? 'none' : 'block';
  return match;
}

function validatePlaca() {
  const v = val('placa');
  return /^[A-Z]{3}-?\d{3,4}$/.test(v);
}

function validateFechaFutura(id) {
  const v = val(id);
  if (!v) return false;
  return new Date(v) > new Date();
}

function validateDocFile(inputId) {
  const file = document.getElementById(inputId).files[0];
  if (!file) return false;
  const maxSize = 10 * 1024 * 1024;
  if (file.size > maxSize) return false;
  const allowed = ['application/pdf', 'image/jpeg', 'image/png'];
  if (!allowed.includes(file.type)) return false;
  return true;
}

function validateTerms() {
  const t = document.getElementById('acepta_terminos').checked;
  const p = document.getElementById('acepta_privacidad').checked;
  const err = document.getElementById('terms-error');
  const valid = t && p;
  err.style.display = valid ? 'none' : 'block';
  return valid;
}

function liveValidate(id) {
  switch(id) {
    case 'nombres': return setField('nombres', validateNames('nombres'), 'Min. 2 caracteres, solo letras y espacios.');
    case 'apellidos': return setField('apellidos', validateNames('apellidos'), 'Min. 2 caracteres, solo letras y espacios.');
    case 'correo': return setField('correo', validateEmail(), 'Ingresa un correo valido.');
    case 'celular': return setField('celular', validatePhone(), 'Celular: 10 digitos, debe empezar con 3.');
    case 'tipodoc': return setField('tipodoc', val('tipodoc') !== '', 'Selecciona un tipo de documento.');
    case 'numdoc': return setField('numdoc', validateNumDoc(), 'Entre 5 y 12 digitos.');
    case 'fecha_nacimiento': return setField('fecha_nacimiento', validateFechaNacimiento(), 'Debes tener al menos 18 anos.');
    case 'direccion': return setField('direccion', val('direccion').length >= 3, 'Ingresa tu direccion completa.');
    case 'ciudad': return setField('ciudad', val('ciudad').length >= 2, 'Ingresa tu ciudad.');
    case 'pass': return setField('pass', validatePassword(), 'Min. 8: mayuscula, minuscula, numero y especial.');
    case 'vehiculo': return setField('vehiculo', val('vehiculo') !== '', 'Selecciona un tipo de vehiculo.');
    case 'placa': return setField('placa', validatePlaca(), 'Formato: ABC123 o ABC-123 (3 letras + 3-4 digitos).');
    case 'licencia': return setField('licencia', val('licencia').length >= 3, 'Min. 3 caracteres.');
    case 'catlicencia': return setField('catlicencia', val('catlicencia') !== '', 'Selecciona la categoria.');
    case 'soat_vencimiento': return setField('soat_vencimiento', validateFechaFutura('soat_vencimiento'), 'La fecha debe ser futura.');
    case 'tecnomecanica_vencimiento': return setField('tecnomecanica_vencimiento', validateFechaFutura('tecnomecanica_vencimiento'), 'La fecha debe ser futura.');
  }
  return true;
}

function checkPassStrength() {
  const pw = val('pass');
  let score = 0;
  if (pw.length >= 8) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\/\\|`~]/.test(pw)) score++;

  const colors = ['#e5e7eb', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
  const labels = ['', 'Debil', 'Regular', 'Buena', 'Segura'];
  for (let i = 1; i <= 4; i++) {
    document.getElementById('ps' + i).style.background = i <= score ? colors[score] : '#e5e7eb';
  }
  document.getElementById('passHint').textContent = score > 0 ? labels[score] : '';
  document.getElementById('passHint').style.color = colors[score] || '#9ca3af';
}

function handleFile(type, input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    if (file.size > 10 * 1024 * 1024) {
      showToast({ title: 'Archivo muy grande', message: 'El maximo es 10MB.', type: 'error' });
      input.value = '';
      return;
    }
    const allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!allowed.includes(file.type)) {
      showToast({ title: 'Formato no valido', message: 'Solo se aceptan PDF, JPG o PNG.', type: 'error' });
      input.value = '';
      return;
    }
    const area = document.getElementById(type + '-area');
    const text = document.getElementById(type + '-text');
    area.classList.add('has-file');
    const sizeKB = (file.size / 1024).toFixed(1);
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    const sizeStr = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';
    text.innerHTML = `<strong><i class="fa-solid fa-check" style="color:var(--success);margin-right:6px;"></i>${file.name}</strong><br><small style="color:var(--text-secondary);">${sizeStr}</small>`;
  }
}

function validateStep1() {
  let ok = true;
  ok = setField('nombres', validateNames('nombres'), 'Min. 2 caracteres, solo letras y espacios.') && ok;
  ok = setField('apellidos', validateNames('apellidos'), 'Min. 2 caracteres, solo letras y espacios.') && ok;
  ok = setField('correo', validateEmail(), 'Ingresa un correo valido.') && ok;
  ok = setField('celular', validatePhone(), 'Celular: 10 digitos, debe empezar con 3.') && ok;
  ok = setField('tipodoc', val('tipodoc') !== '', 'Selecciona un tipo de documento.') && ok;
  ok = setField('numdoc', validateNumDoc(), 'Entre 5 y 12 digitos.') && ok;
  ok = setField('fecha_nacimiento', validateFechaNacimiento(), 'Debes tener al menos 18 anos.') && ok;
  ok = setField('direccion', val('direccion').length >= 3, 'Ingresa tu direccion completa.') && ok;
  ok = setField('ciudad', val('ciudad').length >= 2, 'Ingresa tu ciudad.') && ok;
  ok = setField('pass', validatePassword(), 'Min. 8: mayuscula, minuscula, numero y especial.') && ok;
  ok = validatePassConfirm() && ok;
  if (!validatePassConfirm()) setField('pass_confirm', false, 'Las contrasenas no coinciden.');
  return ok;
}

function validateStep2() {
  let ok = true;
  ok = setField('vehiculo', val('vehiculo') !== '', 'Selecciona un tipo de vehiculo.') && ok;
  ok = setField('placa', validatePlaca(), 'Formato: ABC123 o ABC-123 (3 letras + 3-4 digitos).') && ok;
  ok = setField('licencia', val('licencia').length >= 3, 'Min. 3 caracteres.') && ok;
  ok = setField('catlicencia', val('catlicencia') !== '', 'Selecciona la categoria.') && ok;
  return ok;
}

function validateStep3() {
  let ok = true;
  if (!validateDocFile('tarjeta-file')) {
    showToast({ title: 'Documento requerido', message: 'Sube la tarjeta de propiedad (max. 10MB, PDF/JPG/PNG).', type: 'error' });
    ok = false;
  }
  if (!validateDocFile('licencia-file')) {
    showToast({ title: 'Documento requerido', message: 'Sube la licencia de conduccion (max. 10MB, PDF/JPG/PNG).', type: 'error' });
    ok = false;
  }
  if (!validateDocFile('soat-file')) {
    showToast({ title: 'Documento requerido', message: 'Sube el SOAT (max. 10MB, PDF/JPG/PNG).', type: 'error' });
    ok = false;
  }
  if (!validateDocFile('tecnomecanica-file')) {
    showToast({ title: 'Documento requerido', message: 'Sube la tecnomecanica (max. 10MB, PDF/JPG/PNG).', type: 'error' });
    ok = false;
  }
  ok = setField('soat_vencimiento', validateFechaFutura('soat_vencimiento'), 'La fecha debe ser futura.') && ok;
  ok = setField('tecnomecanica_vencimiento', validateFechaFutura('tecnomecanica_vencimiento'), 'La fecha debe ser futura.') && ok;
  return ok;
}

function nextStep(from) {
  if (from === 1 && !validateStep1()) {
    showToast({ title: 'Corrige los errores', message: 'Revisa los campos resaltados.', type: 'warning' });
    return;
  }
  if (from === 2 && !validateStep2()) {
    showToast({ title: 'Corrige los errores', message: 'Revisa los campos resaltados.', type: 'warning' });
    return;
  }
  if (from === 3 && !validateStep3()) return;
  goStep(from + 1);
}

function buildSummary() {
  const docLabels = { CC: 'Cedula de Ciudadania', CE: 'Cedula de Extranjeria', TI: 'Tarjeta de Identidad', PAS: 'Pasaporte' };
  const vehLabels = { moto: 'Moto', carro: 'Carro', bicicleta: 'Bicicleta', camioneta: 'Camioneta' };
  const formatDate = (d) => { if (!d) return '-'; const p = d.split('-'); return p[2] + '/' + p[1] + '/' + p[0]; };

  function docUploaded(inputId) {
    const f = document.getElementById(inputId).files[0];
    return f ? f.name : 'No subido';
  }

  const html = `
    <div class="summary-section">
      <div class="summary-section-title"><i class="fa-solid fa-user" style="margin-right:6px;"></i>Datos Personales</div>
      <div class="summary-grid">
        <div class="summary-item"><span class="summary-item-label">Nombres</span><span class="summary-item-value">${val('nombres')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Apellidos</span><span class="summary-item-value">${val('apellidos')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Correo</span><span class="summary-item-value">${val('correo')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Celular</span><span class="summary-item-value">${val('celular')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Documento</span><span class="summary-item-value">${val('tipodoc')} - ${val('numdoc')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Fecha Nacimiento</span><span class="summary-item-value">${formatDate(val('fecha_nacimiento'))}</span></div>
        <div class="summary-item"><span class="summary-item-label">Direccion</span><span class="summary-item-value">${val('direccion')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Ciudad</span><span class="summary-item-value">${val('ciudad')}</span></div>
      </div>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-section">
      <div class="summary-section-title"><i class="fa-solid fa-motorcycle" style="margin-right:6px;"></i>Vehiculo</div>
      <div class="summary-grid">
        <div class="summary-item"><span class="summary-item-label">Tipo</span><span class="summary-item-value">${vehLabels[val('vehiculo')] || val('vehiculo')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Placa</span><span class="summary-item-value">${val('placa')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Licencia</span><span class="summary-item-value">${val('licencia')}</span></div>
        <div class="summary-item"><span class="summary-item-label">Categoria</span><span class="summary-item-value">${val('catlicencia')}</span></div>
      </div>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-section">
      <div class="summary-section-title"><i class="fa-solid fa-file-lines" style="margin-right:6px;"></i>Documentos</div>
      <div class="summary-doc-item"><i class="fa-solid fa-check-circle"></i> Tarjeta de Propiedad: ${docUploaded('tarjeta-file')}</div>
      <div class="summary-doc-item"><i class="fa-solid fa-check-circle"></i> Licencia de Conduccion: ${docUploaded('licencia-file')}</div>
      <div class="summary-doc-item"><i class="fa-solid fa-check-circle"></i> SOAT: ${docUploaded('soat-file')} — Vence: ${formatDate(val('soat_vencimiento'))}</div>
      <div class="summary-doc-item"><i class="fa-solid fa-check-circle"></i> Tecnomecanica: ${docUploaded('tecnomecanica-file')} — Vence: ${formatDate(val('tecnomecanica_vencimiento'))}</div>
    </div>
  `;
  document.getElementById('summaryCard').innerHTML = html;
}

function submitForm() {
  if (!validateTerms()) {
    showToast({ title: 'Terminos requeridos', message: 'Debes aceptar los Terminos y la Politica de Privacidad.', type: 'error' });
    return;
  }

  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.classList.add('loading');

  const formData = new FormData();
  formData.append('nombres', val('nombres'));
  formData.append('apellidos', val('apellidos'));
  formData.append('correo', val('correo'));
  formData.append('pass', val('pass'));
  formData.append('pass_confirm', val('pass_confirm'));
  formData.append('celular', val('celular'));
  formData.append('tipodoc', val('tipodoc'));
  formData.append('numdoc', val('numdoc'));
  formData.append('fecha_nacimiento', val('fecha_nacimiento'));
  formData.append('direccion', val('direccion'));
  formData.append('ciudad', val('ciudad'));
  formData.append('vehiculo', val('vehiculo'));
  formData.append('placa', val('placa').toUpperCase());
  formData.append('licencia', val('licencia'));
  formData.append('catlicencia', val('catlicencia'));
  formData.append('tarjeta', 'uploaded');
  formData.append('tarjeta-file', document.getElementById('tarjeta-file').files[0]);
  formData.append('licencia-file', document.getElementById('licencia-file').files[0]);
  formData.append('soat-file', document.getElementById('soat-file').files[0]);
  formData.append('soat_vencimiento', val('soat_vencimiento'));
  formData.append('tecnomecanica-file', document.getElementById('tecnomecanica-file').files[0]);
  formData.append('tecnomecanica_vencimiento', val('tecnomecanica_vencimiento'));
  formData.append('acepta_terminos', document.getElementById('acepta_terminos').checked ? '1' : '0');
  formData.append('acepta_privacidad', document.getElementById('acepta_privacidad').checked ? '1' : '0');

  fetch('<?= APP_URL ?>/api/repartidor/registro', {
    method: 'POST',
    body: formData
  })
  .then(r => r.text().then(text => ({ ok: r.ok, status: r.status, text: text })))
  .then(({ ok, status, text }) => {
    let data;
    try { data = JSON.parse(text); } catch(e) {
      showToast({ title: 'Error del servidor', message: 'Error interno. Intenta de nuevo.', type: 'error' });
      btn.disabled = false;
      btn.classList.remove('loading');
      return;
    }
    if (data.success) {
      showToast({ title: 'Solicitud enviada!', message: data.message || 'Tu solicitud fue enviada correctamente.', type: 'success', duration: 5000 });
      setTimeout(() => {
        window.location.href = data.redirect || '/repartidor/dashboard';
      }, 2000);
    } else {
      showToast({ title: 'Error', message: data.message || 'No se pudo enviar la solicitud.', type: 'error' });
      btn.disabled = false;
      btn.classList.remove('loading');
    }
  })
  .catch(() => {
    showToast({ title: 'Error de conexion', message: 'No pudimos conectar con el servidor.', type: 'error' });
    btn.disabled = false;
    btn.classList.remove('loading');
  });
}
</script>
<!-- Fin de la lógica del registro de repartidor -->
</body>
</html>
